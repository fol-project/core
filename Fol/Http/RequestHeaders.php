<?php
/**
 * Fol\Http\Headers
 *
 * Manage http headers
 */
namespace Fol\Http;

class RequestHeaders extends Headers
{
    public $cookies;

    /**
     * Constructor
     *
     * @param array $items The items to store
     */
    public function __construct(array $items = null)
    {
        parent::__construct($items);

        $this->cookies = new RequestCookies($items);
    }

    /**
     * {@inheritDoc}
     */
    public function setFromString($string, $replace = true)
    {
        parent::setFromString($string, $replace);

        if (strpos($string, 'Cookie:') === 0) {
            $this->cookies->setFromString($string);
        }
    }
}
