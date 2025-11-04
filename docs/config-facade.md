---
sidebar_position: 8
title: Config Facade
description: Using the Config static facade for convenient container access
---

# Config Facade

The `Config` class provides a convenient static interface to access the container functionality without the need to pass
around the container instance. It acts as a facade for the Container class, providing a clean and familiar API
for accessing configuration values and resolving dependencies.

## Initializing the Container

Before using any of the static methods, you must initialize the container with a Definition:

```php
<?php
use ByJG\Config\Definition;
use ByJG\Config\Config;

// Create and configure the definition as needed
$definition = new Definition();
// ... configure definition ...

// Initialize the Config static facade with your definition
Config::initialize($definition, 'prod'); // The second parameter is optional
```

## Accessing Container Values

Once initialized, you can access values from anywhere in your application:

```php
<?php
use ByJG\Config\Config;

// Get a configured value
$dbHost = Config::get('database.host');

// Get a class instance (with dependency injection)
$service = Config::get(\App\Service\MyService::class);

// Get a value with constructor parameters
$calculator = Config::get(\App\Calculator::class, 10, 20);
```

## Getting Raw Values

Unlike `get()`, the `raw()` method returns the raw value without attempting to instantiate classes or resolve dependencies:

```php
<?php
use ByJG\Config\Config;

// Get the raw value of a closure without executing it
$closureValue = Config::raw('my.closure');

// Get the raw configuration value
$rawValue = Config::raw('my.config.key');
```

## Checking Value Existence

You can check if a specific key exists in the container:

```php
<?php
use ByJG\Config\Config;

if (Config::has('api.key')) {
    // The key exists in the container
    $apiKey = Config::get('api.key');
}
```

## Getting File Paths

For file-based configuration values, you can get the resolved path:

```php
<?php
use ByJG\Config\Config;

// Get a configuration value as a resolved filename
$logPath = Config::getAsFilename('log.file');
```

## Benefits of Using Config Facade

1. **Simplicity**: Access your configuration and services from anywhere without dependency injection
2. **Consistency**: Familiar pattern similar to Laravel's Config facade
3. **Flexibility**: Maintains all the power of the underlying PSR-11 Container
4. **Clean Code**: No need to pass container instances through your application

## Complete Method Reference

| Method | Description |
|--------|-------------|
| `initialize(Definition $definition, ?string $env = null)` | Initializes the container with the provided definition |
| `get(string $id, mixed ...$parameters)` | Retrieves an entry from the container, resolving dependencies if needed |
| `raw(string $id)` | Retrieves the raw value without processing |
| `has(string $id)` | Checks if the container can return an entry for the given identifier |
| `getAsFilename(string $id)` | Gets the value as a resolved filename path |

## Example Use Case

Here's a complete example showing how to use the Config facade in a typical application:

```php
<?php
// bootstrap.php
use ByJG\Config\Definition;
use ByJG\Config\Environment;
use ByJG\Config\Config;

// Create environments
$dev = new Environment('dev');
$prod = new Environment('prod', ['dev']);

// Create the definition
$definition = (new Definition())
    ->addEnvironment($dev)
    ->addEnvironment($prod);

// Initialize Config facade with the definition and current environment
$env = getenv('APP_ENV') ?: 'dev';
Config::initialize($definition, $env);
```

```php
<?php
// anywhere in your application code
use ByJG\Config\Config;

class UserController 
{
    public function login()
    {
        // Get values directly using the static interface
        $userService = Config::get(\App\Service\UserService::class);
        $jwtSecret = Config::get('jwt.secret');
        $logFile = Config::getAsFilename('app.log');
        
        // Use these values in your application logic
        // ...
    }
}
```

----
[Open source ByJG](http://opensource.byjg.com) 