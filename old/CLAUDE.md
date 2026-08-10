# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Idioma
Responde siempre en español.

## Project Overview

FitLoop is a mobile-first fitness tracking web app built with **Laravel 12** (PHP 8.2+) and **Blade templates**. It uses a hybrid architecture: server-rendered Blade views that communicate with a JSON REST API (same Laravel app) via vanilla JavaScript `fetch()` calls. Authentication uses Laravel Sanctum with session-based tokens.

## Common Commands

```bash
# Full dev environment (PHP server + queue + logs + Vite HMR — all concurrently)
composer run dev

# Run tests
composer test

# Format PHP code
php artisan pint

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# First-time setup
composer run setup

# Cargar templates (entrenamientos predefinidos)
php artisan db:seed --class=TemplatesTableSeeder

# Reset completo de BD + seed (⚠️ borra todos los datos)
php artisan migrate:fresh --seed
```

## Architecture

### Request Flow
```
Browser → Blade View (HTML/CSS/JS) → fetch() → /api/* routes → Api\*Controller → Eloquent Models → SQLite/MySQL
```

Web routes (`routes/web.php`) serve Blade views; API routes (`routes/api.php`) return JSON and are protected by Sanctum middleware. Frontend JS lives inline in Blade views or in `public/assets/js/`.

### Key Layers

**Controllers** (`app/Http/Controllers/Api/`): All API logic is here. Each controller maps to a resource:
- `AuthController` — register/login
- `WorkoutController` — CRUD for completed workouts with nested `ExerciseSets`
- `TemplateController` — saved workout routines
- `ProfileController` — user profile and weight history
- `ExerciseController` — exercise catalog and history
- `DashboardController` — aggregated stats for `/api/stats`

**Informe de salud** (`app/Http/Controllers/ReportController.php`, web, NO API): informe unificado para médico/nutricionista. `/informe-salud` (pantalla) + `/informe-salud/pdf` (descarga). Rutas **web** (cookie `fitloop_token`, no Bearer) para que el enlace de descarga funcione con navegación normal del navegador. Agrega peso+IMC, medias de nutrición (macros + fibra + azúcar + agua + adherencia al objetivo) y resumen de entrenos por modo en un rango de fechas libre. PDF generado con `barryvdh/laravel-dompdf` (PHP puro, sin binarios → va en `vendor/`, se sube por FTP). Vistas: `resources/views/reports/health.blade.php` (pantalla) y `health-pdf.blade.php` (PDF, maquetado con tablas).

**Models** (`app/Models/`): Eloquent models with relationships:
```
User →hasMany→ Workout →hasMany→ ExerciseSet
User →hasMany→ Template →hasMany→ TemplateExercise
User →hasMany→ WeightLog
```

**Views** (`resources/views/`): Blade templates, one per page. The main shell is `layouts/app.blade.php` (includes CSS/JS, toast system). Navigation is `layouts/navbar.blade.php` (bottom nav bar for mobile, 5 items: Inicio, Historial, Entrenar FAB, Herramientas, Perfil).

**CSS** (`public/assets/css/`): Modular vanilla CSS. El punto de entrada es `styles.css` (importa los otros 4 via `@import`). No cargar los archivos individuales por separado en el layout.

### Database

- **Development**: SQLite (`database/database.sqlite`)
- **Production**: MySQL (configured via `.env`)
- Tests use an in-memory SQLite database (configured in `phpunit.xml`)

## Environment

Copy `.env.example` to `.env`. Key variables:
- `DB_CONNECTION=sqlite` for local dev
- `APP_URL` must match for Sanctum CSRF to work

## Despliegue a producción (FTP / Ginernet)

**Servidor:** Ginernet, **solo FTP** (sin SSH, sin Composer/Node en el server). Por tanto todo se prepara en local y se sube ya listo.

**Layout:** carpeta única. El dominio `fitloop.israelzamora.es` apunta a `public_html/fitloop.israelzamora.es/` y ahí va el proyecto Laravel **completo** (nada en carpetas `data/` fuera). Un `.htaccess` en la raíz reescribe a `/public` y bloquea `.env`/`vendor`/`config`/etc.

> **Document Root → `/public` (recomendado).** Lo más limpio y seguro es poner el Document Root del subdominio en `public_html/fitloop.israelzamora.es/public` (sigue todo dentro de la carpeta). Así solo corre `public/.htaccess`.
>
> ⚠️ **Gotcha del login (Sanctum + Bearer):** el auth manda `Authorization: Bearer` y Apache/LiteSpeed lo descarta salvo que el `.htaccess` lo reinyecte. Si el Document Root es la carpeta raíz (no `/public`), hay doble rewrite (root→public→index.php) y Symfony pierde el header (solo lee `REDIRECT_HTTP_AUTHORIZATION`, un nivel) → el login parece fallar (entra y rebota a /login con 401 en `/api/*`). Por eso el `.htaccess` raíz reescribe **en un solo salto** a `public/index.php` y reinyecta `Authorization`. Con Document Root en `/public` el problema no existe.

