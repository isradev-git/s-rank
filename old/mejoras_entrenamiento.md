# Plan de Mejoras — Funcionalidad de Entrenamientos
**FitLoop · Revisado: 2026-04-01**

---

## Contexto del análisis

Se revisaron las siguientes partes del código:
- `resources/views/training.blade.php` — modo de entrenamiento guiado
- `resources/views/log.blade.php` — registro rápido de rutina
- `resources/views/history.blade.php` — historial de entrenamientos
- `app/Http/Controllers/Api/WorkoutController.php`
- `app/Http/Controllers/Api/TemplateController.php`
- `app/Http/Controllers/Api/ExerciseController.php`
- `routes/api.php`

---

## Problemas detectados (estado actual)

### P1 — Dos páginas que hacen casi lo mismo (`/log` y `/training`)
`training.blade.php` es el modo guiado completo (con timer por ejercicio, progreso, etc.) y `log.blade.php` es el registro rápido. Se solapan: ambas tienen selección de plantilla, lista de ejercicios y botón guardar. Además, `log.blade.php` usa Tailwind CDN mientras que el resto de la app usa CSS propio, lo que rompe la consistencia visual. El FAB principal de la navbar apunta a `/log`, que es la experiencia más pobre.

### P2 — Los sets no se pueden editar
En `training.blade.php`, la tabla de series (`#sets-list`) solo permite añadir o borrar. Si el usuario escribe mal el peso (ej: 100 en vez de 10 kg), debe borrar el set entero y crearlo de nuevo. Esto es especialmente molesto en mitad de un entrenamiento.

### P3 — Sin autocompletado de ejercicios
En el sheet de añadir ejercicio de `log.blade.php`, el input de nombre es texto libre sin sugerencias. El endpoint `GET /api/exercises/history?name=...` ya existe para obtener el último set de un ejercicio, y `GET /api/exercises` devuelve una lista fija de 12 ejercicios. Con el historial del usuario se podría ofrecer autocompletado real.

### P4 — Sin pantalla de resumen al terminar el entrenamiento
La función `finishWorkout()` en `training.blade.php` llama a `POST /api/workouts`, recibe `new_records` en la respuesta y muestra un toast. Pero no hay ninguna pantalla de celebración/resumen que muestre: volumen total, ejercicios completados, tiempo, PRs conseguidos. Apps como Hevy o Strong tienen esto y es clave para la motivación.

### P5 — Vista previa de plantilla antes de iniciar
En `training.blade.php`, al tocar una plantilla se inicia el entrenamiento inmediatamente sin mostrar qué ejercicios contiene. El usuario no puede ver la lista de ejercicios, series y repeticiones antes de comprometerse a iniciar.

### P6 — Las plantillas personalizadas no se pueden editar
`TemplateController` solo tiene `index`, `store` y `destroy`. No hay `PUT /api/templates/{id}`. Si el usuario quiere cambiar un ejercicio de su plantilla, debe borrarla entera y crearla de cero.

### P7 — No hay gráfica de progreso por ejercicio accesible desde la UI
`ExerciseController::progress()` (`GET /api/exercises/progress?name=...`) ya devuelve el histórico de peso/reps por fecha, pero no hay ninguna pantalla en la app que lo muestre como gráfica. La info existe en el backend pero el usuario no puede verla.

### P8 — El selector de plantillas no muestra preview de ejercicios
La tarjeta de plantilla en `training.blade.php` muestra nombre, nivel, modo y número de ejercicios. Pero no muestra cuáles son esos ejercicios (ni siquiera los primeros 3). El usuario tiene que recordar qué hace cada rutina de memoria.

### P9 — Timer de descanso no configurable por defecto
El overlay de descanso en `training.blade.php` tiene presets hardcodeados (±15s, saltar). El tiempo de descanso por defecto que se activa al guardar una serie no está guardado por el usuario — siempre empieza en el valor del `rest_seconds` de la plantilla o en 60s.

### P10 — Inconsistencia visual entre `/log` y `/training`
`log.blade.php` importa Tailwind CDN (`<script src="https://cdn.tailwindcss.com">`), usa clases de Tailwind mezcladas con clases propias y tiene un diseño distinto al resto de la app. Esto genera inconsistencia visual y puede causar conflictos de CSS.

---

## Mejoras propuestas

Las mejoras están ordenadas por impacto y dificultad. Cada una incluye exactamente qué archivos tocar.

---

### MEJORA 1 — Pantalla de resumen post-entrenamiento ⭐ MÁXIMA PRIORIDAD

**Qué hace:** Al terminar el entrenamiento, en lugar de solo mostrar un toast, aparece un bottom-sheet de celebración con los resultados completos.

