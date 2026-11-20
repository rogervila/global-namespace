<?php

namespace Tests\Network;

use Mockery;
use PHP\PHP;
use Random\Network\HostResolver;
use RuntimeException;
use Tests\TestCase;

final class HostResolverTest extends TestCase
{
    public function test_resolves_a_host_name(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('gethostbyname')->once()->with('example.com')->andReturn('93.184.215.14');

        $this->assertSame('93.184.215.14', (new HostResolver($php))->resolve('example.com'));
    }

    public function test_an_unknown_host_throws(): void
    {
        // On failure gethostbyname() returns the host name it received.
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('gethostbyname')->once()->with('unknown.invalid')->andReturn('unknown.invalid');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageContains('Could not resolve host "unknown.invalid".');

        (new HostResolver($php))->resolve('unknown.invalid');
    }

    public function test_ip_addresses_skip_the_lookup(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('gethostbyname')->never();

        $resolver = new HostResolver($php);

        $this->assertSame('127.0.0.1', $resolver->resolve('127.0.0.1'));
        $this->assertSame('::1', $resolver->resolve('::1'));
    }
}
