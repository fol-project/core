<?php
/**
 * Fol\Router\Router
 * 
 * Class to manage all routes
 * Based in PHP-Router library (https://github.com/dannyvankooten/PHP-Router) and Aura-PHP.Router (https://github.com/auraphp/Aura.Router)
 */
namespace Fol\Router;

use Fol\App;
use Fol\Http\Request;
use Fol\Http\Response;
use Fol\Http\HttpException;

class Router {
	private $routes = [];
	private $errorController;
	private $routeFactory;
	private $baseurl;


	/**
	 * Constructor function. Defines the base url
	 * 
	 * @param Fol\Router\RouteFactory $routeFactory
	 */
	public function __construct (RouteFactory $routeFactory) {
		$this->routeFactory = $routeFactory;

		$this->setBaseUrl(BASE_URL);
	}


	/**
	 * Change the current base url
	 *
	 * @param string $baseurl The new baseurl
	 */
	public function setBaseUrl ($baseurl) {
		$components = parse_url($baseurl);

		$this->baseurl = [
			'host' => $components['scheme'].'://'.$components['host'].(isset($components['port']) ? ':'.$components['port'] : ''),
			'path' => isset($components['path']) ? $components['path'] : ''
		];

		foreach ($this->routes as $route) {
			$route->base = $this->baseurl['path'];
		}
	}


	/**
	 * Route factory method
	 * Maps the given URL to the given target.
	 *
	 * @param string $name string The route name.
	 * @param string $path string
	 * @param mixed $target The target of this route.
	 * @param array $config Array of optional arguments.
	 */
	public function map ($name, $path = null, $target = null, array $config = array()) {
		if (is_array($name)) {
			foreach ($name as $name => $config) {
				$config['base'] = $this->baseurl['path'];

				$this->routes[$name] = $this->routeFactory->createRoute($name, $config);
			}

			return;
		}

		$config['path'] = $path;
		$config['target'] = $target;
		$config['base'] = $this->baseurl['path'];

		if ($name === null) {
			$this->routes[] = $this->routeFactory->createRoute($name, $config);
		} else {
			$this->routes[$name] = $this->routeFactory->createRoute($name, $config);
		}
	}


	/**
	 * Error factory method
	 *
	 * Define the router used on errors
	 * @param mixed $target The target of this route
	 */
	public function setError ($target) {
		$this->errorController = $this->routeFactory->createErrorRoute($target);
	}


	/**
	 * Match given request url and request method and see if a route has been defined for it
	 * If so, return route's target
	 * If called multiple times
	 */
	public function match (Request $request) {
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
	public function getByName ($name) {
		if (!isset($this->routes[$name])) {
			return false;
		}

		return $this->routes[$name];
	}


	
	/**
	 * Reverse route a named route
	 * 
	 * @param string $name The name of the route to reverse route.
	 * @param array $params Optional array of parameters to use in URL
	 * @param bool $absolute Set true to get the absolute path (with basehost)
	 * 
	 * @return string The url to the route
	 */
	public function generate ($name, array $params = array(), $absolute = false) {
		if (!isset($this->routes[$name])) {
			throw new \Exception("No route with the name $name has been found.");
		}

		return ($absolute ? $this->baseurl['host'] : '').$this->baseurl['path'].$this->routes[$name]->generate($params);
	}


	/**
	 * Handle a request
	 * 
	 * @param Fol\Request $request
	 *
	 * @throws Exception If no errorController is defined and an exception is thrown
	 * 
	 * @return Fol\Response
	 */
	public function handle (Request $request, App $app) {
		if (($route = $this->match($request))) {
			try {
				$response = $route->execute($request, $app);
			} catch (HttpException $exception) {
				if ($this->errorController) {
					return $this->errorController->execute($exception, $request, $app);
				}

				throw $exception;
			}

			return $response;
		}

		$exception = new HttpException('Not found', 404);

		if ($this->errorController) {
			return $this->errorController->execute($exception, $request, $app);
		}

		throw $exception;
	}
}
