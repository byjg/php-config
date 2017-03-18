<?php

namespace ByJG\Util;

class Config
{
    private static $_config = null;

    protected static function loadConfig()
    {
        self::$_config = self::inherit(getenv('APPLICATION_ENV'));
    }

    public static function inherit($env)
    {
        $file = __DIR__ . '/../../config/config-' . $env .  '.php';
        if (!file_exists($file)) {
            throw new \Exception("The config file '$file' does not found" );
        }
        $config = (include $file);
        return $config;
    }

    /**
     * @param $property
     * @param bool $throwError
     * @return null|string|\Closure
     */
    public static function get($property, $throwError = true)
    {
        if (empty(self::$_config)) {
            self::loadConfig();
        }

        if (!isset(self::$_config[$property])) {
            if ($throwError) {
                throw new \InvalidArgumentException("The key '$property'' does not exists");
            }
            return null;
        }

        return self::$_config[$property];
    }
}
