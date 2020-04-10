<?php


namespace ByJG\Config;


class Param
{
    protected $param;

    /**
     * Param constructor.
     * @param $param
     */
    protected function __construct($param)
    {
        $this->param = $param;
    }

    public static function get($param)
    {
        return new Param($param);
    }

    /**
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }
}
