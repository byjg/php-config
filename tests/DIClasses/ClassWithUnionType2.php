<?php

namespace Tests\DIClasses;

class ClassWithUnionType2
{
    private string|UnionTypeClass2 $dependency;

    public function __construct(string|UnionTypeClass2 $dependency)
    {
        if (is_string($dependency)) {
            $dependency = new $dependency;
        }
        $this->dependency = $dependency;
    }

    public function getDependencyName(): string
    {
        return $this->dependency->getName();
    }
}
