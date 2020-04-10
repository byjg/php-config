<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use DIClasses\Random;
use DIClasses\SumAreas;

return [
    Random::class => DI::bindToSingletonInstance(Random::class),

    SumAreas::class => DI::bindToInstance(SumAreas::class, [Param::get(Random::class), param::get("control")]),

    "control" => DI::bindToInstance(Random::class),
];
