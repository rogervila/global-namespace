<?php

namespace PHP;

class IgnoreAlways implements PHPInterface
{
    /**
     * Static calls are ignored.
     *
     * @param string $name
     * @param array<int|string, mixed> $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return null;
    }
}
