<?php

namespace Random\Config;

use PHP\PHPInterface;

/**
 * Use case: environment variables.
 *
 * Tests fake getenv() instead of calling putenv(), which would change the
 * environment of the whole test process.
 */
class Environment
{
    private const DEFAULT_ENVIRONMENT = 'production';

    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    /**
     * The value of APP_ENV, or "production" when it is not set or empty.
     */
    public function name(): string
    {
        $name = $this->php::getenv('APP_ENV');

        return is_string($name) && $name !== '' ? $name : self::DEFAULT_ENVIRONMENT;
    }

    public function isProduction(): bool
    {
        return $this->name() === self::DEFAULT_ENVIRONMENT;
    }
}
