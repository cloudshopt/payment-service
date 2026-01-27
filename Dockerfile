FROM php:8.4-fpm

WORKDIR /var/www/html

RUN apt-get update  \
    && apt-get install --quiet --yes --no-install-recommends \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip pdo pdo_mysql \
    && pecl install -o -f redis-8.4.0 \
    && docker-php-ext-enable redis

COPY --from=composer /usr/bin/composer /usr/bin/composer