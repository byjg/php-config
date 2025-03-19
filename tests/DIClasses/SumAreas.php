<?php


namespace Tests\DIClasses;

use Override;

class SumAreas implements Area
{
    protected $triangle;
    protected $random;

    /**
     * SumAreas constructor.
     * @param Area $area
     * @param Random $random
     */
    public function __construct(Area $area, Random $random)
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