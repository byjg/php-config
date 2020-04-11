<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use DIClasses\InjectedFail;
use DIClasses\InjectedLegacy;
use DIClasses\Random;
use DIClasses\Square;
use DIClasses\SumAreas;

return [
    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),

    InjectedLegacy::class => DI::bind(InjectedLegacy::class)
        ->withInjectedLegacyConstructor()
        ->toInstance(),
];
