<?php

namespace Tests\Security;

use InvalidArgumentException;
use Mockery;
use PHP\IgnoreAlways;
use PHP\PHP;
use Random\Security\TokenGenerator;
use RuntimeException;
use Tests\TestCase;

final class TokenGeneratorTest extends TestCase
{
    public function test_encodes_the_random_bytes_as_hexadecimal(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('random_bytes')->once()->with(4)->andReturn("\x00\x01\xfe\xff");

        $this->assertSame('0001feff', (new TokenGenerator($php))->generate(4));
    }

    public function test_uses_16_bytes_by_default(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('random_bytes')->once()->with(16)->andReturn(str_repeat("\xab", 16));

        $this->assertSame(str_repeat('ab', 16), (new TokenGenerator($php))->generate());
    }

    public function test_rejects_less_than_one_byte(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('random_bytes')->never();

        $this->expectException(InvalidArgumentException::class);

        (new TokenGenerator($php))->generate(0);
    }

    public function test_fails_when_no_bytes_are_returned(): void
    {
        // IgnoreAlways returns null for every call, which shows how the class
        // handles an unexpected return value.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageContains('Could not generate random bytes.');

        (new TokenGenerator(new IgnoreAlways()))->generate();
    }

    public function test_real_tokens_are_random_with_php(): void
    {
        $generator = new TokenGenerator(new PHP());

        $first = $generator->generate();
        $second = $generator->generate();

        $this->assertSame(1, preg_match('/^[0-9a-f]{32}$/', $first));
        $this->assertNotSame($first, $second);
    }
}
