<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use DIClasses\Random;
use DIClasses\SumAreas;

return [
    Random::class => DI::bind(Random::class)->toSingleton(),

    SumAreas::class => DI::bind(SumAreas::class)
        ->withConstructorArgs([Param::get(Random::class), param::get("control")])
        ->toInstance(),

    "control" => DI::bind(Random::class)->toInstance(),
];
