<?php

use ByJG\Config\DependencyInjection as DI;
use Tests\DIClasses\InjectedFail;

return [

    InjectedFail::class => DI::bind(InjectedFail::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
