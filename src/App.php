<?php
/**
 * Fol\App
 *
 * Manages an app
 */

namespace Fol;

class App extends Container\Container
{
    private $namespace;
    private $path;
    private $url;
    private $environment;

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

        return Fol::fixPath($this->path.'/'.implode('/', func_get_args()));
    }

    /**
     * Set the absolute url of the app
     *
     * @param $url string
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Returns the absolute url of the app
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->url === null) {
            $this->url = Fol::getEnv('BASE_URL');
        }

        if (func_num_args() === 0) {
            return $this->url;
        }

        return $this->url.Fol::fixPath('/'.implode('/', func_get_args()));
    }

    /**
     * Set the app environment name
     *
     * @param string $name
     */
    public function setEnvironment($name)
    {
        $this->environment = $name;
    }

    /**
     * Returns the app environment name
     *
     * @return string
     */
    public function getEnvironment()
    {
        if ($this->environment === null) {
            $this->environment = Fol::getEnv('ENVIRONMENT');
        }

        return $this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return parent::get($id);
        }

        $className = $this->getNamespace($id);

        if (!class_exists($className)) {
            throw new Container\NotFoundException("{$id} has not found and '$className' does not exists");
        }

        if (func_num_args() === 1) {
            return new $className();
        }

        return (new \ReflectionClass($className))->newInstanceArgs(array_slice(func_get_args(), 1));
    }
}