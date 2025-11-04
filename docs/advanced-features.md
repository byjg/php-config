---
sidebar_position: 7
title: Advanced Features
description: Explore caching, abstract/final environments, and advanced configuration techniques
---

# Advanced Features

## Caching Configuration

The library supports caching of configuration values to improve performance, especially in production environments where configuration rarely changes.

### Cache Modes

The `Environment` constructor accepts a PSR-16 compatible cache implementation and a cache mode:

```php
<?php
use ByJG\Config\CacheModeEnum;
use ByJG\Config\Environment;
use ByJG\Cache\Psr16\FileSystemCacheEngine;

// Using file system cache with the environment
$cache = new FileSystemCacheEngine('/path/to/cache');
$prod = new Environment(
    'prod', 
    ['dev'], 
    $cache,
    false,
    false,
    CacheModeEnum::singleFile
);
```

There are two cache modes available:

| Mode                           | Description                                           |
|--------------------------------|-------------------------------------------------------|
| `CacheModeEnum::multipleFiles` | Each configuration key is cached separately (default) |
| `CacheModeEnum::singleFile`    | All configuration keys are cached as a single entry   |

#### When to use each cache mode:

- **Multiple Files**: Better when you have many configuration entries that change independently
- **Single File**: Better performance when loading the entire configuration at once and when changes are infrequent

### Compatible Cache Implementations

You can use any PSR-16 compatible cache implementation. The library works well with:

- [byjg/cache-engine](https://github.com/byjg/cache-engine)
- Any other PSR-16 implementation

## Abstract and Final Environments

The Environment constructor supports two special flags:

```php
<?php
use ByJG\Config\Environment;

// Abstract environment (cannot be loaded directly)
$baseConfig = new Environment('base', [], null, true, false);

// Final environment (cannot be inherited from)
$secureConfig = new Environment('secure', [], null, false, true);
```

### Abstract Environments

Abstract environments (`$abstract = true`) cannot be loaded directly but can be inherited by other environments. This is useful for creating base configurations that should never be used on their own.

:::warning
If you try to build a container with an abstract environment, it will throw an exception.
:::

### Final Environments

Final environments (`$final = true`) cannot be inherited by other environments. This is useful for sensitive configurations that should not be extended.

:::warning
If you try to create an environment that inherits from a final environment, it will throw an exception.
:::

## Extended Container Features

The `Container` class implements both PSR-11's `ContainerInterface` and the library's own `ContainerInterfaceExtended`, which provides additional methods:

```php
<?php
// Standard PSR-11 methods
$value = $container->get('key');
$exists = $container->has('key');

// Extended methods
$rawValue = $container->raw('key'); // Get the raw value without processing
$keyStatus = $container->keyStatus('key'); // Check the status of a key
```

### Key Status

The `keyStatus()` method returns a `KeyStatusEnum` value that provides information about the status of a key:

| Status                     | Description                                                                    |
|----------------------------|--------------------------------------------------------------------------------|
| `KeyStatusEnum::NOT_FOUND` | The key does not exist in the container                                        |
| `KeyStatusEnum::STATIC`    | The key exists and has a static value                                          |
| `KeyStatusEnum::IN_MEMORY` | The key exists, is an instance, and is currently loaded in memory              |
| `KeyStatusEnum::WAS_USED`  | The key exists, is an instance, was previously used but is no longer in memory |
| `KeyStatusEnum::NOT_USED`  | The key exists, is an instance, but has not been used yet                      |

## Error Handling

The library uses exceptions to handle error conditions:

| Exception                         | When it occurs                                        |
|-----------------------------------|-------------------------------------------------------|
| `KeyNotFoundException`            | When a key is not found in the container              |
| `ContainerException`              | General container errors                              |
| `ConfigException`                 | General configuration errors                          |
| `ConfigNotFoundException`         | When a configuration file is not found                |
| `DependencyInjectionException`    | When there's an error in dependency injection         |
| `RunTimeException`                | Runtime errors during container operations            |
| `InvalidDateException`            | When a date parsing error occurs                      |

Example of handling container exceptions:

```php
<?php
use ByJG\Config\Exception\KeyNotFoundException;
use ByJG\Config\Exception\ContainerException;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

try {
    $value = $container->get('non_existent_key');
} catch (KeyNotFoundException $e) {
    // Handle key not found
    error_log("Configuration key not found: " . $e->getMessage());
} catch (NotFoundExceptionInterface $e) {
    // Handle PSR-11 not found exception
    error_log("PSR-11 key not found: " . $e->getMessage());
} catch (ContainerException | ContainerExceptionInterface $e) {
    // Handle other container errors
    error_log("Container error: " . $e->getMessage());
}
```

## Performance Considerations

### Caching

Using caching is strongly recommended for production environments. It significantly reduces the overhead of reading and parsing configuration files on each request.

### Lazy Loading

The library uses lazy loading for configurations defined as closures and dependency injections. Values are only processed when they are requested, which improves performance.

### Environment Inheritance

While environment inheritance is powerful, excessive inheritance chains can impact performance. Try to keep inheritance hierarchies shallow.

### Singleton vs. Instance

Using `toSingleton()` for objects that are frequently accessed can improve performance by reusing the same instance.

## Advanced Environment Setup Example

Here's a comprehensive example of setting up a complex environment configuration:

```php
<?php
use ByJG\Config\Definition;
use ByJG\Config\Environment;
use ByJG\Config\CacheModeEnum;
use ByJG\Cache\Psr16\FileSystemCacheEngine;

// Create abstract base environment
$base = new Environment('base', [], null, true);

// Development environment inherits from base
$dev = new Environment('dev', ['base']);

// Staging environment with cache
$stagingCache = new FileSystemCacheEngine('/tmp/cache/staging');
$staging = new Environment(
    'staging', 
    ['dev'], 
    $stagingCache, 
    false, 
    false, 
    CacheModeEnum::multipleFiles
);

// Production environment with optimized cache
$prodCache = new FileSystemCacheEngine('/tmp/cache/prod');
$prod = new Environment(
    'prod', 
    ['staging'], 
    $prodCache, 
    false, 
    true, // Final environment
    CacheModeEnum::singleFile
);

// Create definition with all environments
$definition = (new Definition())
    ->withConfigVar('APP_ENV')
    ->addEnvironment($base)
    ->addEnvironment($dev)
    ->addEnvironment($staging)
    ->addEnvironment($prod);

// Build the container
$container = $definition->build();
```

----
[Open source ByJG](http://opensource.byjg.com) 