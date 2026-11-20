<?php

namespace Tests;

use DivisionByZeroError;
use PHP\IgnoreMissing as PHP;
use PHPUnit\Framework\Attributes\DataProvider;

use function Tests\Fixtures\Php80\call_missing_function_with_ignore_missing;
use function Tests\Fixtures\Php80\pad_with_ignore_missing;

/**
 * PHP\IgnoreMissing forwards calls to functions that exist and returns null,
 * without any warning or error, when the function does not exist.
 */
final class IgnoreMissingTest extends TestCase
{
    public function test_it_forwards_call_to_existing_function(): void
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            http_build_query($data),
            PHP::http_build_query($data)
        );
    }

    public function test_it_skips_missing_function(): void
    {
        $this->assertNull(PHP::foo('bar'));
    }

    public function test_it_returns_the_function_result_unchanged(): void
    {
        // false from strpos() stays false: only missing functions return null.
        $this->assertFalse(PHP::strpos('abc', 'z'));
        $this->assertSame(0, PHP::strpos('abc', 'a'));
        $this->assertSame(['a', 'b'], PHP::explode(',', 'a,b'));
    }

    public function test_a_missing_function_raises_no_error(): void
    {
        $captured = self::captureErrors(function () {
            return PHP::global_namespace_missing_function('any', 'arguments');
        });

        $this->assertNull($captured['result']);
        $this->assertSame([], $captured['errors']);
    }

    /**
     * @dataProvider languageConstructs
     */
    #[DataProvider('languageConstructs')]
    public function test_language_constructs_are_treated_as_missing_functions(string $construct): void
    {
        $this->assertNull(PHP::$construct('value'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function languageConstructs(): array
    {
        return [
            'isset' => ['isset'],
            'empty' => ['empty'],
            'echo' => ['echo'],
            'print' => ['print'],
            'eval' => ['eval'],
            'include' => ['include'],
        ];
    }

    public function test_it_calls_user_defined_and_namespaced_functions(): void
    {
        $namespaced = 'Tests\Fixtures\multiply';

        $this->assertSame('Hello, World!', PHP::global_namespace_test_greet('World'));
        $this->assertSame(6, PHP::$namespaced(2, 3));
    }

    public function test_function_names_are_case_insensitive_and_may_start_with_a_backslash(): void
    {
        $withBackslash = '\strrev';

        $this->assertSame('ABC', PHP::STRTOUPPER('abc'));
        $this->assertSame('cba', PHP::$withBackslash('abc'));
    }

    public function test_exceptions_thrown_by_existing_functions_are_not_caught(): void
    {
        $this->expectException(DivisionByZeroError::class);

        PHP::intdiv(1, 0);
    }

    public function test_by_reference_arguments_are_not_written_back(): void
    {
        $numbers = [3, 1, 2];

        $captured = self::captureErrors(function () use (&$numbers) {
            return PHP::sort($numbers);
        });

        $this->assertSame([3, 1, 2], $numbers);
        $this->assertCount(1, $captured['errors']);
        $this->assertStringContainsString('reference', $captured['errors'][0]['message']);
    }

    /**
     * IgnoreMissing lets code call functions from newer PHP versions (or from
     * optional extensions) and get null when they are not available.
     *
     * @dataProvider versionSpecificFunctions
     *
     * @param list<mixed> $arguments
     * @param mixed $expected
     */
    #[DataProvider('versionSpecificFunctions')]
    public function test_functions_added_in_newer_php_versions(string $function, int $since, array $arguments, $expected): void
    {
        if (self::phpVersionIsAtLeast($since)) {
            $this->assertTrue(function_exists($function), sprintf('%s() should exist on PHP %s.', $function, PHP_VERSION));
        }

        $this->assertSame(
            function_exists($function) ? $expected : null,
            PHP::$function(...$arguments)
        );
    }

    /**
     * @return array<string, array{string, int, list<mixed>, mixed}>
     */
    public static function versionSpecificFunctions(): array
    {
        $greaterThanOne = function (int $value): bool {
            return $value > 1;
        };

        return [
            'str_contains (PHP 8.0)' => ['str_contains', 80000, ['haystack', 'st'], true],
            'array_is_list (PHP 8.1)' => ['array_is_list', 80100, [[1, 2]], true],
            'json_validate (PHP 8.3)' => ['json_validate', 80300, ['{"valid": true}'], true],
            'array_find (PHP 8.4)' => ['array_find', 80400, [[1, 2, 3], $greaterThanOne], 2],
            'array_any (PHP 8.4)' => ['array_any', 80400, [[1, 2, 3], $greaterThanOne], true],
            'array_first (PHP 8.5)' => ['array_first', 80500, [['a', 'b', 'c']], 'a'],
            'array_last (PHP 8.5)' => ['array_last', 80500, [['a', 'b', 'c']], 'c'],
            'clamp (PHP 8.6)' => ['clamp', 80600, [15, 0, 10], 10],
        ];
    }

    public function test_named_arguments(): void
    {
        $this->requirePhp(80000, 'Named arguments');
        require_once __DIR__ . '/Fixtures/php80-named-arguments.php';

        $this->assertSame('007', pad_with_ignore_missing());
        $this->assertNull(call_missing_function_with_ignore_missing());
    }
}
