# Good Practices

## Create a singleton for your Definition class

This avoids the container to be created more than once and allow you to use the container in any part of your code.

```php
<?php

class Psr11
{
    private static $definition = null;
    private static $container = null;

    public static function container(string $env = null): \ByJG\Config\Container
    {
        if (is_null(self::$container)) {
            self::$container = self::environment()->build($env);
        }

        return self::$container;
    }

    public static function environment(): \ByJG\Config\Definition
    {
        if (is_null(self::$definition)) {
            self::$definition = (new Definition())
                ->addConfig('dev')
            );
        }

        return self::$definition;
    }
}
```

Usage:

```php
<?php
$value = Psr11::container()->get('property1');
```
