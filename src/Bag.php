<?php
namespace Fol;

use ArrayAccess;
use Iterator;
use Countable;
use JsonSerializable;

/**
 * Generic class for data storage and easily extraction from complicated array structured
 */
class Bag implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    protected $items = [];

    /**
     * @see ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * @see ArrayAccess
     */
    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    /**
     * @see ArrayAccess
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    /**
     * @see Iterator
     */
    public function rewind()
    {
        return reset($this->items);
    }

    /**
     * @see Iterator
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * @see Iterator
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     * @see Iterator
     */
    public function next()
    {
        return next($this->items);
    }

    /**
     * @see Iterator
     */
    public function valid()
    {
        return key($this->items) !== null;
    }

    /**
     * @see Countable
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @see JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->items;
    }

    /**
     * Get the data.
     *
     * @param null|string|array $name
     *
     * @return mixed
     */
    public function get($name = null)
    {
        if ($name === null) {
            return $this->items;
        }

        if (is_array($name)) {
            $values = [];

            foreach ($name as $n) {
                $values[$n] = $this->get($n);
            }

            return $values;
        }

        if (strpos($name, '[') !== false) {
            $names = explode('[', str_replace(']', '', $name));
            $offset = array_shift($names);
            $item = $this->offsetGet($offset) ?: [];

            foreach ($names as $n) {
                if (!isset($item[$n])) {
                    return null;
                }
                
                $item = $item[$n];
            }

            return $item;
        }

        return $this[$name];
    }

    /**
     * Set data
     *
     * @param mixed $name
     * @param mixed $value
     *
     * @return $this
     */
    public function set($name = null, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $n => $v) {
                $this->set($n, $v);
            }

            return $this;
        }

        if (strpos($name, '[') !== false) {
            $names = explode('[', str_replace(']', '', $name));
            $offset = array_shift($names);
            $items = $this->offsetGet($offset) ?: [];
            $item = &$items;

            foreach ($names as $n) {
                if (!isset($item[$n])) {
                    $item[$n] = [];
                }

                $item = &$item[$n];
            }

            $item = $value;
            $this[$offset] = $items;
        } else {
            $this[$name] = $value;
        }

        return $this;
    }

    /**
     * delete data
     *
     * @param mixed $name
     *
     * @return $this
     */
    public function delete($name = null)
    {
        if ($name === null) {
            $this->items = [];

            return $this;
        }

        if (is_array($name)) {
            foreach ($name as $n) {
                $this->delete($n);
            }

            return $this;
        }

        if (strpos($name, '[') !== false) {
            $names = explode('[', str_replace(']', '', $name));
            $last = array_pop($names);
            $item = &$this->items;

            foreach ($names as $n) {
                if (!isset($item[$n])) {
                    return $this;
                }

                $item = &$item[$n];
            }

            unset($item[$last]);
        } else {
            unset($this[$name]);
        }

        return $this;
    }

    /**
     * Checks if a parameter is set (exists and it's not null).
     *
     * @param mixed $name The parameter/s name
     *
     * @return boolean
     */
    public function has($name)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
                if (!$this->has($n)) {
                    return false;
                }
            }

            return true;
        }

        if (strpos($name, '[') !== false) {
            $names = explode('[', str_replace(']', '', $name));
            $item = $this;

            foreach ($names as $n) {
                if (!isset($item[$n])) {
                    return false;
                }
                
                $item = $item[$n];
            }

            return true;
        }
        
        return isset($this[$name]);
    }

    /**
     * Converts all items to string.
     */
    public function __toString()
    {
        return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
