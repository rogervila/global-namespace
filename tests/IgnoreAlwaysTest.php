<?php

use PHP\IgnoreAlways as PHP;
use PHPUnit\Framework\TestCase;

final class IgnoreAlwaysTest extends TestCase
{
    public function test_it_skips_existing_function()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            http_build_query($data),
            'foo=bar'
        );

        $this->assertNull(PHP::http_build_query($data));
    }

    public function test_it_skips_missing_function()
    {
        $this->assertNull(PHP::foo('bar'));
    }
}
