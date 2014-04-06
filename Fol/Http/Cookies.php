<?php
/**
 * Fol\Http\Cookies
 *
 * Class to manage cookies
 */
namespace Fol\Http;

use Fol\ContainerTrait;

class Cookies implements \ArrayAccess
{
    use ContainerTrait;

    protected $defaults;


    public function __construct()
    {
        $url = parse_url(BASE_URL);

        $this->setDefaults(0, (empty($url['path']) ? '/' : $url['path']), $url['host'], ($url['scheme'] === 'https'), false);
    }


    /**
     * Sets the cookies default values
     *
     * @param int     $expire   The cookie expiration time by default
     * @param string  $path     The cookie path default
     * @param string  $domain   The cookie domain default
     * @param boolean $secure   If the cookie is secure by default
     * @param boolean $httponly If the cookie is httponly by default
     */
    public function setDefaults($expire, $path, $domain, $secure, $httponly)
    {
        $this->defaults = [
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly
        ];
    }


    /**
     * Magic function to converts all cookies to a string
     */
    public function __toString()
    {
        $text = '';
        $time = time();

        foreach ($this->items as $item) {
            $text .= urlencode($item['name']).' = '.urlencode($item['value']).';';

            if ($item['expires'] < $time) {
                $text .= ' deleted;';
            }

            $text .= ' expires='.gmdate("D, d-M-Y H:i:s T", $item['expires']).';';

            if ($item['path'] && $item['path'] !== '/') {
                $text .= ' path='.$item['path'];
            }

            if ($item['domain']) {
                $text .= ' domain='.$item['domain'].';';
            }

            if ($item['secure']) {
                $text .= ' secure;';
            }

            if ($item['httponly']) {
                $text .= ' httponly;';
            }

            $text .= "\n";
        }

        return $text;
    }


    /**
     * Send the cookies to the browser
     *
     * @return boolean True if all cookies have sent or false on error or if headers have been sent before
     */
    public function send()
    {
        if (headers_sent()) {
            return false;
        }

        foreach ($this->items as $cookie) {
            if (!setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly'])) {
                throw new \Exception('Error saving the cookie '.$cookie['name']);
            }
        }

        return true;
    }


    /**
     * Sets a new cookie
     *
     * @param string                   $name     The cookie name
     * @param string                   $value    The cookie value
     * @param integer|string|\Datetime $expire   The cookie expiration time. It can be a number or a DateTime instance
     * @param string                   $path     The cookie path
     * @param string                   $domain   The cookie domain
     * @param boolean                  $secure   If the cookie is secure, only will be send in secure connection (https)
     * @param boolean                  $httponly If is set true, the cookie only will be accessed via http, so javascript cannot access to it.
     */
    public function set($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $defaults = $this->get($name, $this->defaults);

        if ($expire === null) {
            $expire = $defaults['expire'];
        } elseif ($expire instanceof \DateTime) {
            $expire = $expire->format('U');
        } elseif (!is_numeric($expire)) {
            $expire = strtotime($expire);
        }

        $this->items[$name] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => ($path === null) ? $defaults['path'] : $path,
            'domain' => ($domain === null) ? $defaults['domain'] : $domain,
            'secure' => ($secure === null) ? $defaults['secure'] : (bool) $secure,
            'httponly' => ($httponly === null) ? $defaults['httponly'] : (bool) $httponly
        ];
    }


    /**
     * Deletes a cookie
     *
     * @param string  $name     The cookie name
     * @param string  $path     The cookie path
     * @param string  $domain   The cookie domain
     * @param boolean $secure   If the cookie is secure, only will be send in secure connection (https)
     * @param boolean $httponly If is set true, the cookie only will be accessed via http, so javascript cannot access to it.
     */
    public function setDelete($name, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->set($name, '', 1, $path, $domain, $secure, $httponly);
    }
}
