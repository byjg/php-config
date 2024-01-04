# Dependency Injection

## Basics

Dependency Injection is a design pattern commonly used in computer programming, particularly in object-oriented systems,
to manage the dependencies between classes or components. It is a way to provide the necessary dependencies to an object
 rather than having the object create or find them itself.

In software development, a dependency refers to an object or service that another object relies on to perform its tasks.
These dependencies can include other classes, modules, databases, web services, or any external resources required for the
functioning of a particular component.

Dependency Injection promotes loose coupling between components, making them more modular and easier to maintain and test.
It achieves this by inverting the control of object creation and management. Instead of a class creating or looking up its
dependencies, the dependencies are provided or "injected" into the class from the outside.

Dependency Injection (DI) offers several advantages over directly instantiating dependencies within a class. Here are some reasons why DI is considered a better approach:

- Loose coupling: DI promotes loose coupling between components. When a class directly instantiates its dependencies, it becomes tightly coupled to those dependencies. This tight coupling can make the class harder to modify, test, and reuse. With DI, dependencies are provided externally, allowing for more flexibility and easier swapping of dependencies without modifying the class itself.

- Separation of concerns: By using DI, a class can focus on its primary responsibilities rather than worrying about creating or managing dependencies. This improves the single responsibility principle and makes the class more focused and maintainable.

- Testability: DI enhances testability by allowing for easy substitution of dependencies during testing. With DI, you can inject mock or stub objects for testing purposes, enabling more isolated and targeted unit testing. In contrast, if dependencies are instantiated directly within a class, it becomes challenging to replace them with test doubles, leading to more complex and less reliable tests.

- Reusability: When dependencies are injected, they can be shared and reused across multiple classes or components. This reduces code duplication and promotes modular design. It also simplifies the task of managing and updating dependencies since changes can be made in a centralized manner rather than modifying every class that uses those dependencies.

- Configuration flexibility: DI allows for flexible configuration of dependencies. With DI frameworks or containers, you can easily configure the dependencies at runtime, change configurations without recompiling the code, or even have different configurations for different environments. This flexibility is particularly useful in complex systems where configurations may vary based on deployment scenarios.

- Dependency lifetime management: DI frameworks often provide mechanisms to manage the lifetime of dependencies, such as creating a new instance for each injection or reusing a single instance throughout the application. This control over dependency lifetime can help optimize resource usage, improve performance, and manage the state of shared dependencies.

Overall, DI promotes good software design principles such as loose coupling, separation of concerns, testability, and reusability. It enables easier maintenance, enhances code quality, and improves the overall architecture of an application. By decoupling dependencies from classes, DI helps build more modular, flexible, and scalable systems.

## Using Dependency Injection

Let's get by example the following classes:

```php
<?php
namespace Example;

interface Area
{
    public function calculate();
}

class Square implements Area
{
    public function __construct($side)
    {
        // ...
    }

    //...
}

class RectangleTriangle implements Area
{
    public function __construct($base, $height)
    {
        // ...
    }

    //...
}
```

We can create a definition for this classes:

```php
<?php

use ByJG\Config\DependencyInjection as DI;

return [
    \Example\Square::class => DI::bind(\Example\Square::class)
        ->withConstructorArgs([4])
        ->toInstance(),

    \Example\RectangleTriangle::class => DI::bind(\Example\RectangleTriangle::class)
        ->withConstructorArgs([3, 4])
        ->toInstance(),
];
```

and to use in our code we just need to do:

```php
<?php
$config = $definition->build();
$square = $config->get(\Example\Square::class);
```

## Injecting automatically the Objects

Let's figure it out this class:

```php
<?php
class SumAreas implements Area
{
     /**
     * SumAreas constructor.
     * @param Test\DIClasses\RectangleTriangle $triangle 
     * @param Test\DIClasses\Square $square 
     */
    public function __construct($triangle, $square)
    {
        $this->triangle = $triangle;
        $this->square = $square;
    }

    //...
```

Note that this class needs instances of objects previously defined in our container definition. In that case we just need add
this:

```php
<?php
return [
    // ....

    SumAreas::class => DI::bind(SumAreas::class)
        ->withInjectedConstructor()
        ->toInstance(),
];
```

When use use the method `withInjectedConstructor()` the container will try to inject the constructor automatically based on
its type. Since we previously defined the classes `Square` and `RectangleTriangle` the container will inject the instances
automatically.

This component uses the PHP Document to determine the classed are required.

## Get a singleton object

The `DependencyInjection` class will return a new instance every time you require a new object. However, you can return always the same object by adding `toSingleton()` instead of `toInstance()`.

## All options (bind)

```php
<?php

\ByJG\Config\DependencyInjection::bind("classname")
    // To create a new instance choose *only* one below:
    // --------------------------------------------------
    ->withInjectedConstructor()         // If you want inject the constructor automatically using reflection
    ->withInjectedLegacyConstructor()   // If you want inject the constructor automatically using PHP annotation
    ->withNoConstructor()                // The class has no constructor
    ->withConstructorNoArgs()           // The constructor's class has no arguments
    ->withConstructorArgs(array)        // The constructor's class arguments
    ->withFactoryMethod("method", array_of_args)  // When the class has a static method to instantiate instead of constructor

    // Call methods after you have a instance
    // --------------------------------------
    ->withMethodCall("methodName", array_of_args)

    // How will you get a instance?
    // ----------------------------
    ->toInstance()                   // get a new instance for every every time you get from the container
    ->toSingleton()                  // get the same instance for every time you get from the container
    ->toEagerSingleton()             // same as singleton however get a new instance immediately after the definition.
;
```

----
[Open source ByJG](http://opensource.byjg.com)
