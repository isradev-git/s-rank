# S-RANK · Fase 1.0 — Plan de implementación

> **Para quien ejecute esto:** usa la skill `superpowers:subagent-driven-development`
> (recomendada) o `superpowers:executing-plans` para ir tarea a tarea. Los pasos usan
> casillas `- [ ]` para poder marcarlos.

**Objetivo:** dejar el backend listo para que la app Android tenga contra qué hablar:
Laravel API-only en `backend/`, sobre MySQL, con los datos reales importados, los cuatro
arreglos bloqueantes resueltos, las tablas y endpoints del Sistema funcionando y el alta
y recuperación de cuenta abiertos.

**Arquitectura:** se conserva el Laravel de FitLoop y se le poda todo lo que era web. El
Sistema (XP, niveles, misiones, logros, estadísticas) vive aislado en `app/System/` y no
sabe nada de sentadillas ni calorías: los controladores existentes disparan un evento de
Laravel al guardar y un único listener traduce ese evento en progreso. Los controladores
no cambian su contrato; solo añaden un bloque `system` a la respuesta.

**Stack:** PHP 8.2+ · Laravel 12 · Sanctum 4 · MySQL 8 / MariaDB 10.11 · PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-08-10-s-rank-design.md` (§3, §5, §6, §10, §11, §13).

---

## Restricciones globales

Aplican a todas las tareas. No hace falta repetirlas en cada una.

- **Despliegue solo por FTP.** Nada que necesite SSH, symlinks, cron, Composer o Node en
  el servidor. Todo se prepara en local y se sube ya construido.
- **El XP se calcula siempre en el servidor.** La app nunca decide cuánto XP vale nada.
- **Zona horaria `Europe/Madrid`**, fijada en el servidor. No hay zona horaria por usuario.
- **Textos de cara al usuario en español llano.** Nada de jerga de terminal ni de shell.
- **Ningún secreto en git.** `.env*` y `*.sqlite` están en `.gitignore` y así se quedan.
- **`old/` no se toca.** Es referencia de solo lectura hasta que cierre la fase 1.
- **Constantes de balanceo en `config/srank.php`**, nunca incrustadas en el código.
- **Tests con PHPUnit**, no con Pest (ver «Desviaciones» al final).
- Cada tarea termina con la suite en verde y un commit.

---

## Estructura de ficheros

`backend/` nace como copia podada de `old/`. Lo que se añade encima:

| Fichero | Responsabilidad |
|---|---|
| `config/srank.php` | Todas las constantes de balanceo: XP, topes, `K` de estadísticas, misiones |
| `app/System/Progression.php` | Curva de nivel y rango. Funciones puras, sin base de datos |
| `app/System/Stats.php` | `⌊√(acumulador / K)⌋`. Funciones puras |
| `app/System/XpLedger.php` | Concede XP escribiendo en `xp_events` y aplica los topes diarios |
| `app/System/QuestService.php` | Genera las misiones del día y sincroniza su progreso |
| `app/System/AchievementService.php` | Catálogo de los 40 logros y evaluador |
| `app/System/SystemService.php` | Orquesta: recibe lo que ha pasado, devuelve el bloque `system` |
| `app/Events/*.php` | Cinco eventos: entreno, comida, agua, suplemento, peso |
| `app/Listeners/UpdateSystemProgress.php` | Único puente entre los módulos y el Sistema |
| `app/Http/Controllers/Api/SystemController.php` | `GET /api/system/*` |
| `app/Console/Commands/ImportSqlite.php` | Migración de datos SQLite → MySQL |
| `app/Console/Commands/RecalculateProgress.php` | Reconstrucción del progreso histórico |
| `app/Models/{UserProgress,DailyQuest,UserAchievement,XpEvent}.php` | Las cuatro tablas nuevas |

Cada fichero de `app/System/` se puede leer y probar solo. `SystemService` es el único que
los conoce a todos; los controladores no conocen ninguno.

---

## Tarea 1: `backend/` como Laravel API-only

**Ficheros:**
- Crear: `backend/` (copia podada de `old/`)
- Modificar: `backend/routes/web.php`, `backend/resources/views/layouts/app.blade.php`,
  `backend/composer.json`
- Borrar dentro de `backend/`: 19 vistas Blade, `public/sw.js`, `vite.config.js`,
  `package*.json`, `DEPLOY_*.md`, `mejoras_entrenamiento.md`

**Interfaces:**
- Produce: un proyecto Laravel en `backend/` cuya suite existente pasa entera.

- [ ] **Paso 1: Copiar el proyecto sin la basura regenerable**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/fit_app_android_beta
rsync -a \
  --exclude='vendor/' --exclude='node_modules/' --exclude='deploy/' \
  --exclude='.env' --exclude='.env.produccion' --exclude='database/*.sqlite*' \
  --exclude='docs/' --exclude='alimentos/' --exclude='.claude/' \
  old/ backend/
```

`alimentos/` se queda solo en `old/`: `AlimentosSeeder` lleva los 1.318 alimentos escritos
dentro, no lee los JSON en tiempo de ejecución.

- [ ] **Paso 2: Borrar lo que era web**

```bash
cd backend
rm -rf resources/views/auth resources/views/nutrition resources/views/tools \
       resources/views/layouts/navbar.blade.php \
       resources/views/{achievements,dashboard,history,log,profile,training,welcome}.blade.php
rm -f public/sw.js vite.config.js package.json package-lock.json \
      DEPLOY_CHECKLIST.md DEPLOY_DONDOMINIO.md mejoras_entrenamiento.md README.md CLAUDE.md
```

Se conservan `resources/views/reports/` (informe de salud y su PDF),
`resources/views/layouts/app.blade.php` y `public/assets/` porque el informe los usa: es
la única página web que sobrevive, para poder pasarle un enlace al médico.

- [ ] **Paso 3: Quitar del layout la barra de navegación que ya no existe**

En `backend/resources/views/layouts/app.blade.php`, borrar esta línea:

```blade
    @include('layouts.navbar')
```

y cambiar el título por el nuevo nombre:

```blade
    <title>S-RANK</title>
```

- [ ] **Paso 4: Dejar `routes/web.php` con lo único que sigue siendo web**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureAuthenticated;

// La app es Android. Lo único que sigue siendo una página web es el informe de salud,
// para poder pasarle un enlace al médico o al nutricionista.
Route::middleware(EnsureAuthenticated::class)->group(function () {
    Route::get('/informe-salud', [\App\Http\Controllers\ReportController::class, 'show']);
    Route::get('/informe-salud/pdf', [\App\Http\Controllers\ReportController::class, 'pdf']);
});
```

- [ ] **Paso 5: Renombrar el proyecto en `composer.json` y quitar los scripts de Node**

En `backend/composer.json`, cambiar `"name": "laravel/laravel"` por `"name": "isradev/s-rank-api"`,
y dejar los scripts `setup` y `dev` sin npm:

```json
        "setup": [
            "composer install",
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
            "@php artisan key:generate",
            "@php artisan migrate --force"
        ],
        "dev": [
            "@php artisan serve"
        ],
```

- [ ] **Paso 6: Instalar dependencias y crear el `.env` local**

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

`.gitignore` ya usa patrones sin ancla (`vendor/`, `deploy/`, `node_modules/`), así que
cubre tanto `backend/` como `old/` sin tocar nada: el punto que pedía el spec §3 está hecho.

- [ ] **Paso 7: Ejecutar la suite existente — tiene que pasar entera**

Ejecutar: `cd backend && php artisan test`

FitLoop llega con cuatro tests caducados que fallaban ya antes de tocar nada: prueban un
contrato que el código cambió y nadie actualizó. Hay que arreglarlos aquí, porque esta
suite es la puerta de todas las tareas siguientes.

- `WorkoutTest` ×3 — mandan `'sets' => 3` (un número). Desde el commit `aa2d709` cada
  serie es una fila, así que `sets` es un array de series. Cambiar los tres payloads a
  `'sets' => [['reps' => 10, 'weight_kg' => 80], ...]`.
- `MealLogTest > destroy comida de otro usuario retorna 404` — el borrado se hizo
  idempotente a propósito y responde 200. Lo que hay que comprobar no es el código de
  estado sino que la fila del otro usuario sigue ahí: cambiar a `assertOk()` más un
  `assertDatabaseHas`.
- `ExampleTest` — comprueba que `GET /` redirige a `/login`. En una API no hay raíz ni
  login web: se borra el fichero.

Esperado tras arreglarlos: **188 tests en verde**. Cualquier otro fallo sí sería culpa de
la poda y hay que resolverlo antes de seguir.

- [ ] **Paso 8: Commit**

```bash
git add backend/
git commit -m "feat(backend): Laravel API-only a partir de FitLoop"
```

---

## Tarea 2: MySQL y zona horaria

Arreglo bloqueante 1 del spec (§5.1). SQLite bloquea el fichero entero en cada escritura y
vive dentro del árbol que se sube por FTP; con varios móviles escribiendo a la vez es
pérdida de datos garantizada.

**Ficheros:**
- Modificar: `backend/.env`, `backend/.env.example`, `backend/config/app.php`
- Crear: `backend/config/srank.php`
- Test: `backend/tests/Unit/TimezoneTest.php`

**Interfaces:**
- Produce: `config('srank.timezone')` → `'Europe/Madrid'`; base `srank` local en MySQL con
  el esquema migrado.

- [ ] **Paso 1: Crear la base de datos y el usuario en local**

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS srank CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'srank'@'localhost' IDENTIFIED BY 'srank_local';
GRANT ALL PRIVILEGES ON srank.* TO 'srank'@'localhost';
FLUSH PRIVILEGES;"
```

- [ ] **Paso 2: Apuntar el `.env` local a MySQL**

En `backend/.env`, sustituir la línea `DB_CONNECTION=sqlite` por:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=srank
DB_USERNAME=srank
DB_PASSWORD=srank_local
APP_TIMEZONE=Europe/Madrid
APP_NAME=S-RANK
```

Hacer el mismo cambio en `backend/.env.example`, pero dejando `DB_PASSWORD=` vacío.

`APP_LOCALE` se queda en `en`. Laravel 12 solo trae los textos de validación en inglés;
ponerlo en español sin publicar las traducciones haría que la API devolviera claves crudas
del tipo `validation.required`. Los mensajes que ve el usuario los escribe la app, y los
pocos que salen del servidor ya están en español a mano. Las traducciones de validación
se añaden en la fase 1.1, cuando haya formularios que las enseñen.

- [ ] **Paso 3: Fijar la zona horaria de la aplicación**

En `backend/config/app.php`, línea 68, cambiar:

```php
    'timezone' => env('APP_TIMEZONE', 'Europe/Madrid'),
```

Ojo con esto: las marcas de tiempo ya guardadas se reinterpretan (estaban en UTC). Con 18
entrenos registrados a media tarde el efecto es nulo; solo cambiaría el día en registros
hechos entre las 22:00 y las 00:00 UTC.

**Regla que hay que llevarse a la app:** la API sigue serializando las fechas en UTC, que
es lo estándar y lo que Laravel hace por defecto. Con la aplicación en Madrid, un entreno
del 15 de marzo a medianoche sale como `2026-03-14T23:00:00.000000Z`. **El día natural se
calcula siempre en Madrid**, en el servidor y en la app: quedarse con los diez primeros
caracteres de la cadena UTC da el día anterior. En Android hay que parsear el instante y
convertirlo a `ZoneId.of("Europe/Madrid")` antes de mostrar o agrupar por día.

(Se probó a serializar con desfase local mediante `Carbon::serializeUsing()`; en Carbon 3
ya no afecta a `toJSON()`, así que no se toca nada y se documenta la regla.)

- [ ] **Paso 4: Escribir `config/srank.php` con todas las constantes de balanceo**

Crear `backend/config/srank.php`:

```php
<?php

/**
 * Constantes del Sistema. Viven aquí y no en el código para poder reajustar el
 * balanceo sin publicar una versión nueva de la app en Play Store.
 */
return [
    'timezone' => 'Europe/Madrid',

    'xp' => [
        'workout_base'          => 50,   // entreno de 15 minutos o más
        'workout_min_minutes'   => 15,
        'workout_bonus_step'    => 5,    // +1 XP por cada 5 minutos por encima del mínimo
        'workout_bonus_cap'     => 30,
        'record'                => 30,
        'all_quests_bonus'      => 40,
        'streak_per_day'        => 2,
        'streak_cap'            => 30,
        'daily_cap'             => 300,  // tope total diario, todas las fuentes
        'workouts_per_day_cap'  => 2,    // el tercer entreno del día ya no puntúa
    ],

    // valor de la estadística = floor(sqrt(acumulador / k))
    'stats' => [
        'strength'    => 1500,  // kilogramos
        'endurance'   => 25,    // minutos
        'consistency' => 1.5,   // días activos + misiones cumplidas
        'vitality'    => 2.5,   // objetivos de hábito cumplidos
    ],

    'quests' => [
        'train'       => 20,
        'water'       => 20,
        'protein'     => 30,
        'weight'      => 10,
        'meals_3'     => 20,
        'supplements' => 15,
        'optional'    => 15,
    ],

    // Módulos activos que la app muestra en la ficha de perfil. La fase 2 añade aquí.
    'modules' => ['entrenamiento', 'nutrición'],
];
```

- [ ] **Paso 5: Escribir el test de zona horaria**

Crear `backend/tests/Unit/TimezoneTest.php`:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;

class TimezoneTest extends TestCase
{
    public function test_el_sistema_calcula_las_fechas_en_madrid()
    {
        $this->assertSame('Europe/Madrid', config('srank.timezone'));
        $this->assertSame('Europe/Madrid', config('app.timezone'));
    }
}
```

- [ ] **Paso 6: Ejecutar el test**

Ejecutar: `cd backend && php artisan test --filter=TimezoneTest`
Esperado: PASS.

- [ ] **Paso 7: Arreglar los dos `user_id` que MySQL no va a tragar**

Dos migraciones declaran `user_id` como entero apuntando a `users.id`, que es un UUID:

```
database/migrations/2026_03_30_100001_create_food_items_table.php:29
database/migrations/2026_03_30_100004_create_recipes_table.php:40
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
```

SQLite lo traga porque casi no comprueba tipos; MySQL rechaza la clave ajena con el
errno 150 y `migrate` se para ahí. En las dos, sustituir esa línea por:

```php
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
```

Se editan las migraciones originales en vez de añadir una nueva porque nunca han llegado
a ejecutarse contra MySQL, y en los datos reales las dos columnas están a nulo en las
1.506 filas de `food_items` y las 26 de `recipes`: no hay nada que convertir.

**Y dos migraciones que se ejecutan antes que la tabla a la que apuntan.** Cinco ficheros
comparten el sello `2026_01_11_150829`, así que Laravel los ordena por nombre y
`create_exercise_sets_table` cae antes que `create_workouts_table`. La clave ajena apunta
a una tabla que todavía no existe: en SQLite da igual, MySQL vuelve a fallar con el
errno 150. Se arregla renombrando los dos hijos para que vayan detrás de sus padres:

```bash
cd backend/database/migrations
git mv 2026_01_11_150829_create_exercise_sets_table.php      2026_01_11_150831_create_exercise_sets_table.php
git mv 2026_01_11_150829_create_template_exercises_table.php 2026_01_11_150831_create_template_exercises_table.php
```

Renombrar es seguro: la tabla `migrations` de producción no viaja —el comando de
importación copia solo las tablas de datos— así que MySQL parte de cero.

- [ ] **Paso 8: Migrar el esquema a MySQL vacío**

```bash
cd backend && php artisan migrate --force
mysql -u srank -psrank_local srank -e "SHOW TABLES;"
```

Esperado: 19 tablas (users, workouts, exercise_sets, templates, template_exercises,
weight_logs, exercises, food_items, nutrition_goals, meal_logs, recipes, water_logs,
supplement_logs, personal_access_tokens, cache, cache_locks, jobs, job_batches,
failed_jobs, sessions, password_reset_tokens).

