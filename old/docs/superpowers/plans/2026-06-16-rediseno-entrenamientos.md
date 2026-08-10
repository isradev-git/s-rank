# Rediseño UX/UI de Entrenamientos — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rediseñar el flujo de entrenar de FitLoop al patrón "tabla por ejercicio" (Hevy/Strong) con almacenamiento por serie, datos de sesión anterior y PR a la vista, descanso no intrusivo y auto-guardado.

**Architecture:** Reestructurar in situ `resources/views/training.blade.php` (JS inline en Blade, convención del repo) reusando el backend. Cambio de modelo: `WorkoutController@store` pasa a guardar **una fila `ExerciseSet` por serie** (`sets=1`, sin migración). Nuevo endpoint `/exercises/last-session`. Ajuste de `/exercises/progress` y agrupado de series por ejercicio en `history.blade.php`.

**Tech Stack:** Laravel 12 (PHP 8.2), Blade, JS vanilla `fetch()` inline, SQLite, PHPUnit (tests de feature, sqlite en memoria), Chart.js.

**Reference spec:** `docs/superpowers/specs/2026-06-16-rediseno-entrenamientos-design.md`

---

## Estructura de ficheros

| Fichero | Acción | Responsabilidad |
|---------|--------|-----------------|
| `app/Http/Controllers/Api/WorkoutController.php` | Modificar `store()` | Contrato payload por serie + expandir a filas `sets=1` + detección PR sobre `sets[]` |
| `app/Http/Controllers/Api/ExerciseController.php` | Añadir `lastSession()`, modificar `progress()` | "Anterior" por serie; progreso agregado por fecha |
| `routes/api.php` | Añadir 1 ruta | `GET /exercises/last-session` |
| `resources/views/history.blade.php` | Modificar JS | Agrupar series por ejercicio (preview + detalle) |
| `resources/views/training.blade.php` | Reescritura por fases | Inicio, entreno activo (tabla), resumen, persistencia |
| `tests/Feature/WorkoutLoggingTest.php` | Crear | Tests de `store()` por serie |
| `tests/Feature/ExerciseLastSessionTest.php` | Crear | Test de `/exercises/last-session` |

**Orden:** backend testeable primero (Tareas 1-3), compat de lectura (Tarea 4), luego frontend (Tareas 5-11). Cada tarea termina en commit.

> **Convención de tests del repo:** Feature tests con `RefreshDatabase`, factories existentes (`User`, `Workout`, `ExerciseSet`). Autenticación Sanctum: `Sanctum::actingAs($user)` o `actingAs($user)`. Ejecutar con `php artisan test --filter=NombreTest`.

---

## Tarea 1: `store()` — almacenamiento por serie

