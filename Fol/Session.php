<?php
/**
 * Fol\Session
 *
 * Class to manage the session
 */
namespace Fol;

class Session implements \ArrayAccess
{
    protected $id;
    protected $name;
    protected $items = [];


    /**
     * ArrayAcces interface methods
     */
    public function offsetExists ($offset) {
        return $this->has($offset);
    }
    public function offsetGet ($offset) {
        return $this->get($offset);
    }
    public function offsetSet ($offset, $value) {
        $this->set($offset, $value);
    }
    public function offsetUnset ($offset) {
        $this->delete($offset);
    }


    /**
     * Constructor. Start/resume the latest session.
     *
     * @throws an Exception is the session is disabled
     */
    public static function createFromGlobals($id = null, $name = null, array $cookieParams = array())
    {
        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                throw new \Exception('Session are disabled');
                break;

            case PHP_SESSION_NONE:
                $session = new static();

                if ($name !== null) {
                    session_name($name);
                }

                $session->name = session_name();

                if ($id !== null) {
                    session_id($id);
                }

                $session->id = session_id();

                $cookieParams = array_replace(
                    session_get_cookie_params(),
                    ['httponly' => true, 'path' => parse_url(BASE_URL, PHP_URL_PATH) ?: '/'],
                    $cookieParams
                );

                ini_set('session.use_only_cookies', 1);
                session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'], $cookieParams['domain'], $cookieParams['secure'], $cookieParams['httponly']);

                session_start();

                $session->set($_SESSION);

                return $session;
        }
    }


    /**
     * Constructor class. You can define the items directly
     *
     * @param array $items The items to store
     */
    public function __construct(array $items = null)
    {
        if ($items !== null) {
            $this->set($items);
        }
    }


    /**
     * Magic function to close the session on destroy the object
     */
    public function __destruct()
    {
        $this->close();
    }


    /**
     * Close the session and save the data.
     */
    public function close()
    {
        if ($this->isStarted()) {
            session_write_close();
        }
    }


    /**
     * Sets the session cache expire in minutes
     *
     * @param int $minutes The time in minutes
     */
    public function setCacheExpire($minutes)
    {
        return session_cache_expire($minutes);
    }


    /**
     * Gets the session cache expire in minutes
     *
     * @return int
     */
    public function getCacheExpire()
    {
        return session_cache_expire();
    }


    /**
     * Destroy the current session deleting the data
     */
    public function destroy()
    {
        if (!$this->isStarted()) {
            $this->start();
        }

        $this->delete();

        return session_destroy();
    }


    /**
     * Check if a session is started or not.
     *
     * @return boolean True if it's started, false if not
     */
    public function isStarted()
    {
        return (session_status() === PHP_SESSION_ACTIVE);
    }


    /**
     * Get the current session name
     *
     * @return string The session name
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Sets the current session name
     *
     * @return string The session name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * Get the current session id
     *
     * @return string The id
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set a new id for the current session
     *
     * @param string $id The new id
     *
     * @return string The previous session id
     */
    public function setId($id)
    {
        return $this->id = $id;
    }


    /**
     * Regenerate the id for the current session
     */
    public function regenerateId()
    {
        return session_regenerate_id();
    }


    /**
     * Get a value from the current session
     *
     * @param string $name    The value name. If it is not defined, returns all stored variables
     * @param string $default A default value in case the variable is not defined
     *
     * @return string The value of the variable or the default value.
     * @return array  All stored variables in case no name is defined.
     */
    public function get($name = null, $default = null)
    {
        if ($name === null) {
            return $this->items;
        }

        if (isset($this->items[$name]) && $this->items[$name] !== '') {
            return $this->items[$name];
        }

        return $default;
    }


    /**
     * Set a new or update an existing variable
     *
     * @param string/array $name  The variable name or an array of variables
     * @param string       $value The value of the variable
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            $this->items = array_replace($this->items, $name);
        } else {
            $this->items[$name] = $value;
        }
    }


    /**
     * Delete one or all variables of the session
     *
     * @param string $name The variable name. If it is not defined, delete all variables
     */
    public function delete($name = null)
    {
        if ($name === null) {
            $this->items = [];
        } else {
            unset($this->items[$name]);
        }
    }


    /**
     * Check if a variable is defined or not
     *
     * @param string $name The variable name.
     *
     * @return boolean True if it's defined, false if not
     */
    public function has($name)
    {
        return array_key_exists($name, $this->items);
    }


    /**
     * Get a flash value (read only once)
     *
     * @param string $name    The value name. If it is not defined, returns all stored variables
     * @param string $default A default value in case the variable is not defined
     *
     * @return string The value of the variable or the default value.
     * @return array  All stored variables in case no name is defined.
     */
    public function getFlash($name = null, $default = null)
    {
        if ($name === null) {
            return isset($this->items['_flash']) ? $this->items['_flash'] : [];
        }

        if (isset($this->items['_flash'][$name])) {
            $default = $this->items['_flash'][$name];
            unset($this->items['_flash'][$name]);
        }

        return $default;
    }


    /**
     * Set a new flash value
     *
     * @param string/array $name  The variable name or an array of variables
     * @param string       $value The value of the variable
     */
    public function setFlash($name, $value = null)
    {
        if (!isset($this->items['_flash'])) {
            $this->items['_flash'] = [];
        }

        if (is_array($name)) {
            $this->items['_flash'] = array_replace($this->items['_flash'], $name);
        } else {
            $this->items['_flash'][$name] = $value;
        }
    }


    /**
     * Check if a flash variable is defined or not (but does not remove it)
     *
     * @param string $name The variable name.
     *
     * @return boolean True if it's defined, false if not
     */
    public function hasFlash($name)
    {
        return (isset($this->items['_flash']) && array_key_exists($name, $this->items['_flash']));
    }
}
