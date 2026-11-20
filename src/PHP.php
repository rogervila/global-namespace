<?php

namespace PHP;

class PHP implements PHPInterface
{
    /**
     * Static calls are forwarded to the requested
     * function with the arguments provided.
     * Then, we return the function result.
     *
     * @param string $name
     * @param array<int|string, mixed> $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array($name, $arguments);
    }
}
