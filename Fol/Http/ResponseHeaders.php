<?php
/**
 * Fol\Http\ResponseHeaders
 *
 * Manage http headers in response
 */
namespace Fol\Http;

class ResponseHeaders extends Headers
{

    /**
     * Defines a Last-Modified header
     *
     * @param string/Datetime $datetime
     */
    public function setLastModified($datetime)
    {
        $this->setDateTime('Last-Modified', $datetime);
    }


    /**
     * Defines a Expire header
     *
     * @param string/Datetime $datetime
     */
    public function setExpires($datetime)
    {
        $this->setDateTime('Expires', $datetime);
    }


    /**
     * Defines an Age header
     *
     * @param string/Datetime $datetime
     */
    public function setAge($datetime)
    {
        $this->setDateTime('Age', $datetime);
    }
}
