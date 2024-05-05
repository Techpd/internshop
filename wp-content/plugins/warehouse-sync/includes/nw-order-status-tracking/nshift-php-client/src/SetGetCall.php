<?php

/*
 * This file is part of the BusinessPortal package.
 *
 * (c) Martin Madsen <crakter@gmail.com>
 *
 */

namespace Crakter\nShift;

trait SetGetCall
{
    /**
     * Sets variables in class to be used by toArray()
     * @param  array              $entries
     * @return ApiEntityInterface
     */
    public function set(array $entries)
    {
        foreach ($entries as $key => $var) {
            $this->{$key} = $var;
        }

        return $this;
    }

    /**
     * Sets automagically values in class
     * @param  string             $name
     * @param  mixed              $value
     * @return ApiEntityInterface
     */
    public function __set(string $name, $value)
    {
        $this->{$name} = $value;
    }

    /**
     * Gets automagically values in class
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (!isset($this->{$name})) {
            return '';
        }

        return $this->{$name};
    }

    /**
     * This function settles it so you can use ->setFromDate('01.01.2017');
     * @param  string                          $name  name of the variable needed or you want to set
     * @param  array                           $value value that needs to be set
     * @return string|array|ApiEntityInterface
     */
    public function __call(string $name, array $value)
    {
        $var = substr($name, 3);

        if (strncasecmp($name, "get", 3) === 0) {
            return $this->{$var} ?? '';
        }
        if (strncasecmp($name, "set", 3) === 0) {
            $this->{$var} = $value[0];
        }

        return $this;
    }
}
