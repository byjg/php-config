<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Tests\DIClasses\Random;
use Tests\DIClasses\TestParam;

return [
    TestParam::class => DI::bind(TestParam::class)
        ->withConstructorArgs([Param::get(Random::class)])
        ->withMethodCall('someMethod', [Param::get(Random::class)])
        ->toInstance(),
];
