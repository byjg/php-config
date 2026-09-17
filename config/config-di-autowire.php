<?php

use ByJG\Config\Autowire;
use ByJG\Config\DependencyInjection as DI;
use Tests\DIClasses\Area;
use Tests\DIClasses\Autowired\OverriddenController;
use Tests\DIClasses\RectangleTriangle;

return [
    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),

    // One rule instead of one entry per controller.
    'Tests\DIClasses\Autowired\*' => Autowire::rule()
        ->withInjectedConstructor()
        ->toInstance(),

    // An explicit binding still wins over the pattern.
    OverriddenController::class => DI::bind(OverriddenController::class)
        ->withConstructorArgs(['explicit'])
        ->toInstance(),
];
