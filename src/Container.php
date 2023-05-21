<?php

namespace ByJG\Config;

use ByJG\Config\Exception\KeyNotFoundException;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionException;

class Container implements ContainerInterface
{
    private $config = [];

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

        if ($value instanceof DependencyInjection) {
            $value->injectContainer($this);
            return $value->getInstance();
        }

        if (!($value instanceof Closure)) {
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
}