- [ ] **Paso 9: Commit**

```bash
git add backend/config backend/.env.example backend/database/migrations backend/tests/Unit/TimezoneTest.php
git commit -m "feat(backend): MySQL, zona horaria de Madrid y config del Sistema"
```

---

## Tarea 3: Importar los datos reales de SQLite a MySQL

**Ficheros:**
- Crear: `backend/app/Console/Commands/ImportSqlite.php`
- Modificar: `backend/config/database.php`

**Interfaces:**
- Produce: `php artisan srank:import-sqlite {ruta}` — vuelca el SQLite de FitLoop en la
  base MySQL configurada. Es destructivo sobre el destino y repetible.

- [ ] **Paso 1: Añadir la conexión de origen**

En `backend/config/database.php`, dentro de `'connections'`, añadir:

```php
        // Solo para la importación única desde FitLoop. Se borra al cerrar la fase 1.
        'sqlite_legacy' => [
            'driver'   => 'sqlite',
            'database' => env('LEGACY_SQLITE_PATH', ''),
            'prefix'   => '',
            'foreign_key_constraints' => false,
        ],
```

- [ ] **Paso 2: Escribir el comando**

Crear `backend/app/Console/Commands/ImportSqlite.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImportSqlite extends Command
{
    protected $signature = 'srank:import-sqlite
                            {path : Ruta al database.sqlite de FitLoop}
                            {--force : No preguntar antes de vaciar las tablas de destino}';

    protected $description = 'Copia los datos de FitLoop (SQLite) a la base MySQL de S-RANK';

    /**
     * Orden de inserción: las tablas padre van antes que las que las referencian.
     */
    private const TABLES = [
        'users',
        'exercises',
        'food_items',
        'recipes',
        'templates',
        'template_exercises',
        'workouts',
        'exercise_sets',
        'weight_logs',
        'nutrition_goals',
        'meal_logs',
        'water_logs',
        'supplement_logs',
    ];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("No existe el fichero: {$path}");

            return self::FAILURE;
        }

        Config::set('database.connections.sqlite_legacy.database', realpath($path));
        $source = DB::connection('sqlite_legacy');

        if (! $this->option('force')
            && ! $this->confirm('Esto BORRA el contenido actual de las tablas de destino. ¿Seguir?', false)) {
            return self::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse(self::TABLES) as $table) {
            DB::table($table)->delete();
        }

        $report = [];

        foreach (self::TABLES as $table) {
            $count = 0;

            $source->table($table)->orderBy('id')->chunk(500, function ($rows) use ($table, &$count) {
                $insert = array_map(fn ($row) => (array) $row, $rows->all());
                DB::table($table)->insert($insert);
                $count += count($insert);
            });

            $report[] = [$table, $count];
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->table(['tabla', 'filas'], $report);
        $this->info('Importación terminada.');

        return self::SUCCESS;
    }
}
```

- [ ] **Paso 3: Ejecutarlo contra los datos reales**

```bash
cd backend
php artisan srank:import-sqlite ../old/database/database.sqlite --force
```

- [ ] **Paso 4: Verificar los recuentos contra el origen**

```bash
mysql -u srank -psrank_local srank -e "
  SELECT 'workouts' t, COUNT(*) n FROM workouts
  UNION ALL SELECT 'exercise_sets', COUNT(*) FROM exercise_sets
  UNION ALL SELECT 'meal_logs', COUNT(*) FROM meal_logs
  UNION ALL SELECT 'users', COUNT(*) FROM users;"
```

Esperado, según los datos rescatados de producción: **18 entrenos, 7 series**, y el mismo
número de usuarios y comidas que tiene el SQLite. Si algún número no cuadra, parar aquí:
el resto del plan asume que los datos están completos.

- [ ] **Paso 5: Commit**

```bash
git add backend/app/Console/Commands/ImportSqlite.php backend/config/database.php
git commit -m "feat(backend): comando de importación de FitLoop a MySQL"
```

---

## Tarea 4: `build-deploy.sh` reparado y sin base de datos dentro

Arreglos bloqueantes 1 y 2 (§5.1). El script actual copia `database/database.sqlite` al
paquete de subida —cada despliegue por FTP machaca los datos de producción— y además está
roto desde `60d42c4`, que borró el `.env_produccion` que el script sigue copiando.

**Ficheros:**
- Modificar: `backend/build-deploy.sh`
- Crear: `backend/.env.produccion.example`

**Interfaces:**
- Produce: `bash build-deploy.sh` genera `backend/deploy/` listo para FileZilla, sin
  ninguna base de datos dentro y con un `.env` de producción real.

- [ ] **Paso 1: Reescribir el script**

Sustituir el contenido de `backend/build-deploy.sh` por:

```bash
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
```

- [ ] **Paso 2: Crear la plantilla del `.env` de producción**

Crear `backend/.env.produccion.example` (esta sí va en git; la copia con credenciales
reales, `.env.produccion`, no):

```
APP_NAME=S-RANK
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://rank-s.israelzamora.es
APP_TIMEZONE=Europe/Madrid

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
CACHE_STORE=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=no-reply@israelzamora.es
MAIL_FROM_NAME=S-RANK
```

`QUEUE_CONNECTION=sync` a propósito: en un hosting compartido sin SSH no hay quien ejecute
un worker de colas, así que los correos salen dentro de la propia petición.

El `.gitignore` de la raíz ignora `.env.*`, así que hay que desbloquear la plantilla
—y solo la plantilla— añadiendo debajo de `!.env.example`:

```
!.env.produccion.example
```

Comprobarlo antes de seguir: `git check-ignore -v backend/.env.produccion` tiene que decir
que **sí** está ignorado, y `git check-ignore backend/.env.produccion.example` no debe
devolver nada.

- [ ] **Paso 3: Probar que el script falla bien cuando falta el `.env`**

Ejecutar: `cd backend && bash build-deploy.sh`
Esperado: FALLA con «ERROR: falta .env.produccion» y el texto de ayuda. No debe crear
`deploy/`.

- [ ] **Paso 4: Probar que funciona con el `.env` puesto**

```bash
cd backend
cp .env.produccion.example .env.produccion
php artisan key:generate --show   # pegar el resultado en APP_KEY de .env.produccion
bash build-deploy.sh
find deploy -name '*.sqlite' | wc -l   # tiene que dar 0
test -f deploy/.env && echo "env ok"
```

Esperado: «OK -> ./deploy listo.», cero ficheros `.sqlite` y `deploy/.env` presente.

- [ ] **Paso 5: Commit**

```bash
git add backend/build-deploy.sh backend/.env.produccion.example .gitignore
git commit -m "fix(deploy): el paquete FTP ya no lleva la base de datos dentro"
```

---

## Tarea 5: Imágenes sin symlink

Arreglo bloqueante 3 (§5.1). `Storage::disk('public')` necesita el enlace
`public/storage → storage/app/public`, que sin SSH no se puede crear. Se cambia el disco a
`public/uploads`, que es una carpeta normal.

**Ficheros:**
- Modificar: `backend/config/filesystems.php`,
  `backend/app/Http/Controllers/Api/FoodController.php`,
  `backend/app/Http/Controllers/Api/RecipeController.php`
- Crear: `backend/public/uploads/.gitignore`
- Test: `backend/tests/Feature/UploadDiskTest.php`

**Interfaces:**
- Produce: disco `uploads` con raíz `public_path('uploads')` y URL `{APP_URL}/uploads`.

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/UploadDiskTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_disco_uploads_no_depende_de_ningun_symlink()
    {
        $this->assertSame(public_path('uploads'), config('filesystems.disks.uploads.root'));
    }

    public function test_la_imagen_de_un_alimento_se_guarda_en_el_disco_uploads()
    {
        Storage::fake('uploads');

        $user = User::factory()->create();
        $food = \App\Models\FoodItem::create([
            'name'              => 'Pollo',
            'calories_per_100g' => 165,
            'protein_per_100g'  => 31,
            'carbs_per_100g'    => 0,
            'fat_per_100g'      => 3.6,
            'user_id'           => $user->id,
        ]);

        $this->actingAs($user)
            ->post("/api/foods/{$food->id}/image", ['image' => UploadedFile::fake()->image('pollo.jpg')])
            ->assertOk();

        $this->assertNotNull($food->fresh()->image_path);
        Storage::disk('uploads')->assertExists($food->fresh()->image_path);
    }
}
```

- [ ] **Paso 2: Ejecutar el test y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=UploadDiskTest`
Esperado: FALLA — el disco `uploads` todavía no existe.

- [ ] **Paso 3: Añadir el disco**

En `backend/config/filesystems.php`, dentro de `'disks'`, añadir después de `'public'`:

```php
        // Carpeta normal dentro de public/. No necesita `artisan storage:link`,
        // que sin SSH no se puede ejecutar en Ginernet.
        'uploads' => [
            'driver'     => 'local',
            'root'       => public_path('uploads'),
            'url'        => rtrim(env('APP_URL'), '/').'/uploads',
            'visibility' => 'public',
            'throw'      => false,
        ],
```

- [ ] **Paso 4: Cambiar el disco en los dos controladores**

En `backend/app/Http/Controllers/Api/FoodController.php`, dentro de `uploadImage()`,
sustituir `Storage::disk('public')` por `Storage::disk('uploads')` y
`->store('nutrition/foods', 'public')` por `->store('nutrition/foods', 'uploads')`.

En `backend/app/Http/Controllers/Api/RecipeController.php`, dentro de `uploadImage()`,
hacer el mismo cambio con `'nutrition/recipes'`, y sustituir `Storage::url($path)` por
`Storage::disk('uploads')->url($path)`.

Buscar cualquier otra aparición y cambiarla también:

```bash
cd backend && grep -rn "disk('public')\|'public')" app/Http/Controllers/
```

- [ ] **Paso 5: Crear la carpeta para que exista en el paquete de subida**

```bash
mkdir -p backend/public/uploads
printf '*\n!.gitignore\n' > backend/public/uploads/.gitignore
```

- [ ] **Paso 6: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=UploadDiskTest`
Esperado: PASS en los dos.

No hay imágenes que migrar: en los datos reales, `image_path` es nulo en todos los
alimentos y todas las recetas.

- [ ] **Paso 7: Commit**

```bash
git add backend/config/filesystems.php backend/app/Http/Controllers/Api backend/public/uploads backend/tests/Feature/UploadDiskTest.php
git commit -m "fix(uploads): disco propio en public/uploads, sin symlink"
```

---

## Tarea 6: Tablas y modelos del Sistema

**Ficheros:**
- Crear: `backend/database/migrations/2026_08_10_000001_create_user_progress_table.php`,
  `..._000002_create_daily_quests_table.php`, `..._000003_create_user_achievements_table.php`,
  `..._000004_create_xp_events_table.php`
- Crear: `backend/app/Models/{UserProgress,DailyQuest,UserAchievement,XpEvent}.php`
- Modificar: `backend/app/Models/User.php`
- Test: `backend/tests/Feature/SystemSchemaTest.php`

**Interfaces:**
- Produce: `$user->progress()` → `HasOne<UserProgress>`; modelos `DailyQuest`,
  `UserAchievement`, `XpEvent` con `$fillable` completo.

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/SystemSchemaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_usuario_tiene_una_fila_de_progreso()
    {
        $user = User::factory()->create();

        $progress = UserProgress::create(['user_id' => $user->id]);

        $this->assertSame(1, $progress->level);
        $this->assertSame(0, $progress->xp_total);
        $this->assertTrue($user->progress->is($progress));
    }

    public function test_no_puede_haber_dos_misiones_iguales_el_mismo_dia()
    {
        $user = User::factory()->create();

        \App\Models\DailyQuest::create([
            'user_id' => $user->id, 'date' => '2026-08-10',
            'quest_key' => 'water', 'target' => 2000, 'xp_reward' => 20,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\DailyQuest::create([
            'user_id' => $user->id, 'date' => '2026-08-10',
            'quest_key' => 'water', 'target' => 2000, 'xp_reward' => 20,
        ]);
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=SystemSchemaTest`
Esperado: FALLA — «Base table or view not found: user_progress».

- [ ] **Paso 3: Escribir las cuatro migraciones**

`backend/database/migrations/2026_08_10_000001_create_user_progress_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progreso del usuario. `level` es caché: la verdad es `xp_total`, de donde se
 * derivan nivel y rango. Los acumuladores alimentan las cuatro estadísticas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('xp_total')->default(0);
            $table->decimal('strength_acc', 14, 2)->default(0);
            $table->decimal('endurance_acc', 14, 2)->default(0);
            $table->decimal('consistency_acc', 14, 2)->default(0);
            $table->decimal('vitality_acc', 14, 2)->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_active_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
```

`..._000002_create_daily_quests_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_quests', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('quest_key', 40);
            $table->decimal('target', 10, 2)->default(0);
            $table->decimal('progress', 10, 2)->default(0);
            $table->unsignedSmallInteger('xp_reward')->default(0);
            $table->boolean('is_optional')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date', 'quest_key']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_quests');
    }
};
```

`..._000003_create_user_achievements_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('achievement_key', 40);
            $table->dateTime('unlocked_at');

            $table->unique(['user_id', 'achievement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
```

`..._000004_create_xp_events_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El libro mayor. Todo el XP concedido pasa por aquí: es lo que permite aplicar
 * los topes diarios, auditar la progresión y recalcularla entera desde cero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('source', 20);            // workout|record|quest|quest_bonus|streak
            $table->string('source_id', 60)->nullable();
            $table->integer('amount');
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'source', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');
    }
};
```

- [ ] **Paso 4: Escribir los cuatro modelos**

`backend/app/Models/UserProgress.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    protected $table = 'user_progress';

    protected $fillable = [
        'user_id', 'level', 'xp_total',
        'strength_acc', 'endurance_acc', 'consistency_acc', 'vitality_acc',
        'current_streak', 'longest_streak', 'last_active_date',
    ];

    protected $casts = [
        'level'            => 'integer',
        'xp_total'         => 'integer',
        'strength_acc'     => 'float',
        'endurance_acc'    => 'float',
        'consistency_acc'  => 'float',
        'vitality_acc'     => 'float',
        'current_streak'   => 'integer',
        'longest_streak'   => 'integer',
        'last_active_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

`backend/app/Models/DailyQuest.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyQuest extends Model
{
    protected $fillable = [
        'user_id', 'date', 'quest_key', 'target', 'progress',
        'xp_reward', 'is_optional', 'completed_at',
    ];

    protected $casts = [
        'date'         => 'date',
        'target'       => 'float',
        'progress'     => 'float',
        'xp_reward'    => 'integer',
        'is_optional'  => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
```

`backend/app/Models/UserAchievement.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'achievement_key', 'unlocked_at'];

    protected $casts = ['unlocked_at' => 'datetime'];
}
```

`backend/app/Models/XpEvent.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XpEvent extends Model
{
    protected $fillable = ['user_id', 'date', 'source', 'source_id', 'amount'];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'integer',
    ];
}
```

- [ ] **Paso 5: Añadir la relación en `User`**

En `backend/app/Models/User.php`, después de `workouts()`:

```php
    public function progress()
    {
        return $this->hasOne(UserProgress::class);
    }
