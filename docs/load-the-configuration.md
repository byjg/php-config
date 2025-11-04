---
sidebar_position: 2
title: Loading the Configuration
description: Learn how to load and use configuration values in your application
---

# Loading the Configuration

After [setting up the configuration files](setup.md), you need to load them into the container.

## Create the environment definition

The definition will specify how the configuration will be loaded, what environments will be available, and the inheritance between them.

```php
<?php
use ByJG\Config\Definition;
use ByJG\Config\Environment;
use ByJG\Config\CacheModeEnum;
use ByJG\Cache\Psr16\ArrayCacheEngine;

// Create environments using fluent API (recommended)
$dev = Environment::create('dev');
$prod = Environment::create('prod')
    ->inheritFrom($dev)
    ->withCache(new ArrayCacheEngine(), CacheModeEnum::singleFile);

// Create the definition with environments
$definition = (new Definition())
    ->withConfigVar('APP_ENV')    // Set up the environment var used to auto select the config. 'APP_ENV' is default.
    ->addEnvironment($dev)        // Defining the 'dev' environment
    ->addEnvironment($prod)       // Defining the `prod` environment that inherits from `dev`
;
```

### Fluent API Methods

The `Environment` class provides a fluent API for easier configuration:

```php
<?php
use ByJG\Config\Environment;
use ByJG\Config\CacheModeEnum;
use ByJG\Cache\Psr16\FileSystemCacheEngine;

$base = Environment::create('base')
    ->setAsAbstract();  // Mark as abstract (cannot be loaded, only inherited)

$dev = Environment::create('dev')
    ->inheritFrom($base);  // Inherit from base environment

$prod = Environment::create('prod')
    ->inheritFrom($dev)
    ->withCache(new FileSystemCacheEngine('/tmp/cache'), CacheModeEnum::singleFile)
    ->setAsFinal();  // Mark as final (cannot be inherited from)
```

### Traditional Constructor (Still Supported)

```php
<?php
use ByJG\Config\CacheModeEnum;
use ByJG\Config\Environment;
use Psr\SimpleCache\CacheInterface;

new Environment(
    string $environment,             // The environment name
    array $inheritFrom = [],         // The list of environments to inherit from
    CacheInterface $cache = null,    // The PSR-16 implementation to cache the configuration
    bool $abstract = false,          // If true, the environment will not be used to load the configuration
    bool $final = false,             // If true, the environment cannot be used to inherit from
    CacheModeEnum $cacheMode = CacheModeEnum::multipleFiles  // How the cache will be stored
);
```

## Build the definition

```php
<?php
// This will check the config var 'APP_ENV' and load the configuration
// from the appropriate environment files, creating the container instance
$container = $definition->build();
```

:::caution
This method requires the environment var `APP_ENV` to be set, otherwise it will throw an exception.
:::

The build process will verify that at least one of the following files exists for the specified environment:
- `config-<APP_ENV>.php`
- `config-<APP_ENV>.env`
- `<APP_ENV>/*.php`
- `<APP_ENV>/*.env`

## Get the Values from the container

After building the definition, you can get the values from the container:

```php
<?php
// Get a simple value
$property = $container->get('property1');

// Get a value from dependency injection
$instance = $container->get(\Example\Square::class);
```

If a value is a closure, it will return the closure execution result:

```php
<?php
// Get the closure execution result:
$property = $container->get('property3');

// Get the closure execution result with the arguments 1 and 2:
$property = $container->get('propertyWithArgs', 1, 2);
```

Also, it is possible to get the **raw** value without any parsing:

```php
<?php
// Get the raw value (returns the closure itself, not its execution result)
$closure = $container->raw('property3');
```

## Get the environment name currently in use

```php
<?php
// Get the current environment name (e.g., 'dev', 'prod', etc.)
$currentEnv = $definition->getCurrentEnvironment();
```

## Check key status in container

You can check the status of a key in the container:

```php
<?php
use ByJG\Config\KeyStatusEnum;

// Check if a key exists and its status
$status = $container->keyStatus('myKey');

if ($status === KeyStatusEnum::NOT_FOUND) {
    echo "Key not found";
} elseif ($status === KeyStatusEnum::STATIC) {
    echo "Static value";
} elseif ($status === KeyStatusEnum::IN_MEMORY) {
    echo "Instance is loaded and in memory";
} elseif ($status === KeyStatusEnum::WAS_USED) {
    echo "Instance was used but is no longer in memory";
} elseif ($status === KeyStatusEnum::NOT_USED) {
    echo "Instance is defined but not yet used";
}
```

----
[Open source ByJG](http://opensource.byjg.com)
