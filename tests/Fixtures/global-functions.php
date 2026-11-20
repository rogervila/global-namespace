<?php

/*
 * Global functions loaded by Composer (autoload-dev "files").
 * They prove that the library calls any global function, not only PHP
 * built-ins: for example, WordPress helpers or your own functions.
 */

use Tests\Fixtures\SideEffects;

if (!function_exists('global_namespace_test_greet')) {
    function global_namespace_test_greet(string $name): string
    {
        return 'Hello, ' . $name . '!';
    }
}

if (!function_exists('global_namespace_test_side_effect')) {
    /**
     * Increments a counter, so tests can prove that a function never ran.
     */
    function global_namespace_test_side_effect(): int
    {
        return ++SideEffects::$calls;
    }
}
