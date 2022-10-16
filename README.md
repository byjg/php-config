# Config: Container PSR-11 and Dependency Injection

[![Build Status](https://github.com/byjg/config/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/config/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/config/)
[![GitHub license](https://img.shields.io/github/license/byjg/config.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/config.svg)](https://github.com/byjg/config/releases/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/config/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/config/?branch=master)

A very basic and minimalist PSR-11 implementation for config management and dependency injection.

## How it Works?

The container is created based on the configuration you created (dev, homolog, test, live, ...) defined in array and `.env` files;

See below how to setup:

## Setup files

Create in your project root at the same level of the vendor directory a folder called `config`. 

Inside these folders create files called "config-dev.php", "config-test.php" where dev, test, live, etc
are your configuration sets. 

Your folder will look like to:

```text
<project root>
    |
    +-- config
           |
           + config-dev.php
           + config-dev.env
           + config-homolog.php
           + config-homolog.env
           + config-test.php
           + config-live.php
   +-- vendor
   +-- composer.json
```

## Select the configuration you will use

### Read from the environment variable `APP_ENV`

When you call:

```php
$container = $defintion->build()
```

The component will try to get the proper configuration set based on the contents of the variable `APP_ENV`

There are several ways to set the `APP_ENV` before start your server:

This can be done using nginx:

```text
fastcgi_param   APP_ENV  dev;
```

Apache:

```text
SetEnv APP_ENV dev
```

Docker-Compose

```text
environment:
    APP_ENV: dev
```

Docker CLI

```
docker -e APP_ENV=dev image
```

### Read from a different variable

Instead of use `APP_ENV` you can set your own variable

```php
$container = $definition
    ->withConfigVar("MY_ENV_VAR")
    ->build("live");
```

### Specify directly 

Other way to load the configuration set instead of depending on an environment variable is to specifiy directly
which configuration you want to get:

```php
$container = $definition->build("live");
```

### Configuration Files

#### The `config-xxxx.php` file

**config-homolog.php**
```php
<?php

return [
    'property1' => 'string',
    'property2' => true,
    'property3' => function () {
        return 'xxxxxx';
    },
    'propertyWithArgs' => function ($p1, $p2) {
        return 'xxxxxx';
    },
];
```

**config-live.php**
```php
<?php

return [
    'property2' => false
];
```

#### The `config-xxxx.env` file

Alternatively is possible to set an .env file with the contents KEY=VALUE one per line. 

**live.env**
```
property1=mixed
```

By default, all properties are parsed as string. You can parse as bool, int or float as this example:

```
PARAM1=!bool true
PARAM2=!int 20
PARAM3=!float 3.14
```

### Use in your PHP Code

Create the Definition:

```php
<?php
$definition = (new \ByJG\Config\Definition())
    ->withConfigVar('APP_ENV') // This will setup the environment var to 'APP_ENV' (default)
    ->addConfig('homolog')         // This will setup the HOMOLOG configuration set
    ->addConfig('live')            // This will setup the LIVE environenment inherited HOMOLOG
        ->inheritFrom('homolog')
    ->setCache($somePsr16Implementation, 'live'); // This will cache the "live" configuration set. 
```

The code below will get a property from the defined environment:

```php
<?php
$container = $definition->build();
$property = $container->get('property2');
```

If the property does not exist an error will be throwed.


If the property is a closure, you can call the get method, and you'll get the closure execution result:

```php
<?php
$container = $definition->build();
$property = $container->get('closureProperty');
$property = $container->get('closurePropertyWithArgs', 'value1', 'value2');
$property = $container->get('closurePropertyWithArgs', ['value1', 'value2']);
```

If you want get the RAW value without parse clousure:

```php
<?php
$container = $definition->build();
$property = $container->raw('closureProperty');
```

## Dependency Injection

### Basics

It is possible to create a Dependency Injection and set automatically the instances and constructors. 
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

### Injecting automaticatically the Objects

Let's figure it out this class:

```php
<?php
class SumAreas implements Area
{
     /**
     * SumAreas constructor.
     * @param \DIClasses\RectangleTriangle $triangle 
     * @param \DIClasses\Square $square 
     */
    public function __construct($triangle, $square)
    {
        $this->triangle = $triangle;
        $this->square = $square;
    }

    //... 
```

Note that this class needs instances of objects previously defined in our container definition. In that case we just need add
this:

```php
<?php
return [
    // ....

    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
``` 

When use use the method `withConstructor()` we are expecting that all required classes in the constructor already where 
defined and inject automatically to get a instance.  

This component uses the PHP Document to determine the classed are required. 

### Get a singleton object

The `DependencyInjection` class will return a new instance every time you require a new object. However, you can the same object
by adding `toSingleton()` instead of `toInstance()`. 

### All options

```php
<?php

\ByJG\Config\DependencyInjection::bind("classname")
    // To create a new instance choose *only* one below:
    ->withInjectedConstructor()         // If you want inject the constructor automatically using reflection
    ->withInjectedLegacyConstructor()   // If you want inject the constructor automatically using PHP annotation
    ->withNoConstrutor()                // The class has no constructor
    ->withConstructorArgs(array)        // The constructor's class arguments
    ->withFactoryMethod("method", array_of_args)  // When the class has a static method to instantiate instead of constructure 

    // Call methods after you have a instance
    ->withMethodCall("methodName", array_of_args)
    
    // How will you get a instance?
    ->toInstance()                   // get a new instance for every container get
    ->toSingleton()                  // get the same instance for every container get 
    ->toEagerSingleton()             // same as singleton however get a new instance immediately  
;
```

## Get the configuration set name is active

```php
<?php
$defintion->getCurrentConfig();
```

## Install

```bash
composer require "byjg/config=4.1.*"
```

## Tests

```
phpunit
```
