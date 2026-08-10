#!/usr/bin/env bash
# Genera ./deploy con TODO lo que se sube por FTP a Ginernet.
# El subdominio rank-s.israelzamora.es apunta a public_html/rank-s.israelzamora.es/
# y ahí va el contenido COMPLETO de ./deploy/.
#
# Uso:  bash build-deploy.sh
set -euo pipefail
cd "$(dirname "$0")"

if [[ ! -f .env.produccion ]]; then
  cat >&2 <<'MSG'
ERROR: falta .env.produccion.

Copia .env.produccion.example a .env.produccion y rellena:
  - APP_KEY        (php artisan key:generate --show)
  - DB_*           credenciales de MySQL de Ginernet
  - MAIL_*         SMTP para la recuperación de contraseña

Ese fichero NO va en git: contiene credenciales.
MSG
  exit 1
fi

rm -rf deploy
mkdir -p deploy

rsync -a \
  --exclude='/deploy' \
  --exclude='/.git' \
  --exclude='/tests' \
  --exclude='/docs' \
  --exclude='/.env' \
  --exclude='/.env.*' \
  --exclude='/phpunit.xml' \
  --exclude='/.phpunit.result.cache' \
  --exclude='/build-deploy.sh' \
  --exclude='*.md' \
  --exclude='/database/*.sqlite' \
  --exclude='/database/*.sqlite-journal' \
  ./ deploy/

# .env de producción -> .env  (MySQL de Ginernet, APP_DEBUG=false)
cp .env.produccion deploy/.env

# Datos efímeros de desarrollo: no viajan
find deploy/storage/framework/sessions \
     deploy/storage/framework/cache \
     deploy/storage/framework/views -type f ! -name '.gitignore' -delete 2>/dev/null || true
rm -f deploy/storage/logs/*.log 2>/dev/null || true

# Red de seguridad: si algún día vuelve a colarse una base de datos, que falle aquí
if find deploy -name '*.sqlite' | grep -q .; then
  echo "ERROR: se ha colado una base de datos en deploy/. Abortando." >&2
  exit 1
fi

echo "OK -> ./deploy listo."
echo "Sube TODO el contenido de ./deploy/ a public_html/rank-s.israelzamora.es/"
echo "Tras subir: chmod -R 775 storage bootstrap/cache public/uploads"
