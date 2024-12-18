<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Tests\DIClasses\Area;
use Tests\DIClasses\RectangleTriangle;

return [
    'property1' => 'string',
    'property2' => true,
    'property3' => function () {
        return 'calculated';
    },
    'property5' => 'test',

    'property6' => function () {
        $x = new Tests\DIClasses\Square(2);
        return $x->calculate();
    },

    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, Param::get('property6')])
        ->toInstance(),

];
