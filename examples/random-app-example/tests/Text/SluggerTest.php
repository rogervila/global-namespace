<?php

namespace Tests\Text;

use Mockery;
use PHP\IgnoreMissing;
use Random\Text\Slugger;
use Tests\TestCase;

final class SluggerTest extends TestCase
{
    public function test_transliterates_when_intl_is_installed(): void
    {
        $php = Mockery::mock(IgnoreMissing::class);
        $php->shouldReceive('transliterator_transliterate')
            ->once()
            ->with('Any-Latin; Latin-ASCII', 'Crème Brûlée')
            ->andReturn('Creme Brulee');

        $this->assertSame('creme-brulee', (new Slugger($php))->slugify('Crème Brûlée'));
    }

    public function test_falls_back_when_intl_is_missing(): void
    {
        // This is what IgnoreMissing returns when the function does not exist.
        $php = Mockery::mock(IgnoreMissing::class);
        $php->shouldReceive('transliterator_transliterate')->andReturn(null);

        $slugger = new Slugger($php);

        $this->assertSame('hello-world', $slugger->slugify('Hello, World!'));
        $this->assertSame('cr-me-br-l-e', $slugger->slugify('Crème Brûlée'));
    }

    public function test_works_with_or_without_intl_using_ignore_missing(): void
    {
        $slug = (new Slugger(new IgnoreMissing()))->slugify('Crème Brûlée');

        $this->assertSame(extension_loaded('intl') ? 'creme-brulee' : 'cr-me-br-l-e', $slug);
    }
}
