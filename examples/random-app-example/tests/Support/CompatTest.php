<?php

namespace Tests\Support;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHP\PHP;
use Random\Support\Compat;
use Tests\TestCase;

final class CompatTest extends TestCase
{
    public function test_array_find_uses_the_native_function_when_it_exists(): void
    {
        $callback = function (int $value): bool {
            return $value > 1;
        };

        $php = $this->phpWhereFunctionExists('array_find', true);
        $php->shouldReceive('array_find')->once()->with([1, 2, 3], $callback)->andReturn(2);

        $this->assertSame(2, (new Compat($php))->arrayFind([1, 2, 3], $callback));
    }

    public function test_array_find_falls_back_to_plain_php(): void
    {
        $php = $this->phpWhereFunctionExists('array_find', false);
        $php->shouldReceive('array_find')->never();

        $compat = new Compat($php);

        $this->assertSame('b', $compat->arrayFind(['x' => 'a', 'y' => 'b'], function (string $value, string $key): bool {
            return $key === 'y';
        }));
        $this->assertNull($compat->arrayFind([1, 2], function (int $value): bool {
            return $value > 5;
        }));
    }

    public function test_array_first_and_last_use_the_native_functions_when_they_exist(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('function_exists')->with('array_first')->andReturn(true);
        $php->shouldReceive('function_exists')->with('array_last')->andReturn(true);
        $php->shouldReceive('array_first')->once()->with(['a', 'b'])->andReturn('a');
        $php->shouldReceive('array_last')->once()->with(['a', 'b'])->andReturn('b');

        $compat = new Compat($php);

        $this->assertSame('a', $compat->arrayFirst(['a', 'b']));
        $this->assertSame('b', $compat->arrayLast(['a', 'b']));
    }

    public function test_array_first_and_last_fall_back_to_plain_php(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('function_exists')->andReturn(false);

        $compat = new Compat($php);

        $this->assertSame('a', $compat->arrayFirst([3 => 'a', 1 => 'b']));
        $this->assertSame('b', $compat->arrayLast([3 => 'a', 1 => 'b']));
        $this->assertNull($compat->arrayFirst([]));
        $this->assertNull($compat->arrayLast([]));
    }

    public function test_clamp_uses_the_native_function_when_it_exists(): void
    {
        $php = $this->phpWhereFunctionExists('clamp', true);
        $php->shouldReceive('clamp')->once()->with(15, 0, 10)->andReturn(10);

        $this->assertSame(10, (new Compat($php))->clamp(15, 0, 10));
    }

    public function test_clamp_falls_back_to_plain_php(): void
    {
        $php = $this->phpWhereFunctionExists('clamp', false);

        $compat = new Compat($php);

        $this->assertSame(10, $compat->clamp(15, 0, 10));
        $this->assertSame(0, $compat->clamp(-5, 0, 10));
        $this->assertSame(5.5, $compat->clamp(5.5, 0, 10));
    }

    public function test_clamp_rejects_an_invalid_range_on_both_branches(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('function_exists')->never();

        $this->expectException(InvalidArgumentException::class);

        (new Compat($php))->clamp(5, 10, 0);
    }

    public function test_every_method_works_on_the_running_php_version(): void
    {
        // No mock: the native functions run when this PHP version has them.
        $compat = new Compat(new PHP());

        $this->assertSame(2, $compat->arrayFind([1, 2, 3], function (int $value): bool {
            return $value > 1;
        }));
        $this->assertSame('a', $compat->arrayFirst(['a', 'b', 'c']));
        $this->assertSame('c', $compat->arrayLast(['a', 'b', 'c']));
        $this->assertSame(10, $compat->clamp(15, 0, 10));
    }

    /**
     * A mock of PHP\PHP where function_exists($function) returns $exists.
     *
     * @return MockInterface&PHP
     */
    private function phpWhereFunctionExists(string $function, bool $exists): MockInterface
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('function_exists')->with($function)->andReturn($exists);

        return $php;
    }
}
