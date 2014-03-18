<?php
/**
 * Fol\Http\Router\ErrorRoute
 *
 * Class to manage an error route
 */
namespace Fol\Http\Router;

use Fol\Http\Container;
use Fol\Http\Request;
use Fol\Http\Response;
use Fol\Http\HttpException;

class ErrorRoute extends Container
{
    public $target;

    /**
     * Constructor
     *
     * @param array $options One available option: target
     */
    public function __construct(array $config)
    {
        $this->target = $config['target'];
    }

    /**
     * Execute the route
     *
     * @param Fol\Http\HttpException
     * @param Fol\Http\Request
     * @param array $arguments The arguments passed to the controller (after $request and $response instances)
     */
    public function execute(HttpException $exception, Request $request, array $arguments = array())
    {
        ob_start();

        $response = new Response('', $exception->getCode() ?: 500);
        
        array_unshift($arguments, $request, $response);
        $this->set('exception', $exception);
        $request->route = $this;

        list($class, $method) = $this->target;

        $class = new \ReflectionClass($class);

        $controller = $class->hasMethod('__construct') ? $class->newInstanceArgs($arguments) : $class->newInstance();
        $return = $class->getMethod($method ?: '__invoke')->invokeArgs($controller, $arguments);

        if ($return instanceof Response) {
            $return->appendContent(ob_get_clean());

            $return->prepare($request);

            return $return;
        }

        $response->appendContent(ob_get_clean().$return);

        $response->prepare($request);

        return $response;
    }
}
