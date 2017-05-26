<?php

namespace ByJG\Config;

use ByJG\Config\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("The key '$id'' does not exists");
        }

        return $this->config[$id];
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
    public function has($id)
    {
        return isset($this->config[$id]);
    }

    /**
     * @param $id
     * @param $args
     * @return mixed
     */
    public function getClosure($id, $args = null)
    {
        $closure = $this->get($id);

        if (is_array($args)) {
            return call_user_func_array($closure, $args);
        }

        return call_user_func_array($closure, array_slice(func_get_args(), 1));
    }
}
