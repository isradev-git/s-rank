#!/usr/bin/env bash
# Genera ./deploy con TODO lo que se sube por FTP a Ginernet (carpeta unica).
# El dominio fitloop.israelzamora.es apunta a public_html/fitloop.israelzamora.es/
# y ahi va el contenido COMPLETO de ./deploy/ (arrastrar y soltar en FileZilla).
#
# Uso:  bash build-deploy.sh
#
# Incluye: app bootstrap config database public resources routes storage vendor
#          artisan composer.json composer.lock .htaccess  +  .env (desde .env_produccion)
# Excluye: dev/cruft (node_modules, .git, tests, docs, *.md, scripts de import, etc.)
set -euo pipefail
cd "$(dirname "$0")"

rm -rf deploy
mkdir -p deploy

rsync -a \
  --exclude='/deploy' \
  --exclude='/.git' \
  --exclude='/.github' \
  --exclude='/.claude' \
  --exclude='/node_modules' \
  --exclude='/tests' \
  --exclude='/docs' \
  --exclude='/backup_legacy' \
  --exclude='/alimentos' \
  --exclude='/.env' \
  --exclude='/.env.example' \
  --exclude='/.env_produccion' \
  --exclude='/phpunit.xml' \
  --exclude='/vite.config.js' \
  --exclude='/package.json' \
  --exclude='/package-lock.json' \
  --exclude='/.editorconfig' \
  --exclude='/.gitattributes' \
  --exclude='/.gitignore' \
  --exclude='/_update_user.php' \
  --exclude='/build-deploy.sh' \
  --exclude='*.md' \
  --exclude='public/migrate.php' \
  --exclude='public/seed.php' \
  --exclude='public/_diag_users.php' \
  --exclude='public/_diag_error.php' \
  ./ deploy/

# .env de produccion -> .env (ya con URL Ginernet + SQLite por defecto)
cp .env_produccion deploy/.env

# Limpia datos efimeros de dev: sesiones/cache/views compiladas + logs
find deploy/storage/framework/sessions \
     deploy/storage/framework/cache \
     deploy/storage/framework/views -type f ! -name '.gitignore' -delete 2>/dev/null || true
rm -f deploy/storage/logs/*.log 2>/dev/null || true

echo "OK -> ./deploy listo."
echo "Sube TODO el contenido de ./deploy/ a public_html/fitloop.israelzamora.es/"
echo "Tras subir: chmod -R 775 storage bootstrap/cache database (gestor de archivos)."
