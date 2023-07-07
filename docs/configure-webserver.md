# Configure the webserver

You need to configure your webserver to pass the APP_ENV into the PHP code.

## Nginx

```text
fastcgi_param   APP_ENV  dev;
```

## Apache

```text
SetEnv APP_ENV dev
```

## Docker-Compose

```text
environment:
    APP_ENV: dev
```

## Docker CLI

```bash
docker -e APP_ENV=dev image ...
```

----
[Open source ByJG](http://opensource.byjg.com)
