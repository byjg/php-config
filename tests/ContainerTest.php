<?php

namespace ByJG\Util;

// backward compatibility
use ByJG\Config\Definition;

if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class ContainerTest extends \PHPUnit\Framework\TestCase
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
            ->addEnvironment('closure');
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
     * @expectedException \Psr\Container\NotFoundExceptionInterface
     */
    public function testLoadConfigNotExistant2()
    {
        $config = $this->object->build('test');

        $config->get('property4');
    }
}