```

- [ ] **Paso 6: Migrar y ejecutar el test**

```bash
cd backend && php artisan migrate --force && php artisan test --filter=SystemSchemaTest
```
Esperado: PASS en los dos.

- [ ] **Paso 7: Commit**

```bash
git add backend/database/migrations backend/app/Models backend/tests/Feature/SystemSchemaTest.php
git commit -m "feat(system): tablas y modelos de progreso, misiones, logros y libro mayor"
```

---

## Tarea 7: Niveles, rangos y estadísticas

Funciones puras, sin base de datos. Es la lógica donde un error silencioso corrompe la
progresión de todos los usuarios y no se nota hasta meses después, así que se prueba en
las fronteras exactas.

**Ficheros:**
- Crear: `backend/app/System/Progression.php`, `backend/app/System/Stats.php`
- Test: `backend/tests/Unit/ProgressionTest.php`, `backend/tests/Unit/StatsTest.php`

**Interfaces:**
- Produce:
  - `Progression::xpForNextLevel(int $level): int`
  - `Progression::xpToReachLevel(int $level): int`
  - `Progression::levelForXp(int $xp): int`
  - `Progression::rankForLevel(int $level): string` — `'E'|'D'|'C'|'B'|'A'|'S'`
  - `Stats::value(float $accumulator, float $k): int`
  - `Stats::all(UserProgress $progress): array` — `['strength'=>int, 'endurance'=>int, 'consistency'=>int, 'vitality'=>int]`

- [ ] **Paso 1: Escribir los tests que fallan**

Crear `backend/tests/Unit/ProgressionTest.php`:

```php
<?php

namespace Tests\Unit;

use App\System\Progression;
use PHPUnit\Framework\TestCase;

class ProgressionTest extends TestCase
{
    public function test_el_coste_de_cada_nivel_es_lineal()
    {
        $this->assertSame(100, Progression::xpForNextLevel(1));
        $this->assertSame(140, Progression::xpForNextLevel(2));
        $this->assertSame(660, Progression::xpForNextLevel(15));
    }

    public function test_el_xp_acumulado_coincide_con_las_fronteras_de_rango()
    {
        $this->assertSame(0,     Progression::xpToReachLevel(1));
        $this->assertSame(100,   Progression::xpToReachLevel(2));
        $this->assertSame(5040,  Progression::xpToReachLevel(15));   // rango D
        $this->assertSame(13440, Progression::xpToReachLevel(25));   // rango C
        $this->assertSame(25840, Progression::xpToReachLevel(35));   // rango B
        $this->assertSame(42240, Progression::xpToReachLevel(45));   // rango A
        $this->assertSame(62640, Progression::xpToReachLevel(55));   // rango S
    }

    public function test_el_nivel_se_deriva_del_xp_en_las_fronteras()
    {
        $this->assertSame(1,  Progression::levelForXp(0));
        $this->assertSame(1,  Progression::levelForXp(99));
        $this->assertSame(2,  Progression::levelForXp(100));
        $this->assertSame(14, Progression::levelForXp(5039));
        $this->assertSame(15, Progression::levelForXp(5040));
        $this->assertSame(55, Progression::levelForXp(62640));
    }

    public function test_el_nivel_nunca_es_menor_que_uno()
    {
        $this->assertSame(1, Progression::levelForXp(-50));
    }

    public function test_el_rango_cambia_en_los_niveles_correctos()
    {
        $this->assertSame('E', Progression::rankForLevel(1));
        $this->assertSame('E', Progression::rankForLevel(14));
        $this->assertSame('D', Progression::rankForLevel(15));
        $this->assertSame('D', Progression::rankForLevel(24));
        $this->assertSame('C', Progression::rankForLevel(25));
        $this->assertSame('B', Progression::rankForLevel(35));
        $this->assertSame('A', Progression::rankForLevel(45));
        $this->assertSame('S', Progression::rankForLevel(55));
        $this->assertSame('S', Progression::rankForLevel(300));
    }

    public function test_ida_y_vuelta_entre_nivel_y_xp_para_los_cien_primeros()
    {
        for ($level = 1; $level <= 100; $level++) {
            $exact = Progression::xpToReachLevel($level);

            $this->assertSame($level, Progression::levelForXp($exact), "nivel {$level} exacto");

            if ($level > 1) {
                $this->assertSame($level - 1, Progression::levelForXp($exact - 1), "nivel {$level} - 1 XP");
            }
        }
    }
}
```

Crear `backend/tests/Unit/StatsTest.php`:

```php
<?php

namespace Tests\Unit;

use App\System\Stats;
use PHPUnit\Framework\TestCase;

class StatsTest extends TestCase
{
    public function test_la_estadistica_es_la_raiz_del_acumulador_partido_por_k()
    {
        $this->assertSame(0,  Stats::value(0, 1500));
        $this->assertSame(1,  Stats::value(1500, 1500));
        $this->assertSame(10, Stats::value(150000, 1500));   // 100 entrenos de gimnasio
        $this->assertSame(13, Stats::value(4500, 25));       // 100 sesiones de 45 minutos
        $this->assertSame(23, Stats::value(800, 1.5));       // 200 días + 600 misiones
        $this->assertSame(10, Stats::value(300, 2.5));       // 300 objetivos de hábito
    }

    public function test_un_acumulador_negativo_o_una_k_invalida_dan_cero()
    {
        $this->assertSame(0, Stats::value(-10, 1500));
        $this->assertSame(0, Stats::value(1500, 0));
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que fallan**

Ejecutar: `cd backend && php artisan test --filter="ProgressionTest|StatsTest"`
Esperado: FALLA — «Class "App\System\Progression" not found».

- [ ] **Paso 3: Escribir `Progression`**

Crear `backend/app/System/Progression.php`:

```php
<?php

namespace App\System;

/**
 * La curva de nivel y los rangos. Sin estado y sin base de datos.
 *
 *   XP para pasar del nivel N al N+1 = 100 + 40(N-1)
 *   XP acumulado para alcanzar el nivel N = 100(N-1) + 20(N-1)(N-2) = 20N² + 40N - 60
 *
 * Lineal a propósito: con curva exponencial, a partir del nivel 30 haría falta un mes
 * por nivel y la app se abandona.
 */
final class Progression
{
    /** Umbral de nivel de cada rango, de mayor a menor. */
    private const RANKS = [55 => 'S', 45 => 'A', 35 => 'B', 25 => 'C', 15 => 'D', 1 => 'E'];

    public static function xpForNextLevel(int $level): int
    {
        return 100 + 40 * (max(1, $level) - 1);
    }

    public static function xpToReachLevel(int $level): int
    {
        $n = max(1, $level);

        return 20 * $n * $n + 40 * $n - 60;
    }

    public static function levelForXp(int $xp): int
    {
        if ($xp <= 0) {
            return 1;
        }

        // Inversa de la cuadrática: N = (sqrt(6400 + 80·xp) - 40) / 40
        $level = (int) floor((sqrt(6400 + 80 * $xp) - 40) / 40);
        $level = max(1, $level);

        // ponytail: corrección de ±1 por si la raíz flotante se queda al borde.
        // Cuesta dos comparaciones y elimina toda una clase de errores en las fronteras.
        while (self::xpToReachLevel($level + 1) <= $xp) {
            $level++;
        }
        while ($level > 1 && self::xpToReachLevel($level) > $xp) {
            $level--;
        }

        return $level;
    }

    public static function rankForLevel(int $level): string
    {
        foreach (self::RANKS as $minLevel => $rank) {
            if ($level >= $minLevel) {
                return $rank;
            }
        }

        return 'E';
    }

    /**
     * XP conseguido dentro del nivel actual y XP que cuesta el nivel entero.
     * Es lo que dibuja la barra de la cabecera.
     *
     * @return array{into_level:int, for_next:int}
     */
    public static function levelBar(int $xpTotal): array
    {
        $level = self::levelForXp($xpTotal);

        return [
            'into_level' => $xpTotal - self::xpToReachLevel($level),
            'for_next'   => self::xpForNextLevel($level),
        ];
    }
}
```

- [ ] **Paso 4: Escribir `Stats`**

Crear `backend/app/System/Stats.php`:

```php
<?php

namespace App\System;

use App\Models\UserProgress;

/**
 * Las cuatro estadísticas suben solas: valor = floor(sqrt(acumulador / K)).
 * La raíz hace que suba rápido al principio y cueste más después, sin llegar a
 * estancarse. Las K viven en config/srank.php.
 */
final class Stats
{
    public static function value(float $accumulator, float $k): int
    {
        if ($accumulator <= 0 || $k <= 0) {
            return 0;
        }

        return (int) floor(sqrt($accumulator / $k));
    }

    /**
     * @return array{strength:int, endurance:int, consistency:int, vitality:int}
     */
    public static function all(UserProgress $progress): array
    {
        $k = config('srank.stats');

        return [
            'strength'    => self::value($progress->strength_acc, $k['strength']),
            'endurance'   => self::value($progress->endurance_acc, $k['endurance']),
            'consistency' => self::value($progress->consistency_acc, $k['consistency']),
            'vitality'    => self::value($progress->vitality_acc, $k['vitality']),
        ];
    }
}
```

- [ ] **Paso 5: Ejecutar los tests — ahora pasan**

Ejecutar: `cd backend && php artisan test --filter="ProgressionTest|StatsTest"`
Esperado: PASS en los 8.

- [ ] **Paso 6: Commit**

```bash
git add backend/app/System backend/tests/Unit
git commit -m "feat(system): curva de niveles, rangos y cálculo de estadísticas"
```

---

## Tarea 8: Libro mayor de XP con topes diarios

**Ficheros:**
- Crear: `backend/app/System/XpLedger.php`
- Test: `backend/tests/Feature/XpLedgerTest.php`

**Interfaces:**
- Consume: `App\Models\XpEvent`, `App\Models\UserProgress`.
- Produce:
  - `XpLedger::award(User $user, string $source, ?string $sourceId, int $amount, CarbonImmutable $date): int` — devuelve el XP **realmente** concedido tras aplicar el tope.
  - `XpLedger::spentOn(User $user, CarbonImmutable $date): int`
  - `XpLedger::countSource(User $user, string $source, CarbonImmutable $date): int`
  - `XpLedger::hasSource(User $user, string $source, CarbonImmutable $date): bool`
  - `XpLedger::progressFor(User $user): UserProgress`

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/XpLedgerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\System\XpLedger;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function hoy(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-08-10');
    }

    public function test_conceder_xp_lo_suma_al_total_y_lo_apunta_en_el_libro()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $granted = $ledger->award($user, 'workout', 'w1', 50, $this->hoy());

        $this->assertSame(50, $granted);
        $this->assertSame(50, $ledger->progressFor($user)->xp_total);
        $this->assertDatabaseHas('xp_events', [
            'user_id' => $user->id, 'source' => 'workout', 'amount' => 50,
        ]);
    }

    public function test_el_tope_diario_recorta_la_ultima_concesion()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 280, $this->hoy());
        $granted = $ledger->award($user, 'record', 'w1', 30, $this->hoy());

        $this->assertSame(20, $granted, 'solo caben 20 XP más hasta el tope de 300');
        $this->assertSame(300, $ledger->progressFor($user)->xp_total);
    }

    public function test_pasado_el_tope_no_se_concede_nada_mas()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 300, $this->hoy());
        $granted = $ledger->award($user, 'quest', 'water', 20, $this->hoy());

        $this->assertSame(0, $granted);
        $this->assertSame(300, $ledger->progressFor($user)->xp_total);
    }

    public function test_el_tope_es_por_dia_no_acumulado()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 300, $this->hoy());
        $granted = $ledger->award($user, 'workout', 'w2', 50, $this->hoy()->addDay());

        $this->assertSame(50, $granted);
        $this->assertSame(350, $ledger->progressFor($user)->xp_total);
    }

    public function test_cuenta_los_entrenos_que_han_puntuado_hoy()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 50, $this->hoy());
        $ledger->award($user, 'workout', 'w2', 50, $this->hoy());

        $this->assertSame(2, $ledger->countSource($user, 'workout', $this->hoy()));
        $this->assertSame(0, $ledger->countSource($user, 'workout', $this->hoy()->addDay()));
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=XpLedgerTest`
Esperado: FALLA — «Class "App\System\XpLedger" not found».

- [ ] **Paso 3: Escribir `XpLedger`**

Crear `backend/app/System/XpLedger.php`:

```php
<?php

namespace App\System;

use App\Models\User;
use App\Models\UserProgress;
use App\Models\XpEvent;
use Carbon\CarbonImmutable;

/**
 * Todo el XP concedido pasa por aquí. Nadie escribe en `xp_events` ni toca
 * `xp_total` por su cuenta: es lo que hace que los topes diarios se cumplan
 * siempre y que el progreso se pueda recalcular entero desde el libro mayor.
 */
class XpLedger
{
    /**
     * Concede XP aplicando el tope diario. Devuelve lo realmente concedido,
     * que puede ser menos de lo pedido, o cero.
     */
    public function award(User $user, string $source, ?string $sourceId, int $amount, CarbonImmutable $date): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $room = max(0, (int) config('srank.xp.daily_cap') - $this->spentOn($user, $date));
        $granted = min($amount, $room);

        if ($granted <= 0) {
            return 0;
        }

        XpEvent::create([
            'user_id'   => $user->id,
            'date'      => $date->toDateString(),
            'source'    => $source,
            'source_id' => $sourceId,
            'amount'    => $granted,
        ]);

        $progress = $this->progressFor($user);
        $progress->xp_total += $granted;
        $progress->level = Progression::levelForXp($progress->xp_total);
        $progress->save();

        return $granted;
    }

    public function spentOn(User $user, CarbonImmutable $date): int
    {
        return (int) XpEvent::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->sum('amount');
    }

    public function countSource(User $user, string $source, CarbonImmutable $date): int
    {
        return XpEvent::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->where('source', $source)
            ->count();
    }

    public function hasSource(User $user, string $source, CarbonImmutable $date): bool
    {
        return $this->countSource($user, $source, $date) > 0;
    }

    public function progressFor(User $user): UserProgress
    {
        return UserProgress::firstOrCreate(['user_id' => $user->id]);
    }
}
```

- [ ] **Paso 4: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=XpLedgerTest`
Esperado: PASS en los 5.

- [ ] **Paso 5: Commit**

```bash
git add backend/app/System/XpLedger.php backend/tests/Feature/XpLedgerTest.php
git commit -m "feat(system): libro mayor de XP con tope diario"
```

---

## Tarea 9: Misiones diarias

**Ficheros:**
- Crear: `backend/app/System/QuestService.php`
- Test: `backend/tests/Feature/QuestServiceTest.php`

