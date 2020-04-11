<?php

namespace Test;

require_once (__DIR__ . "/DIClasses/Square.php");
require_once (__DIR__ . "/DIClasses/RectangleTriangle.php");
require_once (__DIR__ . "/DIClasses/SumAreas.php");
require_once (__DIR__ . "/DIClasses/Random.php");

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Config\Definition;
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
        ;
    }

    public function testGetInstances()
    {
        $config = $this->object->build('di-test');

        $square = $config->get(Square::class);
        $this->assertInstanceOf(Square::class, $square);
        $this->assertEquals(16, $square->calculate());

        $triangle = $config->get(RectangleTriangle::class);
        $this->assertInstanceOf(RectangleTriangle::class, $triangle);
        $this->assertEquals(6, $triangle->calculate());

        $sumAreas = $config->get(SumAreas::class);
        $this->assertInstanceOf(SumAreas::class, $sumAreas);
        $this->assertEquals(22, $sumAreas->calculate());
    }

    public function testGetInstancesControl()
    {
        $config = $this->object->build('di-test2');

        $random = $config->get("control");
        $random2 = $config->get("control");

        $this->assertNotSame($random, $random2);
        $this->assertNotEquals($random->calculate(), $random2->calculate());
    }

    public function testGetInstancesSingleton()
    {
        $config = $this->object->build('di-test2');

        $random = $config->get(Random::class);
        $this->assertInstanceOf(Random::class, $random);
        $randomCalc = $random->calculate();

        $random2 = $config->get(Random::class);
        $this->assertInstanceOf(Random::class, $random2);
        $randomCalc2 = $random->calculate();

        $this->assertEquals($randomCalc, $randomCalc2);
        $this->assertSame($random, $random2);
    }

    public function testGetInstancesWithParam()
    {
        $config = $this->object->build('di-test2');

        $sumAreas = $config->get(SumAreas::class);

        $this->assertInstanceOf(SumAreas::class, $sumAreas);
    }

    public function testWithMethodCall()
    {
        $config = $this->object->build('di-test3');

        $random = $config->get(Random::class);
        $this->assertInstanceOf(Random::class, $random);
        $this->assertEquals(10, $random->calculate());
    }

    public function testWithFactoryMethod()
    {
        $config = $this->object->build('di-test3');

        $random = $config->get("factory");
        $this->assertInstanceOf(Random::class, $random);
        $this->assertEquals(20, $random->calculate());
    }

}
