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

    protected CacheModeEnum $cacheMode;

    /**
     * @throws ConfigException
     */
    public function __construct(string $name, array $inheritFrom = [], ?CacheInterface $cache = null, bool $abstract = false, $final = false, CacheModeEnum $cacheMode = CacheModeEnum::multipleFiles)
    {
        $this->name = $name;
        $this->abstract = $abstract;
        $this->final = $final;
        $this->inheritFrom = [];
        $this->cache = $cache;
        $this->cacheMode = $cacheMode;

        if (!empty($inheritFrom)) {
            $this->inheritFrom(...$inheritFrom);
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

    public function getCacheMode(): CacheModeEnum
    {
        return $this->cacheMode;
    }

    /**
     * Static factory method to create a new Environment instance
     *
     * @param string $name The environment name
     * @return self
     */
    public static function create(string $name): self
    {
        return new self($name);
    }

    /**
     * Set which environments this environment should inherit from
     *
     * @param Environment ...$environments One or more Environment instances to inherit from
     * @return $this
     * @throws ConfigException If any environment is final
     */
    public function inheritFrom(Environment ...$environments): self
    {
        foreach ($environments as $environment) {
            if ($environment->isFinal()) {
                $name = $environment->getName();
                throw new ConfigException("The environment '$name' is final and cannot be inherited");
            }
            $this->inheritFrom[] = $environment;
        }
        return $this;
    }

    /**
     * Mark this environment as abstract (cannot be loaded directly, only inherited)
     *
     * @return $this
     */
    public function setAsAbstract(): self
    {
        $this->abstract = true;
        return $this;
    }

    /**
     * Mark this environment as final (cannot be inherited from)
     *
     * @return $this
     */
    public function setAsFinal(): self
    {
        $this->final = true;
        return $this;
    }

    /**
     * Configure caching for this environment
     *
     * @param CacheInterface $cache PSR-16 cache implementation
     * @param CacheModeEnum $mode Cache mode (singleFile or multipleFiles)
     * @return $this
     */
    public function withCache(CacheInterface $cache, CacheModeEnum $mode = CacheModeEnum::multipleFiles): self
    {
        $this->cache = $cache;
        $this->cacheMode = $mode;
        return $this;
    }
}