<?php


namespace DIClasses;

class Random
{
    protected $random;

    public function __construct($value = 0)
    {
        if ($value == 0) {
            $this->random = rand(0, 200000);
        } else {
            $this->setFixedNumber($value);
        }
    }

    public function setFixedNumber($value)
    {
        $this->random = $value;
    }

    public function getNumber()
    {
        return $this->random;
    }

    public static function factory()
    {
        return new Random();
    }
}