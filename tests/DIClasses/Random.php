<?php


namespace DIClasses;

class Random implements Area
{
    protected $random;

    public function __construct()
    {
        $this->random = rand(0, 200000);
    }

    public function setFixedNumber($value)
    {
        $this->random = $value;
    }

    public function calculate()
    {
        return $this->random;
    }

    public static function factory()
    {
        return new Random();
    }
}