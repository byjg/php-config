<?php

namespace ByJG\Util;

require_once (__DIR__ . "/DIClasses/Square.php");
require_once (__DIR__ . "/DIClasses/RectangleTriangle.php");

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Config\Definition;
use DIClasses\RectangleTriangle;
use DIClasses\Square;
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
        ;
    }

    public function tearDown()
    {
        putenv('APPLICATION_ENV');
    }

    public function testGetInstances()
    {
        $config = $this->object->build('di-test');

        $square = $config->get(Square::class);
        $this->assertInstanceOf(Square::class, $square);
        $this->assertEquals(16, $square->calculate());

        $triangule = $config->get(RectangleTriangle::class);
        $this->assertInstanceOf(RectangleTriangle::class, $triangule);
        $this->assertEquals(6, $triangule->calculate());
    }
}
