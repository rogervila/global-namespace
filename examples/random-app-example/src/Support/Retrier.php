<?php

namespace Random\Support;

use Exception;
use InvalidArgumentException;
use PHP\PHPInterface;

/**
 * Use case: sleeping.
 *
 * Retrier waits between attempts with usleep(). Tests fake usleep(), so they
 * run instantly and can check every delay.
 */
class Retrier
{
    private PHPInterface $php;

    private int $attempts;

    private int $delayMicroseconds;

    /**
     * @throws InvalidArgumentException When $attempts is smaller than 1.
     */
    public function __construct(PHPInterface $php, int $attempts = 3, int $delayMicroseconds = 100000)
    {
        if ($attempts < 1) {
            throw new InvalidArgumentException('Retrier needs at least 1 attempt.');
        }

        $this->php = $php;
        $this->attempts = $attempts;
        $this->delayMicroseconds = $delayMicroseconds;
    }

    /**
     * Calls $operation until it succeeds or the attempts run out. The delay
     * grows after every failed attempt: 1 x delay, 2 x delay, 3 x delay...
     *
     * @return mixed What $operation returns.
     *
     * @throws Exception The last exception when every attempt fails.
     */
    public function run(callable $operation)
    {
        $attempt = 1;

        while (true) {
            try {
                return $operation();
            } catch (Exception $exception) {
                if ($attempt >= $this->attempts) {
                    throw $exception;
                }

                $this->php::usleep($this->delayMicroseconds * $attempt);
                $attempt++;
            }
        }
    }
}