**Interfaces:**
- Consume: `XpLedger`.
- Produce:
  - `QuestService::generate(User $user, CarbonImmutable $date): void` — idempotente.
  - `QuestService::sync(User $user, CarbonImmutable $date): array` — recalcula el progreso de las misiones del día, completa las que llegan al objetivo, concede su XP y el bonus de las cuatro obligatorias. Devuelve `['completed' => string[], 'xp' => int]`.
  - `QuestService::forDate(User $user, CarbonImmutable $date): array` — las misiones del día listas para la API, con `label` en español.
  - `QuestService::completeOptional(User $user, string $questKey, CarbonImmutable $date): bool`
  - `QuestService::LABELS` — mapa `quest_key` → texto.

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/QuestServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\DailyQuest;
use App\Models\NutritionGoal;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\Workout;
use App\System\QuestService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestServiceTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $hoy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoy = CarbonImmutable::parse('2026-08-10');   // lunes
    }

    public function test_genera_como_mucho_cuatro_obligatorias_y_una_opcional()
    {
        $user = User::factory()->create(['weekly_goal' => 3, 'water_goal_ml' => 2000]);
        NutritionGoal::create(['user_id' => $user->id, 'target_protein' => 130]);

        app(QuestService::class)->generate($user, $this->hoy);

        $quests = DailyQuest::where('user_id', $user->id)->get();

        $this->assertCount(4, $quests->where('is_optional', false));
        $this->assertCount(1, $quests->where('is_optional', true));
    }

    public function test_generar_dos_veces_el_mismo_dia_no_duplica_nada()
    {
        $user = User::factory()->create();
        $service = app(QuestService::class);

        $service->generate($user, $this->hoy);
        $primera = DailyQuest::where('user_id', $user->id)->pluck('quest_key')->sort()->values();

        $service->generate($user, $this->hoy);
        $segunda = DailyQuest::where('user_id', $user->id)->pluck('quest_key')->sort()->values();

        $this->assertEquals($primera, $segunda);
    }

    public function test_la_rotativa_es_estable_para_el_mismo_usuario_y_dia()
    {
        $user = User::factory()->create();
        $service = app(QuestService::class);

        $service->generate($user, $this->hoy);
        $elegida = DailyQuest::where('user_id', $user->id)
            ->whereIn('quest_key', ['weight', 'meals_3', 'supplements'])
            ->value('quest_key');

        DailyQuest::where('user_id', $user->id)->delete();
        $service->generate($user, $this->hoy);

        $this->assertSame($elegida, DailyQuest::where('user_id', $user->id)
            ->whereIn('quest_key', ['weight', 'meals_3', 'supplements'])
            ->value('quest_key'));
    }

    public function test_sin_objetivo_nutricional_no_hay_mision_de_proteina()
    {
        $user = User::factory()->create();

        app(QuestService::class)->generate($user, $this->hoy);

        $this->assertDatabaseMissing('daily_quests', [
            'user_id' => $user->id, 'quest_key' => 'protein',
        ]);
    }

    public function test_no_aparece_entrenar_si_la_meta_semanal_ya_esta_cumplida()
    {
        $user = User::factory()->create(['weekly_goal' => 2]);

        foreach ([0, 1] as $offset) {
            Workout::create([
                'user_id' => $user->id, 'mode' => 'gym',
                'date' => $this->hoy->addDays($offset)->toDateTimeString(),
                'duration_minutes' => 45,
            ]);
        }

        app(QuestService::class)->generate($user, $this->hoy);

        $this->assertDatabaseMissing('daily_quests', [
            'user_id' => $user->id, 'quest_key' => 'train',
        ]);
    }

    public function test_al_llegar_al_objetivo_de_agua_la_mision_se_completa_y_da_xp()
    {
        $user = User::factory()->create(['water_goal_ml' => 2000]);
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        WaterLog::create(['user_id' => $user->id, 'date' => $this->hoy->toDateString(), 'amount_ml' => 2000]);

        $resultado = $service->sync($user, $this->hoy);

        $this->assertContains('water', $resultado['completed']);
        $this->assertSame(20, $resultado['xp']);
        $this->assertNotNull(DailyQuest::where('user_id', $user->id)->where('quest_key', 'water')->value('completed_at'));
    }

    public function test_una_mision_completada_no_vuelve_a_pagar()
    {
        $user = User::factory()->create(['water_goal_ml' => 2000]);
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        WaterLog::create(['user_id' => $user->id, 'date' => $this->hoy->toDateString(), 'amount_ml' => 2000]);

        $service->sync($user, $this->hoy);
        $segunda = $service->sync($user, $this->hoy);

        $this->assertSame([], $segunda['completed']);
        $this->assertSame(0, $segunda['xp']);
    }

    public function test_completar_todas_las_obligatorias_da_el_bonus_una_sola_vez()
    {
        $user = User::factory()->create();
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        // Damos por cumplidas todas las obligatorias sin pasar por sync()
        DailyQuest::where('user_id', $user->id)->where('is_optional', false)
            ->update(['progress' => 999999, 'target' => 1]);

        $resultado = $service->sync($user, $this->hoy);

        $this->assertDatabaseHas('xp_events', [
            'user_id' => $user->id, 'source' => 'quest_bonus', 'amount' => 40,
        ]);

        $service->sync($user, $this->hoy);

        $this->assertSame(1, \App\Models\XpEvent::where('user_id', $user->id)
            ->where('source', 'quest_bonus')->count());
        $this->assertGreaterThan(0, $resultado['xp']);
    }

    public function test_la_opcional_se_marca_a_mano()
    {
        $user = User::factory()->create();
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        $opcional = DailyQuest::where('user_id', $user->id)->where('is_optional', true)->first();

        $this->assertTrue($service->completeOptional($user, $opcional->quest_key, $this->hoy));
        $this->assertFalse($service->completeOptional($user, $opcional->quest_key, $this->hoy), 'no paga dos veces');
        $this->assertFalse($service->completeOptional($user, 'water', $this->hoy), 'las obligatorias no se marcan a mano');
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=QuestServiceTest`
Esperado: FALLA — «Class "App\System\QuestService" not found».

- [ ] **Paso 3: Escribir `QuestService`**

Crear `backend/app/System/QuestService.php`:

```php
<?php

namespace App\System;

use App\Models\DailyQuest;
use App\Models\MealLog;
use App\Models\SupplementLog;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\WeightLog;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Las misiones del día salen de los objetivos reales del usuario, nunca inventadas:
 * su meta semanal, su objetivo de hidratación, su objetivo de proteína. Máximo cuatro
 * obligatorias y una opcional; una lista larga desmotiva más de lo que empuja.
 *
 * No hay castigo por fallar: fallar rompe la racha, y eso ya duele bastante.
 */
class QuestService
{
    public const LABELS = [
        'train'       => 'Entrenar',
        'water'       => 'Beber :litros litros de agua',
        'protein'     => 'Llegar a :gramos g de proteína',
        'weight'      => 'Apuntar el peso',
        'meals_3'     => 'Registrar las 3 comidas',
        'supplements' => 'Tomar los suplementos',
        'pushups_50'  => '50 flexiones',
        'steps_8000'  => '8.000 pasos',
    ];

    private const ROTATING = ['weight', 'meals_3', 'supplements'];
    private const OPTIONAL = ['pushups_50', 'steps_8000'];

    public function __construct(private XpLedger $ledger) {}

    /**
     * Crea las misiones del día si aún no existen. Idempotente: la primera petición
     * de cada día las crea, las siguientes no hacen nada. Por eso no hace falta cron,
     * que en un hosting compartido es poco fiable.
     */
    public function generate(User $user, CarbonImmutable $date): void
    {
        $exists = DailyQuest::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->exists();

        if ($exists) {
            return;
        }

        $rewards = config('srank.quests');
        $rows = [];

        // Entrenar: solo mientras no haya cumplido la cuota de la semana en curso.
        // FitLoop nunca ha guardado qué días tocan, así que la misión empuja a la cuota.
        $weeklyGoal = max(1, (int) $user->weekly_goal);
        $doneThisWeek = $this->workoutsThisWeek($user, $date);

        if ($doneThisWeek < $weeklyGoal) {
            $rows[] = ['quest_key' => 'train', 'target' => $weeklyGoal, 'xp_reward' => $rewards['train']];
        }

        // Agua: siempre.
        $rows[] = [
            'quest_key'  => 'water',
            'target'     => (int) ($user->water_goal_ml ?? 2000),
            'xp_reward'  => $rewards['water'],
        ];

        // Proteína: solo si tiene objetivo nutricional.
        $goal = DB::table('nutrition_goals')->where('user_id', $user->id)->first();

        if ($goal) {
            $rows[] = [
                'quest_key' => 'protein',
                'target'    => (float) $goal->target_protein,
                'xp_reward' => $rewards['protein'],
            ];
        }

        // Rotativa: una de las tres, elegida de forma determinista a partir de
        // (usuario, fecha), para que el resultado sea estable si se repite la petición.
        $rotating = self::ROTATING[$this->pick($user, $date, count(self::ROTATING))];
        $rows[] = [
            'quest_key' => $rotating,
            'target'    => $rotating === 'meals_3' ? 3 : 1,
            'xp_reward' => $rewards[$rotating],
        ];

        // Opcional: bajo el epígrafe «si te sobra tiempo».
        $optional = self::OPTIONAL[$this->pick($user, $date, count(self::OPTIONAL))];
        $rows[] = [
            'quest_key'   => $optional,
            'target'      => 1,
            'xp_reward'   => $rewards['optional'],
            'is_optional' => true,
        ];

        foreach ($rows as $row) {
            DailyQuest::create($row + [
                'user_id'     => $user->id,
                'date'        => $date->toDateString(),
                'progress'    => 0,
                'is_optional' => false,
            ]);
        }
    }

    /**
     * Recalcula el progreso de las misiones del día, completa las que llegan al
     * objetivo y concede su XP. Es el único sitio donde una misión se da por cumplida:
     * lo llaman tanto GET /api/system/today como el listener tras cada registro.
     *
     * @return array{completed: string[], xp: int}
     */
    public function sync(User $user, CarbonImmutable $date): array
    {
        $quests = DailyQuest::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->get();

        if ($quests->isEmpty()) {
            return ['completed' => [], 'xp' => 0];
        }

        $measured = $this->measure($user, $date);
        $completed = [];
        $xp = 0;

        foreach ($quests as $quest) {
            if (array_key_exists($quest->quest_key, $measured)) {
                $quest->progress = $measured[$quest->quest_key];
            }

            if (! $quest->isCompleted() && $quest->target > 0 && $quest->progress >= $quest->target) {
                $quest->completed_at = now();
                $xp += $this->ledger->award($user, 'quest', $quest->quest_key, $quest->xp_reward, $date);
                $completed[] = $quest->quest_key;
            }

            $quest->save();
        }

        $xp += $this->awardBonusIfAllDone($user, $date, $quests->fresh());

        return ['completed' => $completed, 'xp' => $xp];
    }

    /**
     * Marca a mano una misión opcional. Las opcionales («50 flexiones», «8.000 pasos»)
     * no se pueden medir solas, así que las confirma el usuario pulsando.
     */
    public function completeOptional(User $user, string $questKey, CarbonImmutable $date): bool
    {
        $quest = DailyQuest::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->where('quest_key', $questKey)
            ->where('is_optional', true)
            ->whereNull('completed_at')
            ->first();

        if (! $quest) {
            return false;
        }

        $quest->update(['progress' => $quest->target, 'completed_at' => now()]);
        $this->ledger->award($user, 'quest', $quest->quest_key, $quest->xp_reward, $date);

        return true;
    }

    /**
     * Las misiones del día tal y como las pinta la app.
     */
    public function forDate(User $user, CarbonImmutable $date): array
    {
        return DailyQuest::where('user_id', $user->id)
            ->where('date', $date->toDateString())
            ->orderBy('is_optional')
            ->orderBy('id')
            ->get()
            ->map(fn (DailyQuest $q) => [
                'key'         => $q->quest_key,
                'label'       => $this->label($q),
                'target'      => (float) $q->target,
                'progress'    => (float) $q->progress,
                'xp_reward'   => $q->xp_reward,
                'is_optional' => $q->is_optional,
                'completed'   => $q->isCompleted(),
            ])
            ->all();
    }

    private function label(DailyQuest $quest): string
    {
        $template = self::LABELS[$quest->quest_key] ?? $quest->quest_key;

        return strtr($template, [
            ':litros' => rtrim(rtrim(number_format($quest->target / 1000, 1, ',', ''), '0'), ','),
            ':gramos' => (int) $quest->target,
        ]);
    }

    /**
     * Progreso real de cada tipo de misión medible, leído de los datos del día.
     *
     * @return array<string, float>
     */
    private function measure(User $user, CarbonImmutable $date): array
    {
        $day = $date->toDateString();

        return [
            'train'       => $this->workoutsThisWeek($user, $date),
            'water'       => (float) WaterLog::where('user_id', $user->id)->whereDate('date', $day)->sum('amount_ml'),
            'protein'     => (float) MealLog::where('user_id', $user->id)->whereDate('date', $day)->sum('protein'),
            'weight'      => WeightLog::where('user_id', $user->id)->whereDate('date', $day)->exists() ? 1 : 0,
            'meals_3'     => (float) MealLog::where('user_id', $user->id)->whereDate('date', $day)
                                ->distinct()->count('meal_type'),
            // Los cuatro suplementos del día marcados
            'supplements' => SupplementLog::where('user_id', $user->id)->whereDate('date', $day)
                                ->where('taken', true)->count() >= 4 ? 1 : 0,
        ];
    }

    private function workoutsThisWeek(User $user, CarbonImmutable $date): int
    {
        return Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->whereBetween('date', [
                $date->startOfWeek()->startOfDay()->toDateTimeString(),
                $date->endOfWeek()->endOfDay()->toDateTimeString(),
            ])
            ->count();
    }

    /**
     * Bonus por completar todas las obligatorias del día. Una sola vez: el libro
     * mayor es el que lleva la cuenta.
     */
    private function awardBonusIfAllDone(User $user, CarbonImmutable $date, $quests): int
    {
        $mandatory = $quests->where('is_optional', false);

        if ($mandatory->isEmpty() || $mandatory->contains(fn ($q) => ! $q->isCompleted())) {
            return 0;
        }

        if ($this->ledger->hasSource($user, 'quest_bonus', $date)) {
            return 0;
        }

        return $this->ledger->award($user, 'quest_bonus', null, config('srank.xp.all_quests_bonus'), $date);
    }

    /**
     * Elección determinista a partir de (usuario, fecha).
     */
    private function pick(User $user, CarbonImmutable $date, int $options): int
    {
        return crc32($user->id.$date->toDateString()) % $options;
    }
}
```

- [ ] **Paso 4: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=QuestServiceTest`
Esperado: PASS en los 9.

- [ ] **Paso 5: Commit**

```bash
git add backend/app/System/QuestService.php backend/tests/Feature/QuestServiceTest.php
git commit -m "feat(system): misiones diarias generadas de los objetivos del usuario"
```

---

## Tarea 10: Los 40 logros

**Ficheros:**
- Crear: `backend/app/System/AchievementService.php`
- Test: `backend/tests/Feature/AchievementServiceTest.php`

**Interfaces:**
- Produce:
  - `AchievementService::CATALOG` — 40 entradas `key => ['name','description','rarity']` con rareza `common|rare|epic|legendary`.
  - `AchievementService::evaluate(User $user): array` — devuelve los recién desbloqueados: `[['key'=>string,'name'=>string,'rarity'=>string], ...]`.
  - `AchievementService::listFor(User $user): array` — los 40 con `unlocked` y `unlocked_at`.

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/AchievementServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use App\System\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_tiene_cuarenta_logros_en_cuatro_rarezas()
    {
        $catalog = AchievementService::CATALOG;

        $this->assertCount(40, $catalog);

        $porRareza = collect($catalog)->countBy('rarity');

        $this->assertSame(10, $porRareza['common']);
        $this->assertSame(12, $porRareza['rare']);
        $this->assertSame(10, $porRareza['epic']);
        $this->assertSame(8,  $porRareza['legendary']);
    }

    public function test_el_primer_entreno_desbloquea_primer_paso()
    {
        $user = User::factory()->create();
        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => '2026-08-10 18:00:00', 'duration_minutes' => 45,
        ]);

        $nuevos = app(AchievementService::class)->evaluate($user);

        $this->assertContains('first_step', array_column($nuevos, 'key'));
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id, 'achievement_key' => 'first_step',
        ]);
    }

    public function test_un_logro_ya_desbloqueado_no_se_devuelve_dos_veces()
    {
        $user = User::factory()->create();
        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => '2026-08-10 18:00:00', 'duration_minutes' => 45,
        ]);

        $service = app(AchievementService::class);
        $service->evaluate($user);
        $segunda = $service->evaluate($user);

        $this->assertSame([], $segunda);
    }

    public function test_los_pasos_no_cuentan_como_entrenamiento()
    {
        $user = User::factory()->create();
        Workout::create([
            'user_id' => $user->id, 'mode' => 'pasos',
            'date' => '2026-08-10 18:00:00', 'duration_minutes' => 60,
        ]);

        $nuevos = app(AchievementService::class)->evaluate($user);

        $this->assertNotContains('first_step', array_column($nuevos, 'key'));
    }

    public function test_el_umbral_de_diez_entrenos_es_exacto()
    {
        $user = User::factory()->create();
        $service = app(AchievementService::class);

        for ($i = 1; $i <= 9; $i++) {
            Workout::create([
                'user_id' => $user->id, 'mode' => 'gym',
                'date' => "2026-0{$i}-01 18:00:00", 'duration_minutes' => 45,
            ]);
        }

        $service->evaluate($user);
        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $user->id, 'achievement_key' => 'workouts_10',
        ]);

        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => '2026-10-01 18:00:00', 'duration_minutes' => 45,
        ]);

        $this->assertContains('workouts_10', array_column($service->evaluate($user), 'key'));
    }

    public function test_la_lista_devuelve_los_cuarenta_con_su_estado()
    {
        $user = User::factory()->create();

        $lista = app(AchievementService::class)->listFor($user);

        $this->assertCount(40, $lista);
        $this->assertFalse($lista[0]['unlocked']);
        $this->assertArrayHasKey('rarity', $lista[0]);
        $this->assertArrayHasKey('description', $lista[0]);
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=AchievementServiceTest`
Esperado: FALLA — «Class "App\System\AchievementService" not found».

- [ ] **Paso 3: Escribir `AchievementService`**

Crear `backend/app/System/AchievementService.php`:

```php
<?php

namespace App\System;

use App\Models\User;
use App\Models\UserAchievement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cuarenta logros en cuatro rarezas. Los bloqueados se enseñan con su pista visible,
 * no ocultos: saber qué falta es lo que tira.
 *
 * Las métricas se calculan de una vez y se evalúan todas las condiciones contra ellas.
 * ponytail: es una decena larga de consultas por evaluación, aceptable con un puñado de
 * usuarios; si algún día pesa, se evalúan solo los logros aún bloqueados.
 */
class AchievementService
{
    public const CATALOG = [
        // ── Comunes (10) ──────────────────────────────────────────────────
        'first_step'   => ['name' => 'Primer Paso',    'description' => 'Completa tu primer entrenamiento',            'rarity' => 'common'],
        'first_meal'   => ['name' => 'Primera Comida', 'description' => 'Registra tu primera comida',                  'rarity' => 'common'],
        'hydrated'     => ['name' => 'Hidratado',      'description' => 'Alcanza tu objetivo de agua un día',          'rarity' => 'common'],
        'on_scale'     => ['name' => 'En la Báscula',  'description' => 'Apunta tu peso por primera vez',              'rarity' => 'common'],
        'first_pr'     => ['name' => 'Superación',     'description' => 'Registra tu primer levantamiento con peso',   'rarity' => 'common'],
        'own_routine'  => ['name' => 'Rutina Propia',  'description' => 'Crea tu primera plantilla de entrenamiento',  'rarity' => 'common'],
        'three_a_day'  => ['name' => 'Tres al Día',    'description' => 'Registra las tres comidas de un día',         'rarity' => 'common'],
        'streak_3'     => ['name' => 'Constante',      'description' => '3 días seguidos con actividad',               'rarity' => 'common'],
        'workouts_10'  => ['name' => 'En Racha',       'description' => '10 entrenamientos completados',               'rarity' => 'common'],
        'supplemented' => ['name' => 'Suplementado',   'description' => 'Marca tu primer suplemento',                  'rarity' => 'common'],

        // ── Raras (12) ────────────────────────────────────────────────────
        'workouts_25'  => ['name' => 'Constancia',      'description' => '25 entrenamientos completados',              'rarity' => 'rare'],
        'streak_7'     => ['name' => 'Semana de Fuego', 'description' => '7 días seguidos con actividad',              'rarity' => 'rare'],
        'swimmer'      => ['name' => 'Nadador',         'description' => 'Completa tu primer entrenamiento de natación','rarity' => 'rare'],
        'gym_rat'      => ['name' => 'Gym Rat',         'description' => 'Completa tu primer entrenamiento de gimnasio','rarity' => 'rare'],
        'home_trainer' => ['name' => 'Casero',          'description' => 'Completa tu primer entrenamiento en casa',   'rarity' => 'rare'],
        'bodyweight'   => ['name' => 'Peso Corporal',   'description' => 'Completa tu primer entrenamiento de calistenia','rarity' => 'rare'],
        'all_modes'    => ['name' => 'Explorador',      'description' => 'Entrena en las 4 modalidades',               'rarity' => 'rare'],
        'perfect_week' => ['name' => 'Semana Perfecta', 'description' => 'Ten actividad los 7 días de una semana',     'rarity' => 'rare'],
        'calories_7'   => ['name' => 'Diana',           'description' => '7 días cumpliendo tu objetivo de calorías',  'rarity' => 'rare'],
        'protein_14'   => ['name' => 'Proteico',        'description' => '14 días alcanzando tu objetivo de proteína', 'rarity' => 'rare'],
        'water_30'     => ['name' => 'Bien Regado',     'description' => '30 días alcanzando tu objetivo de agua',     'rarity' => 'rare'],
        'records_5'    => ['name' => 'Cinco Récords',   'description' => 'Bate 5 récords personales',                  'rarity' => 'rare'],

        // ── Épicas (10) ───────────────────────────────────────────────────
        'workouts_50'    => ['name' => 'Medio Centenar',      'description' => '50 entrenamientos completados',          'rarity' => 'epic'],
        'streak_30'      => ['name' => 'Mes Épico',           'description' => '30 días seguidos con actividad',         'rarity' => 'epic'],
        'volume_100k'    => ['name' => 'Tonelada',            'description' => 'Mueve 100.000 kg en total',              'rarity' => 'epic'],
        'pool_10km'      => ['name' => 'Maratón de Piscina',  'description' => 'Nada 10 km acumulados',                  'rarity' => 'epic'],
        'early_bird_20'  => ['name' => 'Madrugador',          'description' => '20 entrenamientos antes de las 8:00',    'rarity' => 'epic'],
        'night_owl_20'   => ['name' => 'Noctámbulo',          'description' => '20 entrenamientos después de las 22:00', 'rarity' => 'epic'],
        'variety_30'     => ['name' => 'Variado',             'description' => 'Registra 30 ejercicios distintos',       'rarity' => 'epic'],
        'chef_10'        => ['name' => 'Chef',                'description' => 'Crea 10 recetas propias',                'rarity' => 'epic'],
        'flawless_month' => ['name' => 'Mes Impecable',       'description' => '20 días de un mes con todas las misiones','rarity' => 'epic'],
        'rank_c'         => ['name' => 'Rango C',             'description' => 'Alcanza el rango C',                     'rarity' => 'epic'],

        // ── Legendarias (8) ───────────────────────────────────────────────
        'workouts_100'  => ['name' => 'Centurión',     'description' => '100 entrenamientos completados',           'rarity' => 'legendary'],
        'workouts_365'  => ['name' => 'Veterano',      'description' => '365 entrenamientos completados',           'rarity' => 'legendary'],
        'streak_100'    => ['name' => 'Imparable',     'description' => '100 días seguidos con actividad',          'rarity' => 'legendary'],
        'year_complete' => ['name' => 'Año Completo',  'description' => '12 meses seguidos con actividad',          'rarity' => 'legendary'],
        'volume_1m'     => ['name' => 'Titán',         'description' => 'Mueve 1.000.000 kg en total',             'rarity' => 'legendary'],
        'rank_a'        => ['name' => 'Rango A',       'description' => 'Alcanza el rango A',                      'rarity' => 'legendary'],
        'rank_s'        => ['name' => 'Rango S',       'description' => 'Alcanza el rango S',                      'rarity' => 'legendary'],
        'collector'     => ['name' => 'Coleccionista', 'description' => 'Desbloquea los otros 39 logros',          'rarity' => 'legendary'],
    ];

    /**
     * Evalúa los logros aún bloqueados y guarda los que se cumplen.
     *
     * @return array<int, array{key:string, name:string, rarity:string}>
     */
    public function evaluate(User $user): array
    {
        $unlocked = UserAchievement::where('user_id', $user->id)
            ->pluck('achievement_key')
            ->all();

        $pending = array_diff(array_keys(self::CATALOG), $unlocked);

        if ($pending === []) {
            return [];
        }

        $m = $this->metrics($user);
        $conditions = $this->conditions($m, count($unlocked));
        $new = [];

        foreach ($pending as $key) {
            if (($conditions[$key] ?? false) === true) {
                UserAchievement::create([
                    'user_id'         => $user->id,
                    'achievement_key' => $key,
                    'unlocked_at'     => now(),
                ]);

                $new[] = [
                    'key'    => $key,
                    'name'   => self::CATALOG[$key]['name'],
                    'rarity' => self::CATALOG[$key]['rarity'],
                ];
            }
        }

        return $new;
    }

    /**
     * Los 40 con su estado, para la pantalla de logros.
     */
    public function listFor(User $user): array
    {
        $unlocked = UserAchievement::where('user_id', $user->id)
            ->pluck('unlocked_at', 'achievement_key');

        $out = [];

        foreach (self::CATALOG as $key => $meta) {
            $out[] = $meta + [
                'key'         => $key,
                'unlocked'    => $unlocked->has($key),
                'unlocked_at' => $unlocked->get($key),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $m
     * @return array<string, bool>
     */
    private function conditions(array $m, int $alreadyUnlocked): array
    {
        return [
            'first_step'   => $m['workouts'] >= 1,
            'first_meal'   => $m['meals'] >= 1,
            'hydrated'     => $m['water_goal_days'] >= 1,
            'on_scale'     => $m['weight_logs'] >= 1,
            'first_pr'     => $m['weighted_sets'] >= 1,
            'own_routine'  => $m['templates'] >= 1,
            'three_a_day'  => $m['three_meal_days'] >= 1,
            'streak_3'     => $m['longest_streak'] >= 3,
            'workouts_10'  => $m['workouts'] >= 10,
            'supplemented' => $m['supplements_taken'] >= 1,

            'workouts_25'  => $m['workouts'] >= 25,
            'streak_7'     => $m['longest_streak'] >= 7,
            'swimmer'      => in_array('swimming', $m['modes'], true),
            'gym_rat'      => in_array('gym', $m['modes'], true),
            'home_trainer' => in_array('home', $m['modes'], true),
            'bodyweight'   => in_array('calisthenics', $m['modes'], true),
            'all_modes'    => count(array_intersect(['gym', 'home', 'calisthenics', 'swimming'], $m['modes'])) >= 4,
            'perfect_week' => $m['perfect_week'],
            'calories_7'   => $m['calorie_goal_days'] >= 7,
            'protein_14'   => $m['protein_goal_days'] >= 14,
            'water_30'     => $m['water_goal_days'] >= 30,
            'records_5'    => $m['records'] >= 5,

            'workouts_50'    => $m['workouts'] >= 50,
            'streak_30'      => $m['longest_streak'] >= 30,
            'volume_100k'    => $m['volume'] >= 100000,
            'pool_10km'      => $m['swim_metres'] >= 10000,
            'early_bird_20'  => $m['early_workouts'] >= 20,
            'night_owl_20'   => $m['late_workouts'] >= 20,
            'variety_30'     => $m['distinct_exercises'] >= 30,
            'chef_10'        => $m['recipes'] >= 10,
            'flawless_month' => $m['best_flawless_month'] >= 20,
            'rank_c'         => $m['level'] >= 25,

            'workouts_100'  => $m['workouts'] >= 100,
            'workouts_365'  => $m['workouts'] >= 365,
            'streak_100'    => $m['longest_streak'] >= 100,
            'year_complete' => $m['consecutive_active_months'] >= 12,
            'volume_1m'     => $m['volume'] >= 1000000,
            'rank_a'        => $m['level'] >= 45,
            'rank_s'        => $m['level'] >= 55,
            'collector'     => $alreadyUnlocked >= 39,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(User $user): array
    {
        $id = $user->id;

        $workouts = DB::table('workouts')
            ->where('user_id', $id)
            ->where('mode', '!=', 'pasos')
            ->get(['date', 'mode']);

        // ponytail: las métricas por hora, semana y mes se calculan en PHP en vez de en
        // SQL porque HOUR()/strftime() no son portables entre MySQL y el SQLite de los
        // tests, y el volumen de datos por usuario es de miles de filas, no de millones.
        $hours = $workouts->map(fn ($w) => Carbon::parse($w->date));

        $activeDates = $this->activeDates($user);

        $waterGoal = max(1, (int) ($user->water_goal_ml ?? 2000));
        $nutrition = DB::table('nutrition_goals')->where('user_id', $id)->first();

        $mealsByDay = DB::table('meal_logs')
            ->where('user_id', $id)
            ->selectRaw('date, COUNT(DISTINCT meal_type) as tipos, SUM(protein) as prot, SUM(calories) as kcal')
            ->groupBy('date')
            ->get();

        return [
            'workouts'  => $workouts->count(),
            'modes'     => $workouts->pluck('mode')->unique()->values()->all(),
            'meals'     => DB::table('meal_logs')->where('user_id', $id)->count(),
            'templates' => DB::table('templates')->where('user_id', $id)->count(),
            'recipes'   => DB::table('recipes')->where('user_id', $id)->count(),
            'weight_logs' => DB::table('weight_logs')->where('user_id', $id)->count(),
            'supplements_taken' => DB::table('supplement_logs')->where('user_id', $id)->where('taken', true)->count(),
            'records'   => DB::table('xp_events')->where('user_id', $id)->where('source', 'record')->count(),
            'level'     => (int) (DB::table('user_progress')->where('user_id', $id)->value('level') ?? 1),

            'volume' => (float) DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)
                ->sum(DB::raw('COALESCE(es.weight_kg, 0) * COALESCE(es.reps, 0) * COALESCE(es.sets, 1)')),

            'swim_metres' => (float) DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)->where('w.mode', 'swimming')
                ->sum(DB::raw('COALESCE(es.distance_m, 0)')),

            'distinct_exercises' => DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)
                ->distinct()->count('es.name'),

            'weighted_sets' => DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)->where('es.weight_kg', '>', 0)
                ->count(),

            'early_workouts' => $hours->filter(fn ($d) => $d->hour < 8)->count(),
            'late_workouts'  => $hours->filter(fn ($d) => $d->hour >= 22)->count(),

            'longest_streak' => $this->longestStreak($activeDates),
            'perfect_week'   => $this->hasPerfectWeek($activeDates),
            'consecutive_active_months' => $this->consecutiveActiveMonths($activeDates),

            'three_meal_days' => $mealsByDay->where('tipos', '>=', 3)->count(),
            'protein_goal_days' => $nutrition
                ? $mealsByDay->filter(fn ($d) => $d->prot >= $nutrition->target_protein)->count()
                : 0,
            'calorie_goal_days' => $nutrition
                ? $mealsByDay->filter(fn ($d) => abs($d->kcal - $nutrition->daily_calories) <= $nutrition->daily_calories * 0.10)->count()
                : 0,

            'water_goal_days' => DB::table('water_logs')
                ->where('user_id', $id)
                ->selectRaw('date, SUM(amount_ml) as ml')
                ->groupBy('date')
                ->havingRaw('SUM(amount_ml) >= ?', [$waterGoal])
                ->get()->count(),

            'best_flawless_month' => $this->bestFlawlessMonth($user),
        ];
    }

    /**
     * Días con cualquier tipo de actividad, en orden ascendente. Es la definición de
     * «día activo» que usan la racha del Sistema y todos los logros de racha.
     *
     * @return string[] fechas 'Y-m-d'
     */
    private function activeDates(User $user): array
    {
        $id = $user->id;

        $sets = [
            DB::table('workouts')->where('user_id', $id)->where('mode', '!=', 'pasos')->pluck('date'),
            DB::table('meal_logs')->where('user_id', $id)->pluck('date'),
            DB::table('water_logs')->where('user_id', $id)->pluck('date'),
            DB::table('supplement_logs')->where('user_id', $id)->where('taken', true)->pluck('date'),
            DB::table('weight_logs')->where('user_id', $id)->pluck('date'),
        ];

        $dates = collect($sets)->flatten()
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $dates;
    }

    /** @param string[] $dates */
    private function longestStreak(array $dates): int
    {
        $best = 0;
        $run = 0;
        $previous = null;

        foreach ($dates as $date) {
            $run = ($previous !== null && Carbon::parse($previous)->addDay()->toDateString() === $date)
                ? $run + 1
                : 1;

            $best = max($best, $run);
            $previous = $date;
        }

        return $best;
    }

    /** @param string[] $dates */
    private function hasPerfectWeek(array $dates): bool
    {
        return collect($dates)
            ->groupBy(fn ($d) => Carbon::parse($d)->format('o-W'))
            ->contains(fn ($week) => count($week) >= 7);
    }

    /** @param string[] $dates */
    private function consecutiveActiveMonths(array $dates): int
    {
        $months = collect($dates)->map(fn ($d) => Carbon::parse($d)->format('Y-m'))->unique()->sort()->values();

        $best = 0;
        $run = 0;
        $previous = null;

        foreach ($months as $month) {
            $run = ($previous !== null && Carbon::parse($previous.'-01')->addMonth()->format('Y-m') === $month)
                ? $run + 1
                : 1;

            $best = max($best, $run);
            $previous = $month;
        }

        return $best;
    }

    /**
     * Mejor mes natural por número de días con todas las misiones obligatorias hechas.
     */
    private function bestFlawlessMonth(User $user): int
    {
        $days = DB::table('daily_quests')
            ->where('user_id', $user->id)
            ->where('is_optional', false)
            ->selectRaw('date, COUNT(*) as total, SUM(CASE WHEN completed_at IS NULL THEN 0 ELSE 1 END) as hechas')
            ->groupBy('date')
            ->get()
            ->filter(fn ($d) => $d->total > 0 && $d->total === (int) $d->hechas);

        return $days
            ->groupBy(fn ($d) => Carbon::parse($d->date)->format('Y-m'))
            ->map(fn ($month) => count($month))
            ->max() ?? 0;
    }
}
```

- [ ] **Paso 4: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=AchievementServiceTest`
Esperado: PASS en los 6.

- [ ] **Paso 5: Commit**

```bash
git add backend/app/System/AchievementService.php backend/tests/Feature/AchievementServiceTest.php
git commit -m "feat(system): catálogo de 40 logros y evaluador"
```

---

## Tarea 11: Eventos, listener y bloque `system` en las respuestas

Aquí se junta todo. Los controladores existentes no cambian su contrato: disparan un
evento y añaden a la respuesta lo que el Sistema haya decidido.

**Ficheros:**
- Crear: `backend/app/Events/{WorkoutStored,MealLogged,WaterLogged,SupplementToggled,WeightLogged}.php`
- Crear: `backend/app/Listeners/UpdateSystemProgress.php`
- Crear: `backend/app/System/SystemService.php`
- Modificar: `backend/app/Providers/AppServiceProvider.php`,
  `backend/app/Http/Controllers/Api/{WorkoutController,MealLogController,WaterController,SupplementController,ProfileController}.php`
- Test: `backend/tests/Feature/SystemRewardsTest.php`

**Interfaces:**
- Consume: `XpLedger`, `QuestService`, `AchievementService`, `Progression`, `Stats`.
- Produce:
  - `SystemService::afterWorkout(Workout $workout, array $newRecords): array`
  - `SystemService::afterHabit(User $user, CarbonImmutable $date): array`
  - `SystemService::snapshot(User $user): array`
  - Todos devuelven el bloque `system`:
    ```
    ['xp_gained'=>int, 'level_up'=>['from'=>int,'to'=>int]|null, 'rank_up'=>['from'=>string,'to'=>string]|null,
     'achievements_unlocked'=>array, 'records'=>array, 'quests_completed'=>string[],
     'progress'=>['level'=>int,'rank'=>string,'xp_total'=>int,'xp_into_level'=>int,'xp_for_next'=>int,
                  'current_streak'=>int,'longest_streak'=>int,'stats'=>array]]
    ```
  - Cada evento expone `public array $rewards` que el listener rellena y el controlador lee.

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/SystemRewardsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRewardsTest extends TestCase
{
    use RefreshDatabase;

    private function entreno(array $override = []): array
    {
        return array_merge([
            'mode'             => 'gym',
            'date'             => '2026-08-10 18:00:00',
            'duration_minutes' => 45,
            'exercises'        => [[
                'name' => 'Press banca',
                'sets' => [['weight_kg' => 80, 'reps' => 5]],
            ]],
        ], $override);
    }

    public function test_guardar_un_entreno_devuelve_el_bloque_system_con_xp()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/workouts', $this->entreno());

        $response->assertCreated();

        // 50 de base + 6 por los 30 minutos por encima del mínimo
        // + 30 del récord + 2 del primer día de racha
        $this->assertSame(88, $response->json('system.xp_gained'));
        $this->assertSame('Press banca', $response->json('system.records.0.exercise'));
        $this->assertSame(1, $response->json('system.progress.level'));
        $this->assertSame('E', $response->json('system.progress.rank'));
    }

    public function test_un_entreno_de_menos_de_quince_minutos_no_da_xp_de_entreno()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/workouts', $this->entreno(['duration_minutes' => 10, 'exercises' => []]));

        $this->assertDatabaseMissing('xp_events', ['user_id' => $user->id, 'source' => 'workout']);

        // Lo único que gana es el punto de racha por haber tenido actividad hoy
        $this->assertSame(2, $response->json('system.xp_gained'));
    }

    public function test_el_tercer_entreno_del_dia_no_puntua()
    {
        $user = User::factory()->create();

        // 58 el primero (56 + 2 de racha), 56 el segundo, 0 el tercero
        $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));
        $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));
        $tercero = $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));

        $tercero->assertCreated();
        $this->assertSame(0, $tercero->json('system.xp_gained'), 'el entreno se guarda pero no da XP');
        $this->assertDatabaseCount('workouts', 3);
    }

    public function test_subir_de_nivel_se_anuncia_en_la_respuesta()
    {
        $user = User::factory()->create();

        // Primer entreno: 56 + 2 de racha. Segundo: 56 más → 114, pasa el umbral de 100.
        $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));
        $segundo = $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));

        $this->assertSame(['from' => 1, 'to' => 2], $segundo->json('system.level_up'));
    }

    public function test_registrar_agua_devuelve_tambien_el_bloque_system()
    {
        $user = User::factory()->create(['water_goal_ml' => 500]);

        // La primera lectura del día genera las misiones
        $this->actingAs($user)->getJson('/api/system/today');

        $response = $this->actingAs($user)->postJson('/api/water', ['amount_ml' => 500]);

        $response->assertCreated();
        $this->assertContains('water', $response->json('system.quests_completed'));
    }

    public function test_la_racha_sube_con_dias_seguidos_de_actividad()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/workouts', $this->entreno([
            'date' => '2026-08-10 18:00:00', 'exercises' => [],
        ]));
        $segundo = $this->actingAs($user)->postJson('/api/workouts', $this->entreno([
            'date' => '2026-08-11 18:00:00', 'exercises' => [],
        ]));

        $this->assertSame(2, $segundo->json('system.progress.current_streak'));
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=SystemRewardsTest`
Esperado: FALLA — la respuesta no tiene la clave `system`.

- [ ] **Paso 3: Escribir los cinco eventos**

Crear `backend/app/Events/WorkoutStored.php`:

```php
<?php

