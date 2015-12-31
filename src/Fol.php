<?php

use Interop\Container\ContainerInterface;
use Fol\NotFoundException;
use Fol\ContainerException;
use Fol\ServiceProviderInterface;

/**
 * Manages an app.
 */
class Fol implements ContainerInterface, ArrayAccess
{
    private $containers = [];
    private $services = [];
    private $namespace;
    private $path;
    private $url;

    /**
     * Check whether a value exists.
     * 
     * @see ArrayAccess
     * 
     * @param string $id
     */
    public function offsetExists($id)
    {
        if (isset($this->services[$id])) {
            return true;
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a value.
     * 
     * @see ArrayAccess
     * 
     * @param string $id
     */
    public function offsetGet($id)
    {
        if (isset($this->services[$id])) {
            $value = $this->services[$id];

            if ($value instanceof Closure) {
                try {
                    return $this->services[$id] = $value($this);
                } catch (Exception $exception) {
                    throw new ContainerException("Error retrieving {$id}: {$exception->getMessage()}");
                }
            }

            return $value;
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        throw new NotFoundException("Identifier {$id} is not defined");
    }

    /**
     * Set a new value.
     * 
     * @see ArrayAccess
     * 
     * @param string $id
     * @param mixed  $value
     */
    public function offsetSet($id, $value)
    {
        $this->services[$id] = $value;
    }

    /**
     * Removes a value.
     * 
     * @see ArrayAccess
     * 
     * @param string $id
     */
    public function offsetUnset($id)
    {
        unset($this->services[$id]);
    }

    /**
     * Register new service provider.
     *
     * @param ServiceProviderInterface $provider
     */
    public function register(ServiceProviderInterface $provider)
    {
        $provider->register($this);
    }

    /**
     * Add new containers.
     *
     * @param ContainerInterface $container
     */
    public function add(ContainerInterface $container)
    {
        $this->containers[] = $container;
    }

    /**
     * @see ContainerInterface
     * 
     * {@inheritdoc}
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }

    /**
     * @see ContainerInterface
     * 
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->offsetExists($id)) {
            return $this->offsetGet($id);
        }

        $class = $this->getNamespace($id);

        if (class_exists($class)) {
            if (func_num_args() === 1) {
                return new $class();
            }

            return (new ReflectionClass($class))->newInstanceArgs(array_slice(func_get_args(), 1));
        }

        throw new NotFoundException("Identifier {$id} is not defined");
    }

    /**
     * Set a variable.
     * 
     * @param string $id
     * @param mixed  $value
     */
    public function set($id, $value)
    {
        $this->offsetSet($id, $value);
    }

    /**
     * Returns the namespace of the app.
     *
     * @param string $namespace Optional namespace to append
     */
    public function getNamespace($namespace = null)
    {
        if ($this->namespace === null) {
            $this->namespace = (new ReflectionClass($this))->getNameSpaceName();
        }

        if ($namespace === null) {
            return $this->namespace;
        }

        if ($this->namespace === '') {
            return $namespace;
        }

        return $this->namespace.(($namespace[0] === '\\') ? '' : '\\').$namespace;
    }

    /**
     * Set the absolute path of the app.
     * 
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Returns the absolute path of the app.
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

        return self::fixPath($this->path.'/'.implode('/', func_get_args()));
    }

    /**
     * Set the absolute url of the app.
     *
     * @param $url string
     */
    public function setUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(sprintf('No valid url defined (%s)', $url));
        }

        $url = parse_url($url);

        $this->url = [
            sprintf('%s://%s%s', $url['scheme'], $url['host'], (isset($url['port']) ? ':'.$url['port'] : '')),
            isset($url['path']) ? $url['path'] : '',
        ];
    }

    /**
     * Returns the url host of the app.
     *
     * @return string
     */
    public function getUrlHost()
    {
        if ($this->url === null) {
            throw new RuntimeException('No url provided');
        }

        return $this->url[0];
    }

    /**
     * Returns the url path of the app.
     *
     * @return string
     */
    public function getUrlPath()
    {
        if ($this->url === null) {
            throw new RuntimeException('No url provided');
        }

        if (func_num_args() === 0) {
            return $this->url[1];
        }

        return $this->url[1].self::fixPath('/'.implode('/', func_get_args()));
    }

    /**
     * Returns the absolute url of the app.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->getUrlHost().call_user_func_array([$this, 'getUrlPath'], func_get_args());
    }

    /**
     * helper function to fix paths '//' or '/./' or '/foo/../' in a path.
     *
     * @param string $path Path to resolve
     *
     * @return string
     */
    private static function fixPath($path)
    {
        $replace = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];

        do {
            $path = preg_replace($replace, '/', $path, -1, $n);
        } while ($n > 0);

        return $path;
    }
}
