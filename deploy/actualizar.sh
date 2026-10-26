#!/usr/bin/env bash
# Actualiza CentroGoma en el VPS. Correr como root: bash /var/www/centrogoma/deploy/actualizar.sh
# Ver docs/DESPLIEGUE_VPS.md §7.
set -euo pipefail

cd /var/www/centrogoma

php artisan down --retry=30 || true
trap 'php artisan up' EXIT

rm -f bootstrap/cache/*.php
git pull --ff-only
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

chown -R www-data:www-data storage bootstrap/cache
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

if [ -n "$(git status --porcelain)" ]; then
    echo "ATENCIÓN: git status no está limpio:" >&2
    git status --short >&2
fi

echo "OK: $(git log --oneline -1)"
