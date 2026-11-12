#!/bin/bash
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

# vendor/ is bind-mounted from host; fill it only when empty
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if ! grep -q "^APP_KEY=base64" .env; then
    php artisan key:generate --no-interaction || true
fi

php artisan migrate --force || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# storage/ and bootstrap/cache are bind-mounted, shadowing the image chown
chown -R www-data:www-data storage bootstrap/cache || true

exec php artisan octane:start --host=0.0.0.0 --port=8000 --server=swoole
