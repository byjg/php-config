<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigException;
use Psr\SimpleCache\CacheInterface;

class Environment
{
    protected string $name;

    protected array $inheritFrom;

    protected bool $abstract;

    protected bool $final;

    protected ?CacheInterface $cache;

    public function __construct(string $name, array $inheritFrom = [], ?CacheInterface $cache = null, bool $abstract = false, $final = false)
    {
        $this->name = $name;
        $this->abstract = $abstract;
        $this->final = $final;
        $this->inheritFrom = [];
        $this->cache = $cache;

        foreach ($inheritFrom as $item) {
            if (!($item instanceof Environment)) {
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

    public function getCacheInterface(): ?CacheInterface
    {
        return $this->cache;
    }
}