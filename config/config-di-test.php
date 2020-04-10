<?php

use ByJG\Config\DependencyInjection;
use DIClasses\RectangleTriangle;
use DIClasses\Square;

return [
    Square::class => DependencyInjection::bindToInstance(Square::class, [4]),

    RectangleTriangle::class => DependencyInjection::bindToInstance(RectangleTriangle::class, [3, 4]),
];
