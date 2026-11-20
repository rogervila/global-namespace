<?php

namespace Random\Clock;

use PHP\PHPInterface;

/**
 * Use case: measuring elapsed time with microtime().
 *
 * Tests return a fixed list of timestamps, so the result is exact and the
 * test never has to wait.
 */
class Stopwatch
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    /**
     * Runs $callback and returns how many seconds it took.
     */
    public function measure(callable $callback): float
    {
        $start = (float) $this->php::microtime(true);

        $callback();

        return (float) $this->php::microtime(true) - $start;
    }
}
