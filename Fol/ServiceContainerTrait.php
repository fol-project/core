<?php
/**
 * Fol\Http\Container
 *
 * Simple class used to store variables
 */
namespace Fol;

trait ServiceContainerTrait
{
    private $services = [];

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

    /**
     * Define new services
     *
     * @param string|array $name     The service name
     * @param \Closure     $resolver A function that returns a service instance
     */
    public function define($name, \Closure $resolver = null)
    {
        if (is_array($name)) {
            foreach ($name as $name => $resolver) {
                $this->define($name, $resolver);
            }

            return;
        }

        $this->services[$name] = $resolver;
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
        if (!empty($this->services[$name])) {
            return call_user_func_array($this->services[$name], array_slice(func_get_args(), 1));
        }
    }
}
