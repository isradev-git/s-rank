# 📝 CHECKLIST: Deploy FitLoop en DonDominio

**Estructura del servidor DonDominio:**
- `/data/fitloop/` → backend privado (Laravel completo, NO accesible desde el navegador)
- `/public/fitloop/` → frontend público de FitLoop (raíz del subdominio `fitloop.thevikingmonkey.com`)
- `/public/` → sigue siendo la raíz de `thevikingmonkey.com` (no se toca)

> ⚠️ Paso previo en el panel DonDominio: crear el subdominio `fitloop.thevikingmonkey.com`
> y asignarle como document root la carpeta `/public/fitloop/`.

---

## ✅ FASE 1: Preparar en tu ordenador (YA LISTO)

### 1.1 Assets compilados ✅
```
[x] public/build/manifest.json — existe
[x] public/build/assets/app-*.js — existe
[x] public/build/assets/app-*.css — existe
```
No necesitas ejecutar `npm run build` salvo que hayas cambiado CSS/JS.

### 1.2 APP_KEY ✅
```
[x] Ya existe en .env: APP_KEY=base64:<la clave real, nunca en el repositorio>
```

### 1.3 Preparar el .env de producción
```
[ ] Renombra .env.production → .env  (reemplaza el existente)
[ ] Edita el nuevo .env:
    - APP_URL=https://tudominio.com  (pon tu dominio real)
    - Si MySQL: descomenta las líneas DB_* y rellena los datos de DonDominio
    - Si SQLite: deja la Opción B tal cual (ruta ya correcta)
```
> ⚠️ El `.env.production` tiene `APP_DEBUG=false` y `APP_ENV=production`. NO subas el `.env` original.

### 1.4 Archivos temporales ya creados ✅
```
[x] public/migrate.php — listo para subir
[x] public/seed.php    — listo para subir
```

### 1.5 public/index.php ya ajustado ✅
```
[x] Apunta a /data/fitloop/vendor/autoload.php
[x] Apunta a /data/fitloop/bootstrap/app.php
```

---

## ✅ FASE 2: Conectar a DonDominio por FTP

```
[ ] Abrir FileZilla (descarga en https://filezilla-project.org si no lo tienes)
[ ] Archivo → Gestor de sitios → Nuevo sitio
[ ] Protocolo: FTP (o SFTP, puerto 22, más seguro)
[ ] Host: ftp.tudominio.com
[ ] Usuario y Contraseña: los del panel DonDominio → Mi Hosting → FTP/SSH
[ ] Conectar
[ ] Verificar que ves /data/ y /public/ en el panel derecho
```

---

## ✅ FASE 2.5: Crear el subdominio en el panel DonDominio

```
[ ] Panel DonDominio → Mi Hosting → Subdominios
[ ] Crear subdominio: fitloop.thevikingmonkey.com
[ ] Document root / Carpeta raíz: /public/fitloop
[ ] Guardar
```

Esto garantiza que `fitloop.thevikingmonkey.com` solo accede a `/public/fitloop/`
y NO interfiere con los archivos de `thevikingmonkey.com` en `/public/`.

---

## ✅ FASE 3: Subir el backend a `/data/fitloop/`

En FileZilla, entra en `/data/`, crea la carpeta `fitloop` si no existe, y sube dentro:

```
[ ] app/
[ ] bootstrap/
[ ] config/
[ ] database/migrations/
[ ] database/seeders/
[ ] resources/
[ ] routes/
[ ] storage/
[ ] vendor/
[ ] .env            ← el que preparaste en Fase 1.3 (NO .env.example)
[ ] artisan
[ ] composer.json
[ ] composer.lock
```

**NO subas:**
```
[ ] node_modules/   ← pesa mucho, no se necesita en el servidor
[ ] .git/
[ ] .env.example
[ ] .env.production
[ ] tests/
[ ] phpunit.xml
[ ] vite.config.js
[ ] package.json / package-lock.json
[ ] debug_log.txt
```

---

## ✅ FASE 4: Subir el frontend a `/public/fitloop/`

En FileZilla, entra en `/public/`, crea la carpeta `fitloop` si no existe, y sube dentro:

```
[ ] index.php       ← ⭐ YA MODIFICADO (apunta a /data/fitloop/)
[ ] .htaccess
[ ] build/          ← assets compilados (CSS + JS)
[ ] assets/         ← si existe
[ ] favicon.ico
[ ] robots.txt
[ ] sw.js           ← service worker
[ ] migrate.php     ← script temporal de migraciones
[ ] seed.php        ← script temporal de seed de plantillas
```

**NO subas:**
```
[ ] hot             ← solo para Vite HMR en dev
```

---

## ✅ FASE 5: Crear la BD en DonDominio

