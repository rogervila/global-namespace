<?php

namespace Random\Text;

use PHP\PHPInterface;

/**
 * Use case: optional extensions.
 *
 * transliterator_transliterate() needs the "intl" extension. Inject
 * PHP\IgnoreMissing: when intl is not installed, the call returns null and
 * the slugger falls back to plain ASCII handling instead of failing.
 */
class Slugger
{
    private PHPInterface $php;

    public function __construct(PHPInterface $php)
    {
        $this->php = $php;
    }

    public function slugify(string $text): string
    {
        $ascii = $this->php::transliterator_transliterate('Any-Latin; Latin-ASCII', $text);

        if (!is_string($ascii)) {
            $ascii = $text;
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii));

        return trim((string) $slug, '-');
    }
}
