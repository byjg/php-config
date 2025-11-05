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

You have two options for initializing the container:

### Option 1: Auto-Initialization (Recommended)

The Config facade can automatically initialize itself by looking for a `config/ConfigBootstrap.php` file. This is the
simplest approach and requires no manual initialization:

```php
<?php
// config/ConfigBootstrap.php

use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        $dev = new Environment('dev');
        $prod = new Environment('prod', [$dev]);

        return (new Definition())
            ->addEnvironment($dev)
            ->addEnvironment($prod);
    }
};
```

Once this file is in place, you can immediately use the Config facade without manual initialization:

```php
<?php
use ByJG\Config\Config;

// No initialization needed! Config will auto-load from ConfigBootstrap.php
$dbHost = Config::get('database.host');
```

The auto-initialization:
- Looks for the bootstrap file in the `config` directory (relative to your project root)
- Uses the `APP_ENV` environment variable to determine which environment to load
- Only initializes once (subsequent calls use the already-initialized container)

### Option 2: Manual Initialization

You can also manually initialize the container with a Definition:

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

1. **Auto-Initialization**: Automatically loads configuration from a bootstrap file
2. **Simplicity**: Access your configuration and services from anywhere without dependency injection
3. **Consistency**: Familiar pattern similar to Laravel's Config facade
4. **Flexibility**: Maintains all the power of the underlying PSR-11 Container
5. **Clean Code**: No need to pass container instances through your application

## Complete Method Reference

| Method | Description |
|--------|-------------|
| `initialize(Definition $definition, ?string $env = null)` | Initializes the container with the provided definition |
| `get(string $id, mixed ...$parameters)` | Retrieves an entry from the container, resolving dependencies if needed |
| `raw(string $id)` | Retrieves the raw value without processing |
| `has(string $id)` | Checks if the container can return an entry for the given identifier |
| `getAsFilename(string $id)` | Gets the value as a resolved filename path |

## Example Use Case

Here's a complete example showing how to use the Config facade with auto-initialization:

```php
<?php
// config/ConfigBootstrap.php
use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        // Create environments
        $dev = new Environment('dev');
        $prod = new Environment('prod', [$dev]);

        // Create the definition
        return (new Definition())
            ->addEnvironment($dev)
            ->addEnvironment($prod);
    }
};
```

```php
<?php
// anywhere in your application code
use ByJG\Config\Config;

class UserController
{
    public function login()
    {
        // No initialization needed - Config auto-loads from ConfigBootstrap.php!
        // Just use Config::get() directly
        $userService = Config::get(\App\Service\UserService::class);
        $jwtSecret = Config::get('jwt.secret');
        $logFile = Config::getAsFilename('app.log');

        // Use these values in your application logic
        // ...
    }
}
```

### Manual Initialization Example

If you prefer manual initialization or need more control:

```php
<?php
// bootstrap.php
use ByJG\Config\Definition;
use ByJG\Config\Environment;
use ByJG\Config\Config;

// Create environments
$dev = new Environment('dev');
$prod = new Environment('prod', [$dev]);

// Create the definition
$definition = (new Definition())
    ->addEnvironment($dev)
    ->addEnvironment($prod);

// Initialize Config facade with the definition and current environment
$env = getenv('APP_ENV') ?: 'dev';
Config::initialize($definition, $env);
```

----
[Open source ByJG](http://opensource.byjg.com) 