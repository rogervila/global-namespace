<?php

namespace Tests\Http;

use LogicException;
use Mockery;
use PHP\IgnoreAlways;
use PHP\PHP;
use Random\Http\Redirector;
use Tests\TestCase;

final class RedirectorTest extends TestCase
{
    public function test_sends_a_location_header(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('headers_sent')->once()->andReturn(false);
        $php->shouldReceive('header')->once()->with('Location: /login', true, 302);

        (new Redirector($php))->redirect('/login');
    }

    public function test_sends_a_custom_status_code(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('headers_sent')->andReturn(false);
        $php->shouldReceive('header')->once()->with('Location: /new-home', true, 301);

        (new Redirector($php))->redirect('/new-home', 301);
    }

    public function test_fails_when_the_headers_were_already_sent(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('headers_sent')->once()->andReturn(true);
        $php->shouldReceive('header')->never();

        $this->expectException(LogicException::class);

        (new Redirector($php))->redirect('/login');
    }

    public function test_ignore_always_turns_the_redirect_into_a_no_op(): void
    {
        // headers_sent() returns null (falsy) and header() is never called.
        $this->expectNotToPerformAssertions();

        (new Redirector(new IgnoreAlways()))->redirect('/login');
    }
}
