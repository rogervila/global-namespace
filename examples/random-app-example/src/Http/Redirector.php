<?php

namespace Random\Http;

use LogicException;
use PHP\PHPInterface;

/**
 * Use case: functions with side effects that tests cannot observe, like
 * header(), which does nothing visible in the CLI.
 *
 * Tests assert the exact header() call with a mock. In scripts, inject
 * PHP\IgnoreAlways to turn the redirect into a no-op.
 */
class Redirector
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    /**
     * @throws LogicException When the response headers were already sent.
     */
    public function redirect(string $url, int $status = 302): void
    {
        if ($this->php::headers_sent()) {
            throw new LogicException('Cannot redirect: the headers were already sent.');
        }

        $this->php::header('Location: ' . $url, true, $status);
    }
}
