<?php
/**
 * Fol\Http\Router\Route
 *
 * Class to manage a http route
 */
namespace Fol\Http\Router;

use Fol\ContainerTrait;
use Fol\Http\Request;
use Fol\Http\Response;
use Fol\Http\HttpException;

class Route implements \ArrayAccess
{
    use ContainerTrait;

    public $name;
    public $target;

    public $ip;
    public $method;
    public $scheme;
    public $host;
    public $port;
    public $path;
    public $format;
    public $language;


    /**
     * Constructor
     *
     * @param string $name   The route name
     * @param array  $config The available options
     * @param mixed  $target The route target
     */
    public function __construct($name, array $config = array(), $target)
    {
        $this->name = $name;
        $this->target = $target;


        foreach (['ip', 'method', 'scheme', 'host', 'port', 'path', 'format', 'language'] as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }
    }


    /**
     * Check the request
     *
     * @param Request $request The request to check
     * @param array   $params The params to check
     *
     * @return bool
     */
    public function checkRequest(Request $request, array $params)
    {
        foreach ($params as $param) {
            if (($value = $this->$param) === null) {
                continue;
            }

            $method = "get{$param}";

            if (is_array($value)) {
                if (!in_array($request->$method(), $value, true)) {
                    return false;
                }
            } else if ($request->$method() !== $value) {
                return false;
            }
        }

        return true;
    }


    /**
     * Check if the route match with the request
     *
     * @param Request The request to check
     *
     * @return bool
     */
    public function match(Request $request)
    {
        return $this->checkRequest($request, ['ip', 'method', 'scheme', 'host', 'port', 'path', 'format', 'language']);
    }


    /**
     * Get the route properties
     * 
     * @param array $properties The properties to return
     * 
     * @return array
     */
    protected function getProperties (array $properties)
    {
        $values = [];

        foreach ($properties as $name) {
            $values[$name] = is_array($this->$name) ? $this->$name[0] : (string) $this->$name;
        }

        return $values;
    }


    /**
     * Generate an url using the properties values
     * 
     * @param array $properties The url properties
     * 
     * @return string
     */
    protected static function buildUrl(array $properties)
    {
        $url = '';

        if ($properties['scheme']) {
            $url .= $properties['scheme'].':';
        }

        if ($properties['host']) {
            $url .= '//'.$properties['host'];
        }

        if ($properties['port'] && $properties['port'] != 80) {
            $url .= ':'.$properties['port'];
        }

        if ($properties['path']) {
            $url .= $properties['path'];

            if ($properties['format']) {
                $url .= '.'.$properties['format'];
            }
        }

        if ($properties['query']) {
            $url .= '?'.http_build_query($properties['query']);
        }

        return $url;
    }


    /**
     * Reverse the route
     *
     * @param array $parameters Optional array of parameters to use in URL
     *
     * @return string The url to the route
     */
    public function generate(array $parameters = array())
    {
        $values = $this->getProperties(['scheme', 'host', 'port', 'path', 'format']);

        return Request::buildUrl($values['scheme'], $values['host'], $values['port'], $values['path'], $values['format'], $parameters);
    }


    /**
     * Reverse the route, returning a Request object
     *
     * @param array $parameters Optional array of parameters to use in URL
     *
     * @return Request The request instance
     */
    public function generateRequest(array $parameters = array())
    {
        $request = new Request($this->generate($parameters));

        if ($this->method) {
            $request->setMethod(is_array($this->method) ? $this->method[0] : $this->method);
        }

        if ($this->ip) {
            $request->setIp(is_array($this->ip) ? $this->ip[0] : $this->ip);
        }

        if ($this->language) {
            $request->setLanguage(is_array($this->language) ? $this->language[0] : $this->language);
        }

        return $request;
    }


    /**
     * Execute the route and returns the response object
     *
     * @param Request $request   The request to send to controller
     * @param array   $arguments The arguments passed to the controller (after $request and $response instances)
     *
     * @return Response
     */
    public function execute(Request $request, array $arguments = array())
    {
        ob_start();

        $return = '';
        $response = new Response;

        array_unshift($arguments, $request, $response);
        $request->route = $this;

        try {
            if (!is_array($this->target) || is_object($this->target[0])) {
                $return = call_user_func_array($this->target, $arguments);
            } else {
                list($class, $method) = $this->target;

                $class = new \ReflectionClass($class);

                $controller = $class->hasMethod('__construct') ? $class->newInstanceArgs($arguments) : $class->newInstance();

                $return = $class->getMethod($method)->invokeArgs($controller, $arguments);

                unset($controller);
            }
        } catch (\Exception $exception) {
            ob_clean();

            if (!($exception instanceof HttpException)) {
                $exception = new HttpException('Error processing request', 500, $exception);
            }

            throw $exception;
        }

        if ($return instanceof Response) {
            $return->write(ob_get_clean());

            $return->prepare($request);

            return $return;
        }

        $response->write(ob_get_clean().$return);

        $response->prepare($request);

        return $response;
    }
}
