<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigException;
use Closure;
use InvalidArgumentException;

class ParamParser
{
    protected static array $parsers = [];

    public static function addParser(string $key, Closure $parser): void
    {
        if (isset(self::$parsers[$key])) {
            throw new InvalidArgumentException("Parser for '$key' already exists");
        }

        self::$parsers[$key] = $parser;
    }

    public static function parse(string $key, string $param): mixed
    {
        if (!self::isParserExists($key)) {
            throw new ConfigException("Parser for '$key' not found");
        }

        return call_user_func(self::$parsers[$key], $param);
    }

    public static function isParserExists(string $key): bool
    {
        return isset(self::$parsers[$key]);
    }
}