**Qué muestra el resumen:**
- Tiempo total del entrenamiento (ya se tiene con el timer)
- Número de ejercicios completados
- Número total de series registradas
- Volumen total levantado (suma de `peso_kg × reps × sets` de cada serie)
- Lista de nuevos PRs conseguidos (ya viene en `new_records` de la respuesta del API)
- Botón "Ver en historial" y botón "Cerrar"

**Por qué es importante:** Es el momento de mayor motivación del usuario. Una celebración visual convierte un hábito en una recompensa. Apps como Hevy, Strong y Fitbod tienen esto como feature central.

**Archivos a modificar:**
- `resources/views/training.blade.php`:
  - Añadir el HTML del bottom-sheet de resumen (similar al sheet de añadir serie, pero más grande)
  - Modificar la función JS `finishWorkout()` para que en vez de redirigir, abra el sheet con los datos calculados
  - Calcular volumen total acumulando los sets registrados en `state.exercises`
  - Mostrar la lista de `new_records` que devuelve el API con animación

**Cambios en el API:** Ninguno. El endpoint `POST /api/workouts` ya devuelve `new_records`. Solo hay que aprovechar esa respuesta en el frontend.

**Estimación:** 3–4 horas (solo frontend).

---

### MEJORA 2 — Edición de sets inline ⭐ ALTA PRIORIDAD

**Qué hace:** Al hacer tap en una fila de set ya guardado en `#sets-list`, se abre el mismo sheet de "Añadir serie" pero pre-rellenado con los valores de ese set. En vez de "Guardar serie" el botón dice "Actualizar serie". Al confirmar, se reemplaza el set en el array de estado.

**Por qué es importante:** Elimina la fricción más grande del flujo activo. Si el usuario se equivoca al escribir el peso, no pierde el set, solo lo edita.

**Archivos a modificar:**
- `resources/views/training.blade.php`:
  - Añadir un atributo `data-set-index` a cada fila de set en el HTML generado por JS
  - Modificar `openAddSet()` para aceptar un parámetro opcional `editIndex`
  - Cuando `editIndex` no es null: pre-rellenar los inputs con los valores del set, cambiar el título del sheet a "Editar serie" y el botón a "Actualizar"
  - Modificar `confirmAddSet()` para que si hay `editIndex`, sobreescriba el set en `state.exercises[currentIdx].sets[editIndex]` en vez de hacer push
  - Re-renderizar la lista de sets tras la edición

**Cambios en el API:** Ninguno. Los sets se guardan todos al final con `POST /api/workouts`. La edición es solo en memoria del estado JS.

**Estimación:** 2–3 horas (solo frontend).

---

### MEJORA 3 — Preview de plantilla antes de iniciar ⭐ ALTA PRIORIDAD

**Qué hace:** Al tocar una tarjeta de plantilla, en vez de iniciar el entrenamiento directamente, se abre un bottom-sheet que muestra:
- Nombre y descripción de la plantilla
- Badge de nivel y modo
- Lista completa de ejercicios con sus series, reps y descanso sugerido
- Botón "Iniciar entrenamiento" y botón "Cancelar"

**Por qué es importante:** El usuario puede confirmar que eligió la rutina correcta antes de comprometerse. También sirve como repaso rápido del plan antes de empezar.

**Archivos a modificar:**
- `resources/views/training.blade.php`:
  - Añadir un bottom-sheet de preview (`#template-preview-sheet`) con el HTML de la lista de ejercicios
  - Modificar la función `startTemplate(template)` para que en vez de iniciar directamente, rellene el sheet y lo abra
  - Añadir función `confirmStartTemplate()` que cierre el sheet e inicie el entrenamiento real
  - El sheet muestra: `template.exercises.map(ex => ex.name + " — " + ex.sets + "×" + ex.reps)`

**Cambios en el API:** Ninguno. Los datos de ejercicios ya vienen en el objeto `template.exercises` que se carga al inicio.

**Estimación:** 2 horas (solo frontend).

---

### MEJORA 4 — Autocompletado de ejercicios desde historial

**Qué hace:** Al escribir en el campo de nombre de ejercicio (tanto en `training.blade.php` como en `log.blade.php`), aparece un dropdown con sugerencias de ejercicios que el usuario ha usado antes, filtradas por lo que está escribiendo.

**Por qué es importante:** Hace la app sentirse "inteligente" y personalizada. Evita errores de escritura que crean ejercicios duplicados con nombres distintos (ej: "Press Banca" vs "press banca" vs "Press de banca").

**Nuevo endpoint en el API:**
```
GET /api/exercises/suggestions?q=press
```
Devuelve un array de strings con los nombres de ejercicios del historial del usuario que contienen `q` (LIKE %q%).

