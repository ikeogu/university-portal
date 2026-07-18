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
# Must be >=8.4.1 — symfony/http-foundation (and ~14 other Symfony 8.x
# components this app locks) use PHP 8.4 property hooks internally, so
# anything older fails with a raw parse error during `composer install`,
# not an application bug. See RAILWAY.md.
FROM php:8.4-fpm-alpine

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
    gettext \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libxml2-dev \
    zip

# Install PHP extensions — dom/fileinfo/simplexml/xml/xmlreader/xmlwriter are
# all required by phpoffice/phpspreadsheet (via maatwebsite/excel, used
# throughout this app's import/upload system) per composer.lock, but aren't
# part of php:8.4-fpm-alpine's default build. Laravel boots every registered
# package's service provider on every request regardless of route, so a
# missing one here breaks even /up, not just spreadsheet code paths —
# confirmed by cross-checking composer.lock's declared ext-* requirements
# against this list, not guessed from the symptom alone.
#
# ext-iconv is deliberately NOT installed here: Alpine's musl libc ships its
# own iconv, which isn't what PHP's iconv extension source expects
# (references GNU libiconv's _libiconv_version/libiconv() specifically) —
# it fails to compile without a separate gnu-libiconv package and
# Alpine/PHP-version-sensitive compiler flags. phpspreadsheet's iconv usage
# is for legacy-encoding .xls/CSV handling; this app only ever reads/writes
# .xlsx, so it's not expected to be on any path this app actually exercises.
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
        fileinfo \
        simplexml \
        xml \
        xmlreader \
        xmlwriter

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

# Nginx & Supervisor — nginx.conf is a template, not the final config:
# Railway assigns $PORT at container *startup*, not build time, so "listen"
# can't be baked in here. It's rendered by CMD below instead.
COPY docker/nginx.conf /etc/nginx/http.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 8080

# envsubst is restricted to just $PORT — nginx's own runtime variables
# ($uri, $query_string, etc.) use the same ${...} syntax and would
# otherwise get silently blanked out by an unrestricted envsubst pass.
CMD ["sh", "-c", "\
export PORT=\"${PORT:-8080}\" && \
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf && \
php artisan config:clear && \
php artisan route:clear && \
php artisan view:clear && \
php artisan event:clear && \
php artisan config:cache && \
exec supervisord -c /etc/supervisord.conf"]
