<?php

namespace Tests\Config;

use Mockery;
use PHP\PHP;
use PHPUnit\Framework\Attributes\DataProvider;
use Random\Config\Environment;
use Tests\Doubles\FakePHP;
use Tests\TestCase;

final class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        FakePHP::reset();
    }

    /**
     * This test uses the hand-written FakePHP double instead of Mockery.
     *
     * @dataProvider environments
     *
     * @param string|false $value What getenv('APP_ENV') returns.
     */
    #[DataProvider('environments')]
    public function test_reads_app_env($value, string $name, bool $production): void
    {
        FakePHP::stub('getenv', $value);

        $environment = new Environment(new FakePHP());

        $this->assertSame($name, $environment->name());
        $this->assertSame($production, $environment->isProduction());
        $this->assertSame([['getenv', ['APP_ENV']], ['getenv', ['APP_ENV']]], FakePHP::calls());
    }

    /**
     * @return array<string, array{string|false, string, bool}>
     */
    public static function environments(): array
    {
        return [
            'not set' => [false, 'production', true],
            'empty' => ['', 'production', true],
            'production' => ['production', 'production', true],
            'testing' => ['testing', 'testing', false],
        ];
    }

    public function test_the_same_check_with_mockery(): void
    {
        $php = Mockery::mock(PHP::class);
        $php->shouldReceive('getenv')->once()->with('APP_ENV')->andReturn('staging');

        $this->assertSame('staging', (new Environment($php))->name());
    }
}
