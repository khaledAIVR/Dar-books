#!/bin/sh
set -e
cd /var/www/html

# If /var/www/html/storage is a Railway volume, it mounts empty and hides the image storage tree.
mkdir -p storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs
chown -R www-data:www-data storage
chmod -R ug+rwx storage

# mod_php must use prefork only (image or deps can leave event/worker symlinks).
rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
    /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

# Railway / Render inject PORT; Apache defaults to 80.
PORT="${PORT:-80}"
if grep -q '^Listen ' /etc/apache2/ports.conf 2>/dev/null; then
    sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
else
    printf 'Listen %s\n' "${PORT}" >> /etc/apache2/ports.conf
fi
if [ -f /etc/apache2/sites-enabled/000-default.conf ]; then
    sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf
fi

# OAuth keys are gitignored; generate on first boot so Passport works after deploy.
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force 2>/dev/null || true
fi

# Legacy / Voyager uploads live under storage/app/public; URLs use /storage/... via this symlink.
# Versioned catalog images can live under public/media/... (no symlink; see PublicStorageUrl).
php artisan storage:link 2>/dev/null || true

# Production deploys (e.g. Render): run DB migrations before serving traffic.
# Set RUN_MIGRATIONS_ON_BOOT=false to skip (local Docker without DB, etc.).
if [ "${APP_ENV:-local}" = "production" ] && [ "${RUN_MIGRATIONS_ON_BOOT:-true}" != "false" ]; then
    php artisan migrate --force
fi

# passport:keys may create files as root; Apache/PHP run as www-data.
chown -R www-data:www-data storage

exec apache2-foreground
