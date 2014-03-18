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
    protected $configPaths = [];
    protected $environment;
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
     * Constructor method. You must define the base folder where the config files are stored
     *
     * @param string/array $paths The base folder paths
     */
    public function __construct($paths)
    {
        $this->addFolders($paths);

        if (defined('ENVIRONMENT')) {
            $this->setEnvironment(ENVIRONMENT);
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
     * @param string/array $paths   The base folder paths
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
     * Magic function to convert all data loaded in a string (for debug purposes)
     *
     * echo (string) $data;
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
     * @param $name The name of the data
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
     * Sets a new value
     *
     * $data->set('database', array(
     *     'host' => 'localhost',
     *     'database' => 'my-database',
     *     'user' => 'admin',
     *     'password' => '1234',
     * ));
     *
     * You can use an array directly to store more than one data:
     *
     * $data->set(array(
     * 	   'database' => array(
     *         'host' => 'localhost',
     *         'database' => 'my-database',
     *         'user' => 'admin',
     *         'password' => '1234'
     *     ),
     *     'database2' => array(
     *         'host' => 'localhost',
     *         'database' => 'my-database',
     *         'user' => 'admin',
     *         'password' => '1234'
     *     ),
     * ));
     *
     * @param string $name  The data name or an array with all data name and value
     * @param array  $value The value of the data
     *
     * @return $this
     */
    public function set($name, array $value = null)
    {
        if (is_array($name)) {
            $this->items = array_replace($this->items, $name);
        } else {
            $this->items[$name] = $value;
        }

        return $this;
    }


    /**
     * Deletes a data value
     *
     * $data->delete('database');
     *
     * @param string $name The name of the data
     *
     * @return $this
     */
    public function delete($name)
    {
        unset($this->items[$name]);

        return $this;
    }


    /**
     * Checks if a configuration is loaded
     *
     * @param string $name The configuration name
     *
     * @return boolean True if the parameter exists (even if it's null) or false if not
     */
    public function has($name)
    {
        return array_key_exists($name, $this->items);
    }


    /**
     * Save the configuration data into a file
     *
     * @param string $name The name of the data
     *
     * @return this
     */
    public function saveFile($name, $configPath = null)
    {
        if (($config = $this->get($name)) === null) {
            throw new \Exception('Empty configuration');
        }

        if (empty($configPath) && !($configPath = reset($this->configPaths))) {
            throw new \Exception('No config path defined');
        }

        if ($this->environment) {
            $configPath = "{$configPath}/{$this->environment}";
        }

        if (!is_dir($configPath)) {
            mkdir($configPath, 0777, true);
        }

        file_put_contents("{$configPath}/{$name}.php", "<?php\n\nreturn ".var_export($config, true).';');

        return $this;
    }
}
