<?php

use ByJG\Config\DependencyInjection as DI;
use Test\DIClasses\Random;

return [
    Random::class => DI::bind(Random::class)
        ->withMethodCall("setFixedNumber", [10])
        ->toInstance(),

    "factory" => DI::bind(Random::class)
        ->withFactoryMethod("factory")
        ->withMethodCall("setFixedNumber", [20])
        ->toInstance(),

    "random2" => DI::bind(Random::class)
        ->withMethodCall("setFixedNumber", [30])
        ->toInstance(),

    "factory2" => DI::bind(Random::class)
        ->withFactoryMethod("factory")
        ->withMethodCall("setFixedNumber", [40])
        ->toSingleton(),

];
