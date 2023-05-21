<?php


namespace DIClasses;

use Exception;

require_once __DIR__ . "/Random.php";

class TestParam
{
    protected $obj1 = null;
    protected $obj2 = null;

    public function __construct($random)
    {
        if (!($random instanceof Random)) {
            throw new Exception("Constructor expected Random class. Got " . get_class($random) . " instead");
        }
        $this->obj1 = $random;
    }

    public function someMethod($random)
    {
        if (!($random instanceof Random)) {
            throw new Exception("someMethod expected Random class. Got " . get_class($random) . " instead");
        }
        $this->obj2 = $random;
        return $this;
    }

    public function isOk()
    {
        return ($this->obj1 instanceof Random) && ($this->obj2 instanceof Random);
    }
}

