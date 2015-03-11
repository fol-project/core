<?php
/**
 * Fol\Config.
 *
 * This is a simple class to load configuration data from php files
 * You must define a base folder and the class search for the files inside automatically.
 */

namespace Fol;

use Fol as FolGlobal;
use ArrayAccess;

class Config implements ArrayAccess
{
    protected $items = [];
    protected $paths = [];
    protected $environment;

    /**
     * Constructor method.
     *
     * @param string      $path        The base folder paths
     * @param null|string $environment The environment name
     */
    public function __construct($path, $environment = null)
    {
        $this->addPath($path);

        if ($environment === null) {
            $this->setEnvironment(FolGlobal::getEnv('ENVIRONMENT') ?: 'development');
        } else {
            $this->setEnvironment($environment);
        }
    }

    /**
     * Changes the environment name.
     *
     * @param string $environment
     *
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        $this->items = [];

        return $this;
    }

    /**
     * Returns the environment name.
     *
     * @return string|null
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Adds new base folders where search for the config files.
     *
     * @param string  $path
     * @param boolean $prepend
     *
     * @return $this
     */
    public function addPath($path, $prepend = true)
    {
        if ($prepend === true) {
            array_unshift($this->paths, $path);
        } else {
            $this->paths[] = $path;
        }

        return $this;
    }

    /**
     * Read data from php file
     *
     * @param string $name
     *
     * @return mixed
     */
    public function read($name)
    {
        foreach ($this->getPathsFor($name) as $path) {
            if (is_file($path)) {
                return include $path;
            }
        }
    }

    /**
     * Returns the possible paths for a value
     *
     * @param string $name
     *
     * @return array
     */
    public function getPathsFor($name)
    {
        $paths = [];

        if ($this->environment) {
            foreach ($this->paths as $path) {
                $paths[] = "{$path}/{$this->environment}/{$name}.php";
            }
        }

        foreach ($this->paths as $path) {
            $paths[] = "{$path}/{$name}.php";
        }

        return $paths;
    }

    /**
     * Gets the data. Loads automatically the data if it has not been loaded.
     *
     * @param string $name
     * @param array  $defaults
     *
     * @return mixed
     */
    public function get($name = null, array $defaults = null)
    {
        if (func_num_args() === 0) {
            return $this->items;
        }

        if (!isset($this->items[$name])) {
            $this->items[$name] = $this->read($name);
        }

        if ($defaults !== null) {
            return array_replace_recursive($defaults, (array) $this->items[$name]);
        }

        return $this->items[$name];
    }

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * Converts all items to a string.
     */
    public function __toString()
    {
        $text = '';

        foreach ($this->items as $name => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $text .= "$name: $value\n";
        }

        return $text;
    }

    /**
     * Counts all stored parameteres.
     *
     * @return int The total number of parameters
     */
    public function length()
    {
        return count($this->items);
    }

    /**
     * Sets one parameter or various new parameters.
     *
     * @param string|array $name
     * @param mixed        $value
     *
     * @return $this
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            $this->items = array_replace($this->items, $name);
        } else {
            $this->items[$name] = $value;
        }

        return $this;
    }

    /**
     * Deletes one or all parameters.
     *
     * $params->delete('name') Deletes one parameter
     * $params->delete() Deletes all parameter
     *
     * @param string $name The parameter name
     *
     * @return $this
     */
    public function delete($name = null)
    {
        if ($name === null) {
            $this->items = [];
        } else {
            unset($this->items[$name]);
        }

        return $this;
    }

    /**
     * Checks if a parameter exists.
     *
     * @param string $name The parameter name
     *
     * @return boolean True if the parameter exists (even if it's null) or false if not
     */
    public function has($name)
    {
        return array_key_exists($name, $this->items);
    }
}
