# 🚀 Guía COMPLETA: Deploy de FitLoop en DonDominio

**Proyecto:** FitLoop (Laravel 12 + Blade + Vite)  
**Objetivo:** Subir desde tu ordenador a DonDominio sin terminal ✅

---

## 📋 Requisitos previos en el servidor

Antes de empezar, **verifica que tu hosting de DonDominio tiene:**

- ✅ PHP 8.2 o superior (`8.3` es ideal)
- ✅ Extensiones PHP: `pdo`, `pdo_mysql`, `pdo_sqlite`, `curl`, `mbstring`, `openssl`, `json`, `xml`, `gd`
- ✅ Mod_rewrite habilitado (para el `.htaccess`)
- ✅ Acceso FTP/SFTP
- ✅ Soporte para SQLite o MySQL

**Dónde verificar en DonDominio:**
1. Panel → **Mi Hosting** → **Datos técnicos** → verás la versión de PHP
2. Si PHP < 8.2, pide upgrade al soporte

> 💡 Los hostings de DonDominio suelen permitir elegir versión de PHP en el panel. Busca "PHP Version" o contacta al soporte.

---

## 🎯 Paso 1: Preparar los archivos en tu ordenador

### 1.1 — Compilar los assets (CSS + JS)

**Es obligatorio.** Los archivos compilados deben estar listos antes de subir.

1. Abre una terminal en tu carpeta del proyecto
2. Ejecuta:

```bash
npm run build
```

**Verifica:** Debe crear/actualizar la carpeta `public/build/` con archivos como:
- `public/build/manifest.json`
- `public/build/assets/*.js`
- `public/build/assets/*.css`

Si ves esos archivos, está listo ✅

### 1.2 — Generar la APP_KEY

Esta clave se usa para encripción. **Debe ser única en producción.**

1. En tu terminal:

```bash
php artisan key:generate
```

2. Verifica que en tu `.env` aparece una línea como:

```
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Si ya la tienes, no necesitas hacer nada más.

### 1.3 — Preparar el archivo `.env` para producción

**En tu ordenador**, edita el `.env` existente con estos cambios:

```env
# Cambiar estos valores
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

# Si usas MySQL en producción (recomendado)
DB_CONNECTION=mysql
DB_HOST=localhost              # O la IP que te dé DonDominio
DB_PORT=3306
DB_DATABASE=nombre_bd          # El nombre que creaste en DonDominio
DB_USERNAME=usuario_bd         # El usuario que creaste
DB_PASSWORD=contraseña_bd      # La contraseña

# O si usas SQLite (más simple)
DB_CONNECTION=sqlite
DB_DATABASE=/camino/a/database.sqlite

# Otros (dejar como está)
LOG_CHANNEL=stack
LOG_LEVEL=error                # En prod: error (no debug)
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

**Importante:**
- `APP_URL` debe ser **exactamente** 🔒 `https://tudominio.com` (sin barra final)
- `APP_DEBUG=false` siempre en producción ⚠️
- La contraseña de BD no debe tener comillas

### 1.4 — Archivos que incluir/excluir

**Archivos/carpetas a subir:**
- ✅ `app/`
- ✅ `bootstrap/`
- ✅ `config/`
- ✅ `database/migrations/` 
- ✅ `database/seeders/`
- ✅ `public/` (incluyendo `build/`)
- ✅ `resources/`
- ✅ `routes/`
- ✅ `storage/`
- ✅ `vendor/` (obligatorio)
- ✅ `.env` (actual, no .example)
- ✅ `.htaccess`
- ✅ `artisan`
- ✅ `composer.json` y `composer.lock`

**Archivos/carpetas a EXCLUIR (nunca subas):**
- ❌ `node_modules/` (pesan mucho, no se usan en server)
- ❌ `.git/` (historial de desarrollo)
- ❌ `.env.example`
- ❌ `debug_log.txt`
- ❌ `phpunit.xml`
- ❌ `tests/`
- ❌ `vite.config.js`
- ❌ `package.json` y `package-lock.json`

---

## 🔗 Paso 2: Conectar FileZilla a tu servidor DonDominio

### Obtener datos FTP

