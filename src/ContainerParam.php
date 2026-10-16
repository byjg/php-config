<?php

namespace ByJG\Config;

use Psr\Container\ContainerInterface;

/**
 * A marker that resolves to the Container itself.
 *
 * Use it anywhere a Param is accepted — constructor arguments or method calls — to hand
 * the container to a service that needs to resolve things on its own (a router
 * instantiating controllers, for example):
 *
 *     Server::class => DI::bind(Server::class)
 *         ->withMethodCall('withContainer', [Param::container()])
 *         ->toSingleton(),
 *
 * Instances are created through {@see Param::container()}.
 *
 * This is deliberately a stateless marker rather than the container instance itself:
 *
 * - Container::get() already injects the container into every DependencyInjection object
 *   before resolving arguments, so the real instance is always at hand at resolution time.
 * - Container::saveToCache() serializes DependencyInjection objects. A marker serializes
 *   cleanly; a live container would not.
 * - It carries no dependency on the static Config facade, so it is safe inside eager
 *   singletons, which are built before Config::$container is assigned.
 */
class ContainerParam extends Param
{
    /**
     * The parent holds a key it will never be looked up by. It is set to
     * ContainerInterface::class so that if this ever *is* resolved as a plain Param —
     * i.e. someone reorders the instanceof checks in DependencyInjection::getArgs() —
     * the resulting KeyNotFoundException names something recognisable instead of ''.
     */
    protected function __construct()
    {
        parent::__construct(ContainerInterface::class);
    }
}
