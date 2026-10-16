---
sidebar_position: 7
title: Special Types
description: Learn about the special type parsers for configuration values
---

# Special Types

## Returning parsed value from Static Files

By default, any value in the static files (*.env) are parsed as string. 

You can parse to a specific types using this syntax:

```text
PARAM1=!parser VALUE
```

Where `!parser` is one of the pre-defined parsers:

| Parser      | Description                             | Example                       |
|-------------|-----------------------------------------|-------------------------------|
| !bool       | Parse to boolean                        | `PARAM=!bool true`            |
| !int        | Parse to integer                        | `PARAM=!int 10`               |
| !float      | Parse to float                          | `PARAM=!float 3.5`            |
| !jsondecode | Parse to JSON and transform to an array | `PARAM=!jsondecode {"a":"b"}` |
| !array      | Parse to array                          | `PARAM=!array 1,2,3,4`        |
| !dict       | Parse to dictionary (associative array) | `PARAM=!dict a=1,b=2`         |
| !unesc      | Unescape the value                      | `PARAM=!unesc a\nb`           |
| !file       | Load the content of a file              | `PARAM=!file /path/to/file`   |

A relative `!file` path is resolved from the library's own directory, not the project. Use
`{__ROOT__}` to point at a project file: `PARAM=!file {__ROOT__}/config/key.pem` (see
[Paths relative to the project root](setup.md#paths-relative-to-the-project-root)).

## Adding a new Parser

You can add a new special type:

```php
<?php
use ByJG\Config\ParamParser;

if (!ParamParser::isParserExists('mytype')) {
    ParamParser::addParser('mytype', function ($value) {
        return 'mytype:' . $value;
    });
}
```

Then you can use:

```text
PARAM1=!mytype 123
```

## Referencing other entries in a binding

To use another entry inside a binding — a value parsed here, another service, or the container
itself — use `Param::get()` or `Param::container()`, never `Config::get()`: the configuration is
evaluated while the container is being built. See
[Referencing other entries: `Param`](dependency-injection.md#referencing-other-entries-param).

----
[Open source ByJG](http://opensource.byjg.com)
