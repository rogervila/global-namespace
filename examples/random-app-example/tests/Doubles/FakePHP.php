<?php

namespace Tests\Doubles;

use PHP\PHP;

/**
 * A hand-written test double, for tests that do not use Mockery.
 *
 * Stubbed functions return the configured value and every other call runs
 * the real function. All calls are recorded. Functions are called
 * statically, so the state is static too: call FakePHP::reset() in setUp().
 *
 * FakePHP extends PHP\PHP, so it can replace PHP\PHP and PHP\PHPInterface
 * type hints.
 */
final class FakePHP extends PHP
{
    /**
     * @var array<string, mixed>
     */
    private static array $returns = [];

    /**
     * @var list<array{0: string, 1: array<int|string, mixed>}>
     */
    private static array $calls = [];

    public static function reset(): void
    {
        self::$returns = [];
        self::$calls = [];
    }

    /**
     * Makes calls to $function return $value instead of running it.
     *
     * @param mixed $value
     */
    public static function stub(string $function, $value): void
    {
        self::$returns[strtolower($function)] = $value;
    }

    /**
     * @return list<array{0: string, 1: array<int|string, mixed>}>
     */
    public static function calls(): array
    {
        return self::$calls;
    }

    /**
     * @param string $name
     * @param array<int|string, mixed> $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        self::$calls[] = [$name, $arguments];

        $function = strtolower(ltrim($name, '\\'));

        if (array_key_exists($function, self::$returns)) {
            return self::$returns[$function];
        }

        return parent::__callStatic($name, $arguments);
    }
}
