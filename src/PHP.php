<?php

namespace PHP;

class PHP
{
    /**
     * Static calls are forwarded to the requested
     * function with the arguments provided.
     * Then, we return the function result.
     *
     * @param mixed $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array($name, $arguments);
    }
}
