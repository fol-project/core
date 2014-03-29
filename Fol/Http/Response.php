<?php
/**
 * Fol\Http\Response
 *
 * Class to manage the http response data
 */
namespace Fol\Http;

class Response
{
    public $headers;
    public $cookies;

    protected $content;
    protected $status;
    protected $content_type;
    protected $headers_sent = false;


    public static function __set_state($array)
    {
        $Response = new static($array['content'], $array['status'][0]);

        $Response->headers = $array['headers'];
        $Response->cookies = $array['cookies'];

        return $Response;
    }


    /**
     * Constructor
     *
     * @param string  $content The body of the response
     * @param integer $status  The status code (200 by default)
     * @param array   $headers The headers to send in the response
     */
    public function __construct ($content = '', $status = 200, array $headers = array())
    {
        $this->setContent($content);
        $this->setStatus($status);

        $this->headers = new ResponseHeaders($headers);
        $this->cookies = new Cookies();

        if (!$this->headers->has('Date')) {
            $this->headers->setDateTime('Date', new \DateTime());
        }
    }


    /**
     * Magic function to clone the internal objects
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->cookies = clone $this->cookies;
    }


    /**
     * Magic function to converts the current response to a string
     */
    public function __toString()
    {
        return (string) $this->content;
    }


    /**
     * Sets the request format
     *
     * @param string $format The new format value
     */
    public function setFormat($format)
    {
        if ($mimetype = Headers::getMimeType($format)) {
            $this->headers->set('Content-Type', "$mimetype; charset=UTF-8");
        }
    }


    /**
     * Sets the request language
     *
     * @param string $language The new language
     */
    public function setLanguage($language)
    {
        $this->headers->set('Content-Language', $language);
    }


    /**
     * Prepare the Response according a request
     *
     * @param Fol\Http\Request $request The original request
     */
    public function prepare(Request $request)
    {
        if (!$this->headers->has('Content-Type') && ($format = $request->getFormat())) {
            $this->setFormat($format);
        }

        if (!$this->headers->has('Content-Language') && ($language = $request->getLanguage())) {
            $this->setLanguage($language);
        }

        if ($this->headers->has('Transfer-Encoding')) {
            $this->headers->remove('Content-Length');
        }

        if ($request->getMethod() === 'HEAD') {
            $this->setContent('');
        }
    }


    /**
     * Sets the content of the response body
     *
     * @param string $content The text content
     */
    public function setContent($content)
    {
        $this->content = (string) $content;
    }


    /**
     * Appends more content to the response body
     *
     * @param string $content The text content to append
     */
    public function appendContent($content)
    {
        $this->content .= (string) $content;
    }


    /**
     * Prepends content to the response body
     *
     * @param string $content The text content to prepend
     */
    public function prependContent($content)
    {
        $this->content = (string) $content.$this->content;
    }


    /**
     * Gets the body content
     *
     * @return string The body of the response
     */
    public function getContent()
    {
        return $this->content;
    }


    /**
     * Sets the status code
     *
     * @param integer $code The status code (for example 404)
     * @param string  $text The status text. If it's not defined, the text will be the defined in the Fol\Http\Headers:$status array
     */
    public function setStatus($code, $text = null)
    {
        $this->status = array($code, ($text ?: ResponseHeaders::getStatusText($code)));
    }


    /**
     * Gets current status
     *
     * @param string $text Set to TRUE to return the status text instead the status code
     *
     * @return integer The status code or the status text if $text parameter is true
     */
    public function getStatus($text = false)
    {
        return $text ? $this->status[1] : $this->status[0];
    }


    /**
     * Set the status code and header needle to redirect to another url
     *
     * @param string  $url    The url of the new location
     * @param integer $status The http code to redirect (302 by default)
     */
    public function redirect($url, $status = 302)
    {
        $this->setStatus($status);
        $this->headers->set('location', $url);
    }


