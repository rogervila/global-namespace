<?php

namespace Random\Network;

use PHP\PHPInterface;
use RuntimeException;

/**
 * Use case: network calls.
 *
 * gethostbyname() needs DNS. Tests fake it, so they run offline and can
 * check the failure branch.
 */
class HostResolver
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    /**
     * Returns $host unchanged when it is already an IP address (IPv4 or
     * IPv6), otherwise its IPv4 address.
     *
     * @throws RuntimeException When the host cannot be resolved.
     */
    public function resolve(string $host): string
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $host;
        }

        $address = $this->php::gethostbyname($host);

        // gethostbyname() returns the host name unchanged when it fails.
        if (!is_string($address) || $address === $host) {
            throw new RuntimeException(sprintf('Could not resolve host "%s".', $host));
        }

        return $address;
    }
}
