# Despliegue de la fase 1.0 a Ginernet

Estado: **pendiente de los pasos manuales**. Todo lo automatizable está hecho; lo que
queda necesita el panel de Ginernet y FileZilla.

## Lo que ya está listo en local

| Cosa | Dónde | Estado |
|---|---|---|
| Volcado inicial de MySQL (esquema + datos reales) | `srank-inicial.sql`, 344 KB, 26 tablas | hecho |
| Paquete FTP | `backend/deploy/` | lo genera `bash build-deploy.sh` |
| `.env` de producción | `backend/.env.produccion` (fuera de git) | **APP_KEY puesta; `DB_*` y `MAIL_*` vacíos** |

El volcado se regenera cuando haga falta con:

```bash
cd backend
mysqldump -u srank -psrank_local --no-tablespaces srank > srank-inicial.sql
```

## Lo que falta, en orden

### 1 · Panel de Ginernet

- Subdominio `rank-s.israelzamora.es` → `public_html/rank-s.israelzamora.es`.
  Si el panel deja fijar el Document Root, ponerlo en la subcarpeta `public/`. Si no, el
  `.htaccess` de la raíz ya reescribe en un salto y reinyecta la cabecera `Authorization`,
  que es lo que necesita Sanctum para leer el token.
- Base de datos MySQL nueva + usuario con todos los permisos sobre ella.
- Certificado SSL (Let's Encrypt desde el panel).

### 2 · Rellenar `backend/.env.produccion`

Faltan por poner:

- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — los del paso anterior.
- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD` — una cuenta de correo del dominio.
  **Sin esto la recuperación de contraseña no funciona**: el código de seis cifras se
  genera y se guarda, pero nunca sale del servidor.

`APP_KEY` ya está puesta y **no es la que se filtró**: esa se descartó.

### 3 · Subir la base de datos

Importar `srank-inicial.sql` por phpMyAdmin del panel.

### 4 · Subir el código

```bash
cd backend && bash build-deploy.sh
```

Arrastrar **todo el contenido** de `backend/deploy/` a
`public_html/rank-s.israelzamora.es/` con FileZilla. Después, desde el gestor de archivos
del panel:

```
chmod -R 775 storage bootstrap/cache public/uploads
```

### 5 · Comprobar

```bash
curl -i https://rank-s.israelzamora.es/api/system/today
```

- `401` con cuerpo JSON → bien.
- `500` → permisos de `storage/` o `.env` mal puesto.
- `404` → el Document Root no apunta donde debe.

Luego, con un usuario real:

```bash
curl -s -X POST https://rank-s.israelzamora.es/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"TU_CORREO","password":"TU_CONTRASEÑA"}'

curl -s https://rank-s.israelzamora.es/api/system/today -H 'Authorization: Bearer TOKEN'
```

Y que el correo sale:

```bash
curl -s -X POST https://rank-s.israelzamora.es/api/auth/forgot-password \
  -H 'Content-Type: application/json' -d '{"email":"TU_CORREO"}'
```

### 6 · Al terminar

Volver a este fichero y apuntar: fecha, nombre de la base de datos, usuario de MySQL
(**sin la contraseña**), cuenta de correo del SMTP y cualquier cosa que hubiera que tocar
en el panel.

## Recordatorio pendiente

`old/database/database.sqlite` y `old/database/database.2026-05-backup.sqlite` son la
**única copia** de los datos reales de FitLoop y no están en git. Hay que respaldarlos
fuera del proyecto antes de borrar `old/` al cerrar la fase 1.
