# Deployment image for the VPS. Sits behind the box's shared Caddy instance
# (see docker-compose.yml and DEPLOY.md) — never exposed to the internet
# directly, so it listens on plain HTTP on the default Apache port.
#
# PHP 8.3, not 8.2 or 8.5: matches what's actually pinned in composer.lock
# (phpoffice/phpspreadsheet refuses PHP >= 8.5) and is readily available.
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libonig-dev libxml2-dev unzip git \
    && docker-php-ext-install pdo pdo_sqlite mbstring gd zip xml dom \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Laravel's front controller lives in public/, not the repo root.
COPY docker/apache-taher.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .

RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/app/public storage/logs \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache
