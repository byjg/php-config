<?php

namespace Test;

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Config\Definition;
use ByJG\Config\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var Definition
     */
    protected $object;

    /**
     * @throws \ByJG\Config\Exception\ConfigException
     */
    public function setUp(): void
    {
        $this->object = (new Definition())
            ->addConfig('test')
            ->addConfig('test2')
                ->inheritFrom('test')
            ->addConfig('test3')
                ->inheritFrom('test2')
                ->inheritFrom('test')
            ->addConfig('closure')
            ->addConfig('notfound')
        ;
    }

    public function tearDown(): void
    {
        putenv('APP_ENV');
    }

    public function testgetCurrentConfig()
    {
        putenv('APP_ENV=test');

        $this->assertEquals("test", $this->object->getCurrentConfig());

        putenv('APP_ENV=bla');
        $this->assertEquals("bla", $this->object->getCurrentConfig());
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigException
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testgetCurrentConfig2()
    {
        $this->object->build("test2");
        $this->assertEquals("test2", $this->object->getCurrentConfig());
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigException
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testgetCurrentConfig3()
    {
        putenv('APP_ENV=test');
        $this->object->build("test2");
        $this->assertEquals("test2", $this->object->getCurrentConfig());
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigException
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testgetCurrentConfig4()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("The environment variable 'APP_ENV' is not set");

        $this->object->getCurrentConfig();
    }

    public function testLoadConfig()
    {
        $config = $this->object->build('test');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertTrue($config->get('property2'));

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('test', $config->get('property5'));
    }

    public function testLoadConfig2()
    {
        $config = $this->object->build('test2');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('new', $config->get('property4'));

        $this->assertEquals('test2', $config->get('property5'));
    }

    public function testLoadConfigMultipleInherits()
    {
        $config = $this->object->build('test3');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('new', $config->get('property4'));

        $this->assertEquals('test3', $config->get('property5'));
    }

    public function testLoadConfig3()
    {
        putenv('APP_ENV=test');
        $config = $this->object->build();
        $this->assertEquals('string', $config->get('property1'));
        $this->assertTrue($config->get('property2'));

        putenv('APP_ENV=test2');
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

    public function testLoadConfigNotExistant()
    {
        $this->expectException(\ByJG\Config\Exception\KeyNotFoundException::class);
        $this->expectExceptionMessage("The key 'property4' does not exists");

        $config = $this->object->build('test');

        $config->get('property4');
    }

    public function testLoadConfigNotExistant2()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("Configuration 'notset' does not defined");

        $this->object->build('notset');
    }

    public function testLoadConfigNotExistant3()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigNotFoundException::class);
        $this->expectExceptionMessage("Configuration 'config-notfound.php' or 'config-notfound.env' could not found");

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
            ->addConfig('test')
            ->addConfig('test2')
            ->inheritFrom('test')
            ->addConfig('closure')
            ->addConfig('notfound')
            ->setCache($arrayCache, 'test')
            ->build('test');   // Expected get from cache

        $this->assertNotNull($arrayCache->get('container-cache-test'));
        $this->assertSame($container, $container2); // The exact object

        $container3 = (new Definition())
            ->addConfig('test')
            ->addConfig('test2')
            ->inheritFrom('test')
            ->addConfig('closure')
            ->addConfig('notfound')
            ->setCache($arrayCache, 'test2')
            ->build('test');   // Expected get a fresh new defintion

        $this->assertNotSame($container, $container3); // Expected to be a different object

        // Without cache
        $container4 = (new Definition())
            ->addConfig('test')
            ->addConfig('test2')
            ->inheritFrom('test')
            ->addConfig('closure')
            ->addConfig('notfound')
            ->build('test');

        $this->assertNotSame($container, $container4);  // There two different objects
    }

    public function testChangeConfigVar()
    {
        $container = $this->object->build('test');

        putenv('NEWENV=test');
        $this->object->withConfigVar('NEWENV');
        $this->assertEquals("test", $this->object->getCurrentConfig());

        $container2 = $this->object->build();
        $this->assertEquals($container, $container2);
    }
}
