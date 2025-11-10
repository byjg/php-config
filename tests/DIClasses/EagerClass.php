<?php

namespace Tests\DIClasses;

class EagerClass
{
    protected static Area $area;
    public static function initialize(Area $area)
    {
        self::$area = $area;
    }

    public static function getArea(): Area
    {
        return self::$area;
    }
}