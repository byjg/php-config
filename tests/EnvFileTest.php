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
     * @throws \ByJG\Config\Exception\EnvironmentException
     */
    public function setUp()
    {
        $this->object = (new Definition())
            ->addEnvironment("file");
    }

    public function tearDown()
    {
        putenv('APPLICATION_ENV');
    }

    public function testGetCurrentEnv()
    {
        putenv('APPLICATION_ENV=file');
        $config = $this->object->build();

        $this->assertEquals('value1', $config->get('KEY1'));
        $this->assertEquals('value2', $config->get('KEY_2'));
        $this->assertEquals('value3', $config->get('KEY3'));
        $this->assertEquals('', $config->get('KEY4'));
        $this->assertTrue($config->get('KEY5'));
        $this->assertSame(10, $config->get('KEY6'));
        $this->assertSame(3.14, $config->get('KEY7'));
    }

}