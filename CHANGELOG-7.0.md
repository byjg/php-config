# Changelog - Version 7.0

## New Features

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
