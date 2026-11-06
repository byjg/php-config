<?php

namespace Tests;

use ByJG\Cache\Psr16\ArrayCacheEngine;
use ByJG\Config\Container;
use ByJG\Config\DependencyInjection as DI;
use ByJG\Config\KeyStatusEnum;
use ByJG\Config\LazyParam;
use Override;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tests\DIClasses\Lazy\Counter;
use Tests\DIClasses\Lazy\CounterChild;
use Tests\DIClasses\Lazy\CounterConsumer;

/**
 * A LazyParam is a proxy of its type, resolved from the container on first use. On PHP 8.4+, for a plain binding of
 * exactly that class, it is a native lazy object (no code generated with eval(), which OPcache cannot cache);
 * otherwise a generated proxy class. Both behave the same.
 */
class LazyProxyTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        Counter::$created = 0;
    }

    private function consumer(array $config): Counter
    {
        $config[CounterConsumer::class] = DI::bind(CounterConsumer::class)
            ->withConstructorArgs([LazyParam::get(Counter::class)]);
        return (new Container($config))->get(CounterConsumer::class)->counter;
    }

    private static function isGenerated(object $proxy): bool
    {
        return str_starts_with(get_class($proxy), 'ByJG\\Config\\LazyProxy\\');
    }

    public function testAPlainBindingOfTheClass(): void
    {
        $container = new Container([
            Counter::class => DI::bind(Counter::class)->withConstructorArgs([5])->toSingleton(),
            CounterConsumer::class => DI::bind(CounterConsumer::class)->withConstructorArgs([LazyParam::get(Counter::class)]),
        ]);
        $proxy = $container->get(CounterConsumer::class)->counter;

        // Lazy until used
        $this->assertInstanceOf(Counter::class, $proxy);
        $this->assertSame(0, Counter::$created);
        $this->assertEquals(KeyStatusEnum::NotUsed, $container->keyStatus(Counter::class));

        $this->assertSame('counter 5', $proxy->value());
        $this->assertSame(1, Counter::$created);
        $this->assertSame($container->get(Counter::class)->value(), $proxy->value());

        if (PHP_VERSION_ID >= 80400) {
            $this->assertSame(Counter::class, get_class($proxy));
            // A public property is the real instance's (a generated proxy class has its own, never set)
            $this->assertSame(5, $proxy->start);
            $this->assertFalse((new ReflectionClass(Counter::class))->isUninitializedLazyObject($proxy));
        } else {
            $this->assertTrue(self::isGenerated($proxy));
        }
    }

    /**
     * The binding creates a subclass: a native proxy would run the methods of the class, not the overrides
     */
    public function testABindingOfASubclass(): void
    {
        $proxy = $this->consumer([Counter::class => DI::bind(CounterChild::class)->withConstructorArgs([7])]);

        $this->assertSame(0, Counter::$created);
        $this->assertSame('child 7', $proxy->value());
        $this->assertTrue(self::isGenerated($proxy));
    }

    /**
     * The public properties of a generated proxy are the real instance's: the proxy class has its own copies (it
     * extends the class), which must not be read instead
     */
    public function testThePropertiesOfAGeneratedProxy(): void
    {
        $proxy = $this->consumer([Counter::class => DI::bind(CounterChild::class)->withConstructorArgs([7])->toSingleton()]);
        $this->assertTrue(self::isGenerated($proxy));

        $this->assertSame(7, $proxy->start);
        $this->assertSame('id-7', $proxy->id);
        $this->assertSame('label 7', $proxy->label);
        $this->assertTrue(isset($proxy->label));
        $this->assertSame(1, Counter::$created);

        $proxy->start = 8;
        $proxy->label = 'changed';
        $this->assertSame('child 8', $proxy->value());
        $this->assertSame('changed', $proxy->label);

        unset($proxy->label);
        $this->assertFalse(isset($proxy->label));
    }

    /**
     * A factory method can return a subclass: it is not known before it runs
     */
    public function testABindingWithAFactoryMethod(): void
    {
        $proxy = $this->consumer([Counter::class => DI::bind(Counter::class)->withFactoryMethod('fromStart', [3])]);

        $this->assertSame(0, Counter::$created);
        $this->assertSame('counter 3', $proxy->value());
        $this->assertTrue(self::isGenerated($proxy));
    }

    /**
     * A container read from its cache does not create the instance to know its binding
     */
    public function testAContainerFromTheCache(): void
    {
        $cache = new ArrayCacheEngine();
        new Container([
            Counter::class => DI::bind(Counter::class)->withConstructorArgs([9])->toSingleton(),
            CounterConsumer::class => DI::bind(CounterConsumer::class)->withConstructorArgs([LazyParam::get(Counter::class)]),
        ], 'lazy-test', $cache);
        $container = Container::createFromCache('lazy-test', $cache);
        Counter::$created = 0;

        $proxy = $container->get(CounterConsumer::class)->counter;

        $this->assertSame(0, Counter::$created);
        $this->assertEquals(KeyStatusEnum::NotUsed, $container->keyStatus(Counter::class));
        $this->assertSame('counter 9', $proxy->value());
        $this->assertSame(1, Counter::$created);
        $this->assertSame(PHP_VERSION_ID < 80400, self::isGenerated($proxy));
    }
}
