<?php


namespace Test;

use ByJG\Config\Environment;
use ByJG\Config\Definition;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testSimple()
    {
        $environment = new Environment('test');

        $this->assertEquals('test', $environment->getName());
        $this->assertEquals([], $environment->getInheritFrom());
        $this->assertFalse($environment->isAbstract());
        $this->assertFalse($environment->isFinal());
    }

    public function testAbstract()
    {
        $environment = new Environment('test', [], null, true);

        $this->assertEquals('test', $environment->getName());
        $this->assertEquals([], $environment->getInheritFrom());
        $this->assertTrue($environment->isAbstract());
        $this->assertFalse($environment->isFinal());
    }

    public function testFinal()
    {
        $environment = new Environment('test', [], null, false, true);

        $this->assertEquals('test', $environment->getName());
        $this->assertEquals([], $environment->getInheritFrom());
        $this->assertFalse($environment->isAbstract());
        $this->assertTrue($environment->isFinal());
    }

    public function testInheritance()
    {
        $environment = new Environment('test');
        $environment2 = new Environment('test2', [$environment]);
        $environment3 = new Environment('test3', [$environment2]);

        $this->assertEquals('test', $environment->getName());
        $this->assertEquals('test2', $environment2->getName());
        $this->assertEquals('test3', $environment3->getName());

        $this->assertEquals([], $environment->getInheritFrom());
        $this->assertEquals([$environment], $environment2->getInheritFrom());
        $this->assertEquals([$environment2], $environment3->getInheritFrom());

        $this->assertFalse($environment->isAbstract());
        $this->assertFalse($environment->isFinal());
        $this->assertFalse($environment2->isAbstract());
        $this->assertFalse($environment2->isFinal());
        $this->assertFalse($environment3->isAbstract());
        $this->assertFalse($environment3->isFinal());
    }

    public function testNotAllowInheritFinal()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("The item 'test' is final and cannot be inherited");

        $environment = new Environment('test', [], null, false, true);
        $environment2 = new Environment('test2', [$environment]);
    }

    public function testNotAllowInheritNonConfig()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("The item 'test' is not a Config object");

        $environment = new Environment('test', [], null, false, true);
        $environment2 = new Environment('test2', ['test']);
    }

    public function testNotAllowCreateDefinitionFromAbstract()
    {
        $this->expectException(\ByJG\Config\Exception\ConfigException::class);
        $this->expectExceptionMessage("Configuration 'test' is abstract and cannot be instantiated");

        $environment = new Environment('test', [], null, true);

        putenv('APP_ENV=test');
        $_ENV['APP_ENV'] = 'test';
        $defintion = (new Definition())
            ->addEnvironment($environment);

        $defintion->build();
    }

}
