<?php
/**
 * Fol\Config
 *
 * This is a simple class to load configuration data from php files
 * You must define a base folder and the class search for the files inside automatically.
 */
namespace Fol;

class Config implements \ArrayAccess
{
    protected $items = [];
    protected $configPaths = [];
    protected $environment;

    /**
     * Constructor method. You must define the base folder where the config files are stored
     *
     * @param string|array $paths The base folder paths
     * @param null|string  $environment The environment
     */
    public function __construct($paths, $environment = null)
    {
        $this->addFolders($paths);

        if ($environment) {
            $this->setEnvironment($environment);
        }
    }

    /**
     * Changes the environment name
     *
     * @param string $environment The new environment name
     *
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * Adds new base folders where search for the config files
     *
     * @param string|array $paths   The base folder paths
     * @param boolean      $prepend If it's true, insert the new folder at begining of the array.
     *
     * @return $this
     */
    public function addFolders($paths, $prepend = true)
    {
        $paths = (array) $paths;

        if ($prepend === true) {
            $this->configPaths = array_merge($paths, $this->configPaths);
        } else {
            $this->configPaths = array_merge($this->configPaths, $paths);
        }

        return $this;
    }


    /**
     * Read data from php file (that returns the value)
     *
     * @param string $name The name of the data (must be the name of the files where the data are stored)
     *
     * @return mixed The data or null if doesn't exists
     */
    public function read($name)
    {
        if (substr($name, -4) !== '.php') {
            $name .= '.php';
        }

        foreach ($this->configPaths as $path) {
            if ($this->environment && is_file("{$path}/{$this->environment}/{$name}")) {
                return include("{$path}/{$this->environment}/{$name}");
            }

            if (is_file("{$path}/{$name}")) {
                return include("{$path}/{$name}");
            }
        }
    }

    /**
     * Gets the data. Loads automatically the data if it has not been loaded.
     * If no name is defined, returns all loaded data
     *
     * @param string $name The name of the data
     *
     * @return mixed The data or null
     */
    public function get($name = null)
    {
        if (func_num_args() === 0) {
            return $this->items;
        }

        if (!isset($this->items[$name])) {
            $this->items[$name] = $this->read($name);
        }

        return $this->items[$name];
    }

    /**
     * ArrayAcces interface methods
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * Converts all items to a string
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
     * Counts all stored parameteres
     *
     * @return int The total number of parameters
     */
    public function length()
    {
        return count($this->items);
    }

    /**
     * Sets one parameter or various new parameters
     *
     * @param string|array $name  The parameter name. You can define an array with name => value to insert various parameters
     * @param mixed        $value The parameter value.
     *
     * @return $this
     */
    public function set($name = null, $value = null)
    {
        if (is_array($name)) {
            $this->items = array_replace($this->items, $name);
        } elseif ($name) {
            $this->items[$name] = $value;
        } else {
            $this->items[] = $value;
        }

        return $this;
    }

    /**
     * Deletes one or all parameters
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
     * Checks if a parameter exists
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
