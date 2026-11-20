<?php

namespace Tests;

use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Runner\Version;

/**
 * Base class for the example tests. It only uses PHPUnit APIs that exist from
 * PHPUnit 9.6 to 13, so the tests run on PHP 7.4 to 8.6.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * @var list<string>
     */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        // Mockery::close() verifies expectations like once() or with().
        // Count them as assertions, so PHPUnit does not report tests that
        // only check mocks as risky.
        $this->addToAssertionCount(max(0, Mockery::getContainer()->mockery_getExpectationCount()));

        Mockery::close();

        parent::tearDown();
    }

    /**
     * Creates a file with $contents that is deleted after the test.
     */
    protected function createTemporaryFile(string $contents = ''): string
    {
        $file = tempnam(sys_get_temp_dir(), 'example');

        if ($file === false) {
            $this->fail('Could not create a temporary file.');
        }

        file_put_contents($file, $contents);
        $this->temporaryFiles[] = $file;

        return $file;
    }

    /**
     * PHPUnit 13.2 soft-deprecated expectExceptionMessage() in favour of
     * expectExceptionMessageIsOrContains(), which older versions do not have.
     */
    protected function expectExceptionMessageContains(string $message): void
    {
        if (version_compare(Version::id(), '13.2', '>=')) {
            $this->expectExceptionMessageIsOrContains($message);

            return;
        }

        $this->expectExceptionMessage($message);
    }
}
