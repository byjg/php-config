<?php


namespace ByJG\Config;

use ByJG\Config\Exception\DependencyInjectionException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class DependencyInjection
{
    /**
     * @var ContainerInterface
     */
    protected $containerInterface;

    protected $class;

    protected $args = [];

    protected $instance;

    protected $singleton = false;

    protected $methodCall = [];

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
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public function withConstructorArgs($args)
    {
        if (!is_null($args) && !is_array($args)) {
            throw new DependencyInjectionException("Arguments should be an array");
        }
        $this->args = $args;

        return $this;
    }

    /**
     * DependencyInjection constructor.
     * @param $class
     * @throws DependencyInjectionException
     */
    protected function __construct($class)
    {
        $this->setClass($class);
    }

    /**
     * @param $class
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function bind($class)
    {
        return new DependencyInjection($class);
    }

    /**
     * @return DependencyInjection
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function withInjectedConstructor()
    {
        $reflection = new ReflectionMethod($this->getClass(), "__construct");

        $docComments = str_replace("\n", " ", $reflection->getDocComment());

        $params = [];
        $result = preg_match_all('/@param\s+\$[\w_\d]+\s+([\d\w_\\\\]+)/', $docComments, $params);

        if ($result) {
            $args = [];
            foreach ($params[1] as $param) {
                $args[] = Param::get(ltrim($param, "\\"));
            }
            return $this->withConstructorArgs($args);
        }

        return $this->withNoConstructor();
    }

    /**
     * @return DependencyInjection
     */
    public function withNoConstructor()
    {
        $this->args = null;
        return $this;
    }

    public function withMethodCall($method, $args = [])
    {
        $this->methodCall[$method] = $args;
        return $this;
    }

    /**
     * @return DependencyInjection
     */
    public function toSingleton()
    {
        $this->singleton = true;
        return $this;
    }

    /**
     * @return DependencyInjection
     */
    public function toInstance()
    {
        $this->singleton = false;
        return $this;
    }

    /**
     * @return object
     * @throws ReflectionException
     */
    public function getInstance()
    {
        if ($this->singleton) {
            return $this->getSingletonInstace();
        }

        return $this->getNewInstance();

    }

    /**
     * @return object
     * @throws ReflectionException
     */
    protected function getNewInstance()
    {
        $reflectionClass = new ReflectionClass($this->getClass());

        if (is_null($this->args)) {
            return $this->callMethods($reflectionClass->newInstanceWithoutConstructor());
        }

        return $this->callMethods($reflectionClass->newInstanceArgs($this->getArgs()));
    }

    /**
     * @param $instance
     * @return mixed
     */
    protected function callMethods($instance)
    {
        foreach ($this->methodCall as $methodName => $args) {
            if (is_null($args)) {
                call_user_func([$instance, $methodName]);
            } else {
                call_user_func_array([$instance, $methodName], $args);
            }
        }

        return $instance;
    }

    /**
     * @return object
     * @throws ReflectionException
     */
    protected function getSingletonInstace()
    {
        if (empty($this->instance)) {
            $this->instance = $this->getNewInstance();
        }
        return $this->instance;
    }
}
