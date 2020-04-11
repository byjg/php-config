<?php


namespace DIClasses;

require_once __DIR__ . "/Area.php";

class SumAreas implements Area
{
    protected $triangle;
    protected $random;

    /**
     * SumAreas constructor.
     * @param \DIClasses\Area $area
     * @param \DIClasses\Random $random
     */
    public function __construct(Area $area, Random $random)
    {
        $this->triangle = $area;
        $this->random = $random;
    }

    public function calculate()
    {
        return $this->random->getNumber() * $this->triangle->calculate();
    }
}