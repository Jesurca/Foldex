#!/bin/bash
# Arranque de Foldex en Azure App Service (Linux, PHP 8.4). Se configura como "Startup Command":
#   bash /home/site/wwwroot/deploy/azure/startup.sh
# Corre en cada reinicio, antes de que la imagen levante php-fpm: no debe quedarse bloqueado.
cd /home/site/wwwroot || exit 1

# 1. Nginx: raíz en public/ y rutas de Laravel (ver nginx.conf).
cp deploy/azure/nginx.conf /etc/nginx/sites-available/default
service nginx reload || service nginx restart || true

# 2. Carpetas que Laravel necesita escribir (el disco /home es persistente: las portadas sobreviven a los despliegues).
mkdir -p storage/app/public/portadas storage/app/private \
         storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs bootstrap/cache
php artisan storage:link --force >/dev/null 2>&1 || true

# 3. Cachés de producción (lee la configuración de las App Settings de Azure).
php artisan optimize:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 4. Base de datos: migraciones y catálogos/admin (los seeders son idempotentes).
php artisan migrate --force || echo "AVISO: migrate falló (¿base de datos accesible?)"
php artisan db:seed --force || echo "AVISO: db:seed falló"

echo "Foldex listo."
