# Rediseño UX/UI de Entrenamientos — Documento de diseño

**Fecha:** 2026-06-16
**Estado:** Aprobado (pendiente revisión final del usuario)
**Autor:** Claude + Isra

## 1. Resumen

Rediseñar la experiencia de **entrenar / registrar** en FitLoop adoptando el patrón de las
apps de referencia (Hevy / Strong): **tabla por ejercicio** donde cada serie es una fila que
se marca como hecha con un ✓, con la sesión anterior y el PR a la vista. Se reescribe el flujo
completo (elegir rutina → registrar → resumen) manteniendo la arquitectura actual (JS inline en
Blade) y reusando el backend, con un único cambio de modelo importante: **almacenamiento por
serie**.

### Problemas que resuelve (prioridades del usuario)
1. **Demasiados toques** al registrar (hoy: ventana modal por cada serie).
2. **Aspecto pobre** (inline styles dispersos, 3 estilos de tarjeta, layout apretado).
3. **Falta de info útil** (no se ve la sesión anterior, ni PR, ni progreso mientras entrenas).
4. **Se pierde el entreno** si cierras o se recarga (estado solo en memoria).

## 2. Alcance

**Dentro:**
- Pantalla de **inicio** (elegir rutina): tarjetas unificadas, "Repetir último", banner "Retomar",
  modo recordado.
- Pantalla de **entreno activo**: nuevo patrón tabla por ejercicio.
- Pantalla de **resumen**: stats + PRs + recap por ejercicio.
- **Modelo por serie** en backend (ver §7).
- **Auto-guardado** del entreno en curso (localStorage).
- Datos **"Anterior"** y **PR** integrados durante el entreno.
- Ajuste mínimo en `history.blade.php` para que el historial agrupe las series por ejercicio
  (consecuencia del modelo por serie).

**Fuera (no se toca / futuro):**
- **Natación**: se queda como está (campos distintos: largos/distancia/estilo).
- **Pasos**: sigue como atajo de 1 toque (no es tabla de series).
- **Gestión avanzada de rutinas** (crear plantilla desde un entreno, reordenar plantillas,
  vídeos de ejercicios): fuera. El editor de plantillas actual se mantiene.
- Supersets, calculadora de discos, 1RM, drag&drop de series: futuro, no en este rediseño.

## 3. Decisiones confirmadas

| Tema | Decisión |
|------|----------|
| Paradigma de registro | **A — Tabla por ejercicio** (Hevy/Strong) |
| Modos con tabla | Gym, Casa, Calistenia (fuerza) |
| Pasos | Atajo de 1 toque, sin tabla |
| Natación | Sin cambios |
| Enfoque técnico | Reestructurar in situ `training.blade.php`, reusar backend |
| Descanso | Barra fina (no overlay a pantalla completa), saltable, ±15s |

## 4. Pantalla: Inicio (elegir rutina)

`#phase-select` reescrita. De arriba a abajo:

1. **Header** "Entrenar" + tabs de modo (Gym/Calistenia/Casa/Natación/Pasos). El modo activo se
   **recuerda** entre visitas (`localStorage: fitloop.training_mode`).
2. **Banner "Retomar"** (solo si hay borrador en curso, §8): "Entreno sin terminar · Push A ·
   3 series · hace 12 min" + botón **Retomar** (amber). Tap → restaura el entreno activo.
3. **Acciones rápidas** (2 botones): **🔁 Repetir último** (clona el último entreno del modo en
   1 toque, vía `GET /workouts?mode=X&per_page=1&sort=desc`) · **⚡ Entreno libre** (en blanco).
4. **Buscador** (rutina o ejercicio) + chips de nivel (Todos/Básico/Intermedio/Avanzado).
5. **Lista de rutinas**: **una sola tarjeta unificada** (hoy conviven `.template-card`,
   `.template-card-v2` y una variante antigua → se eliminan y queda un único componente). Cada
   tarjeta: nombre, badge de nivel, "N ejercicios · ~M min", preview de ejercicios, **Iniciar**
   (primario) + **Ver** (detalle/editar si es propia).

Se elimina la duplicidad "Ver detalle vs Iniciar": **Iniciar** arranca directo; **Ver** abre el
detalle (con Editar si es plantilla propia).

## 5. Pantalla: Entreno activo (núcleo)

`#training-screen` reescrita al patrón tabla. Estructura:

