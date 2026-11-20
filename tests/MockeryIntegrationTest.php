<?php

namespace Tests;

use Mockery;
use Mockery\Exception\BadMethodCallException;
use Mockery\Exception\NoMatchingExpectationException;
use PHP\IgnoreAlways;
use PHP\IgnoreMissing;
use PHP\PHP;
use PHP\PHPInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * How Mockery mocks behave when code calls functions through an injected
 * PHP\PHP instance, like $this->php::rand(0, 1).
 */
final class MockeryIntegrationTest extends TestCase
{
    public function test_a_mocked_function_returns_the_configured_value(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('rand')->once()->with(0, 1)->andReturn(1);

        $this->assertSame(1, $php::rand(0, 1));
    }

    public function test_arguments_that_do_not_match_throw(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('date')->with('G')->andReturn('9');

        $this->expectException(NoMatchingExpectationException::class);

        $php::date('Y');
    }

    public function test_consecutive_calls_return_the_values_in_order(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('microtime')->with(true)->andReturn(1.0, 1.5);

        // The last value repeats when the list runs out.
        $this->assertSame(
            [1.0, 1.5, 1.5],
            [$php::microtime(true), $php::microtime(true), $php::microtime(true)]
        );
    }

    public function test_a_closure_can_compute_the_return_value(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('file_exists')->andReturnUsing(function (string $path): bool {
            return $path === '/etc/app.ini';
        });

        $this->assertTrue($php::file_exists('/etc/app.ini'));
        $this->assertFalse($php::file_exists('/etc/missing.ini'));
    }

    public function test_a_mocked_function_can_throw(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('file_get_contents')->andThrow(new RuntimeException('Disk unavailable'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageContains('Disk unavailable');

        $php::file_get_contents('/var/data.json');
    }

    public function test_call_counts_are_verified(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('usleep')->twice()->with(100);
        $php->shouldReceive('sleep')->never();

        $this->assertNull($php::usleep(100));
        $this->assertNull($php::usleep(100));
    }

    public function test_unexpected_calls_throw(): void
    {
        $php = Mockery::mock(PHP::class);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessageContains('::strtoupper() does not exist on this mock object');

        $php::strtoupper('abc');
    }

    public function test_should_ignore_missing_returns_null_for_unexpected_calls(): void
    {
        $php = Mockery::mock(PHP::class)->shouldIgnoreMissing();
        $php->shouldReceive('rand')->andReturn(4);

        $this->assertSame(4, $php::rand(1, 6));
        $this->assertNull($php::strtoupper('abc'));
    }

    public function test_passthru_calls_the_real_function(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('rand')->andReturn(4);
        $php->shouldReceive('strtoupper')->passthru();

        $this->assertSame(4, $php::rand(1, 6));
        $this->assertSame('ABC', $php::strtoupper('abc'));
    }

    public function test_make_partial_does_not_forward_static_calls(): void
    {
        // makePartial() forwards instance method calls only. Functions called
        // through PHP\PHP are static calls, so declare each real call with
        // ->passthru() instead (see the test above).
        $php = Mockery::mock(PHP::class)->makePartial();

        $this->expectException(BadMethodCallException::class);

        $php::strtoupper('abc');
    }

    public function test_spies_record_calls_for_later_assertions(): void
    {
        $php = Mockery::spy(PHP::class);

        $this->assertNull($php::usleep(250));

        $php->shouldHaveReceived('usleep')->once()->with(250);
        $php->shouldNotHaveReceived('sleep');
    }

    /**
     * @dataProvider mockableTypes
     *
     * @param class-string<PHPInterface> $type
     */
    #[DataProvider('mockableTypes')]
    public function test_every_type_can_be_mocked(string $type): void
    {
        $php = Mockery::mock($type);
        $php->shouldReceive('wp_redirect')->once()->with('/')->andReturn(true);

        $this->assertInstanceOf(PHPInterface::class, $php);
        $this->assertTrue($php::wp_redirect('/'));
    }

    /**
     * @return array<string, array{class-string<PHPInterface>}>
     */
    public static function mockableTypes(): array
    {
        return [
            'PHPInterface' => [PHPInterface::class],
            'PHP' => [PHP::class],
            'IgnoreMissing' => [IgnoreMissing::class],
            'IgnoreAlways' => [IgnoreAlways::class],
        ];
    }

    public function test_only_calls_made_through_the_mock_are_mocked(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('strtoupper')->andReturn('mocked');

        $this->assertSame('mocked', $php::strtoupper('abc'));

        // A hard-coded PHP::strtoupper() call still runs the real function.
        // That is why classes must receive the PHP instance as a dependency.
        $this->assertSame('ABC', PHP::strtoupper('abc'));
    }

    public function test_a_mock_also_accepts_instance_calls(): void
    {
        // $php->strtoupper() works on a mock, but PHP\PHP has no __call(), so
        // the same code fails in production. Always call functions with "::".
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('strtoupper')->andReturn('mocked');

        $this->assertSame('mocked', $php->strtoupper('abc')); // @phpstan-ignore method.notFound
    }

    public function test_static_calls_reach_the_first_mock_of_a_class(): void
    {
        // Mocks with the same configuration share one generated class, and a
        // static call on that class reaches the first mock created in the
        // test. Put every expectation on a single mock.
        $first = Mockery::mock(PHP::class);
        $first->shouldReceive('time')->andReturn(1);

        $second = Mockery::mock(PHP::class);
        $second->shouldReceive('time')->andReturn(2);

        $this->assertSame(get_class($first), get_class($second));
        $this->assertSame(1, $first::time());
        $this->assertSame(1, $second::time());
    }

    public function test_exit_can_be_mocked(): void
    {
        // exit() is a real function since PHP 8.4, so PHP::exit() can replace
        // it there. A mock never stops the script, on any PHP version.
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('exit')->once()->with(3);

        $this->assertNull($php::exit(3));
    }
}
