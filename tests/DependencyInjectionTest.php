<?php

namespace Tests;

use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Config\CacheModeEnum;
use ByJG\Config\DependencyInjection;
use ByJG\Config\Environment;
use ByJG\Config\Definition;
use ByJG\Config\Exception\ConfigException;
use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\DependencyInjectionException;
use ByJG\Config\Exception\KeyNotFoundException;
use ByJG\Config\KeyStatusEnum;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use Tests\DIClasses\Area;
use Tests\DIClasses\ClassWithUnionType;
use Tests\DIClasses\ClassWithUnionType2;
use Tests\DIClasses\InjectedLegacy;
use Tests\DIClasses\Random;
use Tests\DIClasses\RectangleTriangle;
use Tests\DIClasses\Square;
use Tests\DIClasses\SumAreas;
use Tests\DIClasses\TestParam;
use Tests\DIClasses\UnionTypeClass1;
use Tests\DIClasses\UnionTypeClass2;
use PHPUnit\Framework\TestCase;

class DependencyInjectionTest extends TestCase
{
    /**
     * @var Definition
     */
    protected $object;

    protected ?CacheInterface $cache = null;

    /**
     * @throws ConfigException
     */
    #[Override]
    public function setUp(): void
    {
        $this->cache = new FileSystemCacheEngine('cache-test');

        $diTest = new Environment('di-test');
        $diTest2 = new Environment('di-test2');
        $diTest3 = new Environment('di-test3');
        $diTest4 = new Environment('di-test4');
        $diTest5 = new Environment('di-test5', inheritFrom: [$diTest4]);
        $diTest5CacheMultiple = new Environment('di-test5-cache-multiple', inheritFrom: [$diTest4], cache: $this->cache, cacheMode: CacheModeEnum::multipleFiles);
        $diTest5CacheSingle = new Environment('di-test5-cache-single', inheritFrom: [$diTest4], cache: $this->cache, cacheMode: CacheModeEnum::singleFile);
        $diTest6 = new Environment('di-test6', inheritFrom: [$diTest5]);
        $diUnionType = new Environment('di-uniontype');

        $this->object = (new Definition())
            ->addEnvironment($diTest)
            ->addEnvironment($diTest2)
            ->addEnvironment($diTest3)
            ->addEnvironment($diTest4)
            ->addEnvironment($diTest5)
            ->addEnvironment($diTest5CacheMultiple)
            ->addEnvironment($diTest5CacheSingle)
            ->addEnvironment($diTest6)
            ->addEnvironment($diUnionType)
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

    public function testGetLazyInstance()
    {
        $config = $this->object->build('di-test');

        $random = $config->get("Random2");
        $this->assertInstanceOf(DependencyInjection::class, $random);
        $random2 = $random->getInstance(10);
        $this->assertInstanceOf(Random::class, $random2);
        $this->assertEquals(10, $random2->getNumber());
        $random3 = $random->getInstance(30);
        $this->assertInstanceOf(Random::class, $random3);
        $this->assertEquals(30, $random3->getNumber());

        $random4 = $config->get("Random2")->getInstance(10);
        $this->assertInstanceOf(Random::class, $random4);
        $this->assertEquals(10, $random4->getNumber());

        $this->assertNotSame($random2, $random3);
        $this->assertNotSame($random2, $random4);
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

    public function testEagerSingleton()
    {
        $config = $this->object->build('di-test4');

        $this->assertEquals(KeyStatusEnum::STATIC, $config->keyStatus('constnumber'));
        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Square::class));
        $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Random::class));
        $this->assertEquals(KeyStatusEnum::IN_MEMORY, $config->keyStatus(TestParam::class));

        $square = $config->get(Square::class);
        $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Square::class));
    }

    public function testEagerSingletonInherit()
    {
        $config = $this->object->build('di-test5');

        $this->assertEquals(KeyStatusEnum::STATIC, $config->keyStatus('another'));
        $this->assertEquals(KeyStatusEnum::STATIC, $config->keyStatus('constnumber'));
        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Square::class));
        $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Random::class));
        $this->assertEquals(KeyStatusEnum::IN_MEMORY, $config->keyStatus(TestParam::class));

        $square = $config->get(Square::class);
        $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Square::class));
    }

    public function testEagerSingletonInheritDisabling()
    {
        $config = $this->object->build('di-test6');

        $this->assertEquals(KeyStatusEnum::STATIC, $config->keyStatus('another'));
        $this->assertEquals(KeyStatusEnum::STATIC, $config->keyStatus('constnumber'));
        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Square::class));
        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Random::class));
        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(TestParam::class));

        $square = $config->get(TestParam::class);
        $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Random::class));
        $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(TestParam::class));
    }


    public function testEagerSingletonAndCacheMultipleFiles()
    {
        $this->cache->clear();

        // Needs to run twice - one to create the cache and another to use the cache
        for ($i = 0; $i < 10; $i++) {
            $config = $this->object->build('di-test5-cache-multiple');

            $this->assertEquals(KeyStatusEnum::STATIC, $config->keyStatus('constnumber'));
            $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Square::class));
            $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Random::class));
            $this->assertEquals(KeyStatusEnum::IN_MEMORY, $config->keyStatus(TestParam::class));

            $square = $config->get(Square::class);
            $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Square::class));
        }
    }

    public function testEagerSingletonAndCacheSingleFile()
    {
        $this->cache->clear();

        // Needs to run twice - one to create the cache and another to use the cache
        for ($i = 0; $i < 10; $i++) {
            $config = $this->object->build('di-test5-cache-single');

            $this->assertEquals(KeyStatusEnum::STATIC, $config->keyStatus('constnumber'));
            $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Square::class));
            $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Random::class));
            $this->assertEquals(KeyStatusEnum::IN_MEMORY, $config->keyStatus(TestParam::class));

            $square = $config->get(Square::class);
            $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Square::class));
        }
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
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws InvalidArgumentException
     */
    public function testInjectConstructorFail()
    {
        $this->expectException(DependencyInjectionException::class);
        $this->expectExceptionMessage("The class Tests\DIClasses\InjectedFail does not have annotations with the param type");

        $this->object = (new Definition())
            ->addEnvironment(new Environment('di-fail'))
        ;

        $config = $this->object->build("di-fail");
    }

    /**
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws InvalidArgumentException
     */
    public function testInjectConstructorFail2()
    {
        $this->expectException(DependencyInjectionException::class);
        $this->expectExceptionMessage("The parameter '\$area' has no type defined in class 'Tests\DIClasses\InjectedFail'");

        $this->object = (new Definition())
            ->addEnvironment(new Environment('di-fail2'))
        ;

        $config = $this->object->build("di-fail2");
    }

    /**
     * @throws ConfigException
     * @throws ConfigNotFoundException
     * @throws ContainerExceptionInterface
     * @throws DependencyInjectionException
     * @throws InvalidArgumentException
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testGetInstancesFail3_1()
    {
        $this->expectException(KeyNotFoundException::class);
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
     * @throws ConfigException
     * @throws ConfigNotFoundException
     * @throws DependencyInjectionException
     * @throws InvalidArgumentException
     * @throws KeyNotFoundException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetInstancesFail3_2()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessage("The key 'Tests\DIClasses\Area' does not exists injected from 'Tests\DIClasses\InjectedLegacy'");

        $this->object = (new Definition())
            ->addEnvironment(new Environment('di-fail3'))
        ;
        $config = $this->object->build('di-fail3');

        $injectedLegacy = $config->get(InjectedLegacy::class);
        $this->assertInstanceOf(InjectedLegacy::class, $injectedLegacy);
        $this->assertEquals(24, $injectedLegacy->calculate());
    }

    /**
     * Test for the getClassName() method
     *
     * @throws DependencyInjectionException
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws KeyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetClassName()
    {
        $config = $this->object->build('di-test');

        // Test when $use is false (using bind)
        $di = $config->get("Random2");
        $this->assertInstanceOf(DependencyInjection::class, $di);
        $this->assertEquals(Random::class, $di->getClassName());

        // Test when $use is true (using use)
        // The 'Value' key is defined with DI::use(Area::class)
        $value = $config->raw('Value');
        $this->assertInstanceOf(DependencyInjection::class, $value);
        $this->assertEquals(Area::class, $value->getClassName());
    }

    /**
     * Test for union types in constructor parameters
     *
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws DependencyInjectionException
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws InvalidArgumentException
     */
    public function testUnionTypeConstructor()
    {
        $config = $this->object->build('di-uniontype');

        // Get the dependencies
        $class1 = $config->get(UnionTypeClass1::class);
        $this->assertInstanceOf(UnionTypeClass1::class, $class1);
        $this->assertEquals("Class1", $class1->getName());

        $class2 = $config->get(UnionTypeClass2::class);
        $this->assertInstanceOf(UnionTypeClass2::class, $class2);
        $this->assertEquals("Class2", $class2->getName());

        // Get the class with union type
        // It should inject UnionTypeClass1 since it's the first non-builtin type in the union
        $classWithUnion = $config->get(ClassWithUnionType::class);
        $this->assertInstanceOf(ClassWithUnionType::class, $classWithUnion);
        $this->assertEquals("Class1", $classWithUnion->getDependencyName());

        $classWithUnion2 = $config->get(ClassWithUnionType2::class);
        $this->assertInstanceOf(ClassWithUnionType2::class, $classWithUnion2);
        $this->assertEquals("Class2", $classWithUnion2->getDependencyName());
    }
}
