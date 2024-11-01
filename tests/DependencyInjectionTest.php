<?php

namespace Tests;

use ByJG\Config\Environment;
use ByJG\Config\Definition;
use Tests\DIClasses\Area;
use Tests\DIClasses\InjectedLegacy;
use Tests\DIClasses\Random;
use Tests\DIClasses\RectangleTriangle;
use Tests\DIClasses\Square;
use Tests\DIClasses\SumAreas;
use Tests\DIClasses\TestParam;
use PHPUnit\Framework\TestCase;

class DependencyInjectionTest extends TestCase
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
        $diTest = new Environment('di-test');
        $diTest2 = new Environment('di-test2');
        $diTest3 = new Environment('di-test3');
        $diTest4 = new Environment('di-test4');

        $this->object = (new Definition())
            ->addEnvironment($diTest)
            ->addEnvironment($diTest2)
            ->addEnvironment($diTest3)
            ->addEnvironment($diTest4)
        ;
    }

    public function testGetInstances()
    {
        $config = $this->object->build('di-test');

        $random = $config->get(Random::class);
        $this->assertInstanceOf(Random::class, $random);
        $this->assertEquals(4, $random->getNumber());

        $triangle = $config->get(Area::class);
        $this->assertInstanceOf(RectangleTriangle::class, $triangle);
        $this->assertEquals(6, $triangle->calculate());

        $sumAreas = $config->get(SumAreas::class);
        $this->assertInstanceOf(SumAreas::class, $sumAreas);
        $this->assertEquals(24, $sumAreas->calculate());

        $injectedLegacy = $config->get(InjectedLegacy::class);
        $this->assertInstanceOf(InjectedLegacy::class, $injectedLegacy);
        $this->assertEquals(24, $injectedLegacy->calculate());
    }

    public function testGetInstancesControl()
    {
        $config = $this->object->build('di-test2');

        $random = $config->get("control");
        $random2 = $config->get("control");

        $this->assertNotSame($random, $random2);
        $this->assertNotEquals($random->getNumber(), $random2->getNumber());
    }

    public function testGetInstancesSingleton()
    {
        $config = $this->object->build('di-test2');

        $random = $config->get(Random::class);
        $this->assertInstanceOf(Random::class, $random);
        $randomCalc = $random->getNumber();

        $random2 = $config->get(Random::class);
        $this->assertInstanceOf(Random::class, $random2);
        $randomCalc2 = $random->getNumber();

        $this->assertEquals($randomCalc, $randomCalc2);
        $this->assertSame($random, $random2);
    }

    public function testWithMethodCall()
    {
        $config = $this->object->build('di-test3');

        $random = $config->get(Random::class);
        $this->assertInstanceOf(Random::class, $random);
        $this->assertEquals(10, $random->getNumber());
    }

    public function testWithFactoryMethod()
    {
        $config = $this->object->build('di-test3');

        $random = $config->get("factory");
        $this->assertInstanceOf(Random::class, $random);
        $this->assertEquals(20, $random->getNumber());
    }

    public function testWithMethodCallSinglenton()
    {
        $config = $this->object->build('di-test3');

        $random = $config->get('random2');
        $this->assertInstanceOf(Random::class, $random);
        $this->assertEquals(30, $random->getNumber());
    }

    public function testWithFactoryMethodSingleton()
    {
        $config = $this->object->build('di-test3');

        $random = $config->get("factory2");
        $this->assertInstanceOf(Random::class, $random);
        $this->assertEquals(40, $random->getNumber());
    }

    public function testReleaseSingleton()
    {
        $config = $this->object->build('di-test3');

        // Sanity check to verify if a non-singleton always return a new object
        $instance1 = $config->get("factory");
        $this->assertInstanceOf(Random::class, $instance1);

        $instance2 = $config->get("factory");
        $this->assertInstanceOf(Random::class, $instance2);

        $this->assertNotSame($instance1, $instance2);

        // Check if Singleton is returning the same object
        $singleton1 = $config->get("factory2");
        $this->assertInstanceOf(Random::class, $singleton1);

        $singleton2 = $config->get("factory2");
        $this->assertInstanceOf(Random::class, $singleton2);

        $this->assertSame($singleton1, $singleton2);

        // Release the singleton and check if a new is created
        $config->releaseSingletons();

        $this->assertEquals(40, $singleton1->getNumber()); // Make sure the local variable is running

        $singleton3 = $config->get("factory2");
        $this->assertInstanceOf(Random::class, $singleton3);

        $this->assertNotSame($singleton1, $singleton3);
    }


    public function testGetInstancesWithParam()
    {
        $config = $this->object->build('di-test4');

        $square = $config->get(Square::class);

        $this->assertEquals(16, $square->calculate());
    }

    public function testGetInstancesWithParam2()
    {
        $config = $this->object->build('di-test4');

        $test = $config->get(TestParam::class);

        $this->assertTrue($test->isOk());
    }

    public function testUse()
    {
        $config = $this->object->build('di-test');

        $this->assertEquals(6, $config->get('Value'));
    }
    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\ConfigException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testInjectConstructorFail()
    {
        $this->expectException(\ByJG\Config\Exception\DependencyInjectionException::class);
        $this->expectExceptionMessage("The class Tests\DIClasses\InjectedFail does not have annotations with the param type");

        $this->object = (new Definition())
            ->addEnvironment(new Environment('di-fail'))
        ;

        $config = $this->object->build("di-fail");
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\ConfigException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function testInjectConstructorFail2()
    {
        $this->expectException(\ByJG\Config\Exception\DependencyInjectionException::class);
        $this->expectExceptionMessage("The parameter '\$area' has no type defined in class 'Tests\DIClasses\InjectedFail'");

        $this->object = (new Definition())
            ->addEnvironment(new Environment('di-fail2'))
        ;

        $config = $this->object->build("di-fail2");
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\DependencyInjectionException
     * @throws \ByJG\Config\Exception\ConfigException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function testGetInstancesFail3_1()
    {
        $this->expectException(\ByJG\Config\Exception\KeyNotFoundException::class);
        $this->expectExceptionMessage("The key 'Tests\DIClasses\Area' does not exists injected from 'Tests\DIClasses\SumAreas'");

        $this->object = (new Definition())
            ->addEnvironment(new Environment('di-fail3'))
        ;
        $config = $this->object->build('di-fail3');

        $sumAreas = $config->get(SumAreas::class);
        $this->assertInstanceOf(SumAreas::class, $sumAreas);
        $this->assertEquals(24, $sumAreas->calculate());
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\DependencyInjectionException
     * @throws \ByJG\Config\Exception\ConfigException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function testGetInstancesFail3_2()
    {
        $this->expectException(\ByJG\Config\Exception\KeyNotFoundException::class);
        $this->expectExceptionMessage("The key 'Tests\DIClasses\Area' does not exists injected from 'Tests\DIClasses\InjectedLegacy'");

        $this->object = (new Definition())
            ->addEnvironment(new Environment('di-fail3'))
        ;
        $config = $this->object->build('di-fail3');

        $injectedLegacy = $config->get(InjectedLegacy::class);
        $this->assertInstanceOf(InjectedLegacy::class, $injectedLegacy);
        $this->assertEquals(24, $injectedLegacy->calculate());
    }

}
