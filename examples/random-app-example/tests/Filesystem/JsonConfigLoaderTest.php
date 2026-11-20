<?php

namespace Tests\Filesystem;

use Mockery;
use PHP\PHP;
use Random\Filesystem\JsonConfigLoader;
use RuntimeException;
use Tests\TestCase;

final class JsonConfigLoaderTest extends TestCase
{
    public function test_loads_a_json_object(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('is_file')->once()->with('/etc/app.json')->andReturn(true);
        $php->shouldReceive('is_readable')->once()->with('/etc/app.json')->andReturn(true);
        $php->shouldReceive('file_get_contents')->once()->with('/etc/app.json')->andReturn('{"debug": true, "name": "app"}');

        $config = (new JsonConfigLoader($php))->load('/etc/app.json');

        $this->assertSame(['debug' => true, 'name' => 'app'], $config);
    }

    public function test_a_missing_file_is_reported(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('is_file')->once()->andReturn(false);
        $php->shouldReceive('is_readable')->never();
        $php->shouldReceive('file_get_contents')->never();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageContains('Config file "/etc/missing.json" is not readable.');

        (new JsonConfigLoader($php))->load('/etc/missing.json');
    }

    public function test_an_unreadable_file_is_reported(): void
    {
        // chmod() cannot make a file unreadable on Windows or for the root
        // user (in Docker, for example). Fake is_readable() only, and let
        // is_file() run for real with passthru().
        $path = $this->createTemporaryFile('{"debug": true}');

        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('is_file')->once()->passthru();
        $php->shouldReceive('is_readable')->once()->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageContains('is not readable');

        (new JsonConfigLoader($php))->load($path);
    }

    public function test_a_read_error_is_reported(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('is_file')->andReturn(true);
        $php->shouldReceive('is_readable')->andReturn(true);
        $php->shouldReceive('file_get_contents')->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageContains('could not be read');

        (new JsonConfigLoader($php))->load('/etc/app.json');
    }

    public function test_invalid_json_is_reported(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('is_file')->andReturn(true);
        $php->shouldReceive('is_readable')->andReturn(true);
        $php->shouldReceive('file_get_contents')->andReturn('not json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageContains('must contain a JSON object');

        (new JsonConfigLoader($php))->load('/etc/app.json');
    }

    public function test_reads_a_real_file_with_php(): void
    {
        $path = $this->createTemporaryFile('{"cache": {"ttl": 60}}');

        $config = (new JsonConfigLoader(new PHP()))->load($path);

        $this->assertSame(['cache' => ['ttl' => 60]], $config);
    }
}
