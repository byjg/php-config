<?php

namespace Tests\DIClasses;

class ClassWithUnionType
{
    private UnionTypeClass1|UnionTypeClass2 $dependency;

    public function __construct(UnionTypeClass1|UnionTypeClass2 $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getDependencyName(): string
    {
        return $this->dependency->getName();
    }
}
