<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigException;

class ParamParser
{
    protected static $parsers = [];

    public static function addParser(string $key, \Closure $parser)
    {
        if (isset(self::$parsers[$key])) {
            throw new \InvalidArgumentException("Parser for '$key' already exists");
        }

        self::$parsers[$key] = $parser;
    }

    public static function parse($key, $param)
    {
        if (!self::isParserExists($key)) {
            throw new ConfigException("Parser for '$key' not found");
        }

        return call_user_func(self::$parsers[$key], $param);
    }

    public static function isParserExists($key)
    {
        return isset(self::$parsers[$key]);
    }
}