1. En el panel de DonDominio: **Mi Hosting** → **FTP/SSH**
2. Anota estos datos:
   - **Host:** `ftp.tudominio.com` (o el que veas)
   - **Usuario:** (suele ser igual a tu usuario de hosting)
   - **Contraseña:** (la misma del panel)
   - **Puerto:** 21 (para FTP) o 22 (para SFTP, más seguro)

### Configurar FileZilla

1. Abre FileZilla
2. **Archivo → Gestor de sitios → Nuevo sitio**
3. Rellena:
   - **Protocolo:** FTP (o SFTP si prefieres más seguridad)
   - **Host:** `ftp.tudominio.com`
   - **Usuario:** tu usuario FTP
   - **Contraseña:** tu contraseña FTP
   - **Puerto:** 21 (o 22 para SFTP)
4. Haz clic en **Conectar**

**Una vez conectado:**
- Panel izquierdo = tu ordenador
- Panel derecho = el servidor

---

## 📤 Paso 3: Estructura de carpetas en DonDominio

En tu hosting (como se ve en tu panel) la estructura real es:

```
/
├── data/                 ← Privado (backend Laravel completo)
└── public/               ← Público (raíz web del dominio)
```

**Estrategia recomendada (Laravel correcto):**

1. Sube el proyecto Laravel completo a `/data/fitloop/` (o el nombre que prefieras).
2. Sube solo el contenido de la carpeta local `public/` a `/public/`.
3. En `/public/index.php`, ajusta rutas para que apunten a `/data/fitloop`.

Con esto, `.env`, `app/`, `config/`, `vendor/` y base de datos quedan fuera de la carpeta pública.

---

## 📂 Paso 4: Subir el código a FTP

En FileZilla (ya conectado):

1. **Panel izquierdo** (tu ordenador): navega a tu carpeta del proyecto FitLoop
2. **Panel derecho** (servidor): entra en `/data/`
3. Crea carpeta `fitloop`
4. Sube dentro de `/data/fitloop/`: `app/`, `bootstrap/`, `config/`, `database/`, `resources/`, `routes/`, `storage/`, `vendor/`, `.env`, `artisan`, `composer.json`, `composer.lock`
5. En `/public/`, sube solo el contenido de la carpeta local `public/` (`index.php`, `.htaccess`, `assets`, `build`, etc.)

**Con botones:**
1. Selecciona los archivos/carpetas
2. Clic derecho → **Cargar**

**Espera a que termine.** Verás un log abajo en FileZilla diciendo cuántos archivos se cargaron.

### Ajuste obligatorio de `/public/index.php`

Después de subir, edita `/public/index.php` para que cargue Laravel desde `/data/fitloop`:

```php
require __DIR__.'/../data/fitloop/vendor/autoload.php';

$app = require_once __DIR__.'/../data/fitloop/bootstrap/app.php';
```

### Control de lo que subes

Para asegurar que no subes lo innecesario:

1. En FileZilla: **Editar → Opciones** (o **Edit → Preferences** en Mac)
2. Busca **Transferencia de archivos → Ignorar archivos**
3. Añade a la "lista de ignores":
   ```
   node_modules
   .git
   tests
   .env.example
   debug_log.txt
   ```

Así no se subirán accidentalmente.

---

## 🗄️ Paso 5: Crear la base de datos en DonDominio

### Si usas MySQL (recomendado)

1. En el panel DonDominio: **Mi Hosting** → **Bases de datos**
2. Crea una nueva BD:
   - **Nombre:** p.ej. `fitloop_prod`
   - **Usuario:** p.ej. `fitloop_user`
   - **Contraseña:** crea una fuerte
   - Toma nota de estos datos

