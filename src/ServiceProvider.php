<?php
namespace Fol;

/**
 * An abstract class to register services in a app
 */
abstract class ServiceProvider
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Returns the services provided by this provider
     * 
     * @return false|array
     */
    public function provides ()
    {
        return false;
    }

    /**
     * Register the service
     *
     * @param App $app
     */
    abstract public function register();
}
