# Special Types

## Returning parsed value from Static Files

By default, any value in the static files (*.env) are parsed as string. 

You can parse to a specific types using this syntax:

```text
PARAM1=!parser VALUE
```

Where `!parser` is one of the pre-defined parsers:

| Parser      | Description                             | Example                     |
|-------------|-----------------------------------------|-----------------------------|
| !bool       | Parse to boolean                        | PARAM=!bool true            |
| !int        | Parse to integer                        | PARAM=!int 10               |
| !float      | Parse to float                          | PARAM=!float 3.5            |
| !jsondecode | Parse to JSON and transform to an array | PARAM=!jsondecode {"a":"b"} |
| !array      | Parse to array                          | PARAM=!array 1,2,3,4        |
| !dict       | Parse to dictionary (associative array) | PARAM=!dict a=1,b=2         |


## Adding a new Parser

You can add a new special type:

```php
<?php

if (!ParamParser::hasParser('mytype') {
    ParamParser::addParser('mytype', function ($value) {
        return 'mytype:' . $value;
    });
}
```

Then you can use:

```text
PARAM1=!mytype 123
```

## Dependency Injection with a contructor parameter as array getting from the config

Normally when we need to pass to the constructor of the scalar value we use the `Param::get()` method, like this:

```php
return [
    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([Param::get('side')])
        ->toInstance(),
```

However, if you need to pass an array, and inside the array you need to get a value from the config, we will get an error, 
because the `Param::get()` isn't change values inside the array.

The exemple below will not work, because when we get `EXAMPLE_ARRAY` the `Param::get()` inside it will not be executed:

```php
return [
    'custom_side' => 4,
    
    EXAMPLE_ARRAY => [
        'name' => 'Square',
        'side' => Param::get('custom_side'),
    ],
    
    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([Param::get('EXAMPLE_ARRAY')])
        ->toInstance(),
```

To solve this problem, we need to convert `EXAMPLE_ARRAY` into a closure. The clousure is lazy 
and will be executed only when the value is requested allowing us to use container inside the array 
(see [Good Practices](good-practices.md)).

```php
return [
    'custom_side' => 4,
    
    EXAMPLE_ARRAY => function () {
        return [
            'name' => 'Square',
            'side' => Psr11::container()->get('custom_side'),
        ]
    },
    
    Square::class => DI::bind(Square::class)
        ->withConstructorArgs([Param::get('EXAMPLE_ARRAY')])
        ->toInstance(),
```


