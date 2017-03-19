<?php

namespace ByJG\Util;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        Config::reset();
    }

    public function tearDown()
    {
        putenv('APPLICATION_ENV');
    }

    public function testLoadConfig()
    {
        putenv('APPLICATION_ENV=test');

        $this->assertEquals('string', Config::get('property1'));
        $this->assertTrue(Config::get('property2'));

        $closure = Config::get('property3');
        $this->assertEquals('calculated', $closure());
        $this->assertEmpty(Config::get('property4', false));
    }

    public function testLoadConfig2()
    {
        putenv('APPLICATION_ENV=test2');

        $this->assertEquals('string', Config::get('property1'));
        $this->assertFalse(Config::get('property2'));

        $closure = Config::get('property3');
        $this->assertEquals('calculated', $closure());
        $this->assertEquals('new', Config::get('property4'));
    }

    public function testLoadConfigArgs()
    {
        putenv('APPLICATION_ENV=closure');

        $result = Config::getArgs('closureProp', 'value1', 'value2');
        $this->assertEquals('value1:value2', $result);

        $result2 = Config::getArgs('closureProp', ['valueA', 'valueB']);
        $this->assertEquals('valueA:valueB', $result2);

        $result3 = Config::getArgs('closureProp2', null);
        $this->assertEquals('No Param', $result3);

        $result4 = Config::getArgs('closureProp2', []);
        $this->assertEquals('No Param', $result4);
    }

    /**
     * @expectedException \Exception
     */
    public function testLoadConfigNotExistant()
    {
        putenv('APPLICATION_ENV=test');

        Config::get('property4');
    }

    public function testLoadConfigNotExistant2()
    {
        putenv('APPLICATION_ENV=test');

        $this->assertEmpty(Config::get('property4', false));
    }
}
