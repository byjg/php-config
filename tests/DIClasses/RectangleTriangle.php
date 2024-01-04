<?php


namespace Test\DIClasses;

class RectangleTriangle implements Area
{
    protected $base;
    protected $height;

    public function __construct($base, $height)
    {
        $this->base = $base;
        $this->height = $height;
    }

    public function calculate()
    {
        return ($this->base * $this->height) / 2;
    }

}