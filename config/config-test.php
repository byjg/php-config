<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use Test\DIClasses\Area;
use Test\DIClasses\RectangleTriangle;

return [
    'property1' => 'string',
    'property2' => true,
    'property3' => function () {
        return 'calculated';
    },
    'property5' => 'test',

    'property6' => function () {
        $x = new Test\DIClasses\Square(2);
        return $x->calculate();
    },

    Area::class => DI::bind(RectangleTriangle::class)
        ->withConstructorArgs([3, Param::get('property6')])
        ->toInstance(),

];
