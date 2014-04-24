<?php
/**
 * Fol\Http\Sessions\Native
 *
 * Class to manage the PHP native session
 */
namespace Fol\Http\Sessions;

use Fol\Http\Cookies;

class Native extends Session
{
    protected $cookie;
    protected $removeCookie;


    /**
     * {@inheritDoc}
     * 
     * @throws \Exception if the session is disabled
     */
    public function setRequest(Request $request)
    {
        if ($this->name === null) {
            $this->name = session_name();
        }

        if ($this->id === null) {
            if ($request->cookies->has($this->name)) {
                $this->id = $request->cookies->get($this->name);
            } else {
                session_name($this->name);
                $this->id = session_id();
            }
        }

        $this->start();
    }


    /**
     * Starts the session
     * 
     * @throws \RuntimeException if session cannot be started
     */
    protected function start()
    {
        if (session_status() === PHP_SESSION_DISABLED) {
            throw new \RuntimeException('Native sessions are disabled');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Failed to start the session: already started by PHP.');
        }

        session_name($this->name);
        session_id($this->id);

        ini_set('session.use_only_cookies', 1);

        $this->cookie = Cookies::getDefaultsFromGlobals(['httponly' => true]);

        session_set_cookie_params($this->cookie['lifetime'], $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);
        session_start();

        $this->items =& $_SESSION;
    }


    /**
     * {@inheritDoc}
     */
    public function prepare(Response $response)
    {
        if ($this->removeCookie === true) {
            $response->cookies->setDelete($this->name, $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);
        }
    }


    /**
     * Saves the session data on destruct
     */
    public function __destruct()
    {
        if ((session_status() === PHP_SESSION_ACTIVE) && (session_name() === $this->name) && (session_id() === $this->id)) {
            session_write_close();
        }
    }


    /**
     * Regenerate the id for the current session
     */
    public function regenerate($destroy = false, $lifetime = null)
    {
        if ($lifetime !== null) {
            ini_set('session.cookie_lifetime', $lifetime);
        }

        $return = session_regenerate_id($destroy);
        $this->id = session_id();

        return $return;
    }


    /**
     * Close the session and save the data.
     */
    public function save()
    {
        session_write_close();
    }


    /**
     * Destroy the current session deleting the data
     */
    public function destroy()
    {
        $this->delete();

        $this->removeCookie = true;

        session_destroy();
    }
}
