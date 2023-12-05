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
    public function setCache($configName, CacheInterface $cache)
    {
        foreach ((array)$configName as $item) {
            $this->cache[$item] = $cache;
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
            $this->allowCache = $container->saveToCache($configName, $this->cache[$configName]);
        }
        $container->processEagerSingleton();
        $container->initializeParsers();
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
}