namespace App\Events;

use App\Models\Workout;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * El módulo de entrenamiento avisa de que se ha guardado un entreno. No sabe qué
 * hará el Sistema con esto. `$rewards` lo rellena el listener y lo lee el controlador
 * para devolverlo en la misma respuesta, sin una segunda llamada.
 */
class WorkoutStored
{
    use Dispatchable;

    public array $rewards = [];

    public function __construct(
        public Workout $workout,
        public array $newRecords = [],
    ) {}
}
```

Crear `backend/app/Events/MealLogged.php`, `WaterLogged.php`, `SupplementToggled.php` y
`WeightLogged.php`, los cuatro con la misma forma (cambiando solo el nombre de la clase):

```php
<?php

namespace App\Events;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

class MealLogged
{
    use Dispatchable;

    public array $rewards = [];

    public function __construct(
        public User $user,
        public CarbonImmutable $date,
    ) {}
}
```

- [ ] **Paso 4: Escribir `SystemService`**

Crear `backend/app/System/SystemService.php`:

```php
<?php

namespace App\System;

use App\Models\User;
use App\Models\UserProgress;
use App\Models\Workout;
use Carbon\CarbonImmutable;

/**
 * El Sistema. Recibe lo que ha pasado en un módulo y devuelve lo que ha ganado el
 * usuario. Es el único que conoce a la vez el libro mayor, las misiones y los logros;
 * los módulos no conocen a ninguno.
 */
