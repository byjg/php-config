<?php


namespace DIClasses;

use Area;

require_once __DIR__ . "/Area.php";

class Square implements Area
{
    protected $side;

    public function __construct($side)
    {
        $this->side = $side;
    }

    public function calculate()
    {
        return $this->side * $this->side;
    }
}