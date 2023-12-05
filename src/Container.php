<?php

namespace ByJG\Config;

use Laravel\SerializableClosure\SerializableClosure;
use ByJG\Config\Exception\KeyNotFoundException;
use Closure;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use ReflectionException;

class Container implements ContainerInterface
{
    private $config = [];

    private $processedEagers = false;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws Exception\DependencyInjectionException
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    public function get(string $id)
    {
        $value = $this->raw($id);

        if ($value instanceof Param) {
            return $this->get($value->getParam());
        }

        if ($value instanceof DependencyInjection) {
            $value->injectContainer($this);
            return $value->getInstance();
        }

        if (!($value instanceof Closure)) {
            if (is_string($value)) {
                if (substr($value, 0, 6) == "!!^^:") {
                    $value = unserialize(substr($value, 6))->getClosure();
                    $this->config[$id] = $value;
                    return $this->get($id);
                } else if (substr($value, 0, 6) == "!!##:") {
                    $value = unserialize(substr($value, 6));
                    $this->config[$id] = $value;
                    return $this->get($id);
                }
            }

            return $value;
        }

        $args = array_slice(func_get_args(), 1);

        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        if (empty($args)) {
            $args = [];
        }

        return call_user_func_array($value, $args);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->config[$id]);
    }

    /**
     * @param $id
     * @return mixed
     * @throws KeyNotFoundException
     */
    public function raw($id)
    {
        if (!$this->has($id)) {
            throw new KeyNotFoundException("The key '$id' does not exists");
        }

        return $this->config[$id];
    }

    public function saveToCache($definitionName, CacheInterface $cacheObject)
    {
        $toCache = [];
        foreach ($this->config as $key => $value) {
            if ($value instanceof Closure) {
                $toCache[$key] = "!!^^:" . serialize(new SerializableClosure($value));
            } else if ($value instanceof DependencyInjection) {
                $toCache[$key] = "!!##:" . serialize($value);
            } else {
                $toCache[$key] = $value;
            }
        }
        return $cacheObject->set("container-cache-$definitionName", serialize($toCache));
    }

    public static function createFromCache($definitionName, CacheInterface $cacheObject)
    {
        $fromCache = $cacheObject->get("container-cache-$definitionName");
        if (!is_null($fromCache)) {
            $fromCache = unserialize($fromCache);
            return new Container($fromCache);
        }

        return null;
    }

    public function compare(?Container $container)
    {
        if (is_null($container)) {
            return false;
        }

        // Compare recusively the raw config
        $diff = array_udiff_uassoc(
            $this->config,
            $container->config,
            function ($a, $b) {
                return 0;
            },
            function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return 1;
            }
        );

        return empty($diff);
    }

    public function processEagerSingleton()
    {
        if ($this->processedEagers) {
            return;
        }

        $this->processedEagers = true;

        foreach ($this->config as $key => $value) {
            if ($value instanceof DependencyInjection and $value->isEagerSingleton()) {
                $this->get($key);
            }
        }
    }
}
