---
sidebar_position: 7
title: Special Types
description: Learn about special type parsers and using Param::get() for container references
---

# Special Types

## Returning parsed value from Static Files

By default, any value in the static files (*.env) are parsed as string. 

You can parse to a specific types using this syntax:

```text
PARAM1=!parser VALUE
```

Where `!parser` is one of the pre-defined parsers:

| Parser      | Description                             | Example                       |
|-------------|-----------------------------------------|-------------------------------|
| !bool       | Parse to boolean                        | `PARAM=!bool true`            |
| !int        | Parse to integer                        | `PARAM=!int 10`               |
| !float      | Parse to float                          | `PARAM=!float 3.5`            |
| !jsondecode | Parse to JSON and transform to an array | `PARAM=!jsondecode {"a":"b"}` |
| !array      | Parse to array                          | `PARAM=!array 1,2,3,4`        |
| !dict       | Parse to dictionary (associative array) | `PARAM=!dict a=1,b=2`         |
| !unesc      | Unescape the value                      | `PARAM=!unesc a\nb`           |
| !file       | Load the content of a file              | `PARAM=!file /path/to/file`   |

## Adding a new Parser

You can add a new special type:

```php
<?php
use ByJG\Config\ParamParser;

if (!ParamParser::isParserExists('mytype')) {
    ParamParser::addParser('mytype', function ($value) {
        return 'mytype:' . $value;
    });
}
```

Then you can use:

```text
PARAM1=!mytype 123
```

## Using Param::get() to Postpone Container Calls

:::danger Important
When we need to get dependencies from the container, we must use `Param::get()` instead of `Config::get()`. This is because `Param::get()` postpones the call to the container until the dependency is actually needed, preventing infinite loops when dependencies reference each other.
:::

This will cause an error:

```php
<?php
use ByJG\Config\Param;
use ByJG\Config\DependencyInjection as DI;
use Example\Square;

return [
    "side" => 4,
    "Calculator" =>DI::bind(Area::class)
        ->toInstance(),
    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([
               Config::get('side'),
               Config::get('Calculator')])
        ->toInstance(),
];
```

and this is the fix:

```php
<?php
use ByJG\Config\Param;
use ByJG\Config\DependencyInjection as DI;
use Example\Square;

return [
    "side" => 4,
    "Calculator" =>DI::bind(Area::class)
        ->toInstance(), 
    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([
               Param::get('side'),
               Param::get('Calculator')])
        ->toInstance(),
];
```

The same rule applies to the container itself. If a service needs to resolve things on
its own, use `Param::container()` rather than `Config::getContainer()` — the facade is
not populated yet while the configuration is being built:

```php
<?php
use ByJG\Config\Param;
use ByJG\Config\DependencyInjection as DI;

return [
    Example\Server::class => DI::bind(Example\Server::class)
        ->withMethodCall('withContainer', [Param::container()])
        ->toSingleton(),
];
```

See [Dependency Injection](dependency-injection.md#injecting-the-container-itself) for details.

----
[Open source ByJG](http://opensource.byjg.com)
