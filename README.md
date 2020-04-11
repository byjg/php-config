# Config: Container PSR-11

[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg.com-brightgreen.svg)](http://opensource.byjg.com)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/config/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/config/?branch=master)
[![Build Status](https://travis-ci.org/byjg/config.svg?branch=master)](https://travis-ci.org/byjg/config)

A very basic and minimalist PSR-11 implementation for config management and dependency injection.

# How it Works?

The container is created based on your current environment (dev, homolog, test, live, ...) defined in array files;

See below how to setup:

# Setup files:

Create in your project root at the same level of the vendor directory a folder called `config`. 

Inside this folders create files called "config-dev.php", "config-test.php" where dev, test, live, etc
are your environments. 

Your folder will look like to:

```
<project root>
    |
    +-- config
           |
           + config-dev.php
           + config-homolog.php
           + config-test.php
           + config-live.php
   +-- vendor
   +-- composer.json
```

# Create environment variable

You need to setup a variable called "APPLICATION_ENV" before start your server. 

This can be done using nginx:

```
fastcgi_param   APPLICATION_ENV  dev;
```

Apache:

```
SetEnv APPLICATION_ENV dev
```

Docker-Compose

```
environment:
    APPLICATION_ENV: dev
```

Docker CLI

```
docker -e APPLICATION_ENV=dev image
```

# The `config-xxxx.php` file

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

# Use in your PHP Code

Create the Definition:

```php
<?php
$definition = (new \ByJG\Config\Definition())
    ->environmentVar('APPLICATION_ENV') // This will setup the environment var to 'APPLICATION_ENV' (default)
    ->addEnvironment('homolog')         // This will setup the HOMOLOG environment
    ->addEnvironment('live')            // This will setup the LIVE environenment inherited HOMOLOG
        ->inheritFrom('homolog')
    ->setCache($somePsr16Implementation, 'live'); // This will cache the result only to live Environment;
```

The code below will get a property from the defined environment:

```php
<?php
$container = $definition->build();
$property = $container->get('property2');
```

If the property does not exists an error will be throwed.


If the property is a closure, you can call the get method and you'll get the closure execution result:

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

# Dependency Injection

## Basics

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

## Injecting automaticatically the Objects

Let's figure it out this class:

```php
<?php
class SumAreas implements Area
{
     /**
     * SumAreas constructor.
     * @param $triangle \DIClasses\RectangleTriangle
     * @param $square \DIClasses\Square
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
        ->withConstructor()
        ->toInstance(),
];
``` 

When use use the method `withConstructor()` we are expecting that all required classes in the constructor already where 
defined and inject automatically to get a instance.  

This component uses the PHP Document to determine the classed are required. 

## Get a singleton object

The `DependencyInjection` class will return a new instance every time you require a new object. However, you can the same object
by adding `toSingleton()` instead of `toInstance()`. 

## All options:

```php
<?php

\ByJG\Config\DependencyInjection::bind("classname")
    // Use one of these below:
    ->withInjectedConstructor()      // If you want attach  inject automatically
    ->withNoConstrutor()             // There is no constructor
    ->withConstructorArgs(array)     // The constructor arguments

    // Call methods
    ->withMethodCall("methodName", array_of_args)
    
    // Use one of these below:
    ->toInstance()                   // get a new instance for every container get
    ->toSingleton()                  // get the same instance for every container get 
```

# Checking current environment

```php
<?php
$defintion->getCurrentEnv();
```

# Install

```
composer require "byjg/config=4.0.*"
```

# Tests

```
phpunit
```

