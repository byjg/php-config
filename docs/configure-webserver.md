---
sidebar_position: 5
---

# Configure the Environment Variables

To use the library effectively, you need to configure your environment to set the `APP_ENV` variable (or another variable you've configured using `withConfigVar()` in your Definition).

This environment variable determines which configuration set gets loaded. Here are various ways to set it based on your deployment environment:

## Nginx

```text
fastcgi_param   APP_ENV  dev;
```

## Apache

```text
SetEnv APP_ENV dev
```

## PHP Built-in Server

```bash
APP_ENV=dev php -S localhost:8000
```

## Command Line

```bash
export APP_ENV=dev
php your-script.php
```

## Docker-Compose

```yaml
environment:
    APP_ENV: dev
```

## Docker CLI

```bash
docker run -e APP_ENV=dev image ...
```

## .htaccess (Apache)

```text
<IfModule mod_env.c>
    SetEnv APP_ENV dev
</IfModule>
```

## PHP Script (Not Recommended)

You can set the environment variable directly in PHP, but this is generally not recommended as it's better to configure this at the server level:

```php
<?php
putenv('APP_ENV=dev');
// Or
$_ENV['APP_ENV'] = 'dev';
```

It's best practice to set different environment variables for different environments (development, testing, production) to ensure your application behaves correctly in each context.

----
[Open source ByJG](http://opensource.byjg.com)