### Si usas MySQL (recomendado)
```
En panel DonDominio → Mi Hosting → Bases de datos:
[ ] Crear BD nueva → anotar nombre: ___________________
[ ] Crear usuario  → anotar usuario: __________________
[ ] Crear contraseña → anotar contraseña: _____________
[ ] Editar el .env en el servidor (vía FTP) con estos datos
```

### Si usas SQLite (más simple)
```
[ ] En FileZilla, verificar que existe /data/fitloop/database/
[ ] Si no existe, crearla: clic derecho → Crear directorio
[ ] Crear también el archivo vacío /data/fitloop/database/database.sqlite
[ ] Verificar que en el .env del servidor tienes exactamente:
    DB_CONNECTION=sqlite
    DB_DATABASE=/data/fitloop/database/database.sqlite
[ ] IMPORTANTE: no abras la web antes de que exista ese archivo
[ ] Luego ejecutar migrate.php para crear las tablas
```

> ⚠️ Laravel intenta abrir la base de datos en la primera petición porque las sesiones usan BD.
> Si existe la carpeta pero no el archivo `database.sqlite`, verás un error 500 antes incluso de correr migraciones.

---

## ✅ FASE 6: Ejecutar migraciones

Abre en el navegador:
```
https://tudominio.com/migrate.php
```
Debes ver: `✅ Migraciones completadas correctamente.`

**🚨 BORRA `migrate.php` del servidor inmediatamente:**
FileZilla → navega a `/public/` → clic derecho sobre `migrate.php` → Eliminar

---

## ✅ FASE 7: Cargar plantillas de entrenamiento

Abre en el navegador:
```
https://tudominio.com/seed.php
```
Debes ver: `✅ Plantillas cargadas correctamente.`

**🚨 BORRA `seed.php` del servidor inmediatamente.**

---

## ✅ FASE 8: Verificaciones

```
[ ] https://tudominio.com/          → pantalla de login de FitLoop
[ ] https://tudominio.com/.env      → error 403 (NO debe mostrar contenido)
[ ] Crear cuenta nueva              → redirige al dashboard
[ ] Iniciar sesión                  → funciona
[ ] Registrar entrenamiento         → aparece XP ganado
[ ] Ver historial                   → aparece el entrenamiento
[ ] Cerrar navegador y volver       → sigue logueado
[ ] F12 → Network → recarga        → CSS y JS con status 200
```

---

## ✅ FASE 9: Seguridad final

```
[ ] APP_DEBUG=false en el .env del servidor
[ ] APP_ENV=production en el .env del servidor
[ ] migrate.php eliminado de /public/
[ ] seed.php eliminado de /public/
[ ] node_modules/, .git/, tests/ NO están en el servidor
[ ] HTTPS activado en DonDominio (Let's Encrypt, gratis)
```

---

## 🚨 Solución de problemas

| Problema | Solución |
|----------|----------|
| Error 500 o página en blanco | Pon `APP_DEBUG=true` temporalmente, recarga y lee el error |
| CSS/JS no carga | Verifica que `public/build/` se subió completo |
| 404 en rutas (solo `/` funciona) | `.htaccess` no está en `/public/` o mod_rewrite desactivado — contacta soporte |
| Error de BD | Verifica datos de conexión en `.env` del servidor |
| `Database file ... database.sqlite does not exist` | Estás usando SQLite pero falta el archivo físico. Crea `/data/fitloop/database/database.sqlite`, verifica `DB_DATABASE=/data/fitloop/database/database.sqlite` y limpia caché de config si procede. |
| Plantillas no aparecen | Vuelve a subir y ejecutar `seed.php` (bórralo después) |
| Sesión se pierde al cerrar | Verifica `APP_ENV=production` en `.env` del servidor |
| `index.php` da error de ruta | Verifica que las rutas apuntan a `/data/fitloop/` |

---

## 📊 Estructura en el servidor

```
SERVIDOR DonDominio
├── data/
│   └── fitloop/              ← backend Laravel (privado, no accesible desde web)
│       ├── app/
│       ├── bootstrap/
│       ├── config/
│       ├── database/
│       ├── resources/
│       ├── routes/
│       ├── storage/
│       ├── vendor/
│       ├── .env              ← secretos (APP_DEBUG=false, DB, etc.)
│       └── artisan
└── public/                   ← raíz de thevikingmonkey.com (NO TOCAR)
    ├── (archivos existentes de thevikingmonkey.com)
    └── fitloop/              ← raíz de fitloop.thevikingmonkey.com
        ├── index.php         ← apunta a /data/fitloop/ (../../data/fitloop/)
        ├── .htaccess
        ├── build/            ← assets compilados
        ├── assets/
        └── robots.txt
```

---

**Última actualización:** Abril 2026
**Tiempo estimado:** 1-2 horas (incluida transferencia FTP)
