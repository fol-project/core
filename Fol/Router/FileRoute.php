<?php
/**
 * Fol\Router\FileRoute
 * 
 * Class to manage a route to a file
 * Based in PHP-Router library (https://github.com/dannyvankooten/PHP-Router) and Aura-PHP.Router (https://github.com/auraphp/Aura.Router)
 */
namespace Fol\Router;

use Fol\Http\Request;
use Fol\Http\Response;
use Fol\Http\HttpException;
use Fol\App;

class FileRoute {
	private $cachedPath;
	private $originPath;
	private $filename;
	private $target;
	private $app;

	public function __construct ($cachedPath, $originPath, $target, App $app = null) {
		$this->cachedPath = $cachedPath;
		$this->originPath = $originPath;
		$this->target = $target;
		$this->app = $app;
	}

	public function getType () {
		return 'file';
	}

	public function getTarget () {
		return $this->target;
	}

	public function checkPath ($request) {
		return (strpos($request->getPath(true), $this->cachedPath) === 0);
	}

	public function match ($request) {
		if (strpos($request->getPath(true), $this->cachedPath) !== 0) {
			return false;
		}

		$this->filename = substr($request->getPath(true), strlen($this->cachedPath));

		return is_file(BASE_PATH.$this->originPath.$this->filename);
	}


	public function execute ($request) {
		ob_start();

		$return = '';
		$response = $request->generateResponse();

		$request->parameters->set('file', [
			'name' => $this->filename,
			'origin' => $this->originPath.$this->filename,
			'cached' => $this->cachedPath.$this->filename
		]);

		try {
			list($class, $method) = $this->target;

			$class = new \ReflectionClass($class);
			$controller = $class->newInstanceWithoutConstructor();
			$controller->app = $this->app;
			$controller->route = $this;

			if (($constructor = $class->getConstructor())) {
				$constructor->invoke($controller, $request, $response);
			}

			if ($method) {
				$return = $class->getMethod($method)->invoke($controller, $request, $response);
			} else {
				$return = $controller($request, $response);
			}

			unset($controller);
		} catch (\Exception $exception) {
			ob_clean();

			if (!($exception instanceof HttpException)) {
				$exception = new HttpException('Error processing request', 500, $exception);
			}

			throw $exception;
		}

		if ($return instanceof Response) {
			$return->appendContent(ob_get_clean());
			
			return $return;
		}

		$response->appendContent(ob_get_clean().$return);

		return $response;
	}
}
