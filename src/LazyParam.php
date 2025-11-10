<?php

namespace ByJG\Config;

class LazyParam extends Param
{
    protected string $typeHint;

    protected function __construct(string $param, ?string $typeHint = null)
    {
        parent::__construct($param);
        $this->typeHint = $typeHint ?? $param;
    }

    public static function get(string $param, ?string $typeHint = null): LazyParam
    {
        return new LazyParam($param, $typeHint);
    }

    public function getTypeHint(): string
    {
        return $this->typeHint;
    }
}
