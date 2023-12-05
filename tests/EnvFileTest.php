<?php

namespace Test;

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\Config\Definition;
use PHPUnit\Framework\TestCase;

class EnvFileTest extends TestCase
{
    /**
     * @var \ByJG\Config\Definition
     */
    protected $object;

    /**
     * @throws \ByJG\Config\Exception\ConfigException
     */
    public function setUp(): void
    {
        $this->object = (new Definition())
            ->addConfig("file");
    }

    public function tearDown(): void
    {
        putenv('APP_ENV');
    }

    public function testgetCurrentConfig()
    {
        putenv('APP_ENV=file');
        $config = $this->object->build();

        $this->assertEquals('ok', $config->get('GLOBAL_CONFIG'));
        $this->assertEquals('should replace', $config->get('KEY1'));
        $this->assertEquals('value2', $config->get('KEY_2'));
        $this->assertEquals('value3', $config->get('KEY3'));
        $this->assertEquals('', $config->get('KEY4'));
        $this->assertTrue($config->get('KEY5'));
        $this->assertSame(10, $config->get('KEY6'));
        $this->assertSame(3.14, $config->get('KEY7'));
        $this->assertSame(["key1" => "value1", "key2" => "value2"], $config->get('KEY8'));
        $this->assertSame(['1', '2', '3', '4', '5'], $config->get('KEY9'));
        $this->assertSame(["key1" => "value1", "key2" => "value2"], $config->get('KEY10'));
    }

    public function testSaveToCacheBeforeChange()
    {
        putenv('APP_ENV=file');
        $config = $this->object->build();

        ;
        $this->assertTrue($config->saveToCache("file", new NoCacheEngine()));
    }

    public function testCannotSaveToCacheAfterChange()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("The configuration was changed. Can't save to cache.");

        putenv('APP_ENV=file');
        $config = $this->object->build();

        $this->assertSame(["key1" => "value1", "key2" => "value2"], $config->get('KEY10'));

        $config->saveToCache("file", new NoCacheEngine());
    }

    public function testMissingCustomParser()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("Parser for 'nonexistent' not found");

        putenv('APP_ENV=file2');
        $definition = (new Definition())
            ->addConfig("file2");

        $config = $definition->build();
        $config->get('KEY11');
    }

}