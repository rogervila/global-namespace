<?php

namespace Tests\Logging;

use Mockery;
use PHP\IgnoreAlways;
use PHP\PHP;
use PHP\PHPInterface;
use Random\Logging\ErrorLogLogger;
use Tests\TestCase;

final class ErrorLogLoggerTest extends TestCase
{
    public function test_formats_the_message(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('error_log')->once()->with('[ERROR] Disk full');

        (new ErrorLogLogger($php))->log('error', 'Disk full');
    }

    public function test_writes_to_the_php_error_log_with_php(): void
    {
        $log = $this->logWith(new PHP(), 'warning', 'Low memory');

        $this->assertStringContainsString('[WARNING] Low memory', $log);
    }

    public function test_ignore_always_silences_the_logger(): void
    {
        $log = $this->logWith(new IgnoreAlways(), 'warning', 'Low memory');

        $this->assertSame('', $log);
    }

    /**
     * Logs one message with $php while the "error_log" setting points to a
     * temporary file. Returns the contents of that file.
     */
    private function logWith(PHPInterface $php, string $level, string $message): string
    {
        $file = $this->createTemporaryFile();
        $previous = ini_get('error_log');
        ini_set('error_log', $file);

        try {
            (new ErrorLogLogger($php))->log($level, $message);
        } finally {
            ini_set('error_log', $previous === false ? '' : $previous);
        }

        return (string) file_get_contents($file);
    }
}
