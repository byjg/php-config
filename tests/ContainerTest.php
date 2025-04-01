<?php

namespace Tests;

use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Config\CacheModeEnum;
use ByJG\Config\Environment;
use ByJG\Config\Container;
use ByJG\Config\Definition;
use ByJG\Config\Exception\ConfigException;
use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\KeyNotFoundException;
use ByJG\Config\Exception\RunTimeException;
use ByJG\Config\Psr11;
use Override;
use Psr\SimpleCache\InvalidArgumentException;
use Tests\DIClasses\Area;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var Definition
     */
    protected $object;

    /**
     * @throws ConfigException
     */
    #[Override]
    public function setUp(): void
    {
        $test = new Environment('test');
        $testCache = new Environment('test-cache', [$test], new FileSystemCacheEngine("multiple"));
        $testCacheSingle = new Environment('test-cache-single', [$test], new FileSystemCacheEngine("single"), cacheMode: CacheModeEnum::singleFile);
        $test2 = new Environment('test2', [$test]);
        $test3 = new Environment('test3', [$test2, $test]);
        $test4 = new Environment('test4', [$test3]); // Recursive Inheritance
        $closure = new Environment('closure');
        $notFound = new Environment('notfound');
        $folderEnv = new Environment('folderenv');

        $this->object = (new Definition())
            ->addEnvironment($test)
            ->addEnvironment($testCache)
            ->addEnvironment($testCacheSingle)
            ->addEnvironment($test2)
            ->addEnvironment($test3)
            ->addEnvironment($test4)
            ->addEnvironment($closure)
            ->addEnvironment($notFound)
            ->addEnvironment($folderEnv)
        ;
    }

    #[Override]
    public function tearDown(): void
    {
        putenv('APP_ENV');
        Psr11::reset();
    }

    public function testgetCurrentEnvironment()
    {
        putenv('APP_ENV=test');

        $this->assertEquals("test", $this->object->getCurrentEnvironment());

        putenv('APP_ENV=bla');
        $this->assertEquals("bla", $this->object->getCurrentEnvironment());
    }

    /**
     * @throws ConfigException
     * @throws ConfigNotFoundException
     * @throws InvalidArgumentException
     */
    public function testgetCurrentEnvironment2()
    {
        $this->object->build("test2");
        $this->assertEquals("test2", $this->object->getCurrentEnvironment());
    }

    /**
     * @throws ConfigException
     * @throws ConfigNotFoundException
     * @throws InvalidArgumentException
     */
    public function testgetCurrentEnvironment3()
    {
        putenv('APP_ENV=test');
        $this->object->build("test2");
        $this->assertEquals("test2", $this->object->getCurrentEnvironment());
    }

    /**
     * @throws ConfigException
     */
    public function testgetCurrentEnvironment4()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("The environment variable 'APP_ENV' is not set");

        $this->object->getCurrentEnvironment();
    }

    public function testLoadConfig()
    {
        $config = $this->object->build('test');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertTrue($config->get('property2'));

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('test', $config->get('property5'));
    }

    public function testLoadConfigStatic()
    {
        Psr11::initialize($this->object, 'test');

        $this->assertEquals('string', Psr11::get('property1'));
        $this->assertTrue(Psr11::get('property2'));

        $closure = Psr11::raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('test', Psr11::get('property5'));
    }


    public function testLoadConfigStaticNotInitialized()
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage("Environment isn't build yet");

        Psr11::get('property2');
    }

    public function testLoadConfig2()
    {
        $config = $this->object->build('test2');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('new', $config->get('property4'));

        $this->assertEquals('test2', $config->get('property5'));
    }

    public function testLoadConfigRecursiveInheritance()
    {
        $config = $this->object->build('test4');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('new', $config->get('property4'));

        $this->assertEquals('test3', $config->get('property5'));
    }

    public function testLoadConfigMultipleInherits()
    {
        $config = $this->object->build('test3');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));

        $closure = $config->raw('property3');
        $this->assertEquals('calculated', $closure());

        $this->assertEquals('new', $config->get('property4'));

        $this->assertEquals('test3', $config->get('property5'));
    }

    public function testLoadConfig3()
    {
        putenv('APP_ENV=test');
        $config = $this->object->build();
        $this->assertEquals('string', $config->get('property1'));
        $this->assertTrue($config->get('property2'));

        putenv('APP_ENV=test2');
        $config2 = $this->object->build();
        $this->assertEquals('string', $config2->get('property1'));
        $this->assertFalse($config2->get('property2'));
    }

    public function testLoadConfig3Static()
    {
        putenv('APP_ENV=test');
        Psr11::initialize($this->object);
        $this->assertEquals('string', Psr11::get('property1'));
        $this->assertTrue(Psr11::get('property2'));

        putenv('APP_ENV=test2');
        Psr11::initialize($this->object);
        $this->assertEquals('string', Psr11::get('property1'));
        $this->assertFalse(Psr11::get('property2'));
    }

    public function testLoadConfigArgs()
    {
        $config = $this->object->build('closure');

        $result = $config->get('closureProp', 'value1', 'value2');
        $this->assertEquals('value1:value2', $result);

        $result2 = $config->get('closureProp', ['valueA', 'valueB']);
        $this->assertEquals('valueA:valueB', $result2);

        $result3 = $config->get('closureProp2', null);
        $this->assertEquals('No Param', $result3);

        $result4 = $config->get('closureProp2', []);
        $this->assertEquals('No Param', $result4);

        $result5 = $config->get('closureArray', [['a', 'b']]);
        $this->assertTrue($result5);

        $result5 = $config->get('closureArray', 'string');
        $this->assertFalse($result5);

        $result6 = $config->get('closureWithoutArgs');
        $this->assertTrue($result6);
    }

    public function testLoadConfigArgsStatic()
    {
        Psr11::initialize($this->object, 'closure');

        $result = Psr11::get('closureProp', 'value1', 'value2');
        $this->assertEquals('value1:value2', $result);

        $result2 = Psr11::get('closureProp', ['valueA', 'valueB']);
        $this->assertEquals('valueA:valueB', $result2);

        $result3 = Psr11::get('closureProp2', null);
        $this->assertEquals('No Param', $result3);

        $result4 = Psr11::get('closureProp2', []);
        $this->assertEquals('No Param', $result4);

        $result5 = Psr11::get('closureArray', [['a', 'b']]);
        $this->assertTrue($result5);

        $result5 = Psr11::get('closureArray', 'string');
        $this->assertFalse($result5);

        $result6 = Psr11::get('closureWithoutArgs');
        $this->assertTrue($result6);
    }

    public function testLoadConfigNotExistant()
    {
        $this->expectException(KeyNotFoundException::class);
        $this->expectExceptionMessage("The key 'property4' does not exists");

        $config = $this->object->build('test');

        $config->get('property4');
    }

    public function testLoadConfigNotExistant2()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("Configuration 'notset' does not defined");

        $this->object->build('notset');
    }

    // public function testLoadConfigNotExistant3()
    // {
    //     $this->expectException(\ByJG\Config\Exception\ConfigNotFoundException::class);
    //     $this->expectExceptionMessage("Configuration 'config-notfound.php' or 'config-notfound.env' could not found");

    //     $this->object->build('notfound');
    // }

    protected function deleteCache()
    {
        // delete all files *.cache
        $files = glob(sys_get_temp_dir() . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testCache()
    {
        // With Cache!
        $cacheObject = $this->object->getConfigObject('test-cache')->getCacheInterface();

        $this->deleteCache();
        $this->assertNull($cacheObject->get('container-cache-test-cache'));
        $this->assertNull($cacheObject->get('container-cache-test-cache-property3'));
        $this->assertNull($cacheObject->get('container-cache-test-cache-property6'));
        $this->assertNull($cacheObject->get('container-cache-test-cache-tests_diclasses_area'));

        // First Step, create a fresh object
        // Second Step, get the object from cache
        for ($i = 0; $i < 2; $i++) {
            if ($i == 1) {
                $this->assertNotNull($cacheObject->get('container-cache-test-cache'));
                $this->assertNotNull($cacheObject->get('container-cache-test-cache-property3'));
                $this->assertNotNull($cacheObject->get('container-cache-test-cache-property6'));
                $this->assertNotNull($cacheObject->get('container-cache-test-cache-tests_diclasses_area'));
            }
            $container = $this->object->build('test-cache');  // Expected build and set to cache
            $this->assertNotNull($cacheObject->get('container-cache-test-cache'));
            $this->assertNotNull($cacheObject->get('container-cache-test-cache-property3'));
            $this->assertNotNull($cacheObject->get('container-cache-test-cache-property6'));
            $this->assertNotNull($cacheObject->get('container-cache-test-cache-tests_diclasses_area'));

            $this->assertInstanceOf(FileSystemCacheEngine::class, $this->object->getCacheCurrentEnvironment());
            $this->assertEquals(CacheModeEnum::multipleFiles, $this->object->getCacheModeCurrentEnvironment());

            // Get Container2 from cache and should match with the first one
            $container2 = Container::createFromCache('test-cache', $cacheObject);
            $this->assertTrue($container->compare($container2)); // The exact object

            $this->assertEquals('calculated', $container2->get('property3'));
            $this->assertEquals(4, $container2->get('property6'));
            $this->assertEquals(6, $container2->get(Area::class)->calculate());
        }
    }

    public function testCacheSingle()
    {
        // With Cache!
        $cacheObject = $this->object->getConfigObject('test-cache-single')->getCacheInterface();

        $this->deleteCache();
        $this->assertNull($cacheObject->get('container-cache-test-cache-single'));
        $this->assertNull($cacheObject->get('container-cache-test-cache-single-property3'));
        $this->assertNull($cacheObject->get('container-cache-test-cache-single-property6'));
        $this->assertNull($cacheObject->get('container-cache-test-cache-single-tests_diclasses_area'));

        for ($i = 0; $i < 2; $i++) {
            if ($i == 1) {
                $this->assertNotNull($cacheObject->get('container-cache-test-cache-single'));
                $this->assertNull($cacheObject->get('container-cache-test-cache-single-property3'));
                $this->assertNull($cacheObject->get('container-cache-test-cache-single-property6'));
                $this->assertNull($cacheObject->get('container-cache-test-cache-single-tests_diclasses_area'));
            }
            $container = $this->object->build('test-cache-single');  // Expected build and set to cache
            $this->assertNotNull($cacheObject->get('container-cache-test-cache-single'));
            $this->assertNull($cacheObject->get('container-cache-test-cache-single-property3'));
            $this->assertNull($cacheObject->get('container-cache-test-cache-single-property6'));
            $this->assertNull($cacheObject->get('container-cache-test-cache-single-tests_diclasses_area'));

            $this->assertInstanceOf(FileSystemCacheEngine::class, $this->object->getCacheCurrentEnvironment());
            $this->assertEquals(CacheModeEnum::singleFile, $this->object->getCacheModeCurrentEnvironment());

            // Get Container2 from cache and should match with the first one
            $container2 = Container::createFromCache('test-cache-single', $cacheObject);
            $this->assertTrue($container->compare($container2)); // The exact object

            $this->assertEquals('calculated', $container2->get('property3'));
            $this->assertEquals(4, $container2->get('property6'));
            $this->assertEquals(6, $container2->get(Area::class)->calculate());
        }
    }

    public function testChangeConfigVar()
    {
        $test = new Environment('test');
        $test2 = new Environment('test2', [$test]);

        putenv('NEWENV=test');
        $object = (new Definition())
            ->withConfigVar('NEWENV')
            ->addEnvironment($test)
            ->addEnvironment($test2)
        ;
        $this->object->withConfigVar('NEWENV');
        $this->assertEquals("test", $this->object->getCurrentEnvironment());

        $container = $this->object->build();
        $this->assertEquals('string', $container->get('property1'));
    }

    public function testChangeConfigVarNotSet()
    {
        $test = new Environment('test');
        $test2 = new Environment('test2', [$test]);

        $object = (new Definition())
            ->withConfigVar('SETUP')
            ->addEnvironment($test)
            ->addEnvironment($test2)
        ;

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage("The environment variable 'SETUP' is not set");
        $object->build();
    }

    public function testGetCacheNotSetYet()
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage("Environment isn't build yet");
        $this->object->getCacheCurrentEnvironment();

    }

    public function testConfigDirectory()
    {
        $config = $this->object->build('folderenv');

        $this->assertEquals('string', $config->get('property1'));
        $this->assertFalse($config->get('property2'));
        $this->assertEquals('other_value3', $config->get('property3'));
    }

    public function testGetAsFilename()
    {
        $config = $this->object->build('folderenv');

        $this->assertFileDoesNotExist(sys_get_temp_dir() . '/config-folderenv-property1.php');

        // Test if get the proper result
        $filename = $config->getAsFilename('property1');
        $this->assertStringContainsString(sys_get_temp_dir() . '/config-folderenv-property1.php', $filename);
        $this->assertFileExists($filename);
        $this->assertEquals('string', file_get_contents($filename));


        // After a new build the cached files should be cleared
        $config = $this->object->build('folderenv');

        // The build should clear filenames cache
        $this->assertFileDoesNotExist(sys_get_temp_dir() . '/config-folderenv-property1.php');
    }

    public function testGetAsFilenameNonString()
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage("The content of 'property2' is not a string");

        $config = $this->object->build('folderenv');
        $filename = $config->getAsFilename('property2');
    }
}
