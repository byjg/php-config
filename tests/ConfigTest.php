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

    /**
     * @expectedException \Exception
     */
    public function testLoadConfigNotExistant()
    {
        putenv('APPLICATION_ENV=test');

        Config::get('property4');
    }
}
