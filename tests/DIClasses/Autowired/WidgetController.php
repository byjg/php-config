<?php

namespace Tests\DIClasses\Autowired;

use Tests\DIClasses\Area;

/**
 * A terminal class with a dependency: only the container can build it.
 */
class WidgetController
{
    public function __construct(protected Area $area)
    {
    }

    public function calculate(): int
    {
        return $this->area->calculate();
    }
}
