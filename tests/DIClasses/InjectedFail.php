<?php


namespace DIClasses;

require_once __DIR__ . "/Area.php";

class InjectedFail implements Area
{
    protected $triangle;
    protected $random;

    /**
     * SumAreas constructor.
     * @param $area
     * @param $random
     */
    public function __construct($area, $random)
    {
        $this->triangle = $area;
        $this->random = $random;
    }

    public function calculate()
    {
        return $this->random->getNumber() * $this->triangle->calculate();
    }
}