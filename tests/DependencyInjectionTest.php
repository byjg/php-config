<?php

namespace Tests;

use ArrayObject;
use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Config\CacheModeEnum;
use ByJG\Config\Autowire;
use ByJG\Config\Container;
use ByJG\Config\ContainerParam;
use ByJG\Config\DependencyInjection;
use ByJG\Config\Environment;
use ByJG\Config\Definition;
use ByJG\Config\LazyParam;
use ByJG\Config\Param;
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
use Tests\DIClasses\ClassWithIntersectionType;
use Tests\DIClasses\ClassWithUnionType;
use Tests\DIClasses\ClassWithUnionType2;
use Tests\DIClasses\Autowired\OverriddenController;
use Tests\DIClasses\Autowired\PlainController;
use Tests\DIClasses\Autowired\WidgetController;
use Tests\DIClasses\ContainerAware;
use Tests\DIClasses\NotAController;
use Tests\DIClasses\EagerClass;
use Tests\DIClasses\InjectedLegacy;
use Tests\DIClasses\MixedDependencies;
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
        $diTestLazy = new Environment('di-test-lazy');
        $diTestOverrides = new Environment('di-test-overrides');
        $diTestOverridesFail = new Environment('di-test-overrides-fail');
        $diContainer = new Environment('di-container');
        $diAutowire = new Environment('di-autowire');
        $diAutowireCache = new Environment('di-autowire-cache', inheritFrom: [$diAutowire], cache: $this->cache, cacheMode: CacheModeEnum::multipleFiles);
        $diContainerCache = new Environment('di-container-cache', inheritFrom: [$diContainer], cache: $this->cache, cacheMode: CacheModeEnum::multipleFiles);

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
            ->addEnvironment($diTestLazy)
            ->addEnvironment($diTestOverrides)
            ->addEnvironment($diTestOverridesFail)
            ->addEnvironment($diContainer)
            ->addEnvironment($diContainerCache)
            ->addEnvironment($diAutowire)
            ->addEnvironment($diAutowireCache)
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

    public function testGetLazyInstanceWithDirectArgs()
    {
        $config = $this->object->build('di-test');

        // New preferred way: pass arguments directly to get()
        $random1 = $config->get("Random2", 5);
        $this->assertInstanceOf(Random::class, $random1);
        $this->assertEquals(5, $random1->getNumber());

        $random2 = $config->get("Random2", 15);
        $this->assertInstanceOf(Random::class, $random2);
        $this->assertEquals(15, $random2->getNumber());

        // Each call returns a new instance
        $this->assertNotSame($random1, $random2);
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

    public function testLazyParamWithEagerSingleton()
    {
        $config = $this->object->build('di-test-lazy');

        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Area::class));
        $this->assertEquals(KeyStatusEnum::IN_MEMORY, $config->keyStatus(EagerClass::class));

        $instance = $config->get(EagerClass::class);
        $this->assertInstanceOf(EagerClass::class, $instance);
        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Area::class));

        $areaProxy = $instance::getArea();
        $this->assertEquals(KeyStatusEnum::NOT_USED, $config->keyStatus(Area::class));

        $this->assertEquals(6, $areaProxy->calculate());
        $this->assertEquals(KeyStatusEnum::WAS_USED, $config->keyStatus(Area::class));
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
        $this->expectExceptionMessage("The parameter '\$area' has no type defined and no override provided in class 'Tests\DIClasses\InjectedFail'");

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

    /**
     * Test withInjectedConstructorOverrides with single override
     *
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function testWithInjectedConstructorOverridesSingleParameter()
    {
        $config = $this->object->build('di-test-overrides');

        $mixed = $config->get('mixed1');
        $this->assertInstanceOf(MixedDependencies::class, $mixed);

        // Check that auto-injected dependencies work
        $this->assertEquals(42, $mixed->getRandomNumber());
        $this->assertEquals(25, $mixed->getArea()->calculate()); // 5 * 10 / 2 = 25

        // Check that overridden parameter works
        $this->assertEquals('my-secret-key', $mixed->getApiKey());

        // Check default parameter
        $this->assertEquals(3, $mixed->getMaxRetries());
    }

    /**
     * Test withInjectedConstructorOverrides with multiple overrides
     *
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function testWithInjectedConstructorOverridesMultipleParameters()
    {
        $config = $this->object->build('di-test-overrides');

        $mixed = $config->get('mixed2');
        $this->assertInstanceOf(MixedDependencies::class, $mixed);

        // Check that auto-injected dependencies work
        $this->assertEquals(42, $mixed->getRandomNumber());
        $this->assertEquals(25, $mixed->getArea()->calculate());

        // Check that both overridden parameters work
        $this->assertEquals('another-key', $mixed->getApiKey());
        $this->assertEquals(5, $mixed->getMaxRetries());
    }

    /**
     * Test withInjectedConstructorOverrides with Param::get() override
     *
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function testWithInjectedConstructorOverridesWithParamGet()
    {
        $config = $this->object->build('di-test-overrides');

        $mixed = $config->get('mixed3');
        $this->assertInstanceOf(MixedDependencies::class, $mixed);

        // Check that overridden dependency works (custom-random has value 999)
        $this->assertEquals(999, $mixed->getRandomNumber());

        // Check that other auto-injected dependency works
        $this->assertEquals(25, $mixed->getArea()->calculate());

        // Check that string override works
        $this->assertEquals('test-key', $mixed->getApiKey());
    }

    /**
     * Test that withInjectedConstructorOverrides fails without required built-in type parameter
     *
     * @throws ConfigNotFoundException
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function testWithInjectedConstructorOverridesFailsWithoutBuiltinTypeOverride()
    {
        $this->expectException(DependencyInjectionException::class);
        $this->expectExceptionMessage("The parameter '\$apiKey' is a built-in type");

        // This should fail during build because apiKey is required but not provided
        $config = $this->object->build('di-test-overrides-fail');
    }

    /**
     * An intersection type (A&B) has no single class name to resolve, so auto-wiring must
     * refuse it at definition time instead of registering a dependency literally named
     * "Countable&ArrayAccess" that blows up much later with an unrelated "not found".
     *
     * @throws DependencyInjectionException
     * @throws ReflectionException
     */
    public function testInjectedConstructorRejectsIntersectionType()
    {
        $this->expectException(DependencyInjectionException::class);
        $this->expectExceptionMessage(
            "The parameter '\$dependency' has an unsupported type and must be provided in "
            . "overrides array in class '" . ClassWithIntersectionType::class . "'"
        );

        DependencyInjection::bind(ClassWithIntersectionType::class)
            ->withInjectedConstructor();
    }

    /**
     * ... and the escape hatch that message points at genuinely works: an override is
     * consumed before auto-wiring ever inspects the type.
     *
     * @throws ContainerExceptionInterface
     * @throws DependencyInjectionException
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testIntersectionTypeCanBeSuppliedThroughOverrides()
    {
        $instance = DependencyInjection::bind(ClassWithIntersectionType::class)
            ->withInjectedConstructorOverrides(['dependency' => new ArrayObject(['a', 'b'])])
            ->toInstance()
            ->getInstance();

        $this->assertInstanceOf(ClassWithIntersectionType::class, $instance);
        $this->assertEquals(2, $instance->countItems());
    }

    /**
     * Param::container() resolves to the container itself when used as a constructor
     * argument.
     */
    public function testContainerParamAsConstructorArg()
    {
        $config = $this->object->build('di-container');

        $instance = $config->get('container.ctor');
        $this->assertInstanceOf(ContainerAware::class, $instance);
        $this->assertSame($config, $instance->getContainer());

        // Not merely non-null — the container it received actually resolves.
        $this->assertEquals(6, $instance->resolve(Area::class)->calculate());
    }

    /**
     * ... and when used as a withMethodCall() argument, which is the case that carries
     * the container into an already-constructed service.
     */
    public function testContainerParamAsMethodCallArg()
    {
        $config = $this->object->build('di-container');

        $instance = $config->get('container.method');
        $this->assertInstanceOf(ContainerAware::class, $instance);
        $this->assertSame($config, $instance->getContainer());
        $this->assertEquals(6, $instance->resolve(Area::class)->calculate());
    }

    /**
     * Eager singletons are resolved inside Container::__construct(). Reaching for the
     * container through the static Config facade at that point recurses into
     * autoInitialize(); Param::container() uses the instance already injected, so it
     * must work here.
     */
    public function testContainerParamInsideEagerSingleton()
    {
        $config = $this->object->build('di-container');

        $this->assertEquals(KeyStatusEnum::IN_MEMORY, $config->keyStatus('container.eager'));

        $instance = $config->get('container.eager');
        $this->assertSame($config, $instance->getContainer());
        $this->assertEquals(6, $instance->resolve(Area::class)->calculate());
    }

    /**
     * A DependencyInjection holding a ContainerParam must survive serialization to the
     * container cache — and on the way back it must bind to the *new* container, not a
     * stale one. This is why the marker is stateless rather than the container itself.
     */
    public function testContainerParamSurvivesCacheRoundTrip()
    {
        $this->cache->clear();

        $container = $this->object->build('di-container-cache');
        $this->assertSame($container, $container->get('container.ctor')->getContainer());

        $container2 = Container::createFromCache('di-container-cache', $this->cache);
        $this->assertNotNull($container2);
        $this->assertNotSame($container, $container2);

        $restored = $container2->get('container.ctor');
        $this->assertInstanceOf(ContainerAware::class, $restored);
        $this->assertSame($container2, $restored->getContainer());
        $this->assertEquals(6, $restored->resolve(Area::class)->calculate());
    }

    /**
     * A pattern rule builds a matching class with its constructor resolved, with no
     * per-class entry. WidgetController cannot be built with `new`, so a working
     * instance proves the rule was applied.
     */
    public function testAutowireResolvesMatchingClass()
    {
        $config = $this->object->build('di-autowire');

        $instance = $config->get(WidgetController::class);
        $this->assertInstanceOf(WidgetController::class, $instance);
        $this->assertEquals(6, $instance->calculate());
    }

    /**
     * withInjectedConstructor() reflects on __construct, so a class that declares none
     * must degrade to withConstructorNoArgs() rather than blowing up.
     */
    public function testAutowireDegradesForClassWithoutConstructor()
    {
        $config = $this->object->build('di-autowire');

        $instance = $config->get(PlainController::class);
        $this->assertInstanceOf(PlainController::class, $instance);
        $this->assertEquals('hello', $instance->hello());
    }

    /**
     * An explicit binding must win, or a pattern could silently take over a class that
     * was deliberately configured.
     */
    public function testExplicitBindingWinsOverAutowirePattern()
    {
        $config = $this->object->build('di-autowire');

        $this->assertEquals('explicit', $config->get(OverriddenController::class)->label());
    }

    /**
     * toInstance() on the rule means every resolution is a fresh object.
     */
    public function testAutowireHonoursLifetime()
    {
        $config = $this->object->build('di-autowire');

        $this->assertNotSame(
            $config->get(WidgetController::class),
            $config->get(WidgetController::class)
        );
    }

    /**
     * has() has to agree with get(), per PSR-11 — and must stay false for a class the
     * pattern does not cover, or feature checks elsewhere silently change meaning.
     */
    public function testAutowireAffectsHasOnlyForMatchingExistingClasses()
    {
        $config = $this->object->build('di-autowire');

        $this->assertTrue($config->has(WidgetController::class));
        $this->assertTrue($config->has(PlainController::class));

        // Right shape of name, wrong namespace.
        $this->assertFalse($config->has(NotAController::class));

        // Matches the pattern, but no such class exists.
        $this->assertFalse($config->has('Tests\\DIClasses\\Autowired\\NoSuchController'));
    }

    /**
     * A class the pattern does not match still fails the ordinary way.
     */
    public function testAutowireDoesNotSwallowUnknownKeys()
    {
        $config = $this->object->build('di-autowire');

        $this->expectException(KeyNotFoundException::class);
        $config->get(NotAController::class);
    }

    /**
     * The rules live in the config array, so a container restored from cache has to
     * rebuild its index and keep autowiring.
     */
    public function testAutowireSurvivesCacheRoundTrip()
    {
        $this->cache->clear();

        $container = $this->object->build('di-autowire-cache');
        $this->assertEquals(6, $container->get(WidgetController::class)->calculate());

        $container2 = Container::createFromCache('di-autowire-cache', $this->cache);
        $this->assertNotNull($container2);
        $this->assertNotSame($container, $container2);

        $this->assertTrue($container2->has(WidgetController::class));
        $this->assertEquals(6, $container2->get(WidgetController::class)->calculate());
    }

    /**
     * Pattern matching is literal apart from `*`; namespace separators are not regex.
     */
    public function testAutowirePatternMatching()
    {
        $this->assertTrue(Autowire::matchesPattern('App\\Controller\\*', 'App\\Controller\\Thing'));
        $this->assertTrue(Autowire::matchesPattern('App\\Controller\\*', 'App\\Controller\\Sub\\Thing'));
        $this->assertFalse(Autowire::matchesPattern('App\\Controller\\*', 'App\\Service\\Thing'));
        $this->assertFalse(Autowire::matchesPattern('App\\Controller\\*', 'Other\\App\\Controller\\Thing'));

        $this->assertTrue(Autowire::matchesPattern('*Controller', 'Vendor\\Lib\\FooController'));
        $this->assertFalse(Autowire::matchesPattern('*Controller', 'Vendor\\Lib\\FooService'));

        // A dot is literal, not "any character".
        $this->assertFalse(Autowire::matchesPattern('App\\Cont.oller\\*', 'App\\Controller\\Thing'));

        // No wildcard at all is an exact match.
        $this->assertTrue(Autowire::matchesPattern('App\\Thing', 'App\\Thing'));
        $this->assertFalse(Autowire::matchesPattern('App\\Thing', 'App\\ThingElse'));
    }

    /**
     * Param::container() must not be confused with a normal Param — ContainerParam
     * extends Param, so the instanceof checks in getArgs() are order-sensitive.
     */
    public function testContainerParamIsDistinctFromRegularParam()
    {
        $this->assertInstanceOf(ContainerParam::class, Param::container());
        $this->assertInstanceOf(Param::class, Param::container());
        $this->assertNotInstanceOf(ContainerParam::class, Param::get(Area::class));
        $this->assertNotInstanceOf(ContainerParam::class, LazyParam::get(Area::class));
    }
}
