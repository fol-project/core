<?php
/**
 * Fol\Router\RouteFactory
 * 
 * Class to generate route classes
 * Based in PHP-Router library (https://github.com/dannyvankooten/PHP-Router)
 */
namespace Fol\Router;

use Fol\App;

class RouteFactory {
	private $app;

	public function __construct (App $app) {
		$this->app = $app;
	}

	public function getApp () {
		return $this->app;
	}

	private function getTarget ($target) {
		if (strpos($target, '::') === false) {
			$class = $target;
			$method = null;
		} else {
			list($class, $method) = explode('::', $target, 2);
		}

		$class = $this->app->namespace.'\\Controllers\\'.$class;

		return [$class, $method];
	}

	public function createRoute ($name, array $config = array()) {
		$config['target'] = $this->getTarget($config['target']);

		return new Route($name, $config, $app);
	}

	public function createFileRoute ($path, $target) {
		return new FileRoute($path, $this->getTarget($target), $app);
	}

	public function createErrorRoute ($target) {
		return new ErrorRoute($this->getTarget($target), $app);
	}
}
