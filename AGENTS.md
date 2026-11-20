# AGENTS.md

Instructions for AI coding agents (Claude Code, Codex, GitHub Copilot, Cursor, Gemini and others) and for humans who work on this repository. Read this file before you change code. The README explains the library to its users; this file explains how to work on it.

## What this library does

`rogervila/global-namespace` is a small PHP library with no runtime dependencies. `PHP\PHP::anyFunction(...$arguments)` calls the global function `anyFunction(...$arguments)` through `__callStatic()` and returns its result. Applications inject a `PHP` instance and call `$this->php::rand(0, 1)`; tests replace the instance with a Mockery mock or a hand-written double, so code that depends on global functions (`rand()`, `time()`, `file_get_contents()`, `header()`...) becomes testable.

## Repository map

| Path | Contents |
| --- | --- |
| `src/PHPInterface.php` | The contract: `public static function __callStatic($name, $arguments)` |
| `src/PHP.php` | Calls `call_user_func_array($name, $arguments)` |
| `src/IgnoreMissing.php` | Calls the function only if `function_exists($name)`, otherwise returns `null` |
| `src/IgnoreAlways.php` | Never calls anything, always returns `null` |
| `tests/` | PHPUnit tests, namespace `Tests\`. `tests/TestCase.php` has the shared helpers |
| `tests/Fixtures/` | Helper functions and classes. `php80-*.php`, `php81-*.php` and `php85-*.php` use newer syntax and are loaded only on those PHP versions |
| `examples/random-app-example/` | Standalone Composer project with one class and one test class per use case. It installs the library from `../..` (path repository, symlinked) |
| `.github/workflows/` | CI: `build.yml` (push to main), `pull_request.yml`, `example-app.yml` |
| `phpunit.xml`, `phpstan.neon.dist` | Test and static analysis configuration |
| `README.md`, `llms.txt` | User documentation |

## Commands

```sh
composer update          # install the dependencies for the current PHP version
composer test            # PHPUnit (8.5 to 13, depending on the PHP version)
composer test:strict     # also fail on deprecations, notices and warnings (PHP 8.2+ / PHPUnit 11+)
composer analyse         # PHPStan at level max on src/ and tests/ (needs PHPStan 2, so PHP 7.4+)
composer test:coverage   # clover.xml and junit-logfile.xml (PHPUnit 9+, Xdebug or PCOV)

cd examples/random-app-example
composer update && composer test
```

A change is done when `composer test:strict` (or `composer test` on PHP < 8.2), `composer analyse` and the example application tests pass.

## Compatibility rules

The library supports PHP 7.2 to 8.6. These rules keep it that way.

### Library code (`src/`)

- Use PHP 7.2 syntax only: no typed properties, union types, `mixed`, constructor promotion, `match`, named arguments, nullsafe operator, enums, `readonly` or attributes.
- Do not add parameter or return types to `__callStatic()`. User classes and Mockery mocks implement or override it without types; adding types is a breaking change. Use PHPDoc types instead.
- Do not make the classes `final`. Mockery mocks them by extending them, and users extend `PHP\PHP` to write test doubles. `tests/PHPInterfaceTest.php` checks this.
- Keep `require` in `composer.json` empty: no runtime dependencies.
- Any behaviour change needs tests on every PHP version, and updates to `README.md` and `llms.txt`.

### Tests (`tests/`)

The same test files run on PHPUnit 8.5 (PHP 7.2), 9.6 (PHP 7.3 to 8.1), 11.5 (PHP 8.2 and 8.3) and 13 (PHP 8.4 to 8.6).

- Use PHP 7.2 syntax, except in the version-specific fixtures. No arrow functions, typed properties or trailing commas in calls.
- Extend `Tests\TestCase`. It verifies Mockery expectations in `tearDown()` and counts them as assertions.
- Data providers must be `public static` and need both the `@dataProvider` annotation (PHPUnit 8 and 9) and the `#[DataProvider('...')]` attribute on its own line (PHPUnit 10+; PHP 7 reads it as a comment).
- Use `$this->expectExceptionMessageContains()` instead of `expectExceptionMessage()` (soft-deprecated in PHPUnit 13.2).
- Use `self::captureErrors()` to assert PHP warnings. Do not use `expectWarning()` or similar APIs: they differ between PHPUnit versions.
- Use `self::phpVersionIsAtLeast(80000)` for version checks instead of comparing `PHP_VERSION_ID` inline (PHPStan would report the comparison as always true or false).
- Test new syntax (named arguments, first-class callables, pipe operator...) through a fixture file such as `tests/Fixtures/php85-pipe-operator.php`: call `$this->requirePhp(80500, 'Feature')` first, then `require_once` the fixture. Never put that syntax in a `*Test.php` file or in an autoloaded file.
- Do not use APIs that are missing in PHPUnit 8.5, like `assertMatchesRegularExpression()`.
- Never call the real `PHP::exit()` or `PHP::die()`: on PHP 8.4+ they stop the test run. Use a mock or `IgnoreAlways`.

Some behaviour differs between PHP 7 and PHP 8, and the tests assert both:

| Situation | PHP 7 | PHP 8+ |
| --- | --- | --- |
| `PHP::missing_function()` | E_WARNING, returns `null` | `TypeError` |
| `PHP::compact('x')` and other scope functions | E_WARNING `Cannot call compact() dynamically` | `Error` with the same message |
| `PHP::sort($array)` (by-reference parameter) | E_WARNING, array unchanged | E_WARNING, array unchanged |
| `exit` / `die` | Language constructs | Functions since 8.4 |

### Example application (`examples/random-app-example/`)

- PHP 7.4 syntax (typed properties are fine) and PHPUnit 9.6 to 13.
- Every class receives a `PHPInterface` (`App` receives a `PHP`) and calls functions with `$this->php::function()`. Only functions with side effects or changing results go through the proxy.
- Each use case has a class in `src/` and a test in `tests/` with the same sub-namespace. Add new cases to the tables in the example README and in the main README.

## Testing other PHP versions locally

CI covers PHP 7.2 to 8.6 on Linux, Windows and macOS. To reproduce one version locally with Docker, resolve the dependencies for that version on the host, then run the tests in the official image:

```sh
tmp=$(mktemp -d)
cp -r composer.json phpunit.xml src tests "$tmp"
cd "$tmp"
composer config platform.php 7.2.34
composer update --no-interaction --prefer-dist
docker run --rm -v "$PWD":/app -w /app php:7.2-cli php vendor/bin/phpunit
```

PHPUnit needs the `mbstring` extension. Some pre-release images (for example `php:8.6-rc-cli`) do not include it: build an image that runs `docker-php-ext-install mbstring` (it needs the `libonig-dev` package).

## Style

- PSR-12, checked by StyleCI (`.styleci.yml`, preset `psr12`). Four spaces, LF line endings.
- Test methods use the existing `test_snake_case` naming.
- Comments explain why, in short plain sentences. Keep existing comments.

## Do not

- Do not add runtime dependencies or new public classes without discussing it first.
- Do not commit generated files: `vendor/`, `composer.lock`, `clover.xml`, `junit-logfile.xml`, `.phpunit.result.cache` are ignored.
- Do not lower the PHPStan level or add broad ignore rules. The ignore rules in `phpstan.neon.dist` exist because the library resolves calls at runtime.
