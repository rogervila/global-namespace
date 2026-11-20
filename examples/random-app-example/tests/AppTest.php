<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Mockery;
use Random\App;
use Tests\Doubles\FakePHP;

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

    public function test_returns_yay_with_a_hand_written_fake()
    {
        FakePHP::reset();
        FakePHP::stub('rand', 1);

        $app = new App(new FakePHP());

        $this->assertEquals('Yay!', $app->run());
        $this->assertSame([['rand', [0, 1]]], FakePHP::calls());
    }

    public function test_returns_nope_with_a_hand_written_fake()
    {
        FakePHP::reset();
        FakePHP::stub('rand', 0);

        $app = new App(new FakePHP());

        $this->assertEquals('Nope :(', $app->run());
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
