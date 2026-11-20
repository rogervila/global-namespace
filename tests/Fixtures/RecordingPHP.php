<?php

namespace Tests\Fixtures;

use PHP\PHP;

/**
 * A hand-written spy: it records every call, then lets PHP\PHP call the real
 * function. It shows that PHP\PHP can be extended to build test doubles
 * without Mockery.
 */
final class RecordingPHP extends PHP
{
    /**
     * @var list<array{name: string, arguments: array<int|string, mixed>}>
     */
    public static $calls = [];

    /**
     * @param string $name
     * @param array<int|string, mixed> $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        self::$calls[] = ['name' => $name, 'arguments' => $arguments];

        return parent::__callStatic($name, $arguments);
    }
}
