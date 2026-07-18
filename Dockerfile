# ==========================================================
# Stage 1 - Build frontend assets
# ==========================================================
FROM node:20-alpine AS assets

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build


# ==========================================================
# Stage 2 - PHP Application
# ==========================================================
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

# ----------------------------------------------------------
# System packages
# ----------------------------------------------------------
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    unzip \
    bash \
    curl \
    gettext \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libxml2-dev \
    zip

# ----------------------------------------------------------
# PHP Extensions
# ----------------------------------------------------------
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        bcmath \
        mbstring \
        zip \
        gd \
        opcache \
        dom \
        simplexml \
        xml \
        xmlreader \
        xmlwriter

# ----------------------------------------------------------
# Composer
# ----------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ----------------------------------------------------------
# Application
# ----------------------------------------------------------
COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --ignore-platform-reqs

COPY --from=assets /app/public/build ./public/build

# ----------------------------------------------------------
# Laravel writable directories
# ----------------------------------------------------------
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ----------------------------------------------------------
# Nginx / Supervisor
# ----------------------------------------------------------
COPY docker/nginx.conf /etc/nginx/http.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

EXPOSE 8080

# ----------------------------------------------------------
# Startup
# ----------------------------------------------------------
CMD ["sh", "-c", "\
export PORT=${PORT:-8080}; \
envsubst '$PORT' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf; \
php artisan optimize:clear || true; \
php artisan storage:link || true; \
exec supervisord -c /etc/supervisord.conf"]
