<?php

namespace Tests\Clock;

use Mockery;
use PHP\PHP;
use Random\Clock\Stopwatch;
use Tests\TestCase;

final class StopwatchTest extends TestCase
{
    public function test_returns_the_elapsed_seconds(): void
    {
        // Consecutive calls return the values in order.
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('microtime')->twice()->with(true)->andReturn(100.0, 100.25);

        $called = false;
        $seconds = (new Stopwatch($php))->measure(function () use (&$called): void {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertSame(0.25, $seconds);
    }

    public function test_measures_real_time_with_php(): void
    {
        $seconds = (new Stopwatch(new PHP()))->measure(function (): void {
            usleep(1000);
        });

        $this->assertGreaterThan(0.0, $seconds);
    }
}
