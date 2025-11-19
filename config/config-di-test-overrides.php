<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Tests\DIClasses\Area;
use Tests\DIClasses\MixedDependencies;
use Tests\DIClasses\Random;
use Tests\DIClasses\RectangleTriangle;

return [
    Random::class => DI::bind(Random::class)
        ->withConstructorArgs([42])
        ->toInstance(),

    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([5, 10])
        ->toInstance(),

    // Test 1: Override string parameter with literal value
    'mixed1' => DI::bind(MixedDependencies::class)
        ->withInjectedConstructorOverrides(['apiKey' => 'my-secret-key'])
        ->toInstance(),

    // Test 2: Override multiple parameters (string and int)
    'mixed2' => DI::bind(MixedDependencies::class)
        ->withInjectedConstructorOverrides([
            'apiKey' => 'another-key',
            'maxRetries' => 5
        ])
        ->toInstance(),

    // Test 3: Override with Param::get() to use different dependency
    'mixed3' => DI::bind(MixedDependencies::class)
        ->withInjectedConstructorOverrides([
            'apiKey' => 'test-key',
            'random' => Param::get('custom-random')
        ])
        ->toInstance(),

    'custom-random' => DI::bind(Random::class)
        ->withConstructorArgs([999])
        ->toInstance(),
];
