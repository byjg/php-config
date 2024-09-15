<?php

use ByJG\Config\DependencyInjection as DI;
use Tests\DIClasses\InjectedLegacy;
use Tests\DIClasses\SumAreas;

return [
    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),

    InjectedLegacy::class => DI::bind(InjectedLegacy::class)
        ->withInjectedLegacyConstructor()
        ->toInstance(),
];
