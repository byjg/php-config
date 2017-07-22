<?php

namespace ByJG\Util;

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Config\Definition;
use PHPUnit\Framework\TestCase;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class ContainerTest extends TestCase
{
    /**
     * @var \ByJG\Config\Definition
     */
    protected $object;

    public function setUp()
    {
        $this->object = (new Definition())
            ->addEnvironment('test')
            ->addEnvironment('test2')
                ->inheritFrom('test')
            ->addEnvironment('closure')
            ->addEnvironment('notfound');
    }

    public function tearDown()
    {
        putenv('APPLICATION_ENV');
    }

    public function testGetCurrentEnv()
    {
        putenv('APPLICATION_ENV=test');
        $this->assertEquals("test", $this->object->getCurrentEnv());

        putenv('APPLICATION_ENV=bla');
        $this->assertEquals("bla", $this->object->getCurrentEnv());
    }

    public function testLoadConfig()
    {
        $config = $this->object->build('test');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertTrue($config->get('property2'));

        $closure = $config->get('property3');
        $this->assertEquals('calculated', $closure());
        // $this->assertEmpty($config->get('property4', false));
    }

    public function testLoadConfig2()
    {
        $config = $this->object->build('test2');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));

        $closure = $config->get('property3');
        $this->assertEquals('calculated', $closure());
        $this->assertEquals('new', $config->get('property4'));
    }

    public function testLoadConfig3()
    {
        putenv('APPLICATION_ENV=test');
        $config = $this->object->build();
        $this->assertEquals('string', $config->get('property1'));
        $this->assertTrue($config->get('property2'));

        putenv('APPLICATION_ENV=test2');
        $config2 = $this->object->build();
        $this->assertEquals('string', $config2->get('property1'));
        $this->assertFalse($config2->get('property2'));
    }

    public function testLoadConfigArgs()
    {
        $config = $this->object->build('closure');

        $result = $config->getClosure('closureProp', 'value1', 'value2');
        $this->assertEquals('value1:value2', $result);

        $result2 = $config->getClosure('closureProp', ['valueA', 'valueB']);
        $this->assertEquals('valueA:valueB', $result2);

        $result3 = $config->getClosure('closureProp2', null);
        $this->assertEquals('No Param', $result3);

        $result4 = $config->getClosure('closureProp2', []);
        $this->assertEquals('No Param', $result4);
    }

    /**
     * @expectedException \Exception
     */
    public function testLoadConfigNotExistant()
    {
        $config = $this->object->build('test');

        $config->get('property4');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadConfigNotExistant2()
    {
        $this->object->build('notset');
    }

    /**
     * @expectedException \Psr\Container\NotFoundExceptionInterface
     */
    public function testLoadConfigNotExistant3()
    {
        $this->object->build('notfound');
    }

    public function testCache()
    {
        // With Cache!
        $arrayCache = new ArrayCacheEngine();

        $container = $this->object->setCache($arrayCache)
            ->build('test');  // Expected build and set to cache

        $container2 = (new Definition())
            ->addEnvironment('test')
            ->addEnvironment('test2')
            ->inheritFrom('test')
            ->addEnvironment('closure')
            ->addEnvironment('notfound')
            ->setCache($arrayCache)
            ->build('test');   // Expected get from cache

        $this->assertSame($container, $container2); // The exact object

        // Without cache
        $container3 = (new Definition())
            ->addEnvironment('test')
            ->addEnvironment('test2')
            ->inheritFrom('test')
            ->addEnvironment('closure')
            ->addEnvironment('notfound')
            ->build('test');

        $this->assertNotSame($container, $container3);  // There two different objects
    }

    public function testChangeEnvironmentVariable()
    {
        putenv('NEWENV=test');
        $this->object->environmentVar('NEWENV');
        $this->assertEquals("test", $this->object->getCurrentEnv());
    }
}
