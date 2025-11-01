<?php

namespace Tests\DIClasses;

class UnionTypeClass1
{
    private string $name;

    public function __construct()
    {
        $this->name = "Class1";
    }

    public function getName(): string
    {
        return $this->name;
    }
}
