<?php

namespace Random\Filesystem;

use PHP\PHPInterface;
use RuntimeException;

/**
 * Use case: reading files.
 *
 * Tests fake the filesystem functions, so they need no fixture files and can
 * simulate cases that are hard to reproduce, like a file that exists but
 * cannot be read.
 */
class JsonConfigLoader
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    /**
     * @return array<mixed>
     *
     * @throws RuntimeException When the file cannot be read or has no JSON object.
     */
    public function load(string $path): array
    {
        if (!$this->php::is_file($path) || !$this->php::is_readable($path)) {
            throw new RuntimeException(sprintf('Config file "%s" is not readable.', $path));
        }

        $contents = $this->php::file_get_contents($path);

        if (!is_string($contents)) {
            throw new RuntimeException(sprintf('Config file "%s" could not be read.', $path));
        }

        // json_decode() has no side effects, so it is called directly.
        // Only the functions that touch the outside world go through $this->php.
        $config = json_decode($contents, true);

        if (!is_array($config)) {
            throw new RuntimeException(sprintf('Config file "%s" must contain a JSON object.', $path));
        }

        return $config;
    }
}
