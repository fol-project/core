<?php
namespace Fol\Container;

use Interop\Container\ContainerInterface;

/**
 * Fol\Container
 *
 * Universal dependency injection container
 */
class Container implements ContainerInterface
{
    private $containers = [];
    private $registry = [];
    private $services = [];

    
    /**
     * Register new services
     *
     * @param integer|string $id       The service id
     * @param \Closure       $resolver A function that returns a service instance
     * @param boolean        $single   Whether the same instance should be return each time
     */
    public function register($id, \Closure $resolver = null, $single = true)
    {
        $this->registry[$id] = [$resolver, $single];
    }

    /**
     * Add new containers
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

        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        throw new NotFoundException("{$id} has not found");
    }

    /**
     * Set manually new services
     * 
     * @param integer|string $id
     * @param mixed          $service
     */
    public function set($id, $service)
    {
        $this->services[$id] = $service;
    }

    /**
     * Deletes a service
     *
     * @param integer|string $id
     */
    public function delete($id)
    {
        unset($this->services[$id]);
    }

    /**
     * Creates a new instance from registry
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