<?php

namespace ByJG\Config;

use ByJG\Config\Exception\DependencyInjectionException;
use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

class LazyProxyFactory
{
    private const PROXY_NAMESPACE = __NAMESPACE__ . '\\LazyProxy';

    /**
     * Cache of target type => generated proxy class (Fully Qualified)
     * @var array<string, string>
     */
    private static array $generated = [];

    /**
     * @throws DependencyInjectionException
     */
    public static function create(ContainerInterface $container, string $serviceId, string $typeHint): object
    {
        $typeHint = ltrim($typeHint, '\\');

        if (!class_exists($typeHint) && !interface_exists($typeHint)) {
            throw new DependencyInjectionException("LazyParam target '$typeHint' does not exist");
        }

        $proxyClass = self::ensureProxyClass($typeHint);

        $factory = static function () use ($container, $serviceId, $typeHint) {
            $instance = $container->get($serviceId);
            if (!($instance instanceof $typeHint)) {
                $given = is_object($instance) ? get_class($instance) : gettype($instance);
                throw new DependencyInjectionException("LazyParam expected '$typeHint' for '$serviceId', got '$given'");
            }
            return $instance;
        };

        return new $proxyClass($factory);
    }

    /**
     * @param class-string $typeHint
     * @return string
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    private static function ensureProxyClass(string $typeHint): string
    {
        if (isset(self::$generated[$typeHint])) {
            return self::$generated[$typeHint];
        }

        $reflection = new ReflectionClass($typeHint);

        if ($reflection->isTrait()) {
            throw new DependencyInjectionException("LazyParam cannot target trait '$typeHint'");
        }

        if (!$reflection->isInterface() && $reflection->isFinal()) {
            throw new DependencyInjectionException("LazyParam cannot create proxy for final class '$typeHint'");
        }

        $shortName = 'Proxy_' . md5($typeHint);
        $fqcn = self::PROXY_NAMESPACE . '\\' . $shortName;

        if (!class_exists($fqcn)) {
            $code = self::buildProxyCode($reflection, $shortName);
            eval($code);
        }

        self::$generated[$typeHint] = '\\' . $fqcn;
        return self::$generated[$typeHint];
    }

    /**
     * @throws DependencyInjectionException
     */
    private static function buildProxyCode(ReflectionClass $reflection, string $className): string
    {
        $target = '\\' . ltrim($reflection->getName(), '\\');
        $implementsOrExtends = $reflection->isInterface() ? "implements $target" : "extends $target";

        $methodsCode = self::buildMethods($reflection);

        $namespace = self::PROXY_NAMESPACE;

        return <<<PHP
namespace $namespace;

use Closure;

class $className $implementsOrExtends
{
    private Closure \$__factory;
    private bool \$__resolved = false;
    private \$__instance = null;

    public function __construct(callable \$factory)
    {
        \$this->__factory = Closure::fromCallable(\$factory);
    }

    private function __getLazyInstance()
    {
        if (!\$this->__resolved) {
            \$factory = \$this->__factory;
            \$this->__instance = \$factory();
            \$this->__resolved = true;
        }
        return \$this->__instance;
    }

    public function __get(string \$name)
    {
        return \$this->__getLazyInstance()->\$name;
    }

    public function __set(string \$name, \$value): void
    {
        \$this->__getLazyInstance()->\$name = \$value;
    }

    public function __isset(string \$name): bool
    {
        return isset(\$this->__getLazyInstance()->\$name);
    }

    public function __unset(string \$name): void
    {
        unset(\$this->__getLazyInstance()->\$name);
    }

    public function __call(string \$name, array \$arguments)
    {
        return \$this->__getLazyInstance()->\$name(...\$arguments);
    }

    public function __clone()
    {
        if (\$this->__resolved) {
            \$this->__instance = clone \$this->__instance;
        }
    }

$methodsCode
}
PHP;
    }

