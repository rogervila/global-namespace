[![Build Status](https://github.com/rogervila/global-namespace/workflows/build/badge.svg)](https://github.com/rogervila/global-namespace/actions)
[![Example Application Status](https://github.com/rogervila/global-namespace/workflows/example-app/badge.svg)](https://github.com/rogervila/global-namespace/tree/main/examples/random-app-example)
[![StyleCI](https://github.styleci.io/repos/332196097/shield?branch=main)](https://github.styleci.io/repos/332196097)

[![Latest Stable Version](https://poser.pugx.org/rogervila/global-namespace/v/stable)](https://packagist.org/packages/rogervila/global-namespace)
[![Total Downloads](https://poser.pugx.org/rogervila/global-namespace/downloads)](https://packagist.org/packages/rogervila/global-namespace)
[![License](https://poser.pugx.org/rogervila/global-namespace/license)](https://packagist.org/packages/rogervila/global-namespace)

<p align="center"><img width="720" src="https://banners.beyondco.de/Global%20Namespace.png?theme=light&packageManager=composer+require&packageName=rogervila%2Fglobal-namespace&pattern=cage&style=style_1&description=Prefix+global+PHP+functions+under+a+namespace&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Fwww.php.net%2Fimages%2Flogos%2Fphp-logo.svg" alt="Global Namespace" /></p>

# Global Namespace

Call any global PHP function through a class, so tests can replace it.

```php
use PHP\PHP;

PHP::rand(0, 1);                // runs rand(0, 1)
PHP::file_get_contents($path);  // runs file_get_contents($path)
```

`PHP::anyFunction(...$arguments)` runs `anyFunction(...$arguments)` and returns its result. Inject a `PHP` instance into your classes and call `$this->php::rand(0, 1)`. In production the real function runs. In tests you replace the instance with a [Mockery](https://github.com/mockery/mockery) mock or with your own test double, so you can force `rand()` to return `0`, freeze `time()`, fake `file_get_contents()` or assert that `header()` was called.

- No runtime dependencies: one interface and three small classes.
- Works with PHP built-in functions, extension functions, your own functions and namespaced functions.
- Supports and is tested on PHP 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5 and 8.6.

## Contents

- [Installation](#installation)
- [The problem](#the-problem)
- [Quick start](#quick-start)
- [How it works](#how-it-works)
- [The classes](#the-classes)
- [Testing with Mockery](#testing-with-mockery)
- [Testing without Mockery](#testing-without-mockery)
- [Use cases](#use-cases)
- [Modern PHP syntax](#modern-php-syntax)
- [Behaviour reference](#behaviour-reference)
- [Limitations](#limitations)
- [Static analysis](#static-analysis)
- [Performance](#performance)
- [PHP version support](#php-version-support)
- [Example application](#example-application)
- [Development](#development)
- [Documentation for AI assistants](#documentation-for-ai-assistants)

## Installation

```sh
composer require rogervila/global-namespace
```

The library needs PHP 7.2 or later and no PHP extensions. In most projects it is a production dependency, because your classes call functions through it. Mockery is optional: you only need it in `require-dev` if you use it in your tests.

## The problem

Imagine that you have an application that uses `rand(0, 1)` and you want to assert what happens when it returns `0` or `1`.

```php
class App
{
    public function run()
    {
        $result = rand(0, 1);

        if ($result == 0) {
            // do something when $result is 0
        } else {
            // do something else when $result is 1
        }
    }
}
```

If you create a test to assert the results you will find a stopper: **how can we mock `rand()` to force it to return 0 or 1?** The same question applies to every global function with a result you do not control: `time()`, `date()`, `random_bytes()`, `getenv()`, `file_get_contents()`, `gethostbyname()`, `usleep()`, `header()`, `mail()` and many more. This package answers it.

## Quick start

**1. Call the function through `PHP`.** `PHP::rand()` simply calls the built-in PHP function, so the behaviour of your application does not change.

```php
use PHP\PHP;

class App
{
    public function run()
    {
        $result = PHP::rand(0, 1);
        // ...
    }
}
```

**2. Inject the `PHP` instance.** A hard-coded `PHP::rand()` call cannot be replaced in a test. Receive the instance in the constructor and call the function on it with `::`:

```php
use PHP\PHP;

class App
{
    private PHP $php;

    public function __construct(PHP $php)
    {
        $this->php = $php;
    }

    public function run()
    {
        $result = $this->php::rand(0, 1);
        // ...
    }
}

// Production code passes the real implementation:
$app = new App(new PHP());
```

**3. Replace it in your tests.** With Mockery:

```php
final class AppTest extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close(); // verifies the expectations
    }

    public function test_rand_returns_one()
    {
        // Create a mock of the package class
        $php = \Mockery::mock(\PHP\PHP::class);

        // Define its return value
        $php->shouldReceive('rand')
            ->once()
            ->with(0, 1)
            ->andReturn(1);

        // Create the instance with the mocked PHP implementation
        $app = new App($php);

        // Now rand() always returns 1 inside App.
        // Do your application assertions here
        // ...
    }
}
```

> Check this [demo application](https://github.com/rogervila/global-namespace/tree/main/examples/random-app-example) for complete, tested examples of many use cases.

## How it works

The whole implementation of `PHP\PHP` is one magic method:

```php
namespace PHP;

class PHP implements PHPInterface
{
    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array($name, $arguments);
    }
}
```

- `PHP::rand(0, 1)` calls a static method that does not exist, so PHP calls `__callStatic('rand', [0, 1])`, which calls `rand(0, 1)`.
- `$this->php::rand(0, 1)` makes the same static call on the class of the object stored in `$this->php`. When that object is a mock or a subclass, its own `__callStatic()` runs instead. That is what makes the function replaceable.
- Named arguments (PHP 8.0+) reach `__callStatic()` as string keys of `$arguments`, and `call_user_func_array()` passes them on as named arguments.

Always use `::`. The classes have no `__call()` method, so `$this->php->rand()` fails with `Error: Call to undefined method PHP\PHP::rand()`. Be careful: Mockery mocks accept instance calls too, so a test can pass while the same code fails in production.

## The classes

| Class | The function exists | The function does not exist | Typical use |
| --- | --- | --- | --- |
| `PHP\PHP` | Calls it and returns its result | PHP 8+: throws `TypeError`. PHP 7: raises a warning and returns `null` | Production code |
| `PHP\IgnoreMissing` | Calls it and returns its result | Returns `null`, without warnings or errors | Optional extensions, functions of newer PHP versions, framework helpers that may not be loaded |
| `PHP\IgnoreAlways` | Never calls it, returns `null` | Returns `null` | Null object: dry runs, silencing side effects, simple tests |
| `PHP\PHPInterface` | Interface implemented by the three classes. It only declares `public static function __callStatic($name, $arguments)` | | Type hints |

**Which type hint should I use?** Type-hint `PHPInterface` to accept any of the three classes, a mock of any of them, or your own implementation. Type-hint `PHP` if only `PHP\PHP`, its subclasses and its mocks should be accepted. `IgnoreMissing` and `IgnoreAlways` do not extend `PHP`.

The three classes are not `final`: Mockery creates mocks by extending them, and you can extend them to write your own test doubles.

### IgnoreMissing

It works the same way as `PHP\PHP`, but it ignores a function that does not exist.

This is useful when a function may not be available: a function from an optional extension (`intl`, `apcu`...), a function added in a newer PHP version, or a helper of a framework that is not loaded (for example, WordPress functions in a unit test).

```php
use PHP\IgnoreMissing as PHP;

PHP::http_build_query(['foo' => 'bar']); // returns 'foo=bar'

PHP::foo(); // returns null, while PHP\PHP::foo() fails (TypeError on PHP 8, warning on PHP 7)

PHP::array_first([1, 2]); // returns 1 on PHP 8.5+, null on older versions

// missing functions return null. existing functions are called
```

Results that are `false` stay `false` (`IgnoreMissing::strpos('abc', 'z')` returns `false`). Only missing functions return `null`. Errors and exceptions thrown by an existing function are not caught.

### IgnoreAlways

Use it if you want to skip all function calls. It does not matter if the function exists or not.

```php
use PHP\IgnoreAlways as PHP;

PHP::http_build_query(['foo' => 'bar']); // returns null

PHP::foo(); // returns null

PHP::exit(1); // returns null: the script keeps running

// all functions return null
```

Nothing runs, so nothing can fail: no exceptions, no warnings, no side effects. Inject it to turn code with side effects into a no-op, like a logger that calls `error_log()` or a redirect that calls `header()`. Remember that every function returns `null`, so the calling code must handle `null` results.

## Testing with Mockery

Mock `PHP\PHP`, `PHP\IgnoreMissing`, `PHP\IgnoreAlways` or `PHP\PHPInterface` with `Mockery::mock()`, then declare the functions you expect with `shouldReceive()`. Call `Mockery::close()` in `tearDown()` (or use Mockery's PHPUnit integration trait) so the expectations are verified.

Everything below is covered by [`tests/MockeryIntegrationTest.php`](tests/MockeryIntegrationTest.php):

| Goal | Code |
| --- | --- |
| Return a value | `$php->shouldReceive('rand')->andReturn(1);` |
| Match the arguments | `$php->shouldReceive('date')->with('G')->andReturn('9');` |
| Return values in order (the last one repeats) | `$php->shouldReceive('microtime')->andReturn(1.0, 1.5);` |
| Compute the value | `$php->shouldReceive('file_exists')->andReturnUsing(fn ($path) => $path === '/etc/app.ini');` |
| Throw | `$php->shouldReceive('file_get_contents')->andThrow(new RuntimeException());` |
| Check how many calls happen | `->once()`, `->twice()`, `->times(3)`, `->never()` |
| Check the call order | `->ordered()` |
| Run the real function for one expectation | `$php->shouldReceive('is_file')->passthru();` |
| Return `null` for unexpected calls | `Mockery::mock(PHP::class)->shouldIgnoreMissing();` |
| Accept every call, assert later | `$php = Mockery::spy(PHP::class);` ... `$php->shouldHaveReceived('usleep')->with(100);` |

Things to know:

- **Unexpected calls throw.** Calling a function that has no expectation throws `Mockery\Exception\BadMethodCallException` with a message like `Static method Mockery_0_PHP_PHP::strtoupper() does not exist on this mock object`. Arguments that match no `with()` throw `Mockery\Exception\NoMatchingExpectationException`.
- **`makePartial()` does not forward static calls.** Every function call through `PHP` is a static call, so a partial mock still throws for functions without expectations. Declare the functions that must run for real with `->passthru()`.
- **Use one mock per class in a test.** Mocks with the same configuration share one generated class, and a static call on that class reaches the first mock created in the test. Put all the expectations on a single mock.
- **Only calls made through the injected instance are mocked.** A hard-coded `PHP::rand()` call still runs the real `rand()`.
- **`exit()` can be mocked.** Since PHP 8.4, `exit()` and `die()` are functions, so `$this->php::exit(1)` works in production and can be mocked in tests. On older versions they are language constructs and `PHP::exit()` fails like any missing function.

## Testing without Mockery

You do not need a mocking library. Extend `PHP\PHP` (or implement `PHPInterface`) and override `__callStatic()`:

```php
use PHP\PHP;

final class FakePHP extends PHP
{
    /** @var array<string, mixed> */
    public static $returns = [];

    /** @var list<array{0: string, 1: array}> */
    public static $calls = [];

    public static function __callStatic($name, $arguments)
    {
        self::$calls[] = [$name, $arguments];

        if (array_key_exists($name, self::$returns)) {
            return self::$returns[$name];
        }

        return parent::__callStatic($name, $arguments); // run the real function
    }
}

FakePHP::$returns = ['rand' => 1];

$app = new App(new FakePHP()); // App type-hints PHP\PHP: FakePHP is a PHP\PHP
```

Static properties keep their values between tests, so reset them in `setUp()`. The example application has a complete version in [`tests/Doubles/FakePHP.php`](examples/random-app-example/tests/Doubles/FakePHP.php). For one-off doubles, an anonymous class works too:

```php
$php = new class implements \PHP\PHPInterface {
    public static function __callStatic($name, $arguments)
    {
        return $name === 'time' ? 1700000000 : null;
    }
};
```

## Use cases

The [example application](examples/random-app-example) has one small class and one test class for each of these cases. Every class receives a `PHPInterface` (`App` receives a `PHP`).

| Case | Functions | Class | What the tests show |
| --- | --- | --- | --- |
| Randomness | `rand()` | [`App`](examples/random-app-example/src/App.php) | Mockery mock, hand-written fake, real call |
| Time of day | `date()` | [`Clock\Greeter`](examples/random-app-example/src/Clock/Greeter.php) | Data provider over mocked hours |
| Elapsed time | `microtime()` | [`Clock\Stopwatch`](examples/random-app-example/src/Clock/Stopwatch.php) | Values returned in order |
| Environment variables | `getenv()` | [`Config\Environment`](examples/random-app-example/src/Config/Environment.php) | No `putenv()` side effects, fake without Mockery |
| Files | `is_file()`, `is_readable()`, `file_get_contents()` | [`Filesystem\JsonConfigLoader`](examples/random-app-example/src/Filesystem/JsonConfigLoader.php) | No fixture files, unreadable file simulation, `passthru()` |
| HTTP headers | `headers_sent()`, `header()` | [`Http\Redirector`](examples/random-app-example/src/Http/Redirector.php) | Exact header assertion, `IgnoreAlways` as a no-op |
| Error log | `error_log()` | [`Logging\ErrorLogLogger`](examples/random-app-example/src/Logging/ErrorLogLogger.php) | Message assertion, silencing with `IgnoreAlways` |
| DNS | `gethostbyname()` | [`Network\HostResolver`](examples/random-app-example/src/Network/HostResolver.php) | Offline tests, failure branch |
| Secure random bytes | `random_bytes()` | [`Security\TokenGenerator`](examples/random-app-example/src/Security/TokenGenerator.php) | Deterministic tokens, handling `null` results |
| Sleeping | `usleep()` | [`Support\Retrier`](examples/random-app-example/src/Support/Retrier.php) | No real waiting, `ordered()`, spies |
| Newer PHP functions | `array_find()`, `array_first()`, `array_last()`, `clamp()` | [`Support\Compat`](examples/random-app-example/src/Support/Compat.php) | Both branches of `function_exists()` on any PHP version |
| Optional extension | `transliterator_transliterate()` (intl) | [`Text\Slugger`](examples/random-app-example/src/Text/Slugger.php) | `IgnoreMissing` fallback |

A rule of thumb from these examples: route only the functions that touch the outside world (clock, randomness, filesystem, network, environment, output, sleeping) through `$this->php`. Call functions without side effects, like `json_decode()` or `strtolower()`, directly.

## Modern PHP syntax

Every example in this section is covered by the test suite on the PHP versions that support it.

```php
// Named arguments (PHP 8.0+)
PHP::str_pad(string: '7', length: 3, pad_string: '0', pad_type: STR_PAD_LEFT); // '007'
PHP::json_encode(['path' => '/home'], flags: JSON_UNESCAPED_SLASHES);         // '{"path":"/home"}'
PHP::strlen(text: 'abc'); // Error: Unknown named parameter $text

// First-class callable syntax (PHP 8.1+)
$upper = PHP::strtoupper(...);
$upper('abc'); // 'ABC'

// Pipe operator (PHP 8.5+)
'  hello  ' |> PHP::trim(...) |> PHP::strtoupper(...); // 'HELLO'

// exit() and die() are functions since PHP 8.4
$this->php::exit(1); // stops the script with PHP\PHP, can be mocked in tests
```

Functions added in recent versions work like any other function: `array_find()`, `array_find_key()`, `array_any()`, `array_all()` (8.4), `array_first()`, `array_last()` (8.5) and `clamp()` (8.6). With `IgnoreMissing` they return `null` on older versions. To provide a fallback, check `$this->php::function_exists('clamp')` through the proxy: tests can then fake both branches on any PHP version, as [`Support\Compat`](examples/random-app-example/src/Support/Compat.php) does.

## Behaviour reference

These statements are verified by the tests in [`tests/`](tests) on every supported PHP version.

**Arguments and results**

- The result of the function is returned without changes: `false` stays `false`, and functions without a return value return `null`.
- All arguments are passed on, including variadic arguments (`PHP::max(4, 9, 1)`), argument unpacking (`PHP::max(...$numbers)`), callbacks (`PHP::array_map($callback, $items)`) and named arguments.

**Function names**

- Names are case-insensitive, like in PHP: `PHP::STRTOUPPER('abc')` works.
- Any global function works, not only built-in ones: `PHP::wp_redirect('/')` calls the WordPress function when WordPress is loaded.
- Namespaced functions need a dynamic name with the fully qualified name: `$function = 'App\Support\format_price'; PHP::$function(10)`. A leading backslash is allowed (`'\strrev'`).
- Dynamic names also work as expressions: `PHP::{'str' . 'rev'}('abc')`.

**Callables**

- `[PHP::class, 'strtoupper']`, `'PHP\PHP::strtoupper'` and `Closure::fromCallable([PHP::class, 'strtoupper'])` are valid callables: `array_map([PHP::class, 'strtoupper'], ['a', 'b'])` returns `['A', 'B']`.
- `is_callable([PHP::class, $name])` is always `true`, because `__callStatic()` accepts any name. Use `function_exists($name)` to check whether a function exists.

**Errors**

- Exceptions and errors thrown by the function reach the caller unchanged: `PHP::intdiv(1, 0)` throws `DivisionByZeroError`. `IgnoreMissing` does not catch them either.
- A missing function, with `PHP\PHP`:
  - PHP 8.0+: `TypeError: call_user_func_array(): Argument #1 ($callback) must be a valid callback, function "foo" not found or invalid function name`
  - PHP 7: `Warning: call_user_func_array() expects parameter 1 to be a valid callback, function 'foo' not found or invalid function name`, and the call returns `null`.

## Limitations

- **By-reference parameters do not work.** `__callStatic()` receives copies of the arguments, so functions like `sort()`, `shuffle()`, `array_push()`, `preg_match()` (`$matches`) or `exec()` (`$output`) change a copy. PHP raises a warning (`sort(): Argument #1 ($array) must be passed by reference, value given` on PHP 8, `Parameter 1 to sort() expected to be a reference, value given` on PHP 7) and your variable stays unchanged. Call these functions directly, or write a small function that returns the result (for example `function sorted(array $items): array { sort($items); return $items; }`) and call that one through `PHP`.
- **Language constructs are not functions.** `isset`, `empty`, `unset`, `echo`, `print`, `eval`, `include`, `require` and `list` cannot be called through the classes. `exit` and `die` are functions only since PHP 8.4.
- **Scope functions cannot be called dynamically.** `compact()`, `extract()`, `func_get_args()`, `func_get_arg()`, `func_num_args()` and `get_defined_vars()` read or change the caller's variables. PHP 8 throws `Error: Cannot call compact() dynamically` (PHP 7 raises a warning with the same text).
- **Static calls only.** Use `$this->php::function()`, never `$this->php->function()` (see [How it works](#how-it-works)).
- **Never build function names from user input.** `PHP::$name()` can call any function, including `exec()`, `system()`, `unlink()` and `mail()`. If the name must be dynamic, check it against a fixed allowlist first.

## Static analysis

Static analysers cannot know which functions you will call through `__callStatic()`. PHPStan 2 reports `PHP::rand()` as `staticMethod.notFound` (`Call to an undefined static method PHP\PHP::rand()`) from level 1, and `$this->php::rand()` from level 2. From level 7, it also reports callables like `[PHP::class, 'strtoupper']` as `argument.type`. Ignore those errors for the library classes in your `phpstan.neon`:

```neon
parameters:
    ignoreErrors:
        -
            identifier: staticMethod.notFound
            message: '#^Call to an undefined static method PHP\\(PHP|IgnoreMissing|IgnoreAlways|PHPInterface)::#'
        -
            identifier: argument.type
            message: '#^Parameter \#1 \$callback of .+, (array\{)?''PHP\\{1,2}(PHP|IgnoreMissing|IgnoreAlways)(''|::)#'
            # Projects that never use these callables would get an
            # "Ignored error pattern was not matched" error without this line.
            reportUnmatched: false
```

If you call functions on mocks or on your own subclasses in tests, add their class names to the first pattern (this repository adds `Mockery\\.+` and `Tests\\.+`; see [`phpstan.neon.dist`](phpstan.neon.dist)).

Every result is `mixed` for the analyser. Check or convert results before you use them, as the example application does: `(int) $this->php::date('G')`, `is_string($contents)`.

## Performance

Each call goes through `__callStatic()` and `call_user_func_array()`. On PHP 8.4 and 8.5, with and without OPcache, a call through `PHP\PHP` took about 0.2 µs (185 to 200 ns per call; 220 to 240 ns with `IgnoreMissing`), against about 5 ns for a direct `strlen()` call (1,000,000 calls, median of 5 runs, measured once on one machine: your numbers will differ). This does not matter for functions that do I/O, but call pure functions in hot loops directly.

## PHP version support

The library code has no version-specific code, so it runs on PHP 7.2 and every later version. CI runs the tests on Linux, Windows and macOS with:

| PHP | PHPUnit | Mockery |
| --- | --- | --- |
| 7.2 | 8.5 | 1.3 |
| 7.3, 7.4, 8.0, 8.1 | 9.6 | 1.6 |
| 8.2, 8.3 | 11.5 | 1.6 |
| 8.4, 8.5, 8.6 | 13 | 1.6 |

PHP 8.6 was tested with its release candidate (8.6.0RC7). On PHP 8.2 and later, CI runs `composer test:strict`, which fails on any deprecation, notice or warning. Tests for syntax that older versions cannot parse (named arguments, first-class callables, the pipe operator) are skipped on those versions.

The [example application](examples/random-app-example) needs PHP 7.4 or later and is tested on PHP 7.4 to 8.6 (PHPUnit 9.6, 10.5, 11.5 and 13).

## Example application

[`examples/random-app-example`](https://github.com/rogervila/global-namespace/tree/main/examples/random-app-example) is a standalone Composer project that installs this library from the repository root. It shows every pattern from this README with real, tested code: see [Use cases](#use-cases) and its own [README](examples/random-app-example/README.md).

```sh
cd examples/random-app-example
composer update
composer test
```

## Development

```sh
composer update          # install the dependencies for your PHP version
composer test            # run the tests (PHPUnit 8.5 to 13)
composer test:strict     # fail on deprecations, notices and warnings (PHP 8.2+)
composer analyse         # PHPStan at level max (PHPStan 2, PHP 7.4+)
composer test:coverage   # clover.xml and junit-logfile.xml (PHPUnit 9+, Xdebug or PCOV)
```

Code style follows PSR-2 (checked by StyleCI). The tests must keep running on every PHP version from 7.2: read [`AGENTS.md`](AGENTS.md) for the compatibility rules before you change them.

## Documentation for AI assistants

- [`llms.txt`](llms.txt): a short summary of the library for language models.
- [`AGENTS.md`](AGENTS.md): instructions for AI coding agents (and humans) working on this repository.
- [`CLAUDE.md`](CLAUDE.md): Claude Code entry point, which imports `AGENTS.md`.

## Author

Created by [Roger Vilà](https://rogervila.es)

## License

Global Namespace is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
