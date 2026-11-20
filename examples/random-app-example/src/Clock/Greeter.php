<?php

namespace Random\Clock;

use PHP\PHPInterface;

/**
 * Use case: code that depends on the current time.
 *
 * Tests fake date(), so every greeting can be checked at any time of day.
 */
class Greeter
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    public function greet(string $name): string
    {
        // "G" is the hour of the day, from 0 to 23.
        $hour = (int) $this->php::date('G');

        if ($hour >= 5 && $hour < 12) {
            return "Good morning, {$name}!";
        }

        if ($hour >= 12 && $hour < 18) {
            return "Good afternoon, {$name}!";
        }

        if ($hour >= 18 && $hour < 22) {
            return "Good evening, {$name}!";
        }

        return "Good night, {$name}!";
    }
}
