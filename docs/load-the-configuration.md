# Loading the configuration

After [setup the configuration files](setup.md), you need to load them into the container.

## Create the environment definition

The definition will specify how the configuration will be loaded, what environments will be available and the inheritance between them.

```php
<?php
use ByJG\Config\Definition;
use ByJG\Config\Environment;

$dev = new Environment('dev');
$prod = new Environment('prod', ['dev'], $somePsr16Implementation);

$definition = (new Definition())
    ->withConfigVar('APP_ENV')    // Set up the environment var used to auto select the config. 'APP_ENV' is default.
    ->addEnvironment($dev)        // Defining the 'dev' environment
    ->addEnvironment($prod)       // Defining the `prod` environment that inherits from `dev`
;
```

The `Environment` constructor has the following parameters:

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
    CacheModeEnum $cacheMode = CacheModeEnum::multipleFiles  // How the cache will be stored, in a single key or multiple keys
);
```

## Build the definition

```php
<?php
// This will check the config var 'APP_ENV' and load the configuration from the file config-<APP_ENV>.php and config-<APP_ENV>.env and create the instance `$container`
$container = $definition->build();
```

This method requires the environment var `APP_ENV` to be set, otherwise will throw an exception. 

Also, if at least of the files doesn´t exists:
- `config-<APP_ENV>.php`
- `config-<APP_ENV>.env`
- `<APP_ENV>/*.php`
- `<APP_ENV>/*.env`

## Get the Values from the container

After build the definition you can get the values from the container:

```php
<?php
$property = $container->get('property1');
```

If value is a closure, it will return the closure execution result:

```php
<?php
// Get the closure execution result:
$property = $container->get('property3');

// Get the closure execution result with the arguments 1 and 2:
$property = $container->get('propertyWithArgs', 1, 2);
```

Also, it is possible to get **raw** value without any parse:

```php
<?php
$property = $container->raw('property3');
```

## Get the environment name is being used

```php
<?php
$definition->getCurrentEnvironment();
```

----
[Open source ByJG](http://opensource.byjg.com)
