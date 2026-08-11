<?php

namespace Tests\DIClasses\Autowired;

/**
 * Matches the pattern but is also bound explicitly, to prove the explicit binding wins.
 */
class OverriddenController
{
    public function __construct(protected string $label = 'from-pattern')
    {
    }

    public function label(): string
    {
        return $this->label;
    }
}
