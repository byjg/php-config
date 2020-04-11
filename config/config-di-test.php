<?php

use ByJG\Config\DependencyInjection as DI;
use DIClasses\Area;
use DIClasses\Random;
use DIClasses\RectangleTriangle;
use DIClasses\Square;
use DIClasses\SumAreas;

return [
    Random::class => DI::bind(Random::class)
        ->withConstructorArgs([4])
        ->toInstance(),

    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),

    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
