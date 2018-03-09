<?php
/**
 * User: jg
 * Date: 26/05/17
 * Time: 00:56
 */

namespace ByJG\Config;

use ByJG\Config\Exception\NotFoundException;
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
     * @var \DateInterval[]
     */
    private $cacheTTL = [];

    /**
     * @param array $currentConfig The array with current loaded configuration
     * @param string $env The environment to be loaded
     * @return array
     * @throws \ByJG\Config\Exception\NotFoundException
     */
    private function loadConfig($currentConfig, $env)
    {
        $file = __DIR__ . '/../../../../config/config-' . $env .  '.php';

        if (!file_exists($file)) {
            $file = __DIR__ . '/../config/config-' . $env .  '.php';
        }

        if (!file_exists($file)) {
            throw new NotFoundException(
                "The config file '"
                . "config-$env.php'"
                . 'does not found'
            );
        }

        $config = (include $file);
        return array_merge($config, $currentConfig);
    }

    /**
     * @param \Psr\SimpleCache\CacheInterface $cache
     * @param string|array $env
     * @return $this
     * @throws \Exception
     */
    public function setCache(CacheInterface $cache, $env = "live")
    {
        foreach ((array)$env as $item) {
            $this->cache[$item] = $cache;
            $this->cacheTTL[$item] = new \DateInterval('P7D');
        }
        return $this;
    }

    /**
     * @param \DateInterval $ttl
     * @param string|array $env
     * @return $this
     * @throws \Exception
     */
    public function setCacheTTL(\DateInterval $ttl, $env = "live")
    {
        foreach ((array)$env as $item) {
            if (!isset($this->cache[$item])) {
                throw new \Exception('Environment does not exists. Could not set Cache TTL.');
            }

            $this->cacheTTL[$item] = $ttl;
        }
        return $this;
    }

    /**
     * @param string $env
     * @return $this
     */
    public function addEnvironment($env)
    {
        if (isset($this->environments[$env])) {
            throw new \InvalidArgumentException("Environment '$env' already exists");
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
     */
    public function getCurrentEnv()
    {
        $env = getenv($this->envVar);
        if (empty($env)) {
            throw new \InvalidArgumentException("The environment variable '$this->envVar' is not set");
        }
        return $env;
    }

    /**
     * Get the config based on the specified environment
     *
     * @param string|null $env
     * @return \ByJG\Config\Container
     * @throws \ByJG\Config\Exception\NotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function build($env = null)
    {
        if (empty($env)) {
            $env = $this->getCurrentEnv();
        }

        if (!isset($this->environments[$env])) {
            throw new \InvalidArgumentException("Environment '$env' does not defined");
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
