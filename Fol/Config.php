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
    use ContainerTrait;

    protected $configPaths = [];
    protected $environment;


    /**
     * Constructor method. You must define the base folder where the config files are stored
     *
     * @param string|array $paths The base folder paths
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
     * Save the configuration data into a file
     *
     * @param string $name The name of the data
     *
     * @return $this
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
