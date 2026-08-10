# 🚀 Guía para subir FitLoop a producción en DonDominio

> **Sin terminal en ningún momento.** Todo se prepara editando archivos en tu ordenador
> y se sube al servidor por FTP con FileZilla. No necesitas abrir ninguna terminal.

---

## Herramientas que necesitas antes de empezar

- **FileZilla** (gratis) → descárgalo en https://filezilla-project.org
- Los datos FTP de tu hosting: los encuentras en el panel de DonDominio → **Hosting → Acceso FTP**

---

## Cómo funciona la estructura de DonDominio

DonDominio separa el servidor en dos carpetas:

- `/data/` → archivos **privados**, NO accesibles desde el navegador (backend, base de datos, configuración)
- `/public/` → archivos **públicos**, accesibles desde `tudominio.com` (frontend, CSS, JS, imágenes)

```
Servidor DonDominio
├── data/
│   └── backend-php/        ← aquí va todo el PHP del backend
│       ├── api/
│       ├── config/
│       ├── src/
│       ├── db/             ← aquí se creará la base de datos
│       └── .env            ← secretos de la app
└── public/                 ← aquí va todo lo que ve el usuario
    ├── index.php
    ├── login.php
    ├── assets/
    ├── includes/
    ├── tools/
    └── .htaccess
```

---

## Paso 1 — Preparar los archivos en tu ordenador

Haz todo esto antes de conectar FileZilla. No necesitas abrir ninguna terminal.

### 1.1 — Generar el JWT_SECRET

Necesitas una clave secreta larga y aleatoria. Usa este generador online:

👉 **https://generate-secret.vercel.app/64**

Copia el resultado (64 caracteres, algo como `a3f9b2c1d8e7f4...`). Lo necesitarás en el siguiente paso.

### 1.2 — Crear el archivo `.env`

En tu ordenador, abre el explorador de archivos y navega hasta la carpeta del proyecto.
Dentro de `backend-php/` verás el archivo `.env.example`. Ábrelo con cualquier editor de texto (Bloc de notas, TextEdit, VS Code).

Crea un archivo nuevo que se llame exactamente `.env` (sin extensión, solo el punto y "env") en esa misma carpeta `backend-php/` con este contenido:

```
JWT_SECRET=pega_aqui_la_clave_que_generaste_en_el_paso_anterior
APP_ORIGIN=https://tudominio.com
APP_ENV=production
```

Sustituye `tudominio.com` por tu dominio real. El valor de `APP_ORIGIN` debe incluir `https://` y no llevar barra al final.

> 💡 **En Windows:** el explorador a veces oculta las extensiones. Para crear un archivo `.env`,
> abre el Bloc de notas, escribe el contenido, y al guardar escoge "Todos los archivos" en tipo
> y escribe `.env` como nombre.

> 💡 **En Mac:** puedes crear el archivo desde VS Code sin problemas. Si usas el Finder,
> los archivos que empiezan por punto quedan ocultos — es normal, el archivo existe aunque no lo veas.

### 1.3 — Adaptar `install.php` para que funcione desde `/public/`

El archivo `backend-php/install.php` necesita una pequeña modificación para poder ejecutarse
desde la carpeta `/public/` del servidor. Ábrelo con tu editor de texto y busca esta línea al principio:

```php
require_once __DIR__ . '/config/Database.php';
```

Cámbiala por:

```php
require_once __DIR__ . '/../data/backend-php/config/Database.php';
```

Guarda el archivo. Haz lo mismo con `backend-php/seed_templates.php`, buscando la misma línea y aplicando el mismo cambio.

Ahora **copia** (no muevas) ambos archivos a la carpeta `public/`:

- `backend-php/install.php` → copia a `public/install.php`
- `backend-php/seed_templates.php` → copia a `public/seed_templates.php`

> ⚠️ Son copias temporales. Los borraremos del servidor en el Paso 6 justo después de usarlos.

### 1.4 — Verificar el `.htaccess`

