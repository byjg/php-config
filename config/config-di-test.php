<?php

use ByJG\Config\DependencyInjection as DI;
use DIClasses\RectangleTriangle;
use DIClasses\Square;
use DIClasses\SumAreas;

return [
    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([4])
        ->toInstance(),

    RectangleTriangle::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),

    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
