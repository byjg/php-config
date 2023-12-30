<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Test\DIClasses\Random;
use Test\DIClasses\Square;
use Test\DIClasses\TestParam;

return [

    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([Param::get("constnumber")])
        ->toInstance(),

    Random::class => DI::bind(Random::class),

    TestParam::class => DI::bind(TestParam::class)
        ->withConstructorArgs([Param::get(Random::class)])
        ->withMethodCall('someMethod', [Param::get(Random::class)])
        ->toEagerSingleton()
];