**Código del nuevo método en `ExerciseController.php`:**
```php
public function suggestions(Request $request)
{
    $user = $request->user();
    $q    = $request->query('q', '');

    // Busca nombres únicos de ejercicios que el usuario ha hecho antes
    $names = ExerciseSet::whereHas('workout', fn($query) => $query->where('user_id', $user->id))
        ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%"))
        ->select('name')
        ->distinct()
        ->orderByRaw('COUNT(*) DESC')  // Los más usados primero
        ->groupBy('name')
        ->limit(8)
        ->pluck('name');

    return response()->json($names);
}
```

**Nueva ruta en `routes/api.php`** (antes de la ruta `/exercises/history` para evitar conflicto de orden):
```php
Route::get('/exercises/suggestions', [ExerciseController::class, 'suggestions']);
```

**Cambios en el frontend** (`training.blade.php`):
- El campo de nombre de ejercicio libre se convierte en un input con dropdown
- Al escribir >1 carácter, se hace fetch a `/api/exercises/suggestions?q=...` con debounce de 300ms
- Se muestra un dropdown debajo del input con los resultados
- Al seleccionar una sugerencia, se rellena el input y se cierra el dropdown

**Estimación:** 4–5 horas (backend 1h + frontend 3–4h).

---

### MEJORA 5 — Gráfica de progreso por ejercicio

**Qué hace:** Nueva sección en el Historial o en Herramientas que permite al usuario buscar un ejercicio y ver su evolución a lo largo del tiempo: peso máximo por sesión, volumen total por sesión y número de reps.

**Por qué es importante:** Es la feature de análisis más buscada en apps de fitness. Ver que el peso de press banca ha subido de 60kg a 80kg en 3 meses es el mayor motivador a largo plazo.

**El backend ya está listo:** `GET /api/exercises/progress?name=Sentadilla` devuelve array con `{date, weight_kg, reps, sets}` ordenado por fecha. No hay que tocar el backend.

**Archivos a modificar:**
- `resources/views/history.blade.php` (o nueva vista `/tools/progress.blade.php`):
  - Input de búsqueda de ejercicio (con el autocompletado de la Mejora 4)
  - Gráfica Chart.js de línea mostrando `weight_kg` por `date`
  - Selector para cambiar métrica: Peso máximo / Volumen total / Repeticiones
  - Stats resumidos: PR actual, mejor volumen, total de sesiones con ese ejercicio

**Estimación:** 4–5 horas (solo frontend, Chart.js ya se usa en la app para nutrición).

---

### MEJORA 6 — Editar plantillas personalizadas

**Qué hace:** Los botones de las plantillas del usuario tienen un icono de editar además del de borrar. Al tocarlo, se abre un modal/sheet con el formulario de edición: nombre, descripción, nivel, modo y lista de ejercicios con posibilidad de añadir/quitar.

**Nuevo endpoint en el API:**
```
PUT /api/templates/{template}
```

**Código del nuevo método en `TemplateController.php`:**
```php
public function update(Request $request, Template $template)
{
    // Solo el dueño puede editar, no se pueden editar plantillas del sistema
    if ($template->user_id === null) {
        return response()->json(['message' => 'No se pueden editar plantillas del sistema'], 403);
    }
    if ($template->user_id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $validated = $request->validate([
        'name'             => 'required|string|max:100',
        'description'      => 'nullable|string|max:500',
        'level'            => 'nullable|string|in:Básico,Intermedio,Avanzado',
        'mode'             => 'nullable|string|in:gym,home,calisthenics,swimming',
        'duration_minutes' => 'nullable|integer|min:1|max:600',
        'exercises'        => 'required|array|min:1|max:50',
        'exercises.*.name' => 'required|string|max:100',
        'exercises.*.sets' => 'nullable|integer|min:1|max:100',
        'exercises.*.reps' => 'nullable|integer|min:0|max:1000',
    ]);

    $template->update([
        'name'             => $validated['name'],
        'description'      => $validated['description'] ?? $template->description,
        'level'            => $validated['level'] ?? $template->level,
        'mode'             => $validated['mode'] ?? $template->mode,
        'duration_minutes' => $validated['duration_minutes'] ?? $template->duration_minutes,
    ]);

    // Reemplazar ejercicios: borrar todos y crear los nuevos
    $template->exercises()->delete();
    foreach ($validated['exercises'] as $exData) {
        $template->exercises()->create([
            'name' => $exData['name'],
            'sets' => $exData['sets'] ?? 3,
            'reps' => $exData['reps'] ?? 10,
        ]);
    }

    return response()->json($template->load('exercises'));
}
```

**Nueva ruta en `routes/api.php`:**
```php
Route::put('/templates/{template}', [TemplateController::class, 'update']);
```

**Cambios en el frontend** (`training.blade.php`):
- Añadir botón de edición en las tarjetas de plantillas personalizadas
- Crear un modal de edición reutilizando el formulario del modal de creación que ya existe en `log.blade.php`

