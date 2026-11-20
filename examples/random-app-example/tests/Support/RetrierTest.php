<?php

namespace Tests\Support;

use InvalidArgumentException;
use Mockery;
use PHP\PHP;
use PHP\PHPInterface;
use Random\Support\Retrier;
use RuntimeException;
use Tests\TestCase;

final class RetrierTest extends TestCase
{
    public function test_returns_the_first_successful_result(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('usleep')->never();

        $result = (new Retrier($php))->run(function (): string {
            return 'done';
        });

        $this->assertSame('done', $result);
    }

    public function test_waits_longer_after_each_failure(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('usleep')->once()->with(100)->ordered();
        $php->shouldReceive('usleep')->once()->with(200)->ordered();

        $attempts = 0;
        $result = (new Retrier($php, 3, 100))->run(function () use (&$attempts): string {
            $attempts++;

            if ($attempts < 3) {
                throw new RuntimeException('Temporary failure');
            }

            return 'done';
        });

        $this->assertSame('done', $result);
        $this->assertSame(3, $attempts);
    }

    public function test_throws_the_last_exception_when_every_attempt_fails(): void
    {
        // A spy accepts every call and records it for later assertions.
        $php = Mockery::spy(PHPInterface::class);

        $attempts = 0;

        try {
            (new Retrier($php, 3, 100))->run(function () use (&$attempts): void {
                $attempts++;

                throw new RuntimeException('Attempt ' . $attempts);
            });

            $this->fail('The last exception should be thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Attempt 3', $exception->getMessage());
        }

        $php->shouldHaveReceived('usleep')->with(100)->once();
        $php->shouldHaveReceived('usleep')->with(200)->once();
    }

    public function test_needs_at_least_one_attempt(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Retrier(new PHP(), 0);
    }
}
