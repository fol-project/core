<?php
/**
 * Fol\Http\Request
 *
 * Class to manage the http request data
 */
namespace Fol\Http;

use Fol\Http\Globals;

class Request
{
    private $ip;
    private $ips = [];
    private $method = 'GET';
    private $scheme;
    private $host;
    private $port;
    private $path;
    private $session;
    private $format = 'html';
    private $language;
    private $authentication;

    private $parentRequest;

    public $query;
    public $data;
    public $files;
    public $cookies;
    public $route;
    public $headers;


    /**
     * Creates a new request object from global values
     *
     * @param array $validLanguages You can define a list of valid languages, so if an accept language is in the list, returns that language. If doesn't exists, returns the first accept language.
     *
     * @return Request The object with the global data
     */
    public static function createFromGlobals(array $validLanguages = null)
    {
        $request = new static(Globals::getUrl(), Globals::getHeaders(), Globals::getGet(), Globals::getPost(), Globals::getFiles(), Globals::getCookies());
        $request->setMethod(Globals::getMethod());
        $request->setIps(Globals::getIps());

        $request->setAuthentication(Globals::getDigestAuthentication() ?: Globals::getBasicAuthentication());

        //Detect client language
        $userLanguages = array_keys($request->headers->getParsed('Accept-Language'));

        if ($validLanguages === null) {
            $request->setLanguage(isset($userLanguages[0]) ? Headers::getLanguage($userLanguages[0]) : null);
        } elseif (!$userLanguages) {
            $request->setLanguage(isset($validLanguages[0]) ? Headers::getLanguage($validLanguages[0]) : null);
        } else {
            $commonLanguages = array_values(array_intersect($userLanguages, $validLanguages));

            $request->setLanguage(Headers::getLanguage(isset($commonLanguages[0]) ? $commonLanguages[0] : $validLanguages[0]));
        }

        //Detect request payload
        if ($data = Globals::getPayload()) {
            $request->data->set($data);
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
     * @return Request The object with the specified data
     */
    public static function create ($url = '', $method = 'GET', array $vars = array(), array $headers = array())
    {
        if (strpos($url, '://') === false) {
            $url = BASE_URL.$url;
        }

        $request = new static($url, $headers);

        $request->setIp('127.0.0.1');
        $request->setMethod($method);

        if (!$request->getPort()) {
            $request->setPort(($request->getScheme() === 'https') ? 433 : 80);
        }

        if ($vars) {
            if (in_array(strtoupper($method), array('POST', 'PUT', 'DELETE'))) {
                $request->data->set($vars);
                $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');
            } else {
                $request->query->set($vars);
            }
        }

        return $request;
    }


    /**
     * Generates an url
     * 
     * @param string  $scheme
     * @param string  $host
     * @param integer $port
     * @param string  $path
     * @param string  $format
     * @param array   $query
     * 
     * @return string
     */
    public static function buildUrl($scheme, $host, $port, $path, $format, array $query = null)
    {
        $url = '';

        if ($scheme) {
            $url .= "{$scheme}:";
        }

        if ($host) {
            $url .= "//{$host}";
        }

        if ($port && (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 433))) {
            $url .= ":{$port}";
        }

        if ($path) {
            $url .= $path;

            if ($format && $format !== 'html') {
                $url .= ".{$format}";
            }
        }

        if ($query) {
            $url .= '?'.http_build_query($query);
        }

        return $url;
    }


    /**
     * Constructor
     *
     * @param string $url        The request url
     * @param array  $headers    The request headers
     * @param array  $query      The url parameters
     * @param array  $data       The request payload data
     * @param array  $files      The FILES parameters
     * @param array  $cookies    The request cookies
     */
    public function __construct($url = null, array $headers = array(), array $query = array(), array $data = array(), array $files = array(), array $cookies = array())
    {
        $this->query = new Input($query);
        $this->data = new Input($data);
        $this->files = new Files($files);
        $this->cookies = new Input($cookies);
        $this->headers = new Headers($headers);

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
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->data = clone $this->data;
        $this->files = clone $this->files;
        $this->cookies = clone $this->cookies;
        $this->headers = clone $this->headers;
    }


    /**
     * Sets the parent request
     *
     * @param Request $request The parent request
     */
    public function setParent(Request $request)
    {
        $this->parentRequest = $request;
    }


    /**
     * Gets the parent request
     *
     * @return Request The parent request or null
     */
    public function getParent()
    {
        return $this->parentRequest;
    }


    /**
     * Gets the main request
     *
     * @return Request The parent request or itself
     */
    public function getMain()
    {
        return $this->parentRequest ? $this->parentRequest->getMain() : $this;
    }


    /**
     * Check whether the request is main or not
     *
     * @return boolean
     */
    public function isMain()
    {
        return empty($this->parentRequest);
    }


    /**
     * Creates a subrequest based in this request
     *
     * @param string $url        The request url or path
     * @param string $method     The method of the request (GET, POST, PUT, DELETE)
     * @param array  $vars       The parameters of the request (GET, POST, etc)
     *
     * @return Request The object with the specified data
     */
    public function createSubRequest($url = '', $method = 'GET', array $vars = array())
    {
        $request = static::create($url, $method, $vars);

        $request->setIp($this->getIp());
        $request->setLanguage($this->getLanguage());
        $request->setParent($this);

        return $request;
    }


    /**
     * Magic function to convert the request to a string
     */
    public function __toString()
    {
        $text = $this->getMethod().' '.$this->getUrl();
        $text .= "\nIps: ".implode(',', $this->ips);
        $text .= "\nFormat: ".$this->getFormat();
        $text .= "\nQuery:\n".$this->query;
        $text .= "\nData:\n".$this->data;
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
            parse_str(html_entity_decode($url['query']), $query);

            $this->query->set($query);
        }
    }

    
    /**
     * Returns the full url
     *
     * @param boolean $query True to add the query to the url (false by default)
     *
     * @return string The current url
     */
    public function getUrl($query = false)
    {
        return self::buildUrl($this->getScheme(), $this->getHost(), $this->getPort(), $this->getPath(), $this->getFormat(), ($query === true) ? $this->query->get() : null);
    }


