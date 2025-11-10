---
sidebar_position: 6
title: Good Practices
description: Best practices for using the configuration library effectively
---

# Good Practices

## Use the Config Facade

The library provides a built-in `Config` facade that gives you static access to the container from anywhere in your code.

### Initialize Once

Initialize the Config facade early in your application bootstrap:

```php
<?php
use ByJG\Config\Config;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

// In your bootstrap/initialization file
$devConfig = new Environment('dev');
$prodConfig = new Environment('prod', [$devConfig]);

$definition = (new Definition())
    ->addEnvironment($devConfig)
    ->addEnvironment($prodConfig);

// Initialize the Config facade
Config::initialize($definition);
```

### Use Throughout Your Application

Once initialized, you can use `Config` anywhere:

```php
<?php
use ByJG\Config\Config;

// Get a value from the configuration
$value = Config::get('property1');

// Use the container for dependency injection
$square = Config::get(\Example\Square::class);

// Get raw values (without processing)
$closure = Config::raw('some_closure');

// Check if a key exists
if (Config::has('api_key')) {
    $apiKey = Config::get('api_key');
}
```

### Benefits

- No need to pass the container instance around
- Clean, simple syntax
- Familiar pattern (similar to Laravel's Config facade)
- Easy to test (use `Config::reset()` in tests)

----
[Open source ByJG](http://opensource.byjg.com)
