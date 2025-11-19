<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\LazyParam;
use Tests\DIClasses\Area;
use Tests\DIClasses\EagerClass;
use Tests\DIClasses\RectangleTriangle;

return [
    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),

    EagerClass::class => DI::bind(EagerClass::class)
        ->withMethodCall('initialize', [LazyParam::get(Area::class)])
        ->toEagerSingleton(),
];
