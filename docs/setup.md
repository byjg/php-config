---
sidebar_position: 1
title: Setup
description: Learn how to set up configuration files and environments for your PHP project
---

# Setup

## Configuration files

The project is able to work with different configurations for different environments. Each environment is defined by a set of configuration files and only the current environment is loaded into the container.

Let's say we want to work with a development environment and a production environment. We will have two sets of configuration files, one for each environment.

Let's call development as `dev` and production as `prod`. You need to create a folder called `config` in your project root at the same level of the vendor directory.

Inside these folders create files called "config-dev.php", "config-prod.php" where dev, prod, etc. are your configuration sets.

Your folder will look like this:

```text
<project root>
    |
    +-- config
           |
           + .env
           + config-dev.php
           + config-dev.env
           + config-prod.php
           + config-prod.env
   +-- vendor
   +-- composer.json
```

### The .env file

The `*.env` is a file that contains the environment variables that will be loaded into the container. The format is:

```ini
property1=VALUE_1
property2=VALUE_2

; This is a comment
; By default all variables are parsed as string. 
; If you want to parse as boolean, integer or float, you need to use the type casting
; By default, all properties are parsed as string. You can parse as bool, int or float as this example:

PARAM1=!bool true
PARAM2=!int 20
PARAM3=!float 3.14
```

:::info
The file named `.env` will be loaded to **ALL ENVIRONMENTS**.
:::

### The .php file

The `*.php` file needs to return an associative array with the configuration values. It can return:

- A single value
- A closure - It will process the value only when it is requested
- A dependency injection definition

```php
<?php

use ByJG\Config\DependencyInjection as DI;
use Example\SumAreas;

return [
    'property1' => 'string',
    'property2' => true,
    'property3' => function () {
        return 'xxxxxx';
    },
    'propertyWithArgs' => function ($p1, $p2) {
        return "Result with $p1 and $p2";
    },
    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
```

## Advanced configuration

It is possible instead of a single file, to have a folder with multiple files. The files will be loaded in alphabetical order.

Each folder inside the `config` directory will be the name of the environment. The example below will have two environments: `dev` and `prod`.

The file names inside the folder don't matter and don't need to be the same in the other environments. 
The only thing that matters is the extension. It can be only `.env` or `.php`.

```text
<project root>
   |
   +-- config
   |      |
   |      +-- dev
   |      |     |
   |      |     + .env
   |      |     + config.php
   |      |     + config.env
   |      +-- prod
   |            |
   |            + .env
   |            + config.php
   |            + config.env
   +-- vendor
   +-- composer.json
```

This option is useful when you have a lot of configuration files, and you want to split them into multiple files.

You can combine both folder and standalone files. In that case, the individual files will take precedence over folder files.


## Inheritance between environments

One environment can inherit from another. This means that the environment that inherits will have all the variables of the inherited environment and can override them if the names match.

:::tip
This is very important because you can have a common configuration for all environments and override only the variables that are different.
:::

To configure inheritance, you can specify it when creating the Environment object:

```php
<?php
use ByJG\Config\Environment;

// Traditional approach
$dev = new Environment('dev');
$prod = new Environment('prod', [$dev]); // 'prod' inherits from 'dev'

// Fluent API (recommended)
$dev = Environment::create('dev');
$prod = Environment::create('prod')->inheritFrom($dev);
```

## Load Priority

If you have multiple files and the same variable is defined in more than one file,
the system will override the value with the value defined by the last file loaded with the same variable.

The load order within a single directory is:
1. `config-<ENV>.php`
2. `config-<ENV>.env`
3. `<ENV>/*.php` (in alphabetical order)
4. `<ENV>/*.env` (in alphabetical order)
5. `.env`

## Custom Config Directory

By default the library looks for config files in the `config/` directory at the project root. You can override this with `withBaseDir()`:

```php
<?php
use ByJG\Config\Definition;
use ByJG\Config\Environment;

$definition = (new Definition())
    ->addEnvironment(new Environment('prod'))
    ->withBaseDir('/path/to/my/config');
```

:::note
`withBaseDir()` replaces the default directory entirely. It throws a `ConfigException` if the directory does not exist.
:::

## Multiple Config Directories

Use `addConfigDirectory()` to load config from more than one directory. This is useful for layered configurations — for example, a package that ships default values which the application can then override:

```php
<?php
use ByJG\Config\Definition;
use ByJG\Config\Environment;

$definition = (new Definition())
    ->addEnvironment(new Environment('prod'))
    ->addConfigDirectory('/path/to/package/config')   // defaults
    ->addConfigDirectory('/path/to/app/config');      // overrides
```

Directories are loaded in the order they are added. For any given key, the value from the **last directory** that defines it wins. Keys that exist only in one directory are available regardless.

The full load order across all directories is:

| Priority (lowest → highest)  | Source                      |
|------------------------------|-----------------------------|
| 1st directory — config file  | `config-<ENV>.php` / `.env` |
| 1st directory — sub-folder   | `<ENV>/*.php` / `*.env`     |
| 1st directory — root .env    | `.env`                      |
| 2nd directory — config file  | `config-<ENV>.php` / `.env` |
| 2nd directory — sub-folder   | `<ENV>/*.php` / `*.env`     |
| 2nd directory — root .env    | `.env`                      |
| … and so on                  |                             |

:::note
`addConfigDirectory()` throws a `ConfigException` if the directory does not exist. If a directory exists but has no config file for the current environment, it is silently skipped.
:::

:::tip
`withBaseDir()` and `addConfigDirectory()` can be combined. `withBaseDir()` sets a single directory (replacing any previously added ones), while `addConfigDirectory()` appends to the list.
:::


----
[Open source ByJG](http://opensource.byjg.com)
