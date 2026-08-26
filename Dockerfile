# ===========================================================================
# Aine CMS - production image (multi-stage build)
#
# Build:
#   docker build -t aine-app:latest .
#
# Stage 1 - frontend assets (Vite build)
# ===========================================================================
FROM node:22-alpine AS frontend
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# ===========================================================================
# Stage 2 - PHP vendor (composer install, no dev deps)
# ===========================================================================
FROM composer:2.7 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
        --no-progress

# ===========================================================================
# Stage 3 - runtime (php-fpm)
# ===========================================================================
FROM php:8.3-fpm-alpine AS app

# System dependencies for the PHP extensions used by the project
RUN apk add --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        postgresql-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        gd \
        intl \
        bcmath \
        zip \
        exif \
        opcache \
    && rm -rf /var/cache/apk/*

# Tuned php.ini for the app
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-aine.ini

WORKDIR /var/www/html

# Vendors + compiled assets from the build stages
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

# Application source (the .dockerignore keeps secrets/build output out)
COPY --chown=www-data:www-data . .

# Runtime entrypoint (storage:link, caches, permissions)
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/logs \
        storage/app/public \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