Abre `public/.htaccess` con tu editor de texto y confirma que tiene exactamente este contenido
(si ya lo tiene, no cambies nada):

```apache
Options -Indexes

RewriteEngine On

RewriteRule ^api/auth/(.*)$       /data/backend-php/api/auth.php      [QSA,L]
RewriteRule ^api/workouts(.*)$    /data/backend-php/api/workouts.php   [QSA,L]
RewriteRule ^api/user(.*)$        /data/backend-php/api/users.php      [QSA,L]
RewriteRule ^api/stats(.*)$       /data/backend-php/api/stats.php      [QSA,L]
RewriteRule ^api/templates(.*)$   /data/backend-php/api/templates.php  [QSA,L]
RewriteRule ^api/exercises(.*)$   /data/backend-php/api/exercises.php  [QSA,L]

<FilesMatch "\.(env|sqlite|log|git)$">
    Require all denied
</FilesMatch>
```

Esto le dice al servidor cómo redirigir las llamadas de la app al backend.

---

## Paso 2 — Conectar FileZilla al servidor

1. Abre FileZilla
2. Ve a **Archivo → Gestor de sitios → Nuevo sitio**
3. Rellena los datos:
   - **Protocolo:** FTP — Protocolo de transferencia de archivos
   - **Host:** el que aparece en DonDominio (algo como `ftp.tudominio.com`)
   - **Puerto:** 21
   - **Modo de acceso:** Normal
   - **Usuario y Contraseña:** los del panel de DonDominio → Hosting → Acceso FTP
4. Haz clic en **Conectar**

Una vez conectado verás dos paneles: el izquierdo es tu ordenador, el derecho es el servidor.
En el panel derecho navega hasta ver las carpetas `/data/` y `/public/`.

---

## Paso 3 — Subir el backend a `/data/`

1. En el panel derecho de FileZilla, entra en la carpeta `/data/`
2. En el panel izquierdo, navega hasta tu carpeta del proyecto
3. Arrastra la carpeta `backend-php/` al panel derecho

**Archivos que NO debes subir** (clic derecho → Excluir si aparecen):

| Archivo | Por qué |
|---|---|
| `backend-php/db/database.sqlite` | Se crea sola al ejecutar `install.php` en el Paso 6 |
| `backend-php/.env.example` | Solo sube el `.env` real que creaste en el Paso 1 |
| `.git/` | Nunca subir el historial de Git al servidor |
| `debug_log.txt` | No debe existir en producción |

---

## Paso 4 — Subir el frontend a `/public/`

1. En el panel derecho de FileZilla, entra en la carpeta `/public/`
2. Sube el contenido de tu carpeta local `public/`:

| Qué subir | Descripción |
|---|---|
| `index.php`, `login.php`, `profile.php`, `history.php`, `log.php` | Páginas principales |
| `assets/` | CSS, JS y imágenes (carpeta completa) |
| `includes/` | header.php, navbar.php, footer.php |
| `tools/` | timer.php, 1rm.php, explore.php |
| `.htaccess` | Reglas del servidor |
| `install.php` | La copia que preparaste en el Paso 1.3 |
| `seed_templates.php` | La copia que preparaste en el Paso 1.3 |

> ⚠️ **No subas** `debug_api.php` ni `debug_modes.php` — son archivos de desarrollo que no hacen falta en producción.

---

## Paso 5 — Crear la carpeta de la base de datos

La carpeta `/data/backend-php/db/` debe existir en el servidor antes de ejecutar `install.php`.

Si al subir el backend en el Paso 3 la carpeta `db/` ya apareció en el servidor (aunque vacía), ya está. Si no:

1. En FileZilla, navega en el panel derecho hasta `/data/backend-php/`
2. Clic derecho en el panel derecho → **Crear directorio**
3. Escribe `db` y confirma

---

## Paso 6 — Inicializar la base de datos desde el navegador

Abre el navegador y ejecuta los dos scripts en este orden:

**Primero:** `https://tudominio.com/install.php`

