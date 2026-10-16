# Changelog - Version 6.0

## Overview

Version 6.0 represents a major upgrade of the byjg/config package, bringing significant improvements to dependency injection capabilities, developer experience, and PHP version support. This release introduces powerful new features like auto-initialization, lazy loading, fluent API, and enhanced constructor injection while modernizing the codebase for PHP 8.3-8.5.

## New Features

### Auto-Initialization with Bootstrap Support
- Introduced automatic container initialization via `config/ConfigBootstrap.php`
- Zero-setup configuration loading - just create the bootstrap file and start using `Config::get()`
- Automatically detects and loads configuration based on `APP_ENV` environment variable
- Implements `ConfigInitializeInterface` for standardized bootstrap configuration
- Eliminates boilerplate code for simple use cases

### Config Static Facade
- Added new `Config` facade class providing Laravel-style static API
- Access configuration values anywhere with `Config::get()`, `Config::raw()`, `Config::has()`
- Replaced `Psr11` facade with more feature-rich `Config` facade
- Added `Config::definition()` method to access the Definition instance
- Added `Config::reset()` method for clearing container state (useful in tests)
- Added `Config::getAsFilename()` method for file path resolution

### Fluent API for Environment Configuration
- Introduced fluent interface for creating and configuring environments
- Chain methods for cleaner, more readable configuration setup
- Simplified environment inheritance and configuration

### Enhanced Dependency Injection

#### Lazy Dependency Initialization
- Added `LazyParam` class for deferred parameter resolution
- Created `LazyProxyFactory` for lazy instantiation of dependencies
- Reduces memory footprint and improves startup performance
- Delays expensive object creation until actually needed

#### Constructor Injection Improvements
- Added `withInjectedConstructorOverrides()` for mixed injection scenarios
- Automatically inject class dependencies while manually providing scalar values
- Added support for union types in constructor dependency resolution
- Better handling of complex constructor signatures

### Additional Improvements
- Added explicit `sparse` argument to `Container::get()` signature
- Enhanced `DependencyInjection` with validation improvements
- Added `getClassName()` method to `DependencyInjection` class
- Improved cache handling with refactored caching modes
- Added `releaseSingletons()` method to Config for long-running test suites
- Support for unesc and file parsers in configuration values

## Bug Fixes

- Fixed cache handling in `EagerSingleton` instances
- Fixed boolean parsing with `!bool` syntax
- Fixed namespace issues in test configurations
- Improved conditional logic in `getClassName` method
- Enhanced Psalm compatibility across different versions

## Breaking Changes

| **Before (5.x)**                                 | **After (6.0)**                                    | **Description**                                                                                                     |
|--------------------------------------------------|----------------------------------------------------|---------------------------------------------------------------------------------------------------------------------|
| `php: >=8.1 <8.4`                                | `php: >=8.3 <8.6`                                  | **PHP Version Requirement**: Minimum PHP version raised from 8.1 to 8.3. PHP 8.4 and 8.5 now supported.             |
| `use ByJG\Config\Psr11;`<br/>`Psr11::get('key')` | `use ByJG\Config\Config;`<br/>`Config::get('key')` | **Facade Renamed**: The `Psr11` facade has been renamed to `Config`. Update all references in your code.            |
| `byjg/cache-engine: ^5.0`                        | `byjg/cache-engine: ^6.0`                          | **Dependency Update**: Required version of cache-engine updated to 6.0 (dev dependency).                            |
| `phpunit/phpunit: ^9.6`                          | `phpunit/phpunit: ^10.5\|^11.5`                    | **PHPUnit Update**: PHPUnit upgraded to versions 10.5 or 11.5. Update test code if using PHPUnit-specific features. |
| Manual container initialization required         | Auto-initialization via `ConfigBootstrap.php`      | **Initialization**: While manual initialization still works, the recommended approach is now auto-initialization.   |

## Upgrade Path from 5.x to 6.x

