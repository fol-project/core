<?php
/**
 * Fol\App
 *
 * This is the abstract class that all apps must extend. Provides the basic functionality parameters (paths, urls, namespace, parent, etc)
 */

namespace Fol;

abstract class App
{
    private $services;
    private $publicUrl;
    private $path;
    private $namespace;


    /**
     * Magic function to get registered services.
     *
     * @param string $name The name of the service
     *
     * @return string The service instance or null
     */
    public function __get($name)
    {
        if (($service = $this->get($name)) !== null) {
            return $this->$name = $service;
        }
    }


    abstract public function __invoke(Http\Request $request);


    /**
     * Register a new service
     *
     * @param string|int|array $name     The service name
     * @param \Closure         $resolver A function that returns a service instance
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
        if (isset($this->services[$name])) {
            if (func_num_args() === 1) {
                return $this->services[$name]();
            }

            return call_user_func_array($this->services[$name], array_slice(func_get_args(), 1));
        }

        $className = $this->getNamespace($name);

        if (!class_exists($className)) {
            throw new \Exception("'$name' does not exist and it not registered");
        }

        if (func_num_args() === 1) {
            return new $className;
        }

        return (new \ReflectionClass($className))->newInstanceArgs(array_slice(func_get_args(), 1));
    }
}
