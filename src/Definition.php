<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\EnvironmentException;
use ByJG\Config\Exception\InvalidDateException;
use DateInterval;
use Exception;
use Psr\SimpleCache\CacheInterface;

class Definition
{
    private $lastEnv = null;

    private $environments = [];

    private $envVar = 'APPLICATION_ENV';

    /**
     * @var CacheInterface[]
     */
    private $cache = [];

    /**
     * @var DateInterval[]
     */
    private $cacheTTL = [];

    private $baseDir = "";

    private $loadOSEnv = false;

    private function loadConfig($currentConfig, $env)
    {
        $content1 = $this->loadConfigFile($env);
        $content2 = $this->loadEnvFile($env);

        if (is_null($content1) && is_null($content2)) {
            throw new ConfigNotFoundException("Configuration 'config-$env.php' or 'config-$env.env' could not found at " . $this->getBaseDir());
        }

        return array_merge(is_null($content1) ? [] : $content1, is_null($content2) ? [] : $content2, $currentConfig);
    }

    /**
     * @param string $env The environment to be loaded
     * @return array
     */
    private function loadConfigFile($env)
    {
        $file = $this->getBaseDir() . '/config-' . $env .  '.php';

        if (!file_exists($file)) {
            return null;
        }

        return (include $file);
    }

    private function loadEnvFile($env)
    {
        $filename = $this->getBaseDir() . "/config-$env.env";
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
     * @param string|array $env
     * @return $this
     * @throws InvalidDateException
     */
    public function setCache(CacheInterface $cache, $env = "live")
    {
        foreach ((array)$env as $item) {
            try {
                $date = new DateInterval('P7D');
            } catch (Exception $ex) {
                throw new InvalidDateException($ex->getMessage());
            }
            $this->cache[$item] = $cache;
            $this->cacheTTL[$item] = $date;
        }
        return $this;
    }

    /**
     * @param DateInterval $ttl
     * @param string|array $env
     * @return $this
     * @throws EnvironmentException
     */
    public function setCacheTTL(DateInterval $ttl, $env = "live")
    {
        foreach ((array)$env as $item) {
            if (!isset($this->cache[$item])) {
                throw new EnvironmentException('Environment does not exists. Could not set Cache TTL.');
            }

            $this->cacheTTL[$item] = $ttl;
        }
        return $this;
    }

    /**
     * @param string $env
     * @return $this
     * @throws EnvironmentException
     */
    public function addConfig($env)
    {
        if (isset($this->environments[$env])) {
            throw new EnvironmentException("Environment '$env' already exists");
        }
        $this->lastEnv = $env;
        $this->environments[$env] = [];
        return $this;
    }

    /**
     * @param string $env
     * @return $this
     */
    public function inheritFrom($env)
    {
        $this->environments[$this->lastEnv][] = $env;
        return $this;
    }

    /**
     * @return $this
     */
    public function loadOSEnv()
    {
        $this->loadOSEnv = true;
        return $this;
    }

    /**
     * Sets the environment variable. Defaults to APPLICATION_ENV
     *
     * @param string $var
     * @return $this
     */
    public function environmentVar($var)
    {
        $this->envVar = $var;
        return $this;
    }

    /**
     * @throws EnvironmentException
     */
    public function withBaseDir($dir)
    {
        if (!file_exists($dir)) {
            throw new EnvironmentException("Directory $dir doesn't exists");
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
     * Get the current environment
     *
     * @return array|false|string
     * @throws EnvironmentException
     */
    public function getCurrentEnv()
    {
        $env = getenv($this->envVar);
        if (empty($env)) {
            throw new EnvironmentException("The environment variable '$this->envVar' is not set");
        }
        return $env;
    }

    /**
     * Get the config based on the specified environment
     *
     * @param string|null $env
     * @return Container
     * @throws ConfigNotFoundException
     * @throws EnvironmentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function build($env = null)
    {
        if (empty($env)) {
            $env = $this->getCurrentEnv();
        }

        if (!isset($this->environments[$env])) {
            throw new EnvironmentException("Environment '$env' does not defined");
        }

        $container = null;
        if (isset($this->cache[$env])) {
            $container = $this->cache[$env]->get("container-cache-$env");
            if (!is_null($container)) {
                return $container;
            }
        }

        $config = [];
        $config = $this->loadConfig($config, $env);
        foreach ($this->environments[$env] as $environment) {
            $config = $this->loadConfig($config, $environment);
        }

        if ($this->loadOSEnv) {
            $config = array_merge($config, $_ENV);
        }

        $container = new Container($config);
        if (isset($this->cache[$env])) {
            $this->cache[$env]->set("container-cache-$env", $container, $this->cacheTTL[$env]);
        }
        return $container;
    }
}
