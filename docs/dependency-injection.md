---
sidebar_position: 5
title: Dependency Injection
description: Learn how to use dependency injection to manage object creation and dependencies
---

# Dependency Injection

## Basics

Dependency Injection (DI) is a design pattern that provides objects with their dependencies instead of having them create or find dependencies themselves.

In simple terms, rather than a class creating the objects it needs (its dependencies), those objects are "injected" from the outside.

Key benefits:
- **Easier testing**: You can mock dependencies for isolated testing
- **Loose coupling**: Classes aren't tightly linked to specific implementations
- **Improved maintainability**: Dependencies can be changed without modifying the dependent class
- **Better reusability**: Components can be used in different contexts

## Using Dependency Injection

Let's get by example the following classes:

```php
<?php
namespace Example;

interface Area
{
    public function calculate();
}

class Square implements Area
{
    public function __construct($side)
    {
        // ...
    }

    //...
}

class RectangleTriangle implements Area
{
    public function __construct($base, $height)
    {
        // ...
    }

    //...
}
```

We can create a definition for this classes:

```php
<?php

use ByJG\Config\DependencyInjection as DI;

return [
    \Example\Square::class => DI::bind(\Example\Square::class)
        ->withConstructorArgs([4])
        ->toInstance(),

    \Example\RectangleTriangle::class => DI::bind(\Example\RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),
];
```

and to use in our code we just need to do:

```php
<?php
$config = $definition->build();
$square = $config->get(\Example\Square::class);
```

## Injecting automatically the constructor arguments

Let's figure it out this class:

```php
<?php
namespace Example;

class SumAreas implements Area
{
     /**
     * SumAreas constructor.
     * @param Example\RectangleTriangle $triangle 
     * @param Example\Square $square 
     */
    public function __construct($triangle, $square)
    {
        $this->triangle = $triangle;
        $this->square = $square;
    }

    //...
}
```

Note that this class needs instances of objects previously defined in our container definition. In that case we just need add
this:

```php
<?php
use ByJG\Config\DependencyInjection as DI;

return [
    // ....

    \Example\SumAreas::class => DI::bind(\Example\SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
```

When use the method `withInjectedConstructor()` the container will try to inject the constructor automatically based on
its type declaration. Since we previously defined the classes `Square` and `RectangleTriangle` the container will inject the instances
automatically.

:::note
This component uses type hinting and PHP reflection to determine the classes that are required, not PHPDoc. If you're using older PHP versions or code without type declarations, you can use `withInjectedLegacyConstructor()` which uses PHPDoc comments to determine the types.
:::

## Mixing automatic injection with manual parameters

Sometimes you need to inject most dependencies automatically but provide specific values for certain parameters (like configuration strings, API keys, or numeric values). The `withInjectedConstructorOverrides()` method gives you the best of both worlds:

```php
<?php
namespace Example;

class UserService
{
    public function __construct(
        Database $db,        // Will be auto-injected
        Logger $logger,      // Will be auto-injected
        string $apiKey,      // Must be provided manually
        int $timeout = 30    // Optional, uses default if not overridden
    ) {
        // ...
    }
}
```

Configuration:

```php
<?php
use ByJG\Config\DependencyInjection as DI;

return [
    Database::class => DI::bind(Database::class)
        ->withConstructorArgs(['localhost', 'mydb'])
        ->toSingleton(),

    Logger::class => DI::bind(Logger::class)
        ->withConstructorArgs(['/var/log/app.log'])
        ->toSingleton(),

    UserService::class => DI::bind(UserService::class)
        ->withInjectedConstructorOverrides([
            'apiKey' => 'my-secret-api-key',
            'timeout' => 60  // Optional: override the default
        ])
        ->toInstance(),
];
```

**How it works:**
- **Auto-injected**: Class type-hinted parameters (like `Database` and `Logger`) are automatically resolved from the container
- **Manual override**: Built-in types (like `string`, `int`, `bool`) must be provided in the overrides array (unless they have default values)
- **Default values**: Parameters with default values can be omitted from overrides
- **Flexible overrides**: You can also override class dependencies using `Param::get()` if you need a different instance

