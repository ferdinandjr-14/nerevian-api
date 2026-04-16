#!/bin/sh
set -e

cd /var/www

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=.' .env; then
    php artisan key:generate --force
fi

if [ -z "$APP_KEY" ]; then
    unset APP_KEY
fi

exec "$@"