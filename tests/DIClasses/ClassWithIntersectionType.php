<?php

namespace Tests\DIClasses;

use ArrayAccess;
use Countable;

/**
 * An intersection type has no single class name to look up in the container, so this
 * class exists to pin down how auto-wiring must react to one.
 */
class ClassWithIntersectionType
{
    private Countable&ArrayAccess $dependency;

    public function __construct(Countable&ArrayAccess $dependency)
    {
        $this->dependency = $dependency;
    }

    public function countItems(): int
    {
        return count($this->dependency);
    }
}