3. En tu `.env` (en el servidor), pon:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_DATABASE=fitloop_prod
   DB_USERNAME=fitloop_user
   DB_PASSWORD=tu_contraseña
   ```

### Si usas SQLite (más simple, pero menos recomendado)

Usa esta ruta en `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/data/fitloop/database/database.sqlite
```

Crea el archivo vacío `database.sqlite` dentro de `/data/fitloop/database/` si aún no existe.

Importante:
- No basta con que exista la carpeta `/data/fitloop/database/`; el fichero `database.sqlite` debe existir físicamente.
- Laravel usa la base de datos desde la primera petición si `SESSION_DRIVER=database`, así que si el fichero no existe verás un error 500 antes de poder entrar en la app.
- Crea o sube primero el archivo vacío y después ejecuta `migrate.php`.

---

## ⚙️ Paso 6: Configurar permisos y directorios en el servidor

Los directorios de Laravel necesitan permisos especiales. Pero **DonDominio usa suPHP/PHP-FPM**, que generalmente asigna permisos automáticamente.

**Aún así, si tienes acceso SSH, ejecuta:**

```bash
chmod -R 775 storage bootstrap/cache
chmod 644 .env
```

Si **no tienes acceso SSH:**
1. Crea en tu ordenador una carpeta vacía `storage/logs/` (para que exista cuando subas)
2. Idem con `bootstrap/cache/`

---

## 🛠️ Paso 7: Ejecutar migraciones desde el navegador (SIN terminal)

Crea un archivo temporal que ejecute las migraciones:

### 7.1 — Crear `/public/migrate.php`

En tu ordenador, crea este archivo en la carpeta `public/`:

```php
<?php
// FILE: /public/migrate.php
// ELIMINA ESTE ARCHIVO DESPUES DE USARLO

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__ . '/../data/fitloop/vendor/autoload.php';
$app = require_once __DIR__ . '/../data/fitloop/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
   Artisan::call('migrate', ['--force' => true]);
   echo '<pre>' . htmlspecialchars(Artisan::output(), ENT_QUOTES, 'UTF-8') . '</pre>';
   echo '<p>Migraciones completadas.</p>';
} catch (Throwable $e) {
   echo '<p>Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
```

Sube este archivo a `/public/` en FileZilla.

### 7.2 — Ejecutar migraciones desde el navegador

1. Abre en el navegador:
   ```
   https://tudominio.com/migrate.php
   ```

2. Deberías ver un listado de migraciones ejecutadas ✅

3. **Elimina el archivo** `migrate.php` del servidor al terminar.
   ```php
   <?php
   // Este archivo ha sido eliminado
   die('Acceso denegado');
   ```

---

## 🎬 Paso 8: Ser seed (cargar datos iniciales)

Si quieres cargar los templates de entrenamientos:

1. En la carpeta `database/seeders/`, verificar que existe `TemplatesTableSeeder.php`
2. Crear un archivo `public/seed.php`:

```php
<?php
// FILE: /public/seed.php
// ELIMINA ESTE ARCHIVO DESPUES DE USARLO

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require __DIR__ . '/../data/fitloop/vendor/autoload.php';
$app = require_once __DIR__ . '/../data/fitloop/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

try {
   Artisan::call('db:seed', ['--class' => 'TemplatesTableSeeder', '--force' => true]);
   echo '<pre>' . htmlspecialchars(Artisan::output(), ENT_QUOTES, 'UTF-8') . '</pre>';
   echo '<p>Seed completado.</p>';
} catch (Throwable $e) {
   echo '<p>Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
```

Igual que antes:
1. Sube a `/public/seed.php`
2. Abre en navegador: `https://tudominio.com/seed.php`
3. **Elimina el archivo** después

---

## ✅ Paso 9: Verificaciones finales

### 1. Verifica la estructura en el servidor

En FileZilla, conectado a `/data/fitloop/`, deberías ver:

```
app/
bootstrap/
config/
database/
...
resources/
routes/
storage/
vendor/
.env  ← Está aquí (no .example)
artisan

Y en `/public/` deberías ver:

```
index.php
.htaccess
build/
assets/
favicon.ico
robots.txt
```
```

### 2. Prueba de acceso web

1. Abre `https://tudominio.com/` en el navegador
2. Deberías ver la **página de login** ✅

### 3. Verifica que no se ve `.env`

1. Intenta acceder a `https://tudominio.com/.env`
2. Deberías ver un error 403 o que se descarga (no se ve el contenido)

### 4. Prueba el login

1. Ve a `https://tudominio.com/login`
2. Intenta iniciar sesión con credenciales reales

### 5. Verifica los assets (CSS/JS)

1. Abre la inspección del navegador (F12)
2. Ir a la pestaña **Network** 
3. Recarga la página
4. Los archivos `.js` y `.css` de `build/` deben cargar con status **200** ✅

---

## ⚠️ Problemas comunes y soluciones

### Error 500 al abrir el sitio

**Causas comunes:**
- PHP < 8.2 instalado
- Extensión PDO no disponible
- `.env` no está en el site root

**Solución:**
1. Verifica PHP version en el panel DonDominio (actualizarla si es < 8.2)
2. Contacta al soporte para activar extensión PDO
3. Verifica que `.env` está en `/data/fitloop/` (nunca en `/public/`)

### Error "Call to undefined function 'str_starts_with'"

**Causa:** PHP < 8.0
**Solución:** Actualiza a PHP 8.2+ en el panel DonDominio

### 404 en rutas que no sean `/`

**Causa:** `.htaccess` no configurado o mod_rewrite desactivado
**Solución:**
1. Verifica que existe `/public/.htaccess` (exactamente así, sin extensión)
2. Contacta soporte para activar mod_rewrite

### Página blanca (sin errores visibles)

1. En tu `.env` del servidor, cambia temporalmente:
   ```env
   APP_DEBUG=true
   ```
2. Recarga el navegador
3. Ahora deberías ver el error detallado

**Después de arreglarlo, vuelve a `APP_DEBUG=false`**

---

## 🔐 Seguridad: Checklist final

Antes de dar por completado:

- [ ] `APP_DEBUG=false` en `.env`
- [ ] `.env` NO es accesible desde web (intenta `https://tudominio.com/.env`)
- [ ] Carpetas `storage/` y `bootstrap/cache/` tienen permisos 775 (si tienes SSH)
- [ ] `node_modules/`, `.git/`, `tests/` NO están en el servidor
- [ ] La BD está configurada y protegida con contraseña
- [ ] SSL (HTTPS) está activado (DonDominio lo ofrece gratis con Let's Encrypt)
- [ ] Backup de la BD está automatizado

---

## 📞 Soporte DonDominio

Si necesitas ayuda del soporte:

1. Panel → **Ayuda** → **Contactar soporte**
2. Diles:
   - "Necesito usar Laravel 12 (PHP 8.2+) con mod_rewrite y base de datos MySQL"
   - "¿Qué versión de PHP tengo y puedo actualizar a 8.3?"
   - "¿Está activado mod_rewrite y extensión PDO?"

---

## 📚 Comandos útiles (si tienes SSH)

Si DonDominio te da acceso SSH (mejor que FTP), puedes ejecutar estos comandos directo:

```bash
# Conectar por SSH
ssh usuario@dominio.com

# Navegar al site
cd /data/fitloop

# Instalar dependencias PHP
composer install --no-dev

# Generar key (si no la tienes)
php artisan key:generate

# Ejecutar migraciones
php artisan migrate --force

# Cargar seeders
php artisan db:seed --class=TemplatesTableSeeder

# Ver logs
tail -f storage/logs/laravel.log
```

**SSH es mucho más rápido que FTP**, pero no es obligatorio.

---

## ✨ ¡Listo!

Una vez completado todo:

✅ Acceso por `https://tudominio.com`  
✅ Base de datos funcionando  
✅ Assets compilados y sirviendo  
✅ Migraciones ejecutadas  
✅ Seguridad configurada  

**Tu app está en producción! 🚀**

---

## 📋 Resumen de archivos a preparar localmente

```bash
# En tu ordenador, antes de subir:

1. npm run build                    # Compilar assets
2. php artisan key:generate         # Generar APP_KEY (si no existe)
3. Editar .env para producción      # DB, APP_ENV=production, etc.
4. Crear public/migrate.php         # Script temporal para migraciones
5. Crear public/seed.php            # Script temporal para seeders (opcional)
6. Todo en una carpeta lista        # Subir vía FTP
```

---

**Última actualización:** Marzo 2026  
**Versión:** Laravel 12 + Vite  
**Proyecto:** FitLoop
