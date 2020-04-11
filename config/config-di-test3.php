<?php

use ByJG\Config\DependencyInjection as DI;
use DIClasses\Random;

return [
    Random::class => DI::bind(Random::class)
        ->withMethodCall("setFixedNumber", [10])
        ->toInstance(),
];
