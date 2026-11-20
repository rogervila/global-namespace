<?php

/*
 * A namespaced function, loaded by Composer (autoload-dev "files").
 * The library calls it through a dynamic method name that holds the
 * fully qualified function name: $name = 'Tests\Fixtures\multiply'.
 */

namespace Tests\Fixtures;

if (!function_exists('Tests\Fixtures\multiply')) {
    function multiply(int $a, int $b): int
    {
        return $a * $b;
    }
}
