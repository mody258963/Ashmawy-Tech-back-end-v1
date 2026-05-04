#!/bin/sh
set -e

cd /var/www/html

if [ ! -f ".env" ] && [ -f ".env.example" ]; then
  cp .env.example .env
fi

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ ! -f "storage/oauth-private.key" ] || [ ! -f "storage/oauth-public.key" ]; then
  php artisan passport:keys --force || true
fi

if [ -f "storage/oauth-private.key" ] && [ -f "storage/oauth-public.key" ]; then
  chown www-data:www-data storage/oauth-private.key storage/oauth-public.key
  chmod 600 storage/oauth-private.key
  chmod 600 storage/oauth-public.key
fi

php artisan optimize:clear || true

if [ "${RUN_MIGRATIONS}" = "true" ]; then
  php artisan migrate --force || true
fi

php artisan iot:ensure-passport-client -n || true

exec "$@"

