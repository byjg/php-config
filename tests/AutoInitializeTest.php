<?php

namespace Tests;

use ByJG\Config\Config;
use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;
use ByJG\Config\Exception\RunTimeException;
use Override;
use PHPUnit\Framework\TestCase;

class AutoInitializeTest extends TestCase
{
    private string $configDir;
    private string $bootstrapFile;

    #[Override]
    public function setUp(): void
    {
        // Determine the correct config directory path
        $this->configDir = __DIR__ . '/../config';
        $this->bootstrapFile = $this->configDir . '/ConfigBootstrap.php';

        // Clean up any existing bootstrap file
        if (file_exists($this->bootstrapFile)) {
            unlink($this->bootstrapFile);
        }
    }

    #[Override]
    public function tearDown(): void
    {
        // Clean up bootstrap file after each test
        if (file_exists($this->bootstrapFile)) {
            unlink($this->bootstrapFile);
        }

        // Reset Config state
        Config::reset();
        putenv('APP_ENV');
    }

    public function testAutoInitializeWithValidBootstrap()
    {
        // Create a valid bootstrap file
        $bootstrapContent = <<<'PHP'
<?php

use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        $test = new Environment('test');
        return (new Definition())
            ->addEnvironment($test);
    }
};
PHP;

        file_put_contents($this->bootstrapFile, $bootstrapContent);

        putenv('APP_ENV=test');

        // This should auto-initialize from the bootstrap file
        $result = Config::get('property1');

        $this->assertEquals('string', $result);
    }

    public function testAutoInitializeWithoutBootstrapFile()
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage("Environment isn't build yet. Please call Config::initialize() or create a config/ConfigBootstrap.php file that implements ConfigInitializeInterface.");

        putenv('APP_ENV=test');

        // Should throw exception when bootstrap file doesn't exist
        Config::get('property1');
    }

    public function testAutoInitializeWithInvalidBootstrap()
    {
        // Create an invalid bootstrap file (doesn't return ConfigInitializeInterface)
        $bootstrapContent = <<<'PHP'
<?php

return new stdClass();
PHP;

        file_put_contents($this->bootstrapFile, $bootstrapContent);

        putenv('APP_ENV=test');

        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage("The config/ConfigBootstrap.php file must return an instance of ConfigInitializeInterface.");

        // Should throw exception when bootstrap doesn't implement the interface
        Config::get('property1');
    }

    public function testAutoInitializeUsesAppEnv()
    {
        // Create a bootstrap file that sets different values based on environment
        $bootstrapContent = <<<'PHP'
<?php

use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        $test = new Environment('test');
        $test2 = new Environment('test2', [$test]);

        return (new Definition())
            ->addEnvironment($test)
            ->addEnvironment($test2);
    }
};
PHP;

        file_put_contents($this->bootstrapFile, $bootstrapContent);

        putenv('APP_ENV=test2');

        // Should use test2 environment based on APP_ENV
        $result = Config::get('property2');

        // test2 has property2 = false (from config-test2.php)
        $this->assertFalse($result);
    }

    public function testAutoInitializeOnlyOnce()
    {
        // Create a valid bootstrap file
        $bootstrapContent = <<<'PHP'
<?php

use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        $test = new Environment('test');
        return (new Definition())
            ->addEnvironment($test);
    }
};
PHP;

        file_put_contents($this->bootstrapFile, $bootstrapContent);

        putenv('APP_ENV=test');

        // First call should auto-initialize
        $result1 = Config::get('property1');

        // Delete the bootstrap file
        unlink($this->bootstrapFile);

        // Second call should use the already-initialized container, not try to auto-initialize again
        $result2 = Config::get('property1');

        $this->assertEquals($result1, $result2);
        $this->assertEquals('string', $result2);
    }

    public function testAutoInitializeWithAllMethods()
    {
        // Create a valid bootstrap file
        $bootstrapContent = <<<'PHP'
<?php

use ByJG\Config\ConfigInitializeInterface;
use ByJG\Config\Definition;
use ByJG\Config\Environment;

return new class implements ConfigInitializeInterface {
    public function loadDefinition(?string $env = null): Definition {
        $test = new Environment('test');
        return (new Definition())
            ->addEnvironment($test);
    }
};
PHP;

        file_put_contents($this->bootstrapFile, $bootstrapContent);

        putenv('APP_ENV=test');

        // Test that all Config methods trigger auto-initialization
        $this->assertEquals('string', Config::get('property1'));

        Config::reset();

        $this->assertEquals('string', Config::raw('property1'));

        Config::reset();

        $this->assertTrue(Config::has('property1'));
    }

    public function testDefinitionFindBaseDirStatic()
    {
        $baseDir = Definition::findBaseDir();

        $this->assertNotEmpty($baseDir);
        $this->assertTrue(file_exists($baseDir));
        $this->assertStringEndsWith('config', $baseDir);
    }
}
