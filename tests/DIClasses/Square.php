<?php


namespace Tests\DIClasses;

use Override;

class Square implements Area
{
    protected $side;

    public function __construct(int $side)
    {
        $this->side = $side;
    }

    #[Override]
    public function calculate(): float|int
    {
        return $this->side * $this->side;
    }
}