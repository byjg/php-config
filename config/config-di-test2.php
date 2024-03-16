<?php

use ByJG\Config\DependencyInjection as DI;
use Test\DIClasses\Random;

return [
    Random::class => DI::bind(Random::class)->toSingleton(),

    "control" => DI::bind(Random::class)->toInstance(),
];
