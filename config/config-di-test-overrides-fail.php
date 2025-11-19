<?php

use ByJG\Config\DependencyInjection as DI;
use Tests\DIClasses\MixedDependencies;

return [
    // Test: Missing required built-in type override (should fail without apiKey)
    'mixed-fail' => DI::bind(MixedDependencies::class)
        ->withInjectedConstructorOverrides([])
        ->toInstance(),
];
