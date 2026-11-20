# Example application

A small Composer project that shows how to use [`rogervila/global-namespace`](../../README.md) in real code. Each class in `src/` depends on global functions, receives a `PHP\PHPInterface` (or a `PHP\PHP`) in its constructor, and has a test class in `tests/` with the same sub-namespace.

## Run it

```sh
composer update
composer test          # PHPUnit 9.6 to 13
composer test:strict   # PHP 8.2+: also fail on deprecations, notices and warnings
```

It needs PHP 7.4 or later. `composer.json` installs the library from the repository root (`../..`) through a path repository, so the tests always run against the code next to them. In your own project, run `composer require rogervila/global-namespace` instead.

## Use cases

| Class | Functions | Test | What it shows |
| --- | --- | --- | --- |
| [`App`](src/App.php) | `rand()` | [`AppTest`](tests/AppTest.php) | Mockery mock, hand-written fake (`FakePHP`), real call |
| [`Clock\Greeter`](src/Clock/Greeter.php) | `date()` | [`GreeterTest`](tests/Clock/GreeterTest.php) | Data provider over mocked hours |
| [`Clock\Stopwatch`](src/Clock/Stopwatch.php) | `microtime()` | [`StopwatchTest`](tests/Clock/StopwatchTest.php) | Values returned in order |
| [`Config\Environment`](src/Config/Environment.php) | `getenv()` | [`EnvironmentTest`](tests/Config/EnvironmentTest.php) | `FakePHP` without Mockery, no `putenv()` |
| [`Filesystem\JsonConfigLoader`](src/Filesystem/JsonConfigLoader.php) | `is_file()`, `is_readable()`, `file_get_contents()` | [`JsonConfigLoaderTest`](tests/Filesystem/JsonConfigLoaderTest.php) | No fixture files, unreadable file, `passthru()`, `never()` |
| [`Http\Redirector`](src/Http/Redirector.php) | `headers_sent()`, `header()` | [`RedirectorTest`](tests/Http/RedirectorTest.php) | Exact header assertion, `IgnoreAlways` as a no-op |
| [`Logging\ErrorLogLogger`](src/Logging/ErrorLogLogger.php) | `error_log()` | [`ErrorLogLoggerTest`](tests/Logging/ErrorLogLoggerTest.php) | Message assertion, silencing with `IgnoreAlways` |
| [`Network\HostResolver`](src/Network/HostResolver.php) | `gethostbyname()` | [`HostResolverTest`](tests/Network/HostResolverTest.php) | Offline tests, failure branch |
| [`Security\TokenGenerator`](src/Security/TokenGenerator.php) | `random_bytes()` | [`TokenGeneratorTest`](tests/Security/TokenGeneratorTest.php) | Deterministic tokens, handling `null` results |
| [`Support\Retrier`](src/Support/Retrier.php) | `usleep()` | [`RetrierTest`](tests/Support/RetrierTest.php) | No real waiting, `ordered()`, `Mockery::spy()` |
| [`Support\Compat`](src/Support/Compat.php) | `function_exists()`, `array_find()` (8.4), `array_first()`, `array_last()` (8.5), `clamp()` (8.6) | [`CompatTest`](tests/Support/CompatTest.php) | Both branches of a version check on any PHP version |
| [`Text\Slugger`](src/Text/Slugger.php) | `transliterator_transliterate()` (intl) | [`SluggerTest`](tests/Text/SluggerTest.php) | `IgnoreMissing` fallback for an optional extension |

[`tests/Doubles/FakePHP.php`](tests/Doubles/FakePHP.php) is a hand-written test double for projects that do not use Mockery, and [`tests/TestCase.php`](tests/TestCase.php) shows how to verify Mockery expectations in `tearDown()`.

## Patterns to copy

- Call functions with `$this->php::function()`, never with `->`.
- Only route functions with side effects or changing results through `$this->php`. Call pure functions like `json_decode()` directly.
- Results are `mixed`: check or convert them (`is_string()`, `(int)`) before you use them.
- Production code passes `new PHP()` (or `new IgnoreMissing()` / `new IgnoreAlways()`). Tests pass a mock or a fake.
