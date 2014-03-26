<?php
/**
 * Fol\Http\Sessions\Native
 *
 * Class to manage the PHP native session
 */
namespace Fol\Http\Sessions;

class Native extends Session
{

    /**
     * Constructs and load the session data
     *
     * @throws an Exception is the session is disabled
     */
    public function __construct($id = null, $name = null, array $config = array())
    {
        switch (session_status()) {
            case PHP_SESSION_DISABLED:
                throw new \Exception('Session are disabled');
                break;

            case PHP_SESSION_NONE:
                if ($name !== null) {
                    session_name($name);
                }

                if ($id !== null) {
                    session_id($id);
                }

                ini_set('session.use_only_cookies', 1);

                $config += ['httponly' => true, 'path' => parse_url(BASE_URL, PHP_URL_PATH) ?: '/'] + session_get_cookie_params();

                session_set_cookie_params($config['lifetime'], $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                session_start();

                parent::__construct(session_id(), session_name());
                
                $this->items =& $_SESSION;
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

        session_destroy();
    }
}
