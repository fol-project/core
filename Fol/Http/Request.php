<?php
/**
 * Fol\Http\Request
 *
 * Class to manage the http request data
 */
namespace Fol\Http;

class Request
{
    private $ip;
    private $method = 'GET';
    private $scheme;
    private $host;
    private $port;
    private $path;
    private $session;
    private $format = 'html';
    private $language;

    public $get;
    public $post;
    public $files;
    public $cookies;
    public $route;
    public $headers;
    public $content;


    /**
     * Creates a new request object from global values
     *
     * @param array $validLanguages You can define a list of valid languages, so if an accept language is in the list, returns that language. If doesn't exists, returns the first accept language.
     *
     * @return Fol\Http\Request The object with the global data
     */
    public static function createFromGlobals(array $validLanguages = null)
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $port = !empty($_SERVER['X_FORWARDED_PORT']) ? $_SERVER['X_FORWARDED_PORT'] : (!empty($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80);
        $url = "{$scheme}://".$_SERVER['SERVER_NAME'].":{$port}".$_SERVER['REQUEST_URI'];

        $request = new static($url, Headers::getFromGlobals(), (array) filter_input_array(INPUT_GET), (array) filter_input_array(INPUT_POST), $_FILES, (array) filter_input_array(INPUT_COOKIE));

        //Detect request method
        if (($method = $_SERVER['REQUEST_METHOD']) === 'POST' && !empty($_SERVER['X_HTTP_METHOD_OVERRIDE'])) {
            $method = $_SERVER['X_HTTP_METHOD_OVERRIDE'];
        }

        $request->setMethod($method ?: 'GET');

        //Detect client ip
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }

