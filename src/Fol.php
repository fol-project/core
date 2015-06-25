<?php
/**
 * Fol\App.
 *
 * Manages an app
 */

class Fol extends Fol\Container\Container
{
    protected static $globalContainer;

    public $config;

    private $providers = [];
    private $namespace;
    private $path;
    private $url;

    /**
     * Magic method to access to the global container
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        if (!isset(static::$globalContainer)) {
            static::$globalContainer = new Fol\Container\Container();
        }

        if (substr($name, -6) === 'Global') {
            $name = substr($name, 0, -6);

            if (method_exists(static::$globalContainer, $name)) {
                return call_user_func_array([static::$globalContainer, $name], $arguments);
            }
        }

        throw new BadMethodCallException("The method {$name} does not exists");
    }

    /**
     * Register the providers.
     *
     * @param array $providers
     */
    public function addProviders(array $providers)
    {
        foreach ($providers as $class) {
            $provider = new $class($this);

            if (!($provider instanceof Fol\ServiceProvider)) {
                throw new InvalidArgumentException("This provider is not valid");
            }

            if ($provider->provides() === false) {
                $provider->register();
            } else {
                $this->providers[] = $provider;
            }
        }
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

        return static::fixPath($this->path.'/'.implode('/', func_get_args()));
    }

    /**
     * Set the absolute url of the app.
     *
     * @param $url string
     */
    public function setUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("No valid url defined: '$url'");
        }

        $this->url = $url;
    }

    /**
     * Returns the absolute url of the app.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->url === null) {
            $this->setUrl((php_sapi_name() === 'cli-server') ? getenv('APP_CLI_SERVER_URL') : getenv('APP_URL'));
        }

        if (func_num_args() === 0) {
            return $this->url;
        }

        return $this->url.static::fixPath('/'.implode('/', func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if ($this->has($id)) {
            return parent::get($id);
        }

        foreach ($this->providers as $k => $provider) {
            if (in_array($id, $provider->provides())) {
                $provider->register();
                unset($this->providers[$k]);

                return parent::get($id);
            }
        }

        $className = $this->getNamespace($id);

        if (!class_exists($className)) {
            throw new Fol\Container\NotFoundException("{$id} has not found and '$className' does not exists");
        }

        if (func_num_args() === 1) {
            return new $className();
        }

        return (new ReflectionClass($className))->newInstanceArgs(array_slice(func_get_args(), 1));
    }

    /**
     * static function to fix paths '//' or '/./' or '/foo/../' in a path.
     *
     * @param string $path Path to resolve
     *
     * @return string
     */
    public static function fixPath($path)
    {
        $replace = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];

        do {
            $path = preg_replace($replace, '/', $path, -1, $n);
        } while ($n > 0);

        return $path;
    }
}
