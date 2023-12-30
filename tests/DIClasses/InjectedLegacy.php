<?php


namespace Test\DIClasses;

class InjectedLegacy implements Area
{
    protected $triangle;
    protected $random;

    /**
     * SumAreas constructor.
     * @param Test\DIClasses\Area $area
     * @param Test\DIClasses\Random $random
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