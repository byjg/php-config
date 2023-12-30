<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigException;

class Config
{
    protected string $name;

    protected array $inheritFrom;

    protected bool $abstract;

    protected bool $final;

    public function __construct(string $name, array $inheritFrom = [], bool $abstract = false, $final = false)
    {
        $this->name = $name;
        $this->abstract = $abstract;
        $this->final = $final;
        $this->inheritFrom = [];

        foreach ($inheritFrom as $item) {
            if (!($item instanceof Config)) {
                throw new ConfigException("The item '$item' is not a Config object");
            }
            if ($item->isFinal()) {
                $name = $item->getName();
                throw new ConfigException("The item '$name' is final and cannot be inherited");
            }
            $this->inheritFrom[] = $item;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInheritFrom(): array
    {
        return $this->inheritFrom;
    }

    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }
}