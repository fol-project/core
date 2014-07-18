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
    protected $parent;


    /**
     * Define new custom constructors
     *
     * @param string|array $name     The constructor name
     * @param \Closure     $resolver A function that returns a Message instance
     */
    public static function define($name, \Closure $resolver = null)
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
            return call_user_func_array(static::$constructors[$name], array_slice(func_get_args(), 1));
        }

        throw new \Exception("'$name' constructor is not defined");
    }


    /**
     * Sets the parent message
     *
     * @param Message $message
     */
    public function setParent(Message $message)
    {
        $this->parent = $message;
    }

    /**
     * Gets the parent message
     *
     * @return Message The parent message
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Gets the first parent message
     *
     * @return Message The parent message or itself
     */
    public function getMain()
    {
        return $this->parent ? $this->parent->getMain() : $this;
    }

    /**
     * Check whether the message is main or not
     *
     * @return boolean
     */
    public function isMain()
    {
        return empty($this->parent);
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
     * @param boolean $forceString Returns the body as string even if it's a stream resource
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
     * Gets the body type
     *
     * @return int
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
