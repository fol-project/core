<?php
/**
 * Fol\Http\Router\RegexRoute
 *
 * Class to manage a http route using regular expressions for the path
 */
namespace Fol\Http\Router;

use Fol\Http\Request;

class RegexRoute extends Route
{
    protected $regex;
    protected $wildcard;


    /**
     * Constructor
     *
     * @param string $name   The route name
     * @param array  $config The available options
     */
    public function __construct ($name, array $config = array())
    {
        parent::__construct($name, $config);
        $this->setRegex();
    }


    /**
     * Generates the path regex
     */
    private function setRegex()
    {
        if (substr($this->path, -2) === '/*') {
            $this->path = substr($this->path, 0, -2).'(/{:__wildcard__:(.*)})?';
            $this->wildcard = '__wildcard__';
        }

        if (preg_match('/\/\{:([\w-]+)([\+\*])\}$/i', $this->path, $matches)) {
            $this->wildcard = $matches[1];
            $pos = strrpos($this->path, $matches[0]);

            if ($matches[2] === '*') {
                $this->path = substr($this->path, 0, $pos)."(/{:{$this->wildcard}:(.*)})?";
            } else {
                $this->path = substr($this->path, 0, $pos)."/{:{$this->wildcard}:(.+)}";
            }
        }

        unset($matches);

        if (preg_match_all("/\{:(.*?)(:(.*?))?\}/", $this->path, $matches, PREG_SET_ORDER)) {
            $filters = [];

            foreach ($matches as $match) {
                $whole = $match[0];
                $name = $match[1];

                if (isset($match[3])) {
                    $filters[$name] = ($match[3] === '?') ? '([^/]+)?' : $match[3];
                    $this->path = str_replace($whole, "{:$name}", $this->path);
                } else {
                    $filters[$name] = '([^/]+)';
                }
            }

            $this->regex = $this->path;

            if ($filters) {
                $keys = $vals = [];

                foreach ($filters as $name => $filter) {
                    if ($filter[0] !== '(') {
                        throw new \Exception("Filter for parameter '$name' must start with '('.");
                    } elseif (substr($filter, -1) === '?') {
                        $keys[] = "/{:$name}";
                        $vals[] = "(/(?P<$name>".substr($filter, 1, -1).')?';
                    } else {
                        $keys[] = "{:$name}";
                        $vals[] = "(?P<$name>".substr($filter, 1);
                    }
                }

                $this->regex = str_replace($keys, $vals, $this->regex);
            }
        }

        $this->path = str_replace(['(', ')', '?', '*'], '', $this->path);
        $this->regex = '#^'.$this->regex.'$#';
    }


    /**
     * Check the regex of the request
     *
     * @param string $path The path
     *
     * @return bool
     */
    public function checkRegex($path)
    {
        if (preg_match($this->regex, $path, $matches)) {
            return $matches;
        }

        return false;
    }


    /**
     * Check if the route match with the request
     *
     * @param Request $request The request to check
     *
     * @return bool
     */
    public function match(Request $request)
    {
        if (!$this->checkRequest($request, ['ip', 'method', 'scheme', 'host', 'port', 'format'])) {
            return false;
        }

        if (($matches = $this->checkRegex($request->getPath())) === false) {
            return false;
        }

        $this->set($matches);

        return true;
    }


    /**
     * Reverse the route
     *
     * @param array $defaults   Defaults values for scheme, host, port, path and format
     * @param array $parameters Optional array of parameters to use in URL
     *
     * @return string The url to the route
     */
    public function generate (array $defaults, array $parameters = array())
    {
        $path = $this->path;

        if ($this->wildcard && !isset($parameters[$this->wildcard])) {
            $parameters[$this->wildcard] = '';
        }

        foreach ($parameters as $name => $value) {
            if (strpos($path, "{:$name}") !== false) {
                $path = str_replace("{:$name}", rawurlencode($value), $path);
                unset($parameters[$name]);
            }
        }

        $values = $this->getProperties(['scheme', 'host', 'port', 'format'], $defaults);
        $values['path'] = $path;

        return static::buildUrl($values, $parameters);
    }
}
