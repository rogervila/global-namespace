<?php

namespace Tests;

use PHP\IgnoreAlways as PHP;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\SideEffects;

use function Tests\Fixtures\Php80\pad_with_ignore_always;
use function Tests\Fixtures\Php81\ignore_always_strtoupper;

/**
 * PHP\IgnoreAlways never calls any function: every static call returns null.
 * It works like a null object for code with side effects.
 */
final class IgnoreAlwaysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SideEffects::$calls = 0;
    }

    public function test_it_skips_existing_function(): void
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            http_build_query($data),
            'foo=bar'
        );

        $this->assertNull(PHP::http_build_query($data));
    }

    public function test_it_skips_missing_function(): void
    {
        $this->assertNull(PHP::foo('bar'));
    }

    public function test_it_never_runs_the_function(): void
    {
        $this->assertNull(PHP::global_namespace_test_side_effect());
        $this->assertSame(0, SideEffects::$calls);

        // Called directly, the function does run.
        $this->assertSame(1, global_namespace_test_side_effect());
    }

    /**
     * @dataProvider callsThatWouldFail
     *
     * @param list<mixed> $arguments
     */
    #[DataProvider('callsThatWouldFail')]
    public function test_calls_that_would_fail_return_null_without_errors(string $function, array $arguments): void
    {
        $captured = self::captureErrors(function () use ($function, $arguments) {
            return PHP::$function(...$arguments);
        });

        $this->assertNull($captured['result']);
        $this->assertSame([], $captured['errors']);
    }

    /**
     * Each of these calls fails with PHP\PHP. With IgnoreAlways they do not.
     *
     * @return array<string, array{string, list<mixed>}>
     */
    public static function callsThatWouldFail(): array
    {
        return [
            'exception thrown by the function' => ['intdiv', [1, 0]],
            'missing function' => ['global_namespace_missing_function', []],
            'language construct' => ['isset', ['value']],
            'by-reference argument' => ['sort', [[3, 1, 2]]],
            'scope introspection function' => ['compact', ['value']],
            'wrong argument type' => ['strlen', [[]]],
        ];
    }

    public function test_exit_does_not_stop_the_script(): void
    {
        // exit() and die() are real functions since PHP 8.4.
        // IgnoreAlways never calls them, on any PHP version.
        $this->assertNull(PHP::exit(1));
        $this->assertNull(PHP::die('bye'));
    }

    public function test_it_works_as_a_callable(): void
    {
        $this->assertSame([null, null], array_map([PHP::class, 'strtoupper'], ['a', 'b']));
    }

    public function test_named_arguments(): void
    {
        $this->requirePhp(80000, 'Named arguments');
        require_once __DIR__ . '/Fixtures/php80-named-arguments.php';

        $this->assertNull(pad_with_ignore_always());
    }

    public function test_first_class_callable_syntax(): void
    {
        $this->requirePhp(80100, 'First-class callable syntax');
        require_once __DIR__ . '/Fixtures/php81-first-class-callables.php';

        $upper = ignore_always_strtoupper();

        $this->assertNull($upper('abc'));
    }
}
