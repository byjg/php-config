<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use DIClasses\Area;
use DIClasses\Random;
use DIClasses\Square;
use DIClasses\SumAreas;

return [
    Random::class => DI::bind(Random::class)->toSingleton(),

    "control" => DI::bind(Random::class)->toInstance(),
];
