<?php

namespace Fol;

use Interop\Container\ContainerInterface;

/**
 * Fol\Container.
 *
 * Universal dependency injection container
 */
class Container implements ContainerInterface
{
    private $containers = [];
    private $registry = [];
    private $services = [];
    private $providers = [];

    /**
     * Register new services.
     *
     * @param int|string $id       The service id
     * @param \Closure   $resolver A function that returns a service instance
     * @param bool       $single   Whether the same instance should be return each time
     */
    public function register($id, \Closure $resolver = null, $single = true)
    {
        $this->registry[$id] = [$resolver, $single];
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
                throw new InvalidArgumentException('This provider is not valid');
            }

            if ($provider->provides() === false) {
                $provider->register();
            } else {
                $this->providers[] = $provider;
            }
        }
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
     * {@inheritdoc}
     */
    public function has($id)
    {
        if (isset($this->registry[$id]) || isset($this->services[$id])) {
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
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        if (($service = $this->getFromRegistry($id)) !== false) {
            return $service;
        }

        foreach ($this->providers as $k => $provider) {
            if (in_array($id, $provider->provides())) {
                $provider->register();
                unset($this->providers[$k]);

                return $this->get($id);
            }
        }

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        throw new ContainerNotFoundException("{$id} has not found");
    }

    /**
     * Set manually new services.
     *
     * @param int|string $id
     * @param mixed      $service
     */
    public function set($id, $service)
    {
        $this->services[$id] = $service;
    }

    /**
     * Deletes a service.
     *
     * @param int|string $id
     */
    public function delete($id)
    {
        unset($this->services[$id]);
    }

    /**
     * Creates a new instance from registry.
     *
     * @param string $id
     *
     * @throws ContainerException
     *
     * @return mixed
     */
    protected function getFromRegistry($id)
    {
        if (!isset($this->registry[$id])) {
            return false;
        }

        try {
            $service = call_user_func($this->registry[$id][0]);

            if ($this->registry[$id][1]) {
                $this->services[$id] = $service;
            }

            return $service;
        } catch (\Exception $exception) {
            throw new ContainerException("Error on retrieve {$id}");
        }
    }
}