### Step 1: Update PHP Version
Ensure your environment is running PHP 8.3 or higher:
```bash
php -v  # Should show >= 8.3
```

If you're on PHP 8.1 or 8.2, you'll need to upgrade your PHP installation before upgrading this package.

### Step 2: Update Composer Dependencies
Update your `composer.json`:
```bash
composer require "byjg/config:^6.0"
composer update
```

### Step 3: Replace Psr11 Facade with Config Facade
Search and replace all occurrences of the `Psr11` class with `Config`:

**Before:**
```php
use ByJG\Config\Psr11;

$value = Psr11::get('database.host');
$rawValue = Psr11::raw('config.key');
```

**After:**
```php
use ByJG\Config\Config;

$value = Config::get('database.host');
$rawValue = Config::raw('config.key');
```

### Step 4: (Optional) Implement Auto-Initialization
To take advantage of the new auto-initialization feature, create a bootstrap file:

**Create `config/ConfigBootstrap.php`:**
```php
<?php

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

Once this file exists, you can remove manual `Config::initialize()` calls from your application bootstrap - the container will initialize automatically on first use.

### Step 5: Update PHPUnit Tests (If Applicable)
If you have a test suite, update your PHPUnit version:

```bash
composer require --dev "phpunit/phpunit:^10.5|^11.5"
```

Review PHPUnit's migration guides if you encounter test failures:
- [PHPUnit 10 Migration Guide](https://docs.phpunit.de/en/10.5/migration-guide.html)
- [PHPUnit 11 Migration Guide](https://docs.phpunit.de/en/11.5/migration-guide.html)

### Step 6: Test Your Application
After making these changes:

1. Run your test suite to ensure everything works:
```bash
vendor/bin/phpunit
```

2. Test your application in a development environment
3. Verify that configuration loading works as expected
4. Check that dependency injection still resolves correctly

### Step 7: (Optional) Leverage New Features

#### Use Lazy Loading for Expensive Dependencies
```php
use ByJG\Config\LazyParam;

return [
    ExpensiveService::class => DI::bind(ExpensiveService::class)
        ->withConstructorArgs([
            new LazyParam('dependency.key')  // Only resolved when needed
        ])
        ->toSingleton()
];
```

#### Use Constructor Override for Mixed Injection
```php
return [
    UserService::class => DI::bind(UserService::class)
        ->withInjectedConstructorOverrides([
            'apiKey' => 'my-secret-key'  // Manually provide scalars
            // Class dependencies auto-injected
        ])
        ->toInstance()
];
```

### Common Migration Issues

**Issue: "Class Psr11 not found"**
- **Solution**: Replace all `use ByJG\Config\Psr11;` with `use ByJG\Config\Config;`

**Issue: "Your PHP version does not satisfy requirements"**
- **Solution**: Upgrade to PHP 8.3 or higher

**Issue: PHPUnit tests failing after upgrade**
- **Solution**: Review PHPUnit 10/11 migration guides and update deprecated test methods

**Issue: Cache-related errors in tests**
- **Solution**: Use `Config::reset()` or `Config::releaseSingletons()` to clear state between tests

## Documentation Updates

- Comprehensive documentation restructuring with improved navigation
- New documentation for auto-initialization feature
- New documentation for Config facade usage
- Enhanced dependency injection documentation with new features
- Updated examples throughout to use Config facade
- Improved good practices guide
- Better organization of advanced features documentation

## Development & Infrastructure

- Restructured GitHub workflows for Psalm and PHPUnit
- Updated to checkout action v5 in CI/CD
- Added explicit container configuration in PHP workflows
- Added Gitpod configuration for cloud development
- Added VSCode launch configurations
- Added composer scripts for `test` and `psalm` commands
- Improved Psalm configuration and compatibility

## Contributors

This release includes contributions and improvements from the ByJG team and community. Thank you to everyone who helped make version 6.0 possible!

---

For detailed documentation, visit: [https://github.com/byjg/php-config](https://github.com/byjg/php-config)
