<?php


namespace DIClasses;

use Area;

class Random implements Area
{
    protected $random;

    public function __construct()
    {
        $this->random = rand(0, 200000);
    }

    public function calculate()
    {
        return $this->random;
    }
}