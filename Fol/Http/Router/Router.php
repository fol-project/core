<?php
/**
 * Fol\Http\Router\Router
 *
 * Class to manage all routes
 */
namespace Fol\Http\Router;

use Fol\Http\Request;
use Fol\Http\Response;
use Fol\Http\HttpException;

class Router
{
    private $routes = [];
    private $errorController;
    private $routeFactory;

    private $basePath;
    private $defaults;


    /**
     * Constructor function. Defines the base url
     *
     * @param Fol\Router\RouteFactory $routeFactory
     */
    public function __construct(RouteFactory $routeFactory)
    {
        $this->routeFactory = $routeFactory;

        $components = parse_url(BASE_URL);

        $this->setDefaults($components['scheme'], $components['host'], isset($components['port']) ? $components['port'] : null);
        $this->setBasePath(isset($components['path']) ? $components['path'] : '');
    }


    /**
     * Change the base path
     *
     * @param string $basePath
     */
    public function setDefaults($scheme, $host, $port = null)
    {
        $this->defaults = [
            'scheme' => $scheme,
            'host' => $host,
            'port' => $port
        ];
    }


    /**
     * Change the base path
     *
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }


    /**
     * Route factory method
     * Maps the given URL to the given target.
     *
     * @param string $name   string The route name.
     * @param array  $config Array of optional arguments.
     */
    public function map ($name, array $config = array())
    {
        if (is_array($name)) {
            foreach ($name as $name => $config) {
                $this->routes[$name] = $route = $this->routeFactory->createRoute($name, $config, $this->basePath);
            }

            return;
        }

        $route = $this->routeFactory->createRoute($name, $config, $this->basePath);

        if ($name === null) {
            $this->routes[] = $route;
        } else {
            $this->routes[$name] = $route;
        }
    }

    /**
     * Error factory method
     *
     * Define the router used on errors
     * @param mixed $target The target of this route
     */
    public function setError($target)
    {
        $this->errorController = $this->routeFactory->createErrorRoute($target);
    }

    /**
     * Match given request url and request method and see if a route has been defined for it
     * If so, return route's target
     * If called multiple times
     */
    public function match(Request $request)
    {
        foreach ($this->routes as $route) {
            if ($route->match($request)) {
                return $route;
            }
        }

        return false;
    }


    /**
     * Search a router by name
     *
     * @param string $name The route name
     *
     * @return Fol\Http\Route The route found or false
     */
    public function getByName($name)
    {
        if (!isset($this->routes[$name])) {
            return false;
        }

        return $this->routes[$name];
    }



    /**
     * Reverse route a named route
     *
     * @param string $name     The name of the route to reverse route.
     * @param array  $params   Optional array of parameters to use in URL
     *
     * @return string The url to the route
     */
    public function getUrl ($name, array $params = array())
    {
        if (!isset($this->routes[$name])) {
            throw new \Exception("No route with the name $name has been found.");
        }

        return $this->routes[$name]->generate($this->defaults, $params);
    }


    /**
     * Handle a request
     *
     * @param Fol\Request $request
     * @param array $arguments The arguments passed to the controller (after $request and $response instances)
     *
     * @throws Exception If no errorController is defined and an exception is thrown
     *
     * @return Fol\Response
     */
    public function handle(Request $request, array $arguments = array())
    {
        if (($route = $this->match($request))) {
            try {
                $response = $route->execute($request, $arguments);
            } catch (HttpException $exception) {
                if ($this->errorController) {
                    return $this->errorController->execute($exception, $request, $arguments);
                }

                throw $exception;
            }

            return $response;
        }

        $exception = new HttpException('Not found', 404);

        if ($this->errorController) {
            return $this->errorController->execute($exception, $request, $arguments);
        }

        throw $exception;
    }
}
