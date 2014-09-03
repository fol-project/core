<?php
/**
 * Fol\App
 *
 * This is the abstract class that all apps must extend.
 */

namespace Fol;

abstract class App
{
    use ServiceContainerTrait;

    private $namespace;
    private $path;
    private $url;


    /**
     * Returns the namespace of the app
     *
     * @param string $namespace Optional namespace to append
     */
    public function getNamespace($namespace = null)
    {
        if ($this->namespace === null) {
            $this->namespace = (new \ReflectionClass($this))->getNameSpaceName();
        }

        if ($namespace === null) {
            return $this->namespace;
        }

        return $this->namespace.(($namespace[0] === '\\') ? '' : '\\').$namespace;
    }

    /**
     * Returns the absolute path of the app
     *
     * @return string
     */
    public function getPath()
    {
        if ($this->path === null) {
            $this->path = str_replace('\\', '/', dirname((new \ReflectionClass($this))->getFileName()));
        }

        if (func_num_args() === 0) {
            return $this->path;
        }

        return FileSystem::fixPath($this->path.'/'.implode('/', func_get_args()));
    }

    /**
     * Returns the absolute url of the app
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->url === null) {
            $this->url = static::getDefaultUrl();
        }

        if (func_num_args() === 0) {
            return $this->url;
        }

        return $this->url.FileSystem::fixPath('/'.implode('/', func_get_args()));
    }

    /**
     * Returns a registered service or a class instance
     *
     * @param string $name The service name
     *
     * @return mixed The result of the executed closure
     */
    public function get($name)
    {
        $args = array_slice(func_get_args(), 1);

        if (!empty($this->services[$name])) {
            return call_user_func_array($this->services[$name], $args);
        }

        $className = $this->getNamespace($name);

        if (!class_exists($className)) {
            throw new \Exception("'$name' service is not defined and '$className' does not exists");
        }

        if (empty($args)) {
            return new $className;
        }

        return (new \ReflectionClass($className))->newInstanceArgs($args);
    }

    /**
     * Calculate the public url of the app
     * 
     * @return string
     */
    protected function getDefaultUrl()
    {
        return BASE_URL;
    }
}
