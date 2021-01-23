[![StyleCI](https://github.styleci.io/repos/332196097/shield?branch=master)](https://github.styleci.io/repos/332196097)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=rogervila_global-namespace&metric=alert_status)](https://sonarcloud.io/dashboard?id=rogervila_global-namespace)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=rogervila_global-namespace&metric=coverage)](https://sonarcloud.io/dashboard?id=rogervila_global-namespace)

[![Latest Stable Version](https://poser.pugx.org/rogervila/global-namespace/v/stable)](https://packagist.org/packages/rogervila/global-namespace)
[![Total Downloads](https://poser.pugx.org/rogervila/global-namespace/downloads)](https://packagist.org/packages/rogervila/global-namespace)
[![License](https://poser.pugx.org/rogervila/global-namespace/license)](https://packagist.org/packages/rogervila/global-namespace)

<p align="center"><img width="720" src="https://banners.beyondco.de/Global%20Namespace.png?theme=light&packageManager=composer+require&packageName=rogervila%2Fglobal-namespace&pattern=cage&style=style_1&description=Prefix+global+PHP+functions+under+a+namespace&md=1&showWatermark=1&fontSize=100px&images=https%3A%2F%2Fwww.php.net%2Fimages%2Flogos%2Fphp-logo.svg" alt="Global Namespace" /></p>

# Global Namespace

This library provides a namespace for functions that dare called globally, like PHP Built-in functions.

This might sound weird, so let me introduce an example to try to show what it can do.

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

Now, you would start to create the test to assert the results, but you will find an issue: **How can we mockup rand() to force it to return 0 or 1?**

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

Nice. Now you can mockup `rand()` with [Mockery](https://github.com/mockery/mockery) and assert your code:

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

        // Do your application assertions here
        // ...
    }
}
```

This library calls any global PHP function, not only those that are built-in.

```php
// PHP Built-in functions
PHP::http_build_query(['foo' => 'bar']); // returns 'foo=bar'
PHP::json_encode(['foo' => 'bar']); // returns '{"foo": "bar"}'
PHP::json_decode('{"foo": "bar"}'); // return ['foo' => 'bar']
// etc...


// For WordPress projects
PHP::wp_redirect('/') // redirects to home

// Literaly, any global function, built-in or not, could be listed here
```

## Author

Created by [Roger Vilà](https://rogervila.es)

## License

Global Namespace is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
