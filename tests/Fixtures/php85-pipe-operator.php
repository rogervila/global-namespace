<?php

/*
 * Requires PHP 8.5+ (pipe operator). Tests load this file with
 * require_once only after checking the PHP version.
 */

namespace Tests\Fixtures\Php85;

use PHP\PHP;

function shout(string $text): mixed
{
    return $text |> PHP::trim(...) |> PHP::strtoupper(...);
}
