<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigException;
use ByJG\Config\Exception\RunTimeException;
use Exception;
use ByJG\Config\Exception\DependencyInjectionException;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use ByJG\Config\Exception\KeyNotFoundException;
use Closure;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

class Container implements ContainerInterface, ContainerInterfaceExtended
{
    private array $config;

    private string $definitionName;

    private static array $eagerSingleton = [];

    private bool $configChanged = false;

    private ?CacheInterface $cacheObject = null;
    private ?string $cacheKey = null;
    private CacheModeEnum $cacheMode = CacheModeEnum::multipleFiles;

    /**
     * @throws Exception
     */
    public function __construct(array $config, string $definitionName = null, CacheInterface $cacheObject = null, CacheModeEnum $cacheMode = CacheModeEnum::multipleFiles)
    {
        $this->config = $config;
        if (!is_null($definitionName) && !is_null($cacheObject)) {
            $this->config["__eager_singleton"] = Container::$eagerSingleton;
            $this->saveToCache($definitionName, $cacheObject, $cacheMode);
        }
        $this->definitionName = $definitionName ?? 'default';
        $this->initializeParsers();
        $this->processEagerSingleton();
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @param mixed ...$args
     * @return mixed Entry.
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws DependencyInjectionException
     * @throws InvalidArgumentException
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    #[Override]
    public function get(string $id, mixed ...$args): mixed
    {
        $value = $this->raw($id);

        if ($value instanceof Param) {
            return $this->get($value->getParam());
        }

        if (is_string($value)) {
            if (preg_match("/^!(?<parser>\w+)\s+(?<value>.*)$/", $value, $parsed)) {
                $value = ParamParser::parse($parsed["parser"], $parsed["value"]);
                $this->set($id, $value);
            }
        }

        if ($value instanceof DependencyInjection) {
            $value->injectContainer($this);
            if ($value->isDelayedInstance()) {
                return $value;
            }
            return $value->getInstance();
        }

        if (!($value instanceof Closure)) {
            return $value;
        }

        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        if (empty($args)) {
            $args = [];
        }

        return call_user_func_array($value, $args);
    }

    protected function set(string $id, mixed $value): void
    {
        $this->config[$id] = $value;
        $this->configChanged = true;
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
    #[Override]
    public function has(string $id): bool
    {
        return isset($this->config[$id]);
    }

    /**
     * @param string $id
     * @return KeyStatusEnum|null
     */
    #[Override]
    public function keyStatus(string $id): ?KeyStatusEnum
    {
        if (!$this->has($id)) {
            return KeyStatusEnum::NOT_FOUND;
        }

        if ($this->isCachedDependencyInjection($id)) {
            return KeyStatusEnum::NOT_USED;
        }

        if (is_string($this->config[$id]) && str_starts_with($this->config[$id], '!dicached')) {
            return KeyStatusEnum::NOT_USED;
        }

        if ($this->config[$id] instanceof DependencyInjection) {
            if ($this->config[$id]->isLoaded()) {
                return KeyStatusEnum::IN_MEMORY;
            } else if ($this->config[$id]->wasUsed()) {
                return KeyStatusEnum::WAS_USED;
            } else {
                return KeyStatusEnum::NOT_USED;
            }
        }
        return KeyStatusEnum::STATIC;
    }

    /**
     * @param string $id
     * @return mixed
     * @throws InvalidArgumentException
     * @throws KeyNotFoundException
     */
    #[Override]
    public function raw(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new KeyNotFoundException("The key '$id' does not exists");
        }

        if ($this->isCachedDependencyInjection($id)) {
            $this->config[$id] = $this->cacheObject->get($this->cacheKey . "-" . $this->fixCacheKeyName($id));
        }

        return $this->config[$id];
    }

    protected function isCachedDependencyInjection($id): bool
    {
        return $this->config[$id] === hex2bin("FF");
    }

    #[Override]
    public function getAsFilename(string $id): string
    {
        # Transform ID into a valid filename
        $id = preg_replace('/[^a-zA-Z0-9]/', '_', $id);

        $filename = sys_get_temp_dir() . "/config-{$this->definitionName}-$id.php";
        if (!file_exists($filename)) {
            $contents = $this->get($id);
            if (!is_string($contents)) {
                throw new RunTimeException("The content of '$id' is not a string");
            }
            file_put_contents($filename, $contents);
        }
        return $filename;
    }

    /**
     * @throws InvalidArgumentException
     * @throws PhpVersionNotSupportedException
     * @throws Exception
     */
    public function saveToCache(string $definitionName, CacheInterface $cacheObject, CacheModeEnum $cacheModeEnum = CacheModeEnum::multipleFiles): bool
    {
        if ($this->configChanged) {
            throw new Exception("The configuration was changed. Can't save to cache.");
        }

        $this->cacheObject = $cacheObject;
        $this->cacheKey = "container-cache-$definitionName";
        $this->cacheMode = $cacheModeEnum;

        $toCache = [];
        foreach ($this->config as $key => $value) {
            $valueSerialized = null;
            if ($value instanceof Closure) {
                $valueSerialized = "!unserclosure " . base64_encode(serialize(new SerializableClosure($value)));
            } else if ($value instanceof DependencyInjection) {
                $valueSerialized = "!dicached " . base64_encode(serialize($value));
            }

            if ($this->cacheMode === CacheModeEnum::multipleFiles && !is_null($valueSerialized)) {
                $this->cacheObject->set($this->cacheKey . "-" . $this->fixCacheKeyName($key), $valueSerialized);
                $toCache[$key] = hex2bin("FF");
            } else {
                $toCache[$key] = $valueSerialized ?? $value;
            }
        }

        return $this->cacheObject->set($this->cacheKey, serialize($toCache));
    }

    protected function fixCacheKeyName(string $key): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $key));
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public static function createFromCache(string $definitionName, CacheInterface $cacheObject, CacheModeEnum $cacheModeEnum = CacheModeEnum::multipleFiles): ?Container
    {
        $fromCache = $cacheObject->get("container-cache-$definitionName");
        if (!is_null($fromCache)) {
            $fromCache = unserialize($fromCache);
            $container = new Container($fromCache, $definitionName);
            $container->cacheObject = $cacheObject;
            $container->cacheKey = "container-cache-$definitionName";
            $container->cacheMode = $cacheModeEnum;
            Container::$eagerSingleton = $container->get("__eager_singleton");
            $container->processEagerSingleton();
            return $container;
        }

        return null;
    }

    public function compare(?Container $container): bool
    {
        if (is_null($container)) {
            return false;
        }

        // Compare recusively the raw config
        $diff = array_udiff_uassoc(
            $this->config,
            $container->config,
            function ($a, $b) {
                return 0;
            },
            function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return 1;
            }
        );

        return empty($diff);
    }

    /**
     * @throws DependencyInjectionException
     * @throws KeyNotFoundException
     * @throws ReflectionException
     * @throws ConfigException
     */
    protected function processEagerSingleton(): void
    {
        if (count(self::$eagerSingleton) === 0) {
            return;
        }

        foreach (self::$eagerSingleton as $value) {
            if ($this->has($value)) {
                $this->get($value);
            }
        }

        self::$eagerSingleton = [];
    }

    public static function addEagerSingleton(string $id): void
    {
        self::$eagerSingleton[] = $id;
    }

    public function releaseSingletons(array $exceptList = []): void
    {
        foreach ($this->config as $key => $value) {
            if ($value instanceof DependencyInjection and !in_array($key, $exceptList)) {
                $value->releaseInstance();
            }
        }
    }

    protected function initializeParsers(): void
    {
        if (ParamParser::isParserExists('initialized')) {
            return;
        }

        ParamParser::addParser('initialized', function ($value) {
            return true;
        });
        ParamParser::addParser('bool', function ($value) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        });
        ParamParser::addParser('int', function ($value) {
            return intval($value);
        });
        ParamParser::addParser('float', function ($value) {
            return floatval($value);
        });
        ParamParser::addParser('jsondecode', function ($value) {
            return json_decode($value, true);
        });
        ParamParser::addParser('array', function ($value) {
            return explode(',', $value);
        });
        ParamParser::addParser('dict', function ($value) {
            $result = [];
            try {
                foreach (explode(',', $value) as $item) {
                    $item = explode('=', $item);
                    $result[trim($item[0])] = trim($item[1]);
                }
            } catch (Exception $ex) {
                throw new ConfigException("Invalid dict format '$value'");
            }
            return $result;
        });

        ParamParser::addParser('unserclosure', function ($value) {
            return unserialize(base64_decode($value))->getClosure();
        });

        ParamParser::addParser('unserialize', function ($value) {
            return unserialize(base64_decode($value));
        });

        ParamParser::addParser('dicached', function ($value) {
            return unserialize(base64_decode($value));
        });

        ParamParser::addParser('unesc', function ($value) {
            return htmlspecialchars_decode(stripcslashes($value));
        });

        ParamParser::addParser('file', function ($value) {
            if ($value[0] !== '/') {
                $value = __DIR__ . '/' . $value;
            }

            if (!file_exists($value)) {
                throw new ConfigException("File '$value' not found");
            }
            return file_get_contents($value);
        });
    }
}
