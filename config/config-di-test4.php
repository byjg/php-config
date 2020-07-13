<?php

use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\Param;
use DIClasses\Random;
use DIClasses\Square;

return [

    "constnumber" => 4,

    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([Param::get("constnumber")])
        ->toInstance(),
];
