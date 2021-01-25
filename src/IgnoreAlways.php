<?php

namespace PHP;

class IgnoreAlways implements PHPInterface
{
    /**
     * Static calls are ignored.
     *
     * @param mixed $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return null;
    }
}
