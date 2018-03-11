<?php

namespace ByJG\Util;

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Config\Definition;
use PHPUnit\Framework\TestCase;

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

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());
    }

    public function testLoadConfig2()
    {
        $config = $this->object->build('test2');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));

        $closure = $config->raw('property3');
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

        $result = $config->get('closureProp', 'value1', 'value2');
        $this->assertEquals('value1:value2', $result);

        $result2 = $config->get('closureProp', ['valueA', 'valueB']);
        $this->assertEquals('valueA:valueB', $result2);

        $result3 = $config->get('closureProp2', null);
        $this->assertEquals('No Param', $result3);

        $result4 = $config->get('closureProp2', []);
        $this->assertEquals('No Param', $result4);

        $result5 = $config->get('closureArray', [['a', 'b']]);
        $this->assertTrue($result5);

        $result5 = $config->get('closureArray', 'string');
        $this->assertFalse($result5);

        $result6 = $config->get('closureWithoutArgs');
        $this->assertTrue($result6);
    }

    /**
     * @expectedException \ByJG\Config\Exception\KeyNotFoundException
     * @expectedExceptionMessage The key 'property4' does not exists
     */
    public function testLoadConfigNotExistant()
    {
        $config = $this->object->build('test');

        $config->get('property4');
    }

    /**
     * @expectedException \ByJG\Config\Exception\EnvironmentException
     * @expectedExceptionMessage Environment 'notset' does not defined
     */
    public function testLoadConfigNotExistant2()
    {
        $this->object->build('notset');
    }

    /**
     * @expectedException \ByJG\Config\Exception\ConfigNotFoundException
     * @expectedExceptionMessage The config file 'config-notfound.php' does not found at
     */
    public function testLoadConfigNotExistant3()
    {
        $this->object->build('notfound');
    }

    public function testCache()
    {
        // With Cache!
        $arrayCache = new ArrayCacheEngine();

        $this->assertNull($arrayCache->get('container-cache-test'));

        $container = $this->object->setCache($arrayCache, 'test')
            ->build('test');  // Expected build and set to cache

        $container2 = (new Definition())
            ->addEnvironment('test')
            ->addEnvironment('test2')
            ->inheritFrom('test')
            ->addEnvironment('closure')
            ->addEnvironment('notfound')
            ->setCache($arrayCache, 'test')
            ->build('test');   // Expected get from cache

        $this->assertNotNull($arrayCache->get('container-cache-test'));
        $this->assertSame($container, $container2); // The exact object

        $container3 = (new Definition())
            ->addEnvironment('test')
            ->addEnvironment('test2')
            ->inheritFrom('test')
            ->addEnvironment('closure')
            ->addEnvironment('notfound')
            ->setCache($arrayCache, 'test2')
            ->build('test');   // Expected get a fresh new defintion

        $this->assertNotSame($container, $container3); // Expected to be a different object

        // Without cache
        $container4 = (new Definition())
            ->addEnvironment('test')
            ->addEnvironment('test2')
            ->inheritFrom('test')
            ->addEnvironment('closure')
            ->addEnvironment('notfound')
            ->build('test');

        $this->assertNotSame($container, $container4);  // There two different objects
    }

    public function testChangeEnvironmentVariable()
    {
        $container = $this->object->build('test');

        putenv('NEWENV=test');
        $this->object->environmentVar('NEWENV');
        $this->assertEquals("test", $this->object->getCurrentEnv());

        $container2 = $this->object->build();
        $this->assertEquals($container, $container2);
    }
}
