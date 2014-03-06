<?php
/**
 * Fol\Router\RouteFactory
 *
 * Class to generate route classes
 * Based in PHP-Router library (https://github.com/dannyvankooten/PHP-Router)
 */
namespace Fol\Router;

class RouteFactory
{
    private $namespace;

    /**
     * Constructor
     *
     * @param string $namespace The namespace where the controllers are
     */
    public function __construct($namespace = '')
    {
        $this->namespace = $namespace;
    }

    /**
     * Generates the target of the route
     *
     * @param string $target (For example: ControllerClass::method)
     *
     * @return array
     */
    private function getTarget($target)
    {
        if (strpos($target, '::') === false) {
            $class = $target;
            $method = null;
        } else {
            list($class, $method) = explode('::', $target, 2);
        }

        $class = "{$this->namespace}\\{$class}";

        return [$class, $method];
    }

    /**
     * Creates a new route instance
     *
     * @param string $name   Route name
     * @param array  $config Route configuration (path, target, etc)
     *
     * @return Fol\Router\Route
     */
    public function createRoute ($name, array $config = array())
    {
        $config['target'] = $this->getTarget($config['target']);

        return new Route($name, $config);
    }

    /**
     * Creates a new error route instance
     *
     * @param string $target The error target (ControllerClass::method)
     *
     * @return Fol\Router\ErrorRoute
     */
    public function createErrorRoute($target)
    {
        return new ErrorRoute(['target' => $this->getTarget($target)]);
    }
}
