<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\EnvironmentException;
use ByJG\Config\Exception\InvalidDateException;
use DateInterval;
use Exception;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

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

    /**
     * @param array $currentConfig The array with current loaded configuration
     * @param string $env The environment to be loaded
     * @return array
     * @throws ConfigNotFoundException
     */
    private function loadConfig($currentConfig, $env)
    {
        $file = __DIR__ . '/../../../../config/config-' . $env .  '.php';

        if (!file_exists($file)) {
            $file = __DIR__ . '/../config/config-' . $env .  '.php';
        }

        if (!file_exists($file)) {
            throw new ConfigNotFoundException(
                "The config file '"
                . "config-$env.php' "
                . 'does not found at '
                . "'<ROOT>/config/config-$env.php'"
            );
        }

        $config = (include $file);
        return array_merge($config, $currentConfig);
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
    public function addEnvironment($env)
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
     * @throws InvalidArgumentException
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

        $container = new Container($config);
        if (isset($this->cache[$env])) {
            $this->cache[$env]->set("container-cache-$env", $container, $this->cacheTTL[$env]);
        }
        return $container;
    }
}
