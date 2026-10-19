# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## New Features

### Autowiring a family of classes with `Autowire`

Terminal classes — ones nothing else depends on, such as REST controllers — can now be
bound by pattern instead of one entry each. The config key is the pattern, `*` matches any
run of characters:

```php
use ByJG\Config\Autowire;

return [
    'App\Controller\*' => Autowire::rule()
        ->withInjectedConstructor()
        ->toInstance(),
];
```

- A class that declares no constructor degrades to `withConstructorNoArgs()`
  automatically, so ActiveRecord-style controllers need no special case.
- An explicit binding always wins over a pattern.
- `Container::has()` reports pattern-matched classes as available, in line with PSR-11.
  It requires the class to exist, so a typo stays a plain "not found".

Scope patterns to a namespace. A bare `*Controller` also matches vendor classes, and since
`has()` consults these rules that can quietly flip `has() ? get() : $default` checks.

Not intended for services or repositories: those bindings carry real decisions (which
implementation, singleton or not, scalar arguments) and should stay explicit.

### Injecting the container itself with `Param::container()`

Some services must resolve collaborators on their own — a router that instantiates controllers by class
name, for example. `Param::container()` hands such a service the container, and works anywhere a `Param`
is accepted (constructor arguments and `withMethodCall()` alike):

```php
use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;

return [
    Example\Server::class => DI::bind(Example\Server::class)
        ->withConstructorArgs([Param::get(Psr\Log\LoggerInterface::class)])
        ->withMethodCall('withContainer', [Param::container()])
        ->toSingleton(),
];
```

It resolves to the `Container` instance already injected into the binding, which makes it safe in places
the static facade cannot serve:

- **Eager singletons.** These are resolved inside `Container::__construct()`, before `Config::$container`
  is assigned — a `Config::getContainer()` call at that point recurses into auto-initialization and fails.
- **Cached containers.** `Param::container()` stores a stateless marker, so bindings serialize cleanly.
  After `Container::createFromCache()`, the restored binding receives the *new* container rather than a
  stale one.

Backed by the new `ByJG\Config\ContainerParam` class. Being a `Param` subclass, it is matched ahead of the
generic `Param` branch when arguments are resolved.

### `Config::getContainer()` is now public

Previously private. It returns the underlying PSR-11 `Container`, for code that must hand the container to
a collaborator but is itself constructed outside dependency injection — a test harness assembling its own
objects, for instance.

Inside a configuration definition, prefer `Param::container()`. `Config::getContainer()` must not be called
while the configuration is being built: the facade is populated only after `Definition::build()` returns,
so a call made during the build recurses into auto-initialization and throws `RunTimeException`.

### `{__ROOT__}` in `.env` values

`{__ROOT__}` in a `.env` value is replaced, when the file is loaded, by the project root: the directory
of the root `composer.json`, from `Composer\InstalledVersions`:

```ini
DBDRIVER_CONNECTION=sqlite://{__ROOT__}/db/dev.sqlite
LOG_FILE={__ROOT__}/var/log/app.log
LOGO=!file {__ROOT__}/public/logo.svg
```

The same file then works wherever the project is installed (a developer machine, a container under
`/srv`), from any working directory and any entry point (a CLI script, `public/index.php`). It applies
to every `.env` file, including the ones inside an environment folder, and combines with the `!parser`
prefixes. PHP config files keep using `__DIR__`. `Definition::getProjectRoot()` returns the same path.

`composer-runtime-api` `^2.0` is a new requirement: it provides `Composer\InstalledVersions`.

## Breaking Changes

- Constructor auto-injection (`withInjectedConstructor()` / `withInjectedConstructorOverrides()`) now rejects
  intersection types (e.g. `Countable&ArrayAccess`) with a `DependencyInjectionException` at definition time.
  Previously the parameter was registered as a dependency named after the literal type expression
  (`"Countable&ArrayAccess"`), which failed later with an unrelated "key not found" error. Supply such
  parameters through the overrides array instead:

  ```php
  DI::bind(MyClass::class)
      ->withInjectedConstructorOverrides(['dependency' => Param::get(MyCollection::class)])
      ->toInstance();
  ```

### Enum cases renamed to PascalCase

Enum cases follow the ByJG standard and are written in PascalCase. Both enums are pure (not
backed), so every reference to a case (or to `->name`) has to be updated.

| Enum | Before (6.x) | After (7.0) |
|---|---|---|
| `CacheModeEnum` | `singleFile`, `multipleFiles` | `SingleFile`, `MultipleFiles` |
| `KeyStatusEnum` | `NOT_FOUND`, `STATIC`, `IN_MEMORY`, `WAS_USED`, `NOT_USED` | `NotFound`, `Value`, `InMemory`, `WasUsed`, `NotUsed` |

Not a plain change of casing: `KeyStatusEnum::STATIC` is now `KeyStatusEnum::Value`.

## Bug Fixes

- `Container::keyStatus()` on a class matched by an `Autowire` rule but not resolved yet raised an
  "Undefined array key" warning and returned `Value`. It now returns `KeyStatusEnum::NotUsed`.

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/cache-engine` is now `^7.0`.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
- `phpunit.xml` sets `ignoreIndirectDeprecations="true"`. PHP 8.6 deprecates
  `spl_object_hash()`, which `laravel/serializable-closure` still calls (in 2.x too).
  Deprecations raised inside this package's own `src/` still fail the suite.
