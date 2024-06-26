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
    private $configList = [];

    private $configVar = 'APP_ENV';

    /**
     * @var CacheInterface[]
     */
    private $cache = [];

    private $allowCache = true;

    private $baseDir = "";

    /**
     * @var bool|array
     */
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
            $line = trim($line);
            if (!preg_match("/^(?<key>\w+)\s*=\s*([\"'])?(?<value>.*?)([\"'])?$/", $line, $result)) {
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
     * @param string $configName
     * @return $this
     * @throws ConfigException
     */
    public function addEnvironment(Environment $config)
    {
        if (isset($this->configList[$config->getName()])) {
            throw new ConfigException("Configuration '{$config->getName()}' already exists");
        }
        $this->configList[$config->getName()] = $config;
        return $this;
    }


    /**
     * @return $this
     */
    public function withOSEnvironment($keys = [])
    {
        $this->loadOsEnv = is_array($keys) && empty($keys) ? true : $keys;
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
    public function getCurrentEnvironment()
    {
        return $this->setCurrentEnvironment();
    }

    /**
     * @param string|null $configName
     * @return array|mixed|string
     * @throws ConfigException
     */
    protected function setCurrentEnvironment($configName = null)
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
        $configName = $this->setCurrentEnvironment($configName);

        if (!isset($this->configList[$configName])) {
            throw new ConfigException("Configuration '$configName' does not defined");
        }

        if ($this->configList[$configName]->isAbstract()) {
            throw new ConfigException("Configuration '$configName' is abstract and cannot be instantiated");
        }

        // Check if container is saved in the cache
        if ($this->allowCache && !empty($this->configList[$configName]->getCacheInterface())) {
            $container = Container::createFromCache($configName, $this->configList[$configName]->getCacheInterface());
            if (!is_null($container)) {
                return $container;
            }
        }

        // Create from the Definition and configuration files
        $config = [];
        $config = $this->loadConfig($config, $configName);
        $config = $this->loadConfigFromDefinition($config, $configName);

        foreach ($config as $key => $value) {
            $envValue = getenv($key);
            if (!empty($envValue)) {
                $config[$key] = $envValue;
            }
        }

        if ($this->loadOsEnv === true) {
            $config = array_merge($config, $_ENV);
        } elseif (is_array($this->loadOsEnv)) {
            foreach ($this->loadOsEnv as $key) {
                $config[$key] = $_ENV[$key] ?? "";
            }
        }

        return new Container($config, $configName, $this->configList[$configName]->getCacheInterface() ?? null);
    }

    // Recursive function to load from Config
    protected function loadConfigFromDefinition($config, $configName)
    {
        foreach ($this->configList[$configName]->getInheritFrom() as $configData) {
            $config = $this->loadConfig($config, $configData->getName());
            if ($configData->getInheritFrom()) {
                $config = $this->loadConfigFromDefinition($config, $configData->getName());
            }
        }
        return $config;
    }

    public function getCacheCurrentEnvironment(CacheInterface $default = null)
    {
        if (empty($this->configName)) {
            throw new RunTimeException("Environment isn't build yet");
        }

        if (!empty($this->configList[$this->configName]->getCacheInterface())) {
            return $this->configList[$this->configName]->getCacheInterface();
        }
        return $default;
    }

    public function getConfigObject($configName): Environment
    {
        if (isset($this->configList[$configName])) {
            return $this->configList[$configName];
        }

        throw new \Exception('Config Definition not found');
    }
}
