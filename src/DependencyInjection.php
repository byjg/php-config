<?php


namespace ByJG\Config;

use ByJG\Config\Exception\DependencyInjectionException;
use Psr\Container\ContainerInterface;

class DependencyInjection
{
    const TO_INSTANCE=1;
    const SINGLETON=2;
    const TO_CONSTRUCTOR=4;

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
    protected function getBindType()
    {
        return $this->bindType;
    }

    /**
     * @param mixed $bindType
     */
    protected function setBindType($bindType)
    {
        $this->bindType = $bindType;
    }

    /**
     * @return mixed
     */
    protected function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     * @throws DependencyInjectionException
     */
    protected function setClass($class)
    {
        if (!class_exists($class)) {
            throw new DependencyInjectionException("Class $class does not exists");
        }
        $this->class = $class;
    }

    /**
     * @return mixed
     */
    protected function getArgs()
    {
        return array_map(function ($value) {
            if ($value instanceof Param) {
                return $this->containerInterface->get($value->getParam());
            }
            return $value;
        }, $this->args);
    }

    /**
     * @param mixed $args
     * @throws DependencyInjectionException
     */
    protected function setArgs($args)
    {
        if (!is_null($args) && !is_array($args)) {
            throw new DependencyInjectionException("Arguments should be an array");
        }
        $this->args = $args;
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
    public static function bindToInstance($class, $args = [])
    {
        return new DependencyInjection($class, DependencyInjection::TO_INSTANCE, $args);
    }

    /**
     * @param $class
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function bindToConstructor($class)
    {
        return new DependencyInjection($class, DependencyInjection::TO_CONSTRUCTOR, []);
    }

    /**
     * @param $class
     * @param $args
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function bindToSingletonInstance($class, $args = [])
    {
        return new DependencyInjection($class, DependencyInjection::TO_INSTANCE | DependencyInjection::SINGLETON, $args);
    }

    public function getInstance()
    {
        if ($this->getBindType() & DependencyInjection::TO_CONSTRUCTOR) {
            return $this->getNewInstanceConstructor();
        }
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

    /**
     * @throws \ReflectionException
     */
    public function getNewInstanceConstructor()
    {
        $reflection = new \ReflectionMethod($this->getClass(), "__construct");

        $docComments = str_replace("\n", " ", $reflection->getDocComment());

        $params = [];
        $result = preg_match_all('/@param\s+\$[\w_\d]+\s+([\d\w_\\\\]+)/', $docComments, $params);

        $reflectionClass = new \ReflectionClass($this->getClass());
        if (!$result || empty($params)) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        $args = [];
        foreach ($params[1] as $param) {
            $args[] = $this->containerInterface->get(ltrim($param, "\\"));
        }

        return $reflectionClass->newInstanceArgs($args);
    }
}