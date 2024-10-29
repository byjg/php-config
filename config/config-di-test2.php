<?php

use ByJG\Config\DependencyInjection as DI;
use Tests\DIClasses\Random;

return [
    Random::class => DI::bind(Random::class)->toSingleton(),

    "control" => DI::bind(Random::class)->toInstance(),
];