- **Header** (sticky): Cancelar · cronómetro grande (MM:SS) + nombre del entreno · **Terminar**.
- **Barra de progreso**: "X de Y series" + "kg movidos" + barra fina.
- **Lista de ejercicios en scroll** (sin botones ‹ › de navegación). Cada ejercicio es una
  **tarjeta**:
  - Cabecera: nombre · **badge 🏆 PR** (récord actual del ejercicio) · **⋮** (reordenar / quitar
    ejercicio). Las notas son a nivel de entreno (`Workout.notes`), editables al Terminar; no hay
    notas por ejercicio (el modelo no las tiene).
  - Subtítulo opcional: "Objetivo 4×8 · descanso 90s" (si viene de plantilla).
  - **Tabla de series**. Columnas: `Serie · Anterior · Kg · Reps · RPE · ✓`.
    - **Serie**: índice.
    - **Anterior**: lo que hiciste esa misma serie la última sesión (§9). Solo lectura, gris.
    - **Kg / Reps**: celdas **editables inline** (input nativo `inputmode`, sin modal). Para
      Calistenia la columna **Kg** se etiqueta "Lastre" y es opcional.
    - **RPE**: celda opcional (selector compacto 1-10 al tocar).
    - **✓**: marca la serie como hecha (fila verde) y **arranca el descanso** (§ barra fina).
    - Prefill: al añadir una serie, Kg/Reps se prerellenan con la serie anterior de la misma fila
      (o la última serie registrada).
  - **+ Añadir serie** (pie de la tabla).
  - Las tarjetas no activas se muestran **plegadas** (nombre + "0/4"); tap para expandir.
- **+ Añadir ejercicio** (al final; abre buscador con sugerencias del historial).
- **Barra de descanso fina** (cuando hay descanso activo): aparece sobre el teclado, no overlay
  negro. Muestra cuenta atrás + −15 / +15 / Saltar. Se puede ignorar y seguir registrando.

### Interacción "1 toque por dato"
Tocar celda Kg/Reps → foco directo en el input (teclado numérico del SO). Sin abrir ventana por
serie (hoy son ~8 toques/serie; objetivo ~3).

## 6. Pantalla: Resumen

`#workout-summary-sheet` mejorada:
- Cabecera "¡Entreno completado!".
- Stats (2×2): Tiempo · Volumen (kg) · Series · Ejercicios.
- **🏆 Nuevos récords** (de `new_records` que ya devuelve `store()`).
- **Recap por ejercicio**: lista "Press Banca — 4 series · 80–85 kg".
- Botones: **Ver historial** · **Hecho**.

## 7. Modelo de datos: almacenamiento por serie (cambio clave)

**Hoy:** `WorkoutController@store` crea **una fila `ExerciseSet` por ejercicio** (agregado:
`sets`=nº de series, un único `reps`/`weight_kg`). No puede representar series con peso/reps
distintos → incompatible con la tabla.

**Nuevo:** `store()` guarda **una fila `ExerciseSet` por serie registrada**, con `sets = 1`.
No requiere migración (la tabla `exercise_sets` ya tiene todas las columnas).

### Contrato de payload nuevo (`POST /workouts`)
```json
{
  "mode": "gym",
  "date": "2026-06-16",
  "duration_minutes": 48,
  "exercises": [
    {
      "name": "Press Banca",
      "sets": [
        { "weight_kg": 80, "reps": 8, "rpe": 7, "rest_seconds": 90 },
        { "weight_kg": 80, "reps": 7, "rpe": 7, "rest_seconds": 90 },
        { "weight_kg": 85, "reps": 6, "rpe": 9, "rest_seconds": 120 }
      ]
    }
  ]
}
```
`store()` expande cada elemento de `sets[]` a una fila `ExerciseSet` (con `sets=1`). Validación
nueva: `exercises.*.sets` array; `exercises.*.sets.*.weight_kg|reps|rpe|rest_seconds`.

### Detección de PR
Por ejercicio = `max(weight_kg)` sobre sus `sets[]`, comparado con el PR previo (la lógica actual
ya consulta el PR previo; se adapta para iterar las series).

### Compatibilidad de lectura (`sets=1` por fila)
- **Volumen** (`reports`, resumen): `Σ weight·reps·sets` → con `sets=1` queda `Σ weight·reps`,
  correcto. **Importante:** las filas por serie deben llevar `sets=1` (no null) para no anular el
  volumen.
- **`/exercises/records`** (PR), **`top_exercises`**, **`/stats/*`**: siguen funcionando (suman
  filas).
- **Workouts antiguos** (fila agregada con `sets=N`) siguen mostrándose; el display agrupa por
  nombre (abajo).

