# Guía de desarrollo local — FitLoop

## Requisitos previos

| Herramienta | Versión mínima |
|-------------|----------------|
| PHP         | 8.2+           |
| Composer    | 2.x            |
| Node.js     | 18+            |
| npm         | 9+             |

---

## Primera vez (setup inicial)

```bash
# 1. Clonar o descomprimir el proyecto y entrar al directorio
cd FitLoop

# 2. Ejecutar el script de setup automático
#    Hace: composer install, copia .env.example → .env,
#    genera APP_KEY, ejecuta migraciones, npm install y npm run build
composer run setup

# 3. Cargar ejercicios y entrenamientos base en la BD
php artisan db:seed --class=ExercisesTableSeeder
php artisan db:seed --class=TemplatesTableSeeder
```

> El archivo `.env` ya queda creado con `DB_CONNECTION=sqlite`. No es necesario configurar MySQL para desarrollo local.

---

## Arrancar el entorno de desarrollo

Un solo comando levanta los 3 procesos concurrentemente:

```bash
composer run dev
```

Esto ejecuta en paralelo:

| Proceso | Descripción |
|---------|-------------|
| `php artisan serve` | Servidor PHP en `http://127.0.0.1:8000` |
| `npm run dev` (Vite) | HMR para assets JS/CSS |
| `php artisan queue:listen` | Procesador de colas |

> `php artisan pail` (logs en tiempo real) **no está disponible en Windows** por requerir la extensión `pcntl`. Usa `Get-Content storage/logs/laravel.log -Wait` como alternativa.

Abre el navegador en **http://127.0.0.1:8000**.

---

## Variables de entorno clave (`.env`)

```dotenv
APP_NAME=FitLoop
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=sqlite
# DB_DATABASE=database/database.sqlite   ← ruta por defecto, no hace falta cambiarla

SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

> `APP_URL` debe coincidir exactamente con la URL que usas en el navegador para que Sanctum CSRF funcione correctamente.

---

## Base de datos

```bash
# Crear / actualizar la BD con las migraciones
php artisan migrate

# Reset completo (⚠️ borra todos los datos) + seed
php artisan migrate:fresh --seed

# Solo volver a cargar los seeders sin resetear
php artisan db:seed --class=ExercisesTableSeeder
php artisan db:seed --class=TemplatesTableSeeder
```

La BD SQLite se guarda en `database/database.sqlite` (excluido de git).

---

## Tests

```bash
# Ejecutar todos los tests (usa SQLite en memoria, no toca la BD de dev)
composer test

# Ejecutar un archivo de test concreto
php artisan test tests/Feature/ExampleTest.php

# Ejecutar un test específico por nombre
php artisan test --filter NombreDelTest
```

---

## Comandos útiles

```bash
# Formatear código PHP (Laravel Pint)
php artisan pint

# Limpiar caché de rutas, config y vistas
php artisan optimize:clear

# Abrir tinker (REPL de Laravel)
php artisan tinker

# Ver todas las rutas registradas
php artisan route:list

# Compilar assets para producción
npm run build
```

---

## Estructura de procesos

```
Browser
  │
  ├── GET /dashboard  →  web.php  →  Blade view (HTML + JS inline)
  │
  └── fetch('/api/stats')  →  api.php  →  Api\DashboardController  →  Eloquent  →  SQLite
```

- Las rutas **web** (`routes/web.php`) sirven las vistas Blade.
- Las rutas **API** (`routes/api.php`) devuelven JSON, protegidas con `auth:sanctum`.
- El token Bearer se guarda en `localStorage` y se envía en cada `fetch()`.

---

## Notas de desarrollo

- El CSS no usa compilación: los archivos en `public/assets/css/` se sirven directamente. `styles.css` importa los otros cuatro módulos vía `@import`.
- Vite solo gestiona `resources/css/app.css` y `resources/js/app.js` (actualmente casi vacíos).
- El Service Worker (`public/sw.js`) se registra automáticamente; en desarrollo puede cachear respuestas. Para forzar recarga limpia: DevTools → Application → Service Workers → "Unregister".
- Los logs de Laravel se escriben en `storage/logs/laravel.log`. Para verlos en tiempo real en Windows: `Get-Content storage/logs/laravel.log -Wait`.
