<?php
/**
 * Fol\App
 *
 * Manages an app
 */

namespace Fol;

class App
{
    private $services = [];
    private $namespace;
    private $path;
    private $url;
    private $environment;

    /**
     * Magic function to get registered services.
     *
     * @param string $name The name of the service
     *
     * @return null|mixed
     */
    public function __get($name)
    {
        if (($service = $this->get($name)) !== null) {
            return $this->$name = $service;
        }
    }

    /**
     * Register new services
     *
     * @param integer|string|array $name     The service name
     * @param \Closure             $resolver A function that returns a service instance
     */
    public function register($name, \Closure $resolver = null)
    {
        if (is_array($name)) {
            foreach ($name as $name => $resolver) {
                $this->register($name, $resolver);
            }

            return;
        }

        $this->services[$name] = $resolver;
    }

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
            return new $className();
        }

        return (new \ReflectionClass($className))->newInstanceArgs($args);
    }
}
