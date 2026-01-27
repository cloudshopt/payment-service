# Composer dependencies
FROM composer AS composer-build

WORKDIR /var/www/html

COPY composer.json composer.lock /var/www/html/

RUN mkdir -p /var/www/html/database/{factories,seeds} \
    && composer install --no-dev --prefer-dist --no-scripts --no-autoloader --no-progress --ignore-platform-reqs

# Actual production image
FROM php:8.4-fpm

WORKDIR /var/www/html

RUN apt-get update  \
    && apt-get install --quiet --yes --no-install-recommends \
    libzip-dev \
    unzip \
    && docker-php-ext-install opcache zip pdo pdo_mysql \
    && pecl install -o -f redis-8.4.0 \
    && docker-php-ext-enable redis

# Use the default production donfiguration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Override with custom apache settings
COPY .docker/php/opcache.ini $PHP_INI_DIR/conf.d/

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY --chown=www-data --from=composer-build /var/www/html/vendor/ /var/www/html/vendor/
COPY --chown=www-data . /var/www/html

RUN composer dump -o \
    && composer check-platform-reqs \
    && rm -f /usr/bin/composer