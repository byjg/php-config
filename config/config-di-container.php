<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Tests\DIClasses\Area;
use Tests\DIClasses\ContainerAware;
use Tests\DIClasses\RectangleTriangle;

return [
    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),

    'container.ctor' => DI::bind(ContainerAware::class)
        ->withConstructorArgs([Param::container()])
        ->toSingleton(),

    'container.method' => DI::bind(ContainerAware::class)
        ->withConstructorNoArgs()
        ->withMethodCall('withContainer', [Param::container()])
        ->toSingleton(),

    // Eager: resolved inside Container::__construct(), before Config::$container is
    // assigned. This is the case the static-facade alternative cannot serve.
    'container.eager' => DI::bind(ContainerAware::class)
        ->withConstructorArgs([Param::container()])
        ->toEagerSingleton(),
];
