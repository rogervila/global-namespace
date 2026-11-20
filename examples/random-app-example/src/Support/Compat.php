<?php

namespace Random\Support;

use InvalidArgumentException;
use PHP\PHPInterface;

/**
 * Use case: functions that only exist on newer PHP versions.
 *
 * Each method uses the native function when it exists (array_find() since
 * PHP 8.4, array_first() and array_last() since PHP 8.5, clamp() since
 * PHP 8.6) and plain PHP code otherwise. Tests fake function_exists() to run
 * both branches on any PHP version.
 */
class Compat
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    /**
     * Returns the first value for which $callback returns true, or null.
     *
     * @param array<mixed> $items
     * @param callable(mixed, array-key): bool $callback Receives the value and the key.
     *
     * @return mixed
     */
    public function arrayFind(array $items, callable $callback)
    {
        if ($this->php::function_exists('array_find')) {
            return $this->php::array_find($items, $callback);
        }

        foreach ($items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Returns the first value of $items, or null when it is empty.
     *
     * @param array<mixed> $items
     *
     * @return mixed
     */
    public function arrayFirst(array $items)
    {
        if ($this->php::function_exists('array_first')) {
            return $this->php::array_first($items);
        }

        foreach ($items as $value) {
            return $value;
        }

        return null;
    }

    /**
     * Returns the last value of $items, or null when it is empty.
     *
     * @param array<mixed> $items
     *
     * @return mixed
     */
    public function arrayLast(array $items)
    {
        if ($this->php::function_exists('array_last')) {
            return $this->php::array_last($items);
        }

        return $items === [] ? null : $items[array_key_last($items)];
    }

    /**
     * Limits $value to the range from $min to $max.
     *
     * @param int|float $value
     * @param int|float $min
     * @param int|float $max
     *
     * @return int|float
     *
     * @throws InvalidArgumentException When $min is greater than $max.
     */
    public function clamp($value, $min, $max)
    {
        // Checked first, so both branches throw the same exception.
        if ($min > $max) {
            throw new InvalidArgumentException('$min must be smaller than or equal to $max.');
        }

        if ($this->php::function_exists('clamp')) {
            return $this->php::clamp($value, $min, $max);
        }

        return max($min, min($max, $value));
    }
}
