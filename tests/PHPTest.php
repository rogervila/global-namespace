<?php

namespace Tests;

use Closure;
use DivisionByZeroError;
use Error;
use Mockery;
use PHP\PHP;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Fixtures\RecordingPHP;
use TypeError;

use function Tests\Fixtures\Php80\call_with_unknown_named_argument;
use function Tests\Fixtures\Php80\encode_with_positional_and_named_arguments;
use function Tests\Fixtures\Php80\pad_with_php;
use function Tests\Fixtures\Php80\record_named_arguments;
use function Tests\Fixtures\Php81\php_strtoupper;
use function Tests\Fixtures\Php85\shout;

/**
 * PHP\PHP forwards every static call to the global function with the same
 * name: PHP::rand(0, 1) runs rand(0, 1) and returns its result.
 */
final class PHPTest extends TestCase
{
    /**
     * Global tests
     */

    public function test_it_forwards_call_to_existing_function(): void
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            http_build_query($data),
            PHP::http_build_query($data)
        );
    }

    public function test_global_function_can_be_mocked(): void
    {
        $result = 1234;

        $php = Mockery::mock(PHP::class);

        $php->shouldReceive('time')
            ->once()
            ->andReturn($result);

        $this->assertEquals(
            $result,
            $php::time()
        );
    }

    /**
     * Here we can test any PHP function to assert that it is working
     */

    public function test_rand(): void
    {
        $result = 1;

        $php = Mockery::mock(PHP::class);

        $php->shouldReceive('rand')
            ->once()
            ->andReturn($result);

        $this->assertEquals(
            $result,
            $php::rand()
        );
    }

    public function test_json_encode(): void
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            json_encode($data),
            PHP::json_encode($data)
        );

        $this->assertEquals(
            json_encode($data, JSON_HEX_TAG),
            PHP::json_encode($data, JSON_HEX_TAG)
        );
    }

    public function test_json_decode(): void
    {
        $data = '{"foo": "bar"}';

        $this->assertEquals(
            json_decode($data),
            PHP::json_decode($data)
        );

        $this->assertEquals(
            json_decode($data, true),
            PHP::json_decode($data, true)
        );
    }

    /**
     * Arguments and return values
     */

    public function test_it_returns_the_function_result_unchanged(): void
    {
        $this->assertSame(3, PHP::strlen('foo'));
        $this->assertSame(3.14, PHP::round(3.14159, 2));
        $this->assertSame(['a', 'b'], PHP::explode(',', 'a,b'));
        $this->assertFalse(PHP::strpos('abc', 'z'));
        $this->assertNull(PHP::usleep(0));
    }

    public function test_it_forwards_every_argument(): void
    {
        $this->assertSame('a-b-c', PHP::sprintf('%s-%s-%s', 'a', 'b', 'c'));
        $this->assertSame(9, PHP::max(4, 9, 1));
        $this->assertSame([1, 2, 3, 4], PHP::array_merge([1, 2], [3], [4]));
    }

    public function test_it_supports_argument_unpacking(): void
    {
        $numbers = [4, 9, 1];

        $this->assertSame(9, PHP::max(...$numbers));
    }

    public function test_it_forwards_callbacks(): void
    {
        $double = function (int $number): int {
            return $number * 2;
        };

        $this->assertSame([2, 4, 6], PHP::array_map($double, [1, 2, 3]));
        $this->assertSame('ABC', PHP::call_user_func('strtoupper', 'abc'));
    }

    /**
     * Function names
     */

    public function test_function_names_are_case_insensitive(): void
    {
        $this->assertSame('ABC', PHP::STRTOUPPER('abc'));
        $this->assertSame('ABC', PHP::StrToUpper('abc'));
    }

    public function test_function_names_can_be_dynamic(): void
    {
        $function = 'strrev';

        $this->assertSame('cba', PHP::$function('abc'));
        $this->assertSame('cba', PHP::{'str' . 'rev'}('abc'));
    }

    public function test_function_names_can_start_with_a_backslash(): void
    {
        $function = '\strrev';

        $this->assertSame('cba', PHP::$function('abc'));
    }

    public function test_it_calls_user_defined_global_functions(): void
    {
        $this->assertSame('Hello, World!', PHP::global_namespace_test_greet('World'));
    }

    public function test_it_calls_namespaced_functions_by_their_fully_qualified_name(): void
    {
        $function = 'Tests\Fixtures\multiply';

        $this->assertSame(6, PHP::$function(2, 3));
    }

    public function test_instance_method_calls_are_not_supported(): void
    {
        // Always call functions with "::". PHP\PHP has __callStatic() but no
        // __call(), so $php->strtoupper() is an undefined method.
        $php = new PHP();

        $this->expectException(Error::class);
        $this->expectExceptionMessageContains('Call to undefined method PHP\PHP::strtoupper()');

        $php->strtoupper('abc'); // @phpstan-ignore method.notFound
    }

    /**
     * PHP\PHP as a callable
     */

    public function test_it_can_be_used_as_a_callable(): void
    {
        $this->assertSame(['A', 'B'], array_map([PHP::class, 'strtoupper'], ['a', 'b']));
        $this->assertSame('ABC', call_user_func(PHP::class . '::strtoupper', 'abc'));
        $this->assertSame('ABC', call_user_func(Closure::fromCallable([PHP::class, 'strtoupper']), 'abc'));
    }

    public function test_every_name_is_reported_as_callable(): void
    {
        // __callStatic() accepts any name, so is_callable() cannot tell
        // whether the function exists. Use function_exists() for that.
        $callable = [PHP::class, 'global_namespace_missing_function'];

        $this->assertTrue(is_callable($callable));
        $this->assertFalse(function_exists('global_namespace_missing_function'));
    }

    /**
     * Errors and exceptions
     */

    public function test_exceptions_thrown_by_the_function_are_not_caught(): void
    {
        $this->expectException(DivisionByZeroError::class);

        PHP::intdiv(1, 0);
    }

    public function test_errors_thrown_by_the_function_are_not_caught(): void
    {
        // PHP 8 throws a ValueError, PHP 7 throws an Error.
        // ValueError extends Error.
        $this->expectException(Error::class);

        PHP::random_int(10, 1);
    }

    public function test_calling_a_missing_function_fails(): void
    {
        if (self::phpVersionIsAtLeast(80000)) {
            $this->expectException(TypeError::class);
            $this->expectExceptionMessageContains('function "global_namespace_missing_function" not found or invalid function name');

            PHP::global_namespace_missing_function();

            return;
        }

        // PHP 7 raises a warning and returns null instead of throwing.
        $captured = self::captureErrors(function () {
            return PHP::global_namespace_missing_function();
        });

        $this->assertNull($captured['result']);
        $this->assertCount(1, $captured['errors']);
        $this->assertSame(E_WARNING, $captured['errors'][0]['level']);
        $this->assertStringContainsString(
            "function 'global_namespace_missing_function' not found or invalid function name",
            $captured['errors'][0]['message']
        );
    }

    /**
     * @dataProvider languageConstructs
     */
    #[DataProvider('languageConstructs')]
    public function test_language_constructs_are_not_functions(string $construct): void
    {
        if (self::phpVersionIsAtLeast(80000)) {
            $this->expectException(TypeError::class);
            $this->expectExceptionMessageContains(sprintf('function "%s" not found or invalid function name', $construct));

            PHP::$construct('value');

            return;
        }

        // PHP 7 raises a warning and returns null instead of throwing.
        $captured = self::captureErrors(function () use ($construct) {
            return PHP::$construct('value');
        });

        $this->assertNull($captured['result']);
        $this->assertCount(1, $captured['errors']);
        $this->assertStringContainsString(
            sprintf("function '%s' not found or invalid function name", $construct),
            $captured['errors'][0]['message']
        );
    }

    /**
     * Language constructs look like functions but cannot be called
     * dynamically. exit() and die() are not listed: they are real
     * functions since PHP 8.4.
     *
     * @return array<string, array{string}>
     */
    public static function languageConstructs(): array
    {
        return [
            'isset' => ['isset'],
            'empty' => ['empty'],
            'unset' => ['unset'],
            'echo' => ['echo'],
            'print' => ['print'],
            'eval' => ['eval'],
            'include' => ['include'],
            'require' => ['require'],
        ];
    }

    public function test_by_reference_arguments_are_not_written_back(): void
    {
        $numbers = [3, 1, 2];

        $captured = self::captureErrors(function () use (&$numbers) {
            return PHP::sort($numbers);
        });

        // __callStatic() receives copies of the arguments, so sort() changes
        // a copy: the original array keeps its order and PHP raises a warning.
        $this->assertSame([3, 1, 2], $numbers);
        $this->assertCount(1, $captured['errors']);
        $this->assertSame(E_WARNING, $captured['errors'][0]['level']);
        $this->assertStringContainsString('reference', $captured['errors'][0]['message']);
    }

    /**
     * @dataProvider scopeIntrospectionFunctions
     *
     * @param list<mixed> $arguments
     */
    #[DataProvider('scopeIntrospectionFunctions')]
    public function test_scope_introspection_functions_cannot_be_called_dynamically(string $function, array $arguments): void
    {
        $message = sprintf('Cannot call %s() dynamically', $function);

        if (self::phpVersionIsAtLeast(80000)) {
            $this->expectException(Error::class);
            $this->expectExceptionMessageContains($message);

            PHP::$function(...$arguments);

            return;
        }

        // PHP 7 raises a warning instead of throwing.
        $captured = self::captureErrors(function () use ($function, $arguments) {
            return PHP::$function(...$arguments);
        });

        $this->assertCount(1, $captured['errors']);
        $this->assertStringContainsString($message, $captured['errors'][0]['message']);
    }

    /**
     * Functions that read or write the caller's local variables. PHP does
     * not allow dynamic calls to them (PHP 7.1+).
     *
     * @return array<string, array{string, list<mixed>}>
     */
    public static function scopeIntrospectionFunctions(): array
    {
        return [
            'compact' => ['compact', ['value']],
            'extract' => ['extract', [['value' => 1]]],
            'func_get_args' => ['func_get_args', []],
            'get_defined_vars' => ['get_defined_vars', []],
        ];
    }

    /**
     * Modern PHP syntax
     */

    public function test_named_arguments_are_forwarded(): void
    {
        $this->requirePhp(80000, 'Named arguments');
        require_once __DIR__ . '/Fixtures/php80-named-arguments.php';

        $this->assertSame('007', pad_with_php());
        $this->assertSame('{"path":"/home"}', encode_with_positional_and_named_arguments());
    }

    public function test_named_arguments_reach_call_static_as_string_keys(): void
    {
        $this->requirePhp(80000, 'Named arguments');
        require_once __DIR__ . '/Fixtures/php80-named-arguments.php';

        RecordingPHP::$calls = [];

        $this->assertSame('700', record_named_arguments());
        $this->assertSame(
            [['name' => 'str_pad', 'arguments' => [0 => '7', 'length' => 3, 'pad_string' => '0']]],
            RecordingPHP::$calls
        );
    }

    public function test_unknown_named_arguments_throw_an_error(): void
    {
        $this->requirePhp(80000, 'Named arguments');
        require_once __DIR__ . '/Fixtures/php80-named-arguments.php';

        $this->expectException(Error::class);
        $this->expectExceptionMessageContains('Unknown named parameter $text');

        call_with_unknown_named_argument();
    }

    public function test_first_class_callable_syntax_creates_a_closure(): void
    {
        $this->requirePhp(80100, 'First-class callable syntax');
        require_once __DIR__ . '/Fixtures/php81-first-class-callables.php';

        $upper = php_strtoupper();

        $this->assertSame('ABC', $upper('abc'));
        $this->assertSame(['A', 'B'], array_map($upper, ['a', 'b']));
    }

    public function test_pipe_operator(): void
    {
        $this->requirePhp(80500, 'The pipe operator');
        require_once __DIR__ . '/Fixtures/php85-pipe-operator.php';

        $this->assertSame('HELLO', shout('  hello  '));
    }
}