class SystemService
{
    public function __construct(
        private XpLedger $ledger,
        private QuestService $quests,
        private AchievementService $achievements,
    ) {}

    public function afterWorkout(Workout $workout, array $newRecords = []): array
    {
        $user = $workout->user()->firstOrFail();
        $date = CarbonImmutable::parse($workout->date);
        $before = $this->snapshotOf($this->ledger->progressFor($user));

        // Los pasos son actividad diaria, no entrenamiento: no puntúan.
        if ($workout->mode !== 'pasos') {
            $cap = (int) config('srank.xp.workouts_per_day_cap');

            if ($this->ledger->countSource($user, 'workout', $date) < $cap) {
                $this->ledger->award($user, 'workout', $workout->id, $this->workoutXp($workout), $date);

                foreach ($newRecords as $record) {
                    $this->ledger->award($user, 'record', $workout->id, config('srank.xp.record'), $date);
                }

                $progress = $this->ledger->progressFor($user);
                $progress->strength_acc += $this->volumeOf($workout);
                $progress->endurance_acc += $workout->duration_minutes;
                $progress->save();
            }
        }

        return $this->close($user, $date, $before, $this->formatRecords($newRecords));
    }

    public function afterHabit(User $user, CarbonImmutable $date): array
    {
        $before = $this->snapshotOf($this->ledger->progressFor($user));

        return $this->close($user, $date, $before, []);
    }

    /**
     * Estado actual sin conceder nada. Lo usan los GET.
     */
    public function snapshot(User $user): array
    {
        return $this->snapshotOf($this->ledger->progressFor($user));
    }

    /**
     * Cierra el ciclo: racha, misiones, logros y comparación con el estado anterior.
     */
    private function close(User $user, CarbonImmutable $date, array $before, array $records): array
    {
        $this->touchStreak($user, $date);

        $quests = $this->quests->sync($user, $date);

        // Constancia: un punto por misión cumplida. Vitalidad: solo las de hábito.
        $habitKeys = ['water', 'protein', 'weight', 'meals_3', 'supplements'];
        $progress = $this->ledger->progressFor($user);
        $progress->consistency_acc += count($quests['completed']);
        $progress->vitality_acc += count(array_intersect($quests['completed'], $habitKeys));
        $progress->save();

        $unlocked = $this->achievements->evaluate($user);
        $after = $this->snapshotOf($this->ledger->progressFor($user));

        return [
            'xp_gained'             => $after['xp_total'] - $before['xp_total'],
            'level_up'              => $after['level'] !== $before['level']
                                        ? ['from' => $before['level'], 'to' => $after['level']]
                                        : null,
            'rank_up'               => $after['rank'] !== $before['rank']
                                        ? ['from' => $before['rank'], 'to' => $after['rank']]
                                        : null,
            'achievements_unlocked' => $unlocked,
            'records'               => $records,
            'quests_completed'      => $quests['completed'],
            'progress'              => $after,
        ];
    }

    /**
     * Marca el día como activo y actualiza la racha. Un día activo suma un punto de
     * Constancia; volver tras un hueco reinicia la racha a uno.
     */
    private function touchStreak(User $user, CarbonImmutable $date): void
    {
        $progress = $this->ledger->progressFor($user);
        $today = $date->toDateString();
        $last = $progress->last_active_date?->toDateString();

        if ($last === $today) {
            return;
        }

        $progress->current_streak = ($last !== null && CarbonImmutable::parse($last)->addDay()->toDateString() === $today)
            ? $progress->current_streak + 1
            : 1;

        $progress->longest_streak = max($progress->longest_streak, $progress->current_streak);
        $progress->last_active_date = $today;
        $progress->consistency_acc += 1;
        $progress->save();

        // Bonus de racha: +2 por día consecutivo, hasta 30. Una vez al día.
        $bonus = min(
            config('srank.xp.streak_per_day') * $progress->current_streak,
            config('srank.xp.streak_cap')
        );

        $this->ledger->award($user, 'streak', null, (int) $bonus, $date);
    }

    private function workoutXp(Workout $workout): int
    {
        $xp = config('srank.xp');
        $minutes = (int) $workout->duration_minutes;

        if ($minutes < $xp['workout_min_minutes']) {
            return 0;
        }

        $bonus = intdiv($minutes - $xp['workout_min_minutes'], $xp['workout_bonus_step']);

        return $xp['workout_base'] + min($bonus, $xp['workout_bonus_cap']);
    }

    private function volumeOf(Workout $workout): float
    {
        return (float) $workout->sets()->get()->sum(
            fn ($set) => (float) ($set->weight_kg ?? 0) * (int) ($set->reps ?? 0) * max(1, (int) ($set->sets ?? 1))
        );
    }

    private function formatRecords(array $newRecords): array
    {
        return array_map(fn (array $r) => [
            'exercise' => $r['name'],
            'kind'     => 'weight',
            'value'    => $r['weight_kg'],
            'previous' => $r['previous_pr'] ?? null,
        ], $newRecords);
    }

    private function snapshotOf(UserProgress $progress): array
    {
        $bar = Progression::levelBar($progress->xp_total);

        return [
            'level'          => Progression::levelForXp($progress->xp_total),
            'rank'           => Progression::rankForLevel(Progression::levelForXp($progress->xp_total)),
            'xp_total'       => $progress->xp_total,
            'xp_into_level'  => $bar['into_level'],
            'xp_for_next'    => $bar['for_next'],
            'current_streak' => $progress->current_streak,
            'longest_streak' => $progress->longest_streak,
            'stats'          => Stats::all($progress),
        ];
    }
}
```

- [ ] **Paso 5: Escribir el listener**

Crear `backend/app/Listeners/UpdateSystemProgress.php`:

```php
<?php

namespace App\Listeners;

use App\Events\MealLogged;
use App\Events\SupplementToggled;
use App\Events\WaterLogged;
use App\Events\WeightLogged;
use App\Events\WorkoutStored;
use App\System\SystemService;

/**
 * El único puente entre los módulos y el Sistema. Si mañana aparece un módulo nuevo,
 * publica su evento y se añade aquí una línea: el núcleo no se toca.
 */
class UpdateSystemProgress
{
    public function __construct(private SystemService $system) {}

    public function handleWorkout(WorkoutStored $event): void
    {
        $event->rewards = $this->system->afterWorkout($event->workout, $event->newRecords);
    }

    public function handleHabit(MealLogged|WaterLogged|SupplementToggled|WeightLogged $event): void
    {
        $event->rewards = $this->system->afterHabit($event->user, $event->date);
    }
}
```

- [ ] **Paso 6: Registrar los listeners**

En `backend/app/Providers/AppServiceProvider.php`, dentro de `boot()`:

```php
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\WorkoutStored::class,
            [\App\Listeners\UpdateSystemProgress::class, 'handleWorkout']
        );

        foreach ([
            \App\Events\MealLogged::class,
            \App\Events\WaterLogged::class,
            \App\Events\SupplementToggled::class,
            \App\Events\WeightLogged::class,
        ] as $event) {
            \Illuminate\Support\Facades\Event::listen(
                $event,
                [\App\Listeners\UpdateSystemProgress::class, 'handleHabit']
            );
        }
