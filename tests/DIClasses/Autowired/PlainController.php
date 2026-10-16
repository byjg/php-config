<?php

namespace Tests\DIClasses\Autowired;

/**
 * Declares no constructor — withInjectedConstructor() would fail on this, so the rule
 * has to degrade by itself.
 */
class PlainController
{
    public function hello(): string
    {
        return 'hello';
    }
}
