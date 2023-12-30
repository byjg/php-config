<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Test\DIClasses\InjectedFail;
use Test\DIClasses\Random;
use Test\DIClasses\Square;

return [

    InjectedFail::class => DI::bind(InjectedFail::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