```php
<?php
// Override a dependency to use a different container entry
UserService::class => DI::bind(UserService::class)
    ->withInjectedConstructorOverrides([
        'apiKey' => 'my-secret-key',
        'logger' => Param::get('FileLogger')  // Use specific logger instead of default
    ])
    ->toInstance(),
```

## Get a singleton object

The `DependencyInjection` class with the parameter `toInstance()` will return a new instance 
every time you require a new object. 

However, you can return always the same object by adding `toSingleton()` instead of `toInstance()`.

## Eager Singleton

Eager Singleton looks like a Singleton, but it creates the instance immediately after the definition.

It is useful when you need a specific object to be created before the application starts.

```php
<?php
use ByJG\Config\DependencyInjection as DI;

return [
    // ....

    \Example\Square::class => DI::bind(\Example\Square::class)
        ->withConstructorArgs([4])
        ->toEagerSingleton(),
];
```

You can also register a class to be loaded as eager singleton regardless of how it was defined in the container:

```php
<?php
use ByJG\Config\Container;

// Register a class to be loaded as eager singleton
Container::addEagerSingleton(\Example\Square::class);
```

### Lazy parameters for eager singletons

Sometimes an eager singleton needs to keep its own constructor or initializer type-hinted (so static analyzers remain happy) but still postpone the creation of heavy collaborators until they are really used.  
`LazyParam::get()` solves this by returning a lightweight proxy that satisfies the original type-hint yet asks the container for the real object only on the first method/property call.

```php
<?php
use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\LazyParam;

return [
    Example\Service::class => DI::bind(Example\Service::class)
        ->withMethodCall('boot', [LazyParam::get(Example\ExpensiveDependency::class)])
        ->toEagerSingleton(),
];
```

Because `LazyParam` still resolves through the container, the dependency is tracked normally, but it avoids the upfront instantiation cost that eager singletons would otherwise incur.

## Delayed Instance

The delayed instance will not return the object immediately. 
Instead, it will return the DependencyInjection object, and then you can get the instance with
customized constructor arguments.

You should prefer to use `toInstance()` or `toSingleton()` instead of `toDelayedInstance()`.

Only use `toDelayedInstance()` when you need to pass custom arguments to the constructor for every
instance you get from the container.

```php
<?php
return [
    // ....

    Square::class => DI::bind(Square::class)
        ->toDelayedInstance(),
 ];
```

And then you can get the instance with custom arguments:

```php
<?php

$square1 = $config->get(Square::class)->getInstance(5);
$square2 = $config->get(Square::class)->getInstance(7);
```

:::warning Delayed Instance Limitations
Delayed Instances **cannot** be used with:
- `withFactoryMethod()`
- `withInjectedConstructor()`
- `withInjectedLegacyConstructor()`
- `withConstructorNoArgs()`

Delayed Instances also **cannot** be:
- Injected automatically to the constructor (classes with `withInjectedConstructor()` or `withInjectedLegacyConstructor()` pointing to a delayed instance will fail)
:::

## All options (bind)

```php
<?php

\ByJG\Config\DependencyInjection::bind("classname")
    // To create a new instance choose *only* one below:
    // --------------------------------------------------
    ->withInjectedConstructor()         // If you want inject the constructor automatically using reflection
    ->withInjectedConstructorOverrides(array)  // Auto-inject dependencies but override specific parameters (e.g., ['apiKey' => 'value'])
    ->withInjectedLegacyConstructor()   // If you want inject the constructor automatically using PHP annotation
    ->withNoConstructor()                // The class has no constructor
    ->withConstructorNoArgs()           // The constructor's class has no arguments
    ->withConstructorArgs(array)        // The constructor's class arguments
    ->withFactoryMethod("method", array_of_args)  // When the class has a static method to instantiate instead of constructor

    // Call methods after you have a instance
    // --------------------------------------
    ->withMethodCall("methodName", array_of_args)

    // How will you get a instance?
    // ----------------------------
    ->toInstance()                   // get a new instance for every time you get from the container
    ->toSingleton()                  // get the same instance for every time you get from the container
    ->toEagerSingleton()             // same as singleton however get a new instance immediately after the definition.
    ->toDelayedInstance()            // get a new instance for every time you get from the container,
                                     // however you can force the constructor parameters
;
```

----
[Open source ByJG](http://opensource.byjg.com)
