<?php
/**
 * Fol\App
 * 
 * This is the abstract class that all apps must extend. Provides the basic functionality parameters (paths, urls, namespace, parent, etc)
 */

namespace Fol;

abstract class App {
	private $services;


	/**
	 * Magic function to get some special properties.
	 * Instead calculate this on the __constructor, is better use __get to do not obligate to call this constructor in the extensions of this class
	 * 
	 * @param string $name The name of the property
	 * 
	 * @return string The property value or null
	 */
	public function __get ($name) {
		//Registered services
		if (($service = $this->get($name)) !== null) {
			return $this->$name = $service;
		}

		//The app name. (Web)
		if ($name === 'name') {
			return $this->name = substr(strrchr($this->namespace, '\\'), 1);
		}

		//The app namespace. (Apps\Web)
		if ($name === 'namespace') {
			return $this->namespace = (new \ReflectionClass($this))->getNameSpaceName();
		}

		//The app path (relative to root). (/web)
		if ($name === 'path') {
			return $this->path = preg_replace('|^'.BASE_PATH.'|', '', str_replace('\\', '/', dirname((new \ReflectionClass($this))->getFileName())));
		}

		//The app base url
		if ($name === 'url') {
			return $this->url = '';
		}
	}


	public function __invoke ($request = null) {
		throw new \Exception('This app is not callable. The magic method "__invoke" is required to use it.');
	}


	/**
	 * Register a new service
	 * 
	 * @param string $name The service name
	 * @param Closure $resolver A function that returns a service instance
	 */
	public function register ($name, \Closure $resolver = null) {
		if (is_array($name)) {
			foreach ($name as $name => $resolver) {
				$this->register($name, $resolver);
			}

			return;
		}

		$this->services[$name] = $resolver;
	}


	/**
	 * Deletes a service
	 * 
	 * @param string $name The service name
	 */
	public function unregister ($name) {
		unset($this->services[$name]);
	}


	/**
	 * Returns a service
	 *
	 * @param  string $name The service name
	 *
	 * @return mixed The result of the executed closure
	 */
	public function get ($name) {
		if (!isset($this->services[$name])) {
			return null;
		}

		if (func_num_args() === 1) {
			return $this->services[$name]();
		}

		return call_user_func_array($this->services[$name], array_slice(func_get_args(), 1));
	}


	/**
	 * Returns a class instance
	 *
	 * @param string $className The class name (must be in the same namespace than the app, for example: 'Controllers\Index' referers to 'Apps\Web\Controllers\Index');
	 *
	 * @return object A new instance of this class
	 */
	public function getClassInstance ($className) {
		$className = $this->namespace.'\\'.$className;

		if (func_num_args() === 1) {
			return new $className;
		}

		return (new \ReflectionClass($className))->newInstanceArgs(array_slice(func_get_args(), 1));
	}
}
