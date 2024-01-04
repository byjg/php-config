<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Test\DIClasses\Area;
use Test\DIClasses\Random;
use Test\DIClasses\Square;
use Test\DIClasses\SumAreas;

return [
    Random::class => DI::bind(Random::class)->toSingleton(),

    "control" => DI::bind(Random::class)->toInstance(),
];
