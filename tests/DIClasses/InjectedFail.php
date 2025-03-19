<?php


namespace Tests\DIClasses;

use Override;

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

    #[Override]
    public function calculate(): float|int
    {
        return $this->random->getNumber() * $this->triangle->calculate();
    }
}