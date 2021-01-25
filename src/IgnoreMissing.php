<?php

namespace PHP;

class IgnoreMissing implements PHPInterface
{
    /**
     * Static calls are forwarded to the requested
     * function with the arguments provided.
     * If it does not exist, we skip the call.
     * Otherwise, we return the function result.
     *
     * @param mixed $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (!function_exists($name)) {
            return;
        }

        return call_user_func_array($name, $arguments);
    }
}
