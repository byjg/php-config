<?php

use ByJG\Config\DependencyInjection as DI;
use Test\DIClasses\InjectedLegacy;
use Test\DIClasses\SumAreas;

return [
    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),

    InjectedLegacy::class => DI::bind(InjectedLegacy::class)
        ->withInjectedLegacyConstructor()
        ->toInstance(),
];
