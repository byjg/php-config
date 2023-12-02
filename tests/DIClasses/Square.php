<?php


namespace DIClasses;

require_once __DIR__ . "/Area.php";

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