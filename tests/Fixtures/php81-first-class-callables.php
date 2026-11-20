<?php

/*
 * Requires PHP 8.1+ (first-class callable syntax). Tests load this file
 * with require_once only after checking the PHP version.
 */

namespace Tests\Fixtures\Php81;

use Closure;
use PHP\IgnoreAlways;
use PHP\PHP;

function php_strtoupper(): Closure
{
    return PHP::strtoupper(...);
}

function ignore_always_strtoupper(): Closure
{
    return IgnoreAlways::strtoupper(...);
}