```

- [ ] **Paso 7: Disparar el evento desde `WorkoutController::store`**

En `backend/app/Http/Controllers/Api/WorkoutController.php`, sustituir el final del
`return DB::transaction(...)` (las tres últimas líneas del closure) por:

```php
            $data                = $workout->load('sets')->toArray();
            $data['new_records'] = $newRecords;

            $event = new \App\Events\WorkoutStored($workout, $newRecords);
            event($event);
            $data['system'] = $event->rewards;

            return response()->json($data, 201);
```

- [ ] **Paso 8: Disparar el evento desde los otros cuatro controladores**

En `WaterController::store`, antes del `return`:

```php
        $event = new \App\Events\WaterLogged($user, \Carbon\CarbonImmutable::parse($log->date));
        event($event);
```

y añadir `'system' => $event->rewards,` al array de la respuesta.

En `MealLogController::store`, antes del `return`:

```php
        $event = new \App\Events\MealLogged($request->user(), \Carbon\CarbonImmutable::parse($log->date));
        event($event);
```

y añadir `'system' => $event->rewards,` a la respuesta.

En `SupplementController::upsert`, antes del `return`:

```php
        $event = new \App\Events\SupplementToggled($request->user(), \Carbon\CarbonImmutable::parse($validated['date']));
        event($event);

        return response()->json(['message' => 'Suplemento actualizado.', 'system' => $event->rewards]);
```

En `ProfileController::update`, dentro del `if` que registra el peso, después del
`WeightLog::updateOrCreate(...)`:

```php
            $event = new \App\Events\WeightLogged($user, \Carbon\CarbonImmutable::today());
            event($event);
            $system = $event->rewards;
```

e incluir `'system' => $system ?? null` en la respuesta.

- [ ] **Paso 9: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=SystemRewardsTest`
Esperado: PASS en los 6.

- [ ] **Paso 10: Ejecutar la suite entera para comprobar que no se ha roto nada**

Ejecutar: `cd backend && php artisan test`
Esperado: PASS. Los tests antiguos de `WorkoutTest`, `MealLogTest` y compañía siguen
valiendo porque el contrato de las respuestas no ha cambiado: solo se ha añadido una clave.

- [ ] **Paso 11: Commit**

```bash
git add backend/app/Events backend/app/Listeners backend/app/System/SystemService.php \
        backend/app/Providers backend/app/Http/Controllers/Api backend/tests/Feature/SystemRewardsTest.php
git commit -m "feat(system): eventos, listener y bloque system en las respuestas"
```

---

## Tarea 12: Endpoints `GET /api/system/*`

**Ficheros:**
- Crear: `backend/app/Http/Controllers/Api/SystemController.php`
- Modificar: `backend/routes/api.php`
- Test: `backend/tests/Feature/SystemEndpointsTest.php`

**Interfaces:**
- Consume: `SystemService`, `QuestService`, `AchievementService`.
- Produce cuatro rutas autenticadas:
  - `GET /api/system/today`
  - `GET /api/system/profile`
  - `GET /api/system/achievements`
  - `POST /api/system/quests/{key}/complete`

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/SystemEndpointsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_genera_las_misiones_la_primera_vez_y_no_las_duplica()
    {
        $user = User::factory()->create();

        $primera = $this->actingAs($user)->getJson('/api/system/today')->assertOk();
        $claves = collect($primera->json('quests'))->pluck('key')->sort()->values();

        $segunda = $this->actingAs($user)->getJson('/api/system/today')->assertOk();

        $this->assertEquals($claves, collect($segunda->json('quests'))->pluck('key')->sort()->values());
        $this->assertSame(count($claves), \App\Models\DailyQuest::where('user_id', $user->id)->count());
    }

    public function test_today_devuelve_progreso_y_misiones_con_texto_en_castellano()
    {
        $user = User::factory()->create(['water_goal_ml' => 2000]);

        $response = $this->actingAs($user)->getJson('/api/system/today');

        $response->assertOk()
            ->assertJsonPath('progress.level', 1)
            ->assertJsonPath('progress.rank', 'E');

        $agua = collect($response->json('quests'))->firstWhere('key', 'water');
        $this->assertSame('Beber 2 litros de agua', $agua['label']);
    }

    public function test_profile_devuelve_las_cuatro_estadisticas_y_los_modulos()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/system/profile')->assertOk();

        $this->assertSame(
            ['consistency', 'endurance', 'strength', 'vitality'],
            collect($response->json('progress.stats'))->keys()->sort()->values()->all()
        );
        $this->assertSame(['entrenamiento', 'nutrición'], $response->json('modules'));
    }

    public function test_achievements_devuelve_los_cuarenta_con_su_rareza()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/system/achievements')->assertOk();

        $this->assertCount(40, $response->json('achievements'));
        $this->assertSame(0, $response->json('unlocked_count'));
    }

    public function test_se_puede_marcar_a_mano_la_mision_opcional()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/system/today');

        $opcional = \App\Models\DailyQuest::where('user_id', $user->id)->where('is_optional', true)->first();

        $this->actingAs($user)
            ->postJson("/api/system/quests/{$opcional->quest_key}/complete")
            ->assertOk()
            ->assertJsonPath('system.quests_completed', []);

        $this->assertNotNull($opcional->fresh()->completed_at);
    }

    public function test_no_se_puede_marcar_a_mano_una_mision_obligatoria()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/system/today');

        $this->actingAs($user)
            ->postJson('/api/system/quests/water/complete')
            ->assertStatus(422);
    }

    public function test_los_endpoints_del_sistema_exigen_autenticacion()
    {
        $this->getJson('/api/system/today')->assertUnauthorized();
        $this->getJson('/api/system/profile')->assertUnauthorized();
        $this->getJson('/api/system/achievements')->assertUnauthorized();
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=SystemEndpointsTest`
Esperado: FALLA con 404 en todas las rutas.

- [ ] **Paso 3: Escribir el controlador**

Crear `backend/app/Http/Controllers/Api/SystemController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\Workout;
use App\System\AchievementService;
use App\System\QuestService;
use App\System\SystemService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function __construct(
        private SystemService $system,
        private QuestService $quests,
        private AchievementService $achievements,
    ) {}

    /**
     * Progreso + misiones del día + entreno sugerido.
     *
     * Genera las misiones si aún no existen. Es idempotente, así que no hace falta
     * cron: la primera petición de cada día las crea y las siguientes las leen.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $this->today_date();

        $this->quests->generate($user, $date);
        $this->quests->sync($user, $date);

        return response()->json([
            'date'              => $date->toDateString(),
            'progress'          => $this->system->snapshot($user),
            'quests'            => $this->quests->forDate($user, $date),
            'suggested_workout' => $this->suggestedWorkout($user, $date),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'progress' => $this->system->snapshot($request->user()),
            'modules'  => config('srank.modules'),
        ]);
    }

    public function achievements(Request $request): JsonResponse
    {
        $list = $this->achievements->listFor($request->user());

        return response()->json([
            'achievements'   => $list,
            'unlocked_count' => count(array_filter($list, fn ($a) => $a['unlocked'])),
            'total_count'    => count($list),
        ]);
    }

    /**
     * Marca a mano una misión opcional. Las opcionales no se pueden medir solas.
     */
    public function completeQuest(Request $request, string $key): JsonResponse
    {
        $user = $request->user();
        $date = $this->today_date();

        if (! $this->quests->completeOptional($user, $key, $date)) {
            return response()->json([
                'message' => 'Esa misión no se puede marcar a mano o ya está hecha.',
            ], 422);
        }

        return response()->json([
            'system' => $this->system->afterHabit($user, $date),
            'quests' => $this->quests->forDate($user, $date),
        ]);
    }

    private function today_date(): CarbonImmutable
    {
        return CarbonImmutable::now(config('srank.timezone'))->startOfDay();
    }

    /**
     * Qué entrenar hoy: una plantilla del modo en el que más ha entrenado
     * últimamente, con el motivo escrito en español llano.
     */
    private function suggestedWorkout($user, CarbonImmutable $date): array
    {
        $goal = max(1, (int) $user->weekly_goal);

        $done = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->whereBetween('date', [
                $date->startOfWeek()->startOfDay()->toDateTimeString(),
                $date->endOfWeek()->endOfDay()->toDateTimeString(),
            ])
            ->count();

        $favouriteMode = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->where('date', '>=', $date->subDays(30)->toDateTimeString())
            ->selectRaw('mode, COUNT(*) as total')
            ->groupBy('mode')
            ->orderByDesc('total')
            ->value('mode');

        $template = Template::where('user_id', $user->id)
            ->when($favouriteMode, fn ($q) => $q->where('mode', $favouriteMode))
            ->latest('id')
            ->first();

        $pending = max(0, $goal - $done);

        return [
            'reason' => $pending === 0
                ? 'Ya has cumplido tu meta de esta semana.'
                : ($pending === 1
                    ? 'Te falta 1 entreno para tu meta de esta semana.'
                    : "Te faltan {$pending} entrenos para tu meta de esta semana."),
            'weekly_done'  => $done,
            'weekly_goal'  => $goal,
            'template'     => $template,
        ];
    }
}
```

- [ ] **Paso 4: Añadir las rutas**

En `backend/routes/api.php`, dentro del grupo `Route::middleware('auth:sanctum')`, después
de la línea de `/achievements`:

```php
    // ── El Sistema ─────────────────────────────────────────────────────────
    Route::get('/system/today', [SystemController::class, 'today']);
    Route::get('/system/profile', [SystemController::class, 'profile']);
    Route::get('/system/achievements', [SystemController::class, 'achievements']);
    Route::post('/system/quests/{key}/complete', [SystemController::class, 'completeQuest']);
```

y añadir el `use` arriba:

```php
use App\Http\Controllers\Api\SystemController;
```

- [ ] **Paso 5: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=SystemEndpointsTest`
Esperado: PASS en los 7.

- [ ] **Paso 6: Commit**

```bash
git add backend/app/Http/Controllers/Api/SystemController.php backend/routes/api.php backend/tests/Feature/SystemEndpointsTest.php
git commit -m "feat(api): endpoints del Sistema"
```

---

## Tarea 13: Alta de cuenta y recuperación de contraseña

Arreglo bloqueante 4 (§5.1). Con cada usuario en su propio móvil, no puede hacer falta que
un administrador cree las cuentas a mano ni que nadie pierda el acceso para siempre.

**Ficheros:**
- Modificar: `backend/app/Http/Controllers/Api/AuthController.php`, `backend/routes/api.php`
- Crear: `backend/app/Notifications/ResetPasswordCode.php`
- Test: `backend/tests/Feature/RegisterAndResetTest.php`

**Interfaces:**
- Produce:
  - `POST /api/auth/register` `{name, email, password}` → `201 {access_token, token_type, user_name, is_admin}`
  - `POST /api/auth/forgot-password` `{email}` → `200` siempre
  - `POST /api/auth/reset-password` `{email, code, password}` → `200` o `422`

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/RegisterAndResetTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterAndResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_cualquiera_puede_darse_de_alta_y_recibe_un_token()
    {
        $response = $this->postJson('/api/auth/register', [
            'name'     => 'Slavka',
            'email'    => 'slavka@example.com',
            'password' => 'contrasena123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'user_name', 'is_admin']);

        $this->assertDatabaseHas('users', ['email' => 'slavka@example.com', 'is_admin' => false]);
    }

    public function test_no_se_puede_repetir_el_correo()
    {
        User::factory()->create(['email' => 'isra@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Otro', 'email' => 'isra@example.com', 'password' => 'contrasena123',
        ])->assertStatus(422);
    }

    public function test_la_contrasena_tiene_un_minimo()
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Corto', 'email' => 'corto@example.com', 'password' => '1234',
        ])->assertStatus(422);
    }

    public function test_pedir_el_codigo_envia_un_correo_y_no_revela_si_el_usuario_existe()
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'isra@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com'])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'nadie@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPasswordCode::class);
        Notification::assertCount(1);
    }

    public function test_el_codigo_correcto_cambia_la_contrasena_y_cierra_las_sesiones()
    {
        $user = User::factory()->create(['email' => 'isra@example.com']);
        $user->createToken('viejo');

        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com'])->assertOk();

        $code = \App\Http\Controllers\Api\AuthController::$lastCodeForTesting;

        $this->postJson('/api/auth/reset-password', [
            'email' => 'isra@example.com', 'code' => $code, 'password' => 'nuevacontrasena',
        ])->assertOk();

        $this->assertTrue(Hash::check('nuevacontrasena', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'isra@example.com']);
    }

    public function test_un_codigo_equivocado_no_cambia_nada()
    {
        $user = User::factory()->create(['email' => 'isra@example.com']);
        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'isra@example.com', 'code' => '000000', 'password' => 'nuevacontrasena',
        ])->assertStatus(422);

        $this->assertFalse(Hash::check('nuevacontrasena', $user->fresh()->password));
    }

    public function test_un_codigo_caducado_no_vale()
    {
        $user = User::factory()->create(['email' => 'isra@example.com']);
        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com']);
        $code = \App\Http\Controllers\Api\AuthController::$lastCodeForTesting;

        $this->travel(31)->minutes();

        $this->postJson('/api/auth/reset-password', [
            'email' => 'isra@example.com', 'code' => $code, 'password' => 'nuevacontrasena',
        ])->assertStatus(422);
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=RegisterAndResetTest`
Esperado: FALLA con 404 en `/api/auth/register`.

- [ ] **Paso 3: Escribir la notificación con el código**

Crear `backend/app/Notifications/ResetPasswordCode.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Un código de seis cifras, no un enlace: la app es Android y un enlace obligaría a
 * mantener una página web solo para esto.
 */
class ResetPasswordCode extends Notification
{
    public function __construct(private string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Tu código para cambiar la contraseña')
            ->greeting('Hola')
            ->line('Este es tu código para cambiar la contraseña en S-RANK:')
            ->line('**'.$this->code.'**')
            ->line('Caduca en 30 minutos.')
            ->line('Si no lo has pedido tú, no hace falta que hagas nada: tu contraseña sigue igual.')
            ->salutation('S-RANK');
    }
}
```

- [ ] **Paso 4: Ampliar `AuthController`**

En `backend/app/Http/Controllers/Api/AuthController.php`, añadir los `use` que faltan y
los tres métodos nuevos:

```php
use App\Notifications\ResetPasswordCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
```

```php
    /**
     * Último código generado. Solo lo lee la suite de tests: en producción el código
     * viaja por correo y nunca sale en ninguna respuesta.
     */
    public static ?string $lastCodeForTesting = null;

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:60',
            'email'    => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:200',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'is_admin'    => false,
            'weekly_goal' => 3,
            'main_goal'   => 'health',
        ]);

        return response()->json([
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type'   => 'Bearer',
            'user_name'    => $user->name,
            'is_admin'     => false,
        ], 201);
    }

    /**
     * Manda un código de seis cifras. Responde 200 exista o no el correo: decir
     * «ese usuario no existe» es regalar una lista de cuentas válidas.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            self::$lastCodeForTesting = $code;

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            $user->notify(new ResetPasswordCode($code));
        }

        return response()->json([
            'message' => 'Si ese correo está registrado, te hemos enviado un código.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'code'     => 'required|string|size:6',
            'password' => 'required|string|min:8|max:200',
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();
        $user = User::where('email', $validated['email'])->first();

        $valid = $row
            && $user
            && \Illuminate\Support\Carbon::parse($row->created_at)->addMinutes(30)->isFuture()
            && Hash::check($validated['code'], $row->token);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['El código no es válido o ha caducado.'],
            ]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json(['message' => 'Contraseña cambiada. Ya puedes entrar.']);
    }
```

- [ ] **Paso 5: Abrir las rutas con límite de intentos**

En `backend/routes/api.php`, sustituir el grupo `auth` por:

```php
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,60');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,60');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,60');
});
```

`throttle:3,60` en el alta: tres cuentas por hora y por IP. Es una app familiar, no un
servicio público; sin ese límite, un formulario de registro abierto en un hosting
compartido es una invitación a llenar la base de datos de basura.

- [ ] **Paso 6: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=RegisterAndResetTest`
Esperado: PASS en los 7.

- [ ] **Paso 7: Commit**

```bash
git add backend/app/Http/Controllers/Api/AuthController.php backend/app/Notifications \
        backend/routes/api.php backend/tests/Feature/RegisterAndResetTest.php
git commit -m "feat(auth): alta de cuenta y recuperación de contraseña por código"
```

---

## Tarea 14: Recálculo del progreso histórico

Con 18 entrenos ya registrados, al abrir S-RANK por primera vez el usuario tiene que estar
en el nivel que le corresponde, no en el 1.

**Ficheros:**
- Crear: `backend/app/Console/Commands/RecalculateProgress.php`
- Test: `backend/tests/Feature/RecalculateProgressTest.php`

**Interfaces:**
- Produce: `php artisan srank:recalculate {--user=}` — idempotente, se puede repetir entero
  si cambia el balanceo.

- [ ] **Paso 1: Escribir el test que falla**

Crear `backend/tests/Feature/RecalculateProgressTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateProgressTest extends TestCase
{
    use RefreshDatabase;

    private function entreno(User $user, string $date, int $minutes = 45, string $mode = 'gym'): Workout
    {
        return Workout::create([
            'user_id' => $user->id, 'mode' => $mode,
            'date' => $date, 'duration_minutes' => $minutes,
        ]);
    }

    public function test_reconstruye_el_xp_de_los_entrenos_ya_registrados()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');   // 56 XP + 2 de racha
        $this->entreno($user, '2026-08-02 18:00:00');   // 56 XP + 4 de racha

        $this->artisan('srank:recalculate')->assertSuccessful();

        $this->assertSame(118, $user->progress->fresh()->xp_total);
        $this->assertSame(2, $user->progress->fresh()->longest_streak);
    }

    public function test_es_idempotente()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');

        $this->artisan('srank:recalculate');
        $primera = $user->progress->fresh()->xp_total;

        $this->artisan('srank:recalculate');

        $this->assertSame($primera, $user->progress->fresh()->xp_total);
        $this->assertSame(2, \App\Models\XpEvent::where('user_id', $user->id)->count());
    }

    public function test_respeta_el_tope_de_dos_entrenos_al_dia()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 08:00:00');
        $this->entreno($user, '2026-08-01 13:00:00');
        $this->entreno($user, '2026-08-01 20:00:00');

        $this->artisan('srank:recalculate');

        $this->assertSame(2, \App\Models\XpEvent::where('user_id', $user->id)
            ->where('source', 'workout')->count());
    }

    public function test_avisa_de_cuantos_entrenos_no_aportaron_kilos()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');

        $this->artisan('srank:recalculate')
            ->expectsOutputToContain('sin detalle de series')
            ->assertSuccessful();
    }

    public function test_desbloquea_los_logros_que_correspondan()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');

        $this->artisan('srank:recalculate');

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id, 'achievement_key' => 'first_step',
        ]);
    }
}
```

- [ ] **Paso 2: Ejecutar y verificar que falla**

Ejecutar: `cd backend && php artisan test --filter=RecalculateProgressTest`
Esperado: FALLA — «The command "srank:recalculate" does not exist».

- [ ] **Paso 3: Escribir el comando**

Crear `backend/app/Console/Commands/RecalculateProgress.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserProgress;
use App\Models\Workout;
use App\Models\XpEvent;
use App\System\AchievementService;
use App\System\Progression;
use App\System\Stats;
use App\System\XpLedger;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconstruye el progreso desde los datos que ya existen, reproduciendo el libro
 * mayor día a día. Idempotente: borra lo que él mismo escribió y lo vuelve a hacer.
 *
 * Lo que NO reconstruye, y por qué:
 *   - Las misiones diarias, porque nunca existieron: no hay de dónde sacarlas.
 *   - Vitalidad, que se alimenta de misiones de hábito cumplidas.
 *   - Fuerza queda casi a cero: el almacenamiento por serie se añadió muy tarde a
 *     FitLoop y los entrenos antiguos no guardaron ni peso ni repeticiones.
 */
