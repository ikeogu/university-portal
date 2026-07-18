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
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    unzip \
    bash \
    curl \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libxml2-dev \
    zip

# Install PHP extensions
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
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application
COPY . .

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --optimize-autoloader \
    --ignore-platform-reqs

# Copy frontend build
COPY --from=assets /app/public/build ./public/build

# Laravel writable directories
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

# Nginx & Supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 8080

CMD ["sh", "-c", "\
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear && \
php artisan event:clear && \
php artisan config:cache && \
exec supervisord -c /etc/supervisord.conf"]