Verás un listado de tablas que se han creado (`users`, `workouts`, `exercise_sets`...). Si ves eso, todo ha ido bien.

**Segundo:** `https://tudominio.com/seed_templates.php`

Verás un listado de rutinas que se han insertado. Estas son las plantillas predefinidas de la app.

### 🚨 Borra estos archivos del servidor inmediatamente

Son peligrosos si se quedan accesibles — si alguien los ejecuta de nuevo, borrará todos los datos.

En FileZilla, navega a `/public/` y borra los dos archivos:

1. Clic derecho sobre `install.php` → **Eliminar**
2. Clic derecho sobre `seed_templates.php` → **Eliminar**

---

## Paso 7 — Verificar que todo funciona

Comprueba esto en orden desde el navegador:

- [ ] `https://tudominio.com/login.php` → aparece la pantalla de FitLoop
- [ ] Crea una cuenta nueva → redirige al dashboard
- [ ] Cierra sesión y vuelve a entrar → login funciona
- [ ] Ve a `/profile.php` y guarda tu peso → mensaje de éxito
- [ ] Ve a `/log.php` y registra un entrenamiento → aparece el XP ganado
- [ ] Ve a `/history.php` → aparece el entrenamiento registrado
- [ ] Cierra el navegador, vuelve a abrirlo → sigues logueado (la sesión persiste)

**Comprobación rápida de la API:** entra en el navegador a:

```
https://tudominio.com/api/auth/login
```

Debe aparecer en pantalla: `{"detail":"Method not allowed"}` — eso confirma que PHP está funcionando y el `.htaccess` está redirigiendo correctamente.

---

## Solución de problemas frecuentes

| Problema | Qué hacer |
|---|---|
| Pantalla en blanco o error 500 | La carpeta `/data/backend-php/db/` no existe o no tiene permisos. Créala desde FileZilla (Paso 5) y vuelve a ejecutar `install.php`. |
| `"JWT_SECRET not configured"` | El archivo `.env` no se subió correctamente. Comprueba que está en `/data/backend-php/.env` y que `JWT_SECRET` no está vacío. |
| No carga el CSS | La carpeta `/public/assets/` no se subió completa. Vuelve a subirla desde FileZilla. |
| Error CORS al hacer login | `APP_ORIGIN` en el `.env` no coincide exactamente con el dominio. Debe ser `https://tudominio.com` sin barra final. |
| `"Database connection failed"` | La carpeta `db/` no existe en el servidor. Créala desde FileZilla y ejecuta `install.php` de nuevo. |
| Las plantillas no aparecen | No se ejecutó `seed_templates.php`. Vuelve a subirlo a `/public/` y accede a él desde el navegador. Bórralo después. |
| La sesión se pierde al cerrar el navegador | El `.env` no tiene `APP_ENV=production`. Con ese valor la cookie de sesión se marca como segura y persiste. |
| Error 404 en rutas `/api/` | El `.htaccess` no está en `/public/` o tiene algún error. Verifica que se subió correctamente. |
| `install.php` da error de ruta | Comprueba que modificaste la ruta de `require_once` en el Paso 1.3 antes de copiarlo a `/public/`. |

---

## Cómo subir actualizaciones

Cuando hayas hecho cambios en tu ordenador y quieras actualizarlos en el servidor:

1. Conecta FileZilla igual que antes
2. Sube únicamente los archivos que hayas modificado — no hace falta resubir todo
3. Comprueba desde el navegador que los cambios se ven correctamente

> ⚠️ **Nunca vuelvas a ejecutar `install.php` en producción.** Borraría toda la base de datos con los entrenamientos de los dos.

---

## Backup de la base de datos

La base de datos es el único archivo que no se puede recuperar si se pierde. Guárdala periódicamente:

1. Abre FileZilla y navega a `/data/backend-php/db/`
2. Arrastra `database.sqlite` al panel izquierdo (a tu ordenador)
3. Renómbrala con la fecha: `database_2026-03-23.sqlite`
4. Repite cada semana o después de sesiones importantes de entrenamiento
