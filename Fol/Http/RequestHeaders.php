<?php
/**
 * Fol\Http\RequestHeaders
 *
 * Manage http headers in requests
 */
namespace Fol\Http;

class RequestHeaders extends Headers
{

    /**
     * Defines a If-Modified-Since header
     *
     * @param string/Datetime $datetime
     */
    public function setIfModifiedSince($datetime)
    {
        $this->setDateTime('If-Modified-Since', $datetime);
    }
}
