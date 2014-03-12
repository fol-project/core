<?php
/**
 * Fol\Http\RequestStack
 *
 * Class to manage the stack of http requests and subrequests
 */
namespace Fol\Http;

class RequestStack
{
    private $requests = [];


    /**
     * Pushes a Request on the stack.
     *
     * @param Fol\Http\Request $request The request to push
     */
    public function push(Request $request)
    {
        $this->requests[] = $request;
    }


    /**
     * Pops the current request from the stack.
     *
     * @return Fol\Http\Request or null
     */
    public function pop()
    {
        if (!$this->requests) {
            return null;
        }

        return array_pop($this->requests);
    }


    /**
     * Return the current request
     *
     * @return Fol\Http\Request or null
     */
    public function getCurrentRequest()
    {
        return end($this->requests) ?: null;
    }


    /**
     * Gets the main Request.
     *
     * @return Fol\Http\Request or null
     */
    public function getMainRequest()
    {
        if (!$this->requests) {
            return null;
        }

        return $this->requests[0];
    }


    /**
     * Returns the parent request of the current.
     *
     * @return Fol\Http\Request or null
     */
    public function getParentRequest()
    {
        $pos = count($this->requests) - 2;

        if (!isset($this->requests[$pos])) {
            return null;
        }

        return $this->requests[$pos];
    }
}
