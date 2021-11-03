<?php


namespace ByJG\Config;

use ByJG\Config\Exception\DependencyInjectionException;
use ByJG\Config\Exception\KeyNotFoundException;
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

    protected $factory = null;

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
     * @throws KeyNotFoundException
     */
    protected function getArgs()
    {
            return array_map(function ($value) {
                if ($value instanceof Param) {
                    try {
                        return $this->containerInterface->get($value->getParam());
                    } catch (KeyNotFoundException $ex) {
                        throw new KeyNotFoundException($ex->getMessage() . " injected from '" . $this->getClass() . "'");
                    }
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
     * @param mixed $args
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public function withFactoryMethod($method, $args = [])
    {
        if (!is_null($args) && !is_array($args)) {
            throw new DependencyInjectionException("Arguments should be an array");
        }
        $this->args = $args;

        $this->factory = $method;

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
        $params = $reflection->getParameters();

        if (count($params) > 0) {
            $args = [];
            foreach ($params as $param) {
                $type = $param->getType();
                if (is_null($type)) {
                    throw new DependencyInjectionException("The parameter '$" . $param->getName() . "' has no type defined in class '" . $this->getClass() . "'");
                }
                if (method_exists($type, "getName")) {
                    $args[] = Param::get(ltrim($type->getName(), "\\"));
                } else {
                    $args[] = Param::get(ltrim($type, "\\"));
                }
            }
            return $this->withConstructorArgs($args);
        }

        return $this->withNoConstructor();
    }

    /**
     * @return DependencyInjection
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function withInjectedLegacyConstructor()
    {
        $reflection = new ReflectionMethod($this->getClass(), "__construct");

        $docComments = str_replace("\n", " ", $reflection->getDocComment());

        $methodParams = $reflection->getParameters();

        $params = [];
        $result = preg_match_all('/@param\s+([\d\w_\\\\]+)\s+\$[\w_\d]+/', $docComments, $params);

        if (count($methodParams) <> $result) {
            throw new DependencyInjectionException("The class " . $this->getClass() . " does not have annotations with the param type.");
        }

        if (count($methodParams) > 0) {
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
        $this->methodCall[] = [$method, $args];
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
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function toEagerSingleton()
    {
        $this->singleton = true;
        $this->getInstance();
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
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function getInstance()
    {
        $instance = $this->getInternalInstance();

        if (is_null($instance)) {
            throw new DependencyInjectionException("Could not get a instance of " . $this->getClass());
        }

        return $instance;
    }

    /**
     * @return object
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    protected function getInternalInstance()
    {
        if ($this->singleton) {
            return $this->getSingletonInstace();
        }

        return $this->getNewInstance();
    }

    /**
     * @return object
     * @throws KeyNotFoundException
     * @throws ReflectionException
     */
    protected function getNewInstance()
    {
        if (!empty($this->factory)) {
            return $this->callMethods(call_user_func_array([$this->getClass(), $this->factory], $this->getArgs()));
        }

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
        foreach ($this->methodCall as $methodDefinition) {
            if (is_null($methodDefinition[1])) {
                call_user_func([$instance, $methodDefinition[0]]);
            } else {
                call_user_func_array([$instance, $methodDefinition[0]], $methodDefinition[1]);
            }
        }

        return $instance;
    }

    /**
     * @return object
     * @throws KeyNotFoundException
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
