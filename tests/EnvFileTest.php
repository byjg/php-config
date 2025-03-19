<?php

namespace Tests;

use ByJG\Cache\Psr16\NoCacheEngine;
use ByJG\Config\Environment;
use ByJG\Config\Definition;
use ByJG\Config\Exception\ConfigException;
use Exception;
use Override;
use PHPUnit\Framework\TestCase;

class EnvFileTest extends TestCase
{
    /**
     * @var Definition
     */
    protected $object;

    /**
     * @throws ConfigException
     */
    #[Override]
    public function setUp(): void
    {
        $this->object = (new Definition())
            ->addEnvironment(new Environment('file'));
    }

    #[Override]
    public function tearDown(): void
    {
        putenv('APP_ENV');
    }

    public function testgetCurrentEnvironment()
    {
        putenv('APP_ENV=file');
        $config = $this->object->build();

        $this->assertEquals('ok', $config->get('GLOBAL_CONFIG'));
        $this->assertEquals('should replace', $config->get('KEY1'));
        $this->assertEquals('value2', $config->get('KEY_2'));
        $this->assertEquals(' value3 ', $config->get('KEY3'));
        $this->assertEquals('', $config->get('KEY4'));
        $this->assertTrue($config->get('KEY5'));
        $this->assertSame(10, $config->get('KEY6'));
        $this->assertSame(3.14, $config->get('KEY7'));
        $this->assertSame(["key1" => "value1", "key2" => "value2"], $config->get('KEY8'));
        $this->assertSame(['1', '2', '3', '4', '5'], $config->get('KEY9'));
        $this->assertSame(["key1" => "value1", "key2" => "value2"], $config->get('KEY10'));
        $this->assertFalse($config->get('KEY11'));
        $this->assertEquals("Test\nTest\n", $config->get('KEY12'));
        $this->assertEquals("Test2\\nTest2\\n", $config->get('KEY13'));
        $this->assertEquals("file\ncontent\n", $config->get('KEY14'));
    }

    public function testSaveToCacheBeforeChange()
    {
        putenv('APP_ENV=file');
        $config = $this->object->build();

        $this->assertTrue($config->saveToCache("file", new NoCacheEngine()));
    }

    public function testCannotSaveToCacheAfterChange()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The configuration was changed. Can't save to cache.");

        putenv('APP_ENV=file');
        $config = $this->object->build();

        $this->assertSame(["key1" => "value1", "key2" => "value2"], $config->get('KEY10'));

        $config->saveToCache("file", new NoCacheEngine());
    }

    public function testMissingCustomParser()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("Parser for 'nonexistent' not found");

        putenv('APP_ENV=file2');
        $definition = (new Definition())
            ->addEnvironment(new Environment("file2"));

        $config = $definition->build();
        $config->get('KEY11');
    }

}
