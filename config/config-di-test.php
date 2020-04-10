<?php

use ByJG\Config\DependencyInjection as DI;
use DIClasses\RectangleTriangle;
use DIClasses\Square;
use DIClasses\SumAreas;

return [
    Square::class => DI::bind(Square::class)
        ->withArgs([4])
        ->toInstance(),

    RectangleTriangle::class => DI::bind(RectangleTriangle::class)
        ->withArgs([3, 4])
        ->toInstance(),

    SumAreas::class => DI::bind(SumAreas::class)
        ->withConstructor()
        ->toInstance(),
];
