# Preview / small deploy: single URL (Laravel + Nuxt in public/_nuxt) + Apache.
# Build from repo root: waha-darin/

# --- Frontend: Nuxt SPA -> public/_nuxt (same-origin API /api) ---
FROM node:16-bullseye AS frontend
WORKDIR /build
COPY client/package.json client/package-lock.json ./client/
RUN cd client && npm ci
COPY client ./client
COPY public ./public
# Default: relative /api (see client/nuxt.config.js). Override at build: docker build --build-arg API_URL=/api .
ARG API_URL=
ENV API_URL=${API_URL}
RUN cd client && npm run generate

# --- Backend: PHP 7.4 + Apache (Laravel 7) ---
FROM php:7.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        gd \
    && a2enmod rewrite deflate headers \
    && rm -rf /var/lib/apt/lists/*

# OPcache for ~50% PHP speedup
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

COPY . .
COPY --from=frontend /build/public/_nuxt ./public/_nuxt

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# DocumentRoot = Laravel public/ + gzip compression for all text responses
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf '<IfModule mod_deflate.c>\n  AddOutputFilterByType DEFLATE text/html text/plain text/css application/json application/javascript text/xml application/xml\n</IfModule>\n<IfModule mod_expires.c>\n  ExpiresActive On\n  ExpiresByType application/javascript "access plus 1 year"\n  ExpiresByType text/css "access plus 1 year"\n  ExpiresByType image/png "access plus 1 year"\n</IfModule>\n' >> /etc/apache2/conf-available/gzip.conf \
    && a2enconf gzip 2>/dev/null || true \
    && a2enmod expires 2>/dev/null || true

COPY docker/entrypoint-preview.sh /usr/local/bin/entrypoint-preview.sh
RUN chmod +x /usr/local/bin/entrypoint-preview.sh

# Fail build if Apache config is invalid (catches duplicate MPM, etc.).
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
        /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
    && a2enmod mpm_prefork \
    && apache2ctl -t

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint-preview.sh"]
