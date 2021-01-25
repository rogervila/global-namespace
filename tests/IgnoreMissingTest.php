<?php

use PHP\IgnoreMissing as PHP;
use PHPUnit\Framework\TestCase;

final class IgnoreMissingTest extends TestCase
{
    public function test_it_forwards_call_to_existing_function()
    {
        $data = ['foo' => 'bar'];

        $this->assertEquals(
            http_build_query($data),
            PHP::http_build_query($data)
        );
    }

    public function test_it_skips_missing_function()
    {
        $this->assertNull(PHP::foo('bar'));
    }
}
