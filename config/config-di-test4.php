<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use DIClasses\Random;
use DIClasses\Square;
use DIClasses\TestParam;

return [

    "constnumber" => 4,

    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([Param::get("constnumber")])
        ->toInstance(),

    Random::class => DI::bind(Random::class),

    TestParam::class => DI::bind(TestParam::class)
        ->withConstructorArgs([Param::get(Random::class)])
        ->withMethodCall('testCall', [Param::get(Random::class)])
];
