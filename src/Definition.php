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

    public function setCache(CacheInterface $cache, $env = "live")
    {
        $this->cache[$env] = $cache;
        $this->cacheTTL[$env] = new \DateInterval('P7D');
        return $this;
    }

    public function setCacheTTL(\DateInterval $ttl, $env = "live")
    {
        if (!isset($this->cache[$env])) {
            throw new \Exception('Environment does not exists. Could not set Cache TTL.');
        }

        $this->cacheTTL[$env] = $ttl;
    }

    public function addEnvironment($env)
    {
        if (isset($this->environments[$env])) {
            throw new \InvalidArgumentException("Environment '$env' already exists");
        }
        $this->lastEnv = $env;
        $this->environments[$env] = [];
        return $this;
    }

    public function inheritFrom($env)
    {
        $this->environments[$this->lastEnv][] = $env;
        return $this;
    }

    public function environmentVar($var)
    {
        $this->envVar = $var;
    }

    public function getCurrentEnv()
    {
        $env = getenv($this->envVar);
        if (empty($env)) {
            throw new \InvalidArgumentException("The environment variable '$this->envVar' is not set");
        }
        return  $env;
    }

    /**
     * @param string|null $env
     * @return \ByJG\Config\Container
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
