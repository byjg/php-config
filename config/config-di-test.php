<?php

use ByJG\Config\DependencyInjection as DI;
use DIClasses\RectangleTriangle;
use DIClasses\Square;
use DIClasses\SumAreas;

return [
    Square::class => DI::bindToInstance(Square::class, [4]),

    RectangleTriangle::class => DI::bindToInstance(RectangleTriangle::class, [3, 4]),

    SumAreas::class => DI::bindToConstructor(SumAreas::class),
];
