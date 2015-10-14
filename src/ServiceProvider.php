<?php

namespace Fol;

use Interop\Container\ContainerInterface;

/**
 * An abstract class to register services in a app.
 */
abstract class ServiceProvider
{
    protected $app;

    /**
     * Set the app.
     *
     * @param ContainerInterface $app
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Returns the services provided by this provider.
     *
     * @return false|array
     */
    public function provides()
    {
        return false;
    }

    /**
     * Register the service.
     */
    abstract public function register();
}
