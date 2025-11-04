---
sidebar_position: 5
title: Configure the Environment Variables
description: Learn how to set up environment variables in different web servers and deployment environments
---

# Configure the Environment Variables

To use the library effectively, you need to configure your environment to set the `APP_ENV` variable (or another variable you've configured using `withConfigVar()` in your Definition).

This environment variable determines which configuration set gets loaded. The value of `APP_ENV` typically corresponds to different environments in your application lifecycle, such as:
- `dev` for development
- `test` for testing
- `prod` for production
- `staging` for staging environments

Here are various ways to set it based on your deployment environment:

## Nginx

Nginx is a popular web server that often works with PHP through FastCGI. To set environment variables in Nginx, you need to configure them within the FastCGI parameters:

```text
server {
    listen 80;
    server_name example.com;
    root /var/www/html/public;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Application environment variable
        fastcgi_param APP_ENV dev;
    }
}
```

The line `fastcgi_param APP_ENV dev;` is setting an environment variable named `APP_ENV` with the value `dev` that will be passed to your PHP application through the FastCGI protocol.
This is commonly used in:
1. **Nginx configuration files** (like `nginx.conf` or site configuration files in `/etc/nginx/sites-available/`)
2. **Within a server or location block** that handles PHP processing

:::tip
After modifying Nginx configuration, remember to test the configuration (`nginx -t`) and reload Nginx (`systemctl reload nginx` or `service nginx reload`).
:::

## Apache

Apache uses the `SetEnv` directive to set environment variables. Here's a complete example of an Apache virtual host configuration:

```text
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/html/public
    
    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Set environment variables
        SetEnv APP_ENV dev
        SetEnv DB_HOST localhost
        SetEnv DB_NAME myapp
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

This configuration can be placed in:
- Virtual host configuration files (typically in `/etc/apache2/sites-available/`)
- `.htaccess` files
- Apache's main configuration file (`httpd.conf` or `apache2.conf`)

:::tip
Make sure the `mod_env` module is enabled in Apache for this to work. You can enable it with:
```bash
sudo a2enmod env
sudo systemctl restart apache2
```
:::

## PHP Built-in Server

The PHP built-in development server is useful for local development. Here's a complete example of how to set it up:

```bash
# Create a router script (router.php)
<?php
// Set environment variables
putenv('APP_ENV=dev');
putenv('DB_HOST=localhost');
putenv('DB_NAME=myapp');

// Your router logic here
if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
    return false;
} else {
    include __DIR__ . $_SERVER["REQUEST_URI"];
}
```

Then run the server:
```bash
APP_ENV=dev php -S localhost:8000 router.php
```

This is particularly useful for:
- Local development environments
- Quick testing of your application
- Development without needing a full web server setup

## Command Line

When running PHP scripts directly from the command line, you can set environment variables using the `export` command. Here's a complete example:

```bash
# Set multiple environment variables
export APP_ENV=dev
export DB_HOST=localhost
export DB_NAME=myapp
export DEBUG=true

# Run your PHP script
php your-script.php

# Or set variables inline for a single command
APP_ENV=dev DB_HOST=localhost php your-script.php
```

This method is useful for:
- Running PHP scripts directly
- Testing in a controlled environment
- Development and debugging

## Docker-Compose

Docker Compose allows you to set environment variables in your service definitions. Here's a complete example:

```yaml
services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      APP_ENV: dev
      DB_HOST: db
      DB_NAME: myapp
      DEBUG: "true"
    volumes:
      - .:/var/www/html
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: myapp
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_USER: user
      MYSQL_PASSWORD: password
    volumes:
      - dbdata:/var/lib/mysql

volumes:
  dbdata:
```

This configuration can be placed in your `docker-compose.yml` file. It's particularly useful for:
- Containerized applications
- Development environments
- Consistent environment variable management across containers

## Docker CLI

When running Docker containers directly, you can pass environment variables using the `-e` flag. Here's a complete example:

```bash
# Run with multiple environment variables
docker run -d \
  -e APP_ENV=dev \
  -e DB_HOST=db \
  -e DB_NAME=myapp \
  -e DEBUG=true \
  -p 8000:8000 \
  your-image-name

# Or use an environment file
docker run -d \
  --env-file .env \
  -p 8000:8000 \
  your-image-name
```

This is useful for:
- Single container deployments
- Testing specific environment configurations
- CI/CD pipelines

## .htaccess (Apache)

Apache's `.htaccess` files provide a way to set environment variables at the directory level. Here's a complete example:

```text
# Enable required Apache modules
<IfModule mod_env.c>
    # Set environment variables
    SetEnv APP_ENV dev
    SetEnv DB_HOST localhost
    SetEnv DB_NAME myapp
    SetEnv DEBUG true
    
    # You can also set variables conditionally
    SetEnvIf Host "staging\.example\.com" APP_ENV staging
    SetEnvIf Host "prod\.example\.com" APP_ENV prod
</IfModule>

# Additional security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
</IfModule>

# PHP settings
<IfModule mod_php8.c>
    php_value display_errors Off
    php_value log_errors On
    php_value error_log /path/to/error.log
</IfModule>
```

This approach is useful when:
- You don't have access to the main Apache configuration
- You need different settings for different directories
- You want to keep environment-specific settings with your application code

:::tip
Make sure `.htaccess` files are enabled in your Apache configuration (`AllowOverride All`).
:::

## PHP Script (Not Recommended)

While it's possible to set environment variables directly in PHP, this is generally not recommended as it's better to configure this at the server level. Here's an example of what to avoid:

```php
<?php
// Not recommended - setting environment variables in PHP
putenv('APP_ENV=dev');
putenv('DB_HOST=localhost');
putenv('DB_NAME=myapp');

// Also not recommended - using $_ENV directly
$_ENV['APP_ENV'] = 'dev';
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_NAME'] = 'myapp';

// Your application code here
```

:::warning
This approach is discouraged because:
- It can lead to inconsistent behavior across different environments
- It makes it harder to manage environment-specific configurations
- It may not work in all server configurations
- It can be overridden by server-level settings
:::

## Best Practices

1. **Environment Separation**: Use different environment variables for different environments (development, testing, production) to ensure your application behaves correctly in each context.
2. **Security**: Never commit sensitive environment variables to version control. Use `.env` files or secure secret management systems.
3. **Consistency**: Maintain consistent environment variable naming across your application.
4. **Documentation**: Document all required environment variables and their expected values.
5. **Validation**: Implement validation for required environment variables in your application startup.

----
[Open source ByJG](http://opensource.byjg.com)