### Ajustes de lectura necesarios
- **`history.blade.php`**: la preview de tarjeta y el detalle deben **agrupar series por nombre**
  de ejercicio (hoy listan filas sueltas → con per-serie saldrían duplicados). Ej.: "Press
  Banca — 3 series: 80×8, 80×7, 85×6".
- **`/exercises/progress`**: agrupar por fecha tomando la **mejor serie** (max weight) del día,
  para que el gráfico de progreso no pinte varios puntos por sesión.

## 8. Persistencia (auto-guardado / retomar)

Solo cliente (sin backend). `localStorage` key `fitloop.active_workout`:
```json
{
  "mode": "gym", "name": "Push A", "templateId": "uuid|null",
  "startTime": 1781600000000, "currentIdx": 0,
  "exercises": [
    { "name": "Press Banca", "target": {"sets":4,"reps":8,"rest":90},
      "sets": [ {"weight_kg":80,"reps":8,"rpe":7,"rest_seconds":90,"done":true} ] }
  ]
}
```
- Se guarda en cada cambio (debounce ~400ms).
- Al cargar `/training`: si existe borrador no finalizado → **banner "Retomar"**.
- Al **Terminar** o **Cancelar** → se borra.
- El cronómetro se reanuda desde `startTime`.

## 9. Datos "Anterior" y PR

- **PR badge**: `GET /exercises/records` (ya existe). Fetch al iniciar el entreno; se mapea por
  nombre → badge en la cabecera de cada ejercicio.
- **Columna "Anterior"** (por serie): **nuevo endpoint** `GET /exercises/last-session?name=X` →
  devuelve las series de la **última sesión** que contiene ese ejercicio:
  `[{ "weight_kg":80, "reps":8, "rpe":7 }, ...]`. La fila N de la tabla muestra la serie N de esa
  respuesta (o la última si hay menos). Se pide de forma perezosa al expandir/renderizar cada
  ejercicio y se cachea en JS.
  - *Por qué un endpoint nuevo:* `/exercises/history` solo devuelve **una** fila (la última
    serie suelta), insuficiente para "anterior por serie".

## 10. Cambios backend (lista)

1. `WorkoutController@store`: nuevo contrato `exercises[].sets[]`; expandir a filas por serie
   (`sets=1`); detección PR sobre `sets[]`. (Único cliente es la web → se cambia el contrato sin
   compatibilidad hacia atrás.)
2. `ExerciseController@lastSession` (**nuevo**) + ruta `GET /exercises/last-session`.
3. `ExerciseController@progress`: agrupar por fecha (mejor serie del día).
4. (Sin migración de BD.)

## 11. Cambios frontend (lista)

1. `training.blade.php`: reescritura de `#phase-select` (§4), `#training-screen` (§5),
   `#workout-summary-sheet` (§6). Edición inline en celdas. Barra de descanso fina (sustituye
   `rest-overlay`). Auto-guardado + retomar (§8). Modo recordado. "Repetir último". Eliminar
   estilos inline redundantes y las 3 variantes de tarjeta → una sola.
2. `history.blade.php`: agrupar series por ejercicio en preview y detalle (§7).
3. Pasos: conservar el sheet actual de pasos como atajo (sin tabla).

## 12. Casos límite

- **Calistenia / Casa sin peso**: columna Kg opcional ("Lastre"); una serie puede tener solo
  reps. El volumen de esas filas es 0 (peso null), correcto.
- **Ejercicio sin "Anterior"** (primera vez): columna vacía, sin error.
- **Entreno cruzando medianoche**: `date` se fija al iniciar (no al guardar).
- **Borrador viejo** (días): el banner Retomar muestra la antigüedad; el usuario decide.
- **Series marcadas pero no guardadas**: al Terminar se envían todas las series con datos
  (tengan ✓ o no); una serie sin reps ni peso se descarta.
- **Workouts antiguos** (modelo agregado): se muestran agrupados igual; no se migran.

## 13. Pruebas

- **Test de feature** (`tests/Feature`): `store()` con payload por serie crea N filas
  `ExerciseSet` con `sets=1`, calcula volumen y detecta PR correctamente; y un caso de
  Calistenia sin peso.
- **Test de feature**: `GET /exercises/last-session?name=X` devuelve las series de la última
  sesión.
- **Verificación manual**: flujo completo en Gym (plantilla → tabla → ✓ → descanso → Terminar →
  resumen → aparece en historial agrupado) y "Retomar" tras recargar.

## 14. Futuro (fuera de este rediseño)

Supersets, calculadora de discos / 1RM, vídeos de ejercicios, drag&drop de series, guardar
plantilla desde un entreno, tabla rica de tiempo/distancia, rediseño de Natación.
