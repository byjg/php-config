<?php
/**
 * User: jg
 * Date: 26/05/17
 * Time: 00:56
 */

namespace ByJG\Config;

use Psr\SimpleCache\CacheInterface;

class Definition
{
    private $lastEnv = null;

    private $environments = [];

    /**
     * @var CacheInterface
     */
    private $cache = null;

    private function loadConfig($currentConfig, $env)
    {
        $file = __DIR__ . '/../../../../config/config-' . $env .  '.php';

        if (!file_exists($file)) {
            $file = __DIR__ . '/../config/config-' . $env .  '.php';
        }

        if (!file_exists($file)) {
            throw new \Exception(
                "The config file '"
                . "config-$env.php'"
                . 'does not found'
            );
        }

        $config = (include $file);
        return array_merge($config, $currentConfig);
    }

    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
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

    public function getCurrentEnv()
    {
        return getenv('APPLICATION_ENV');
    }

    /**
     * @todo PSR-6 Method para Cache
     * @todo Fazer o Cache Engine utilizar o SimpleCache também!
     * @todo Performance!
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
        if (isset($this->cache)) {
            $container = $this->cache->get("container-cache-$env");
            if (!is_null($container)) {
                return unserialize($container);
            }
        }

        $config = [];
        $config = $this->loadConfig($config, $env);
        foreach ($this->environments[$env] as $environment) {
            $config = $this->loadConfig($config, $environment);
        }

        $container = new Container($config);
        if (isset($this->cache)) {
            $this->cache->set("container-cache-$env", serialize($container));
        }
        return $container;
    }
}
