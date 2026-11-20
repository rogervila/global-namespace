<?php

namespace Random\Security;

use InvalidArgumentException;
use PHP\PHPInterface;
use RuntimeException;

/**
 * Use case: randomness.
 *
 * random_bytes() returns different bytes on every call. Tests fake it to
 * check the exact token.
 */
class TokenGenerator
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    /**
     * Returns a hexadecimal token made of $bytes random bytes.
     *
     * @throws InvalidArgumentException When $bytes is smaller than 1.
     * @throws RuntimeException When no random bytes are returned.
     */
    public function generate(int $bytes = 16): string
    {
        if ($bytes < 1) {
            throw new InvalidArgumentException('A token needs at least 1 byte.');
        }

        $random = $this->php::random_bytes($bytes);

        if (!is_string($random)) {
            throw new RuntimeException('Could not generate random bytes.');
        }

        return bin2hex($random);
    }
}
