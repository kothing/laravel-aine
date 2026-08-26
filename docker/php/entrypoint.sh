#!/bin/sh
set -e

# Make sure writable dirs exist and are owned by www-data (named volumes
# are mounted with root ownership on first run).
mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    storage/app/public \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

# Discover packages (safe even before the web installer has run).
php artisan package:discover --ansi || true

# Link public/storage -> storage/app/public for uploaded media.
php artisan storage:link --no-interaction || true

# Once the app is installed, cache the config/routes for production.
if [ -f storage/installed ]; then
    php artisan config:cache --ansi || true
    php artisan route:cache --ansi || true
fi

exec php-fpm
