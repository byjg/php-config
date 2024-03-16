<?php

use ByJG\Config\DependencyInjection as DI;
use Test\DIClasses\InjectedFail;

return [

    InjectedFail::class => DI::bind(InjectedFail::class)
        ->withInjectedLegacyConstructor()
        ->toInstance(),
];
