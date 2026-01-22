FROM composer:2 AS composer

FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip unzip \
    && docker-php-ext-install pdo pdo_pgsql opcache

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini

WORKDIR /var/www/html
