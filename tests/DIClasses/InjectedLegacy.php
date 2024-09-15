<?php


namespace Tests\DIClasses;

class InjectedLegacy implements Area
{
    protected $triangle;
    protected $random;

    /**
     * SumAreas constructor.
     * @param Tests\DIClasses\Area $area
     * @param Tests\DIClasses\Random $random
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