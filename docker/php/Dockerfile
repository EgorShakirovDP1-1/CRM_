FROM php:8.3-fpm-bookworm

# Wayfinder invokes `php artisan` while Vite is compiling. Keeping Node in the
# PHP image lets the frontend build use the same PHP version and extensions as
# the application container.
COPY --from=node:24-bookworm-slim /usr/local/ /usr/local/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) bcmath intl opcache pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/entrypoint.sh /usr/local/bin/nexus-entrypoint
RUN chmod +x /usr/local/bin/nexus-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["/usr/local/bin/nexus-entrypoint"]
CMD ["php-fpm"]
