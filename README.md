[![Build Status](https://github.com/rogervila/global-namespace/workflows/build/badge.svg)](https://github.com/rogervila/global-namespace/actions)
[![Example Application Status](https://github.com/rogervila/global-namespace/workflows/example-app/badge.svg)](https://github.com/rogervila/global-namespace/tree/main/examples/random-app-example)
[![StyleCI](https://github.styleci.io/repos/332196097/shield?branch=main)](https://github.styleci.io/repos/332196097)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=rogervila_global-namespace&metric=alert_status)](https://sonarcloud.io/dashboard?id=rogervila_global-namespace)

[![Latest Stable Version](https://poser.pugx.org/rogervila/global-namespace/v/stable)](https://packagist.org/packages/rogervila/global-namespace)
[![Total Downloads](https://poser.pugx.org/rogervila/global-namespace/downloads)](https://packagist.org/packages/rogervila/global-namespace)
[![License](https://poser.pugx.org/rogervila/global-namespace/license)](https://packagist.org/packages/rogervila/global-namespace)

<p align="center"><img width="720" src="https://banners.beyondco.de/Global%20Namespace.png?theme=light&packageManager=composer+require&packageName=rogervila%2Fglobal-namespace&pattern=cage&style=style_1&description=Prefix+global+PHP+functions+under+a+namespace&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Fwww.php.net%2Fimages%2Flogos%2Fphp-logo.svg" alt="Global Namespace" /></p>

# Global Namespace

This library provides a namespace for functions that are called globally, like PHP Built-in functions.

This might sound weird, so let me introduce an example to show what it can do.

## Example

Imagine that you have an application that uses `rand(0, 1)` and you want to assert what happens when it returns `0` or `1`

Let's scaffold that class

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

If you create a test to assert the results you will find a stopper: **How can we mockup rand() to force it to return 0 or 1?**. This package provides a solution for this question.


First, require this package on your application.

```sh
composer require rogervila/global-namespace
```

Now, update your application to prefix `rand()` with the global namespace.

**`PHP::rand()` will simply call the built-in PHP function** so your application behaviour will not change.

```php
use PHP\PHP;

class App
{
    public function run()
    {
        $result = PHP::rand(0, 1);
        //...
    }
}
```

This works as expected but is not yet fully testeable because the `PHP` dependency is hardcoded and it cannot be mocked.

Let's add it in the constructor so we can use any `PHP` implementation.

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
        //...
    }
}
```

Nice. Now you can mockup `rand()` with [Mockery](https://github.com/mockery/mockery) and assert the application code:

```php
final class AppTest extends \PHPUnit\Framework\TestCase
{
    public function test_rand_returns_one()
    {
        // Create a mock of the package class
        $php = Mockery::mock(\PHP\PHP::class);

        // Define its return value
        $php->shouldReceive('rand')
            ->once()
            ->andReturn(1);

        // Now rand() always returns 1
        $this->assertEquals(
            1,
            $php::rand()
        );

        // Create the instance with the mocked PHP implementation
        $app = new App($php);

        // Do your application assertions here
        // ...
    }
}
```

> Check this [demo application](https://github.com/rogervila/global-namespace/tree/main/examples/random-app-example) for a more complete example

## About

This library calls any global function, not only those that are built-in.

```php
use PHP\PHP;

// PHP Built-in functions
PHP::http_build_query(['foo' => 'bar']); // returns 'foo=bar'
PHP::json_encode(['foo' => 'bar']); // returns '{"foo": "bar"}'
PHP::json_decode('{"foo": "bar"}'); // return ['foo' => 'bar']
// etc...


// For WordPress projects
PHP::wp_redirect('/') // redirects to home
// etc...

// Literaly, any function, built-in or not, could be listed here
```

## Helper classes

If you do not want to use Mockery, or if you are working on test environment that does not fit with it, you may use some helper classes that come with this package

### IgnoreMissing

It works the same way, but it will ignore a function if it does not exist.

This can be useful if you have a function that you cannot be mocked for some reason.

```php
use PHP\IgnoreMissing as PHP;

PHP::http_build_query(['foo' => 'bar']); // returns 'foo=bar'

PHP::foo(); // returns null instead of triggering a "Call to undefined function" error

// missing functions return null. existing functions are called
```

### IgnoreAlways

Use it if, for some reason, you want to directly skip all function calls.

It does not matter if the function exists or not.

```php
use PHP\IgnoreAlways as PHP;

PHP::http_build_query(['foo' => 'bar']); // returns null

PHP::foo(); // returns null

// all functions return null
```

## Author

Created by [Roger Vilà](https://rogervila.es)

## License

Global Namespace is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