            foreach (explode(',', $_SERVER[$key]) as $ip) {
                if (!empty($ip) && $ip !== 'unknown') {
                    $request->setIp($ip);
                    break 2;
                }
            }
        }

        //Detect client language
        $userLanguages = array_keys($request->headers->getParsed('Accept-Language'));

        if ($validLanguages === null) {
            $request->setLanguage(isset($userLanguages[0]) ? $userLanguages[0] : null);
        } else if (!$userLanguages) {
            $request->setLanguage(isset($validLanguages[0]) ? $validLanguages[0] : null);
        } else {
            $commonLanguages = array_values(array_intersect($userLanguages, $validLanguages));

            $request->setLanguage(isset($commonLanguages[0]) ? $commonLanguages[0] : $validLanguages[0]);
        }

        //Detect request payload
        $contentType = $request->headers->get('Content-Type');

        if ((strpos($contentType, 'application/x-www-form-urlencoded') === 0) && in_array($request->getMethod(), ['PUT', 'DELETE']) && ($content = file_get_contents('php://input'))) {
            parse_str($content, $data);
            $request->post->set($data);
        } elseif ((strpos($contentType, 'application/json') === 0) && in_array($request->getMethod(), ['POST', 'PUT', 'DELETE']) && ($content = file_get_contents('php://input'))) {
            $request->post->set(json_decode($content, true));
        }

        return $request;
    }


    /**
     * Creates a new custom request object
     *
     * @param string $url        The request url or path
     * @param string $method     The method of the request (GET, POST, PUT, DELETE)
     * @param array  $vars       The parameters of the request (GET, POST, etc)
     * @param array  $headers    The headers of the request
     *
     * @return Fol\Http\Request The object with the specified data
     */
    public static function create ($url = '', $method = 'GET', array $vars = array(), array $headers = array())
    {
        if (strpos($url, '://') === false) {
            $url = BASE_URL.$url;
        }

        $request = new static($url, $headers);

        $request->setIp('127.0.0.1');
        $request->setMethod($method);
        $request->setPort(80);

        if ($request->getScheme() === 'https') {
            $request->setPort(443);
        }

        if ($vars) {
            if (in_array(strtoupper($method), array('POST', 'PUT', 'DELETE'))) {
                $request->post->set($vars);
                $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
            } else {
                $request->get->set($vars);
            }
        }

        return $request;
    }


    /**
     * Constructor
     *
     * @param string $url        The request url
     * @param array  $headers    The request headers
     * @param array  $get        The GET parameters
     * @param array  $post       The POST parameters
     * @param array  $files      The FILES parameters
     * @param array  $cookies    The request cookies
     */
    public function __construct($url = null, array $headers = array(), array $get = array(), array $post = array(), array $files = array(), array $cookies = array())
    {
        $this->get = new Input($get);
        $this->post = new Input($post);
        $this->files = new Files($files);
        $this->cookies = new Input($cookies);
        $this->headers = new RequestHeaders($headers);

        foreach (array_keys($this->headers->getParsed('Accept')) as $mimetype) {
            if ($format = Headers::getFormat($mimetype)) {
                $this->format = $format;
                break;
            }
        }

        if ($url) {
            $this->setUrl($url);
        }
    }


    /**
     * Magic function to clone the internal objects
     *
     * Note that session is not cloned because is shared in all requests
     * unless is changed manually
     */
    public function __clone()
    {
        $this->get = clone $this->get;
        $this->post = clone $this->post;
        $this->files = clone $this->files;
        $this->cookies = clone $this->cookies;
        $this->headers = clone $this->headers;
    }


    /**
     * Creates a subrequest based in this request
     *
     * @param string $url        The request url or path
     * @param string $method     The method of the request (GET, POST, PUT, DELETE)
     * @param array  $vars       The parameters of the request (GET, POST, etc)
     *
     * @return Fol\Http\Request The object with the specified data
     */
    public function createSubRequest($url = '', $method = 'GET', array $vars = array())
    {
        $request = static::create($url, $method, $vars);

        $request->setIp($this->getIp());
        $request->setLanguage($this->getLanguage());

        if (isset($this->session)) {
            $request->setSession($this->getSession());
        }

        return $request;
    }


    /**
     * Magic function to convert the request to a string
     */
    public function __toString()
    {
        $text = $this->getMethod().' '.$this->getUrl();
        $text .= "\nFormat: ".$this->getFormat();
        $text .= "\nGet:\n".$this->get;
        $text .= "\nPost:\n".$this->post;
        $text .= "\nFiles:\n".$this->files;
        $text .= "\nCookies:\n".$this->cookies;
        $text .= "\nHeaders:\n".$this->headers;
        $text .= "\nSession:\n".$this->session;

        if (isset($this->route)) {
            $text .= "\nRoute:\n".$this->route;
        }

        return $text;
    }


    /**
     * Set a new url to the request
     *
     * @param string $url The new url
     */
    public function setUrl($url)
    {
        $url = parse_url($url);

        $this->setScheme($url['scheme']);
        $this->setHost($url['host']);
        $this->setPath(isset($url['path']) ? $url['path'] : '');

        if (isset($url['port'])) {
            $this->setPort($url['port']);
        }

        if (isset($url['query'])) {
            parse_str(html_entity_decode($url['query']), $get);

            $this->get->set($get);
        }
    }

    
    /**
     * Returns the full url
     *
     * @param boolean $absolute True to return the absolute url (with scheme and host)
     * @param boolean $format   True to add the format of the request at the end of the path
     * @param boolean $query    True to add the query to the url (false by default)
     *
     * @return string The current url
     */
    public function getUrl($absolute = true, $format = true, $query = false)
    {
        $url = '';

        if ($absolute === true) {
            $url .= $this->getScheme().'://'.$this->getHost();

            if ($this->getPort() !== 80) {
                $url .= ':'.$this->getPort();
            }
        }

        $url .= $this->getPath($format);

        if (($query === true) && ($query = $this->get->get())) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }


    /**
     * Gets the current path
     *
     * @param boolean $format True to add the format of the request at the end of the path
     *
     * @return string The path
     */
    public function getPath($format = false)
    {
        if (($format === true) && ($this->path !== '/') && ($format = $this->getFormat()) && ($format !== 'html')) {
            return $this->path.'.'.$format;
        }

        return $this->path;
    }


    /**
     * Sets a new current path
     *
     * @param string $path The new path
     */
    public function setPath($path)
    {
        $path = urldecode($path);

        if (preg_match('/\.([\w]+)$/', $path, $match)) {
            $this->setFormat($match[1]);
            $path = preg_replace('/'.$match[0].'$/', '', $path);
        }

        if (empty($path)) {
            $path = '/';
        } elseif ($path !== '/' && (substr($path, -1) === '/')) {
            $path = substr($path, 0, -1);
        }

        if ($path[0] !== '/') {
            $path = "/$path";
        }

        $this->path = $path;
    }


    /**
     * Gets the requested format.
     *
     * @return string The current format (html, xml, css, etc)
     */
    public function getFormat()
    {
        return $this->format;
    }


    /**
     * Sets the a new format
     *
     * @param string $format The new format value
     */
    public function setFormat($format)
    {
        $this->format = strtolower($format);
    }


    /**
     * Returns the client language
     *
     * @return string The language code
     */
    public function getLanguage()
    {
        return $this->language;
    }


    /**
     * Set the client language
     *
     * @param string $language The language code
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }


    /**
     * Returns the real client IP
     *
     * @return string The client IP
     */
    public function getIp()
    {
        return $this->id;
    }


    /**
     * Set the client IP
     *
     * @param string $ip The client IP
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }


    /**
     * Detects if the request has been made by ajax or not
     *
     * @return boolean TRUE if the request if ajax, FALSE if not
     */
    public function isAjax()
    {
        return (strtolower($this->headers->get('X-Requested-With')) === 'xmlhttprequest') ? true : false;
    }


    /**
     * Gets the request scheme
     *
     * @return string The request scheme (http or https)
     */
    public function getScheme()
    {
        return $this->scheme;
    }


    /**
     * Sets the request scheme
     *
     * @param string $scheme The request scheme (http, https, etc)
     */
    public function setScheme($scheme)
    {
        $this->scheme = strtolower($scheme);
    }


    /**
     * Gets the request host
     *
     * @return string The request host
     */
    public function getHost()
    {
        return $this->host;
    }


    /**
     * Sets the request host
     *
     * @param string $host The request host
     */
    public function setHost($host)
    {
        $this->host = strtolower($host);
    }


    /**
     * Gets the port on which the request is made
     *
     * @return int The port number
     */
    public function getPort()
    {
        return $this->port;
    }


    /**
     * Sets the port of the request
     *
     * @param int $port The port number
     */
    public function setPort($port)
    {
        $this->port = intval($port);
    }


    /**
     * Gets the request method
     *
     * @return string The request method (in uppercase: GET, POST, etc)
     */
    public function getMethod()
    {
        return $this->method;
    }


    /**
     * Set the request method
     *
     * @param string $method The request method (GET, POST, etc)
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }


    /**
     * Set the request session
     *
     * @param Fol\Http\Sessions\Session A session instance
     */
    public function setSession(Sessions\Session $session)
    {
        $this->session = $session;
    }


    /**
     * Returns the session
     *
     * @return Fol\Http\Sessions\Session The session instance or null
     */
    public function getSession()
    {
        return $this->session;
    }
}
