<?php
/**
 * Fol\App
 *
 * This is the abstract class that all apps must extend.
 */

namespace Fol;

abstract class App
{
    use ServiceContainerTrait { get as private parentGet; }

    private $publicUrl;
    private $path;
    private $namespace;


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
     * Returns the absolute url of the public directory of the path
     *
     * @return string
     */
    public function getPublicUrl()
    {
        if ($this->publicUrl === null) {
            $this->publicUrl = BASE_URL.PUBLIC_DIR;
        }

        if (func_num_args() === 0) {
            return $this->publicUrl;
        }

        return $this->publicUrl.FileSystem::fixPath('/'.implode('/', func_get_args()));
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
        if (($return = $this->parentGet($name)) !== null) {
            return $return;
        }

        $className = $this->getNamespace($name);

        if (!class_exists($className)) {
            throw new \Exception("'$name' service is not defined and '$className' does not exists");
        }

        if (func_num_args() === 1) {
            return new $className;
        }

        return (new \ReflectionClass($className))->newInstanceArgs(array_slice(func_get_args(), 1));
    }
}
