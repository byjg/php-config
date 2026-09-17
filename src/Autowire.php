<?php

namespace ByJG\Config;

use ByJG\Config\Exception\DependencyInjectionException;

/**
 * A binding template applied to every class matching a pattern, instead of one entry per
 * class.
 *
 * The config key is the pattern; `*` matches any run of characters:
 *
 *     return [
 *         'App\Controller\*' => Autowire::rule()
 *             ->withInjectedConstructor()
 *             ->toInstance(),
 *     ];
 *
 * This is meant for *terminal* classes — ones nothing else depends on, such as REST
 * controllers. There the per-class binding encodes no decision: one implementation named
 * directly by the router, always per-request, all constructor arguments type-hinted
 * services that are themselves explicitly bound. Listing them one by one is ceremony.
 *
 * It is deliberately a poor fit for services and repositories, where the binding does
 * carry decisions (which implementation, singleton or not, scalar constructor arguments).
 * Bind those explicitly.
 *
 * Scope patterns to a namespace. A bare suffix such as `*Controller` also matches classes
 * in your vendor directory, and since Container::has() consults these rules, that can
 * quietly flip `has() ? get() : $default` feature checks elsewhere in an application.
 *
 * An explicit binding always wins over a pattern.
 */
class Autowire
{
    protected bool $injectConstructor = true;

    protected bool $singleton = false;

    protected function __construct()
    {
    }

    public static function rule(): Autowire
    {
        return new Autowire();
    }

    /**
     * Resolve constructor arguments from their type hints.
     *
     * A class that declares no constructor degrades to withConstructorNoArgs()
     * automatically — withInjectedConstructor() reflects on a __construct that does not
     * exist and would fail. An ActiveRecord-style controller is the usual case.
     */
    public function withInjectedConstructor(): static
    {
        $this->injectConstructor = true;
        return $this;
    }

    public function withConstructorNoArgs(): static
    {
        $this->injectConstructor = false;
        return $this;
    }

    public function toSingleton(): static
    {
        $this->singleton = true;
        return $this;
    }

    public function toInstance(): static
    {
        $this->singleton = false;
        return $this;
    }

    /**
     * Does $id match $pattern? `*` matches any run of characters; everything else is
     * literal, so namespace separators need no escaping by the caller.
     */
    public static function matchesPattern(string $pattern, string $id): bool
    {
        if (!str_contains($pattern, '*')) {
            return $pattern === $id;
        }

        $quoted = array_map(
            fn(string $part): string => preg_quote($part, '/'),
            explode('*', $pattern)
        );

        return (bool)preg_match('/^' . implode('.*', $quoted) . '$/', $id);
    }

    /**
     * Build the binding this rule describes for a concrete class.
     *
     * @throws DependencyInjectionException
     * @throws \ReflectionException
     */
    public function createBinding(string $className): DependencyInjection
    {
        if (!class_exists($className)) {
            throw new DependencyInjectionException("Class $className does not exists");
        }

        $binding = DependencyInjection::bind($className);

        if ($this->injectConstructor && method_exists($className, '__construct')) {
            $binding->withInjectedConstructor();
        } else {
            $binding->withConstructorNoArgs();
        }

        return $this->singleton ? $binding->toSingleton() : $binding->toInstance();
    }
}
