<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Mockery;
use Random\App;

class AppTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_yay()
    {
        $php = Mockery::mock(\PHP\PHP::class);
        $php->shouldReceive('rand')
            ->once()
            ->andReturn(1);

        $app = new App($php);

        $this->assertEquals(
            'Yay!',
            $app->run()
        );
    }

    public function test_returns_nope()
    {
        $php = Mockery::mock(\PHP\PHP::class);
        $php->shouldReceive('rand')
            ->once()
            ->andReturn(0);

        $app = new App($php);

        $this->assertEquals(
            'Nope :(',
            $app->run()
        );
    }

    public function test_without_mock()
    {
        $app = new App(new \PHP\PHP());

        $this->assertContains(
            $app->run(),
            ['Yay!', 'Nope :(']
        );
    }
}
