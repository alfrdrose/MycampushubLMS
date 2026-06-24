# ---- Stage 1: build frontend assets with Node ----
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY . .
RUN npm run build

# ---- Stage 2: PHP runtime ----
FROM php:8.4-cli-alpine

# System deps + PHP extensions Laravel/Filament/medialibrary need
RUN apk add --no-cache \
        git curl unzip libzip-dev oniguruma-dev icu-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev sqlite sqlite-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_sqlite zip intl gd bcmath exif pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP deps first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# App source
COPY . .

# Built assets from the frontend stage
COPY --from=frontend /app/public/build ./public/build

# Finish composer. Use --no-scripts so Laravel is NOT booted during the build
# (package:discover would try to connect to the DB, which doesn't exist yet —
# it's created at runtime in start.sh). Discovery is regenerated at boot.
RUN composer dump-autoload --optimize --no-scripts \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render provides $PORT; default to 8080 locally
ENV PORT=8080
EXPOSE 8080

# Startup: prepare SQLite, migrate+seed, cache config, then serve
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["/usr/local/bin/start.sh"]
