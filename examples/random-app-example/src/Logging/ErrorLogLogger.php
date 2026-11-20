<?php

namespace Random\Logging;

use PHP\PHPInterface;

/**
 * Use case: writing to the PHP error log.
 *
 * Tests assert the exact message with a mock. Inject PHP\IgnoreAlways to
 * silence the logger, for example in a dry run.
 */
class ErrorLogLogger
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    public function log(string $level, string $message): void
    {
        $this->php::error_log(sprintf('[%s] %s', strtoupper($level), $message));
    }
}
