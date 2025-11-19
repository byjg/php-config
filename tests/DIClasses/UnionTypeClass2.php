<?php

namespace Tests\DIClasses;

class UnionTypeClass2
{
    private string $name;

    public function __construct()
    {
        $this->name = "Class2";
    }

    public function getName(): string
    {
        return $this->name;
    }
}
