<?php

namespace Test;

use ByJG\Cache\Psr16\ArrayCacheEngine;
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
        $this->assertEquals('value1', $config->get('KEY1'));
        $this->assertEquals('value2', $config->get('KEY_2'));
        $this->assertEquals('value3', $config->get('KEY3'));
        $this->assertEquals('', $config->get('KEY4'));
        $this->assertTrue($config->get('KEY5'));
        $this->assertSame(10, $config->get('KEY6'));
        $this->assertSame(3.14, $config->get('KEY7'));
    }

}