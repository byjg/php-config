<?php


namespace ByJG\Config;


class Param
{
    protected string $param;

    /**
     * Param constructor.
     * @param string $param
     */
    protected function __construct(string $param)
    {
        $this->param = $param;
    }

    public static function get(string $param): Param
    {
        return new Param($param);
    }

    /**
     * @return mixed
     */
    public function getParam(): string
    {
        return $this->param;
    }
}