class RecalculateProgress extends Command
{
    protected $signature = 'srank:recalculate {--user= : Solo este usuario (uuid)}';
    protected $description = 'Reconstruye XP, nivel, estadísticas y logros desde el historial';

    public function __construct(private XpLedger $ledger, private AchievementService $achievements)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $users = User::query()
            ->when($this->option('user'), fn ($q) => $q->where('id', $this->option('user')))
            ->get();

        foreach ($users as $user) {
            $this->recalculateFor($user);
        }

        return self::SUCCESS;
    }

    private function recalculateFor(User $user): void
    {
        DB::transaction(function () use ($user) {
            XpEvent::where('user_id', $user->id)->delete();
            UserAchievement::where('user_id', $user->id)->delete();

            $progress = UserProgress::firstOrCreate(['user_id' => $user->id]);
            $progress->fill([
                'level' => 1, 'xp_total' => 0,
                'strength_acc' => 0, 'endurance_acc' => 0,
                'consistency_acc' => 0, 'vitality_acc' => 0,
                'current_streak' => 0, 'longest_streak' => 0, 'last_active_date' => null,
            ])->save();

            $workouts = Workout::where('user_id', $user->id)
                ->where('mode', '!=', 'pasos')
                ->with('sets')
                ->orderBy('date')
                ->get();

            $bestByExercise = [];      // récords, recalculados en orden cronológico
            $perDay = [];              // entrenos que ya han puntuado en cada día
            $withoutKg = 0;

            foreach ($workouts as $workout) {
                $date = CarbonImmutable::parse($workout->date)->startOfDay();
                $day = $date->toDateString();
                $perDay[$day] = ($perDay[$day] ?? 0);

                $volume = (float) $workout->sets->sum(
                    fn ($s) => (float) ($s->weight_kg ?? 0) * (int) ($s->reps ?? 0) * max(1, (int) ($s->sets ?? 1))
                );

                if ($volume <= 0) {
                    $withoutKg++;
                }

                // Récords: se detectan siempre, aunque el entreno ya no puntúe
                $records = 0;

                foreach ($workout->sets->groupBy('name') as $name => $sets) {
                    $best = (float) $sets->max('weight_kg');

                    if ($best > 0 && $best > ($bestByExercise[$name] ?? 0)) {
                        $bestByExercise[$name] = $best;
                        $records++;
                    }
                }

                $this->touchStreak($progress, $date);

                if ($perDay[$day] >= (int) config('srank.xp.workouts_per_day_cap')) {
                    continue;
                }

                $perDay[$day]++;

                $this->ledger->award($user, 'workout', $workout->id, $this->workoutXp($workout), $date);

                for ($i = 0; $i < $records; $i++) {
                    $this->ledger->award($user, 'record', $workout->id, config('srank.xp.record'), $date);
                }

                $progress->refresh();
                $progress->strength_acc += $volume;
                $progress->endurance_acc += $workout->duration_minutes;
                $progress->save();
            }

            $progress->refresh();
            $progress->level = Progression::levelForXp($progress->xp_total);
            $progress->save();

            $this->achievements->evaluate($user);

            $stats = Stats::all($progress);

            $this->info("Usuario {$user->email}");
            $this->line("  XP {$progress->xp_total} · nivel {$progress->level} · rango "
                .Progression::rankForLevel($progress->level));
            $this->line("  Fuerza {$stats['strength']} · Resistencia {$stats['endurance']}"
                ." · Constancia {$stats['consistency']} · Vitalidad {$stats['vitality']}");

            if ($withoutKg > 0) {
                $this->warn("  {$withoutKg} de {$workouts->count()} entrenos sin detalle de series: "
                    .'no aportan kilos, así que Fuerza arranca baja. No es un fallo, '
                    .'es que esos datos nunca se guardaron.');
            }
        });
    }

    private function touchStreak(UserProgress $progress, CarbonImmutable $date): void
    {
        $today = $date->toDateString();
        $last = $progress->last_active_date?->toDateString();

        if ($last === $today) {
            return;
        }

        $progress->current_streak = ($last !== null && CarbonImmutable::parse($last)->addDay()->toDateString() === $today)
            ? $progress->current_streak + 1
            : 1;

        $progress->longest_streak = max($progress->longest_streak, $progress->current_streak);
        $progress->last_active_date = $today;
        $progress->consistency_acc += 1;
        $progress->save();

        $bonus = min(
            config('srank.xp.streak_per_day') * $progress->current_streak,
            config('srank.xp.streak_cap')
        );

        $this->ledger->award($progress->user, 'streak', null, (int) $bonus, $date);
        $progress->refresh();
    }

    private function workoutXp(Workout $workout): int
    {
        $xp = config('srank.xp');
        $minutes = (int) $workout->duration_minutes;

        if ($minutes < $xp['workout_min_minutes']) {
            return 0;
        }

        return $xp['workout_base'] + min(
            intdiv($minutes - $xp['workout_min_minutes'], $xp['workout_bonus_step']),
            $xp['workout_bonus_cap']
        );
    }
}
```

- [ ] **Paso 4: Ejecutar el test — ahora pasa**

Ejecutar: `cd backend && php artisan test --filter=RecalculateProgressTest`
Esperado: PASS en los 5.

- [ ] **Paso 5: Ejecutarlo contra los datos reales**

```bash
cd backend && php artisan srank:recalculate
```

Esperado: una línea por usuario con su XP, nivel, rango y estadísticas, y el aviso de
cuántos de los 18 entrenos no aportaron kilos (deberían ser 15 o 16, ya que solo hay 7
series registradas).

- [ ] **Paso 6: Ejecutar la suite entera**

Ejecutar: `cd backend && php artisan test`
Esperado: PASS.

- [ ] **Paso 7: Commit**

```bash
git add backend/app/Console/Commands/RecalculateProgress.php backend/tests/Feature/RecalculateProgressTest.php
git commit -m "feat(system): recálculo del progreso histórico"
```

---

## Tarea 15: Despliegue a Ginernet

Esta tarea tiene pasos manuales en el panel de Ginernet y en FileZilla. Se hace entera de
una sentada porque hasta el último paso no se sabe si funciona.

**Ficheros:**
- Modificar: `backend/.env.produccion` (fuera de git)
- Crear: `docs/superpowers/plans/despliegue-fase-1-0.md` (registro de lo que se hizo)

- [ ] **Paso 1: Crear el subdominio y la base de datos en el panel**

En el panel de Ginernet:
1. Subdominio `rank-s.israelzamora.es` → `public_html/rank-s.israelzamora.es`.
   Document Root en la subcarpeta `public/` si el panel lo permite; si no, el `.htaccess`
   de la raíz ya reescribe en un solo salto y reinyecta la cabecera `Authorization`.
2. Base de datos MySQL nueva + usuario con todos los permisos sobre ella. Apuntar nombre,
   usuario y contraseña.
3. Certificado SSL para el subdominio (Let's Encrypt desde el panel).

- [ ] **Paso 2: Rellenar el `.env` de producción**

En `backend/.env.produccion`, poner los `DB_*` del paso anterior, el `APP_KEY`
(`php artisan key:generate --show`), y los `MAIL_*` de una cuenta de correo del dominio
(la recuperación de contraseña no funciona sin esto).

- [ ] **Paso 3: Volcar el esquema y los datos ya migrados**

```bash
cd backend
mysqldump -u srank -psrank_local --no-tablespaces srank > /tmp/srank-inicial.sql
```

Subir ese `.sql` por phpMyAdmin del panel a la base de datos de Ginernet.

- [ ] **Paso 4: Construir y subir**

```bash
cd backend && bash build-deploy.sh
```

Arrastrar **todo el contenido** de `backend/deploy/` a `public_html/rank-s.israelzamora.es/`
con FileZilla. Después, desde el gestor de archivos del panel:

```
chmod -R 775 storage bootstrap/cache public/uploads
```

- [ ] **Paso 5: Comprobar que responde**

```bash
curl -i https://rank-s.israelzamora.es/api/system/today
```

Esperado: `401 Unauthorized` con cuerpo JSON. Un 500 significa permisos de `storage/` o un
`.env` mal puesto; un 404, que el Document Root no apunta donde debe.

```bash
curl -s -X POST https://rank-s.israelzamora.es/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"TU_CORREO","password":"TU_CONTRASEÑA"}'
```

Esperado: un `access_token`. Con ese token:

```bash
curl -s https://rank-s.israelzamora.es/api/system/today -H 'Authorization: Bearer TOKEN'
```

Esperado: el progreso reconstruido, las misiones del día generadas al vuelo y el entreno
sugerido.

- [ ] **Paso 6: Comprobar que el correo sale**

```bash
curl -s -X POST https://rank-s.israelzamora.es/api/auth/forgot-password \
  -H 'Content-Type: application/json' -d '{"email":"TU_CORREO"}'
```

Esperado: 200, y el código de seis cifras en la bandeja de entrada.

- [ ] **Paso 7: Dejar constancia**

Crear `docs/superpowers/plans/despliegue-fase-1-0.md` con: fecha, nombre de la base de
datos, usuario de MySQL (sin contraseña), cuenta de correo usada para el SMTP y cualquier
cosa que hubiera que tocar en el panel. Sin contraseñas: van solo en `.env.produccion`,
que no está en git.

- [ ] **Paso 8: Commit**

```bash
git add docs/superpowers/plans/despliegue-fase-1-0.md
git commit -m "docs: registro del despliegue de la fase 1.0"
```

---

## Definición de terminado

La fase 1.0 está cerrada cuando:

- [ ] `cd backend && php artisan test` pasa entero.
- [ ] `https://rank-s.israelzamora.es/api/system/today` devuelve el progreso real con un
      token válido.
- [ ] Los 18 entrenos históricos están en MySQL y `srank:recalculate` deja al usuario en
      el nivel que le corresponde.
- [ ] `bash build-deploy.sh` genera un paquete sin ninguna base de datos dentro.
- [ ] Se puede crear una cuenta nueva desde cero y recuperar su contraseña por correo.
- [ ] Subir imágenes de alimentos y recetas funciona sin ningún symlink.

Entonces empieza la **fase 1.1**: esqueleto Android, navegación, sistema de diseño, login
y registro.

---

## Desviaciones respecto al spec

Tres, todas conscientes:

**1 · Tests con PHPUnit, no con Pest.** El spec §11 dice Pest. El proyecto ya tiene 14
ficheros de test escritos en PHPUnit y Pest no está instalado. Añadir una dependencia y
mezclar dos estilos no compra nada: lo que importa es que la lógica del Sistema esté
probada, y lo está. Si más adelante se quiere Pest, se migra la suite entera de una vez.

**2 · Un endpoint más de lo previsto:** `POST /api/system/quests/{key}/complete`. Las
misiones opcionales del spec («50 flexiones», «8.000 pasos») no se pueden medir con los
datos que tenemos, así que sin una forma de marcarlas nunca se completarían y el XP
opcional sería inalcanzable. Está restringido a misiones con `is_optional = true`.

**3 · El bloque `system` lleva un `progress` que el spec no listaba.** Sin él, la app
tendría que llamar otra vez a `/api/system/today` después de cada registro solo para
repintar la cabecera de nivel y XP. Es una clave más en una respuesta que ya se estaba
enviando.

Y dos limitaciones que amplían la ya documentada en el spec §10:

- **Vitalidad arranca en cero para todos.** Se alimenta de misiones de hábito cumplidas, y
  las misiones no existían antes de hoy. Sube desde el primer día de uso.
- **La racha del Sistema es de días activos, no de días entrenados.** Cuenta cualquier
  registro: entreno, comida, agua, suplemento o peso. Los logros de racha usan esa misma
  definición, para que no haya dos rachas distintas conviviendo.
