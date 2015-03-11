<?php
/**
 * Fol\App.
 *
 * Manages an app
 */

namespace Fol;

use Fol as FolGlobal;

class App extends Container\Container
{
    public $config;

    private $providers = [];
    private $namespace;
    private $path;
    private $url;
    private $environment;

    /**
     * Constructor.
     */
    final public function __construct(Config $config = null)
    {
        $this->config = ($config === null) ? new Config($this->getPath('config')) : $config;

        $this->init();
    }

    /**
     * Init the app
     */
    protected function init()
    {
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

            if (!($provider instanceof ServiceProvider)) {
                throw new \InvalidArgumentException("This provider is not valid");
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
            $this->namespace = (new \ReflectionClass($this))->getNameSpaceName();
        }

        if ($namespace === null) {
            return $this->namespace;
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

        return FolGlobal::fixPath($this->path.'/'.implode('/', func_get_args()));
    }

    /**
     * Set the absolute url of the app.
     *
     * @param $url string
     */
    public function setUrl($url)
    {
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
            $config = $this->config->get('app', [
                'server_cli_port' => 80,
                'base_url' => 'http://localhost',
            ]);

            if (php_sapi_name() === 'cli-server') {
                $this->setUrl('http://127.0.0.1:'.$config['server_cli_port']);
            } else {
                $this->setUrl($config['base_url']);
            }
        }

        if (func_num_args() === 0) {
            return $this->url;
        }

        return $this->url.FolGlobal::fixPath('/'.implode('/', func_get_args()));
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
            throw new Container\NotFoundException("{$id} has not found and '$className' does not exists");
        }

        if (func_num_args() === 1) {
            return new $className();
        }

        return (new \ReflectionClass($className))->newInstanceArgs(array_slice(func_get_args(), 1));
    }
}
