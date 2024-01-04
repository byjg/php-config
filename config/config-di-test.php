<?php

use ByJG\Config\DependencyInjection as DI;
use Test\DIClasses\Area;
use Test\DIClasses\InjectedLegacy;
use Test\DIClasses\Random;
use Test\DIClasses\RectangleTriangle;
use Test\DIClasses\SumAreas;

return [
    Random::class => DI::bind(Random::class)
        ->withConstructorArgs([4])
        ->toInstance(),

    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),

    'Value' => DI::use(Area::class)
        ->withMethodCall('calculate')
        ->toInstance(),

    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),

    InjectedLegacy::class => DI::bind(InjectedLegacy::class)
        ->withInjectedLegacyConstructor()
        ->toInstance(),
];
