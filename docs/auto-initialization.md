---
sidebar_position: 9
title: Auto-Initialization
description: Automatically initialize the Config facade with a bootstrap file
---

# Auto-Initialization

The auto-initialization feature allows the Config facade to automatically load and initialize itself from a bootstrap
file without requiring manual initialization code. This provides a cleaner, more convenient way to use the Config facade
throughout your application.

## How It Works

When you call any Config method (like `Config::get()`, `Config::has()`, etc.) without manually initializing it first,
the Config facade will automatically:

1. Look for a `config/ConfigBootstrap.php` file in your project
2. Load the bootstrap file and expect it to return an instance of `ConfigInitializeInterface`
3. Call the `loadDefinition()` method to get your configuration definition
4. Initialize itself with the definition and the environment specified in the `APP_ENV` environment variable

This happens only once - subsequent calls to Config methods will use the already-initialized container.

## Creating the Bootstrap File

Create a file at `config/ConfigBootstrap.php` that returns an instance implementing `ConfigInitializeInterface`:

```php
<?php
// config/ConfigBootstrap.php

use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        // Define your environments
        $dev = new Environment('dev');
        $staging = new Environment('staging', [$dev]);
        $prod = new Environment('prod', [$dev]);

        // Build and return the definition
        return (new Definition())
            ->addEnvironment($dev)
            ->addEnvironment($staging)
            ->addEnvironment($prod);
    }
};
```

## Using Auto-Initialization

Once the bootstrap file is in place, you can use Config directly without any initialization:

```php
<?php
use ByJG\Config\Config;

// No initialization needed - Config automatically loads from ConfigBootstrap.php
$databaseHost = Config::get('database.host');
$userService = Config::get(\App\Services\UserService::class);

if (Config::has('feature.enabled')) {
    // Feature is enabled
}
```

## Environment Detection

The auto-initialization respects the `APP_ENV` environment variable:

```bash
# Set the environment
export APP_ENV=prod

# Or in a .env file
APP_ENV=prod
```

If `APP_ENV` is not set, the bootstrap's `loadDefinition()` method receives `null` as the `$env` parameter, and the
behavior depends on your Definition configuration.

## Bootstrap File Location

The Config facade looks for the bootstrap file in the `config` directory using the same logic as `Definition::findBaseDir()`:

1. First, it checks `vendor/../../../config/ConfigBootstrap.php` (when installed as a dependency)
2. If not found, it checks `src/../config/ConfigBootstrap.php` (when in development)

This ensures the bootstrap file is found correctly whether your package is installed via Composer or you're developing it.

## Error Handling

The auto-initialization provides clear error messages when something goes wrong:

### Bootstrap File Not Found

```
RunTimeException: Environment isn't build yet. Please call Config::initialize() or
create a config/ConfigBootstrap.php file that implements ConfigInitializeInterface.
```

**Solution**: Create the `config/ConfigBootstrap.php` file or manually initialize the Config facade.

### Invalid Bootstrap File

```
RunTimeException: The config/ConfigBootstrap.php file must return an instance of ConfigInitializeInterface.
```

**Solution**: Ensure your bootstrap file returns an object that implements `ConfigInitializeInterface`.

## Advanced Bootstrap Example

Here's a more advanced bootstrap example with additional features:

```php
<?php
// config/ConfigBootstrap.php

use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;
use ByJG\Cache\Psr16\FileSystemCacheEngine;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        // Define environments with caching
        $dev = new Environment('dev');

        $prod = new Environment(
            'prod',
            [$dev],
            new FileSystemCacheEngine('config-cache')
        );

        $definition = (new Definition())
            ->addEnvironment($dev)
            ->addEnvironment($prod)
            ->withOSEnvironment(['PATH', 'HOME']) // Load specific OS environment variables
            ->withConfigVar('APP_ENV'); // Use APP_ENV to determine the environment

        // You can add custom logic here
        if ($env === 'dev') {
            // Development-specific setup
            error_reporting(E_ALL);
        }

        return $definition;
    }
};
```

## When to Use Auto-Initialization

**Use auto-initialization when:**
- You want a simple, Laravel-style configuration setup
- You're building a web application or CLI tool
- You want to use the Config facade throughout your application
- Your configuration setup is straightforward

**Use manual initialization when:**
- You need fine-grained control over when and how the container is initialized
- You're building a library that shouldn't assume a specific file structure
- You need to initialize multiple containers with different configurations
- You want to dynamically generate the definition based on runtime conditions

## Combining with Manual Initialization

If you call `Config::initialize()` manually, the auto-initialization will not occur:

```php
<?php
use ByJG\Config\Config;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

// Manual initialization takes precedence
$dev = new Environment('dev');
$definition = (new Definition())->addEnvironment($dev);
Config::initialize($definition, 'dev');

// Now Config uses your manual initialization, not the bootstrap file
$value = Config::get('some.key');
```

## Testing with Auto-Initialization

During testing, you may want to reset the Config state between tests:

```php
<?php
use ByJG\Config\Config;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset Config state after each test
        Config::reset();
    }

    public function testSomething()
    {
        // Config will auto-initialize from bootstrap file
        $value = Config::get('test.value');
        $this->assertEquals('expected', $value);
    }
}
```

## See Also

- [Config Facade](config-facade.md) - Learn more about using the Config facade
- [Load the Configuration](load-the-configuration.md) - Traditional configuration loading
- [Setup](setup.md) - Setting up configuration files

----
[Open source ByJG](http://opensource.byjg.com)
