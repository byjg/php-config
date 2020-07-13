<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use DIClasses\InjectedFail;
use DIClasses\Random;
use DIClasses\Square;

return [

    InjectedFail::class => DI::bind(InjectedFail::class)
        ->withInjectedLegacyConstructor()
        ->toInstance(),
];
