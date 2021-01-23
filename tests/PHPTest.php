<?php

use PHP\PHP;
use PHPUnit\Framework\TestCase;

final class PHPTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Global tests
     */

    public function test_it_forwards_call_to_existing_function()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            http_build_query($data),
            PHP::http_build_query($data)
        );
    }

    public function test_global_function_can_be_mocked()
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

    public function test_rand()
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

    public function test_json_encode()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            json_encode($data),
            PHP::json_encode($data)
        );

        $this->assertEquals(
            json_encode($data, true),
            PHP::json_encode($data, true)
        );
    }

    public function test_json_decode()
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
}
