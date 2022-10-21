<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\ConfigException;
use ByJG\Config\Exception\InvalidDateException;
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
        $content2 = $this->loadEnvFile($configName);

        if (is_null($content1) && is_null($content2)) {
            throw new ConfigNotFoundException("Configuration 'config-$configName.php' or 'config-$configName.env' could not found at " . $this->getBaseDir());
        }

        return array_merge(is_null($content1) ? [] : $content1, is_null($content2) ? [] : $content2, $currentConfig);
    }

    /**
     * @param string $configName The configuration to be loaded
     * @return array
     */
    private function loadConfigFile($configName)
    {
        $file = $this->getBaseDir() . '/config-' . $configName .  '.php';

        if (!file_exists($file)) {
            return null;
        }

        return (include $file);
    }

    private function loadEnvFile($configName)
    {
        $filename = $this->getBaseDir() . "/config-$configName.env";
        if (!file_exists($filename)) {
            return null;
        }

        $config = [];
        foreach (file($filename) as $line) {
            if (!preg_match("/^\s*(?<key>\w+)\s*=\s*(?<value>.*)$/", $line, $result)) {
                continue;
            }
            if (preg_match("/^!bool\s+(?<value>true|false)$/", $result["value"], $parsed)) {
                $result["value"] = boolval($parsed["value"]);
            }
            if (preg_match("/^!int\s+(?<value>.*)$/", $result["value"], $parsed)) {
                $result["value"] = intval($parsed["value"]);
            }
            if (preg_match("/^!float\s+(?<value>.*)$/", $result["value"], $parsed)) {
                $result["value"] = floatval($parsed["value"]);
            }
            $config[$result["key"]] = $result["value"];
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
    public function addConfig($configName)
    {
        if (isset($this->configList[$configName])) {
            throw new ConfigException("Configuration '$configName' already exists");
        }
        $this->lastConfig = $configName;
        $this->configList[$configName] = [];
        return $this;
    }

    /**
     * @param string $configName
     * @return $this
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
     * @param null $configName
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
        $configName = $this->setCurrentConfig($configName);

        if (!isset($this->configList[$configName])) {
            throw new ConfigException("Configuration '$configName' does not defined");
        }

        $container = null;
        if (isset($this->cache[$configName])) {
            $container = $this->cache[$configName]->get("container-cache-$configName");
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
            $this->cache[$configName]->set("container-cache-$configName", $container, $this->cacheTTL[$configName]);
        }
        return $container;
    }
}
