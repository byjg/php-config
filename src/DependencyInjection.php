<?php


namespace ByJG\Config;

use ByJG\Config\Exception\DependencyInjectionException;
use Psr\Container\ContainerInterface;

class DependencyInjection
{
    const TO_INSTANCE=1;
    const SINGLETON=2;

    /**
     * @var ContainerInterface
     */
    protected $containerInterface;

    protected $bindType;

    protected $class;

    protected $args;

    protected $instance;

    /**
     * @param $containerInterface ContainerInterface
     * @return DependencyInjection
     */
    public function injectContainer($containerInterface)
    {
        $this->containerInterface = $containerInterface;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBindType()
    {
        return $this->bindType;
    }

    /**
     * @param mixed $bindType
     */
    public function setBindType($bindType)
    {
        $this->bindType = $bindType;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     * @throws DependencyInjectionException
     */
    public function setClass($class)
    {
        if (!class_exists($class)) {
            throw new DependencyInjectionException("Class $class does not exists");
        }
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $args
     * @throws DependencyInjectionException
     */
    public function setArgs($args)
    {
        if (!is_null($args) && !is_array($args)) {
            throw new DependencyInjectionException("Arguments should be an array");
        }
        $this->args = $args;
    }


    /**
     * @return ContainerInterface
     */
    public function container() {
        return $this->containerInterface;
    }

    /**
     * DependencyInjection constructor.
     * @param $class
     * @param $type
     * @param $args
     * @throws DependencyInjectionException
     */
    protected function __construct($class, $type, $args)
    {
        $this->setArgs($args);
        $this->setClass($class);
        $this->setBindType($type);
    }

    /**
     * @param $class
     * @param $args
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function bindToInstance($class, $args = null)
    {
        return new DependencyInjection($class, DependencyInjection::TO_INSTANCE, $args);
    }

    /**
     * @param $class
     * @param $args
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function bindToSingletonInstance($class, $args)
    {
        return new DependencyInjection($class, DependencyInjection::TO_INSTANCE | DependencyInjection::SINGLETON, $args);
    }

    public function getInstance()
    {
        if ($this->getBindType() & DependencyInjection::SINGLETON) {
            return $this->getSingletonInstace();
        }

        return $this->getNewInstance();

    }

    protected function getNewInstance()
    {
        $reflectionClass = new \ReflectionClass($this->getClass());

        if (is_null($this->args)) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        return $reflectionClass->newInstanceArgs($this->getArgs());
    }

    protected function getSingletonInstace()
    {
        if (empty($this->instance)) {
            $this->instance = $this->getNewInstance();
        }
        return $this->instance;
    }
}