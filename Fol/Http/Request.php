<?php
/**
 * Fol\Http\Request
 *
 * Class to manage the http request data
 */
namespace Fol\Http;

class Request extends Message
{
    protected static $constructors = [];

    protected $method = 'GET';
    protected $scheme;
    protected $host;
    protected $port;
    protected $path;
    protected $session;
    protected $format = 'html';
    protected $language;

    public $query;
    public $data;
    public $files;
    public $cookies;
    public $route;

    /**
     * Creates a new request object from global values
     *
     * @return Request The object with the global data
     */
    public static function createFromGlobals()
    {
        $request = new static(Globals::getUrl(), Globals::getHeaders(), Globals::getGet(), Globals::getPost(), Globals::getFiles(), Globals::getCookies());
        $request->setMethod(Globals::getMethod());

        if (!$request->data->length()) {
            $request->setBody('php://input', true);
        }

        return $request;
    }

    /**
     * Creates a new custom request object
     *
     * @param string $url     The request url or path
     * @param string $method  The method of the request (GET, POST, PUT, DELETE)
     * @param array  $vars    The parameters of the request (GET, POST, etc)
     * @param array  $headers The headers of the request
     *
     * @return Request The object with the specified data
     */
    public static function create ($url = '', $method = 'GET', array $vars = array(), array $headers = array())
    {
        if (strpos($url, '://') === false) {
            $url = BASE_URL.$url;
        }

        $request = new static($url, $headers);

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
     * @param string $url     The request url
     * @param array  $headers The request headers
     * @param array  $query   The url parameters
     * @param array  $data    The request payload data
     * @param array  $files   The FILES parameters
     * @param array  $cookies The request cookies
     */
    public function __construct($url = null, array $headers = array(), array $query = array(), array $data = array(), array $files = array(), array $cookies = array())
    {
        $this->query = new Input($query);
        $this->data = new Input($data);
        $this->files = new InputFiles($files);
        $this->cookies = new InputCookies($cookies);
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
     * Creates a subrequest based in this request
     *
     * @param string $url    The request url or path
     * @param string $method The method of the request (GET, POST, PUT, DELETE)
     * @param array  $vars   The parameters of the request (GET, POST, etc)
     *
     * @return Request The object with the specified data
     */
    public function createSubRequest($url = '', $method = 'GET', array $vars = array())
    {
        $request = static::create($url, $method, $vars);

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
        $text .= "\nFormat: ".$this->getFormat();
        $text .= "\nLanguage: ".$this->getLanguage();
        $text .= "\nQuery:\n".$this->query;
        $text .= "\nData:\n".$this->data;
        $text .= "\nFiles:\n".$this->files;
        $text .= "\nCookies:\n".$this->cookies;
        $text .= "\nHeaders:\n".$this->headers;
        $text .= "\nSession:\n".$this->session;

        if (isset($this->route)) {
            $text .= "\nRoute:\n".$this->route;
        }

        $text .= "\n\n".$this->getBody(true);

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
     * Gets the preferred language
     *
     * @param array $locales Ordered available languages
     *
     * @param string|null
     */
    public function getPreferredLanguage(array $locales)
    {
        $languages = array_keys($this->headers->getParsed('Accept-Language'));

        if ($locales === null) {
            return isset($languages[0]) ? Headers::getLanguage($languages[0]) : null;
        }

        if (!$languages) {
            return isset($locales[0]) ? Headers::getLanguage($locales[0]) : null;
        }

        $common = array_values(array_intersect($languages, $locales));

        return Headers::getLanguage(isset($common[0]) ? $common[0] : $locales[0]);
    }

    /**
     * Returns all client IPs
     *
     * @return array The client IPs
     */
    public function getIps()
    {
        static $forwarded = [
            'Client-Ip',
            'X-Forwarded-For',
            'X-Forwarded',
            'X-Cluster-Client-Ip',
            'Forwarded-For',
            'Forwarded'
        ];

        $flags = \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE;
        $ips = [];

        foreach ($forwarded as $key) {
            if ($this->headers->has($key)) {
                foreach (array_map('trim', explode(',', $this->headers->get($key))) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, $flags)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return $ips;
    }

    /**
     * Returns the client IP
     *
     * @return string|null The client IP
     */
    public function getIp()
    {
        $ips = $this->getIps();

        return isset($ips[0]) ? $ips[0] : null;
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
     * Gets the authentication data
     *
     * @return array|false
     */
    public function getAuthentication()
    {
        if (!($authorization = $this->headers->get('Authorization'))) {
            return false;
        }

        if (strpos($authorization, 'Basic') === 0) {
            $authorization = explode(':', base64_decode(substr($authorization, 6)), 2);

            return [
                'type' => 'Basic',
                'username' => $authorization[0],
                'password' => isset($authorization[1]) ? $authorization[1] : null
            ];
        } elseif (strpos($authorization, 'Digest') === 0) {
            $needed_parts = ['nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1];
            $data = ['type' => 'Digest'];

            preg_match_all('@('.implode('|', array_keys($needed_parts)).')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', substr($authorization, 7), $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                $data[$m[1]] = $m[3] ? $m[3] : $m[4];
                unset($needed_parts[$m[1]]);
            }

            if (!$needed_parts) {
                return $data;
            }
        }

        return false;
    }

    /**
     * Gets the request username
     *
     * @return string|null
     */
    public function getUser()
    {
        $authentication = $this->getAuthentication();

        return isset($authentication['username']) ? $authentication['username'] : null;
    }

    /**
     * Gets the request password
     *
     * @return string
     */
    public function getPassword()
    {
        $authentication = $this->getAuthentication();

        return isset($authentication['password']) ? $authentication['password'] : null;
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
        $authentication = $this->getAuthentication();

        if (empty($authentication['type']) || $authentication['type'] !== 'Digest') {
            return false;
        }

        $method = $this->getMethod();

        $A1 = md5("{$authentication['username']}:{$realm}:{$password}");
        $A2 = md5("{$method}:{$authentication['uri']}");

        $validResponse = md5("{$A1}:{$authentication['nonce']}:{$authentication['nc']}:{$authentication['cnonce']}:{$authentication['qop']}:{$A2}");

        return ($authentication['response'] === $validResponse);
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

    /**
     * Sends the request and returns the response
     *
     * @return Response
     */
    public function send()
    {
        if ($this->sendCallback) {
            return call_user_func($this->sendCallback, $this);
        }

        return CurlDispatcher::execute($this);
    }
}
