<?php
/**
 * Developer: Roquie
 * DateTime: 06.03.15 14:08
 * Current file name: Collection.php
 *
 * All rights reserved (c)
 */

namespace Requester;

use Closure;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Arr;

class Collection extends BaseCollection
{

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        array_set($this->items, $key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        //unset($this->items[$key]);

        // Recursive call
        if (is_array($key))
            foreach ($key as $k)
                $this->internalRemove($this->items, $k);

        $this->internalRemove($this->items, $key);
    }

    /**
     * @see: https://github.com/Anahkiasen/underscore-php/blob/master/src/Methods/CollectionMethods.php
     * @param $collection
     * @param $key
     * @return bool
     */
    protected function internalRemove(&$collection, $key)
    {
        // Explode keys
        $keys = explode('.', $key);
        // Crawl though the keys
        while (count($keys) > 1) {
            $key = array_shift($keys);
            // If we're dealing with an object
            if (is_object($collection)) {
                if (!isset($collection->$key)) {
                    return false;
                }
                $collection = &$collection->$key;
                // If we're dealing with an array
            } else {
                if (!isset($collection[$key])) {
                    return false;
                }
                $collection = &$collection[$key];
            }
        }
        $key = array_shift($keys);
        if (is_object($collection)) {
            unset($collection->$key);
        } else {
            unset($collection[$key]);
        }
    }

    /**
     * Get an item at a given offset using Dot-notation.
     *
     * @param  mixed  $key
     * @return Collection|bool|string
     */
    public function offsetGet($key)
    {
        $result = Arr::get($this->items, $key);

        if (is_array($result))
            return new static($result);

        return $result;
    }

    /**
     * Get an item from the collection by key using Dot-notation.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return Collection|bool|string|Closure
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key))
        {
            return $this->offsetGet($key);
        }

        return value($default);
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return (bool) Arr::get($this->items, $key);
    }

}
