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

    protected $use = false;

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
    protected function getArgs($argsToParse = null)
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
            }, is_null($argsToParse) ? $this->args : $argsToParse);
    }

    /**
     * @param mixed $args
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public function withConstructorArgs($args)
    {
        if ($this->use) {
            throw new DependencyInjection('You cannot set constructor on a already set object (DI::use())');
        }

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
        if ($this->use) {
            throw new DependencyInjection('You cannot set constructor on a already set object (DI::use())');
        }

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
    protected function __construct($class, $use = false)
    {
        if ($use) {
            $this->class = Param::get($class);
            $this->use = true;
        } else {
            $this->setClass($class);
        }
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
     * @param $class
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function use($class)
    {
        return new DependencyInjection($class, true);
    }

    /**
     * @return DependencyInjection
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function withInjectedConstructor()
    {
        if ($this->use) {
            throw new DependencyInjection('You cannot set constructor on a already set object (DI::use())');
        }

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
        if ($this->use) {
            throw new DependencyInjection('You cannot set constructor on a already set object (DI::use())');
        }

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
        if ($this->use) {
            throw new DependencyInjection('You cannot set constructor on a already set object (DI::use())');
        }

        $this->args = null;
        return $this;
    }

    /**
     * @return DependencyInjection
     */
    public function withConstructorNoArgs()
    {
        if ($this->use) {
            throw new DependencyInjection('You cannot set constructor on a already set object (DI::use())');
        }

        $this->args = [];
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
        if ($this->use) {
            throw new DependencyInjection('You cannot get a singleton over an existent object (DI::use())');
        }
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
        if ($this->use) {
            throw new DependencyInjection('You cannot get an eager singleton over an existent object (DI::use()');
        }

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

        return $this->callMethods($instance, !$this->use);
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
        if ($this->use) {
            return $this->getArgs([$this->class])[0];
        }

        if (!empty($this->factory)) {
            return call_user_func_array([$this->getClass(), $this->factory], $this->getArgs());
        }

        $reflectionClass = new ReflectionClass($this->getClass());

        if (is_null($this->args)) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        return $reflectionClass->newInstanceArgs($this->getArgs());
    }

    /**
     * @param $instance
     * @return mixed
     */
    protected function callMethods($instance, $returnInstance = true)
    {
        $result = null;
        foreach ($this->methodCall as $methodDefinition) {
            if (is_null($methodDefinition[1])) {
                $result = call_user_func([$instance, $methodDefinition[0]]);
            } else {
                $result = call_user_func_array([$instance, $methodDefinition[0]], $this->getArgs($methodDefinition[1]));
            }
        }

        if (!$returnInstance) {
            return $result;
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
