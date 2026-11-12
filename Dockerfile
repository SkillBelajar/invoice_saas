FROM php:8.4-cli

# System deps: git/zip for composer, libbrotli+libssl+pkg-config to build Swoole, libpng/libjpeg/libfreetype for GD
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip libzip-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libxml2-dev \
    libbrotli-dev libssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions Laravel + Octane need
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath pcntl opcache gd

# Swoole (Octane) + Redis (cache/session/queue)
RUN pecl install swoole redis \
    && docker-php-ext-enable swoole redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Composer layer cache
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev \
    && composer dump-autoload --optimize \
    && mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
