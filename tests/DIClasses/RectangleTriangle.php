<?php


namespace Tests\DIClasses;

use Override;

class RectangleTriangle implements Area
{
    protected $base;
    protected $height;

    public function __construct($base, $height)
    {
        $this->base = $base;
        $this->height = $height;
    }

    #[Override]
    public function calculate(): float|int
    {
        return ($this->base * $this->height) / 2;
    }

}