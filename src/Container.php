<?php

namespace ByJG\Config;

use ByJG\Config\Exception\NotFoundException;
use Psr\Container\ContainerInterface;

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
     * @throws \ByJG\Config\Exception\NotFoundException
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException("The key '$id'' does not exists");
        }

        $value = $this->config[$id];

        if (!($value instanceof \Closure)) {
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
    public function has($id)
    {
        return isset($this->config[$id]);
    }
}
