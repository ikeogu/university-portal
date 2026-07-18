# Stage 1: build frontend assets
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP app
FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache nginx supervisor libzip-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip bcmath opcache mbstring gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 8080

# config:cache is deliberately NOT run during the build above — Railway
# injects real env vars (DB credentials, APP_KEY, etc.) at container
# runtime, not at `docker build` time, and this image has no .env baked in.
# Caching config at build time would freeze in empty/missing values that
# every later request would then be stuck with. Caching here instead, right
# before the app starts serving, means it runs once per container start
# with the real runtime environment already present.
CMD ["sh", "-c", "php artisan config:cache && exec supervisord -c /etc/supervisord.conf"]
