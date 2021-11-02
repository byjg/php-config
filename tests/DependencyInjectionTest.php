<?php

namespace Test;

require_once (__DIR__ . "/DIClasses/Square.php");
require_once (__DIR__ . "/DIClasses/RectangleTriangle.php");
require_once (__DIR__ . "/DIClasses/SumAreas.php");
require_once (__DIR__ . "/DIClasses/Random.php");
require_once (__DIR__ . "/DIClasses/InjectedLegacy.php");
require_once (__DIR__ . "/DIClasses/InjectedFail.php");

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Config\Definition;
use DIClasses\Area;
use DIClasses\InjectedFail;
use DIClasses\InjectedLegacy;
use DIClasses\Random;
use DIClasses\RectangleTriangle;
use DIClasses\Square;
use DIClasses\SumAreas;
use PHPUnit\Framework\TestCase;

class DependencyInjectionTest extends TestCase
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
            ->addEnvironment('di-test')
            ->addEnvironment('di-test2')
            ->addEnvironment('di-test3')
            ->addEnvironment('di-test4')
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

    public function testGetInstancesWithParam()
    {
        $config = $this->object->build('di-test4');

        $square = $config->get(Square::class);

        $this->assertEquals(16, $square->calculate());
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @expectedException \ByJG\Config\Exception\DependencyInjectionException
     * @expectedExceptionMessage The class DIClasses\InjectedFail does not have annotations with the param type
     */
    public function testInjectConstructorFail()
    {
        $this->object = (new Definition())
            ->addEnvironment('di-fail')
        ;

        $config = $this->object->build("di-fail");
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @expectedException \ByJG\Config\Exception\DependencyInjectionException
     * @expectedExceptionMessage The parameter '$area' has no type defined in class 'DIClasses\InjectedFail'
     */
    public function testInjectConstructorFail2()
    {
        $this->object = (new Definition())
            ->addEnvironment('di-fail2')
        ;

        $config = $this->object->build("di-fail2");
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\DependencyInjectionException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @expectedException \ByJG\Config\Exception\KeyNotFoundException
     * @expectedExceptionMessage The key 'DIClasses\Area' does not exists injected from 'DIClasses\SumAreas'
     */
    public function testGetInstancesFail3_1()
    {
        $this->object = (new Definition())
            ->addEnvironment('di-fail3')
        ;
        $config = $this->object->build('di-fail3');

        $sumAreas = $config->get(SumAreas::class);
        $this->assertInstanceOf(SumAreas::class, $sumAreas);
        $this->assertEquals(24, $sumAreas->calculate());
    }

    /**
     * @throws \ByJG\Config\Exception\ConfigNotFoundException
     * @throws \ByJG\Config\Exception\DependencyInjectionException
     * @throws \ByJG\Config\Exception\EnvironmentException
     * @throws \ByJG\Config\Exception\KeyNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     * @expectedException \ByJG\Config\Exception\KeyNotFoundException
     * @expectedExceptionMessage The key 'DIClasses\Area' does not exists injected from 'DIClasses\InjectedLegacy'
     */
    public function testGetInstancesFail3_2()
    {
        $this->object = (new Definition())
            ->addEnvironment('di-fail3')
        ;
        $config = $this->object->build('di-fail3');

        $injectedLegacy = $config->get(InjectedLegacy::class);
        $this->assertInstanceOf(InjectedLegacy::class, $injectedLegacy);
        $this->assertEquals(24, $injectedLegacy->calculate());
    }


}
