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
	 * Returns the absolute path of the app
	 * 
	 * @param string $path1, $path2, ... Optional paths to append
	 */
	public function getPath () {
		if (func_num_args() === 0) {
			return BASE_PATH.$this->path.$path;
		}

		return BASE_PATH.$this->path.str_replace('//', '/', '/'.implode('/', func_get_args()));
	}


	/**
	 * Returns the absolute url of the app
	 * 
	 * @param string $path1, $path2, ... Optional paths to append
	 */
	public function getUrl () {
		if (func_num_args() === 0) {
			return BASE_HOST.BASE_URL.$this->path.$path;
		}

		return BASE_HOST.BASE_URL.$this->path.str_replace('//', '/', '/'.implode('/', func_get_args()));
	}


	/**
	 * Returns a registered service or a class instance 
	 *
	 * @param  string $name The service name
	 *
	 * @return mixed The result of the executed closure
	 */
	public function get ($name) {
		if (!isset($this->services[$name])) {
			$className = $this->namespace.'\\'.$className;

			if (!class_exists($className)) {
				throw new \Exception("The class '$name' does not exist and it not registered");
			}

			if (func_num_args() === 1) {
				return new $className;
			}

			return (new \ReflectionClass($className))->newInstanceArgs(array_slice(func_get_args(), 1));
		}

		if (func_num_args() === 1) {
			return $this->services[$name]();
		}

		return call_user_func_array($this->services[$name], array_slice(func_get_args(), 1));
	}
}
