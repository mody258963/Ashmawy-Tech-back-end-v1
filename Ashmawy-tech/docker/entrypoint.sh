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

# IoT ingestion auto-start:
# - Default image CMD runs supervisord, which already starts:
#     iot-mqtt-subscribe  → php artisan iot:mqtt-subscribe
#     laravel-queue-iot   → php artisan queue:work … --queue=iot
#   See docker/supervisord.conf
# - If your platform overrides CMD (not supervisord), set START_IOT_INGESTION=true
#   so these run in the background before the main process.
case "${1:-}" in
  /usr/bin/supervisord)
    ;;
  *)
    if [ "${START_IOT_INGESTION}" = "true" ]; then
      echo "entrypoint: START_IOT_INGESTION=true — starting MQTT subscriber and iot queue worker"
      php artisan iot:mqtt-subscribe >> storage/logs/iot-mqtt.log 2>&1 &
      php artisan queue:work --queue=iot --sleep=1 --tries=3 --backoff=2 --timeout=120 >> storage/logs/iot-queue.log 2>&1 &
    fi
    ;;
esac

exec "$@"

