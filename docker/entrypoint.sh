#!/bin/sh

# Salir inmediatamente si un comando falla
set -e

# Asegurar directorios necesarios para Supervisor en runtime
mkdir -p /var/log/supervisor /var/run

# Crear la base de datos SQLite si no existe
if [ ! -f "/var/www/database/database.sqlite" ]; then
    touch /var/www/database/database.sqlite
    chown www-data:www-data /var/www/database/database.sqlite
fi

# Optimizar Laravel
php artisan storage:link --force
php artisan config:cache
php artisan route:cache

# Ejecutar migraciones (importante para el monolito)
php artisan migrate --force

# Dar el control a Supervisor (que lanzará Nginx y PHP)
exec "$@"