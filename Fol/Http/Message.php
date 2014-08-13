<?php
/**
 * Fol\Http\Message
 *
 * Class to manage a http message
 */
namespace Fol\Http;

abstract class Message
{
    public $headers;

    protected $body;
    protected $bodyStream = false;
    protected $sendCallback;


    /**
     * Define new custom constructors
     *
     * @param string|array $name     The constructor name
     * @param \Closure     $resolver A function that returns a Message instance
     */
    public static function defineConstructor($name, \Closure $resolver = null)
    {
        if (is_array($name)) {
            foreach ($name as $name => $resolver) {
                static::define($name, $resolver);
            }

            return;
        }

        static::$constructors[$name] = $resolver;
    }


    /**
     * Execute custom constructors
     * 
     * @throws \Exception if the constructor doesn't exist
     *
     * @return Message
     */
    public static function __callStatic($name, $arguments)
    {
        if (!empty(static::$constructors)) {
            return call_user_func_array(static::$constructors[$name], $arguments);
        }

        throw new \Exception("'$name' constructor is not defined");
    }


    /**
     * Sets the message body
     *
     * @param string|resource $body     The string or stream handler or stream filename
     * @param boolean         $isStream True to define the body as stream.
     */
    public function setBody($body, $isStream = false)
    {
        $this->bodyStream = is_resource($body) ?: $isStream;
        $this->body = $this->bodyStream ? $body : (string) $body;
    }

    /**
     * Gets the message body
     *
     * @return string|resource The body string or streaming resource
     */
    public function getBody()
    {
        if ($this->isStream()) {
            if (is_string($this->body)) {
                return $this->body = fopen($this->body, 'r+');
            }
        }

        return $this->body;
    }

    /**
     * Gets whether the body is stream or not
     *
     * @return boolean
     */
    public function isStream()
    {
        return $this->bodyStream;
    }

    /**
     * Write content in the body
     *
     * @param string $content
     * @param int    $length  Only used on streams
     *
     * @return int|null
     */
    public function write($content, $length = null)
    {
        if ($content === '') {
            return;
        }

        if ($this->isStream()) {
            return fwrite($this->getBody(), $content, $length);
        }

        $this->body .= (string) $content;
    }

    /**
     * Reads content from the body
     *
     * @return string
     */
    public function read()
    {
        $body = $this->getBody();

        if (is_string($body)) {
            return $body;
        }

        rewind($body);
        return stream_get_contents($body);
    }

    /**
     * Set the content callback
     */
    public function setSendCallback(callable $callback = null)
    {
        $this->sendCallback = $callback;
    }
}
