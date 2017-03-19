<?php

namespace ByJG\Util;

class Config
{
    private static $config = null;

    protected static function loadConfig()
    {
        self::$config = self::inherit(getenv('APPLICATION_ENV'));
    }

    public static function inherit($env)
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
        return $config;
    }

    /**
     * @param $property
     * @param bool $throwError
     * @return null|string|\Closure
     */
    public static function get($property, $throwError = true)
    {
        if (empty(self::$config)) {
            self::loadConfig();
        }

        if (!isset(self::$config[$property])) {
            if ($throwError) {
                throw new \InvalidArgumentException("The key '$property'' does not exists");
            }
            return null;
        }

        return self::$config[$property];
    }

    /**
     * @param $property
     * @param $args
     * @return mixed
     */
    public static function getArgs($property, $args)
    {
        $closure = self::get($property);

        if (is_array($args)) {
            return call_user_func_array($closure, $args);
        }

        return call_user_func_array($closure, array_slice(func_get_args(), 1));
    }

    public static function reset()
    {
        self::$config = null;
    }
}
