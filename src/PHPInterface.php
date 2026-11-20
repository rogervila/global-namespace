<?php

namespace PHP;

interface PHPInterface
{
    /**
     * Handles static calls like PHP::rand(0, 1), where the
     * method name is the name of a function.
     * String keys in $arguments are named arguments (PHP 8.0+).
     *
     * @param string $name
     * @param array<int|string, mixed> $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments);
}