**Flujo (cada subida a producción):**
```bash
bash build-deploy.sh   # regenera ./deploy con TODO lo que va al FTP
```
Luego arrastrar **todo el contenido de `./deploy/`** a `public_html/fitloop.israelzamora.es/` por FileZilla. Tras subir (primera vez): `chmod -R 775 storage bootstrap/cache database` desde el gestor de archivos del panel.

- `./deploy/` se regenera entero cada vez (`rm -rf` + rsync). Está en `.gitignore`, no se commitea.
- `.env_produccion` es la plantilla de prod (URL Ginernet + SQLite por defecto + `SESSION_SECURE_COOKIE=true`); el script lo copia como `deploy/.env`.
- La BD `database/database.sqlite` se sube tal cual (lleva los datos). **Cero migraciones** en el traslado.
- **No** se usa Vite en prod: el layout carga `public/assets/css|js` con `asset()`. No hace falta `npm run build`.
- `vendor/` se sube tal cual (incluye dev deps; inofensivo, no hay composer en el server).
- Excluidos del deploy: `node_modules .git tests docs backup_legacy alimentos *.md package*.json vite.config.js phpunit.xml public/migrate.php public/seed.php`.

## Design System

### CSS Files (`public/assets/css/`)
| Archivo | Contenido |
|---------|-----------|
| `styles.css` | Entry point — importa los otros 4 módulos |
| `variables.css` | Tokens de diseño: colores, sombras, radios, transiciones |
| `base.css` | Reset, tipografía, animaciones (@keyframes), utilidades visuales |
| `layout.css` | Flexbox, grid, spacing, position, z-index |
| `components.css` | Botones, cards, badges, navbar, progress bar, skeleton, empty-state, forms |

### Tokens principales
- **Brand color**: `--color-primary: #f59e0b` (amber)
- **Backgrounds**: `--bg-background: #0a0a0b` / `--bg-card: #18181b` / `--bg-muted: #27272a`
- **Sombra brand**: `--shadow-primary` / `--shadow-primary-lg` (glow amarillo)
- **Transiciones**: `--transition-fast` / `--transition-base` / `--transition-slow`

### Componentes disponibles
- **Botones**: `.btn`, `.btn-primary`, `.btn-outline`, `.btn-ghost`, `.btn-danger`, `.btn-sm`, `.btn-lg`, `.btn-icon`, `.btn-block`
- **Cards**: `.card`, `.card-hover`, `.card-interactive` (con hover + active states)
- **Badges**: `.badge` + `.badge-{primary|success|warning|danger|blue|purple|teal}`
- **Progress**: `.progress-container` + `.progress-bar`
- **Skeleton**: `.skeleton`, `.skeleton-text`, `.skeleton-title`, `.skeleton-card`
- **Empty state**: `.empty-state`, `.empty-state-icon`, `.empty-state-title`, `.empty-state-desc`
- **Stats**: `.stat-card`, `.stat-value`, `.stat-label`
- **Sección**: `.section-header`, `.section-title`, `.section-link`
- **Tabs**: `.tabs-container` + `.tab-btn` (cuadradas) / `.pill-tab` (redondas, para filtros)
- **Iconos**: `.icon-wrap`, `.icon-wrap-{sm|md|lg|xl}`
- **Glass**: `.glass-panel`
- **Danger**: `.danger-zone`
- **Animaciones**: `.animate-spin`, `.animate-pulse`, `.animate-fade-in`, `.animate-slide-up`, `.animate-scale-in`

### Patrones UI
- Headers con `position:sticky; backdrop-filter:blur(16px)` para scroll
- Modales tipo **bottom-sheet** (abre desde abajo en móvil)
- Loading states con **skeleton shimmer** en vez de spinners
- **Empty states** siempre con icono + título + descripción + CTA opcional
- Cards interactivas usan `.card-interactive` (hover + `translateY(-1px)` + active scale)
- Iconos contextuales por modo: gym=dumbbell, home=home, calisthenics=user, swimming=waves

## Templates / Entrenamientos

Los templates se cargan desde `database/seeders/TemplatesTableSeeder.php`. Categorías y cantidades actuales:

| Modo | Cantidad | Niveles |
|------|----------|---------|
| `gym` | 5 | Básico, Intermedio (×2), Avanzado (×2) |
| `calisthenics` | 5 | Básico, Intermedio (×2), Avanzado (×2) |
| `home` | 15 | Básico (×3), Intermedio (×5), Avanzado (×7) |
| `swimming` | 5 | Básico, Intermedio (×2), Avanzado (×2) |

Los de casa (`home`) incluyen:
- **Peso corporal puro** (sin equipamiento): niveles Básico, Intermedio, Avanzado, Core, HIIT
- **Mancuernas**: Primeras mancuernas (Básico), Torso completo, Lower Body, Push Avanzado, Full Body Express
- **Originales**: Full Body Mancuernas, Torso Gomas, Pierna Casa, Metabólico Gomas, Estáticas Paralelas

Los niveles en español son: `"Básico"`, `"Intermedio"`, `"Avanzado"` (así están en BD y así se filtran en la UI).