**Estimación:** 3–4 horas (backend 1.5h + frontend 2h).

---

### MEJORA 7 — Unificar `/log` y `/training` (refactor visual)

**Qué hace:** Eliminar la inconsistencia entre las dos páginas. La opción más limpia es hacer que el FAB de la navbar apunte a `/training` (que es la experiencia completa y ya funciona bien) y deprecar o eliminar `/log`.

**Cambios necesarios:**
- `resources/views/layouts/navbar.blade.php`: cambiar el href del FAB central de `/log` a `/training`
- `log.blade.php`: eliminar el `<script src="https://cdn.tailwindcss.com">` y migrar las clases de Tailwind a las clases CSS propias del proyecto (`.btn`, `.card`, `.input`, etc.)
- Opcionalmente añadir a `log.blade.php` un redirect a `/training`: `Route::get('/log', fn() => redirect('/training'));` en `routes/web.php`

**Por qué es importante:** Evita mantener dos páginas que hacen lo mismo, elimina la carga de Tailwind CDN (que puede causar conflictos) y da al usuario un único punto de entrada claro.

**Estimación:** 2–3 horas.

---

### MEJORA 8 — Timer de descanso configurable por ejercicio

**Qué hace:** En el sheet de "Añadir serie", debajo del RPE, añadir un campo de descanso (segundos) que se guarda por ejercicio. Cuando se confirma la serie, el timer de descanso arranca con ese tiempo en vez del genérico de 60s o el de la plantilla.

**Datos:** El campo `rest_seconds` ya existe en `exercise_sets` de la base de datos y en el estado JS de `training.blade.php`. Solo hay que conectarlo con el timer de descanso.

**Archivos a modificar:**
- `resources/views/training.blade.php`:
  - Añadir en el sheet de añadir serie, debajo del RPE, una fila de presets de descanso (30s, 60s, 90s, 2min, 3min) + un input manual
  - Al confirmar la serie, guardar `rest_seconds` en el objeto del set
  - El `startRest()` debe usar `rest_seconds` del último set en vez del valor hardcodeado

**Estimación:** 2 horas (solo frontend).

---

## Resumen y orden de implementación recomendado

| # | Mejora | Impacto | Dificultad | Backend | Frontend |
|---|--------|---------|------------|---------|----------|
| 1 | Resumen post-entrenamiento | ⭐⭐⭐ Muy alto | Media | No | Sí |
| 2 | Edición de sets inline | ⭐⭐⭐ Muy alto | Media | No | Sí |
| 3 | Preview de plantilla | ⭐⭐ Alto | Baja | No | Sí |
| 4 | Autocompletado de ejercicios | ⭐⭐ Alto | Media | Sí | Sí |
| 5 | Gráfica de progreso | ⭐⭐ Alto | Media | No | Sí |
| 6 | Editar plantillas personalizadas | ⭐ Medio | Media | Sí | Sí |
| 7 | Unificar /log y /training | ⭐⭐ Alto | Baja-Media | No | Sí |
| 8 | Timer de descanso configurable | ⭐ Medio | Baja | No | Sí |

**Orden sugerido para implementar:**
1. Mejora 7 primero (limpieza base — elimina deuda técnica)
2. Mejora 3 (preview de plantilla — rápida y muy visible)
3. Mejora 2 (edición de sets — elimina la mayor fricción)
4. Mejora 1 (resumen post-entrenamiento — impacto motivacional más alto)
5. Mejora 8 (timer de descanso configurable — pequeña pero completa el flujo)
6. Mejora 4 (autocompletado — requiere backend nuevo)
7. Mejora 5 (gráfica de progreso — requiere nueva UI)
8. Mejora 6 (editar plantillas — requiere backend nuevo)

---

## Notas técnicas importantes

- **Chart.js:** Ya se usa en la sección de Nutrición (`/nutrition/history`). Para la Mejora 5, se puede reutilizar el mismo patrón sin instalar nada nuevo.
- **CSS:** Todas las mejoras de frontend deben usar las clases del sistema de diseño propio (`.btn`, `.card`, `.badge`, `.input`, etc. definidas en `public/assets/css/components.css`). No usar Tailwind CDN.
- **Estado JS:** `training.blade.php` usa un objeto `state` en memoria con `state.exercises[i].sets[]`. Las mejoras 1, 2 y 8 operan sobre este mismo estado sin necesidad de cambios en el API.
- **Sanctum CSRF:** Todas las llamadas al API usan `apiCall()` de `public/assets/js/utils.js` que ya maneja el Bearer token. Las nuevas rutas del API siguen el mismo patrón: protegidas con `auth:sanctum`.
- **Cache de plantillas del sistema:** `TemplateController::index()` cachea las plantillas del sistema 1 hora. La Mejora 6 solo edita plantillas de usuario (`user_id != null`), así que no afecta al cache.
