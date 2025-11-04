---
sidebar_position: 8
title: PSR-11 Container Interface
description: Using the Psr11 static facade for convenient container access
---

# PSR-11 Container Interface

The `Psr11` class provides a convenient static interface to access the container functionality without the need to pass 
around the container instance. It acts as a facade for the Container class, implementing methods similar to 
the PSR-11 Container Interface standard but with additional functionality specific to the ByJG Config implementation.

## Initializing the Container

Before using any of the static methods, you must initialize the container with a Definition:

```php
<?php
use ByJG\Config\Definition;
use ByJG\Config\Psr11;

// Create and configure the definition as needed
$definition = new Definition();
// ... configure definition ...

// Initialize the Psr11 static container with your definition
Psr11::initialize($definition, 'prod'); // The second parameter is optional
```

## Accessing Container Values

Once initialized, you can access values from anywhere in your application:

```php
<?php
use ByJG\Config\Psr11;

// Get a configured value
$dbHost = Psr11::get('database.host');

// Get a class instance (with dependency injection)
$service = Psr11::get(\App\Service\MyService::class);

// Get a value with constructor parameters
$calculator = Psr11::get(\App\Calculator::class, 10, 20);
```

## Getting Raw Values

Unlike `get()`, the `raw()` method returns the raw value without attempting to instantiate classes or resolve dependencies:

```php
<?php
use ByJG\Config\Psr11;

// Get the raw value of a closure without executing it
$closureValue = Psr11::raw('my.closure');

// Get the raw configuration value
$rawValue = Psr11::raw('my.config.key');
```

## Checking Value Existence

You can check if a specific key exists in the container:

```php
<?php
use ByJG\Config\Psr11;

if (Psr11::has('api.key')) {
    // The key exists in the container
    $apiKey = Psr11::get('api.key');
}
```

## Getting File Paths

For file-based configuration values, you can get the resolved path:

```php
<?php
use ByJG\Config\Psr11;

// Get a configuration value as a resolved filename
$logPath = Psr11::getAsFilename('log.file');
```

## Benefits of Using Psr11

1. **Simplicity**: Access your configuration and services from anywhere without dependency injection
2. **Consistency**: Standardized approach to working with container values
3. **Flexibility**: Still maintains all the power of the underlying Container system
4. **PSR Compatibility**: Follows PSR-11 approach while extending it with useful features

## Complete Method Reference

| Method | Description |
|--------|-------------|
| `initialize(Definition $definition, ?string $env = null)` | Initializes the container with the provided definition |
| `get(string $id, mixed ...$parameters)` | Retrieves an entry from the container, resolving dependencies if needed |
| `raw(string $id)` | Retrieves the raw value without processing |
| `has(string $id)` | Checks if the container can return an entry for the given identifier |
| `getAsFilename(string $id)` | Gets the value as a resolved filename path |

## Example Use Case

Here's a complete example showing how to use the Psr11 class in a typical application:

```php
<?php
// bootstrap.php
use ByJG\Config\Definition;
use ByJG\Config\Environment;
use ByJG\Config\Psr11;

// Create environments
$dev = new Environment('dev');
$prod = new Environment('prod', ['dev']);

// Create the definition
$definition = (new Definition())
    ->addEnvironment($dev)
    ->addEnvironment($prod);

// Initialize Psr11 with the definition and current environment
$env = getenv('APP_ENV') ?: 'dev';
Psr11::initialize($definition, $env);
```

```php
<?php
// anywhere in your application code
use ByJG\Config\Psr11;

class UserController 
{
    public function login()
    {
        // Get values directly using the static interface
        $userService = Psr11::get(\App\Service\UserService::class);
        $jwtSecret = Psr11::get('jwt.secret');
        $logFile = Psr11::getAsFilename('app.log');
        
        // Use these values in your application logic
        // ...
    }
}
```

----
[Open source ByJG](http://opensource.byjg.com) 