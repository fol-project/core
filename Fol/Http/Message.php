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
        $this->bodyStream = $isStream;
        $this->body = $isStream ? $body : (string) $body;
    }

    /**
     * Gets the message body
     *
     * @param boolean $forceString Returns the body as string even if it's a stream resource
     *
     * @return string|resource The body string or streaming resource
     */
    public function getBody($forceString = false)
    {
        if ($this->isStream()) {
            if ($forceString) {
                $body = $this->getBody();
                rewind($body);

                return stream_get_contents($body);
            }

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

        $this->body += (string) $content;
    }

    /**
     * Set the content callback
     */
    public function setSendCallback(callable $callback = null)
    {
        $this->sendCallback = $callback;
    }
}
