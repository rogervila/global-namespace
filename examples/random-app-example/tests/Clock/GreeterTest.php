<?php

namespace Tests\Clock;

use Mockery;
use PHP\PHP;
use PHPUnit\Framework\Attributes\DataProvider;
use Random\Clock\Greeter;
use Tests\TestCase;

final class GreeterTest extends TestCase
{
    /**
     * @dataProvider greetings
     */
    #[DataProvider('greetings')]
    public function test_greets_according_to_the_hour(string $hour, string $expected): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('date')->once()->with('G')->andReturn($hour);

        $this->assertSame($expected, (new Greeter($php))->greet('Ada'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function greetings(): array
    {
        return [
            'midnight' => ['0', 'Good night, Ada!'],
            'early morning' => ['5', 'Good morning, Ada!'],
            'before noon' => ['11', 'Good morning, Ada!'],
            'noon' => ['12', 'Good afternoon, Ada!'],
            'evening' => ['18', 'Good evening, Ada!'],
            'late evening' => ['22', 'Good night, Ada!'],
        ];
    }

    public function test_uses_the_real_clock_with_php(): void
    {
        $greeting = (new Greeter(new PHP()))->greet('Ada');

        $this->assertContains($greeting, [
            'Good morning, Ada!',
            'Good afternoon, Ada!',
            'Good evening, Ada!',
            'Good night, Ada!',
        ]);
    }
}
