---
sidebar_position: 6
---

# Good Practices

## Create a singleton for your Definition class

This avoids the container being created more than once and allows you to use the container in any part of your code.

```php
<?php
namespace YourNamespace;

use ByJG\Config\Container;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

class Psr11
{
    private static ?Definition $definition = null;
    private static ?Container $container = null;

    public static function get(string $id, mixed ...$parameters): mixed
    {
        return Psr11::container()->get($id, ...$parameters);
    }

    public static function container(string $env = null): Container
    {
        if (is_null(self::$container)) {
            self::$container = self::environment()->build($env);
        }

        return self::$container;
    }

    public static function environment(): Definition
    {
        if (is_null(self::$definition)) {
            $devConfig = new Environment('dev');
            $prodConfig = new Environment('prod', ['dev']);
            
            self::$definition = (new Definition())
                ->addEnvironment($devConfig)
                ->addEnvironment($prodConfig);
        }

        return self::$definition;
    }
}
```

Usage:

```php
<?php
use YourNamespace\Psr11;

// Get a value from the container
$value = Psr11::get('property1');

// Use the container for dependency injection
$square = Psr11::get(\Example\Square::class);
```

----
[Open source ByJG](http://opensource.byjg.com)