    /**
     * Defines a Not Modified status
     */
    public function setNotModified()
    {
        $this->setStatus(304);
        $this->setContent('');

        foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $name) {
            $this->headers->remove($header);
        }
    }


    /**
     * Sends the headers and the content
     */
    public function send()
    {
        if (!$this->headers_sent) {
            $this->sendHeaders();
            $this->headers_sent = true;
        }

        $this->sendContent();
    }


    /**
     * Send the output buffer and empty the response content
     */
    public function flush()
    {
        $this->send();

        flush();

        if (ob_get_level() > 0) {
            ob_flush();
        }

        $this->content = '';
    }


    /**
     * Sends the headers if don't have been sent before
     *
     * @return boolean TRUE if the headers are sent and false if headers had been sent before
     */
    public function sendHeaders()
    {
        header(sprintf('HTTP/1.1 %s', $this->status[0], $this->status[1]));

        $this->headers->send();
        $this->cookies->send();

        return true;
    }


    /**
     * Sends the content
     */
    public function sendContent()
    {
        echo $this->content;
    }


    /**
     * Defines a Last-Modified header
     *
     * @param string/Datetime $datetime
     */
    public function setLastModified($datetime)
    {
        $this->headers->setDateTime('Last-Modified', $datetime);
    }


    /**
     * Defines a Expire header
     *
     * @param string/Datetime $datetime
     */
    public function setExpires($datetime)
    {
        $this->headers->setDateTime('Expires', $datetime);
    }


    /**
     * Returns the age of the response
     *
     * @return integer The age in seconds
     */
    public function getAge()
    {
        if ($this->headers->has('Age')) {
            return (int) $this->headers->get('Age');
        }

        return max(time() - $this->headers->getDateTime('Date')->getTimestamp(), 0);
    }


    /**
     * Defines a max-age and optionally s-maxage cache directive
     *
     * @param int $max_age        The max age in seconds
     * @param int $shared_max_age The shared max age in seconds
     */
    public function setMaxAge($max_age, $shared_max_age = null)
    {
        $cacheControl = $this->headers->getParsed('Cache-Control');
        $cacheControl['max-age'] = (int) $max_age;

        if ($shared_max_age !== null) {
            $cacheControl['s-maxage'] = (int) $shared_max_age;
        }

        $this->headers->setParsed('Cache-Control', $cacheControl);
    }


    /**
     * Returns the max-age cache directive
     *
     * @return int $age The age in seconds
     */
    public function getMaxAge()
    {
        $cacheControl = $this->headers->getParsed('Cache-Control');

        if (isset($cacheControl['s-maxage'])) {
            return (int) $cacheControl['s-maxage'];
        }

        return isset($cacheControl['max-age']) ? (int) $cacheControl['max-age'] : 0;
    }


    /**
     * Defines the response as public in Cache-Control directive
     */
    public function setPublic()
    {
        $cacheControl = $this->headers->getParsed('Cache-Control');
        $cacheControl['public'] = true;
        unset($cacheControl['private']);
        $this->headers->setParsed('Cache-Control', $cacheControl);
    }


    /**
     * Defines the response as private in Cache-Control directive
     */
    public function setPrivate()
    {
        $cacheControl = $this->headers->getParsed('Cache-Control');
        $cacheControl['private'] = true;
        unset($cacheControl['public']);
        $this->headers->setParsed('Cache-Control', $cacheControl);
    }


    /**
     * Check if the response must be revalidated by the origin
     */
    public function mustRevalidate()
    {
        $cacheControl = $this->headers->getParsed('Cache-Control');

        return (!empty($cacheControl['must-revalidate']) || $this->headers->has('proxy-revalidate'));
    }


    /**
     * Add a must-revalidate cache control directive
     */
    public function setMustRevalidate()
    {
        $cacheControl = $this->headers->getParsed('Cache-Control');
        $cacheControl['must-revalidate'] = true;
        $this->headers->setParsed('Cache-Control', $cacheControl);
    }
}
