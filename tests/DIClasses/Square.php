<?php


namespace Test\DIClasses;

class Square implements Area
{
    protected $side;

    public function __construct(int $side)
    {
        $this->side = $side;
    }

    public function calculate()
    {
        return $this->side * $this->side;
    }
}