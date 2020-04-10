<?php


namespace DIClasses;

use Area;

require_once __DIR__ . "/Area.php";

class SumAreas implements Area
{
    protected $triangle;
    protected $square;

    /**
     * SumAreas constructor.
     * @param $triangle \DIClasses\RectangleTriangle
     * @param $square \DIClasses\Square
     */
    public function __construct($triangle, $square)
    {
        $this->triangle = $triangle;
        $this->square = $square;
    }

    public function calculate()
    {
        return $this->square->calculate() + $this->triangle->calculate();
    }
}