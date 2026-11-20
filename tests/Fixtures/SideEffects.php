<?php

namespace Tests\Fixtures;

/**
 * Counts the calls to global_namespace_test_side_effect().
 */
final class SideEffects
{
    /**
     * @var int
     */
    public static $calls = 0;
}