**Files:**
- Modify: `app/Http/Controllers/Api/WorkoutController.php:86-171`
- Test: `tests/Feature/WorkoutLoggingTest.php` (crear)

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/WorkoutLoggingTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'mode' => 'gym',
            'date' => '2026-06-16',
            'duration_minutes' => 45,
            'exercises' => [[
                'name' => 'Press Banca',
                'sets' => [
                    ['weight_kg' => 80, 'reps' => 8, 'rpe' => 7, 'rest_seconds' => 90],
                    ['weight_kg' => 80, 'reps' => 7, 'rpe' => 8, 'rest_seconds' => 90],
                    ['weight_kg' => 85, 'reps' => 6, 'rpe' => 9, 'rest_seconds' => 120],
                ],
            ]],
        ], $overrides);
    }

    public function test_store_creates_one_row_per_set(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/workouts', $this->payload());

        $res->assertCreated();
        $this->assertSame(1, Workout::where('user_id', $user->id)->count());
        // 3 series => 3 filas, todas sets=1
        $rows = ExerciseSet::where('name', 'Press Banca')->get();
        $this->assertCount(3, $rows);
        $this->assertTrue($rows->every(fn ($r) => (int) $r->sets === 1));
        $this->assertEqualsCanonicalizing([8, 7, 6], $rows->pluck('reps')->all());
    }

    public function test_store_detects_pr_from_best_set(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // PR previo 82.5
        $old = Workout::factory()->create(['user_id' => $user->id, 'mode' => 'gym']);
        ExerciseSet::factory()->create(['workout_id' => $old->id, 'name' => 'Press Banca', 'weight_kg' => 82.5, 'reps' => 5, 'sets' => 1]);

        $res = $this->postJson('/api/workouts', $this->payload()); // mejor serie 85

        $res->assertCreated();
        $records = $res->json('new_records');
        $this->assertCount(1, $records);
        $this->assertSame('Press Banca', $records[0]['name']);
        $this->assertEquals(85.0, $records[0]['weight_kg']);
        $this->assertEquals(82.5, $records[0]['previous_pr']);
    }

    public function test_store_accepts_bodyweight_set_without_weight(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/workouts', $this->payload([
            'mode' => 'calisthenics',
            'exercises' => [[
                'name' => 'Dominadas',
                'sets' => [['reps' => 10], ['reps' => 8]],
            ]],
        ]));

        $res->assertCreated();
        $rows = ExerciseSet::where('name', 'Dominadas')->get();
        $this->assertCount(2, $rows);
        $this->assertNull($rows->first()->weight_kg);
        $this->assertEmpty($res->json('new_records')); // sin peso => sin PR
    }
}
```

- [ ] **Step 2: Ejecutar para verificar que falla**

Run: `php artisan test --filter=WorkoutLoggingTest`
Expected: FAIL (hoy `store()` espera `exercises.*.sets` como entero y guarda 1 fila/ejercicio → cuenta de filas y PR no cuadran).

- [ ] **Step 3: Reescribir `store()`**

Reemplazar el método `store()` completo (líneas 86-171) por:

```php
    public function store(Request $request)
    {
        $request->validate([
            'mode' => 'required|string|in:gym,home,calisthenics,swimming,pasos',
            'date' => 'required|date',
            'duration_minutes' => 'required|integer|min:0|max:600',
            'calories_burned' => 'nullable|integer|min:0|max:10000',
            'notes' => 'nullable|string|max:2000',
            'exercises' => 'array|max:50',
            'exercises.*.name' => 'required|string|max:100',
            'exercises.*.sets' => 'array|max:50',
            'exercises.*.sets.*.weight_kg' => 'nullable|numeric|min:0|max:2000',
            'exercises.*.sets.*.reps' => 'nullable|integer|min:0|max:1000',
            'exercises.*.sets.*.rpe' => 'nullable|integer|min:1|max:10',
            'exercises.*.sets.*.rest_seconds' => 'nullable|integer|min:0|max:3600',
            'exercises.*.sets.*.time_seconds' => 'nullable|integer|min:0|max:100000',
            'exercises.*.sets.*.distance_m' => 'nullable|numeric|min:0|max:100000',
            'exercises.*.sets.*.laps' => 'nullable|integer|min:0|max:1000',
            'exercises.*.sets.*.style' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        // PR previos por ejercicio (antes de insertar)
        $exerciseNames = collect($request->exercises ?? [])->pluck('name')->unique();
        $previousPRs = [];
        if ($exerciseNames->isNotEmpty()) {
            $previousPRs = DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $user->id)
                ->whereNotNull('es.weight_kg')
                ->where('es.weight_kg', '>', 0)
                ->whereIn('es.name', $exerciseNames)
                ->groupBy('es.name')
                ->select('es.name', DB::raw('MAX(es.weight_kg) as max_weight'))
                ->pluck('max_weight', 'name')
                ->toArray();
        }

        return DB::transaction(function () use ($request, $user, $previousPRs) {
            $workout = Workout::create([
                'user_id'          => $user->id,
                'mode'             => $request->mode,
                'date'             => $request->date,
                'duration_minutes' => $request->duration_minutes,
                'calories_burned'  => $request->calories_burned ?? null,
                'notes'            => $request->notes ?? null,
            ]);

            $newRecords = [];

            foreach ($request->exercises ?? [] as $ex) {
                $bestWeight = 0.0;

                foreach ($ex['sets'] ?? [] as $set) {
                    ExerciseSet::create([
                        'workout_id'   => $workout->id,
                        'name'         => $ex['name'],
                        'sets'         => 1, // una fila = una serie; sets=1 mantiene el cálculo de volumen
                        'reps'         => $set['reps'] ?? null,
                        'weight_kg'    => $set['weight_kg'] ?? null,
                        'rpe'          => $set['rpe'] ?? null,
                        'rest_seconds' => $set['rest_seconds'] ?? null,
                        'time_seconds' => $set['time_seconds'] ?? null,
                        'distance_m'   => $set['distance_m'] ?? null,
                        'laps'         => $set['laps'] ?? null,
                        'style'        => $set['style'] ?? null,
                    ]);

                    $w = (float) ($set['weight_kg'] ?? 0);
                    if ($w > $bestWeight) {
                        $bestWeight = $w;
                    }
                }

                if ($bestWeight > 0) {
                    $prev = (float) ($previousPRs[$ex['name']] ?? 0);
                    if ($bestWeight > $prev) {
                        $newRecords[] = [
                            'name'        => $ex['name'],
                            'weight_kg'   => $bestWeight,
                            'previous_pr' => $prev > 0 ? $prev : null,
                            'is_first'    => $prev === 0.0,
                        ];
                    }
                }
            }

            $data = $workout->load('sets')->toArray();
            $data['new_records'] = $newRecords;

            return response()->json($data, 201);
        });
    }
```

- [ ] **Step 4: Ejecutar para verificar que pasa**

Run: `php artisan test --filter=WorkoutLoggingTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/WorkoutController.php tests/Feature/WorkoutLoggingTest.php
git commit -m "feat(workouts): almacenamiento por serie en store() + deteccion PR sobre mejor serie"
```

---

## Tarea 2: Endpoint `/exercises/last-session`

**Files:**
- Modify: `app/Http/Controllers/Api/ExerciseController.php` (añadir método `lastSession`)
- Modify: `routes/api.php` (añadir ruta, junto a las otras `/exercises/*`)
- Test: `tests/Feature/ExerciseLastSessionTest.php` (crear)

- [ ] **Step 1: Escribir el test que falla**

Crear `tests/Feature/ExerciseLastSessionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExerciseLastSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_sets_of_most_recent_session(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Sesión antigua (no debe usarse)
        $old = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-06-01']);
        ExerciseSet::factory()->create(['workout_id' => $old->id, 'name' => 'Press Banca', 'weight_kg' => 70, 'reps' => 10, 'sets' => 1]);

        // Sesión reciente con 2 series
        $recent = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-06-10']);
        ExerciseSet::factory()->create(['workout_id' => $recent->id, 'name' => 'Press Banca', 'weight_kg' => 80, 'reps' => 8, 'sets' => 1]);
        ExerciseSet::factory()->create(['workout_id' => $recent->id, 'name' => 'Press Banca', 'weight_kg' => 80, 'reps' => 7, 'sets' => 1]);

        $res = $this->getJson('/api/exercises/last-session?name=Press Banca');

        $res->assertOk();
        $res->assertJsonCount(2);
        $res->assertJsonPath('0.weight_kg', 80);
        $res->assertJsonPath('0.reps', 8);
    }

    public function test_returns_empty_when_no_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/exercises/last-session?name=Inexistente');

        $res->assertOk();
        $res->assertExactJson([]);
    }
}
```

- [ ] **Step 2: Ejecutar para verificar que falla**

Run: `php artisan test --filter=ExerciseLastSessionTest`
Expected: FAIL (404, ruta no existe).

- [ ] **Step 3: Añadir el método `lastSession`**

En `app/Http/Controllers/Api/ExerciseController.php`, añadir tras `history()`:

```php
    /**
     * Series de la ÚLTIMA sesión que contiene este ejercicio (para la columna "Anterior").
     * GET /api/exercises/last-session?name=Press%20Banca
     */
    public function lastSession(Request $request)
    {
        $user = $request->user();
        $name = $request->query('name');
        if (!$name) {
            return response()->json([]);
        }

        // Fecha del entreno más reciente que incluye este ejercicio
        $lastDate = ExerciseSet::query()
            ->join('workouts', 'exercise_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $user->id)
            ->where('exercise_sets.name', $name)
            ->max('workouts.date');

        if (!$lastDate) {
            return response()->json([]);
        }

        $sets = ExerciseSet::query()
            ->join('workouts', 'exercise_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $user->id)
            ->where('exercise_sets.name', $name)
            ->whereDate('workouts.date', \Carbon\Carbon::parse($lastDate)->toDateString())
            ->orderBy('exercise_sets.id')
            ->get(['exercise_sets.weight_kg', 'exercise_sets.reps', 'exercise_sets.rpe', 'exercise_sets.time_seconds', 'exercise_sets.distance_m']);

        return response()->json($sets->map(fn ($s) => [
            'weight_kg' => $s->weight_kg !== null ? (float) $s->weight_kg : null,
            'reps'      => $s->reps,
            'rpe'       => $s->rpe,
            'time_seconds' => $s->time_seconds,
            'distance_m'   => $s->distance_m !== null ? (float) $s->distance_m : null,
        ]));
    }
```

- [ ] **Step 4: Añadir la ruta**

En `routes/api.php`, junto a las otras `/exercises/*` (cerca de la línea 59), añadir:

```php
    Route::get('/exercises/last-session', [ExerciseController::class, 'lastSession']);
```

- [ ] **Step 5: Ejecutar para verificar que pasa**

Run: `php artisan test --filter=ExerciseLastSessionTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/ExerciseController.php routes/api.php tests/Feature/ExerciseLastSessionTest.php
git commit -m "feat(exercises): endpoint last-session para columna Anterior por serie"
```

---

## Tarea 3: `progress()` agregado por fecha (mejor serie/día)

**Files:**
- Modify: `app/Http/Controllers/Api/ExerciseController.php:88-109`
- Test: `tests/Feature/ExerciseLastSessionTest.php` (añadir método)

Con el modelo por serie, `progress()` devolvería varias filas por fecha → el gráfico pintaría puntos duplicados. Agregar por fecha tomando la **mejor serie** (mayor peso; en empate, más reps).

- [ ] **Step 1: Añadir el test que falla**

Añadir a `tests/Feature/ExerciseLastSessionTest.php`:

```php
    public function test_progress_returns_one_point_per_date_best_set(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $w = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-06-10']);
        ExerciseSet::factory()->create(['workout_id' => $w->id, 'name' => 'Sentadilla', 'weight_kg' => 100, 'reps' => 5, 'sets' => 1]);
        ExerciseSet::factory()->create(['workout_id' => $w->id, 'name' => 'Sentadilla', 'weight_kg' => 110, 'reps' => 3, 'sets' => 1]);

        $res = $this->getJson('/api/exercises/progress?name=Sentadilla');

        $res->assertOk();
        $res->assertJsonCount(1);                 // un punto para esa fecha
        $res->assertJsonPath('0.weight_kg', 110); // mejor serie del día
    }
```

- [ ] **Step 2: Ejecutar para verificar que falla**

Run: `php artisan test --filter=ExerciseLastSessionTest::test_progress_returns_one_point_per_date_best_set`
Expected: FAIL (devuelve 2 filas).

- [ ] **Step 3: Reescribir `progress()`**

Reemplazar el cuerpo de `progress()` (líneas 88-109) por:

```php
    public function progress(Request $request)
    {
        $user = $request->user();
        $name = $request->query('name');
        if (!$name) return response()->json([]);

        $rows = ExerciseSet::whereHas('workout', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('name', $name)
            ->join('workouts', 'exercise_sets.workout_id', '=', 'workouts.id')
            ->orderBy('workouts.date', 'asc')
            ->select('exercise_sets.*', 'workouts.date as workout_date')
            ->get();

        // Una entrada por fecha: la mejor serie (mayor peso; empate => más reps)
        $byDate = $rows
            ->groupBy(fn ($s) => \Carbon\Carbon::parse($s->workout_date)->toDateString())
            ->map(function ($daySets, $date) {
                $best = $daySets->sortByDesc(fn ($s) => [(float) ($s->weight_kg ?? 0), (int) ($s->reps ?? 0)])->first();
                return [
                    'date'      => $date,
                    'weight_kg' => $best->weight_kg,
                    'reps'      => $best->reps,
                    'sets'      => $daySets->count(),
                ];
            })
            ->values();

        return response()->json($byDate);
    }
```

- [ ] **Step 4: Ejecutar para verificar que pasa**

Run: `php artisan test --filter=ExerciseLastSessionTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/ExerciseController.php tests/Feature/ExerciseLastSessionTest.php
git commit -m "fix(exercises): progress agregado por fecha (mejor serie del dia)"
```

---

## Tarea 4: Historial — agrupar series por ejercicio

Con el modelo por serie, un entreno tiene varias filas `sets` con el mismo `name`. `history.blade.php` lista filas sueltas → saldrían duplicados. Agrupar por nombre en **preview de tarjeta** y **detalle**.

**Files:**
- Modify: `resources/views/history.blade.php` (funciones `renderCard` ~556-601 y `renderDetailContent` ~731-796)

- [ ] **Step 1: Helper de agrupado**

Añadir cerca de los helpers JS (p.ej. tras `formatMealType`, ~línea 474):

```javascript
    // Agrupa filas de series por nombre de ejercicio (modelo por-serie).
    // Devuelve [{ name, setCount, sets:[fila,...] }] en orden de aparición.
    function groupSetsByExercise(sets) {
        const order = [];
        const map = new Map();
        (sets || []).forEach(s => {
            if (!map.has(s.name)) { map.set(s.name, []); order.push(s.name); }
            map.get(s.name).push(s);
        });
        return order.map(name => ({ name, setCount: map.get(name).length, sets: map.get(name) }));
    }
```

- [ ] **Step 2: Usar el agrupado en `renderCard`**

En `renderCard` (~556), sustituir el bloque que construye `exerciseChips` por uno basado en grupos:

```javascript
        const groups = groupSetsByExercise(workout.sets || workout.exercises || []);
        const exerciseChips = groups.slice(0, 3).map(g =>
            `<span style="background:var(--bg-muted);padding:0.2rem 0.5rem;border-radius:var(--radius-sm);font-size:0.6875rem;color:var(--text-secondary);">${esc(String(g.setCount))}× ${esc(g.name)}</span>`
        ).join('');
        const remaining = groups.length > 3 ? `<span style="font-size:0.6875rem;color:var(--text-muted);">+${groups.length - 3} más</span>` : '';
```

(El resto de `renderCard` no cambia; `groups.length` sustituye a `exercises.length` en el condicional de "Sin ejercicios".)

- [ ] **Step 3: Usar el agrupado en `renderDetailContent`**

En `renderDetailContent` (~731), sustituir el `.map` sobre `exercises` por uno sobre grupos, mostrando cada serie:

```javascript
        const groups = groupSetsByExercise(workout.sets || []);
        // ...
        ${groups.length > 0 ? `
            <div style="margin-bottom:1rem;">
                <p style="font-size:0.6875rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin-bottom:0.75rem;">${groups.length} ejercicio${groups.length !== 1 ? 's' : ''}</p>
                <div style="display:flex;flex-direction:column;gap:0.5rem;">
                    ${groups.map(g => `
                        <div style="background:var(--bg-surface);border-radius:var(--radius-md);padding:0.75rem;">
                            <p style="font-size:0.875rem;font-weight:600;color:var(--text-primary);margin:0 0 0.5rem;">${esc(g.name)} <span style="color:var(--text-muted);font-weight:400;">· ${g.setCount} serie${g.setCount !== 1 ? 's' : ''}</span></p>
                            <div style="display:flex;flex-direction:column;gap:0.25rem;">
                                ${g.sets.map((s, i) => `
                                    <div style="display:flex;gap:0.75rem;font-size:0.75rem;color:var(--text-secondary);">
                                        <span style="color:var(--text-muted);width:1.5rem;">${i + 1}</span>
                                        ${s.weight_kg ? `<span style="color:var(--color-primary);font-weight:600;">${s.weight_kg} kg</span>` : ''}
                                        ${s.reps ? `<span>${s.reps} reps</span>` : ''}
                                        ${s.rpe ? `<span style="color:var(--text-muted);">RPE ${s.rpe}</span>` : ''}
                                        ${s.time_seconds ? `<span style="color:var(--text-muted);">${s.time_seconds}s</span>` : ''}
                                        ${s.distance_m ? `<span style="color:var(--text-muted);">${s.distance_m}m</span>` : ''}
                                    </div>`).join('')}
                            </div>
                        </div>`).join('')}
                </div>
            </div>` : ''}
```

- [ ] **Step 4: Verificación manual**

Run: `composer run dev` (o `php artisan serve`), abrir `/history`.
Expected: un entreno nuevo (creado tras Tarea 1) muestra "3× Press Banca" en la tarjeta y, en el detalle, las 3 series numeradas con sus pesos/reps. Entrenos antiguos (fila agregada) siguen mostrándose sin error.

- [ ] **Step 5: Commit**

```bash
git add resources/views/history.blade.php
git commit -m "feat(history): agrupar series por ejercicio (modelo por-serie)"
```

---

## Tarea 5: Training — modelo de estado, payload nuevo y auto-guardado

Base JS del rediseño. No cambia UI todavía: introduce el **modelo de datos por serie** en `activeWorkout`, el **builder del payload nuevo** y la **persistencia en localStorage**. Se valida construyendo el payload y guardando/restaurando.

**Files:**
- Modify: `resources/views/training.blade.php` (bloque `@push('scripts')`, estado global ~1208 y funciones de guardado ~2076)

**Modelo `activeWorkout` (nuevo):**
```js
activeWorkout = {
  mode, name, templateId,            // contexto
  startTime,                         // ms epoch (para cronómetro y "hace X")
  currentIdx,                        // ejercicio expandido
  exercises: [{
    name,
    target: { sets, reps, rest },    // de plantilla (o null)
    pr: null,                        // récord actual (de /exercises/records)
    previous: null,                  // [{weight_kg,reps,...}] de /exercises/last-session (lazy)
    sets: [{ weight_kg, reps, rpe, rest_seconds, time_seconds, distance_m, done }]
  }]
}
```

- [ ] **Step 1: Constantes de persistencia + helpers**

Añadir al estado global (tras `let currentRestSeconds = ...`, ~1222):

```javascript
    const DRAFT_KEY = 'fitloop.active_workout';
    const MODE_KEY  = 'fitloop.training_mode';
    let draftSaveTimer = null;

    function saveDraft() {
        if (!activeWorkout) return;
        clearTimeout(draftSaveTimer);
        draftSaveTimer = setTimeout(() => {
            try { localStorage.setItem(DRAFT_KEY, JSON.stringify(activeWorkout)); } catch (e) {}
        }, 400);
    }

    function clearDraft() {
        clearTimeout(draftSaveTimer);
        try { localStorage.removeItem(DRAFT_KEY); } catch (e) {}
    }

    function loadDraft() {
        try {
            const raw = localStorage.getItem(DRAFT_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    }
```

- [ ] **Step 2: Builder del payload nuevo (contrato por serie)**

Añadir función (cerca de `finishWorkout`, ~2076):

```javascript
    // Construye el payload por-serie para POST /workouts.
    // Descarta series totalmente vacías (sin peso, reps, tiempo ni distancia).
    function buildWorkoutPayload() {
        const exercises = (activeWorkout.exercises || []).map(ex => ({
            name: ex.name,
            sets: (ex.sets || [])
                .filter(s => s.weight_kg != null || s.reps != null || s.time_seconds != null || s.distance_m != null)
                .map(s => ({
                    weight_kg: s.weight_kg ?? null,
                    reps: s.reps ?? null,
                    rpe: s.rpe ?? null,
                    rest_seconds: s.rest_seconds ?? null,
                    time_seconds: s.time_seconds ?? null,
                    distance_m: s.distance_m ?? null,
                    style: s.style ?? null,
                })),
        })).filter(ex => ex.sets.length > 0);

        const durationMin = Math.max(1, Math.round((Date.now() - activeWorkout.startTime) / 60000));

        return {
            mode: activeWorkout.mode,
            date: new Date(activeWorkout.startTime).toISOString().slice(0, 10),
            duration_minutes: durationMin,
            notes: activeWorkout.notes || null,
            exercises,
        };
    }
```

- [ ] **Step 3: `finishWorkout` usa el builder + limpia draft**

En `finishWorkout` (~2076), sustituir la construcción del payload por `const payload = buildWorkoutPayload();`, mantener `apiCall('/workouts', 'POST', payload)`, y tras éxito llamar `clearDraft();` (antes de mostrar el resumen). En `abortWorkout` (~2220) añadir `clearDraft();`.

- [ ] **Step 4: Demo runnable de verificación (auto-check temporal)**

Añadir temporalmente al final del script (se borra en Step 6) para validar la lógica pura en consola:

```javascript
    // DEMO temporal — verificar builder + persistencia (borrar después)
    window.__trainingSelfCheck = function () {
        activeWorkout = { mode: 'gym', name: 'Test', templateId: null, startTime: Date.now() - 600000, currentIdx: 0,
            exercises: [{ name: 'Press Banca', target: null, pr: null, previous: null,
                sets: [ {weight_kg:80,reps:8,done:true}, {weight_kg:0,reps:0,done:false}, {} ] }] };
        const p = buildWorkoutPayload();
        console.assert(p.exercises.length === 1, 'debe quedar 1 ejercicio');
        console.assert(p.exercises[0].sets.length === 1, 'solo la serie con datos reales');
        console.assert(p.duration_minutes >= 9 && p.duration_minutes <= 11, 'duracion ~10 min');
        saveDraft(); setTimeout(() => { console.assert(loadDraft() && loadDraft().name === 'Test', 'draft persiste'); clearDraft(); console.log('selfcheck OK'); }, 500);
    };
```

- [ ] **Step 5: Ejecutar la demo**

Run: `composer run dev`, abrir `/training`, en consola del navegador ejecutar `__trainingSelfCheck()`.
Expected: consola imprime `selfcheck OK` sin asserts fallando.

- [ ] **Step 6: Borrar la demo y commit**

Eliminar el bloque `window.__trainingSelfCheck` del Step 4.

```bash
git add resources/views/training.blade.php
git commit -m "feat(training): modelo por-serie, builder de payload y auto-guardado (localStorage)"
```

---

## Tarea 6: Training — pantalla de entreno activo (tabla por ejercicio)

Reescritura de `#training-screen` y su render. Reemplaza el patrón "un ejercicio a la vez + modal por serie" por la tabla con edición inline, ✓, "Anterior", PR y barra de descanso fina.

**Files:**
- Modify: `resources/views/training.blade.php` — markup `#training-screen` (~788-891), CSS del bloque `@push('styles')`, JS de render y de series (~1500-2070). Eliminar: `#add-set-sheet` (modal de serie, ~893-961) y `#rest-overlay` (~1117-1140) → sustituidos.

**Estructura HTML de `#training-screen` (reemplazo):**
```html
<div id="training-screen" style="display:none;">
  <div class="training-header">
    <button onclick="confirmAbort()" class="btn btn-ghost btn-sm" style="color:var(--color-danger);font-weight:700;">Cancelar</button>
    <div style="text-align:center;">
      <div class="training-elapsed" id="elapsed-timer">0:00</div>
      <div id="training-name" class="tn-sub"></div>
    </div>
    <button onclick="finishWorkout()" class="btn btn-primary btn-sm" id="btn-finish" style="font-weight:700;">Terminar</button>
  </div>
  <div class="exercise-progress-bar"><div class="fill" id="exercise-progress-fill" style="width:0%;"></div></div>
  <div class="training-subprogress">
    <span id="sets-progress">0 de 0 series</span>
    <span id="volume-progress">0 kg</span>
  </div>
  <div class="training-body" id="exercises-container"><!-- tarjetas de ejercicio aquí --></div>
  <button class="addex-btn" onclick="addNewExercise()"><i data-lucide="plus"></i> Añadir ejercicio</button>
  <div id="rest-bar" class="rest-bar" style="display:none;">
    <span class="rest-bar-label">Descanso</span>
    <span class="rest-bar-time" id="rest-bar-time">0:00</span>
    <button class="rest-bar-btn" onclick="adjustRest(-15)">−15</button>
    <button class="rest-bar-btn" onclick="adjustRest(15)">+15</button>
    <button class="rest-bar-btn primary" onclick="skipRest()">Saltar ▸</button>
  </div>
</div>
```

**CSS a añadir** (en el bloque `@push('styles')`; reemplaza estilos del modal/overlay viejos):
```css
.training-subprogress { display:flex; justify-content:space-between; padding:0.5rem 1.25rem; font-size:0.6875rem; color:var(--text-muted); background:var(--bg-background); }
.exercise-table-card { background:var(--bg-card); border:1px solid var(--border-light); border-radius:1rem; padding:0.85rem 0.9rem; margin-bottom:0.85rem; }
.exercise-table-card.active { border-color:rgba(245,158,11,0.4); box-shadow:0 0 0 1px rgba(245,158,11,0.15); }
.etc-head { display:flex; align-items:center; gap:0.5rem; }
.etc-name { font-size:0.95rem; font-weight:800; flex:1; }
.etc-pr { background:rgba(245,158,11,0.12); color:var(--color-primary); font-size:0.625rem; font-weight:800; padding:0.15rem 0.4rem; border-radius:0.35rem; white-space:nowrap; }
.etc-kebab { color:var(--text-muted); background:none; border:none; font-size:1.1rem; cursor:pointer; padding:0 0.25rem; }
.etc-sub { font-size:0.6875rem; color:var(--text-muted); margin:0.15rem 0 0.5rem; }
.set-table { width:100%; border-collapse:collapse; }
.set-table th { font-size:0.5625rem; color:var(--text-muted); font-weight:700; text-transform:uppercase; letter-spacing:0.04em; padding-bottom:0.3rem; text-align:center; }
.set-table th.l { text-align:left; }
.set-table td { font-size:0.8125rem; text-align:center; padding:0.35rem 0; border-top:1px solid var(--border-light); font-variant-numeric:tabular-nums; }
.set-table tr.done td { color:var(--color-success); background:rgba(34,197,94,0.05); }
.set-table td.prev { color:var(--text-muted); font-size:0.6875rem; }
.set-cell-input { width:3rem; text-align:center; background:var(--bg-muted); border:1px solid var(--border-light); border-radius:0.4rem; padding:0.25rem; font-size:0.8125rem; font-weight:700; color:var(--text-primary); }
.set-cell-input:focus { border-color:var(--color-primary); outline:none; }
.set-check { width:1.4rem; height:1.4rem; border-radius:0.4rem; border:1.5px solid var(--border-medium); background:none; cursor:pointer; }
.set-check.on { background:var(--color-success); border-color:var(--color-success); color:#000; font-weight:900; }
.etc-addset { color:var(--color-primary); font-size:0.75rem; font-weight:700; text-align:center; padding:0.5rem 0 0.1rem; cursor:pointer; }
.exercise-collapsed { display:flex; align-items:center; justify-content:space-between; cursor:pointer; }
.exercise-collapsed .cnt { font-size:0.75rem; color:var(--text-muted); font-weight:700; }
.addex-btn { display:block; width:calc(100% - 2.5rem); margin:0 1.25rem 1rem; padding:0.7rem; border:1.5px dashed var(--border-medium); border-radius:0.9rem; background:none; color:var(--text-secondary); font-weight:700; font-size:0.8125rem; }
.rest-bar { position:fixed; left:50%; transform:translateX(-50%); bottom:0; width:100%; max-width:48rem; z-index:250; display:flex; align-items:center; gap:0.6rem; padding:0.7rem 1.25rem; padding-bottom:calc(0.7rem + env(safe-area-inset-bottom)); background:#1a1206; border-top:1px solid rgba(245,158,11,0.35); }
.rest-bar-label { font-size:0.75rem; font-weight:800; color:var(--color-primary); flex:1; }
.rest-bar-time { font-size:1rem; font-weight:900; color:var(--color-primary); font-variant-numeric:tabular-nums; }
.rest-bar-btn { font-size:0.6875rem; color:var(--text-secondary); border:1px solid var(--border-medium); border-radius:0.4rem; padding:0.25rem 0.5rem; background:none; }
.rest-bar-btn.primary { color:#000; background:var(--color-primary); border-color:var(--color-primary); font-weight:800; }
.col-cfg-strength .col-swim { display:none; }
.col-cfg-swim .col-strength { display:none; }
```

- [ ] **Step 1: Configuración de columnas por modo**

Añadir helper JS:

```javascript
    // Columnas de la tabla según modo. Fuerza: Kg/Reps/RPE. Natación: Distancia/Tiempo/Estilo.
    function columnsForMode(mode) {
        if (mode === 'swimming') {
            return { kind: 'swim', headers: ['Serie', 'Distancia (m)', 'Tiempo (s)', '✓'], fields: ['distance_m', 'time_seconds'] };
        }
        // gym, home, calisthenics
        const weightLabel = mode === 'calisthenics' ? 'Lastre' : 'Kg';
        return { kind: 'strength', headers: ['Serie', 'Anterior', weightLabel, 'Reps', 'RPE', '✓'], fields: ['weight_kg', 'reps'] };
    }
```

- [ ] **Step 2: Render de una tarjeta de ejercicio**

```javascript
    function renderExerciseCard(ex, idx) {
        const cols = columnsForMode(activeWorkout.mode);
        const active = idx === activeWorkout.currentIdx;
        const doneCount = ex.sets.filter(s => s.done).length;

        if (!active) {
            return `<div class="exercise-table-card" onclick="expandExercise(${idx})">
                <div class="etc-head exercise-collapsed">
                    <span class="etc-name">${esc(ex.name)}</span>
                    <span class="cnt">${doneCount}/${ex.sets.length || (ex.target?.sets ?? 0)}</span>
                    <span class="etc-kebab">⌄</span>
                </div></div>`;
        }

        const prBadge = ex.pr ? `<span class="etc-pr">🏆 PR ${Math.round(ex.pr)}</span>` : '';
        const sub = ex.target ? `<p class="etc-sub">Objetivo ${ex.target.sets ?? '-'}×${ex.target.reps ?? '-'}${ex.target.rest ? ' · descanso ' + ex.target.rest + 's' : ''}</p>` : '';
        const head = `<tr>${cols.headers.map((h, i) => `<th class="${i === 0 ? 'l' : ''}">${h}</th>`).join('')}</tr>`;
        const rows = ex.sets.map((s, si) => renderSetRow(ex, idx, s, si, cols)).join('');

        return `<div class="exercise-table-card active">
            <div class="etc-head">
                <span class="etc-name">${esc(ex.name)}</span>${prBadge}
                <button class="etc-kebab" onclick="openExerciseMenu(${idx})">⋮</button>
            </div>${sub}
            <table class="set-table">${head}${rows}</table>
            <div class="etc-addset" onclick="addSetRow(${idx})">+ Añadir serie</div>
        </div>`;
    }
```

- [ ] **Step 3: Render de una fila de serie (con edición inline y ✓)**

```javascript
    function renderSetRow(ex, exIdx, s, si, cols) {
        const num = `<td>${si + 1}</td>`;
        const check = `<td><button class="set-check ${s.done ? 'on' : ''}" onclick="toggleSetDone(${exIdx},${si})">${s.done ? '✓' : ''}</button></td>`;

        if (cols.kind === 'swim') {
            return `<tr class="${s.done ? 'done' : ''}">${num}
                <td><input class="set-cell-input" inputmode="numeric" value="${s.distance_m ?? ''}" onchange="editSet(${exIdx},${si},'distance_m',this.value)"></td>
                <td><input class="set-cell-input" inputmode="numeric" value="${s.time_seconds ?? ''}" onchange="editSet(${exIdx},${si},'time_seconds',this.value)"></td>
                ${check}</tr>`;
        }

        const prev = ex.previous && ex.previous[si]
            ? `${ex.previous[si].weight_kg ?? '–'}×${ex.previous[si].reps ?? '–'}`
            : '–';
        return `<tr class="${s.done ? 'done' : ''}">${num}
            <td class="prev">${prev}</td>
            <td><input class="set-cell-input" inputmode="decimal" value="${s.weight_kg ?? ''}" onchange="editSet(${exIdx},${si},'weight_kg',this.value)"></td>
            <td><input class="set-cell-input" inputmode="numeric" value="${s.reps ?? ''}" onchange="editSet(${exIdx},${si},'reps',this.value)"></td>
            <td><input class="set-cell-input" inputmode="numeric" value="${s.rpe ?? ''}" onchange="editSet(${exIdx},${si},'rpe',this.value)" style="width:2.2rem;"></td>
            ${check}</tr>`;
    }
```

- [ ] **Step 4: Acciones de series y render global**

```javascript
    function editSet(exIdx, si, field, value) {
        const num = value === '' ? null : Number(value);
        activeWorkout.exercises[exIdx].sets[si][field] = (num != null && !isNaN(num)) ? num : null;
        saveDraft();
        updateProgress();
    }

    function toggleSetDone(exIdx, si) {
        const set = activeWorkout.exercises[exIdx].sets[si];
        set.done = !set.done;
        saveDraft();
        renderExercises();
        updateProgress();
        if (set.done) {
            const rest = set.rest_seconds ?? activeWorkout.exercises[exIdx].target?.rest ?? currentRestSeconds;
            startRest(rest);
        }
    }

    function addSetRow(exIdx) {
        const sets = activeWorkout.exercises[exIdx].sets;
        const last = sets[sets.length - 1];
        sets.push({ weight_kg: last?.weight_kg ?? null, reps: last?.reps ?? null, rpe: null,
            rest_seconds: activeWorkout.exercises[exIdx].target?.rest ?? null, done: false });
        saveDraft();
        renderExercises();
    }

    function expandExercise(idx) { activeWorkout.currentIdx = idx; loadPreviousFor(idx); renderExercises(); saveDraft(); }

    function renderExercises() {
        const c = document.getElementById('exercises-container');
        c.innerHTML = activeWorkout.exercises.map((ex, i) => renderExerciseCard(ex, i)).join('');
        lucide.createIcons();
    }

    function updateProgress() {
        const allSets = activeWorkout.exercises.flatMap(e => e.sets);
        const done = allSets.filter(s => s.done).length;
        const vol = allSets.reduce((a, s) => a + (Number(s.weight_kg) || 0) * (Number(s.reps) || 0), 0);
        document.getElementById('sets-progress').textContent = `${done} de ${allSets.length} series`;
        document.getElementById('volume-progress').textContent = `${Math.round(vol).toLocaleString('es')} kg`;
        document.getElementById('exercise-progress-fill').style.width = allSets.length ? `${(done / allSets.length) * 100}%` : '0%';
    }
```

- [ ] **Step 5: "Anterior" y PR (carga perezosa)**

```javascript
    async function loadPreviousFor(idx) {
        const ex = activeWorkout.exercises[idx];
        if (ex.previous !== null && ex.pr !== null) return; // ya cargado
        try {
            const prev = await apiCall(`/exercises/last-session?name=${encodeURIComponent(ex.name)}`);
            ex.previous = Array.isArray(prev) ? prev : [];
        } catch (e) { ex.previous = []; }
        try {
            if (ex.pr === null) {
                const records = await apiCall('/exercises/records');
                const rec = (records || []).find(r => r.name === ex.name);
                ex.pr = rec ? Number(rec.max_weight) : 0;
            }
        } catch (e) { ex.pr = 0; }
        renderExercises();
    }
```

- [ ] **Step 6: Barra de descanso fina (sustituye overlay)**

```javascript
    let restHandle = null, restRemaining = 0;
    function startRest(seconds) {
        if (!seconds || seconds < 1) return;
        restRemaining = seconds;
        document.getElementById('rest-bar').style.display = 'flex';
        updateRestBar();
        clearInterval(restHandle);
        restHandle = setInterval(() => {
            restRemaining--;
            if (restRemaining <= 0) { skipRest(); return; }
            updateRestBar();
        }, 1000);
    }
    function updateRestBar() {
        const m = Math.floor(restRemaining / 60), s = restRemaining % 60;
        document.getElementById('rest-bar-time').textContent = `${m}:${String(s).padStart(2, '0')}`;
    }
    function adjustRest(delta) { restRemaining = Math.max(1, restRemaining + delta); updateRestBar(); }
    function skipRest() { clearInterval(restHandle); document.getElementById('rest-bar').style.display = 'none'; }
```

- [ ] **Step 7: `beginWorkout` construye el modelo nuevo**

Adaptar `beginWorkout` (~1476) para construir `activeWorkout.exercises` con la forma nueva: por cada ejercicio de plantilla/libre, `{ name, target:{sets,reps,rest}, pr:null, previous:null, sets:[<una serie inicial vacía con prefill de target>] }`. Fijar `startTime = Date.now()`, `currentIdx = 0`, llamar `renderExercises()`, `updateProgress()`, `loadPreviousFor(0)`, arrancar cronómetro y `saveDraft()`.

- [ ] **Step 8: Verificación manual**

Run: `composer run dev`, `/training` → iniciar una plantilla de Gym.
Expected: ejercicios en tarjetas; el activo muestra tabla; editar Kg/Reps actualiza progreso; ✓ marca verde y abre barra de descanso (saltable); columna "Anterior" aparece si hay historial; badge PR si existe. Calistenia muestra "Lastre" opcional. Iniciar Natación: tabla con Distancia/Tiempo (sin romper).

- [ ] **Step 9: Commit**

```bash
git add resources/views/training.blade.php
git commit -m "feat(training): pantalla de entreno activo con tabla por ejercicio, edicion inline y descanso fino"
```

---

## Tarea 7: Training — pantalla de inicio (elegir rutina)

**Files:**
- Modify: `resources/views/training.blade.php` — `#phase-select` (~692-785), CSS de tarjetas (eliminar `.template-card-v2` y variante vieja, dejar una sola), JS `renderTemplates`/`filterMode`.

- [ ] **Step 1: Persistir el modo**

En `filterMode(mode)` (~1258) añadir `localStorage.setItem(MODE_KEY, mode);`. En la inicialización (`loadTemplates`/DOMContentLoaded) leer `currentMode = localStorage.getItem(MODE_KEY) || 'gym';` y activar su tab antes del primer render.

- [ ] **Step 2: Banner "Retomar" + "Repetir último"**

Añadir markup al inicio del `.training-selector-body` (~718):

```html
<div id="resume-banner" class="resume-banner" style="display:none;" onclick="resumeDraft()">
  <span class="rb-ic">⏸️</span>
  <div class="rb-tx"><b>Entreno sin terminar</b><span id="resume-meta"></span></div>
  <span class="rb-go">Retomar</span>
</div>
<div class="quick-actions-grid">
  <button onclick="repeatLastWorkout()" class="quick-action-btn">
    <div class="icon-wrap icon-wrap-sm" style="background:rgba(245,158,11,0.16);"><i data-lucide="rotate-cw"></i></div>
    <div style="min-width:0;"><p class="quick-action-title">Repetir último</p><p class="quick-action-desc">tu último de este modo</p></div>
  </button>
  <button onclick="startFreeWorkout()" class="quick-action-btn">
    <div class="icon-wrap icon-wrap-sm" style="background:rgba(59,130,246,0.16);"><i data-lucide="zap"></i></div>
    <div style="min-width:0;"><p class="quick-action-title">Entreno libre</p><p class="quick-action-desc">en blanco</p></div>
  </button>
</div>
```

CSS:
```css
.resume-banner { display:flex; align-items:center; gap:0.6rem; background:#1a1206; border:1px solid rgba(245,158,11,0.35); border-radius:0.9rem; padding:0.7rem 0.85rem; margin-bottom:0.85rem; cursor:pointer; }
.resume-banner .rb-tx { flex:1; } .resume-banner .rb-tx b { font-size:0.8125rem; display:block; } .resume-banner .rb-tx span { font-size:0.6875rem; color:var(--text-muted); }
.resume-banner .rb-go { background:var(--color-primary); color:#000; font-size:0.6875rem; font-weight:800; padding:0.3rem 0.6rem; border-radius:0.45rem; }
```

- [ ] **Step 3: Lógica de retomar / repetir**

```javascript
    function checkResumeBanner() {
        const d = loadDraft();
        const banner = document.getElementById('resume-banner');
        if (!d || !d.exercises) { banner.style.display = 'none'; return; }
        const mins = Math.round((Date.now() - d.startTime) / 60000);
        const sets = d.exercises.flatMap(e => e.sets).filter(s => s.done).length;
        document.getElementById('resume-meta').textContent = `${esc(d.name || 'Entreno')} · ${sets} series · hace ${mins} min`;
        banner.style.display = 'flex';
    }

    function resumeDraft() {
        const d = loadDraft();
        if (!d) return;
        activeWorkout = d;
        showTrainingScreen();   // muestra #training-screen, oculta #phase-select
        renderExercises(); updateProgress(); startElapsedTimer();
        loadPreviousFor(activeWorkout.currentIdx || 0);
    }

    async function repeatLastWorkout() {
        try {
            const res = await apiCall(`/workouts?mode=${currentMode}&per_page=1&sort=desc`);
            const last = (res.data || res)[0];
            if (!last || !last.sets || !last.sets.length) { showToast('No hay un entreno previo de este modo', 'error'); return; }
            const groups = groupSetsByName(last.sets);   // helper local (ver Step 4)
            beginWorkoutFromGroups(last.mode, 'Repetir: ' + formatDateShort(last.date), groups);
        } catch (e) { showToast('No se pudo cargar el último entreno', 'error'); }
    }
```

- [ ] **Step 4: Helpers `groupSetsByName` y `beginWorkoutFromGroups`**

```javascript
    function groupSetsByName(sets) {
        const order = [], map = new Map();
        sets.forEach(s => { if (!map.has(s.name)) { map.set(s.name, []); order.push(s.name); } map.get(s.name).push(s); });
        return order.map(name => ({ name, sets: map.get(name) }));
    }

    // Arranca un entreno a partir de grupos {name, sets:[fila previa...]} (repetir último)
    function beginWorkoutFromGroups(mode, name, groups) {
        activeWorkout = {
            mode, name, templateId: null, startTime: Date.now(), currentIdx: 0,
            exercises: groups.map(g => ({
                name: g.name, target: null, pr: null, previous: null,
                sets: g.sets.map(s => ({ weight_kg: s.weight_kg ?? null, reps: s.reps ?? null, rpe: null,
                    rest_seconds: s.rest_seconds ?? null, done: false })),
            })),
        };
        showTrainingScreen();
        renderExercises(); updateProgress(); startElapsedTimer(); saveDraft();
        loadPreviousFor(0);
    }
```

- [ ] **Step 5: Tarjeta de rutina unificada**

En `renderTemplates` (~1320) dejar **un solo** estilo de tarjeta (`.template-card-v2` renombrado a `.template-card`; borrar las otras dos variantes del CSS y su markup). Cada tarjeta: cabecera (nombre + badge nivel), meta ("N ejercicios · ~M min"), preview de ejercicios, pie con **Iniciar** (primario, `startTemplate(id)`) y **Ver** (`openTemplatePreview`). El lápiz de editar solo en plantillas propias.

- [ ] **Step 6: Llamar a `checkResumeBanner()` al cargar**

En la inicialización (tras `loadTemplates()`), añadir `checkResumeBanner();`.

- [ ] **Step 7: Verificación manual**

Run: `composer run dev`, `/training`.
Expected: el modo persiste al recargar; "Repetir último" clona el último entreno del modo; tras dejar un entreno a medias y volver, aparece el banner "Retomar" y restaura el estado; una sola estética de tarjeta de rutina.

- [ ] **Step 8: Commit**

```bash
git add resources/views/training.blade.php
git commit -m "feat(training): inicio con repetir-ultimo, retomar borrador, modo recordado y tarjeta unica"
```

---

## Tarea 8: Training — resumen con recap por ejercicio

**Files:**
- Modify: `resources/views/training.blade.php` — `#workout-summary-sheet` (~1163-1200) y `renderWorkoutSummary` (~2113-2160).

- [ ] **Step 1: Añadir bloque recap al markup**

Tras la card de PRs (~1193), añadir:

```html
<div class="card" style="padding:0.875rem 1rem;margin-top:0.75rem;">
  <p style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.06em;margin:0 0 0.5rem;">Resumen por ejercicio</p>
  <div id="summary-recap" style="display:flex;flex-direction:column;gap:0.25rem;"></div>
</div>
```

- [ ] **Step 2: Rellenar el recap desde `activeWorkout`**

En la función que muestra el resumen (`finishWorkout`/`renderWorkoutSummary`), antes de limpiar `activeWorkout`, calcular y pintar:

```javascript
    function renderSummaryRecap() {
        const el = document.getElementById('summary-recap');
        el.innerHTML = activeWorkout.exercises.map(ex => {
            const withW = ex.sets.filter(s => s.weight_kg != null);
            const range = withW.length
                ? `${Math.min(...withW.map(s => s.weight_kg))}–${Math.max(...withW.map(s => s.weight_kg))} kg`
                : `${ex.sets.length} series`;
            return `<div style="display:flex;justify-content:space-between;font-size:0.8125rem;padding:0.25rem 0;border-top:1px solid var(--border-light);">
                <span>${esc(ex.name)}</span><span style="color:var(--text-muted);">${ex.sets.length} series · ${range}</span></div>`;
        }).join('');
    }
```

Llamar `renderSummaryRecap()` en el flujo de resumen **antes** de `clearDraft()`/reset.

- [ ] **Step 3: Verificación manual**

Run: `composer run dev`, completar un entreno.
Expected: el resumen muestra stats, PRs y el recap por ejercicio ("Press Banca — 3 series · 80–85 kg").

- [ ] **Step 4: Commit**

```bash
git add resources/views/training.blade.php
git commit -m "feat(training): resumen con recap por ejercicio"
```

---

## Tarea 9: Compatibilidad de Pasos y limpieza de código muerto

**Files:**
- Modify: `resources/views/training.blade.php` — `savePasosWorkout` (~2177) y eliminación de markup/JS obsoletos.

- [ ] **Step 1: Adaptar Pasos al contrato nuevo**

En `savePasosWorkout`, construir el payload con el contrato por serie (una "serie" portadora):

```javascript
    const payload = {
        mode: 'pasos',
        date: new Date().toISOString().slice(0, 10),
        duration_minutes: Number(document.getElementById('pasos-duration').value) || 0,
        calories_burned: Number(document.getElementById('pasos-calories').value) || null,
        exercises: [{ name: 'Pasos del día', sets: [{ reps: Number(document.getElementById('pasos-steps').value) || 0 }] }],
    };
    await apiCall('/workouts', 'POST', payload);
```

- [ ] **Step 2: Eliminar código obsoleto**

Borrar el markup y el JS ya no usados: `#add-set-sheet` (modal de serie) y sus funciones `openAddSet/closeAddSet/confirmAddSet/setRpe/setRestSeconds`; `#rest-overlay` y `startRestTimer/updateRestUI` viejos; los botones `‹ ›` de navegación entre ejercicios (`#exercise-nav`, `prevExercise/nextExercise`) y el `exercise-rail` si quedó sin uso. Verificar que ninguna función viva los referencia (buscar por nombre antes de borrar).

- [ ] **Step 3: Verificación manual**

Run: `composer run dev`. Probar modo Pasos (guarda y aparece su registro) y que el resto del flujo sigue OK sin errores en consola.
Expected: sin referencias rotas; Pasos guarda; consola limpia.

- [ ] **Step 4: Commit**

```bash
git add resources/views/training.blade.php
git commit -m "chore(training): pasos al contrato nuevo y limpieza de modal/overlay obsoletos"
```

---

## Tarea 10: Verificación end-to-end y regenerar deploy

**Files:** ninguno nuevo (verificación + build).

- [ ] **Step 1: Suite de tests backend**

Run: `php artisan test --filter=WorkoutLoggingTest && php artisan test --filter=ExerciseLastSessionTest`
Expected: PASS.

- [ ] **Step 2: Recorrido manual completo**

Comprobar, en `composer run dev`:
- Gym: plantilla → tabla → editar series → ✓ → descanso → Terminar → resumen con PR/recap → aparece en `/history` agrupado.
- Recargar a media sesión → banner "Retomar" restaura.
- "Repetir último" clona el último de Gym.
- Calistenia: "Lastre" opcional, registro sin peso guarda.
- Natación: tabla Distancia/Tiempo, guarda sin romper.
- Pasos: atajo guarda.
- Informe de salud (`/informe-salud`) y reportes (`/stats/reports`) siguen calculando volumen correctamente (filas `sets=1`).

- [ ] **Step 3: Regenerar deploy**

Run: `bash build-deploy.sh`
Expected: `OK -> ./deploy listo.` (incluye el `training.blade.php` y backend nuevos).

- [ ] **Step 4: Commit final**

```bash
git add -A
git commit -m "feat(training): rediseno UX/UI de entrenamientos (tabla por ejercicio) completo"
```

---

## Notas de ejecución

- **Sin migración de BD:** el modelo por serie usa columnas existentes de `exercise_sets`.
- **Entrenos antiguos** (fila agregada `sets=N`) conviven con los nuevos; el historial los agrupa por nombre igual (Tarea 4).
- **Natación/Pasos**: funcionales, no son foco (sin pulido).
- **Tras desplegar**: subir `./deploy/` por FTP como siempre (ver CLAUDE.md). El backend nuevo no requiere pasos extra en el servidor.
