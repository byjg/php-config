<?php


namespace ByJG\Config;

use ByJG\Config\Exception\DependencyInjectionException;
use ByJG\Config\Exception\KeyNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class DependencyInjection
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $containerInterface;

    protected Param|string $class;

    protected ?array $args = [];

    protected ?object $instance = null;

    protected bool $singleton = false;

    protected bool $use = false;

    protected ?string $factory = null;

    protected array $methodCall = [];

    protected bool $eager = false;

    protected bool $processed = false;

    protected bool $delayedInstance = false;

    /**
     * @param $containerInterface ContainerInterface
     * @return $this
     */
    public function injectContainer(ContainerInterface $containerInterface): static
    {
        $this->containerInterface = $containerInterface;
        return $this;
    }

    /**
     * @return Param|string
     */
    protected function getClass(): Param|string
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     * @throws DependencyInjectionException
     */
    protected function setClass(Param|string $class): void
    {
        if (!class_exists($class)) {
            throw new DependencyInjectionException("Class $class does not exists");
        }
        $this->class = $class;
    }

    /**
     * @param array|null $argsToParse
     * @return array
     * @throws KeyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getArgs(?array $argsToParse = null): array
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
     * @return $this
     * @throws DependencyInjectionException
     */
    public function withConstructorArgs(array $args): static
    {
        if ($this->use) {
            throw new DependencyInjectionException('You cannot set constructor on a already set object (DI::use())');
        }

        $this->args = $args;

        return $this;
    }

    /**
     * @param string $method
     * @param mixed $args
     * @return $this
     * @throws DependencyInjectionException
     */
    public function withFactoryMethod(string $method, array $args = []): static
    {
        if ($this->use) {
            throw new DependencyInjectionException('You cannot set constructor on a already set object (DI::use())');
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
     * @param string $class
     * @param bool $use
     * @throws DependencyInjectionException
     */
    protected function __construct(string $class, bool $use = false)
    {
        if ($use) {
            $this->class = Param::get($class);
            $this->use = true;
        } else {
            $this->setClass($class);
        }
    }

    /**
     * @param string $class
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function bind(string $class): DependencyInjection
    {
        return new DependencyInjection($class);
    }

    /**
     * @param string $class
     * @return DependencyInjection
     * @throws DependencyInjectionException
     */
    public static function use(string $class): DependencyInjection
    {
        return new DependencyInjection($class, true);
    }

    /**
     * @return $this
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function withInjectedConstructor(): static
    {
        if ($this->use) {
            throw new DependencyInjectionException('You cannot set constructor on a already set object (DI::use())');
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
                    $args[] = Param::get(ltrim($type->__toString(), "\\"));
                }
            }
            return $this->withConstructorArgs($args);
        }

        return $this->withNoConstructor();
    }

    /**
     * @return $this
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function withInjectedLegacyConstructor(): static
    {
        if ($this->use) {
            throw new DependencyInjectionException('You cannot set constructor on a already set object (DI::use())');
        }

        $reflection = new ReflectionMethod($this->getClass(), "__construct");

        $docComments = str_replace("\n", " ", $reflection->getDocComment());

        $methodParams = $reflection->getParameters();

        $params = [];
        $result = preg_match_all('/@param\s+([\w_\\\\]+)\s+\$[\w_]+/', $docComments, $params);

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
     * @return $this
     * @throws DependencyInjectionException
     */
    public function withNoConstructor(): static
    {
        if ($this->use) {
            throw new DependencyInjectionException('You cannot set constructor on a already set object (DI::use())');
        }

        $this->args = null;
        return $this;
    }

    /**
     * @return $this
     * @throws DependencyInjectionException
     */
    public function withConstructorNoArgs(): static
    {
        if ($this->use) {
            throw new DependencyInjectionException('You cannot set constructor on a already set object (DI::use())');
        }

        $this->args = [];
        return $this;
    }

    public function withMethodCall(string $method, array $args = []): static
    {
        $this->methodCall[] = [$method, $args];
        return $this;
    }

    /**
     * @return $this
     * @throws DependencyInjectionException
     */
    public function toSingleton(): static
    {
        if ($this->use) {
            throw new DependencyInjectionException('You cannot get a singleton over an existent object (DI::use())');
        }
        $this->singleton = true;
        return $this;
    }

    /**
     * @return $this
     * @throws DependencyInjectionException
     */
    public function toEagerSingleton(): static
    {
        $this->eager = true;
        return $this->toSingleton();
    }

    /**
     * @return $this
     */
    public function toInstance(): static
    {
        $this->singleton = false;
        return $this;
    }

    public function toDelayedInstance(): static
    {
        $this->delayedInstance = true;
        return $this;
    }

    /**
     * @return object
     * @throws ContainerExceptionInterface
     * @throws DependencyInjectionException
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getInstance(mixed ...$args): mixed
    {
        if (!empty($args)) {
            $this->args = $args;
        }

        $instance = $this->getInternalInstance();

        if (is_null($instance)) {
            throw new DependencyInjectionException("Could not get a instance of " . $this->getClass());
        }

        $this->processed = true;

        return $instance;
    }

    /**
     * @return object
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function getInternalInstance(): mixed
    {
        if ($this->singleton) {
            return $this->getSingletonInstace();
        }

        return $this->getNewInstance();
    }

    /**
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function getNewInstance(): mixed
    {
        if ($this->use) {
            $instance = $this->getArgs([$this->class])[0];
        } else if (!empty($this->factory)) {
            $instance = call_user_func_array([$this->getClass(), $this->factory], $this->getArgs());
        } else {

            $reflectionClass = new ReflectionClass($this->getClass());

            if (is_null($this->args)) {
                $instance = $reflectionClass->newInstanceWithoutConstructor();
            } else {
                $instance = $reflectionClass->newInstanceArgs($this->getArgs());
            }
        }

        return $this->callMethods($instance, !$this->use);
    }

    /**
     * @param mixed $instance
     * @param bool $returnInstance
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     */
    protected function callMethods(mixed $instance, bool $returnInstance = true): mixed
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
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    protected function getSingletonInstace(): mixed
    {
        if (empty($this->instance)) {
            $this->instance = $this->getNewInstance();
        }
        return $this->instance;
    }

    public function isEagerSingleton(): bool
    {
        return $this->eager;
    }

    public function isLoaded(): bool
    {
        return (!is_null($this->instance));
    }

    public function isDelayedInstance(): bool
    {
        return $this->delayedInstance;
    }

    public function wasUsed(): bool
    {
        return $this->processed;
    }

    public function releaseInstance()
    {
        if (!empty($this->instance) && !$this->isEagerSingleton()) {
            $this->instance = null;
        }
    }
}
