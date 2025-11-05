<?php

namespace ByJG\Config;

use ByJG\Config\Exception\ConfigException;
use ByJG\Config\Exception\ConfigNotFoundException;
use ByJG\Config\Exception\DependencyInjectionException;
use ByJG\Config\Exception\KeyNotFoundException;
use ByJG\Config\Exception\RunTimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;

/**
 * Configuration and Container Facade
 *
 * This class provides a convenient static interface to access the container functionality
 * without the need to pass around the container instance. It acts as a facade for
 * the Container class.
 *
 * Usage:
 * 1. Initialize the container with a Definition:
 *    Config::initialize($definition, $env);
 *
 * 2. Access container values and services:
 *    $value = Config::get('config.key');
 *    $rawValue = Config::raw('config.key');
 *
 * This facade provides static access to both configuration values and dependency injection,
 * similar to Laravel's Config facade but with extended container functionality.
 */
class Config
{
    private static ?Container $container = null;

    private static ?Definition $definition = null;

    /**
     * Gets the container instance
     *
     * @return Container
     * @throws RunTimeException if the container is not initialized
     */
    private static function getContainer(): Container
    {
        if (is_null(self::$container)) {
            self::autoInitialize();
        }
        return self::$container;
    }

    /**
     * Attempts to auto-initialize the container by loading a bootstrap file
     *
     * Looks for a ConfigBootstrap.php file in the config directory that implements
     * ConfigInitializeInterface. If found, it will use it to initialize the container.
     *
     * @return void
     * @throws RunTimeException if no bootstrap file is found or initialization fails
     */
    private static function autoInitialize(): void
    {
        $bootstrapFile = Definition::findBaseDir() . '/ConfigBootstrap.php';

        if (!file_exists($bootstrapFile)) {
            throw new RunTimeException("Environment isn't build yet. Please call Config::initialize() or create a config/ConfigBootstrap.php file that implements ConfigInitializeInterface.");
        }

        $bootstrap = require $bootstrapFile;

        if (!$bootstrap instanceof ConfigInitializeInterface) {
            throw new RunTimeException("The config/ConfigBootstrap.php file must return an instance of ConfigInitializeInterface.");
        }

        $env = getenv('APP_ENV') ?: null;
        $definition = $bootstrap->loadDefinition($env);
        self::initialize($definition, $env);
    }

    /**
     * Retrieves an entry from the container
     *
     * This method not only retrieves a value from the configuration but also
     * resolves dependencies for class instantiation if the value is a class name.
     *
     * @param string $id The identifier of the entry to look for
     * @param mixed ...$parameters Additional parameters to pass to the constructor if instantiating a class
     * @return mixed The entry value or instantiated class
     * @throws ConfigException
     * @throws ContainerExceptionInterface
     * @throws DependencyInjectionException
     * @throws InvalidArgumentException
     * @throws KeyNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws RunTimeException
     */
    public static function get(string $id, mixed ...$parameters): mixed
    {
        return self::getContainer()->get($id, ...$parameters);
    }

    /**
     * Retrieves a raw value from the container without processing
     * 
     * Unlike get(), this method returns the raw value without attempting to
     * instantiate classes or resolve dependencies.
     * 
     * @param string $id The identifier of the entry to look for
     * @return mixed The raw entry value
     * @throws InvalidArgumentException
     * @throws KeyNotFoundException
     * @throws RunTimeException
     */
    public static function raw(string $id): mixed
    {
        return self::getContainer()->raw($id);
    }

    /**
     * Checks if the container can return an entry for the given identifier
     * 
     * @param string $id The identifier to check for
     * @return bool True if the container contains the given identifier, false otherwise
     * @throws RunTimeException if the container is not initialized
     */
    public static function has(string $id): bool
    {
        return self::getContainer()->has($id);
    }

    /**
     * Gets the value as a filename
     * 
     * This method is useful for file-based configuration values where the path
     * needs to be resolved relative to the project root.
     * 
     * @param string $id The identifier of the entry to get as a filename
     * @return string The resolved filename
     * @throws RunTimeException if the container is not initialized
     */
    public static function getAsFilename(string $id): string
    {
        return self::getContainer()->getAsFilename($id);
    }

    /**
     * Initializes the container with the given definition
     * 
     * This method must be called before using any other method in this class.
     * It builds the container from the provided definition and optional environment.
     * 
     * @param Definition $definition The configuration definition
     * @param string|null $env Optional environment name to build the configuration for
     * @return void
     * @throws ConfigException
     * @throws ConfigNotFoundException
     * @throws InvalidArgumentException
     */
    public static function initialize(Definition $definition, ?string $env = null): void
    {
        self::$container = $definition->build($env);
        self::$definition = $definition;
    }

    public static function reset(): void
    {
        self::$container = null;
    }

    public static function definition(): ?Definition
    {
        return self::$definition;
    }
}