    /**
     * Gets the current path
     *
     * @return string The path
     */
    public function getPath()
    {
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

        $this->path = preg_replace('|^/?([^/].*)?/?$|U', '/$1', $path);
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
        $this->language = strtolower($language);
    }


    /**
     * Returns all user IPs
     *
     * @return array The client IPs
     */
    public function getIps()
    {
        return $this->ips;
    }


    /**
     * Set the client IPs
     *
     * @param array $ip The client IP
     */
    public function setIps(array $ips)
    {
        $this->ips = $ips;
        $this->ip = isset($ips[0]) ? $ips[0] : null;
    }


    /**
     * Returns the real client IP
     *
     * @return string The client IP
     */
    public function getIp()
    {
        return $this->ip;
    }


    /**
     * Set the client IP
     *
     * @param string $ip The client IP
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        if (($key = array_search($ip, $this->ips)) !== false) {
            array_splice($this->ips, $key, 1);
        }

        array_unshift($this->ips, $ip);
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
     * Sets the authentication data
     *
     * @param data
     */
    public function setAuthentication(array $authentication = null)
    {
        $this->authentication = $authentication;
    }


    /**
     * Gets the authentication data
     *
     * @return array|null
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }


    /**
     * Gets the request username
     *
     * @return string|null
     */
    public function getUser()
    {
        return isset($this->authentication['username']) ? $this->authentication['username'] : null;
    }


    /**
     * Gets the request password
     *
     * @return string
     */
    public function getPassword()
    {
        return isset($this->authentication['password']) ? $this->authentication['password'] : null;
    }


    /**
     * Validate the user password in a digest authentication
     * 
     * @param string $password
     * @param string $realm
     *
     * @return boolean
     */
    public function checkPassword($password, $realm)
    {
        if (empty($this->authentication['type']) || $this->authentication['type'] !== 'digest') {
            return false;
        }

        $method = $this->getMethod();


        $A1 = md5("{$this->authentication['username']}:{$realm}:{$password}");
        $A2 = md5("{$method}:{$this->authentication['uri']}");

        $validResponse = md5("{$A1}:{$this->authentication['nonce']}:{$this->authentication['nc']}:{$this->authentication['cnonce']}:{$this->authentication['qop']}:{$A2}");

        return ($this->authentication['response'] === $validResponse);
    }


    /**
     * Set the request session
     *
     * @param Sessions\Session A session instance
     */
    public function setSession(Sessions\Session $session)
    {
        if ($this->isMain()) {
            $session->setRequest($this);

            $this->session = $session;
        } else {
            $this->getMain()->setSession($session);
        }
    }


    /**
     * Returns the session
     *
     * @return Sessions\Session The session instance or null
     */
    public function getSession()
    {
        if ($this->isMain()) {
            return $this->session;
        }

        return $this->getMain()->getSession();
    }
}
