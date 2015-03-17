<?php
namespace Fol;

use Fol as FolGlobal;

/**
 * This is a simple class to load configuration data from php files
 * You must define a base folder and the class search for the files inside automatically.
 */
class Config extends Bag
{
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
        $this->delete();

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
     * Load the configuration in lazy mode
     * 
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->items)) {
            $this->items[$offset] = $this->read($offset);
        }

        return isset($this->items[$offset]) ? $this->items[$offset] : null;
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
}
