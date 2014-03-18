<?php
/**
 * Fol\Http\Router\RouteFactory
 *
 * Class to generate route classes
 */
namespace Fol\Http\Router;

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
     * @param string $name     Route name
     * @param array  $config   Route configuration (path, target, etc)
     * @param string $basePath The path to prepend
     *
     * @return Fol\Http\Router\Route
     */
    public function createRoute ($name, array $config, $basePath)
    {
        $config['target'] = $this->getTarget($config['target']);

        if ($basePath) {
            $config['path'] = $basePath.$config['path'];
        }

        if (isset($config['path'][1])) {
            $config['path'] = rtrim($config['path'], '/');
        }

        if (strpos($config['path'], '{:') !== false) {
            return new RegexRoute($name, $config);
        }

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
