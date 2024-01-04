<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Test\DIClasses\InjectedFail;
use Test\DIClasses\InjectedLegacy;
use Test\DIClasses\Random;
use Test\DIClasses\Square;
use Test\DIClasses\SumAreas;

return [
    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),

    InjectedLegacy::class => DI::bind(InjectedLegacy::class)
        ->withInjectedLegacyConstructor()
        ->toInstance(),
];
