<?php

use ByJG\Config\DependencyInjection as DI;
use DIClasses\Random;

return [
    Random::class => DI::bindToSingletonInstance(Random::class),

//    SumAreas::class => DI::bindToInstance(SumAreas::class, [DI::get(Random::class), DI::get("control")]),


    "control" => DI::bindToInstance(Random::class),
];
