<?php


namespace Test;

use ByJG\Config\Config;
use ByJG\Config\Definition;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testSimple()
    {
        $config = new Config('test');

        $this->assertEquals('test', $config->getName());
        $this->assertEquals([], $config->getInheritFrom());
        $this->assertFalse($config->isAbstract());
        $this->assertFalse($config->isFinal());
    }

    public function testAbstract()
    {
        $config = new Config('test', [], null, true);

        $this->assertEquals('test', $config->getName());
        $this->assertEquals([], $config->getInheritFrom());
        $this->assertTrue($config->isAbstract());
        $this->assertFalse($config->isFinal());
    }

    public function testFinal()
    {
        $config = new Config('test', [], null, false, true);

        $this->assertEquals('test', $config->getName());
        $this->assertEquals([], $config->getInheritFrom());
        $this->assertFalse($config->isAbstract());
        $this->assertTrue($config->isFinal());
    }

    public function testInheritance()
    {
        $config = new Config('test');
        $config2 = new Config('test2', [$config]);
        $config3 = new Config('test3', [$config2]);

        $this->assertEquals('test', $config->getName());
        $this->assertEquals('test2', $config2->getName());
        $this->assertEquals('test3', $config3->getName());

        $this->assertEquals([], $config->getInheritFrom());
        $this->assertEquals([$config], $config2->getInheritFrom());
        $this->assertEquals([$config2], $config3->getInheritFrom());

        $this->assertFalse($config->isAbstract());
        $this->assertFalse($config->isFinal());
        $this->assertFalse($config2->isAbstract());
        $this->assertFalse($config2->isFinal());
        $this->assertFalse($config3->isAbstract());
        $this->assertFalse($config3->isFinal());
    }

    public function testNotAllowInheritFinal()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("The item 'test' is final and cannot be inherited");

        $config = new Config('test', [], null, false, true);
        $config2 = new Config('test2', [$config]);
    }

    public function testNotAllowInheritNonConfig()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("The item 'test' is not a Config object");

        $config = new Config('test', [], null, false, true);
        $config2 = new Config('test2', ['test']);
    }

    public function testNotAllowCreateDefinitionFromAbstract()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("Configuration 'test' is abstract and cannot be instantiated");

        $config = new Config('test', [], null, true);

        putenv('APP_ENV=test');
        $_ENV['APP_ENV'] = 'test';
        $defintion = (new Definition())
            ->addConfig($config);

        $defintion->build();
    }

}
