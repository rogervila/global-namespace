<?php

namespace Tests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Runner\Version;

/**
 * Base class for the library tests.
 *
 * The helpers only use PHPUnit APIs that exist from PHPUnit 8.5 to 13, so the
 * same tests run on every supported PHP version (7.2 to 8.6).
 */
abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // Mockery::close() verifies expectations like once() or with().
        // Count them as assertions, so PHPUnit does not report tests that
        // only check mocks as risky.
        $this->addToAssertionCount(max(0, Mockery::getContainer()->mockery_getExpectationCount()));

        Mockery::close();

        parent::tearDown();
    }

    /**
     * Whether the running PHP version is at least $versionId, written in the
     * PHP_VERSION_ID format: 80100 means PHP 8.1.0.
     *
     * This is a method, not an inline comparison, so static analysis does not
     * report version checks as "always true" or "always false".
     */
    protected static function phpVersionIsAtLeast(int $versionId): bool
    {
        return \PHP_VERSION_ID >= $versionId;
    }

    /**
     * Skips the test when the running PHP version is older than $versionId.
     */
    protected function requirePhp(int $versionId, string $feature): void
    {
        if (!self::phpVersionIsAtLeast($versionId)) {
            $this->markTestSkipped(sprintf(
                '%s requires PHP %d.%d or later.',
                $feature,
                intdiv($versionId, 10000),
                intdiv($versionId % 10000, 100)
            ));
        }
    }

    /**
     * Expects the exception message to contain $message, on every PHPUnit
     * version. PHPUnit 13.2 soft-deprecated expectExceptionMessage() in
     * favour of expectExceptionMessageIsOrContains(), which older PHPUnit
     * versions do not have.
     */
    protected function expectExceptionMessageContains(string $message): void
    {
        if (version_compare(Version::id(), '13.2', '>=')) {
            $this->expectExceptionMessageIsOrContains($message);

            return;
        }

        $this->expectExceptionMessage($message);
    }

    /**
     * Runs $callback with a temporary error handler. Returns what the callback
     * returned and every PHP error (warning, notice, deprecation) it raised.
     * The previous error handler is always restored.
     *
     * @return array{result: mixed, errors: list<array{level: int, message: string}>}
     */
    protected static function captureErrors(callable $callback): array
    {
        $errors = [];

        set_error_handler(static function (int $level, string $message) use (&$errors): bool {
            $errors[] = ['level' => $level, 'message' => $message];

            return true;
        });

        try {
            $result = $callback();
        } finally {
            restore_error_handler();
        }

        return ['result' => $result, 'errors' => $errors];
    }
}
