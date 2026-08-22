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
     * A marker that resolves to the Container itself, for services that need to resolve
     * dependencies on their own:
     *
     *     ->withMethodCall('withContainer', [Param::container()])
     *
     * @see ContainerParam
     */
    public static function container(): ContainerParam
    {
        return new ContainerParam();
    }

    /**
     * @return mixed
     */
    public function getParam(): string
    {
        return $this->param;
    }
}
