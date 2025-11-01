<?php

use ByJG\Config\DependencyInjection as DI;
use Tests\DIClasses\ClassWithUnionType;
use Tests\DIClasses\ClassWithUnionType2;
use Tests\DIClasses\UnionTypeClass1;
use Tests\DIClasses\UnionTypeClass2;

return [
    UnionTypeClass1::class => DI::bind(UnionTypeClass1::class)
        ->withConstructorNoArgs()
        ->toSingleton(),

    UnionTypeClass2::class => DI::bind(UnionTypeClass2::class)
        ->withConstructorNoArgs()
        ->toSingleton(),

    ClassWithUnionType::class => DI::bind(ClassWithUnionType::class)
        ->withInjectedConstructor()
        ->toInstance(),

    ClassWithUnionType2::class => DI::bind(ClassWithUnionType2::class)
        ->withInjectedConstructor()
        ->toInstance()
];
