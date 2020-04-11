<?php


namespace DIClasses;

require_once __DIR__ . "/Area.php";

class SumAreas implements Area
{
    protected $triangle;
    protected $square;

    /**
     * SumAreas constructor.
     * @param \DIClasses\RectangleTriangle $triangle
     * @param \DIClasses\Square $square
     */
    public function __construct(RectangleTriangle $triangle, Square $square)
    {
        $this->triangle = $triangle;
        $this->square = $square;
    }

    public function calculate()
    {
        return $this->square->calculate() + $this->triangle->calculate();
    }
}