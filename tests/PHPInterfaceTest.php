<?php

namespace Tests;

use PHP\IgnoreAlways;
use PHP\IgnoreMissing;
use PHP\PHP;
use PHP\PHPInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\Fixtures\RecordingPHP;

/**
 * PHPInterface is the contract of PHP\PHP, PHP\IgnoreMissing and
 * PHP\IgnoreAlways. Type-hint it to accept any of them, or a test double.
 */
final class PHPInterfaceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RecordingPHP::$calls = [];
    }

    /**
     * @dataProvider implementations
     *
     * @param class-string<PHPInterface> $class
     */
    #[DataProvider('implementations')]
    public function test_every_implementation_follows_the_contract(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $method = new ReflectionMethod($class, '__callStatic');

        $this->assertTrue($reflection->implementsInterface(PHPInterface::class));
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        // Mockery mocks a class by extending it, and test doubles like
        // RecordingPHP extend PHP\PHP: the classes must not be final.
        $this->assertFalse($reflection->isFinal());
    }

    /**
     * @return array<string, array{class-string<PHPInterface>}>
     */
    public static function implementations(): array
    {
        return [
            'PHP' => [PHP::class],
            'IgnoreMissing' => [IgnoreMissing::class],
            'IgnoreAlways' => [IgnoreAlways::class],
        ];
    }

    public function test_the_interface_only_declares_call_static(): void
    {
        $methods = array_map(function (ReflectionMethod $method): string {
            return $method->getName();
        }, (new ReflectionClass(PHPInterface::class))->getMethods());

        $this->assertSame(['__callStatic'], $methods);
    }

    /**
     * @dataProvider builtInCalls
     *
     * @param list<mixed> $arguments
     */
    #[DataProvider('builtInCalls')]
    public function test_the_implementations_side_by_side(string $function, array $arguments): void
    {
        $callable = $function;
        $this->assertTrue(is_callable($callable));

        $expected = call_user_func_array($callable, $arguments);

        $this->assertSame($expected, PHP::$function(...$arguments));
        $this->assertSame($expected, IgnoreMissing::$function(...$arguments));
        $this->assertNull(IgnoreAlways::$function(...$arguments));
    }

    /**
     * @return array<string, array{string, list<mixed>}>
     */
    public static function builtInCalls(): array
    {
        return [
            'string' => ['str_repeat', ['ab', 2]],
            'math' => ['max', [1, 5, 3]],
            'array' => ['array_reverse', [[1, 2, 3]]],
            'json' => ['json_encode', [['a' => 1]]],
            'url' => ['http_build_query', [['foo' => 'bar', 'baz' => 1]]],
            'hash' => ['md5', ['global-namespace']],
            'false result' => ['strpos', ['abc', 'z']],
            'void function' => ['usleep', [0]],
        ];
    }

    public function test_any_implementation_can_be_injected(): void
    {
        $shout = function (PHPInterface $php, string $text) {
            return $php::strtoupper($text);
        };

        $custom = new class () implements PHPInterface {
            /**
             * @param string $name
             * @param array<int|string, mixed> $arguments
             *
             * @return string
             */
            public static function __callStatic($name, $arguments)
            {
                return 'custom ' . $name;
            }
        };

        $this->assertSame('HELLO', $shout(new PHP(), 'hello'));
        $this->assertSame('HELLO', $shout(new IgnoreMissing(), 'hello'));
        $this->assertNull($shout(new IgnoreAlways(), 'hello'));
        $this->assertSame('custom strtoupper', $shout($custom, 'hello'));
        $this->assertSame('HELLO', $shout(new RecordingPHP(), 'hello'));
    }

    public function test_php_can_be_extended_to_build_test_doubles(): void
    {
        $this->assertSame('cba', RecordingPHP::strrev('abc'));
        $this->assertSame(3, RecordingPHP::strlen('abc'));

        $this->assertSame([
            ['name' => 'strrev', 'arguments' => ['abc']],
            ['name' => 'strlen', 'arguments' => ['abc']],
        ], RecordingPHP::$calls);
    }
}