    /**
     * @throws DependencyInjectionException
     */
    private static function buildMethods(ReflectionClass $reflection): string
    {
        $code = '';
        $processed = [];
        foreach ($reflection->getMethods() as $method) {
            if (!$method->isPublic() || $method->isStatic() || $method->isConstructor()) {
                continue;
            }

            if (!$reflection->isInterface() && $method->isFinal()) {
                $className = $reflection->getName();
                throw new DependencyInjectionException("LazyParam cannot proxy final method '{$method->getName()}' from '$className'");
            }

            $methodKey = $method->getName() . ':' . $method->getNumberOfParameters();
            if (isset($processed[$methodKey])) {
                continue;
            }
            $processed[$methodKey] = true;

            $code .= self::buildMethodDefinition($method) . PHP_EOL;
        }

        return $code;
    }

    private static function buildMethodDefinition(ReflectionMethod $method): string
    {
        $reference = $method->returnsReference() ? '&' : '';
        $parameters = array_map(static fn(ReflectionParameter $param) => self::formatParameter($param), $method->getParameters());
        $paramsString = implode(', ', $parameters);
        $returnType = self::formatReturnType($method->getReturnType());
        $callArgs = self::buildCallArgs($method->getParameters());

        $callLines = "        \$instance = \$this->__getLazyInstance();" . PHP_EOL;

        if (self::isVoidReturn($method)) {
            $callLines .= "        \$instance->{$method->getName()}($callArgs);" . PHP_EOL;
        } else {
            $callLines .= "        return \$instance->{$method->getName()}($callArgs);" . PHP_EOL;
        }

        $signatureParams = $paramsString;

        return <<<PHP
    public function {$reference}{$method->getName()}($signatureParams)$returnType
    {
$callLines    }
PHP;
    }

    private static function buildCallArgs(array $parameters): string
    {
        $args = [];
        foreach ($parameters as $parameter) {
            $arg = '';
            if ($parameter->isVariadic()) {
                $arg .= '...';
            }
            $arg .= '$' . $parameter->getName();
            $args[] = $arg;
        }

        return implode(', ', $args);
    }

    private static function formatParameter(ReflectionParameter $parameter): string
    {
        $code = '';
        if ($parameter->hasType()) {
            $code .= self::typeToString($parameter->getType()) . ' ';
        }
        if ($parameter->isPassedByReference()) {
            $code .= '&';
        }
        if ($parameter->isVariadic()) {
            $code .= '...';
        }
        $code .= '$' . $parameter->getName();

        if ($parameter->isOptional() && !$parameter->isVariadic()) {
            if ($parameter->isDefaultValueAvailable()) {
                $code .= ' = ' . self::formatDefaultValue($parameter);
            } elseif ($parameter->allowsNull()) {
                $code .= ' = null';
            }
        }

        return trim($code);
    }

    private static function formatDefaultValue(ReflectionParameter $parameter): string
    {
        if ($parameter->isDefaultValueConstant()) {
            return $parameter->getDefaultValueConstantName();
        }

        return var_export($parameter->getDefaultValue(), true);
    }

    private static function formatReturnType(?ReflectionType $type): string
    {
        if (is_null($type)) {
            return '';
        }

        return ': ' . self::typeToString($type);
    }

    private static function typeToString(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
            $nullable = $type->allowsNull() && $name !== 'mixed' && $name !== 'null';
            $normalized = ($type->isBuiltin() || in_array($name, ['self', 'static', 'parent'], true))
                ? $name
                : '\\' . ltrim($name, '\\');
            return ($nullable ? '?' : '') . $normalized;
        }

        if ($type instanceof ReflectionUnionType) {
            $parts = [];
            foreach ($type->getTypes() as $inner) {
                $parts[] = self::typeToString($inner);
            }
            return implode('|', $parts);
        }

        if ($type instanceof ReflectionIntersectionType) {
            $parts = [];
            foreach ($type->getTypes() as $inner) {
                $parts[] = self::typeToString($inner);
            }
            return implode('&', $parts);
        }

        return '';
    }

    private static function isVoidReturn(ReflectionMethod $method): bool
    {
        $type = $method->getReturnType();
        if ($type instanceof ReflectionNamedType && $type->getName() === 'void') {
            return true;
        }

        return false;
    }
}
