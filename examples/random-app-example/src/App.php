<?php

namespace Random;

use PHP\PHP;

class App
{
    private PHP $php;

    public function __construct(PHP $php)
    {
        $this->php = $php;
    }

    public function run(): string
    {
        $result = $this->php::rand(0, 1);

        if ($result == 1) {
            return 'Yay!';
        }

        return 'Nope :(';
    }
}
