<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\ConfigException;
use ByJG\Config\Exception\InvalidDateException;
use ByJG\Config\Exception\RunTimeException;
use DateInterval;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Definition
{
    private $lastConfig = null;

    private $configList = [];

    private $configVar = 'APP_ENV';

    /**
     * @var CacheInterface[]
     */
    private $cache = [];

    private $allowCache = true;

    /**
     * @var DateInterval[]
     */
    private $cacheTTL = [];

    private $baseDir = "";

    private $loadOsEnv = false;

    private $configName = null;

    private function loadConfig($currentConfig, $configName)
    {
        $content1 = $this->loadConfigFile($configName);
        $content2 = $this->loadDirectory($configName);
        $content3 = $this->loadEnvFileContents($this->getBaseDir() . "/.env");

        if (is_null($content1) && is_null($content2)) {
            throw new ConfigNotFoundException("Configuration 'config-$configName.php' or 'config-$configName.env' could not found at " . $this->getBaseDir());
        }

        return array_merge(
            is_null($content1) ? [] : $content1,
            is_null($content2) ? [] : $content2,
            is_null($content3) ? [] : $content3,
            $currentConfig
        );
    }

    /**
     * @param string $configName The configuration to be loaded
     * @return array|null
     */
    private function loadConfigFile($configName)
    {
        $phpConfig = $this->_loadPhp($this->getBaseDir() . '/config-' . $configName .  '.php');
        $envFile = $this->loadEnvFileContents($this->getBaseDir() . "/config-$configName.env");

        if (is_null($phpConfig) && is_null($envFile)) {
            return null;
        }

        return array_merge((array)$phpConfig, (array)$envFile);
    }

    private function _loadPhp($file)
    {
        if (!file_exists($file)) {
            return null;
        }

        return (include $file);
    }

    private function loadEnvFileContents($filename)
    {
        if (!file_exists($filename)) {
            return null;
        }

        $config = [];
        foreach (file($filename) as $line) {
            if (!preg_match("/^\s*(?<key>\w+)\s*=\s*(?<value>.*)$/", $line, $result)) {
                continue;
            }
            if (preg_match("/^!(?<parser>\w+)\s+(?<value>.*)$/", $result["value"], $parsed)) {
                $result["value"] = ParamParser::parse($parsed["parser"], $parsed["value"]);
            }
            $config[$result["key"]] = $result["value"];
        }

        return $config;
    }

    private function loadDirectory($configName)
    {
        $dir = $this->getBaseDir() . '/' . $configName;

        if (!file_exists($dir)) {
            return [];
        }

        $config = [];
        foreach (glob( "$dir/*.php") as $file) {
            $config = array_merge($config, $this->_loadPhp($file));
        }
        foreach (glob("$dir/*.env") as $file) {
            $config = array_merge($config, $this->loadEnvFileContents($file));
        }

        return $config;
    }

    /**
     * @param CacheInterface $cache
     * @param string|array $configName
     * @return $this
     * @throws InvalidDateException
     */
    public function setCache($configName, CacheInterface $cache, DateInterval $ttl = null)
    {
        foreach ((array)$configName as $item) {
            try {
                $date = empty($ttl) ? new DateInterval('P7D') : $ttl;
            } catch (Exception $ex) {
                throw new InvalidDateException($ex->getMessage());
            }
            $this->cache[$item] = $cache;
            $this->cacheTTL[$item] = $date;
        }
        return $this;
    }

    /**
     * @param string $configName
     * @return $this
     * @throws ConfigException
     */
    public function addConfig($configName, array $inheritFrom = [])
    {
        if (isset($this->configList[$configName])) {
            throw new ConfigException("Configuration '$configName' already exists");
        }
        $this->lastConfig = $configName;
        $this->configList[$configName] = $inheritFrom;
        return $this;
    }

    /**
     * @param string $configName
     * @return $this
     * @deprecated Use addConfig($config, $inheritFrom) instead
     */
    public function inheritFrom($configName)
    {
        $this->configList[$this->lastConfig][] = $configName;
        return $this;
    }

    /**
     * @return $this
     */
    public function withOSEnvironment()
    {
        $this->loadOsEnv = true;
        return $this;
    }

    /**
     * Sets the environment variable. Defaults to APP_ENV
     *
     * @param string $var
     * @return $this
     */
    public function withConfigVar($var)
    {
        $this->configVar = $var;
        return $this;
    }

    /**
     * @throws ConfigException
     */
    public function withBaseDir($dir)
    {
        if (!file_exists($dir)) {
            throw new ConfigException("Directory $dir doesn't exists");
        }
        $this->baseDir = $dir;
        return $this;
    }

    private function getBaseDir()
    {
        if (empty($this->baseDir)) {
            $dir = __DIR__ . '/../../../../config';

            if (!file_exists($dir)) {
                $dir = __DIR__ . '/../config';
            }
            $this->baseDir = $dir;
        }
        return $this->baseDir;
    }

    /**
     * Get the current config
     *
     * @return array|false|string
     * @throws ConfigException
     */
    public function getCurrentConfig()
    {
        return $this->setCurrentConfig();
    }

    /**
     * @param string|null $configName
     * @return array|mixed|string
     * @throws ConfigException
     */
    protected function setCurrentConfig($configName = null)
    {
        if (!empty($configName)) {
            $this->configName = $configName;
        }

        if (empty($this->configName)) {
            $configName = getenv($this->configVar);
            if (empty($configName)) {
                throw new ConfigException("The environment variable '$this->configVar' is not set");
            }
            return $configName;
        }

        return $this->configName;
    }

    /**
     * Get the config based on the specified configuration
     *
     * @param string|null $configName
     * @return Container
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function build($configName = null)
    {
        $this->initializeParsers();

        $configName = $this->setCurrentConfig($configName);

        if (!isset($this->configList[$configName])) {
            throw new ConfigException("Configuration '$configName' does not defined");
        }

        if ($this->allowCache && isset($this->cache[$configName])) {
            $container = Container::createFromCache($configName, $this->cache[$configName]);
            if (!is_null($container)) {
                return $container;
            }
        }

        $config = [];
        $config = $this->loadConfig($config, $configName);
        foreach ($this->configList[$configName] as $configData) {
            $config = $this->loadConfig($config, $configData);
        }

        foreach ($config as $key => $value) {
            $envValue = getenv($key);
            if (!empty($envValue)) {
                $config[$key] = $envValue;
            }
        }

        if ($this->loadOsEnv) {
            $config = array_merge($config, $_ENV);
        }

        $container = new Container($config);
        if (isset($this->cache[$configName])) {
            $this->allowCache = $container->saveToCache($configName, $this->cacheTTL[$configName], $this->cache[$configName]);
        }
        return $container;
    }

    public function getCacheCurrentEnvironment(CacheInterface $default = null)
    {
        if (empty($this->configName)) {
            throw new RunTimeException("Environment isn't build yet");
        }

        if (isset($this->cache[$this->configName])) {
            return $this->cache[$this->configName];
        }
        return $default;
    }

    protected function initializeParsers()
    {
        if (ParamParser::isParserExists('initialized')) {
            return;
        }

        ParamParser::addParser('initialized', function ($value) {
            return true;
        });
        ParamParser::addParser('bool', function ($value) {
            return boolval($value);
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

    }
}
