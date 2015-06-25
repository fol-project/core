<?php
namespace Fol;

/**
 * This is a simple class to load configuration data from php files
 * You must define a base folder and the class search for the files inside automatically.
 */
class Config extends Bag
{
    protected $path;

    /**
     * Constructor method.
     *
     * @param string $path The base folder paths
     */
    public function __construct($path)
    {
        $this->path = $path;
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

        return $this->items[$offset];
    }

    /**
     * Read data from php file
     *
     * @param string $name
     *
     * @return array|null
     */
    protected function read($name)
    {
        $path = "{$this->path}/{$name}.php";

        if (is_file($path)) {
            return include $path;
        }
    }
}
