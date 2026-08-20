# Fase 1.3 · Nutrición, agua, suplementos y actividad — Plan de implementación

> **Para quien ejecute esto por agentes:** SUB-SKILL OBLIGATORIA: usa
> `superpowers:subagent-driven-development` (recomendado) o
> `superpowers:executing-plans` para implementar tarea por tarea. Los pasos van con
> casilla (`- [ ]`) para poder marcarlos.

**Objetivo:** registrar la comida, el agua, los suplementos, los pasos y el peso del día
desde el móvil con el menor número de toques posible, y que el Sistema diga qué se ha
ganado.

**Arquitectura:** todo el frontend. `api.ts` sigue siendo la única puerta a la API y la que
traduce los errores; `formato.ts` guarda los cálculos con ramas para poderlos probar sin
montar React; `recientes.ts` es el único sitio nuevo que menciona `localStorage`, igual que
`borrador.ts` en la 1.2. Agua, suplementos, actividad y peso son secciones dentro de «hoy»,
no pantallas. El XP y las misiones los decide el servidor y llegan en el bloque `system` de
cada respuesta.

**Tecnología:** React 19 + TypeScript sobre Vite 8, tests con Vitest 4 y
`@testing-library/react` en `jsdom`. Backend Laravel con PHPUnit. **Sin dependencias
nuevas**: el redimensionado de fotos va con `<canvas>`, que es del navegador.

**Diseño aprobado:** [`../specs/2026-08-20-fase-1-3-nutricion-design.md`](../specs/2026-08-20-fase-1-3-nutricion-design.md)

---

## Restricciones globales

Valen para **todas** las tareas. No se repiten en cada una.

1. **Todo en español:** código, comentarios, nombres de función, textos de pantalla y
   mensajes de commit. El porqué en el cuerpo del commit, no solo el qué.
2. **Sin vocabulario de terminal en pantalla.** «Proteínas», «grasas», «hidratos», «vaso».
   Los `[✓]`, `//`, `▸`, `▓` y `$` son decoración: van `aria-hidden="true"` y el estado se
   dice con palabras. Un suplemento tomado se oye «Multivitaminas, tomado», nunca
   «corchete, marca, corchete».
3. **Sin iconos ni emoji.** En ninguna pantalla, en ningún estado. Las tablas de `old/`
   traen `icon:` en cada entrada: **no se portan.**
4. **⚠️ Ningún `--nombre: #rrggbb` nuevo en `estilos.css`.** `estilos.test.ts` extrae con
   una expresión regular todas las propiedades personalizadas que valen un hexadecimal y
   compara `toEqual` contra los once colores del spec. Una propiedad nueva **rompe ese
   test**. El CSS nuevo usa `var(--ambar)`, `var(--verde)`, etc. y nada más. Las tablas de
   `old/` traen `color: '#60a5fa'`: **tampoco se portan.**
5. **El cian es exclusivo de la ventana del Sistema.** Si aparece en la interfaz normal,
   deja de significar «premio».
6. **Objetivos táctiles de 48 px** en todo lo que se pulse.
7. **El XP y las misiones los calcula el servidor.** La app los pinta, nunca los decide,
   nunca los estima. La sección de suplementos enseña «2 de 4» y no adelanta si la misión
   está cumplida.
8. **La fecha se manda siempre explícita**, tomada del `date` de `/api/system/today`. Se
   lee y se escribe en UTC, sin convertir a la zona del navegador.
9. **Ningún número de error HTTP llega a la pantalla.** `api.ts` ya traduce; las pantallas
   enseñan `error.fallo.general`.
10. **Un test que no falla sin el arreglo no vale.** En cada tarea, el paso «comprobar que
    falla» es obligatorio y no se salta.
11. Comandos del frontend: `cd web && npm test` (toda la suite), `npm test -- <fichero>`
    (uno solo), `npm run build` (comprueba tipos con `tsc -b`).
12. Comandos del backend: `cd backend && php artisan test`,
    `php artisan test --filter=<NombreDelTest>`.

### Datos reales de la API, ya verificados

No hay que ir a mirarlos: están comprobados contra el backend de esta rama.

- **`meal_type` son cuatro cadenas en inglés:** `breakfast`, `lunch`, `dinner`, `snack`. En
  pantalla se escriben Desayuno, Comida, Cena y Tentempié.
- **`GET /api/meals` agrupa las comidas en un objeto** `{breakfast: {items, calories}, …}`,
  y **cuando el día está vacío Laravel lo serializa como `[]`**, no como `{}`.
- **Los macros de `food_items` y `meal_logs` son `decimal(7,2)`,** que MySQL devuelve como
  cadena, **pero los dos modelos los castean a `float`**: llegan como número. `users.weight`
  **no** está casteado y hay que pasarlo por `Number()`, igual que ya se hace con
  `max_weight`.
- `POST /api/meals` admite dos formas: `food_item_id` + `quantity_grams` (el servidor
  calcula los macros), o `custom_food_name` + `calories` y los macros que se sepan. Sin
  ninguna de las dos responde 422. `quantity_grams` va de 1 a 5000 y por defecto es 100.
- `DELETE /api/meals/{uuid}` **es idempotente**: si ya no existe responde 200 con
  «Entrada ya eliminada.», no un 404.
- `GET /api/foods?search=&limit=` devuelve `{foods: [...]}`. Cada alimento trae
  `calories_per_100g`, `protein_per_100g`, `carbs_per_100g`, `fat_per_100g`,
  `fiber_per_100g`, `sugar_per_100g`, `unit` (`g` o `ml`) e `is_verified`.
- `POST /api/water` acepta `amount_ml` de **1 a 2000**. `PUT /api/water/goal` acepta
  `goal_ml` de **500 a 6000**; por defecto son 2000. La respuesta del POST trae
  `total_ml`, `goal_ml`, `pct` y `system`.
- `PUT /api/supplements` exige `date`, `supplement_key` y `taken`. Las cuatro claves son
  **`multivitaminas`, `omega3`, `vitamina_d`, `magnesio`** — ojo, `vitamina_d` sin el 3.
  Devuelve `{message, system}` y **no** devuelve el estado actualizado.
- `PUT /api/activity` exige `steps` (0–150000) **y `calories_burned` (0–10000), los dos
  obligatorios**. Devuelve `{message, date, steps, calories_burned}`, y con la tarea 2
  también `system`.
- `PUT /api/user/profile` acepta `gender`, `age`, `main_goal`, `weight`, `height`,
  `weekly_goal`. Devuelve `{message, user, system}`, y **`system` es `null` si el peso no
  cambió**.
- `GET /api/nutrition/goal` devuelve `{goal, has_goal}`. Con `has_goal: false` el `goal` es
  una sugerencia calculada, no algo guardado. `PUT /api/nutrition/goal` exige
  `daily_calories` (500–10000), `target_protein`, `target_carbs`, `target_fat` y
  `goal_type` (`lose_weight` | `maintain` | `gain_muscle`).
- `GET /api/user` devuelve **el modelo `User` entero**, así que `weight`, `height`, `age` y
  `gender` ya están en memoria desde que arranca la aplicación.
- `GET /api/recipes` devuelve `{recipes: [...]}` con los macros **por ración**. Hay 26
  recetas del sistema sembradas. `POST /api/recipes` exige `name`, `category`
  (`desayuno` | `almuerzo` | `cena` | `snack`) y `calories_per_serving`.
- Las imágenes se leen de `image_path` y se pintan desde `/uploads/{image_path}`. El
  límite del servidor es **2 MB** y acepta `jpeg`, `png` y `webp`.

---

## Estructura de ficheros

| Fichero | Responsabilidad | Tareas |
|---|---|---|
| `backend/app/Http/Controllers/Api/FoodController.php` | Arreglo: la URL de la imagen. | 1 |
| `backend/public/.htaccess` | Arreglo: `img-src` deja pasar `blob:`. | 1 |
| `backend/app/Http/Controllers/Api/ActivityController.php` | Arreglo: publica el evento. | 2 |
| `web/src/formato.ts` | Crece: macros por cantidad, textos de nutrición, Mifflin-St Jeor. | 3–4 |
| `web/src/recientes.ts` | **NUEVO.** «Lo de siempre», en `localStorage`. Sin React. | 5 |
| `web/src/api.ts` | Crece: nutrición, hábitos y `subir()` para las fotos. | 6–7 |
| `web/src/componentes.tsx` | Crece: `Casilla`, `Contador`, `ChipComida`, `FilaMacros`, `FotoElegible`. | 8, 14 |
| `web/src/estilos.css` | Crece: las clases de lo anterior. Sin colores nuevos. | 8, 14 |
| `web/src/pantallas/Nutricion.tsx` | **NUEVO.** El día, las cuatro comidas y los totales. | 9 |
| `web/src/pantallas/AnadirComida.tsx` | **NUEVO.** Recientes, buscador y entrada a mano. | 10–12 |
| `web/src/pantallas/CrearAlimento.tsx` | **NUEVO.** Alimento propio, con foto. | 13–14 |
| `web/src/pantallas/habitos.tsx` | **NUEVO.** Agua, suplementos, actividad y peso. | 15–17 |
| `web/src/pantallas/Hoy.tsx` | Crece: monta las secciones y el resumen de nutrición. | 18 |
| `web/src/pantallas/Objetivo.tsx` | **NUEVO.** El asistente de tres pasos. | 19 |
| `web/src/pantallas/Recetas.tsx` | **NUEVO.** Lista, detalle, usar y crear. | 20–21 |
| `web/src/App.tsx` | Crece: cada tarea de pantalla añade su propia ruta. | 9–13, 19–21 |
| — | Comprobación en el móvil y cierre de la fase. | 22 |

**Cada tarea que crea una pantalla añade su ruta a `App.tsx` en el mismo commit.** Así no
queda ningún estado intermedio con un enlace que no lleva a ninguna parte.

`recientes.ts` acaba en unas 90 líneas y es, junto a `borrador.ts`, el único sitio del
proyecto que menciona `localStorage`.

---

## Tarea 1 · Que la foto se pueda ver: la URL y la CSP

`FoodController::uploadImage` devuelve la URL bajo `/storage`, que en Ginernet no existe
porque no hay symlink. Y la CSP bloquea `blob:`, que es lo que usa la vista previa. Los dos
arreglos hacen falta para lo mismo, así que van juntos.

**Ficheros:**
- Modificar: `backend/app/Http/Controllers/Api/FoodController.php:274`
- Modificar: `backend/public/.htaccess:13`
- Test: `backend/tests/Feature/UploadDiskTest.php`

**Interfaces:**
- Consume: nada.
- Produce: `POST /api/foods/{id}/image` devuelve `image_url` bajo `/uploads`. La tarea 14
  depende de ello.

- [ ] **Paso 1 · Escribir el test que falla**

En `backend/tests/Feature/UploadDiskTest.php`, después del test que ya existe:

```php
    /**
     * El disco `uploads` apunta a public/uploads y en Ginernet no hay symlink, así que
     * una URL bajo /storage da 404. El test que ya existía solo miraba que el fichero
     * aterrizara en disco, y con la URL rota pasaba igual.
     *
     * ⚠️ El segundo argumento de Storage::fake NO es opcional aquí. `fake()` no hereda la
     * configuración del disco de verdad: crea uno local con la raíz en un temporal y sin
     * `url`, y sin `url` el adaptador local devuelve /storage/... para todo. Sin esta
     * línea el test fallaría con el arreglo puesto, que es lo contrario de lo que sirve.
     */
    public function test_la_url_de_la_imagen_apunta_a_uploads_y_no_a_storage()
    {
        Storage::fake('uploads', ['url' => config('filesystems.disks.uploads.url')]);

        $user = User::factory()->create();
        $food = FoodItem::create([
            'name'              => 'Pollo',
            'calories_per_100g' => 165,
            'protein_per_100g'  => 31,
            'carbs_per_100g'    => 0,
            'fat_per_100g'      => 3.6,
            'user_id'           => $user->id,
        ]);

        $url = $this->actingAs($user, 'sanctum')
            ->post("/api/foods/{$food->id}/image", ['image' => UploadedFile::fake()->image('pollo.jpg')])
            ->assertOk()
            ->json('image_url');

        $this->assertStringContainsString('/uploads/', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd backend && php artisan test --filter=UploadDiskTest`
Esperado: FALLA en `test_la_url_de_la_imagen_apunta_a_uploads_y_no_a_storage`, porque la
URL devuelta contiene `/storage/`.

- [ ] **Paso 3 · Arreglar la URL**

En `backend/app/Http/Controllers/Api/FoodController.php`, línea 274, dentro de
`uploadImage()`:

```php
        return response()->json([
            'message'    => 'Imagen subida correctamente.',
            'image_path' => $path,
            // Storage::disk('uploads')->url() y no asset('storage/...'): el disco apunta a
            // public/uploads y en Ginernet no se pueden crear symlinks, así que /storage
            // no existe. RecipeController ya lo hacía bien; esto lo iguala.
            'image_url'  => Storage::disk('uploads')->url($path),
        ]);
```

Y en el `catch` de más arriba, el mensaje menciona un enlace `/storage` que aquí no pinta
nada:

```php
            return response()->json([
                'message' => 'No se pudo guardar la imagen en el servidor. Vuelve a intentarlo.',
            ], 500);
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd backend && php artisan test --filter=UploadDiskTest`
Esperado: PASA, los tres tests del fichero.

- [ ] **Paso 5 · Abrir la CSP a `blob:`**

En `backend/public/.htaccess`, línea 13, cambia **solo** la directiva `img-src`:

```
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self'; font-src 'self'; img-src 'self' blob:; connect-src 'self'; manifest-src 'self'; worker-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'"
```

Y justo encima, en el comentario del bloque, deja escrito por qué:

```
    # img-src lleva blob: porque la vista previa de una foto antes de subirla se pinta
    # desde un objeto de la propia página (canvas → blob), no desde una URL del servidor.
    # Con 'self' a secas el hueco sale en blanco y NO hay ningún error visible en la
    # interfaz. En local no se ve: ahí no hay Apache que aplique esta cabecera.
```

**Esto no tiene test.** La suite corre sin Apache. Se comprueba en la tarea 22, en el
servidor y con una foto de verdad.

- [ ] **Paso 6 · Commit**

```bash
git add backend/app/Http/Controllers/Api/FoodController.php backend/public/.htaccess backend/tests/Feature/UploadDiskTest.php
git commit -m "$(cat <<'EOF'
fix(alimentos): la foto de un alimento no se podía ver

La URL que devolvía uploadImage apuntaba a /storage, y ese directorio no
existe: el disco «uploads» va a public/uploads justamente porque Ginernet
no deja crear symlinks. RecipeController ya lo hacía bien.

UploadDiskTest solo comprobaba que el fichero llegara al disco, así que la
suite estaba verde con la imagen rota. El test nuevo mira la URL.

Y la CSP llevaba img-src 'self', que también bloquea blob:. La vista previa
de la foto antes de subirla se pinta desde un blob de la propia página, así
que habría salido un hueco en blanco solo en el servidor y sin ningún error
a la vista. Eso no se puede probar aquí: la suite corre sin Apache.
EOF
)"
```

---

## Tarea 2 · Los pasos mueven la racha

`ActivityController::upsert()` crea el `Workout` de modo `pasos` pero no publica ningún
evento, así que `SystemService::afterWorkout()` nunca corre y `current_streak` no se mueve.
`afterWorkout()` ya se salta el XP cuando el modo es `pasos` y llama a `touchStreak()`
igual, así que basta con publicar el evento.

**Ficheros:**
- Modificar: `backend/app/Http/Controllers/Api/ActivityController.php`
- Test: `backend/tests/Feature/ActividadDiariaTest.php` (crear)

**Interfaces:**
- Consume: `App\Events\WorkoutStored`, que ya existe y tiene la forma
  `__construct(public Workout $workout, public array $newRecords = [])` más una propiedad
  pública `array $rewards` que rellena el listener.
- Produce: `PUT /api/activity` devuelve un bloque `system`. La tarea 17 lo pinta.

- [ ] **Paso 1 · Escribir el test que falla**

Crea `backend/tests/Feature/ActividadDiariaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los pasos son actividad diaria, no entrenamiento. La diferencia tiene dos mitades y
 * las dos importan: NO dan XP de entreno, pero SÍ marcan el día como activo y mantienen
 * la racha. Hay quien usa la aplicación solo para eso.
 */
class ActividadDiariaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardar_pasos_mueve_la_racha_y_devuelve_el_bloque_system()
    {
        $user = User::factory()->create();

        $respuesta = $this->actingAs($user, 'sanctum')
            ->putJson('/api/activity', [
                'date'            => now()->toDateString(),
                'steps'           => 8200,
                'calories_burned' => 0,
            ])
            ->assertOk();

        $this->assertSame(1, $respuesta->json('system.progress.current_streak'));
    }

    public function test_guardar_pasos_no_da_xp_de_entreno()
    {
        $user = User::factory()->create();

        $respuesta = $this->actingAs($user, 'sanctum')
            ->putJson('/api/activity', [
                'date'            => now()->toDateString(),
                'steps'           => 8200,
                'calories_burned' => 0,
            ])
            ->assertOk();

        // El bonus de racha sí puede sumar; lo que no puede aparecer es XP de entreno.
        $this->assertDatabaseMissing('xp_ledger', [
            'user_id' => $user->id,
            'source'  => 'workout',
        ]);
    }
}
```

⚠️ Antes de escribirlo, **comprueba el nombre real de la tabla y de la columna** del
registro de XP: `grep -n "table\|'source'" backend/app/System/XpLedger.php`. Si no
coinciden con `xp_ledger` y `source`, usa los que haya — el test tiene que afirmar sobre
lo que existe, no sobre lo que este plan supuso.

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd backend && php artisan test --filter=ActividadDiariaTest`
Esperado: el primer test FALLA porque `system` llega como `null` y
`system.progress.current_streak` no existe. El segundo pasa desde ya, y así tiene que ser:
comprueba que el arreglo no rompe lo que ya estaba bien.

- [ ] **Paso 3 · Publicar el evento**

En `backend/app/Http/Controllers/Api/ActivityController.php`, añade el `use` arriba:

```php
use App\Events\WorkoutStored;
```

Y al final de `upsert()`, sustituye el `return`:

```php
        // El módulo avisa; el Sistema decide. afterWorkout() ya se salta el XP cuando el
        // modo es «pasos» y llama a touchStreak() igual, así que publicar el evento es
        // todo lo que hace falta para que apuntar los pasos mantenga la racha.
        $event = new WorkoutStored($log);
        event($event);

        return response()->json([
            'message'         => 'Actividad diaria guardada.',
            'date'            => Carbon::parse($log->date)->format('Y-m-d'),
            'steps'           => (int) $validated['steps'],
            'calories_burned' => (int) round((float) $log->calories_burned),
            'system'          => $event->rewards,
        ]);
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd backend && php artisan test --filter=ActividadDiariaTest`
Esperado: PASAN los dos.

Y la suite entera, que este cambio toca el Sistema:
`cd backend && php artisan test`
Esperado: 285 tests o más, todos en verde.

- [ ] **Paso 5 · Commit**

```bash
git add backend/app/Http/Controllers/Api/ActivityController.php backend/tests/Feature/ActividadDiariaTest.php
git commit -m "$(cat <<'EOF'
fix(actividad): apuntar los pasos no mantenía la racha

ActivityController creaba el Workout de modo «pasos» pero no publicaba
ningún evento, así que SystemService::afterWorkout() no llegaba a correr y
current_streak se quedaba donde estaba. AchievementService sí cuenta los
pasos como día activo, pero eso solo se aplica al pasar srank:recalculate,
de modo que la racha aparecía mal hasta el siguiente recálculo.

Publicar WorkoutStored basta: afterWorkout() ya se salta el XP cuando el
modo es «pasos» y llama a touchStreak() igual. El módulo sigue sin mencionar
al Sistema.

De paso, la respuesta devuelve el bloque system como hacen agua y comidas,
así que la sección de actividad puede pintar la racha sin pedir nada más.
EOF
)"
```

---

## Tarea 3 · `formato.ts`: macros por cantidad y los textos de nutrición

**Ficheros:**
- Modificar: `web/src/formato.ts`
- Test: `web/src/formato.test.ts`

**Interfaces:**
- Consume: nada.
- Produce: el tipo `Macros`; `macrosPara(porCien, cantidad): Macros`,
  `textoRestante(consumidas, objetivo): string`, `textoAgua(totalMl, objetivoMl): string`.

- [ ] **Paso 1 · Escribir el test que falla**

Al final de `web/src/formato.test.ts`:

```ts
import { macrosPara, textoAgua, textoRestante } from "./formato";

const POLLO = {
  calories_per_100g: 165,
  protein_per_100g: 31,
  carbs_per_100g: 0,
  fat_per_100g: 3.6,
  fiber_per_100g: 0,
  sugar_per_100g: 0,
};

test("los macros se calculan por regla de tres sobre 100 g", () => {
  expect(macrosPara(POLLO, 150)).toEqual({
    calories: 247.5, protein: 46.5, carbs: 0, fat: 5.4, fiber: 0, sugar: 0,
  });
});

test("los macros se redondean a dos decimales, como el servidor", () => {
  // 3,6 × 0,33 = 1,188. El servidor hace round(..., 2) y devuelve 1.19: si aquí
  // saliera 1.188, la cifra cambiaría sola al recargar la pantalla.
  expect(macrosPara(POLLO, 33).fat).toBe(1.19);
});

test("una cantidad de cero da todo a cero y no NaN", () => {
  expect(macrosPara(POLLO, 0).calories).toBe(0);
});

test("el texto de lo que queda distingue quedarse corto de pasarse", () => {
  expect(textoRestante(1360, 2000)).toBe("te quedan 640 kcal");
  expect(textoRestante(2120, 2000)).toBe("te has pasado en 120 kcal");
  expect(textoRestante(2000, 2000)).toBe("has llegado justo a tu objetivo");
});

test("sin objetivo nutricional el texto lo dice en vez de inventarse un número", () => {
  expect(textoRestante(1360, null)).toBe("todavía sin objetivo");
});

test("el agua se cuenta en litros con coma, que es como se dice en español", () => {
  expect(textoAgua(1500, 2000)).toBe("1,5 de 2 litros");
  expect(textoAgua(0, 2000)).toBe("0 de 2 litros");
  // Pasarse está bien y no se recorta: bebiste lo que bebiste.
  expect(textoAgua(2250, 2000)).toBe("2,25 de 2 litros");
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- formato`
Esperado: FALLA al importar — `macrosPara`, `textoRestante` y `textoAgua` no existen.

- [ ] **Paso 3 · Escribir la implementación mínima**

Al final de `web/src/formato.ts`:

```ts
/** Los seis macros de una comida, ya en la cantidad que se comió. */
export type Macros = {
  calories: number;
  protein: number;
  carbs: number;
  fat: number;
  fiber: number;
  sugar: number;
};

/** Lo que trae un alimento del catálogo: siempre por 100 g o por 100 ml. */
export type PorCien = {
  calories_per_100g: number;
  protein_per_100g: number;
  carbs_per_100g: number;
  fat_per_100g: number;
  fiber_per_100g: number;
  sugar_per_100g: number;
};

/** Dos decimales, igual que `FoodItem::macrosForQuantity()`. Que no coincidan sería peor
 *  que no calcularlo aquí: la cifra que se enseña antes de guardar cambiaría sola al
 *  recargar la pantalla con la que devolvió el servidor. */
const dosDecimales = (n: number) => Math.round(n * 100) / 100;

export function macrosPara(porCien: PorCien, cantidad: number): Macros {
  const factor = cantidad / 100;
  return {
    calories: dosDecimales(porCien.calories_per_100g * factor),
    protein: dosDecimales(porCien.protein_per_100g * factor),
    carbs: dosDecimales(porCien.carbs_per_100g * factor),
    fat: dosDecimales(porCien.fat_per_100g * factor),
    fiber: dosDecimales(porCien.fiber_per_100g * factor),
    sugar: dosDecimales(porCien.sugar_per_100g * factor),
  };
}

/** Español, con coma decimal y sin ceros de relleno. */
const CIFRA = new Intl.NumberFormat("es-ES", { maximumFractionDigits: 2 });

/** `objetivo` a null es «este usuario no ha configurado nada», que no es lo mismo que un
 *  objetivo de cero. Inventarle un número sería darle un dato de salud falso. */
export function textoRestante(consumidas: number, objetivo: number | null): string {
  if (objetivo == null) return "todavía sin objetivo";

  const diferencia = Math.round(objetivo - consumidas);
  if (diferencia === 0) return "has llegado justo a tu objetivo";
  if (diferencia > 0) return `te quedan ${CIFRA.format(diferencia)} kcal`;
  return `te has pasado en ${CIFRA.format(-diferencia)} kcal`;
}

/** «1,5 de 2 litros». En mililitros se lee fatal y en la barra no cabe. Pasarse no se
 *  recorta: el `pct` del servidor sí topa en 100, pero el litraje es lo que se bebió. */
export function textoAgua(totalMl: number, objetivoMl: number): string {
  return `${CIFRA.format(totalMl / 1000)} de ${CIFRA.format(objetivoMl / 1000)} litros`;
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- formato`
Esperado: PASAN todos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/formato.ts web/src/formato.test.ts
git commit -m "$(cat <<'EOF'
feat(nutrición): los macros por cantidad y los textos con ramas

macrosPara redondea a dos decimales igual que FoodItem::macrosForQuantity.
No es un capricho: la pantalla enseña los macros antes de guardar y el
servidor devuelve los suyos después, y si redondearan distinto la cifra
cambiaría sola delante del usuario.

textoRestante distingue no tener objetivo de tener uno en cero. Sin objetivo
no se estima nada: es un dato de salud y aquí no se inventa ninguno.
EOF
)"
```

---

## Tarea 4 · `formato.ts`: Mifflin-St Jeor y el asistente

Se porta de `old/resources/views/nutrition/dashboard.blade.php:1037-1049` (las dos tablas)
y `:1222-1247` (el cálculo). **De las tablas no se porta ni `icon` ni `color`:** aquí no
hay iconos y los colores solo salen de las once variables del CSS.

**Ficheros:**
- Modificar: `web/src/formato.ts`
- Test: `web/src/formato.test.ts`

**Interfaces:**
- Consume: nada.
- Produce: `ACTIVIDADES`, `OBJETIVOS`, los tipos `ClaveActividad`, `ClaveObjetivo`,
  `DatosCuerpo`, `ObjetivoNutricional`; `calcularObjetivo(cuerpo, actividad, objetivo)`.

- [ ] **Paso 1 · Escribir el test que falla**

```ts
import { calcularObjetivo } from "./formato";

// Hombre de 80 kg, 180 cm, 30 años.
// BMR = 10×80 + 6,25×180 − 5×30 + 5 = 800 + 1125 − 150 + 5 = 1780
const HOMBRE = { weight: 80, height: 180, age: 30, gender: "male" as const };
// Mujer de 65 kg, 165 cm, 30 años.
// BMR = 650 + 1031,25 − 150 − 161 = 1370,25
const MUJER = { weight: 65, height: 165, age: 30, gender: "female" as const };

test("la constante de sexo cambia el resultado", () => {
  // 1780 × 1,55 = 2759 → mantener no ajusta nada.
  expect(calcularObjetivo(HOMBRE, "moderate", "maintain").daily_calories).toBe(2759);
  // 1370,25 × 1,55 = 2123,8875 → 2124.
  expect(calcularObjetivo(MUJER, "moderate", "maintain").daily_calories).toBe(2124);
});

test("perder resta 500 y ganar suma 300", () => {
  expect(calcularObjetivo(HOMBRE, "moderate", "lose_weight").daily_calories).toBe(2259);
  expect(calcularObjetivo(HOMBRE, "moderate", "gain_muscle").daily_calories).toBe(3059);
});

test("nunca baja de 1200 kcal", () => {
  // Persona pequeña y sedentaria con déficit: el cálculo se iría por debajo de lo que
  // ninguna dieta debería recomendar sin supervisión.
  const menuda = { weight: 45, height: 150, age: 60, gender: "female" as const };
  expect(calcularObjetivo(menuda, "sedentary", "lose_weight").daily_calories).toBe(1200);
});

test("los macros salen de los ratios del objetivo y cuadran con las calorías", () => {
  const objetivo = calcularObjetivo(HOMBRE, "moderate", "maintain");
  // maintain reparte 30 % proteína, 45 % hidratos, 25 % grasa.
  expect(objetivo.target_protein).toBe(Math.round((2759 * 0.3) / 4));
  expect(objetivo.target_carbs).toBe(Math.round((2759 * 0.45) / 4));
  expect(objetivo.target_fat).toBe(Math.round((2759 * 0.25) / 9));
  expect(objetivo.goal_type).toBe("maintain");
});

test("el factor de actividad se aplica", () => {
  // 1780 × 1,2 = 2136.
  expect(calcularObjetivo(HOMBRE, "sedentary", "maintain").daily_calories).toBe(2136);
  // 1780 × 1,9 = 3382.
  expect(calcularObjetivo(HOMBRE, "very_active", "maintain").daily_calories).toBe(3382);
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- formato`
Esperado: FALLA — `calcularObjetivo` no existe.

- [ ] **Paso 3 · Escribir la implementación mínima**

Al final de `web/src/formato.ts`:

```ts
/* El asistente nutricional, portado de FitLoop
   (old/resources/views/nutrition/dashboard.blade.php:1037-1049 y :1222-1247).

   ponytail: Mifflin-St Jeor queda escrita en dos sitios, aquí y en
   NutritionGoal::calculateRecommended. Se acepta para que el asistente recalcule al
   tocar, sin una petición por opción. El techo es que se separen; si pasa, el sitio
   donde unificarlas es GET /api/nutrition/goal aceptando activity y goal_type. */

export const ACTIVIDADES = [
  { clave: "sedentary", etiqueta: "Poco o nada", factor: 1.2 },
  { clave: "light", etiqueta: "Ejercicio ligero, de 1 a 3 días por semana", factor: 1.375 },
  { clave: "moderate", etiqueta: "Ejercicio moderado, de 3 a 5 días", factor: 1.55 },
  { clave: "active", etiqueta: "Ejercicio intenso, casi a diario", factor: 1.725 },
  { clave: "very_active", etiqueta: "Muy intenso, o deporte más trabajo físico", factor: 1.9 },
] as const;

export const OBJETIVOS = [
  { clave: "lose_weight", etiqueta: "Perder peso", ajuste: -500, ratios: [0.35, 0.4, 0.25] },
  { clave: "maintain", etiqueta: "Mantener el peso", ajuste: 0, ratios: [0.3, 0.45, 0.25] },
  { clave: "gain_muscle", etiqueta: "Ganar músculo", ajuste: 300, ratios: [0.3, 0.5, 0.2] },
] as const;

export type ClaveActividad = (typeof ACTIVIDADES)[number]["clave"];
export type ClaveObjetivo = (typeof OBJETIVOS)[number]["clave"];

export type DatosCuerpo = {
  weight: number;
  height: number;
  age: number;
  gender: "male" | "female";
};

/** Lo que acepta `PUT /api/nutrition/goal`. */
export type ObjetivoNutricional = {
  daily_calories: number;
  target_protein: number;
  target_carbs: number;
  target_fat: number;
  target_fiber: number;
  goal_type: ClaveObjetivo;
};

/** Por debajo de esto no se recomienda una dieta sin que la vigile alguien. FitLoop ya
 *  tenía este suelo y se conserva: el cálculo de una persona menuda y sedentaria con
 *  déficit se va por debajo sin ningún aviso. */
const MINIMO_KCAL = 1200;

export function calcularObjetivo(
  cuerpo: DatosCuerpo,
  actividad: ClaveActividad,
  objetivo: ClaveObjetivo,
): ObjetivoNutricional {
  const factor = ACTIVIDADES.find((a) => a.clave === actividad)!.factor;
  const meta = OBJETIVOS.find((o) => o.clave === objetivo)!;

  // Mifflin-St Jeor. La constante de sexo es +5 en hombres y −161 en mujeres.
  const constante = cuerpo.gender === "female" ? -161 : 5;
  const bmr = 10 * cuerpo.weight + 6.25 * cuerpo.height - 5 * cuerpo.age + constante;
  const tdee = Math.round(bmr * factor);
  const calorias = Math.max(MINIMO_KCAL, tdee + meta.ajuste);

  const [proteina, hidratos, grasa] = meta.ratios;
  return {
    daily_calories: calorias,
    // 4 kcal por gramo de proteína y de hidratos, 9 por gramo de grasa.
    target_protein: Math.round((calorias * proteina) / 4),
    target_carbs: Math.round((calorias * hidratos) / 4),
    target_fat: Math.round((calorias * grasa) / 9),
    target_fiber: 25,
    goal_type: objetivo,
  };
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- formato`
Esperado: PASAN todos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/formato.ts web/src/formato.test.ts
git commit -m "$(cat <<'EOF'
feat(nutrición): Mifflin-St Jeor, portada de FitLoop

El asistente recalcula al tocar cada opción, así que la fórmula tiene que
estar aquí y no detrás de una petición: una ida y vuelta por opción haría
que el número parpadeara en cada toque.

Queda duplicada con NutritionGoal::calculateRecommended y así se marca con
un ponytail:, con el sitio donde unificarlas si algún día se separan.

De las tablas de old/ no se porta ni icon ni color: aquí no hay iconos y los
colores solo salen de las once variables del CSS.

El suelo de 1200 kcal viene de FitLoop y se conserva. Sin él, una persona
menuda y sedentaria con déficit recibe una recomendación por debajo de lo
que debería seguir nadie sin supervisión.
EOF
)"
```

---

## Tarea 5 · `recientes.ts`: «lo de siempre»

Ni el backend ni FitLoop tienen alimentos recientes: en `old/` había que buscar cada vez,
las 182 veces. Esta lista es lo que convierte repetir el desayuno en dos toques.

**Ficheros:**
- Crear: `web/src/recientes.ts`
- Crear: `web/src/recientes.test.ts`

**Interfaces:**
- Consume: el tipo `TipoComida`, que **todavía no existe** — se declara aquí y la tarea 6
  lo importa desde este fichero. Así `recientes.ts` no depende de `api.ts` y se prueba sin
  tocar la red.
- Produce: `TipoComida`, `Reciente`, `MAXIMO`, `recientes(tipo)`, `apuntar(reciente)`.

- [ ] **Paso 1 · Escribir el test que falla**

Crea `web/src/recientes.test.ts`:

```ts
/* La lista vive solo en este móvil, así que tiene que aguantar sola tres cosas: que no
   haya nada, que lo que haya sea de una versión anterior, y que localStorage no deje
   escribir. Ninguna de las tres puede dejar la pantalla de añadir comida rota. */

import { beforeEach, expect, test, vi } from "vitest";
import { MAXIMO, apuntar, recientes } from "./recientes";

const CAFE = { id: 1, nombre: "Café con leche", gramos: 200, tipo: "breakfast" as const, kcal100: 44 };
const TOSTADA = { id: 2, nombre: "Tostada integral", gramos: 60, tipo: "breakfast" as const, kcal100: 267 };

beforeEach(() => localStorage.clear());

test("sin nada guardado la lista está vacía y no revienta", () => {
  expect(recientes("breakfast")).toEqual([]);
});

test("lo último usado va primero", () => {
  apuntar(CAFE);
  apuntar(TOSTADA);
  expect(recientes("breakfast").map((r) => r.nombre)).toEqual([
    "Tostada integral",
    "Café con leche",
  ]);
});

test("repetir un alimento lo sube arriba en vez de duplicarlo", () => {
  apuntar(CAFE);
  apuntar(TOSTADA);
  apuntar({ ...CAFE, gramos: 250 });

  const lista = recientes("breakfast");
  expect(lista).toHaveLength(2);
  expect(lista[0].nombre).toBe("Café con leche");
  // Y se queda con la cantidad de la última vez, que es la que se ofrece.
  expect(lista[0].gramos).toBe(250);
});

test("el mismo alimento en otra comida es otra entrada", () => {
  apuntar(CAFE);
  apuntar({ ...CAFE, tipo: "snack" });

  expect(recientes("breakfast")).toHaveLength(1);
  expect(recientes("snack")).toHaveLength(1);
});

test("la lista se corta por el máximo y tira lo más viejo", () => {
  for (let i = 1; i <= MAXIMO + 5; i++) {
    apuntar({ id: i, nombre: `Alimento ${i}`, gramos: 100, tipo: "lunch", kcal100: 100 });
  }
  const lista = recientes("lunch");
  expect(lista).toHaveLength(MAXIMO);
  expect(lista[0].nombre).toBe(`Alimento ${MAXIMO + 5}`);
  expect(lista.some((r) => r.nombre === "Alimento 1")).toBe(false);
});

test("basura guardada por una versión anterior se ignora, no rompe la pantalla", () => {
  localStorage.setItem("srank.comidas-recientes", "{no es json");
  expect(recientes("breakfast")).toEqual([]);

  localStorage.setItem("srank.comidas-recientes", JSON.stringify({ v: 99, lista: [] }));
  expect(recientes("breakfast")).toEqual([]);
});

test("si localStorage no deja escribir, apuntar no lanza", () => {
  vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
    throw new Error("cuota llena");
  });
  // Perder un reciente no es perder un dato: la comida ya está guardada en el servidor.
  expect(() => apuntar(CAFE)).not.toThrow();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- recientes`
Esperado: FALLA — no existe `./recientes`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/recientes.ts`:

```ts
/* «Lo de siempre»: los últimos alimentos registrados, para que repetir el desayuno sean
   dos toques y no cinco. Es la acción más repetida de la aplicación —182 comidas frente a
   3 entrenos en los datos reales— y ni el backend ni FitLoop tenían nada parecido.

   Junto a borrador.ts, es el único sitio del proyecto que menciona localStorage.

   ponytail: la lista vive solo en este móvil. El techo es que en un dispositivo nuevo
   sale vacía y se rellena sola en unos días, y que no se comparte entre dispositivos. Si
   algún día hace falta que sincronice, el sitio es GET /api/foods/recent en el backend.

   Perder esta lista no pierde ningún dato: las comidas están en el servidor. Por eso aquí
   los fallos se tragan en silencio, al revés que en borrador.ts, donde un `false` sin
   mirar significaba perder un entreno. */

export type TipoComida = "breakfast" | "lunch" | "dinner" | "snack";

export type Reciente = {
  /** `food_items.id`, que es un entero. Los alimentos a mano no entran aquí: sin id no
   *  se pueden volver a registrar de un toque. */
  id: number;
  nombre: string;
  gramos: number;
  tipo: TipoComida;
  /** Las calorías por 100 g del alimento. Se guardan aquí para que el chip pueda decir
   *  cuántas calorías son sin pedir nada: si no, abrir la pantalla serían cuatro
   *  peticiones al catálogo solo para pintar cuatro números. */
  kcal100: number;
};

/** Diez llenan la pantalla sin obligar a desplazar. Más es una lista que hay que leer, y
 *  leer cuesta más que buscar. */
export const MAXIMO = 10;

const VERSION = 1;
const CLAVE = "srank.comidas-recientes";

type Guardado = { v: number; lista: Reciente[] };

function leerTodo(): Reciente[] {
  try {
    const texto = localStorage.getItem(CLAVE);
    if (!texto) return [];
    const dato = JSON.parse(texto) as Guardado | null;
    if (!dato || dato.v !== VERSION || !Array.isArray(dato.lista)) return [];
    return dato.lista;
  } catch {
    // JSON roto, versión anterior, o localStorage que no deja leer en modo privado.
    return [];
  }
}

/** Los de una comida concreta, del más reciente al más antiguo. */
export function recientes(tipo: TipoComida): Reciente[] {
  return leerTodo().filter((r) => r.tipo === tipo);
}

/** Sube el alimento al principio con la cantidad de esta vez. Un mismo alimento en dos
 *  comidas distintas son dos entradas: el café de la mañana y el de media tarde no llevan
 *  la misma cantidad. */
export function apuntar(reciente: Reciente): void {
  const resto = leerTodo().filter(
    (r) => !(r.id === reciente.id && r.tipo === reciente.tipo),
  );
  const lista = [reciente, ...resto].slice(0, MAXIMO * 4);

  try {
    localStorage.setItem(CLAVE, JSON.stringify({ v: VERSION, lista } satisfies Guardado));
  } catch {
    // Cuota llena o modo privado. Se pierde el atajo, no la comida.
  }
}
```

⚠️ El corte de `apuntar` es `MAXIMO * 4` **a propósito**: la lista guardada es común a las
cuatro comidas y `recientes()` filtra después. Cortar por `MAXIMO` a secas dejaría a la
cena sin recientes en cuanto el desayuno llenara la lista.

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- recientes`
Esperado: PASAN todos. El test del máximo pasa porque llena una sola comida.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/recientes.ts web/src/recientes.test.ts
git commit -m "$(cat <<'EOF'
feat(nutrición): «lo de siempre», los alimentos recientes

Registrar una comida es la acción más repetida de la aplicación y en FitLoop
había que buscar cada una de las 182 veces. Con esta lista, repetir el
desayuno son dos toques.

Vive en localStorage y no en el servidor: así no hay que tocar el backend ni
volver a desplegar. El techo —que no sincroniza entre dispositivos— queda
escrito en el fichero con su ponytail:.

Los fallos se tragan en silencio, al revés que en borrador.ts: perder un
atajo no es perder un dato, porque la comida ya está en el servidor.

Guarda también las calorías por 100 g del alimento. Sin ellas, pintar los
cuatro chips serían cuatro peticiones al catálogo solo para enseñar cuatro
números.

La lista se corta por MAXIMO × 4 y no por MAXIMO porque es común a las
cuatro comidas: cortando antes, llenar el desayuno dejaría la cena sin
recientes.
EOF
)"
```

---

## Tarea 6 · `api.ts`: nutrición

**Ficheros:**
- Modificar: `web/src/api.ts`
- Test: `web/src/api.test.ts`

**Interfaces:**
- Consume: `pedir()` y `TipoComida` de `./recientes`.
- Produce: `TIPOS_COMIDA`, los tipos `Alimento`, `EntradaComida`, `DiaDeComidas`,
  `ComidaNueva`, `AlimentoNuevo`; y `comidasDelDia(fecha)`, `buscarAlimentos(texto)`,
  `registrarComida(datos)`, `borrarComida(uuid)`, `crearAlimento(datos)`.

- [ ] **Paso 1 · Escribir el test que falla**

Al final de `web/src/api.test.ts`:

```ts
import { buscarAlimentos, comidasDelDia } from "./api";

test("un día sin comidas llega como [] y se normaliza a las cuatro claves", async () => {
  // Laravel serializa una colección con claves vacía como array, no como objeto. Sin
  // normalizar, la pantalla haría meals.breakfast.items sobre undefined y reventaría
  // justo el primer día que alguien abre la aplicación.
  servidorQueResponde(200, {
    date: "2026-08-20",
    meals: [],
    totals: { calories: 0, protein: 0, carbs: 0, fat: 0, fiber: 0, sugar: 0 },
    count: 0,
    calories_burned: 0,
  });

  const dia = await comidasDelDia("2026-08-20");

  expect(Object.keys(dia.meals)).toEqual(["breakfast", "lunch", "dinner", "snack"]);
  expect(dia.meals.breakfast).toEqual({ items: [], calories: 0 });
});

test("las comidas que sí hay se conservan y las que faltan salen vacías", async () => {
  servidorQueResponde(200, {
    date: "2026-08-20",
    meals: { breakfast: { items: [{ uuid: "a", custom_food_name: "Café" }], calories: 88 } },
    totals: { calories: 88, protein: 4, carbs: 6, fat: 4, fiber: 0, sugar: 6 },
    count: 1,
    calories_burned: 0,
  });

  const dia = await comidasDelDia("2026-08-20");

  expect(dia.meals.breakfast.calories).toBe(88);
  expect(dia.meals.dinner).toEqual({ items: [], calories: 0 });
});

test("el buscador manda el texto escapado y desenvuelve la lista", async () => {
  const fetchFalso = vi.fn(async () =>
    new Response(JSON.stringify({ foods: [{ id: 1, name: "Pollo" }] }), {
      status: 200,
      headers: { "Content-Type": "application/json" },
    }),
  );
  vi.stubGlobal("fetch", fetchFalso);

  const encontrados = await buscarAlimentos("aceite & sal");

  expect(encontrados).toHaveLength(1);
  expect(fetchFalso.mock.calls[0][0]).toContain("aceite%20%26%20sal");
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- api`
Esperado: FALLA — `comidasDelDia` y `buscarAlimentos` no existen.

- [ ] **Paso 3 · Escribir la implementación mínima**

En `web/src/api.ts`, después del bloque de Ejercicios:

```ts
// ── Nutrición ───────────────────────────────────────────────────────────────

import { type TipoComida } from "./recientes";
import type { Macros, PorCien } from "./formato";

export type { TipoComida };

/** El orden en que se pintan y cómo se dicen. Las claves son las del servidor y no se
 *  traducen nunca en la petición; las etiquetas no se ven nunca en la petición. */
export const TIPOS_COMIDA: { clave: TipoComida; etiqueta: string }[] = [
  { clave: "breakfast", etiqueta: "Desayuno" },
  { clave: "lunch", etiqueta: "Comida" },
  { clave: "dinner", etiqueta: "Cena" },
  { clave: "snack", etiqueta: "Tentempié" },
];

export type Alimento = PorCien & {
  id: number;
  name: string;
  brand: string | null;
  category: string | null;
  /** `g` o `ml`. El cálculo es el mismo: los macros van siempre por 100 unidades. */
  unit: "g" | "ml";
  is_verified: boolean;
  image_path?: string | null;
};

export type EntradaComida = {
  uuid: string;
  meal_type: TipoComida;
  food_item_id: number | null;
  custom_food_name: string | null;
  quantity_grams: number;
  food_item?: Alimento | null;
} & Macros;

export type DiaDeComidas = {
  date: string;
  meals: Record<TipoComida, { items: EntradaComida[]; calories: number }>;
  totals: Macros;
  count: number;
  calories_burned: number;
};

const COMIDA_VACIA = { items: [] as EntradaComida[], calories: 0 };

/** ⚠️ `meals` llega como objeto agrupado por tipo, **pero cuando el día está vacío Laravel
 *  lo serializa como `[]`**, porque una colección con claves sin elementos es un array
 *  vacío en JSON. Se normaliza aquí, en la puerta, para que ninguna pantalla tenga que
 *  saberlo: si una sola se olvidara, reventaría el primer día que alguien abre la app. */
export async function comidasDelDia(fecha: string): Promise<DiaDeComidas> {
  const crudo = await pedir<Omit<DiaDeComidas, "meals"> & { meals: unknown }>(
    `/meals?date=${encodeURIComponent(fecha)}`,
  );

  const agrupadas = (crudo.meals ?? {}) as Partial<DiaDeComidas["meals"]>;
  const meals = {} as DiaDeComidas["meals"];
  for (const { clave } of TIPOS_COMIDA) {
    meals[clave] = agrupadas[clave] ?? { ...COMIDA_VACIA };
  }

  return { ...crudo, meals };
}

/** Búsqueda por servidor: el catálogo son 1.506 alimentos y no se baja al móvil. */
export async function buscarAlimentos(texto: string): Promise<Alimento[]> {
  const respuesta = await pedir<{ foods: Alimento[] }>(
    `/foods?search=${encodeURIComponent(texto)}&limit=20`,
  );
  return respuesta.foods;
}

/** Las dos formas que acepta el servidor. Con `food_item_id` calcula él los macros; con
 *  `custom_food_name` se los damos nosotros. Sin ninguna de las dos responde 422. */
export type ComidaNueva =
  | { date: string; meal_type: TipoComida; food_item_id: number; quantity_grams: number }
  | ({ date: string; meal_type: TipoComida; custom_food_name: string } & Partial<Macros>);

export function registrarComida(datos: ComidaNueva) {
  return pedir<{ message: string; meal_log: EntradaComida; system: BloqueSistema }>("/meals", {
    metodo: "POST",
    cuerpo: datos,
  });
}

/** El servidor es idempotente aquí: si ya no existe responde 200, no 404. Así un doble
 *  toque no saca un error por algo que sí se hizo. */
export function borrarComida(uuid: string) {
  return pedir<{ message: string }>(`/meals/${uuid}`, { metodo: "DELETE" });
}

export type AlimentoNuevo = PorCien & {
  name: string;
  brand?: string | null;
  category?: string | null;
  unit?: "g" | "ml";
};

/** ⚠️ **No se manda nunca `from_ingredients`.** Ese campo hace que el alimento nazca en el
 *  catálogo global, con `user_id` a null y visible para todo el mundo. Aquí se crean
 *  siempre alimentos personales. */
export function crearAlimento(datos: AlimentoNuevo) {
  return pedir<{ message: string; food: Alimento }>("/foods", {
    metodo: "POST",
    cuerpo: datos,
  });
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- api` y luego `cd web && npm run build`
Esperado: PASAN los tests y `tsc -b` no se queja.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/api.ts web/src/api.test.ts
git commit -m "$(cat <<'EOF'
feat(nutrición): las comidas y el catálogo en api.ts

comidasDelDia normaliza `meals` a las cuatro claves. Laravel serializa una
colección agrupada vacía como [] y no como {}, así que sin esto la pantalla
haría meals.breakfast.items sobre undefined justo el primer día que alguien
abre la aplicación. Se arregla en la puerta y no en cada pantalla: basta con
que una se olvide para que vuelva el fallo.

crearAlimento no manda from_ingredients a propósito. Ese campo hace que el
alimento nazca en el catálogo global y visible para todos; aquí se crean
siempre personales.

El buscador va contra el servidor: el catálogo son 1.506 alimentos y no se
baja al móvil.
EOF
)"
```

---

## Tarea 7 · `api.ts`: hábitos, el objetivo y `subir()`

**Ficheros:**
- Modificar: `web/src/api.ts`
- Test: `web/src/api.test.ts`

**Interfaces:**
- Consume: `pedir()`, `asegurarCsrf()` (privada, ya existe), `ObjetivoNutricional` de
  `./formato`.
- Produce: `Usuario` ensanchado; los tipos `DiaDeAgua`, `Suplemento`, `DiaDeActividad`;
  `agua`, `anadirAgua`, `objetivoAgua`, `suplementos`, `marcarSuplemento`, `actividad`,
  `guardarActividad`, `guardarPeso`, `objetivoNutricional`, `guardarObjetivoNutricional`,
  `subir`.

- [ ] **Paso 1 · Escribir el test que falla**

```ts
import { subir } from "./api";

test("subir no pone Content-Type a mano: lo pone el navegador con su boundary", async () => {
  const fetchFalso = vi.fn(async () =>
    new Response(JSON.stringify({ image_path: "nutrition/foods/x.jpg" }), {
      status: 200,
      headers: { "Content-Type": "application/json" },
    }),
  );
  vi.stubGlobal("fetch", fetchFalso);
  document.cookie = "XSRF-TOKEN=abc123";

  await subir("/foods/1/image", "image", new File(["x"], "x.jpg", { type: "image/jpeg" }));

  const opciones = fetchFalso.mock.calls[0][1] as RequestInit;
  const cabeceras = opciones.headers as Record<string, string>;

  // Con Content-Type puesto a mano, el boundary se pierde y el servidor no encuentra
  // ningún fichero: responde 422 diciendo que «image» es obligatorio.
  expect(cabeceras["Content-Type"]).toBeUndefined();
  // Y el token sí tiene que ir, o toda escritura contesta 419.
  expect(cabeceras["X-XSRF-TOKEN"]).toBe("abc123");
  expect(opciones.body).toBeInstanceOf(FormData);
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- api`
Esperado: FALLA — `subir` no existe.

- [ ] **Paso 3 · Escribir la implementación mínima**

Primero, **ensancha el tipo `Usuario`**, que hoy declara tres campos de los once que
devuelve `GET /api/user`:

```ts
export type Usuario = {
  name: string;
  email: string;
  is_admin: boolean;
  /** ⚠️ `users.weight` es una columna float y el modelo no la castea, así que puede llegar
   *  como cadena. `usuarioActual()` la pasa por `Number()`, igual que ya se hace con
   *  `max_weight`: sin eso, `"75.5" + 5` daría `"75.55"` en cualquier suma. */
  weight: number | null;
  height: number | null;
  age: number | null;
  gender: "male" | "female" | null;
  weekly_goal: number | null;
  water_goal_ml: number | null;
};
```

Y en `usuarioActual()`, normaliza al recibir:

```ts
export async function usuarioActual(): Promise<Usuario | null> {
  try {
    const crudo = await pedir<Usuario>("/user", { avisarSiCaduca: false });
    return { ...crudo, weight: crudo.weight == null ? null : Number(crudo.weight) };
  } catch {
    return null;
  }
}
```

Después, al final del fichero:

```ts
// ── Hábitos: agua, suplementos, actividad y peso ─────────────────────────────

export type DiaDeAgua = {
  date: string;
  total_ml: number;
  goal_ml: number;
  /** El servidor lo topa en 100. Para el texto en litros se usa `total_ml`, que no. */
  pct: number;
  entries: { id: number; amount_ml: number }[];
};

export function agua(fecha: string) {
  return pedir<DiaDeAgua>(`/water?date=${encodeURIComponent(fecha)}`);
}

/** Entre 1 y 2000 ml por registro. */
export function anadirAgua(fecha: string, ml: number) {
  return pedir<{ total_ml: number; goal_ml: number; pct: number; system: BloqueSistema }>(
    "/water",
    { metodo: "POST", cuerpo: { date: fecha, amount_ml: ml } },
  );
}

/** Entre 500 y 6000 ml. */
export function objetivoAgua(ml: number) {
  return pedir<{ goal_ml: number }>("/water/goal", { metodo: "PUT", cuerpo: { goal_ml: ml } });
}

/** Las cuatro claves son las del servidor. Ojo: `vitamina_d`, sin el 3, aunque se
 *  escriba «Vitamina D3» en pantalla. */
export type ClaveSuplemento = "multivitaminas" | "omega3" | "vitamina_d" | "magnesio";

export type Suplemento = {
  key: ClaveSuplemento;
  name: string;
  dose: string;
  taken: boolean;
};

export function suplementos(fecha: string) {
  return pedir<{ items: Suplemento[]; taken_count: number; total_count: number }>(
    `/supplements?date=${encodeURIComponent(fecha)}`,
  );
}

/** ⚠️ No devuelve el estado actualizado, solo el bloque `system`. Quien llame a esto pinta
 *  el cambio por su cuenta. */
export function marcarSuplemento(fecha: string, clave: ClaveSuplemento, tomado: boolean) {
  return pedir<{ message: string; system: BloqueSistema }>("/supplements", {
    metodo: "PUT",
    cuerpo: { date: fecha, supplement_key: clave, taken: tomado },
  });
}

export type DiaDeActividad = { date: string; steps: number; calories_burned: number };

export function actividad(fecha: string) {
  return pedir<DiaDeActividad>(`/activity?date=${encodeURIComponent(fecha)}`);
}

/** ⚠️ El servidor exige las dos cifras. Las calorías son opcionales en la interfaz —mucha
 *  gente solo conoce sus pasos— y aquí se manda 0 cuando no se saben. **No se estiman a
 *  partir de los pasos:** sería un número inventado presentado como dato de salud. */
export function guardarActividad(fecha: string, pasos: number, calorias: number) {
  return pedir<DiaDeActividad & { system: BloqueSistema | null }>("/activity", {
    metodo: "PUT",
    cuerpo: { date: fecha, steps: pasos, calories_burned: calorias },
  });
}

/** ⚠️ `system` llega a `null` si el peso no cambió. */
export function guardarPeso(kg: number) {
  return pedir<{ user: Usuario; system: BloqueSistema | null }>("/user/profile", {
    metodo: "PUT",
    cuerpo: { weight: kg },
  });
}

/** Los datos del cuerpo que necesita el asistente, cuando falta alguno. */
export function guardarDatosCuerpo(datos: {
  weight?: number;
  height?: number;
  age?: number;
  gender?: "male" | "female";
}) {
  return pedir<{ user: Usuario; system: BloqueSistema | null }>("/user/profile", {
    metodo: "PUT",
    cuerpo: datos,
  });
}

// ── El objetivo nutricional ─────────────────────────────────────────────────

import type { ObjetivoNutricional } from "./formato";

/** `has_goal` a false significa que `goal` es una sugerencia calculada al vuelo por el
 *  servidor, no algo guardado. La misión de proteína solo existe cuando es true. */
export function objetivoNutricional() {
  return pedir<{ goal: ObjetivoNutricional; has_goal: boolean }>("/nutrition/goal");
}

export function guardarObjetivoNutricional(objetivo: ObjetivoNutricional) {
  return pedir<{ message: string; goal: ObjetivoNutricional }>("/nutrition/goal", {
    metodo: "PUT",
    cuerpo: objetivo,
  });
}

// ── Subir ficheros ──────────────────────────────────────────────────────────

/** `pedir()` manda siempre JSON. Una subida necesita `FormData`, y ahí hay dos reglas que
 *  no son evidentes:
 *
 *  1. **No se pone `Content-Type`.** Lo pone el navegador, y tiene que incluir el
 *     `boundary` que él mismo genera. Ponerlo a mano se lo carga: el servidor no encuentra
 *     ningún fichero y responde 422 diciendo que falta.
 *  2. **El `X-XSRF-TOKEN` sí va**, como en toda escritura, o Laravel contesta 419.
 *
 *  La traducción de errores no se repite: se reutiliza la que ya hace `pedir`. */
export async function subir<T>(ruta: string, campo: string, fichero: File): Promise<T> {
  const cuerpo = new FormData();
  cuerpo.append(campo, fichero);

  let respuesta: Response;
  try {
    respuesta = await fetch(`/api${ruta}`, {
      method: "POST",
      credentials: "same-origin",
      headers: { Accept: "application/json", "X-XSRF-TOKEN": await asegurarCsrf() },
      body: cuerpo,
    });
  } catch {
    throw new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} });
  }

  if (respuesta.ok) return (await respuesta.json()) as T;

  if (respuesta.status === 413 || respuesta.status === 422) {
    throw new ErrorApi({
      general: "La foto no se ha podido subir. Prueba con otra más pequeña.",
      campos: {},
    });
  }

  throw new ErrorApi({ general: "No hemos podido subir la foto. Inténtalo otra vez.", campos: {} });
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test` (la suite entera: `Usuario` cambia y lo usan varias pantallas)
Esperado: PASAN todos. Si algún test construye un `Usuario` a mano, añádele los campos
nuevos a `null`.

Y `cd web && npm run build`, que es donde saltan los tipos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/api.ts web/src/api.test.ts
git commit -m "$(cat <<'EOF'
feat(hábitos): agua, suplementos, actividad, peso y el objetivo en api.ts

Y subir(), que es lo que falta para las fotos: pedir() manda siempre JSON y
una subida necesita FormData. Las dos reglas que no se ven venir quedan
escritas en el fichero: el Content-Type NO se pone a mano —el navegador lo
pone con su boundary, y ponerlo lo rompe— y el X-XSRF-TOKEN sí va, o Laravel
contesta 419.

El tipo Usuario declaraba tres campos de los once que devuelve GET /api/user.
Ensanchado, el asistente nutricional ya tiene peso, altura, edad y sexo en
memoria y no necesita pedir nada.

users.weight es float sin castear en el modelo, así que puede llegar como
cadena. Se normaliza con Number() en la puerta, igual que ya se hacía con
max_weight: sin eso, cualquier suma daría una concatenación.
EOF
)"
```

---

## Tarea 8 · Los componentes nuevos: `Casilla`, `FilaMacros` y `ChipComida`

**Ficheros:**
- Modificar: `web/src/componentes.tsx`
- Modificar: `web/src/estilos.css`
- Test: `web/src/componentes.test.tsx`

**Interfaces:**
- Consume: `Macros` de `./formato`, `Reciente` de `./recientes`.
- Produce: `Casilla`, `FilaMacros`, `ChipComida`.

⚠️ **Ni un solo `--nombre: #rrggbb` nuevo en `estilos.css`.** `estilos.test.ts` compara la
paleta entera con `toEqual` contra los once colores del spec: una propiedad nueva rompe ese
test. Todo el CSS de esta tarea usa las variables que ya hay.

- [ ] **Paso 1 · Escribir el test que falla**

Al final de `web/src/componentes.test.tsx`:

```ts
import { Casilla, ChipComida, FilaMacros } from "./componentes";

test("una casilla dice su estado con palabras, no con el dibujo", () => {
  render(<Casilla etiqueta="Multivitaminas" marcada onCambiar={() => {}} />);

  // Lo que se oye es «Multivitaminas, tomado». El [✓] es decoración.
  const boton = screen.getByRole("button", { name: "Multivitaminas, tomado" });
  expect(boton.getAttribute("aria-pressed")).toBe("true");
  expect(boton.textContent).not.toContain("corchete");
});

test("una casilla sin marcar se oye pendiente", () => {
  render(<Casilla etiqueta="Magnesio" marcada={false} onCambiar={() => {}} />);
  expect(screen.getByRole("button", { name: "Magnesio, sin tomar" })).toBeTruthy();
});

test("la casilla avisa del cambio que se pide, no del que ya hay", () => {
  const cambios: boolean[] = [];
  render(<Casilla etiqueta="Omega 3" marcada={false} onCambiar={(v) => cambios.push(v)} />);

  fireEvent.click(screen.getByRole("button", { name: "Omega 3, sin tomar" }));

  expect(cambios).toEqual([true]);
});

test("los macros se dicen con palabras enteras y sin abreviar", () => {
  render(
    <FilaMacros macros={{ calories: 1360, protein: 92, carbs: 140, fat: 45, fiber: 18, sugar: 30 }} />,
  );

  expect(screen.getByText("Proteínas")).toBeTruthy();
  expect(screen.getByText("Hidratos")).toBeTruthy();
  expect(screen.getByText("Grasas")).toBeTruthy();
  // Nada de «P/H/G» ni de «kcal/100g» sin explicar.
  expect(screen.queryByText("P")).toBe(null);
});

test("el chip de un reciente registra con un toque y ajustar es otro botón", () => {
  const registrados: string[] = [];
  const ajustados: string[] = [];
  render(
    <ChipComida
      reciente={{ id: 1, nombre: "Café con leche", gramos: 200, tipo: "breakfast" }}
      kcal={88}
      onRegistrar={() => registrados.push("sí")}
      onAjustar={() => ajustados.push("sí")}
    />,
  );

  fireEvent.click(screen.getByRole("button", { name: "Café con leche, 200 g, 88 kilocalorías" }));
  expect(registrados).toHaveLength(1);

  // Ajustar es un botón con su propio nombre: un mantener pulsado no se ve, no se puede
  // teclear y en el móvil pelea con el menú del navegador.
  fireEvent.click(screen.getByRole("button", { name: "Cambiar la cantidad de Café con leche" }));
  expect(ajustados).toHaveLength(1);
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- componentes`
Esperado: FALLA — los tres componentes no existen.

- [ ] **Paso 3 · Escribir la implementación mínima**

Al final de `web/src/componentes.tsx`:

```tsx
/** `[✓] Multivitaminas`. Es un botón con `aria-pressed` y no un `<input type=checkbox>`
 *  porque no vive dentro de ningún formulario: cada toque es una petición, no un campo que
 *  se envía luego. Lo que se oye es «Multivitaminas, tomado». */
export function Casilla({
  etiqueta,
  marcada,
  onCambiar,
  disabled,
}: {
  etiqueta: string;
  marcada: boolean;
  onCambiar: (marcada: boolean) => void;
  disabled?: boolean;
}) {
  return (
    <button
      type="button"
      className={marcada ? "casilla marcada" : "casilla"}
      aria-pressed={marcada}
      aria-label={`${etiqueta}, ${marcada ? "tomado" : "sin tomar"}`}
      disabled={disabled}
      onClick={() => onCambiar(!marcada)}
    >
      <span className="marca" aria-hidden="true">[{marcada ? "✓" : " "}]</span>
      <span aria-hidden="true">{etiqueta}</span>
    </button>
  );
}

/** Los tres macros en fila, con las palabras enteras. Nada de «P/H/G» ni de abreviaturas
 *  de tabla nutricional: hay quien no ha visto una en su vida. La fibra y el azúcar se
 *  registran pero no se pintan aquí — no hay misión de fibra y la fila se vuelve ilegible
 *  con cinco columnas en un móvil. */
export function FilaMacros({ macros }: { macros: Macros }) {
  const columnas = [
    { etiqueta: "Proteínas", valor: macros.protein },
    { etiqueta: "Hidratos", valor: macros.carbs },
    { etiqueta: "Grasas", valor: macros.fat },
  ];

  return (
    <ul className="fila-macros">
      {columnas.map((c) => (
        <li key={c.etiqueta}>
          <span className="valor">{NUMEROS.format(Math.round(c.valor))} g</span>
          <span className="etiqueta-macro">{c.etiqueta}</span>
        </li>
      ))}
    </ul>
  );
}

/** «Lo de siempre». Pulsar el chip registra con la cantidad de la última vez; el segundo
 *  botón abre la cantidad. Dos botones y no un mantener pulsado: un gesto largo no se ve,
 *  no tiene equivalente de teclado y en el móvil pelea con el menú contextual. */
export function ChipComida({
  reciente,
  kcal,
  onRegistrar,
  onAjustar,
  disabled,
}: {
  reciente: Reciente;
  kcal: number;
  onRegistrar: () => void;
  onAjustar: () => void;
  disabled?: boolean;
}) {
  return (
    <li className="chip-comida">
      <button
        type="button"
        className="chip-principal"
        // «kilocalorías» entero: «kcal» leído por un lector de pantalla suena a nada.
        aria-label={`${reciente.nombre}, ${reciente.gramos} g, ${Math.round(kcal)} kilocalorías`}
        disabled={disabled}
        onClick={onRegistrar}
      >
        <span aria-hidden="true">[ </span>
        <span className="nombre" aria-hidden="true">{reciente.nombre}</span>
        <span className="cantidad" aria-hidden="true">{reciente.gramos} g</span>
        <span className="kcal" aria-hidden="true">{NUMEROS.format(Math.round(kcal))} kcal</span>
        <span aria-hidden="true"> ]</span>
      </button>
      <button
        type="button"
        className="chip-ajustar"
        // El texto suelto «CAMBIAR» no dice de qué alimento habla.
        aria-label={`Cambiar la cantidad de ${reciente.nombre}`}
        disabled={disabled}
        onClick={onAjustar}
      >
        <span aria-hidden="true">[ CAMBIAR ]</span>
      </button>
    </li>
  );
}
```

Y arriba del fichero, añade a los `import type` que ya hay:

```ts
import type { Macros } from "./formato";
import type { Reciente } from "./recientes";
```

En `web/src/estilos.css`, al final. **Sin colores nuevos:**

```css
/* Casillas de suplementos: dos por fila en un móvil, con 48 px de alto para que se
   acierten de pie y con una mano. */
.casilla {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 48px;
  width: 100%;
  padding: 0 0.75rem;
  background: var(--superficie);
  border: 1px solid var(--lineas);
  color: var(--texto);
  font: inherit;
  font-size: var(--cuerpo);
  text-align: left;
}

.casilla.marcada { border-color: var(--verde); }
.casilla.marcada .marca { color: var(--verde); }
.casilla:disabled { opacity: 0.6; }

.rejilla-casillas {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
  gap: 0.5rem;
  padding: 0;
  margin: 0.5rem 0 0;
  list-style: none;
}

/* Los tres macros en fila. El hueco lo abre el grid, nunca los espacios. */
.fila-macros {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
  padding: 0;
  margin: 0.5rem 0 0;
  list-style: none;
  text-align: center;
}

.fila-macros .valor {
  display: block;
  font-size: var(--seccion);
  color: var(--texto);
}

.fila-macros .etiqueta-macro {
  display: block;
  font-size: var(--etiqueta);
  color: var(--apagado);
}

/* «Lo de siempre»: el chip ocupa la fila y el botón de cambiar va a su derecha. */
.lista-chips { padding: 0; margin: 0.5rem 0 0; list-style: none; }

.chip-comida { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; }

.chip-principal {
  display: flex;
  flex: 1;
  align-items: center;
  gap: 0.5rem;
  min-height: 48px;
  padding: 0 0.5rem;
  background: var(--superficie);
  border: 1px solid var(--lineas);
  color: var(--texto);
  font: inherit;
  font-size: var(--cuerpo);
  text-align: left;
}

.chip-principal .nombre { flex: 1; }
.chip-principal .cantidad,
.chip-principal .kcal { color: var(--apagado); font-size: var(--nota); }

.chip-ajustar {
  min-height: 48px;
  padding: 0 0.5rem;
  background: transparent;
  border: 1px solid var(--lineas);
  color: var(--apagado);
  font: inherit;
  font-size: var(--nota);
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- componentes` y después `cd web && npm test -- estilos`
Esperado: PASAN los dos. El de estilos es el que avisa si se ha colado un color nuevo.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/componentes.tsx web/src/componentes.test.tsx web/src/estilos.css
git commit -m "$(cat <<'EOF'
feat(diseño): casillas, fila de macros y el chip de «lo de siempre»

La casilla es un botón con aria-pressed y no un input de tipo checkbox: no
vive en ningún formulario, cada toque es una petición. El [✓] va aria-hidden
y lo que se oye es «Multivitaminas, tomado».

FilaMacros escribe «Proteínas», «Hidratos» y «Grasas» enteras. Ni P/H/G ni
abreviaturas de tabla nutricional: hay quien no ha visto una en su vida. La
fibra y el azúcar se registran pero no se pintan; no hay misión de fibra y
cinco columnas no caben en un móvil.

El chip lleva dos botones, registrar y cambiar la cantidad, en vez de un
mantener pulsado. Un gesto largo no se ve, no tiene equivalente de teclado y
en el móvil pelea con el menú contextual del navegador.

Sin colores nuevos: todo sale de las once variables que ya vigila
estilos.test.ts.
EOF
)"
```

---

## Tarea 9 · `Nutricion.tsx`: el día

**Ficheros:**
- Crear: `web/src/pantallas/Nutricion.tsx`
- Crear: `web/src/pantallas/Nutricion.test.tsx`
- Modificar: `web/src/App.tsx`

**Interfaces:**
- Consume: `comidasDelDia`, `borrarComida`, `objetivoNutricional`, `diaDeHoy`,
  `TIPOS_COMIDA` de `../api`; `FilaMacros`, `Seccion`, `Boton`, `Comentario`,
  `TituloPantalla` de `../componentes`; `textoRestante` de `../formato`.
- Produce: la ruta `/nutricion` y el componente por defecto `Nutricion`.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
/* La pantalla que se abre tres o cuatro veces al día. Lo que no puede pasar: que un día
   vacío la rompa, y que se enseñe un objetivo que el usuario no ha configurado. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { borrarComida, comidasDelDia, diaDeHoy, objetivoNutricional } from "../api";
import Nutricion from "./Nutricion";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  comidasDelDia: vi.fn(),
  borrarComida: vi.fn(),
  objetivoNutricional: vi.fn(),
  diaDeHoy: vi.fn(),
}));

const VACIAS = {
  items: [] as never[],
  calories: 0,
};

const DIA_VACIO = {
  date: "2026-08-20",
  meals: { breakfast: VACIAS, lunch: VACIAS, dinner: VACIAS, snack: VACIAS },
  totals: { calories: 0, protein: 0, carbs: 0, fat: 0, fiber: 0, sugar: 0 },
  count: 0,
  calories_burned: 0,
};

const CAFE = {
  uuid: "c-1", meal_type: "breakfast" as const, food_item_id: 1,
  custom_food_name: null, quantity_grams: 200,
  calories: 88, protein: 4, carbs: 6, fat: 4, fiber: 0, sugar: 6,
  food_item: { id: 1, name: "Café con leche" },
};

const pintar = () => render(<MemoryRouter><Nutricion /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(diaDeHoy).mockResolvedValue({ date: "2026-08-20" } as never);
  vi.mocked(comidasDelDia).mockResolvedValue(DIA_VACIO as never);
  vi.mocked(objetivoNutricional).mockResolvedValue({
    goal: { daily_calories: 2000 }, has_goal: true,
  } as never);
});

test("un día sin nada registrado se ve entero y no se rompe", async () => {
  pintar();

  expect(await screen.findByText("Desayuno")).toBeTruthy();
  expect(screen.getByText("Comida")).toBeTruthy();
  expect(screen.getByText("Cena")).toBeTruthy();
  expect(screen.getByText("Tentempié")).toBeTruthy();
});

test("la fecha sale de /api/system/today y no del reloj del navegador", async () => {
  pintar();
  await screen.findByText("Desayuno");

  // A las 00:30 de Madrid el día del navegador ya no es el que puntúa.
  expect(vi.mocked(comidasDelDia)).toHaveBeenCalledWith("2026-08-20");
});

test("sin objetivo configurado se invita a configurarlo en vez de inventar un número", async () => {
  vi.mocked(objetivoNutricional).mockResolvedValue({
    goal: { daily_calories: 2400 }, has_goal: false,
  } as never);

  pintar();

  expect(await screen.findByText("todavía sin objetivo")).toBeTruthy();
  expect(screen.getByRole("link", { name: /CALCULAR MI OBJETIVO/ })).toBeTruthy();
  // La sugerencia del servidor no se enseña como si fuera suya.
  expect(screen.queryByText(/2.400/)).toBe(null);
});

test("con objetivo se dice cuánto queda", async () => {
  vi.mocked(comidasDelDia).mockResolvedValue({
    ...DIA_VACIO,
    meals: { ...DIA_VACIO.meals, breakfast: { items: [CAFE], calories: 88 } },
    totals: { calories: 1360, protein: 92, carbs: 140, fat: 45, fiber: 18, sugar: 30 },
    count: 1,
  } as never);

  pintar();

  expect(await screen.findByText("te quedan 640 kcal")).toBeTruthy();
});

test("quitar una entrada la borra y recarga el día", async () => {
  vi.mocked(comidasDelDia).mockResolvedValue({
    ...DIA_VACIO,
    meals: { ...DIA_VACIO.meals, breakfast: { items: [CAFE], calories: 88 } },
    count: 1,
  } as never);
  vi.mocked(borrarComida).mockResolvedValue({ message: "ok" });

  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "Quitar Café con leche" }));

  expect(vi.mocked(borrarComida)).toHaveBeenCalledWith("c-1");
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- Nutricion`
Esperado: FALLA — no existe `./Nutricion`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/pantallas/Nutricion.tsx`:

```tsx
import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import {
  ErrorApi, TIPOS_COMIDA, borrarComida, comidasDelDia, diaDeHoy,
  objetivoNutricional, type DiaDeComidas,
} from "../api";
import { Boton, Comentario, FilaMacros, Seccion, TituloPantalla } from "../componentes";
import { textoRestante } from "../formato";

export default function Nutricion() {
  const [dia, setDia] = useState<DiaDeComidas | null>(null);
  // null = todavía no se sabe; number = el objetivo guardado. La sugerencia que manda el
  // servidor cuando has_goal es false NO se usa: enseñarla sería darle al usuario un
  // objetivo que él no ha decidido.
  const [objetivo, setObjetivo] = useState<number | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);
  const [cargando, setCargando] = useState(true);

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      // La fecha la decide el servidor en Europe/Madrid. Usar la del navegador diría
      // «hoy» sobre un día distinto del que puntúa.
      const hoy = await diaDeHoy();
      const [comidas, meta] = await Promise.all([
        comidasDelDia(hoy.date),
        objetivoNutricional(),
      ]);
      setDia(comidas);
      setObjetivo(meta.has_goal ? meta.goal.daily_calories : null);
      setFallo(null);
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido conectar. Inténtalo otra vez.",
      );
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => { void cargar(); }, [cargar]);

  async function quitar(uuid: string) {
    try {
      await borrarComida(uuid);
      await cargar();
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido quitarla. Inténtalo otra vez.",
      );
    }
  }

  if (!dia) {
    return (
      <>
        <TituloPantalla pantalla="nutrición" />
        {cargando ? (
          <Comentario>cargando…</Comentario>
        ) : (
          <>
            <p className="aviso" role="alert">{fallo}</p>
            <Boton type="button" onClick={() => void cargar()}>REINTENTAR</Boton>
          </>
        )}
      </>
    );
  }

  return (
    <>
      <TituloPantalla pantalla="nutrición" />

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <p className="xp">{Math.round(dia.totals.calories)} kcal</p>
      <Comentario>{textoRestante(dia.totals.calories, objetivo)}</Comentario>
      <FilaMacros macros={dia.totals} />

      {objetivo === null && (
        <div className="acciones">
          <Link className="boton compacto" to="/nutricion/objetivo">
            <span aria-hidden="true">[ </span>CALCULAR MI OBJETIVO<span aria-hidden="true"> ]</span>
          </Link>
        </div>
      )}

      {TIPOS_COMIDA.map(({ clave, etiqueta }) => {
        const comida = dia.meals[clave];
        return (
          <Seccion
            key={clave}
            titulo={etiqueta}
            resumen={comida.items.length === 0 ? "sin registrar" : `${Math.round(comida.calories)} kcal`}
          >
            <ul className="lista-entradas">
              {comida.items.map((entrada) => {
                const nombre = entrada.food_item?.name ?? entrada.custom_food_name ?? "Sin nombre";
                return (
                  <li key={entrada.uuid}>
                    <span className="nombre">{nombre}</span>
                    <span className="cantidad">{Math.round(entrada.quantity_grams)} g</span>
                    <span className="kcal">{Math.round(entrada.calories)} kcal</span>
                    <button
                      type="button"
                      className="quitar"
                      // Un botón y no un gesto de deslizar: deslizar no se ve, no se puede
                      // teclear y ningún lector de pantalla lo anuncia.
                      aria-label={`Quitar ${nombre}`}
                      onClick={() => void quitar(entrada.uuid)}
                    >
                      <span aria-hidden="true">[ QUITAR ]</span>
                    </button>
                  </li>
                );
              })}
            </ul>

            <div className="acciones">
              <Link className="boton compacto" to={`/nutricion/anadir?tipo=${clave}`}>
                <span aria-hidden="true">[ </span>+ {etiqueta.toUpperCase()}
                <span aria-hidden="true"> ]</span>
              </Link>
            </div>
          </Seccion>
        );
      })}

      <div className="acciones">
        <Link className="boton compacto" to="/nutricion/recetas">
          <span aria-hidden="true">[ </span>RECETAS<span aria-hidden="true"> ]</span>
        </Link>
        <Link className="boton compacto" to="/nutricion/objetivo">
          <span aria-hidden="true">[ </span>MI OBJETIVO<span aria-hidden="true"> ]</span>
        </Link>
      </div>
    </>
  );
}
```

En `web/src/estilos.css`:

```css
.lista-entradas { padding: 0; margin: 0.5rem 0 0; list-style: none; }

.lista-entradas li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 48px;
  border-bottom: 1px solid var(--lineas);
  font-size: var(--cuerpo);
}

.lista-entradas .nombre { flex: 1; }
.lista-entradas .cantidad,
.lista-entradas .kcal { color: var(--apagado); font-size: var(--nota); }

.lista-entradas .quitar {
  min-height: 48px;
  padding: 0 0.5rem;
  background: transparent;
  border: 0;
  color: var(--apagado);
  font: inherit;
  font-size: var(--nota);
}
```

En `web/src/App.tsx`, dentro de `<Route element={<ConPestanas />}>`:

```tsx
        {/* Sub-pantallas de «hoy» (spec §8): la navegación no desaparece por estar
            apuntando la cena. */}
        <Route path="/nutricion" element={<Nutricion />} />
```

Y el `import Nutricion from "./pantallas/Nutricion";` arriba.

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- Nutricion`
Esperado: PASAN todos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/Nutricion.tsx web/src/pantallas/Nutricion.test.tsx web/src/App.tsx web/src/estilos.css
git commit -m "$(cat <<'EOF'
feat(nutrición): la pantalla del día

Las cuatro comidas, los totales y cuánto queda. La fecha sale de
/api/system/today y no del reloj del navegador: a las 00:30 de Madrid ya no
son el mismo día, y el que puntúa es el del servidor.

Sin objetivo configurado no se enseña la sugerencia que manda el servidor.
Es un cálculo suyo, no una decisión del usuario, y presentarla como propia
sería darle un objetivo de salud que él no ha elegido. En su sitio va la
invitación al asistente, que es justo donde el documento de la fase dice que
toca invitar.

Quitar una entrada es un botón y no un gesto de deslizar: deslizar no se ve,
no se puede teclear y ningún lector de pantalla lo anuncia.
EOF
)"
```

---

## Tarea 10 · `AnadirComida.tsx`: «lo de siempre», el camino de dos toques

Es el criterio duro de la fase. Esta tarea entrega el camino corto entero: abrir la
pantalla y pulsar un chip.

**Ficheros:**
- Crear: `web/src/pantallas/AnadirComida.tsx`
- Crear: `web/src/pantallas/AnadirComida.test.tsx`
- Modificar: `web/src/App.tsx`

**Interfaces:**
- Consume: `registrarComida`, `diaDeHoy`, `TIPOS_COMIDA` de `../api`; `recientes`,
  `apuntar` de `../recientes`; `ChipComida` de `../componentes`.
- Produce: la ruta `/nutricion/anadir` y el componente por defecto `AnadirComida`. Las
  tareas 11 y 12 le añaden el buscador y la entrada a mano.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
/* «Menos de cinco toques» es el criterio duro de la fase. Este fichero es el que lo
   demuestra: si alguien añade un paso de más al camino corto, aquí falla. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { diaDeHoy, registrarComida } from "../api";
import { apuntar } from "../recientes";
import AnadirComida from "./AnadirComida";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  diaDeHoy: vi.fn(),
  registrarComida: vi.fn(),
  buscarAlimentos: vi.fn(),
}));

// 44 kcal/100 g × 200 g = 88 kcal, que es lo que tiene que decir el chip.
const CAFE = { id: 1, nombre: "Café con leche", gramos: 200, tipo: "breakfast" as const, kcal100: 44 };

const pintar = (tipo = "breakfast") =>
  render(
    <MemoryRouter initialEntries={[`/nutricion/anadir?tipo=${tipo}`]}>
      <AnadirComida />
    </MemoryRouter>,
  );

beforeEach(() => {
  localStorage.clear();
  vi.mocked(diaDeHoy).mockResolvedValue({ date: "2026-08-20" } as never);
  vi.mocked(registrarComida).mockResolvedValue({
    message: "ok", meal_log: {}, system: {},
  } as never);
});

test("registrar un reciente es un solo toque dentro de la pantalla", async () => {
  apuntar(CAFE);
  pintar();

  const chip = await screen.findByRole("button", {
    name: "Café con leche, 200 g, 88 kilocalorías",
  });
  fireEvent.click(chip);

  expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
    date: "2026-08-20",
    meal_type: "breakfast",
    food_item_id: 1,
    quantity_grams: 200,
  });
});

test("los recientes son los de esta comida, no los de todas", async () => {
  apuntar(CAFE);
  apuntar({ id: 9, nombre: "Yogur", gramos: 125, tipo: "dinner", kcal100: 61 });

  pintar("breakfast");

  expect(await screen.findByText("Café con leche")).toBeTruthy();
  expect(screen.queryByText("Yogur")).toBe(null);
});

test("sin recientes no se enseña una lista vacía y muda", async () => {
  pintar();
  expect(await screen.findByText("todavía no has registrado nada aquí")).toBeTruthy();
});

test("si el registro falla se dice con palabras y el chip vuelve a poderse pulsar", async () => {
  const { ErrorApi } = await import("../api");
  vi.mocked(registrarComida).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  apuntar(CAFE);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: /Café con leche/ }));

  expect(await screen.findByText("No hay conexión. Comprueba el wifi o los datos.")).toBeTruthy();
  const chip = screen.getByRole("button", { name: /Café con leche/ }) as HTMLButtonElement;
  expect(chip.disabled).toBe(false);
});

test("un tipo de comida que no existe cae en desayuno en vez de romperse", async () => {
  pintar("cualquiercosa");
  expect(await screen.findByText(/Desayuno/)).toBeTruthy();
});
```

El primer test espera **88 kilocalorías** en el nombre accesible: son los 44 kcal/100 g del
`kcal100` que la tarea 5 ya guarda en el reciente, por los 200 g. No hace falta ninguna
petición para pintarlo.

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- AnadirComida`
Esperado: FALLA — no existe `./AnadirComida`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/pantallas/AnadirComida.tsx`:

```tsx
import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router";
import {
  ErrorApi, TIPOS_COMIDA, diaDeHoy, registrarComida, type TipoComida,
} from "../api";
import { ChipComida, Comentario, TituloPantalla } from "../componentes";
import { apuntar, recientes, type Reciente } from "../recientes";

/** El tipo viene de la URL, o sea de fuera. Cualquier cosa que no sea una de las cuatro
 *  claves del servidor cae en desayuno: un 422 por un parámetro mal escrito no es algo
 *  que el usuario pueda arreglar. */
function tipoDeLaUrl(valor: string | null): TipoComida {
  return TIPOS_COMIDA.some((t) => t.clave === valor) ? (valor as TipoComida) : "breakfast";
}

export default function AnadirComida() {
  const [parametros] = useSearchParams();
  const navegar = useNavigate();
  const tipo = tipoDeLaUrl(parametros.get("tipo"));
  const etiqueta = TIPOS_COMIDA.find((t) => t.clave === tipo)!.etiqueta;

  const [fecha, setFecha] = useState<string | null>(null);
  const [lista, setLista] = useState<Reciente[]>(() => recientes(tipo));
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    diaDeHoy()
      .then((hoy) => setFecha(hoy.date))
      .catch(() => setFallo("No hemos podido saber qué día es hoy. Comprueba la conexión."));
  }, []);

  useEffect(() => setLista(recientes(tipo)), [tipo]);

  async function registrar(reciente: Reciente, gramos: number) {
    if (!fecha) return;
    setGuardando(true);
    setFallo(null);
    try {
      await registrarComida({
        date: fecha,
        meal_type: tipo,
        food_item_id: reciente.id,
        quantity_grams: gramos,
      });
      apuntar({ ...reciente, gramos });
      navegar("/nutricion");
    } catch (error) {
      // El chip vuelve a poderse pulsar: el usuario querrá reintentar sin recargar.
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido registrarla. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }

  return (
    <>
      <TituloPantalla pantalla="añadir comida" />
      <Comentario>{etiqueta}</Comentario>

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Comentario decorativo>lo de siempre</Comentario>
      {lista.length === 0 ? (
        <Comentario>todavía no has registrado nada aquí</Comentario>
      ) : (
        <ul className="lista-chips">
          {lista.map((reciente) => (
            <ChipComida
              key={`${reciente.id}-${reciente.tipo}`}
              reciente={reciente}
              kcal={(reciente.kcal100 * reciente.gramos) / 100}
              disabled={guardando || !fecha}
              onRegistrar={() => void registrar(reciente, reciente.gramos)}
              onAjustar={() => {
                const escrito = prompt(`¿Cuántos gramos de ${reciente.nombre}?`, String(reciente.gramos));
                const gramos = Number(escrito);
                if (Number.isFinite(gramos) && gramos >= 1 && gramos <= 5000) {
                  void registrar(reciente, gramos);
                }
              }}
            />
          ))}
        </ul>
      )}
    </>
  );
}
```

```
ponytail: ajustar la cantidad usa prompt(). Es feo y no se puede estilar, pero es
nativo, accesible y son cero líneas de diálogo propio. El techo: si molesta cómo se ve,
se sustituye por un campo desplegable dentro del propio chip.
```

En `web/src/App.tsx`, junto a la ruta de la tarea 9:

```tsx
        <Route path="/nutricion/anadir" element={<AnadirComida />} />
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- AnadirComida` y `cd web && npm test -- recientes`
Esperado: PASAN los dos. El de recientes se vuelve a pasar porque esta pantalla es la
primera que llama a `apuntar()` de verdad.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/AnadirComida.tsx web/src/pantallas/AnadirComida.test.tsx web/src/App.tsx
git commit -m "$(cat <<'EOF'
feat(nutrición): «lo de siempre», el camino de dos toques

Registrar una comida es la acción más repetida de la aplicación y el
criterio duro de la fase es que cueste menos de cinco toques. Repetir el
desayuno son dos: abrir la pantalla y pulsar el chip.

El test lo fija. Si alguien añade un paso de más al camino corto, falla ahí
y no en una revisión seis meses después.

El tipo de comida viene de la URL, o sea de fuera: cualquier cosa que no sea
una de las cuatro claves del servidor cae en desayuno. Un 422 por un
parámetro mal escrito no es algo que el usuario pueda arreglar.
EOF
)"
```

---

## Tarea 11 · `AnadirComida.tsx`: el buscador

**Ficheros:**
- Modificar: `web/src/pantallas/AnadirComida.tsx`
- Modificar: `web/src/pantallas/AnadirComida.test.tsx`

**Interfaces:**
- Consume: `buscarAlimentos` de `../api`, `macrosPara` de `../formato`.
- Produce: nada nuevo hacia fuera.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
import { buscarAlimentos } from "../api";

const POLLO = {
  id: 7, name: "Pechuga de pollo", brand: null, category: "carnes", unit: "g" as const,
  is_verified: true,
  calories_per_100g: 165, protein_per_100g: 31, carbs_per_100g: 0,
  fat_per_100g: 3.6, fiber_per_100g: 0, sugar_per_100g: 0,
};

test("el buscador espera antes de pedir, no una petición por tecla", async () => {
  vi.useFakeTimers();
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  const campo = await screen.findByLabelText("Buscar un alimento");
  fireEvent.change(campo, { target: { value: "p" } });
  fireEvent.change(campo, { target: { value: "po" } });
  fireEvent.change(campo, { target: { value: "pol" } });

  // 1.506 alimentos y una conexión de móvil: una petición por tecla es tirar datos.
  expect(vi.mocked(buscarAlimentos)).not.toHaveBeenCalled();

  await vi.advanceTimersByTimeAsync(300);

  expect(vi.mocked(buscarAlimentos)).toHaveBeenCalledTimes(1);
  expect(vi.mocked(buscarAlimentos)).toHaveBeenCalledWith("pol");
  vi.useRealTimers();
});

test("elegir un alimento precarga 100 g y enseña los macros de esa cantidad", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "pollo" },
  });
  fireEvent.click(await screen.findByRole("button", { name: /Pechuga de pollo/ }));

  const cantidad = screen.getByLabelText("Cantidad en gramos") as HTMLInputElement;
  expect(cantidad.value).toBe("100");
  expect(screen.getByText("165 kcal")).toBeTruthy();
});

test("cambiar la cantidad recalcula los macros al vuelo", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "pollo" },
  });
  fireEvent.click(await screen.findByRole("button", { name: /Pechuga de pollo/ }));
  fireEvent.change(screen.getByLabelText("Cantidad en gramos"), { target: { value: "150" } });

  expect(screen.getByText("248 kcal")).toBeTruthy();
});

test("añadir manda el id y los gramos, y deja el alimento en los recientes", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "pollo" },
  });
  fireEvent.click(await screen.findByRole("button", { name: /Pechuga de pollo/ }));
  fireEvent.change(screen.getByLabelText("Cantidad en gramos"), { target: { value: "150" } });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR" }));

  expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
    date: "2026-08-20", meal_type: "breakfast", food_item_id: 7, quantity_grams: 150,
  });
});

test("una búsqueda sin resultados lo dice y ofrece crear el alimento", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "bizcocho de la abuela" },
  });

  expect(await screen.findByText("no hemos encontrado nada con ese nombre")).toBeTruthy();
  expect(screen.getByRole("link", { name: /CREAR UN ALIMENTO/ })).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- AnadirComida`
Esperado: FALLAN los cinco nuevos — no existe el campo «Buscar un alimento».

- [ ] **Paso 3 · Escribir la implementación mínima**

En `web/src/pantallas/AnadirComida.tsx`, añade el estado y el efecto de la espera:

```tsx
const ESPERA_MS = 300;

// … dentro del componente, junto al resto del estado:
  const [texto, setTexto] = useState("");
  const [encontrados, setEncontrados] = useState<Alimento[] | null>(null);
  const [elegido, setElegido] = useState<Alimento | null>(null);
  const [gramos, setGramos] = useState(100);

  // Una petición por tecla contra un catálogo de 1.506 alimentos, desde una conexión de
  // móvil, es tirar datos. Se espera a que el usuario pare de escribir.
  useEffect(() => {
    const limpio = texto.trim();
    if (limpio.length < 2) {
      setEncontrados(null);
      return;
    }
    const temporizador = setTimeout(() => {
      buscarAlimentos(limpio)
        .then(setEncontrados)
        .catch(() => setFallo("No hemos podido buscar. Comprueba la conexión."));
    }, ESPERA_MS);
    return () => clearTimeout(temporizador);
  }, [texto]);
```

Y debajo de la lista de chips, el buscador:

```tsx
      <Comentario decorativo>buscar en el catálogo</Comentario>
      <Campo
        etiqueta="Buscar un alimento"
        name="buscar"
        type="search"
        autoComplete="off"
        value={texto}
        onChange={(e) => { setTexto(e.target.value); setElegido(null); }}
      />

      {encontrados?.length === 0 && (
        <>
          <Comentario>no hemos encontrado nada con ese nombre</Comentario>
          <div className="acciones">
            <Link className="boton compacto" to="/nutricion/alimento/nuevo">
              <span aria-hidden="true">[ </span>CREAR UN ALIMENTO<span aria-hidden="true"> ]</span>
            </Link>
          </div>
        </>
      )}

      {!elegido && encontrados && encontrados.length > 0 && (
        <ul className="lista-chips">
          {encontrados.map((alimento) => (
            <li key={alimento.id} className="chip-comida">
              <button
                type="button"
                className="chip-principal"
                aria-label={`${alimento.name}, ${Math.round(alimento.calories_per_100g)} kilocalorías por 100 ${alimento.unit}`}
                onClick={() => { setElegido(alimento); setGramos(100); }}
              >
                <span className="nombre" aria-hidden="true">{alimento.name}</span>
                <span className="kcal" aria-hidden="true">
                  {Math.round(alimento.calories_per_100g)} kcal/100 {alimento.unit}
                </span>
              </button>
            </li>
          ))}
        </ul>
      )}

      {elegido && (
        <>
          <Comentario>{elegido.name}</Comentario>
          <Campo
            etiqueta="Cantidad en gramos"
            name="gramos"
            type="number"
            inputMode="numeric"
            min="1"
            max="5000"
            value={gramos}
            onChange={(e) => setGramos(Number(e.target.value))}
          />
          <p className="xp">{Math.round(macrosPara(elegido, gramos).calories)} kcal</p>
          <FilaMacros macros={macrosPara(elegido, gramos)} />
          <Boton
            type="button"
            disabled={guardando || !fecha || gramos < 1 || gramos > 5000}
            onClick={() => void registrar(
              { id: elegido.id, nombre: elegido.name, gramos, tipo, kcal100: elegido.calories_per_100g },
              gramos,
            )}
          >
            AÑADIR
          </Boton>
        </>
      )}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- AnadirComida`
Esperado: PASAN todos, los de la tarea 10 incluidos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/AnadirComida.tsx web/src/pantallas/AnadirComida.test.tsx
git commit -m "$(cat <<'EOF'
feat(nutrición): el buscador del catálogo

Cuatro toques y una escritura para algo que no está en los recientes:
escribir, elegir, ajustar la cantidad y añadir.

La búsqueda espera 300 ms a que el usuario pare de escribir. Una petición
por tecla contra 1.506 alimentos, desde una conexión de móvil, es tirar
datos de alguien que a lo mejor los tiene contados. El test lo fija
comprobando que tres teclas seguidas son una sola petición.

Los macros se recalculan al vuelo con la misma función que usa el servidor,
redondeada igual, para que la cifra no cambie sola al guardar.

Sin resultados se ofrece crear el alimento, que es lo que el usuario iba a
querer hacer a continuación.
EOF
)"
```

---

## Tarea 12 · `AnadirComida.tsx`: la entrada a mano

Para lo que no está en el catálogo y tampoco merece crearse como alimento: el menú del día
de un restaurante, algo de casa de alguien.

**Ficheros:**
- Modificar: `web/src/pantallas/AnadirComida.tsx`
- Modificar: `web/src/pantallas/AnadirComida.test.tsx`

**Interfaces:**
- Consume: `registrarComida` con la forma `custom_food_name`.
- Produce: nada nuevo hacia fuera.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
test("a mano se manda el nombre escrito y los macros, sin id de catálogo", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "A MANO" }));
  fireEvent.change(screen.getByLabelText("Qué has comido"), { target: { value: "Menú del día" } });
  fireEvent.change(screen.getByLabelText("Calorías"), { target: { value: "780" } });
  fireEvent.change(screen.getByLabelText("Proteínas en gramos"), { target: { value: "35" } });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR" }));

  expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
    date: "2026-08-20",
    meal_type: "breakfast",
    custom_food_name: "Menú del día",
    calories: 780,
    protein: 35,
    carbs: 0,
    fat: 0,
  });
});

test("a mano no se puede añadir sin nombre ni sin calorías", async () => {
  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "A MANO" }));

  const anadir = screen.getByRole("button", { name: "AÑADIR" }) as HTMLButtonElement;
  expect(anadir.disabled).toBe(true);

  fireEvent.change(screen.getByLabelText("Qué has comido"), { target: { value: "Menú" } });
  expect((screen.getByRole("button", { name: "AÑADIR" }) as HTMLButtonElement).disabled).toBe(true);

  fireEvent.change(screen.getByLabelText("Calorías"), { target: { value: "780" } });
  expect((screen.getByRole("button", { name: "AÑADIR" }) as HTMLButtonElement).disabled).toBe(false);
});

test("lo escrito a mano no entra en los recientes", async () => {
  const { recientes } = await import("../recientes");
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "A MANO" }));
  fireEvent.change(screen.getByLabelText("Qué has comido"), { target: { value: "Menú del día" } });
  fireEvent.change(screen.getByLabelText("Calorías"), { target: { value: "780" } });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR" }));

  // Sin food_item_id no se puede volver a registrar de un toque, así que un chip suyo
  // sería un botón que no hace lo que promete.
  await vi.waitFor(() => expect(recientes("breakfast")).toEqual([]));
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- AnadirComida`
Esperado: FALLAN los tres — no existe el botón «A MANO».

- [ ] **Paso 3 · Escribir la implementación mínima**

En `AnadirComida.tsx`, añade el estado y la función:

```tsx
  const [aMano, setAMano] = useState(false);
  const [nombre, setNombre] = useState("");
  const [kcal, setKcal] = useState("");
  const [proteina, setProteina] = useState("");
  const [hidratos, setHidratos] = useState("");
  const [grasas, setGrasas] = useState("");

  const numero = (texto: string) => {
    const n = Number(texto);
    return Number.isFinite(n) && n >= 0 ? n : 0;
  };

  async function registrarAMano() {
    if (!fecha) return;
    setGuardando(true);
    setFallo(null);
    try {
      await registrarComida({
        date: fecha,
        meal_type: tipo,
        custom_food_name: nombre.trim(),
        calories: numero(kcal),
        protein: numero(proteina),
        carbs: numero(hidratos),
        fat: numero(grasas),
      });
      // A propósito, no se apunta en los recientes: sin food_item_id no se puede volver a
      // registrar de un toque, y un chip que no registra es un botón que miente.
      navegar("/nutricion");
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido registrarla. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }
```

Y el bloque de interfaz, debajo del buscador:

```tsx
      <div className="acciones">
        <Boton type="button" compacto onClick={() => setAMano((v) => !v)}>
          {aMano ? "VOLVER AL BUSCADOR" : "A MANO"}
        </Boton>
      </div>

      {aMano && (
        <>
          <Comentario decorativo>si no está en el catálogo</Comentario>
          <Campo etiqueta="Qué has comido" name="nombre" value={nombre}
                 onChange={(e) => setNombre(e.target.value)} />
          <Campo etiqueta="Calorías" name="kcal" type="number" inputMode="numeric" min="0"
                 value={kcal} onChange={(e) => setKcal(e.target.value)} />
          <Campo etiqueta="Proteínas en gramos" name="proteina" type="number"
                 inputMode="decimal" min="0" value={proteina}
                 onChange={(e) => setProteina(e.target.value)} />
          <Campo etiqueta="Hidratos en gramos" name="hidratos" type="number"
                 inputMode="decimal" min="0" value={hidratos}
                 onChange={(e) => setHidratos(e.target.value)} />
          <Campo etiqueta="Grasas en gramos" name="grasas" type="number"
                 inputMode="decimal" min="0" value={grasas}
                 onChange={(e) => setGrasas(e.target.value)} />
          <Boton
            type="button"
            disabled={guardando || !fecha || nombre.trim() === "" || kcal.trim() === ""}
            onClick={() => void registrarAMano()}
          >
            AÑADIR
          </Boton>
        </>
      )}
```

⚠️ Cuando `aMano` está activo, **el buscador y su botón `AÑADIR` no se pintan**: si no,
habría dos botones con el mismo nombre y los tests de la tarea 11 empezarían a fallar por
ambigüedad. Envuelve el bloque del buscador en `{!aMano && ( … )}`.

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- AnadirComida`
Esperado: PASAN todos, los de las tareas 10 y 11 incluidos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/AnadirComida.tsx web/src/pantallas/AnadirComida.test.tsx
git commit -m "$(cat <<'EOF'
feat(nutrición): la entrada a mano

Para el menú del día de un restaurante o algo de casa de alguien: lo que no
está en el catálogo y tampoco merece crearse como alimento propio.

Lo escrito a mano NO entra en los recientes. Sin food_item_id no se puede
volver a registrar de un toque, así que su chip sería un botón que promete
algo que no hace.

Nombre y calorías son obligatorios, que es exactamente lo que exige el
servidor: sin uno de los dos responde 422, y es mejor que el botón no se
pueda pulsar a que el usuario reciba un error después de escribirlo todo.
EOF
)"
```

---

## Tarea 13 · `CrearAlimento.tsx`, sin la foto todavía

**Ficheros:**
- Crear: `web/src/pantallas/CrearAlimento.tsx`
- Crear: `web/src/pantallas/CrearAlimento.test.tsx`
- Modificar: `web/src/App.tsx`

**Interfaces:**
- Consume: `crearAlimento` de `../api`.
- Produce: la ruta `/nutricion/alimento/nuevo` y el componente por defecto
  `CrearAlimento`. La tarea 14 le añade la foto.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { crearAlimento } from "../api";
import CrearAlimento from "./CrearAlimento";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  crearAlimento: vi.fn(),
}));

const pintar = () => render(<MemoryRouter><CrearAlimento /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(crearAlimento).mockResolvedValue({ message: "ok", food: { id: 42 } } as never);
});

test("crear un alimento manda los macros por 100 g", () => {
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Bizcocho de la abuela" } });
  fireEvent.change(screen.getByLabelText("Calorías por 100 g"), { target: { value: "410" } });
  fireEvent.change(screen.getByLabelText("Proteínas por 100 g"), { target: { value: "6" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  expect(vi.mocked(crearAlimento)).toHaveBeenCalledWith({
    name: "Bizcocho de la abuela",
    brand: null,
    category: null,
    unit: "g",
    calories_per_100g: 410,
    protein_per_100g: 6,
    carbs_per_100g: 0,
    fat_per_100g: 0,
    fiber_per_100g: 0,
    sugar_per_100g: 0,
  });
});

test("nunca se manda from_ingredients, que haría el alimento visible para todos", () => {
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Algo" } });
  fireEvent.change(screen.getByLabelText("Calorías por 100 g"), { target: { value: "100" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  const enviado = vi.mocked(crearAlimento).mock.calls[0][0];
  expect("from_ingredients" in enviado).toBe(false);
});

test("sin nombre o sin calorías no se puede guardar", () => {
  pintar();
  expect((screen.getByRole("button", { name: "GUARDAR" }) as HTMLButtonElement).disabled).toBe(true);
});

test("se puede elegir mililitros para lo que se bebe", () => {
  pintar();

  fireEvent.click(screen.getByRole("button", { name: "Medir en mililitros" }));

  expect(screen.getByLabelText("Calorías por 100 ml")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- CrearAlimento`
Esperado: FALLA — no existe `./CrearAlimento`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/pantallas/CrearAlimento.tsx`:

```tsx
import { useState } from "react";
import { useNavigate } from "react-router";
import { ErrorApi, crearAlimento } from "../api";
import { Boton, Campo, Comentario, TituloPantalla } from "../componentes";

/** Los seis macros que acepta el servidor, con su etiqueta. Solo las calorías son
 *  obligatorias; lo demás se queda en cero si no se sabe, que es mejor que obligar a
 *  inventárselo. */
const MACROS = [
  { campo: "calories_per_100g", etiqueta: "Calorías", obligatorio: true },
  { campo: "protein_per_100g", etiqueta: "Proteínas", obligatorio: false },
  { campo: "carbs_per_100g", etiqueta: "Hidratos", obligatorio: false },
  { campo: "fat_per_100g", etiqueta: "Grasas", obligatorio: false },
  { campo: "fiber_per_100g", etiqueta: "Fibra", obligatorio: false },
  { campo: "sugar_per_100g", etiqueta: "Azúcares", obligatorio: false },
] as const;

export default function CrearAlimento() {
  const navegar = useNavigate();
  const [nombre, setNombre] = useState("");
  const [marca, setMarca] = useState("");
  const [unidad, setUnidad] = useState<"g" | "ml">("g");
  const [valores, setValores] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  const numero = (campo: string) => {
    const n = Number(valores[campo] ?? "");
    return Number.isFinite(n) && n >= 0 ? n : 0;
  };

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      // ⚠️ Sin `from_ingredients`. Ese campo hace que el alimento nazca en el catálogo
      // global, con user_id a null y visible para todo el mundo. Aquí siempre es personal.
      await crearAlimento({
        name: nombre.trim(),
        brand: marca.trim() || null,
        category: null,
        unit: unidad,
        calories_per_100g: numero("calories_per_100g"),
        protein_per_100g: numero("protein_per_100g"),
        carbs_per_100g: numero("carbs_per_100g"),
        fat_per_100g: numero("fat_per_100g"),
        fiber_per_100g: numero("fiber_per_100g"),
        sugar_per_100g: numero("sugar_per_100g"),
      });
      navegar("/nutricion");
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido guardarlo. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }

  const listo = nombre.trim() !== "" && (valores.calories_per_100g ?? "").trim() !== "";

  return (
    <>
      <TituloPantalla pantalla="crear alimento" />
      <Comentario>para lo que no está en el catálogo</Comentario>

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Nombre" name="nombre" value={nombre}
             onChange={(e) => setNombre(e.target.value)} />
      <Campo etiqueta="Marca, si la tiene" name="marca" value={marca}
             onChange={(e) => setMarca(e.target.value)} />

      <div className="acciones">
        <Boton
          type="button"
          compacto
          aria-pressed={unidad === "ml"}
          aria-label={unidad === "g" ? "Medir en mililitros" : "Medir en gramos"}
          onClick={() => setUnidad(unidad === "g" ? "ml" : "g")}
        >
          {unidad === "g" ? "MEDIR EN MILILITROS" : "MEDIR EN GRAMOS"}
        </Boton>
      </div>

      {MACROS.map(({ campo, etiqueta }) => (
        <Campo
          key={campo}
          etiqueta={`${etiqueta} por 100 ${unidad}`}
          name={campo}
          type="number"
          inputMode="decimal"
          min="0"
          value={valores[campo] ?? ""}
          onChange={(e) => setValores({ ...valores, [campo]: e.target.value })}
        />
      ))}

      <Boton type="button" disabled={!listo || guardando} onClick={() => void guardar()}>
        {guardando ? "GUARDANDO…" : "GUARDAR"}
      </Boton>
    </>
  );
}
```

En `App.tsx`:

```tsx
        <Route path="/nutricion/alimento/nuevo" element={<CrearAlimento />} />
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- CrearAlimento`
Esperado: PASAN todos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/CrearAlimento.tsx web/src/pantallas/CrearAlimento.test.tsx web/src/App.tsx
git commit -m "$(cat <<'EOF'
feat(nutrición): crear un alimento propio

Nombre, marca, unidad y los seis macros por 100 g o por 100 ml. Solo el
nombre y las calorías son obligatorios: obligar a rellenar los seis lleva a
que alguien se los invente, y un macro inventado es peor que un cero.

No se manda from_ingredients, y hay un test que lo vigila. Ese campo hace
que el alimento nazca en el catálogo global del sistema, con user_id a null
y visible para todo el mundo. Aquí los alimentos son siempre personales.
EOF
)"
```

---

## Tarea 14 · La foto: redimensionar y subir

Depende de la tarea 1: sin ella, la URL que devuelve el servidor apunta a un directorio que
no existe, y sin el `blob:` de la CSP la vista previa sale en blanco en producción.

**Ficheros:**
- Crear: `web/src/foto.ts`
- Crear: `web/src/foto.test.ts`
- Modificar: `web/src/componentes.tsx`, `web/src/estilos.css`
- Modificar: `web/src/pantallas/CrearAlimento.tsx` y su test

**Interfaces:**
- Consume: `subir` de `../api`.
- Produce: `encoger(fichero): Promise<File>` en `foto.ts`; el componente `FotoElegible`.

- [ ] **Paso 1 · Escribir el test que falla**

Crea `web/src/foto.test.ts`:

```ts
/* Una foto de móvil son 4 MB y el servidor acepta 2. Sin encoger, subir una foto falla
   siempre y el usuario no sabe por qué. Y hay quien tiene los datos contados. */

import { expect, test, vi } from "vitest";
import { LADO_MAXIMO, encoger } from "./foto";

/* jsdom no implementa ni createImageBitmap ni el 2d de canvas, así que se sustituyen por
   los mínimos que esta función usa. Lo que se prueba es la decisión —qué tamaño elige y
   qué formato pide—, no el dibujado, que es del navegador. */
function navegadorConImagenDe(ancho: number, alto: number) {
  vi.stubGlobal("createImageBitmap", vi.fn(async () => ({ width: ancho, height: alto, close() {} })));

  const dibujado: { w: number; h: number }[] = [];
  const pedido: { tipo: string; calidad: number }[] = [];

  vi.spyOn(HTMLCanvasElement.prototype, "getContext").mockReturnValue({
    drawImage: (_img: unknown, _x: number, _y: number, w: number, h: number) =>
      dibujado.push({ w, h }),
  } as unknown as CanvasRenderingContext2D);

  vi.spyOn(HTMLCanvasElement.prototype, "toBlob").mockImplementation(
    (cb: BlobCallback, tipo?: string, calidad?: number) => {
      pedido.push({ tipo: tipo ?? "", calidad: calidad ?? 0 });
      cb(new Blob(["x"], { type: "image/jpeg" }));
    },
  );

  return { dibujado, pedido };
}

test("una foto grande se encoge por el lado mayor y mantiene la proporción", async () => {
  const { dibujado } = navegadorConImagenDe(4032, 3024);

  await encoger(new File(["x"], "foto.jpg", { type: "image/jpeg" }));

  expect(dibujado[0].w).toBe(LADO_MAXIMO);
  expect(dibujado[0].h).toBe(Math.round((3024 / 4032) * LADO_MAXIMO));
});

test("una foto vertical se encoge por el alto, que es su lado mayor", async () => {
  const { dibujado } = navegadorConImagenDe(3024, 4032);

  await encoger(new File(["x"], "foto.jpg", { type: "image/jpeg" }));

  expect(dibujado[0].h).toBe(LADO_MAXIMO);
});

test("una foto que ya es pequeña no se agranda", async () => {
  const { dibujado } = navegadorConImagenDe(600, 400);

  await encoger(new File(["x"], "foto.jpg", { type: "image/jpeg" }));

  expect(dibujado[0]).toEqual({ w: 600, h: 400 });
});

test("sale siempre como JPEG comprimido, aunque entre un PNG", async () => {
  const { pedido } = navegadorConImagenDe(2000, 2000);

  const salida = await encoger(new File(["x"], "captura.png", { type: "image/png" }));

  expect(pedido[0].tipo).toBe("image/jpeg");
  expect(pedido[0].calidad).toBe(0.8);
  expect(salida.type).toBe("image/jpeg");
  expect(salida.name.endsWith(".jpg")).toBe(true);
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- foto`
Esperado: FALLA — no existe `./foto`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/foto.ts`:

```ts
/* Una foto de un móvil actual son 3 o 4 MB y el servidor acepta 2. Sin encoger, subir una
   foto falla siempre y el mensaje no explica nada. Además hay gente con los datos
   contados: 200 KB frente a 4 MB es la diferencia entre subirla y no hacerlo.

   Se hace con <canvas>, que es del navegador: ninguna dependencia nueva. */

/** El lado mayor. A 1280 una foto de plato se ve perfectamente en un móvil y pesa unos
 *  200 KB en JPEG 0.8. */
export const LADO_MAXIMO = 1280;

const CALIDAD = 0.8;

export async function encoger(fichero: File): Promise<File> {
  const imagen = await createImageBitmap(fichero);

  // Una foto que ya es pequeña no se agranda: interpolar hacia arriba solo añade peso y
  // le quita nitidez.
  const escala = Math.min(1, LADO_MAXIMO / Math.max(imagen.width, imagen.height));
  const ancho = Math.round(imagen.width * escala);
  const alto = Math.round(imagen.height * escala);

  const lienzo = document.createElement("canvas");
  lienzo.width = ancho;
  lienzo.height = alto;
  lienzo.getContext("2d")!.drawImage(imagen, 0, 0, ancho, alto);
  imagen.close?.();

  const blob = await new Promise<Blob | null>((resolver) =>
    // Siempre JPEG, aunque entre un PNG: una captura de pantalla en PNG puede pesar más
    // que la foto original, y el servidor acepta los tres formatos igual.
    lienzo.toBlob(resolver, "image/jpeg", CALIDAD),
  );

  if (!blob) throw new Error("el navegador no pudo convertir la imagen");

  const nombre = fichero.name.replace(/\.[^.]+$/, "") + ".jpg";
  return new File([blob], nombre, { type: "image/jpeg" });
}
```

En `componentes.tsx`:

```tsx
/** Elegir una foto, verla antes de subirla y quitarla.
 *
 *  ⚠️ La vista previa es un `blob:` de la propia página, y la CSP del servidor lleva
 *  `img-src 'self' blob:` justo por esto (tarea 1). Con `'self'` a secas el hueco sale en
 *  blanco **solo en producción** y sin ningún error visible. */
export function FotoElegible({
  vistaPrevia,
  onElegir,
  onQuitar,
  disabled,
}: {
  vistaPrevia: string | null;
  onElegir: (fichero: File) => void;
  onQuitar: () => void;
  disabled?: boolean;
}) {
  return (
    <div className="foto-elegible">
      <label className="campo">
        <span>Foto, si quieres</span>
        <input
          type="file"
          accept="image/*"
          disabled={disabled}
          onChange={(e) => {
            const fichero = e.target.files?.[0];
            if (fichero) onElegir(fichero);
          }}
        />
      </label>

      {vistaPrevia && (
        <>
          <img className="vista-previa" src={vistaPrevia} alt="La foto que has elegido" />
          <Boton type="button" compacto disabled={disabled} onClick={onQuitar}>
            QUITAR LA FOTO
          </Boton>
        </>
      )}
    </div>
  );
}
```

En `estilos.css`:

```css
.foto-elegible { margin-top: 0.75rem; }

.vista-previa {
  display: block;
  max-width: 100%;
  margin: 0.5rem 0;
  border: 1px solid var(--lineas);
}
```

En `CrearAlimento.tsx`, el estado y la subida encadenada:

```tsx
  const [foto, setFoto] = useState<File | null>(null);
  const [vistaPrevia, setVistaPrevia] = useState<string | null>(null);

  // Un objeto de URL se queda en memoria hasta que alguien lo suelta.
  useEffect(() => {
    if (!foto) return setVistaPrevia(null);
    const url = URL.createObjectURL(foto);
    setVistaPrevia(url);
    return () => URL.revokeObjectURL(url);
  }, [foto]);
```

Y dentro de `guardar()`, después de crear el alimento:

```tsx
      const creado = await crearAlimento({ /* … lo de la tarea 13 … */ });

      // La foto va después y en su propia petición: el endpoint de crear no la acepta.
      // Si falla, el alimento ya está guardado — se avisa, pero no se deshace nada.
      if (foto) {
        try {
          await subir(`/foods/${creado.food.id}/image`, "image", await encoger(foto));
        } catch {
          setFallo("El alimento se ha guardado, pero la foto no se ha podido subir.");
          setGuardando(false);
          return;
        }
      }

      navegar("/nutricion");
```

Y el test nuevo en `CrearAlimento.test.tsx`:

```tsx
test("la foto se sube después de crear el alimento, contra el id que devolvió", async () => {
  const { subir } = await import("../api");
  vi.mocked(subir).mockResolvedValue({ image_path: "nutrition/foods/x.jpg" } as never);
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Bizcocho" } });
  fireEvent.change(screen.getByLabelText("Calorías por 100 g"), { target: { value: "410" } });
  fireEvent.change(screen.getByLabelText("Foto, si quieres"), {
    target: { files: [new File(["x"], "b.jpg", { type: "image/jpeg" })] },
  });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() =>
    expect(vi.mocked(subir)).toHaveBeenCalledWith("/foods/42/image", "image", expect.any(File)),
  );
});
```

⚠️ Ese test necesita `encoger` mockeada (`vi.mock("../foto", …)`) o el `createImageBitmap`
de jsdom lo tumbará. Mockéala devolviendo el mismo fichero.

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- foto` y `cd web && npm test -- CrearAlimento`
Esperado: PASAN los dos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/foto.ts web/src/foto.test.ts web/src/componentes.tsx web/src/estilos.css web/src/pantallas/CrearAlimento.tsx web/src/pantallas/CrearAlimento.test.tsx
git commit -m "$(cat <<'EOF'
feat(nutrición): las fotos, encogidas antes de subirlas

Una foto de móvil son 3 o 4 MB y el servidor acepta 2, así que sin encoger
la subida falla siempre. Y hay gente con los datos contados: 200 KB frente a
4 MB es la diferencia entre subirla y no hacerlo.

Se hace con <canvas>, que es del navegador: ninguna dependencia nueva. Sale
siempre JPEG aunque entre un PNG, porque una captura en PNG puede pesar más
que la foto original.

La foto va en su propia petición, después de crear el alimento, porque el
endpoint de crear no la acepta. Si esa segunda falla, el alimento ya está
guardado: se avisa y no se deshace nada.

La vista previa es un blob: de la propia página, y por eso la tarea 1 abrió
la CSP. Con img-src 'self' a secas el hueco sale en blanco solo en
producción y sin ningún error visible.
EOF
)"
```

---

## Tarea 15 · `habitos.tsx`: agua, con incrementos optimistas

**Ficheros:**
- Crear: `web/src/pantallas/habitos.tsx`
- Crear: `web/src/pantallas/habitos.test.tsx`

**Interfaces:**
- Consume: `agua`, `anadirAgua` de `../api`; `BarraBloques`, `Boton`, `Seccion`,
  `Comentario` de `../componentes`; `textoAgua` de `../formato`.
- Produce: `SeccionAgua({ fecha, alGanar })`. `alGanar` recibe el bloque `system` de cada
  respuesta y lo sube a «hoy», que es quien abre la ventana del Sistema.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
/* Beber agua es lo que más veces se toca al día. Es optimista a propósito, y lo que hay
   que demostrar es lo otro: que cuando falla, la barra vuelve y se dice con palabras. */

import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, expect, test, vi } from "vitest";
import { ErrorApi, agua, anadirAgua } from "../api";
import { SeccionAgua } from "./habitos";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  agua: vi.fn(),
  anadirAgua: vi.fn(),
}));

const pintar = (alGanar = () => {}) =>
  render(<SeccionAgua fecha="2026-08-20" alGanar={alGanar} />);

beforeEach(() => {
  vi.mocked(agua).mockResolvedValue({
    date: "2026-08-20", total_ml: 1250, goal_ml: 2000, pct: 63, entries: [],
  });
});

test("un vaso sube la barra antes de que conteste el servidor", async () => {
  // Una petición que no resuelve nunca: si la barra espera, este test lo caza.
  vi.mocked(anadirAgua).mockReturnValue(new Promise(() => {}) as never);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,5 de 2 litros")).toBeTruthy();
});

test("si el vaso no se guarda, la barra vuelve atrás y se dice por qué", async () => {
  vi.mocked(anadirAgua).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,25 de 2 litros")).toBeTruthy();
  expect(screen.getByText("No se ha podido guardar el vaso. Comprueba la conexión.")).toBeTruthy();
});

test("el total que manda el servidor gana al optimista", async () => {
  // Otro dispositivo pudo apuntar agua entre medias.
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 1900, goal_ml: 2000, pct: 95, system: {} as never,
  });
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,9 de 2 litros")).toBeTruthy();
});

test("llegar al objetivo sube el bloque system, que es quien abre la ventana", async () => {
  const ganados: unknown[] = [];
  const bloque = { achievements_unlocked: [{ key: "hydrated", name: "Hidratado" }] };
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 2000, goal_ml: 2000, pct: 100, system: bloque as never,
  });
  pintar((s) => ganados.push(s));

  fireEvent.click(await screen.findByRole("button", { name: "+MEDIO LITRO" }));

  await vi.waitFor(() => expect(ganados).toEqual([bloque]));
});

test("la barra se oye como un porcentaje, no como veinte caracteres", async () => {
  pintar();
  const barra = await screen.findByRole("progressbar");
  expect(barra.getAttribute("aria-valuetext")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- habitos`
Esperado: FALLA — no existe `./habitos`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/pantallas/habitos.tsx`:

```tsx
/* Las cuatro secciones de hábitos que viven dentro de «hoy». Van juntas en un fichero
   porque son pequeñas y todas hacen lo mismo: leer su día, tocar, reconciliar.

   El spec §8 las listaba como sub-pantallas. Aquí son secciones: beber un vaso pasa de
   tres toques —entrar, pulsar, volver— a uno, y eso en algo que se hace ocho veces al día
   se nota más que cualquier otra decisión de esta fase. */

import { useCallback, useEffect, useState } from "react";
import { ErrorApi, agua, anadirAgua, type BloqueSistema, type DiaDeAgua } from "../api";
import { BarraBloques, Boton, Comentario, Seccion } from "../componentes";
import { textoAgua } from "../formato";

/** Un vaso. FitLoop ya usaba 250 ml y es lo que cabe en un vaso normal. */
const VASO_ML = 250;
const MEDIO_LITRO_ML = 500;

export function SeccionAgua({
  fecha,
  alGanar,
}: {
  fecha: string;
  /** Sube el bloque `system` a «hoy», que es quien abre la ventana del Sistema. Las
   *  secciones no la abren: si cada una tuviera la suya, podrían salir dos a la vez. */
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [dia, setDia] = useState<DiaDeAgua | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  const cargar = useCallback(() => {
    agua(fecha).then(setDia).catch(() => setFallo("No hemos podido cargar el agua de hoy."));
  }, [fecha]);

  useEffect(() => cargar(), [cargar]);

  async function beber(ml: number) {
    if (!dia) return;
    const antes = dia;
    // Optimista: la barra sube ya y la petición va detrás. Ocho vasos al día por dos o
    // tres décimas de botón muerto cada uno se notan.
    setDia({ ...dia, total_ml: dia.total_ml + ml });
    setFallo(null);

    try {
      const respuesta = await anadirAgua(fecha, ml);
      // El total del servidor gana al optimista: otro dispositivo pudo apuntar entre medias.
      setDia({ ...antes, total_ml: respuesta.total_ml, goal_ml: respuesta.goal_ml, pct: respuesta.pct });
      alGanar(respuesta.system);
    } catch (error) {
      setDia(antes);
      setFallo(
        error instanceof ErrorApi && error.fallo.general?.includes("conexión")
          ? "No se ha podido guardar el vaso. Comprueba la conexión."
          : "No se ha podido guardar el vaso. Inténtalo otra vez.",
      );
    }
  }

  if (!dia) return <Seccion titulo="Agua" resumen="cargando"><Comentario>cargando…</Comentario></Seccion>;

  return (
    <Seccion titulo="Agua" resumen={textoAgua(dia.total_ml, dia.goal_ml)}>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <BarraBloques porcentaje={(dia.total_ml / dia.goal_ml) * 100} />
      <Comentario>{textoAgua(dia.total_ml, dia.goal_ml)}</Comentario>

      <div className="acciones">
        <Boton type="button" compacto onClick={() => void beber(VASO_ML)}>+1 VASO</Boton>
        <Boton type="button" compacto onClick={() => void beber(MEDIO_LITRO_ML)}>+MEDIO LITRO</Boton>
      </div>
    </Seccion>
  );
}
```

⚠️ `BarraBloques` trae hoy `aria-label="Progreso hacia el siguiente nivel"` escrito a
mano. Como ahora la usan dos cosas, **añádele una propiedad `etiqueta` opcional** que caiga
en ese texto por defecto, y pásale `"Agua bebida hoy"` aquí. Ajusta
`componentes.test.tsx` en el mismo commit.

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- habitos` y `cd web && npm test -- componentes`
Esperado: PASAN los dos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/habitos.tsx web/src/pantallas/habitos.test.tsx web/src/componentes.tsx web/src/componentes.test.tsx
git commit -m "$(cat <<'EOF'
feat(hábitos): el agua, con la barra optimista

El spec §8 listaba el agua como sub-pantalla. Aquí es una sección de «hoy»:
beber un vaso pasa de tres toques a uno, y eso en algo que se hace ocho
veces al día pesa más que cualquier otra decisión de esta fase.

La barra sube antes de que conteste el servidor. Lo que hay que demostrar no
es eso, sino lo otro: el test comprueba que cuando la petición falla la barra
vuelve a donde estaba y se dice con palabras, sin ningún número de error.

El total que devuelve el servidor gana al optimista, por si otro dispositivo
apuntó agua entre medias.

El bloque system sube a «hoy» en vez de abrir la ventana aquí: si cada
sección abriera la suya, podrían salir dos a la vez.

BarraBloques gana una etiqueta configurable: ahora la usan dos cosas y
«Progreso hacia el siguiente nivel» era mentira en una de ellas.
EOF
)"
```

---

## Tarea 16 · `habitos.tsx`: los suplementos

**Ficheros:**
- Modificar: `web/src/pantallas/habitos.tsx` y su test

**Interfaces:**
- Consume: `suplementos`, `marcarSuplemento` de `../api`; `Casilla` de `../componentes`.
- Produce: `SeccionSuplementos({ fecha, alGanar })`.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
import { marcarSuplemento, suplementos } from "../api";
import { SeccionSuplementos } from "./habitos";

const CUATRO = [
  { key: "multivitaminas" as const, name: "Multivitaminas", dose: "1 pastilla", taken: false },
  { key: "omega3" as const, name: "Omega 3", dose: "1 capsula", taken: false },
  { key: "vitamina_d" as const, name: "Vitamina D3", dose: "1 pastilla", taken: false },
  { key: "magnesio" as const, name: "Magnesio", dose: "1 pastilla", taken: false },
];

const pintarSuplementos = (alGanar = () => {}) =>
  render(<SeccionSuplementos fecha="2026-08-20" alGanar={alGanar} />);

test("se enseñan los cuatro y el recuento, no si la misión está cumplida", async () => {
  vi.mocked(suplementos).mockResolvedValue({ items: CUATRO, taken_count: 0, total_count: 4 });
  pintarSuplementos();

  expect(await screen.findByRole("button", { name: "Multivitaminas, sin tomar" })).toBeTruthy();
  expect(screen.getByText("0 de 4")).toBeTruthy();
  // La misión la decide el servidor. Adelantarla aquí sería que la app puntuara.
  expect(screen.queryByText(/misión/i)).toBe(null);
});

test("marcar uno manda su clave, la del servidor y no la que se ve", async () => {
  vi.mocked(suplementos).mockResolvedValue({ items: CUATRO, taken_count: 0, total_count: 4 });
  vi.mocked(marcarSuplemento).mockResolvedValue({ message: "ok", system: {} as never });
  pintarSuplementos();

  fireEvent.click(await screen.findByRole("button", { name: "Vitamina D3, sin tomar" }));

  // En pantalla es «Vitamina D3»; la clave del servidor es vitamina_d, sin el 3.
  expect(vi.mocked(marcarSuplemento)).toHaveBeenCalledWith("2026-08-20", "vitamina_d", true);
});

test("la casilla se marca al instante y vuelve si la petición falla", async () => {
  vi.mocked(suplementos).mockResolvedValue({ items: CUATRO, taken_count: 0, total_count: 4 });
  vi.mocked(marcarSuplemento).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  pintarSuplementos();

  fireEvent.click(await screen.findByRole("button", { name: "Magnesio, sin tomar" }));

  expect(await screen.findByRole("button", { name: "Magnesio, sin tomar" })).toBeTruthy();
  expect(screen.getByText("No se ha podido guardar. Comprueba la conexión.")).toBeTruthy();
});

test("el recuento se mueve con la casilla", async () => {
  vi.mocked(suplementos).mockResolvedValue({
    items: [{ ...CUATRO[0], taken: true }, ...CUATRO.slice(1)], taken_count: 1, total_count: 4,
  });
  vi.mocked(marcarSuplemento).mockResolvedValue({ message: "ok", system: {} as never });
  pintarSuplementos();

  fireEvent.click(await screen.findByRole("button", { name: "Omega 3, sin tomar" }));

  expect(await screen.findByText("2 de 4")).toBeTruthy();
});
```

Añade `suplementos` y `marcarSuplemento` a la factoría de `vi.mock("../api", …)` que ya
tiene el fichero.

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- habitos`
Esperado: FALLAN los cuatro — `SeccionSuplementos` no existe.

- [ ] **Paso 3 · Escribir la implementación mínima**

En `habitos.tsx`:

```tsx
import { marcarSuplemento, suplementos, type ClaveSuplemento, type Suplemento } from "../api";
import { Casilla } from "../componentes";

export function SeccionSuplementos({
  fecha,
  alGanar,
}: {
  fecha: string;
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [items, setItems] = useState<Suplemento[] | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    suplementos(fecha)
      .then((r) => setItems(r.items))
      .catch(() => setFallo("No hemos podido cargar los suplementos de hoy."));
  }, [fecha]);

  async function marcar(clave: ClaveSuplemento, tomado: boolean) {
    if (!items) return;
    const antes = items;
    setItems(items.map((s) => (s.key === clave ? { ...s, taken: tomado } : s)));
    setFallo(null);

    try {
      // ⚠️ El servidor NO devuelve el estado actualizado, solo el bloque `system`. Lo que
      // se ve es lo que pintamos aquí.
      const respuesta = await marcarSuplemento(fecha, clave, tomado);
      alGanar(respuesta.system);
    } catch (error) {
      setItems(antes);
      setFallo(
        error instanceof ErrorApi && error.fallo.general?.includes("conexión")
          ? "No se ha podido guardar. Comprueba la conexión."
          : "No se ha podido guardar. Inténtalo otra vez.",
      );
    }
  }

  if (!items) {
    return <Seccion titulo="Suplementos" resumen="cargando"><Comentario>cargando…</Comentario></Seccion>;
  }

  const tomados = items.filter((s) => s.taken).length;

  return (
    // El recuento y nada más. Si la misión está cumplida lo decide el servidor: la misión
    // solo se completa con los cuatro, y adelantarlo aquí sería que la app puntuara.
    <Seccion titulo="Suplementos" resumen={`${tomados} de ${items.length}`}>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <ul className="rejilla-casillas">
        {items.map((s) => (
          <li key={s.key}>
            <Casilla
              etiqueta={s.name}
              marcada={s.taken}
              onCambiar={(marcada) => void marcar(s.key, marcada)}
            />
          </li>
        ))}
      </ul>
    </Seccion>
  );
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- habitos`
Esperado: PASAN todos, los del agua incluidos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/habitos.tsx web/src/pantallas/habitos.test.tsx
git commit -m "$(cat <<'EOF'
feat(hábitos): las cuatro casillas de suplementos

La misión solo se cumple con los cuatro marcados, pero eso lo decide el
servidor: la sección enseña «2 de 4» y no adelanta nada. Hay un test que lo
vigila, porque adelantarlo sería que la aplicación puntuara.

En pantalla pone «Vitamina D3» y la clave del servidor es vitamina_d, sin el
3. El test lo fija: es el error de dedo más fácil de cometer aquí.

El servidor no devuelve el estado actualizado, solo el bloque system, así
que lo que se ve es lo que pinta la sección. Por eso el camino de vuelta
cuando la petición falla tiene su propio test.
EOF
)"
```

---

## Tarea 17 · `habitos.tsx`: actividad y peso

**Ficheros:**
- Modificar: `web/src/pantallas/habitos.tsx` y su test

**Interfaces:**
- Consume: `actividad`, `guardarActividad`, `guardarPeso` de `../api`.
- Produce: `SeccionActividad({ fecha, alGanar })` y
  `SeccionPeso({ pesoActual, alGanar })`.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
import { actividad, guardarActividad, guardarPeso } from "../api";
import { SeccionActividad, SeccionPeso } from "./habitos";

test("los pasos se guardan y las calorías, si no se saben, van a cero", async () => {
  vi.mocked(actividad).mockResolvedValue({ date: "2026-08-20", steps: 0, calories_burned: 0 });
  vi.mocked(guardarActividad).mockResolvedValue({
    date: "2026-08-20", steps: 8200, calories_burned: 0, system: null,
  });
  render(<SeccionActividad fecha="2026-08-20" alGanar={() => {}} />);

  fireEvent.change(await screen.findByLabelText("Pasos"), { target: { value: "8200" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  // El servidor exige las dos cifras. Mucha gente solo conoce sus pasos, y estimar las
  // calorías sería inventarse un dato de salud.
  expect(vi.mocked(guardarActividad)).toHaveBeenCalledWith("2026-08-20", 8200, 0);
});

test("las calorías se dicen opcionales y de dónde salen", async () => {
  vi.mocked(actividad).mockResolvedValue({ date: "2026-08-20", steps: 0, calories_burned: 0 });
  render(<SeccionActividad fecha="2026-08-20" alGanar={() => {}} />);

  expect(await screen.findByLabelText("Calorías quemadas, si tu reloj te las da")).toBeTruthy();
});

test("apuntar el peso lo manda en kilos y sube el bloque system", async () => {
  const ganados: unknown[] = [];
  const bloque = { quests_completed: ["weight"] };
  vi.mocked(guardarPeso).mockResolvedValue({ user: {} as never, system: bloque as never });
  render(<SeccionPeso pesoActual={78} alGanar={(s) => ganados.push(s)} />);

  fireEvent.change(screen.getByLabelText("Peso en kilos"), { target: { value: "77.5" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  expect(vi.mocked(guardarPeso)).toHaveBeenCalledWith(77.5);
  await vi.waitFor(() => expect(ganados).toEqual([bloque]));
});

test("si el peso no cambia el servidor manda system a null y no se sube nada", async () => {
  const ganados: unknown[] = [];
  vi.mocked(guardarPeso).mockResolvedValue({ user: {} as never, system: null });
  render(<SeccionPeso pesoActual={78} alGanar={(s) => ganados.push(s)} />);

  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() => expect(vi.mocked(guardarPeso)).toHaveBeenCalled());
  expect(ganados).toEqual([]);
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- habitos`
Esperado: FALLAN los cuatro.

- [ ] **Paso 3 · Escribir la implementación mínima**

En `habitos.tsx`:

```tsx
import { actividad, guardarActividad, guardarPeso } from "../api";
import { Campo } from "../componentes";

const aNumero = (texto: string, porDefecto = 0) => {
  const n = Number(texto);
  return Number.isFinite(n) && n >= 0 ? n : porDefecto;
};

export function SeccionActividad({
  fecha,
  alGanar,
}: {
  fecha: string;
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [pasos, setPasos] = useState("");
  const [calorias, setCalorias] = useState("");
  const [guardado, setGuardado] = useState<number | null>(null);
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    actividad(fecha)
      .then((a) => {
        setGuardado(a.steps);
        if (a.steps > 0) setPasos(String(a.steps));
        if (a.calories_burned > 0) setCalorias(String(a.calories_burned));
      })
      .catch(() => setFallo("No hemos podido cargar la actividad de hoy."));
  }, [fecha]);

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      // ⚠️ El servidor exige las dos cifras, pero mucha gente solo conoce sus pasos. Las
      // calorías vacías van a 0. NO se estiman a partir de los pasos: sería un número
      // inventado presentado como dato de salud.
      const r = await guardarActividad(fecha, aNumero(pasos), aNumero(calorias));
      setGuardado(r.steps);
      if (r.system) alGanar(r.system);
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No se ha podido guardar. Inténtalo otra vez.",
      );
    } finally {
      setGuardando(false);
    }
  }

  return (
    <Seccion
      titulo="Actividad"
      resumen={guardado && guardado > 0 ? `${guardado} pasos` : "sin apuntar"}
    >
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Pasos" name="pasos" type="number" inputMode="numeric" min="0"
             max="150000" value={pasos} onChange={(e) => setPasos(e.target.value)} />
      <Campo etiqueta="Calorías quemadas, si tu reloj te las da" name="calorias"
             type="number" inputMode="numeric" min="0" max="10000" value={calorias}
             onChange={(e) => setCalorias(e.target.value)} />

      <Boton type="button" compacto disabled={guardando} onClick={() => void guardar()}>
        {guardando ? "GUARDANDO…" : "GUARDAR"}
      </Boton>
    </Seccion>
  );
}

export function SeccionPeso({
  pesoActual,
  alGanar,
}: {
  pesoActual: number | null;
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [kilos, setKilos] = useState(pesoActual == null ? "" : String(pesoActual));
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      const r = await guardarPeso(aNumero(kilos));
      // ⚠️ `system` llega a null cuando el peso no cambió. No hay nada que anunciar.
      if (r.system) alGanar(r.system);
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No se ha podido guardar. Inténtalo otra vez.",
      );
    } finally {
      setGuardando(false);
    }
  }

  return (
    <Seccion titulo="Peso" resumen={pesoActual == null ? "sin apuntar" : `${pesoActual} kg`}>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Peso en kilos" name="peso" type="number" inputMode="decimal"
             step="0.1" min="0" value={kilos} onChange={(e) => setKilos(e.target.value)} />

      <Boton type="button" compacto disabled={guardando} onClick={() => void guardar()}>
        {guardando ? "GUARDANDO…" : "GUARDAR"}
      </Boton>
    </Seccion>
  );
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- habitos`
Esperado: PASAN todos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/habitos.tsx web/src/pantallas/habitos.test.tsx
git commit -m "$(cat <<'EOF'
feat(hábitos): la actividad diaria y el peso

El endpoint de actividad exige pasos y calorías, las dos obligatorias, pero
mucha gente solo conoce sus pasos. En la interfaz las calorías son
opcionales —«si tu reloj te las da»— y se manda 0 cuando están vacías.

No se estiman a partir de los pasos y hay un test que lo fija. Cualquier
fórmula de pasos a calorías da un número que parece un dato y no lo es, y
esto es una aplicación de salud.

El peso devuelve system a null cuando no cambia respecto al guardado. Ese
null se comprueba antes de subir nada: si no, se abriría la ventana del
Sistema por apuntar el mismo peso de ayer.
EOF
)"
```

---

## Tarea 18 · `Hoy.tsx`: montar las secciones y el resumen de nutrición

**Ficheros:**
- Modificar: `web/src/pantallas/Hoy.tsx` y su test

**Interfaces:**
- Consume: `SeccionAgua`, `SeccionSuplementos`, `SeccionActividad`, `SeccionPeso` de
  `./habitos`; `comidasDelDia`, `objetivoNutricional` de `../api`; `VentanaSistema` de
  `../componentes`.
- Produce: nada nuevo hacia fuera.

- [ ] **Paso 1 · Escribir el test que falla**

Al final de `web/src/pantallas/Hoy.test.tsx`:

```tsx
test("«hoy» monta las cuatro secciones de hábitos y el resumen de nutrición", async () => {
  pintar();
  await screen.findByText("Misiones de hoy");

  expect(screen.getByText("Nutrición")).toBeTruthy();
  expect(screen.getByText("Agua")).toBeTruthy();
  expect(screen.getByText("Suplementos")).toBeTruthy();
  expect(screen.getByText("Actividad")).toBeTruthy();
  expect(screen.getByText("Peso")).toBeTruthy();
});

test("sin misión de proteína se invita a configurar el objetivo", async () => {
  // La misión de proteína solo existe si el usuario tiene objetivo nutricional. Que no
  // esté es la señal de que no lo tiene, y el momento de invitarle.
  vi.mocked(diaDeHoy).mockResolvedValue({
    date: "2026-08-20",
    progress: PROGRESO,
    quests: [{ key: "water", label: "Beber 2 litros de agua", target: 2000, progress: 0,
               xp_reward: 20, is_optional: false, completed: false }],
    suggested_workout: { reason: "", weekly_done: 0, weekly_goal: 3, template: null },
  } as never);

  pintar();

  expect(await screen.findByRole("link", { name: /CALCULAR MI OBJETIVO/ })).toBeTruthy();
});

test("con misión de proteína no se invita a nada", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue({
    date: "2026-08-20",
    progress: PROGRESO,
    quests: [{ key: "protein", label: "Llegar a 150 g de proteína", target: 150, progress: 40,
               xp_reward: 30, is_optional: false, completed: false }],
    suggested_workout: { reason: "", weekly_done: 0, weekly_goal: 3, template: null },
  } as never);

  pintar();
  await screen.findByText("Misiones de hoy");

  expect(screen.queryByRole("link", { name: /CALCULAR MI OBJETIVO/ })).toBe(null);
});

test("un logro que llega desde una sección abre la ventana del Sistema", async () => {
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 2000, goal_ml: 2000, pct: 100,
    system: {
      xp_gained: 20, level_up: null, rank_up: null,
      achievements_unlocked: [{ key: "hydrated", name: "Hidratado", rarity: "common" }],
      records: [], quests_completed: ["water"], progress: PROGRESO,
    } as never,
  });

  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "+MEDIO LITRO" }));

  expect(await screen.findByRole("dialog", { name: "El Sistema" })).toBeTruthy();
  expect(screen.getByText("Hidratado")).toBeTruthy();
});
```

Añade a la factoría de `vi.mock("../api", …)` de ese fichero: `comidasDelDia`,
`objetivoNutricional`, `agua`, `anadirAgua`, `suplementos`, `marcarSuplemento`,
`actividad`, `guardarActividad`, `guardarPeso`. Y devuélveles valores por defecto en el
`beforeEach`, o las secciones se quedarán en «cargando…» y los `findByText` fallarán.

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- Hoy`
Esperado: FALLAN los cuatro nuevos.

- [ ] **Paso 3 · Escribir la implementación mínima**

En `Hoy.tsx`, el estado del resumen y de la ventana:

```tsx
  const [resumen, setResumen] = useState<DiaDeComidas | null>(null);
  const [objetivo, setObjetivo] = useState<number | null>(null);
  // Lo que abre la ventana del Sistema. Se guarda aquí y no en cada sección: si cada una
  // tuviera la suya, dos podrían salir a la vez.
  const [premio, setPremio] = useState<BloqueSistema | null>(null);
```

Dentro de `cargar()`, después de `setDatos(...)`:

```tsx
      const [comidas, meta] = await Promise.all([
        comidasDelDia(dia.date),
        objetivoNutricional(),
      ]);
      setResumen(comidas);
      setObjetivo(meta.has_goal ? meta.goal.daily_calories : null);
```

Y una función que recoge lo que suben las secciones:

```tsx
  // Solo abre la ventana si hay algo que anunciar. VentanaSistema ya devuelve null cuando
  // no hay motivos, pero dejarlo montado con un bloque vacío pone un diálogo invisible en
  // el árbol y el foco se va a él.
  function recogerPremio(sistema: BloqueSistema) {
    const hayAlgo =
      sistema.level_up || sistema.rank_up ||
      sistema.achievements_unlocked.length > 0 || sistema.records.length > 0;
    if (hayAlgo) setPremio(sistema);
    // Cambió el progreso: las misiones y la barra de XP de arriba ya no son las de antes.
    void cargar();
  }
```

Y en el árbol, después de la sección de entreno:

```tsx
      {premio && <VentanaSistema sistema={premio} alCerrar={() => setPremio(null)} />}

      <Seccion
        titulo="Nutrición"
        resumen={resumen ? `${Math.round(resumen.totals.calories)} kcal` : "cargando"}
      >
        {resumen && (
          <>
            <Comentario>{textoRestante(resumen.totals.calories, objetivo)}</Comentario>
            <FilaMacros macros={resumen.totals} />
          </>
        )}

        <div className="acciones">
          <Link className="boton compacto" to="/nutricion">
            <span aria-hidden="true">[ </span>VER EL DÍA<span aria-hidden="true"> ]</span>
          </Link>
          {/* La misión de proteína solo existe si hay objetivo nutricional. Que no esté
              es la señal de que falta, y este es el momento de invitar. */}
          {!misiones.some((m) => m.key === "protein") && (
            <Link className="boton compacto" to="/nutricion/objetivo">
              <span aria-hidden="true">[ </span>CALCULAR MI OBJETIVO<span aria-hidden="true"> ]</span>
            </Link>
          )}
        </div>
      </Seccion>

      <SeccionAgua fecha={datos.date} alGanar={recogerPremio} />
      <SeccionSuplementos fecha={datos.date} alGanar={recogerPremio} />
      <SeccionActividad fecha={datos.date} alGanar={recogerPremio} />
      <SeccionPeso pesoActual={usuario.weight} alGanar={recogerPremio} />
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- Hoy` y luego `cd web && npm test`
Esperado: PASA la suite entera.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/Hoy.tsx web/src/pantallas/Hoy.test.tsx
git commit -m "$(cat <<'EOF'
feat(hoy): el resumen de nutrición y las cuatro secciones de hábitos

«Hoy» gana una sección por hábito, no una pestaña, que es lo que dice el
spec §8 que tiene que pasar cuando llega algo nuevo.

La ventana del Sistema se abre aquí y no en cada sección: si cada una
abriera la suya, beber el vaso que completa la misión mientras se marca un
suplemento sacaría dos a la vez. Y solo se abre si hay algo que anunciar —
subir de nivel, subir de rango, un logro o un récord—, nunca por ganar XP a
secas, que pasa en todo.

La invitación al asistente aparece cuando NO hay misión de proteína. Esa
misión solo existe si el usuario tiene objetivo nutricional, así que su
ausencia es la señal exacta de que falta configurarlo.
EOF
)"
```

---

## Tarea 19 · `Objetivo.tsx`: el asistente de tres pasos

**Ficheros:**
- Crear: `web/src/pantallas/Objetivo.tsx`
- Crear: `web/src/pantallas/Objetivo.test.tsx`
- Modificar: `web/src/App.tsx`

**Interfaces:**
- Consume: `ACTIVIDADES`, `OBJETIVOS`, `calcularObjetivo` de `../formato`;
  `guardarObjetivoNutricional`, `guardarDatosCuerpo`, `usuarioActual` de `../api`.
- Produce: la ruta `/nutricion/objetivo` y el componente por defecto `Objetivo`.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { guardarDatosCuerpo, guardarObjetivoNutricional, usuarioActual } from "../api";
import Objetivo from "./Objetivo";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  usuarioActual: vi.fn(),
  guardarObjetivoNutricional: vi.fn(),
  guardarDatosCuerpo: vi.fn(),
}));

const COMPLETO = {
  name: "Isra", email: "a@b.c", is_admin: false,
  weight: 80, height: 180, age: 30, gender: "male" as const,
  weekly_goal: 3, water_goal_ml: 2000,
};

const pintar = () => render(<MemoryRouter><Objetivo /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(usuarioActual).mockResolvedValue(COMPLETO);
  vi.mocked(guardarObjetivoNutricional).mockResolvedValue({ message: "ok", goal: {} as never });
});

test("el número se recalcula al tocar, sin pedir nada al servidor", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Mantener el peso" }));
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));
  fireEvent.click(screen.getByRole("button", { name: "Poco o nada" }));

  // 1780 × 1,2 = 2136.
  expect(screen.getByText("2.136 kcal")).toBeTruthy();

  fireEvent.click(screen.getByRole("button", { name: "Muy intenso, o deporte más trabajo físico" }));
  expect(screen.getByText("3.382 kcal")).toBeTruthy();

  // Nada de esto ha salido a la red.
  expect(vi.mocked(guardarObjetivoNutricional)).not.toHaveBeenCalled();
});

test("el tercer paso deja ajustar las cifras antes de guardar", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Mantener el peso" }));
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));
  fireEvent.click(screen.getByRole("button", { name: "Ejercicio moderado, de 3 a 5 días" }));
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));

  fireEvent.change(screen.getByLabelText("Calorías al día"), { target: { value: "2600" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR MI OBJETIVO" }));

  expect(vi.mocked(guardarObjetivoNutricional)).toHaveBeenCalledWith(
    expect.objectContaining({ daily_calories: 2600, goal_type: "maintain" }),
  );
});

test("si faltan datos del cuerpo se piden antes, en vez de calcular con inventos", async () => {
  vi.mocked(usuarioActual).mockResolvedValue({ ...COMPLETO, height: null, age: null });
  pintar();

  expect(await screen.findByLabelText("Altura en centímetros")).toBeTruthy();
  expect(screen.getByLabelText("Edad")).toBeTruthy();
  // Y no se enseña ninguna cifra todavía.
  expect(screen.queryByText(/kcal/)).toBe(null);
});

test("los datos que faltaban se guardan en el perfil antes de seguir", async () => {
  vi.mocked(usuarioActual).mockResolvedValue({ ...COMPLETO, height: null, age: null });
  vi.mocked(guardarDatosCuerpo).mockResolvedValue({ user: COMPLETO, system: null });
  pintar();

  fireEvent.change(await screen.findByLabelText("Altura en centímetros"), { target: { value: "180" } });
  fireEvent.change(screen.getByLabelText("Edad"), { target: { value: "30" } });
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));

  expect(vi.mocked(guardarDatosCuerpo)).toHaveBeenCalledWith(
    expect.objectContaining({ height: 180, age: 30 }),
  );
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- Objetivo`
Esperado: FALLA — no existe `./Objetivo`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/pantallas/Objetivo.tsx`:

```tsx
import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { ErrorApi, guardarDatosCuerpo, guardarObjetivoNutricional, usuarioActual } from "../api";
import { Boton, Campo, Comentario, FilaMacros, TituloPantalla } from "../componentes";
import {
  ACTIVIDADES, OBJETIVOS, calcularObjetivo,
  type ClaveActividad, type ClaveObjetivo, type DatosCuerpo,
} from "../formato";

const NUMEROS = new Intl.NumberFormat("es-ES", { useGrouping: "always" });

/** Una lista de opciones excluyentes. Botones con `aria-pressed` y no radios: cada toque
 *  cambia el número de arriba, no rellena un formulario que se envía luego. */
function Opciones<T extends string>({
  opciones,
  elegida,
  alElegir,
}: {
  opciones: readonly { clave: T; etiqueta: string }[];
  elegida: T;
  alElegir: (clave: T) => void;
}) {
  return (
    <ul className="lista-opciones">
      {opciones.map((o) => (
        <li key={o.clave}>
          <button
            type="button"
            className={o.clave === elegida ? "casilla marcada" : "casilla"}
            aria-pressed={o.clave === elegida}
            onClick={() => alElegir(o.clave)}
          >
            <span className="marca" aria-hidden="true">[{o.clave === elegida ? "✓" : " "}]</span>
            <span>{o.etiqueta}</span>
          </button>
        </li>
      ))}
    </ul>
  );
}

export default function Objetivo() {
  const navegar = useNavigate();
  const [cuerpo, setCuerpo] = useState<Partial<DatosCuerpo> | null>(null);
  const [paso, setPaso] = useState(1);
  const [objetivo, setObjetivo] = useState<ClaveObjetivo>("maintain");
  const [actividad, setActividad] = useState<ClaveActividad>("moderate");
  const [ajustes, setAjustes] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    usuarioActual().then((u) =>
      setCuerpo(
        u ? { weight: u.weight ?? undefined, height: u.height ?? undefined,
              age: u.age ?? undefined, gender: u.gender ?? undefined } : {},
      ),
    );
  }, []);

  if (!cuerpo) return <><TituloPantalla pantalla="mi objetivo" /><Comentario>cargando…</Comentario></>;

  const completo = (c: Partial<DatosCuerpo>): c is DatosCuerpo =>
    c.weight != null && c.height != null && c.age != null && c.gender != null;

  // Paso 0: sin peso, altura, edad y sexo no hay fórmula que valga. Se piden antes de
  // enseñar ninguna cifra, en vez de calcular con valores por defecto que serían mentira.
  if (!completo(cuerpo)) {
    return (
      <>
        <TituloPantalla pantalla="mi objetivo" />
        <Comentario>necesitamos cuatro datos para calcularlo</Comentario>
        {fallo && <p className="aviso" role="alert">{fallo}</p>}

        {cuerpo.weight == null && (
          <Campo etiqueta="Peso en kilos" name="peso" type="number" inputMode="decimal" step="0.1"
                 value={ajustes.weight ?? ""} onChange={(e) => setAjustes({ ...ajustes, weight: e.target.value })} />
        )}
        {cuerpo.height == null && (
          <Campo etiqueta="Altura en centímetros" name="altura" type="number" inputMode="numeric"
                 value={ajustes.height ?? ""} onChange={(e) => setAjustes({ ...ajustes, height: e.target.value })} />
        )}
        {cuerpo.age == null && (
          <Campo etiqueta="Edad" name="edad" type="number" inputMode="numeric"
                 value={ajustes.age ?? ""} onChange={(e) => setAjustes({ ...ajustes, age: e.target.value })} />
        )}
        {cuerpo.gender == null && (
          <Opciones
            opciones={[{ clave: "male", etiqueta: "Hombre" }, { clave: "female", etiqueta: "Mujer" }] as const}
            elegida={(ajustes.gender as "male" | "female") ?? "male"}
            alElegir={(g) => setAjustes({ ...ajustes, gender: g })}
          />
        )}

        <Boton
          type="button"
          disabled={guardando}
          onClick={() => {
            const nuevos: Partial<DatosCuerpo> = {};
            if (ajustes.weight) nuevos.weight = Number(ajustes.weight);
            if (ajustes.height) nuevos.height = Number(ajustes.height);
            if (ajustes.age) nuevos.age = Number(ajustes.age);
            nuevos.gender = (ajustes.gender as "male" | "female") ?? cuerpo.gender ?? "male";
            setGuardando(true);
            guardarDatosCuerpo(nuevos)
              .then(() => { setCuerpo({ ...cuerpo, ...nuevos }); setAjustes({}); })
              .catch(() => setFallo("No hemos podido guardar tus datos. Inténtalo otra vez."))
              .finally(() => setGuardando(false));
          }}
        >
          SIGUIENTE
        </Boton>
      </>
    );
  }

  const calculado = calcularObjetivo(cuerpo, actividad, objetivo);
  const cifra = (campo: keyof typeof calculado) =>
    ajustes[campo] !== undefined ? Number(ajustes[campo]) : (calculado[campo] as number);

  return (
    <>
      <TituloPantalla pantalla="mi objetivo" />
      <Comentario>paso {paso} de 3</Comentario>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      {paso === 1 && (
        <>
          <Comentario decorativo>qué quieres conseguir</Comentario>
          <Opciones opciones={OBJETIVOS} elegida={objetivo} alElegir={setObjetivo} />
        </>
      )}

      {paso === 2 && (
        <>
          <Comentario decorativo>cuánto te mueves</Comentario>
          <Opciones opciones={ACTIVIDADES} elegida={actividad} alElegir={setActividad} />
          <p className="xp">{NUMEROS.format(calculado.daily_calories)} kcal</p>
          <FilaMacros macros={{
            calories: calculado.daily_calories, protein: calculado.target_protein,
            carbs: calculado.target_carbs, fat: calculado.target_fat, fiber: 0, sugar: 0,
          }} />
        </>
      )}

      {paso === 3 && (
        <>
          <Comentario decorativo>puedes ajustarlo antes de guardar</Comentario>
          <Campo etiqueta="Calorías al día" name="daily_calories" type="number" inputMode="numeric"
                 value={cifra("daily_calories")}
                 onChange={(e) => setAjustes({ ...ajustes, daily_calories: e.target.value })} />
          <Campo etiqueta="Proteínas al día en gramos" name="target_protein" type="number"
                 inputMode="numeric" value={cifra("target_protein")}
                 onChange={(e) => setAjustes({ ...ajustes, target_protein: e.target.value })} />
          <Campo etiqueta="Hidratos al día en gramos" name="target_carbs" type="number"
                 inputMode="numeric" value={cifra("target_carbs")}
                 onChange={(e) => setAjustes({ ...ajustes, target_carbs: e.target.value })} />
          <Campo etiqueta="Grasas al día en gramos" name="target_fat" type="number"
                 inputMode="numeric" value={cifra("target_fat")}
                 onChange={(e) => setAjustes({ ...ajustes, target_fat: e.target.value })} />
        </>
      )}

      <div className="acciones">
        {paso > 1 && (
          <Boton type="button" compacto onClick={() => setPaso(paso - 1)}>ATRÁS</Boton>
        )}
        {paso < 3 ? (
          <Boton type="button" compacto onClick={() => setPaso(paso + 1)}>SIGUIENTE</Boton>
        ) : (
          <Boton
            type="button"
            disabled={guardando}
            onClick={() => {
              setGuardando(true);
              setFallo(null);
              guardarObjetivoNutricional({
                daily_calories: cifra("daily_calories"),
                target_protein: cifra("target_protein"),
                target_carbs: cifra("target_carbs"),
                target_fat: cifra("target_fat"),
                target_fiber: calculado.target_fiber,
                goal_type: objetivo,
              })
                .then(() => navegar("/nutricion"))
                .catch((error: unknown) => {
                  setFallo(
                    error instanceof ErrorApi && error.fallo.general
                      ? error.fallo.general
                      : "No hemos podido guardarlo. Inténtalo otra vez.",
                  );
                  setGuardando(false);
                });
            }}
          >
            {guardando ? "GUARDANDO…" : "GUARDAR MI OBJETIVO"}
          </Boton>
        )}
      </div>
    </>
  );
}
```

En `estilos.css`:

```css
.lista-opciones { padding: 0; margin: 0.5rem 0 0; list-style: none; }
.lista-opciones li { margin-bottom: 0.5rem; }
```

En `App.tsx`:

```tsx
        <Route path="/nutricion/objetivo" element={<Objetivo />} />
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- Objetivo`
Esperado: PASAN todos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/Objetivo.tsx web/src/pantallas/Objetivo.test.tsx web/src/App.tsx web/src/estilos.css
git commit -m "$(cat <<'EOF'
feat(nutrición): el asistente del objetivo, en tres pasos

El número se recalcula al tocar cada opción, sin salir a la red. Ese es el
motivo de que Mifflin-St Jeor esté en el cliente: con una petición por toque,
la cifra parpadearía en cada cambio.

Si faltan peso, altura, edad o sexo se piden antes y no se enseña ninguna
cifra. Calcular con valores por defecto daría un objetivo de salud inventado
con pinta de estar calculado.

El tercer paso deja ajustar las cuatro cifras a mano: la fórmula es una
recomendación, no una orden, y quien sepa lo que hace tiene que poder
cambiarla.
EOF
)"
```

---

## Tarea 20 · `Recetas.tsx`: lista, detalle y registrar como comida

**Ficheros:**
- Modificar: `web/src/api.ts`
- Crear: `web/src/pantallas/Recetas.tsx`
- Crear: `web/src/pantallas/Recetas.test.tsx`
- Modificar: `web/src/App.tsx`

**Interfaces:**
- Consume: `pedir`, `registrarComida`, `diaDeHoy`.
- Produce: los tipos `Receta`, `RecetaNueva`; `recetas(filtros)`, `receta(id)`,
  `crearReceta(datos)`, `borrarReceta(id)`; las rutas `/nutricion/recetas` y
  `/nutricion/recetas/:id`; los componentes `Recetas` (por defecto) y `Receta`.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { diaDeHoy, receta, recetas, registrarComida } from "../api";
import Recetas, { Receta } from "./Recetas";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  recetas: vi.fn(),
  receta: vi.fn(),
  registrarComida: vi.fn(),
  diaDeHoy: vi.fn(),
}));

const POLLO = {
  id: 3, name: "Pollo al horno con verduras", description: "Sencillo",
  category: "cena", image_path: null, image_url: null,
  calories_per_serving: 420, protein_per_serving: 38, carbs_per_serving: 22,
  fat_per_serving: 18, fiber_per_serving: 5, servings: 2,
  prep_time_min: 10, cook_time_min: 40, difficulty: "fácil",
  is_system: true, user_id: null,
  ingredients: [{ name: "Pechuga de pollo", quantity: "400 g" }],
  instructions: "Al horno 40 minutos.",
};

beforeEach(() => {
  vi.mocked(diaDeHoy).mockResolvedValue({ date: "2026-08-20" } as never);
  vi.mocked(recetas).mockResolvedValue([POLLO] as never);
  vi.mocked(receta).mockResolvedValue(POLLO as never);
  vi.mocked(registrarComida).mockResolvedValue({ message: "ok", meal_log: {}, system: {} } as never);
});

test("la lista enseña las recetas con sus calorías por ración", async () => {
  render(<MemoryRouter><Recetas /></MemoryRouter>);

  expect(await screen.findByText("Pollo al horno con verduras")).toBeTruthy();
  expect(screen.getByText(/420 kcal/)).toBeTruthy();
});

test("filtrar por categoría se lo pide al servidor, no se filtra aquí", async () => {
  render(<MemoryRouter><Recetas /></MemoryRouter>);
  await screen.findByText("Pollo al horno con verduras");

  fireEvent.click(screen.getByRole("button", { name: "Cena" }));

  await vi.waitFor(() =>
    expect(vi.mocked(recetas)).toHaveBeenLastCalledWith({ category: "cena" }),
  );
});

test("usar una receta la registra como entrada manual con sus macros por ración", async () => {
  render(
    <MemoryRouter initialEntries={["/nutricion/recetas/3"]}>
      <Routes><Route path="/nutricion/recetas/:id" element={<Receta />} /></Routes>
    </MemoryRouter>,
  );

  fireEvent.click(await screen.findByRole("button", { name: "REGISTRAR COMO CENA" }));

  // No hay endpoint que registre una receta: es una entrada manual con su nombre.
  expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
    date: "2026-08-20",
    meal_type: "dinner",
    custom_food_name: "Pollo al horno con verduras",
    calories: 420, protein: 38, carbs: 22, fat: 18,
  });
});

test("el detalle enseña los ingredientes y los pasos", async () => {
  render(
    <MemoryRouter initialEntries={["/nutricion/recetas/3"]}>
      <Routes><Route path="/nutricion/recetas/:id" element={<Receta />} /></Routes>
    </MemoryRouter>,
  );

  expect(await screen.findByText("Pechuga de pollo")).toBeTruthy();
  expect(screen.getByText("400 g")).toBeTruthy();
  expect(screen.getByText("Al horno 40 minutos.")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- Recetas`
Esperado: FALLA — no existe `./Recetas`.

- [ ] **Paso 3 · Escribir la implementación mínima**

En `api.ts`, al final:

```ts
// ── Recetas ─────────────────────────────────────────────────────────────────

/** Las categorías del servidor. Ojo: **`almuerzo`, no `lunch`** — las recetas usan
 *  castellano y las comidas inglés. No se pueden mezclar. */
export type CategoriaReceta = "desayuno" | "almuerzo" | "cena" | "snack";

/** El `meal_type` que le toca a cada categoría cuando se registra como comida. */
export const COMIDA_DE_CATEGORIA: Record<CategoriaReceta, TipoComida> = {
  desayuno: "breakfast",
  almuerzo: "lunch",
  cena: "dinner",
  snack: "snack",
};

export type Receta = {
  id: number;
  name: string;
  description: string | null;
  category: CategoriaReceta;
  image_path: string | null;
  /** Los macros van **por ración**, no por 100 g. */
  calories_per_serving: number;
  protein_per_serving: number;
  carbs_per_serving: number;
  fat_per_serving: number;
  fiber_per_serving: number;
  servings: number;
  prep_time_min: number;
  cook_time_min: number;
  difficulty: string;
  is_system: boolean;
  user_id: string | null;
  ingredients?: { name: string; quantity: string }[];
  instructions?: string;
};

export async function recetas(filtros: { category?: CategoriaReceta; search?: string } = {}) {
  const parametros = new URLSearchParams();
  if (filtros.category) parametros.set("category", filtros.category);
  if (filtros.search) parametros.set("search", filtros.search);
  const respuesta = await pedir<{ recipes: Receta[] }>(`/recipes?${parametros}`);
  return respuesta.recipes;
}

export async function receta(id: number) {
  const respuesta = await pedir<{ recipe: Receta }>(`/recipes/${id}`);
  return respuesta.recipe;
}

export type RecetaNueva = {
  name: string;
  description?: string | null;
  category: CategoriaReceta;
  calories_per_serving: number;
  protein_per_serving?: number;
  carbs_per_serving?: number;
  fat_per_serving?: number;
  servings?: number;
  prep_time_min?: number;
  cook_time_min?: number;
  ingredients?: { name: string; quantity: string }[];
  instructions?: string;
  difficulty?: "fácil" | "media" | "difícil";
};

/** ⚠️ El servidor guarda toda receta de usuario con `is_system = true`, o sea **visible
 *  para el resto de personas de la instancia**. No se arregla en esta fase porque cambia
 *  la visibilidad de filas que ya están en producción, pero la pantalla que llama a esto
 *  **tiene que avisar antes de guardar**. */
export async function crearReceta(datos: RecetaNueva) {
  const respuesta = await pedir<{ recipe: Receta }>("/recipes", { metodo: "POST", cuerpo: datos });
  return respuesta.recipe;
}

export function borrarReceta(id: number) {
  return pedir<{ message: string }>(`/recipes/${id}`, { metodo: "DELETE" });
}
```

Crea `web/src/pantallas/Recetas.tsx` con los dos componentes:

```tsx
import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router";
import {
  COMIDA_DE_CATEGORIA, ErrorApi, diaDeHoy, receta as pedirReceta, recetas,
  registrarComida, type CategoriaReceta, type Receta as TipoReceta,
} from "../api";
import { Boton, Comentario, FilaMacros, Seccion, TituloPantalla } from "../componentes";

const CATEGORIAS: { clave: CategoriaReceta; etiqueta: string }[] = [
  { clave: "desayuno", etiqueta: "Desayuno" },
  { clave: "almuerzo", etiqueta: "Comida" },
  { clave: "cena", etiqueta: "Cena" },
  { clave: "snack", etiqueta: "Tentempié" },
];

export default function Recetas() {
  const [lista, setLista] = useState<TipoReceta[] | null>(null);
  const [categoria, setCategoria] = useState<CategoriaReceta | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    // El filtro va al servidor: filtrar aquí obligaría a bajarse el catálogo entero.
    recetas(categoria ? { category: categoria } : {})
      .then(setLista)
      .catch(() => setFallo("No hemos podido cargar las recetas. Comprueba la conexión."));
  }, [categoria]);

  return (
    <>
      <TituloPantalla pantalla="recetas" />
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <div className="acciones">
        {CATEGORIAS.map((c) => (
          <Boton
            key={c.clave}
            type="button"
            compacto
            aria-pressed={categoria === c.clave}
            aria-label={c.etiqueta}
            onClick={() => setCategoria(categoria === c.clave ? null : c.clave)}
          >
            {c.etiqueta.toUpperCase()}
          </Boton>
        ))}
      </div>

      {lista === null ? (
        <Comentario>cargando…</Comentario>
      ) : lista.length === 0 ? (
        <Comentario>no hay recetas de esa clase todavía</Comentario>
      ) : (
        <ul className="lista-entradas">
          {lista.map((r) => (
            <li key={r.id}>
              <Link className="nombre" to={`/nutricion/recetas/${r.id}`}>{r.name}</Link>
              <span className="kcal">{Math.round(r.calories_per_serving)} kcal</span>
            </li>
          ))}
        </ul>
      )}

      <div className="acciones">
        <Link className="boton compacto" to="/nutricion/recetas/nueva">
          <span aria-hidden="true">[ </span>NUEVA RECETA<span aria-hidden="true"> ]</span>
        </Link>
      </div>
    </>
  );
}

export function Receta() {
  const { id } = useParams();
  const navegar = useNavigate();
  const [datos, setDatos] = useState<TipoReceta | null>(null);
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    pedirReceta(Number(id))
      .then(setDatos)
      .catch(() => setFallo("No hemos podido cargar la receta."));
  }, [id]);

  async function registrar() {
    if (!datos) return;
    setGuardando(true);
    setFallo(null);
    try {
      const hoy = await diaDeHoy();
      // No hay endpoint que registre una receta como comida: es una entrada manual con el
      // nombre de la receta y sus macros por ración. Funciona con la API tal y como está.
      await registrarComida({
        date: hoy.date,
        meal_type: COMIDA_DE_CATEGORIA[datos.category],
        custom_food_name: datos.name,
        calories: datos.calories_per_serving,
        protein: datos.protein_per_serving,
        carbs: datos.carbs_per_serving,
        fat: datos.fat_per_serving,
      });
      navegar("/nutricion");
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido registrarla. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }

  if (!datos) {
    return (
      <>
        <TituloPantalla pantalla="receta" />
        {fallo ? <p className="aviso" role="alert">{fallo}</p> : <Comentario>cargando…</Comentario>}
      </>
    );
  }

  const etiqueta = CATEGORIAS.find((c) => c.clave === datos.category)!.etiqueta;

  return (
    <>
      <TituloPantalla pantalla="receta" />
      <Comentario>{datos.name}</Comentario>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      {datos.image_path && (
        <img className="vista-previa" src={`/uploads/${datos.image_path}`} alt={datos.name} />
      )}

      <p className="xp">{Math.round(datos.calories_per_serving)} kcal por ración</p>
      <FilaMacros macros={{
        calories: datos.calories_per_serving, protein: datos.protein_per_serving,
        carbs: datos.carbs_per_serving, fat: datos.fat_per_serving,
        fiber: datos.fiber_per_serving, sugar: 0,
      }} />
      <Comentario>
        {datos.servings} raciones · {datos.prep_time_min + datos.cook_time_min} minutos · {datos.difficulty}
      </Comentario>

      <Seccion titulo="Ingredientes" resumen={`${datos.ingredients?.length ?? 0}`}>
        <ul className="lista-entradas">
          {(datos.ingredients ?? []).map((i) => (
            <li key={`${i.name}-${i.quantity}`}>
              <span className="nombre">{i.name}</span>
              <span className="cantidad">{i.quantity}</span>
            </li>
          ))}
        </ul>
      </Seccion>

      <Seccion titulo="Cómo se hace" resumen="pasos">
        <p className="instrucciones">{datos.instructions}</p>
      </Seccion>

      <Boton type="button" disabled={guardando} onClick={() => void registrar()}>
        {guardando ? "REGISTRANDO…" : `REGISTRAR COMO ${etiqueta.toUpperCase()}`}
      </Boton>
    </>
  );
}
```

En `estilos.css`:

```css
.instrucciones { margin: 0.5rem 0 0; font-size: var(--cuerpo); white-space: pre-wrap; }
```

En `App.tsx`, **la ruta estática antes de la dinámica** para que `nueva` no la capture la
de `:id`:

```tsx
        <Route path="/nutricion/recetas" element={<Recetas />} />
        <Route path="/nutricion/recetas/nueva" element={<CrearReceta />} />
        <Route path="/nutricion/recetas/:id" element={<Receta />} />
```

`CrearReceta` llega en la tarea 21. Hasta entonces, deja esa línea fuera.

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- Recetas`
Esperado: PASAN todos.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/api.ts web/src/pantallas/Recetas.tsx web/src/pantallas/Recetas.test.tsx web/src/App.tsx web/src/estilos.css
git commit -m "$(cat <<'EOF'
feat(nutrición): ver y usar recetas

Hay 26 recetas del sistema sembradas, así que la pantalla tiene contenido
desde el primer día sin que nadie cree ninguna.

Registrar una receta es una entrada manual disfrazada: no existe ningún
endpoint que registre una receta como comida, así que se manda su nombre en
custom_food_name con los macros por ración. Funciona con la API tal como
está y no hace falta tocar el backend.

Las categorías de receta van en castellano (desayuno, almuerzo, cena, snack)
y los tipos de comida en inglés (breakfast, lunch, dinner, snack). Son dos
vocabularios distintos del mismo backend y mezclarlos da un 422, así que la
traducción vive en una tabla con nombre, COMIDA_DE_CATEGORIA.

El filtro va al servidor: filtrarlo aquí obligaría a bajarse el catálogo.
EOF
)"
```

---

## Tarea 21 · `Recetas.tsx`: crear una receta, con foto y con aviso

**Ficheros:**
- Crear: `web/src/pantallas/CrearReceta.tsx`
- Crear: `web/src/pantallas/CrearReceta.test.tsx`
- Modificar: `web/src/App.tsx`

**Interfaces:**
- Consume: `crearReceta`, `subir` de `../api`; `encoger` de `../foto`; `FotoElegible` de
  `../componentes`.
- Produce: la ruta `/nutricion/recetas/nueva` y el componente por defecto `CrearReceta`.

- [ ] **Paso 1 · Escribir el test que falla**

```tsx
import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { crearReceta, subir } from "../api";
import CrearReceta from "./CrearReceta";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  crearReceta: vi.fn(),
  subir: vi.fn(),
}));
vi.mock("../foto", () => ({ encoger: vi.fn(async (f: File) => f), LADO_MAXIMO: 1280 }));

const pintar = () => render(<MemoryRouter><CrearReceta /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(crearReceta).mockResolvedValue({ id: 77, name: "La mía" } as never);
  vi.mocked(subir).mockResolvedValue({ image_path: "nutrition/recipes/x.jpg" } as never);
});

test("se avisa de que la receta la verá el resto de gente, antes de guardar", () => {
  pintar();

  // El servidor guarda toda receta de usuario con is_system = true. El arreglo queda
  // fuera de esta fase, pero callarlo sería peor que el propio fallo.
  expect(
    screen.getByText("Tus recetas las verá el resto de personas que usan S-RANK."),
  ).toBeTruthy();
});

test("los ingredientes se añaden y se quitan de uno en uno", () => {
  pintar();

  fireEvent.click(screen.getByRole("button", { name: "AÑADIR INGREDIENTE" }));
  fireEvent.change(screen.getByLabelText("Ingrediente 1"), { target: { value: "Arroz" } });
  fireEvent.change(screen.getByLabelText("Cantidad del ingrediente 1"), { target: { value: "80 g" } });

  fireEvent.click(screen.getByRole("button", { name: "AÑADIR INGREDIENTE" }));
  expect(screen.getByLabelText("Ingrediente 2")).toBeTruthy();

  fireEvent.click(screen.getByRole("button", { name: "Quitar el ingrediente 2" }));
  expect(screen.queryByLabelText("Ingrediente 2")).toBe(null);
});

test("guardar manda la receta y después la foto contra el id devuelto", async () => {
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "La mía" } });
  fireEvent.change(screen.getByLabelText("Calorías por ración"), { target: { value: "520" } });
  fireEvent.change(screen.getByLabelText("Foto, si quieres"), {
    target: { files: [new File(["x"], "r.jpg", { type: "image/jpeg" })] },
  });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() =>
    expect(vi.mocked(crearReceta)).toHaveBeenCalledWith(
      expect.objectContaining({ name: "La mía", calories_per_serving: 520, category: "almuerzo" }),
    ),
  );
  await vi.waitFor(() =>
    expect(vi.mocked(subir)).toHaveBeenCalledWith("/recipes/77/image", "image", expect.any(File)),
  );
});

test("si la foto falla, la receta ya está guardada y se dice tal cual", async () => {
  const { ErrorApi } = await import("../api");
  vi.mocked(subir).mockRejectedValue(
    new ErrorApi({ general: "No hemos podido subir la foto. Inténtalo otra vez.", campos: {} }),
  );
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "La mía" } });
  fireEvent.change(screen.getByLabelText("Calorías por ración"), { target: { value: "520" } });
  fireEvent.change(screen.getByLabelText("Foto, si quieres"), {
    target: { files: [new File(["x"], "r.jpg", { type: "image/jpeg" })] },
  });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  expect(
    await screen.findByText("La receta se ha guardado, pero la foto no se ha podido subir."),
  ).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecuta: `cd web && npm test -- CrearReceta`
Esperado: FALLA — no existe `./CrearReceta`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crea `web/src/pantallas/CrearReceta.tsx`:

```tsx
import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { ErrorApi, crearReceta, subir, type CategoriaReceta } from "../api";
import { Aviso, Boton, Campo, Comentario, FotoElegible, TituloPantalla } from "../componentes";
import { encoger } from "../foto";

const CATEGORIAS: { clave: CategoriaReceta; etiqueta: string }[] = [
  { clave: "desayuno", etiqueta: "Desayuno" },
  { clave: "almuerzo", etiqueta: "Comida" },
  { clave: "cena", etiqueta: "Cena" },
  { clave: "snack", etiqueta: "Tentempié" },
];

type Ingrediente = { name: string; quantity: string };

export default function CrearReceta() {
  const navegar = useNavigate();
  const [nombre, setNombre] = useState("");
  const [categoria, setCategoria] = useState<CategoriaReceta>("almuerzo");
  const [kcal, setKcal] = useState("");
  const [raciones, setRaciones] = useState("1");
  const [instrucciones, setInstrucciones] = useState("");
  const [ingredientes, setIngredientes] = useState<Ingrediente[]>([]);
  const [foto, setFoto] = useState<File | null>(null);
  const [vistaPrevia, setVistaPrevia] = useState<string | null>(null);
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    if (!foto) return setVistaPrevia(null);
    const url = URL.createObjectURL(foto);
    setVistaPrevia(url);
    return () => URL.revokeObjectURL(url);
  }, [foto]);

  const numero = (texto: string, porDefecto = 0) => {
    const n = Number(texto);
    return Number.isFinite(n) && n >= 0 ? n : porDefecto;
  };

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      const creada = await crearReceta({
        name: nombre.trim(),
        category: categoria,
        calories_per_serving: numero(kcal),
        servings: numero(raciones, 1),
        ingredients: ingredientes.filter((i) => i.name.trim() !== ""),
        instructions: instrucciones.trim(),
      });

      if (foto) {
        try {
          await subir(`/recipes/${creada.id}/image`, "image", await encoger(foto));
        } catch {
          // La receta ya está guardada. Se dice tal cual y no se deshace nada.
          setFallo("La receta se ha guardado, pero la foto no se ha podido subir.");
          setGuardando(false);
          return;
        }
      }

      navegar("/nutricion/recetas");
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido guardarla. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }

  return (
    <>
      <TituloPantalla pantalla="nueva receta" />

      {/* El servidor guarda toda receta de usuario con is_system = true, o sea visible
          para el resto de personas de la instancia. El arreglo queda fuera de esta fase,
          pero callarlo sería peor que el propio fallo. */}
      <Aviso tono="ambar">Tus recetas las verá el resto de personas que usan S-RANK.</Aviso>

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Nombre" name="nombre" value={nombre}
             onChange={(e) => setNombre(e.target.value)} />

      <div className="acciones">
        {CATEGORIAS.map((c) => (
          <Boton key={c.clave} type="button" compacto aria-pressed={categoria === c.clave}
                 aria-label={c.etiqueta} onClick={() => setCategoria(c.clave)}>
            {c.etiqueta.toUpperCase()}
          </Boton>
        ))}
      </div>

      <Campo etiqueta="Calorías por ración" name="kcal" type="number" inputMode="numeric"
             min="0" value={kcal} onChange={(e) => setKcal(e.target.value)} />
      <Campo etiqueta="Raciones" name="raciones" type="number" inputMode="numeric" min="1"
             value={raciones} onChange={(e) => setRaciones(e.target.value)} />

      <Comentario decorativo>ingredientes</Comentario>
      {ingredientes.map((ing, i) => (
        <div key={i} className="acciones">
          <Campo etiqueta={`Ingrediente ${i + 1}`} name={`ing-${i}`} value={ing.name}
                 onChange={(e) => setIngredientes(
                   ingredientes.map((v, j) => (j === i ? { ...v, name: e.target.value } : v)),
                 )} />
          <Campo etiqueta={`Cantidad del ingrediente ${i + 1}`} name={`cant-${i}`} value={ing.quantity}
                 onChange={(e) => setIngredientes(
                   ingredientes.map((v, j) => (j === i ? { ...v, quantity: e.target.value } : v)),
                 )} />
          <Boton type="button" compacto aria-label={`Quitar el ingrediente ${i + 1}`}
                 onClick={() => setIngredientes(ingredientes.filter((_, j) => j !== i))}>
            QUITAR
          </Boton>
        </div>
      ))}
      <Boton type="button" compacto
             onClick={() => setIngredientes([...ingredientes, { name: "", quantity: "" }])}>
        AÑADIR INGREDIENTE
      </Boton>

      <Campo etiqueta="Cómo se hace" name="instrucciones" value={instrucciones}
             onChange={(e) => setInstrucciones(e.target.value)} />

      <FotoElegible vistaPrevia={vistaPrevia} disabled={guardando}
                    onElegir={setFoto} onQuitar={() => setFoto(null)} />

      <Boton type="button"
             disabled={guardando || nombre.trim() === "" || kcal.trim() === ""}
             onClick={() => void guardar()}>
        {guardando ? "GUARDANDO…" : "GUARDAR"}
      </Boton>
    </>
  );
}
```

Y en `App.tsx`, descomenta la ruta `/nutricion/recetas/nueva` **antes** de la de `:id`.

- [ ] **Paso 4 · Comprobar que pasa**

Ejecuta: `cd web && npm test -- CrearReceta` y después `cd web && npm test`
Esperado: PASA la suite entera.

- [ ] **Paso 5 · Commit**

```bash
git add web/src/pantallas/CrearReceta.tsx web/src/pantallas/CrearReceta.test.tsx web/src/App.tsx
git commit -m "$(cat <<'EOF'
feat(nutrición): crear recetas propias, con foto

La pantalla avisa de que la receta la verá el resto de gente, y hay un test
que lo vigila. RecipeController guarda toda receta de usuario con
is_system = true, o sea visible para toda la instancia. El arreglo queda
fuera de esta fase porque cambia la visibilidad de filas que ya están en
producción, pero callarlo sería peor que el propio fallo.

La foto va en su propia petición después de crear la receta, igual que en
los alimentos. Si falla, la receta ya está guardada: se dice tal cual y no se
deshace nada.

La ruta /nutricion/recetas/nueva va declarada antes que la de :id.
EOF
)"
```

---

## Tarea 22 · Comprobación en el móvil y cierre de la fase

Lo que la suite **no puede** demostrar. Los cuatro fallos de la fase 1.0 pasaron 254 tests
y solo aparecieron al tocar el servidor; el de la CSP de esta fase es de la misma familia.

**Ficheros:**
- Modificar: `CLAUDE.md`
- Modificar: `docs/superpowers/fases/README.md`
- Modificar: `docs/superpowers/fases/fase-1.3-nutricion.md`

**Interfaces:**
- Consume: todo lo anterior.
- Produce: la fase cerrada.

- [ ] **Paso 1 · Comprobar que la suite entera está verde**

```bash
cd web && npm test && npm run build
cd ../backend && php artisan test
```

Esperado: verde en los tres. `npm run build` es el que comprueba los tipos.

- [ ] **Paso 2 · Desplegar**

```bash
cd backend && bash build-deploy.sh
```

Sube `deploy/` por FTP. **No hay migraciones nuevas en esta fase**, así que no hay nada que
ejecutar a mano en phpMyAdmin.

⚠️ `backend/public/.htaccess` **tiene que subir**. Es el fichero de la tarea 1 y sin él la
vista previa de las fotos sale en blanco. Comprueba que está en el paquete antes de subirlo.

- [ ] **Paso 3 · Comprobar en el móvil, con una cuenta de verdad**

Una lista, y cada punto es uno de los criterios de «Terminado cuando» del documento de la
fase. **Con la aplicación instalada como PWA, no en el navegador de escritorio.**

- [ ] Registrar una comida del catálogo cuesta menos de cinco toques.
- [ ] Repetir un alimento reciente son dos toques.
- [ ] Crear un alimento propio con foto, y **la foto se ve después de guardarla**. Este es
      el que demuestra las dos mitades de la tarea 1: la URL y la CSP.
- [ ] La vista previa de la foto **se ve antes de subirla**. Si sale un hueco en blanco, el
      `.htaccess` no llegó al servidor o `img-src` no lleva `blob:`.
- [ ] El agua sube con `+1 vaso` y la barra se mueve al instante.
- [ ] Llegar al objetivo de agua abre la ventana del Sistema con «Hidratado».
- [ ] Los cuatro suplementos marcados completan su misión, y con tres no.
- [ ] El asistente calcula el objetivo y las cifras cambian al tocar cada opción.
- [ ] Después de guardar el objetivo, **aparece la misión de proteína** en «hoy».
- [ ] Una receta se abre, se registra como comida y sale en el día.
- [ ] Crear una receta con foto funciona y **el aviso de que se comparte se lee**.
- [ ] Apuntar los pasos **mueve la racha** y no da XP de entreno.
- [ ] Apuntar el peso completa su misión el día que toca la rotativa.
- [ ] Poner el móvil en modo avión y tocar un vaso: la barra vuelve atrás y sale «No se ha
      podido guardar el vaso. Comprueba la conexión.», sin ningún número.

- [ ] **Paso 4 · Actualizar la documentación**

En `docs/superpowers/fases/README.md`, la tabla de estado:

```
| [1.3](fase-1.3-nutricion.md) | Nutrición, agua, suplementos, actividad, recetas | **hecha** — comprobada en el móvil |
| [1.4](fase-1.4-progreso.md) | Historial, calendario, gráficas, récords | **la siguiente** |
```

En `CLAUDE.md`, la tabla de fases y el árbol de `web/src/`, que gana `recientes.ts`,
`foto.ts` y las seis pantallas nuevas.

Y en `CLAUDE.md`, dos trampas nuevas para la sección que ya existe:

```markdown
**La CSP también bloquea `blob:` y `data:`.** `img-src 'self'` no deja pintar una vista
previa hecha con `URL.createObjectURL` ni un `canvas` volcado a `data:`. En local no se ve
—no hay Apache—, y en el servidor sale un hueco en blanco sin ningún error en la interfaz.
Por eso lleva `img-src 'self' blob:`.

**Las recetas de usuario nacen visibles para todos.** `RecipeController::store` las guarda
con `is_system = true`. La pantalla de crear lo avisa; el arreglo está pendiente porque
cambia la visibilidad de filas que ya están en producción.
```

- [ ] **Paso 5 · Commit y etiqueta**

```bash
git add CLAUDE.md docs/superpowers/fases/README.md docs/superpowers/fases/fase-1.3-nutricion.md
git commit -m "$(cat <<'EOF'
docs: la fase 1.3 queda cerrada

Nutrición, agua, suplementos, actividad, peso y recetas, comprobados en el
móvil con una cuenta de verdad y con la CSP del servidor de por medio.

Dos trampas nuevas para CLAUDE.md, las dos de la familia que la suite no
puede ver: que img-src 'self' bloquea también blob: y data:, y que las
recetas de usuario nacen visibles para toda la instancia.
EOF
)"

git tag fase-1.3
```

---

## Lo que esta fase deja anotado y sin arreglar

Está en el §11 del diseño y se repite aquí para que no se pierda entre las tareas:

1. **`RecipeController::store` marca las recetas de usuario con `is_system = true`.** La
   pantalla lo avisa; el arreglo cambia la visibilidad de filas que ya están en producción.
2. **`POST /api/foods` sigue aceptando `from_ingredients`.** La aplicación no lo manda
   nunca, pero el endpoint lo acepta de quien lo llame a mano.
3. **`FoodController::update` deja editar los alimentos del sistema a cualquier usuario.**
4. **`RecipeController::show` tiene una guarda muerta:** `if (!$recipe->is_system && …)`
   nunca se cumple para una receta de usuario, porque todas nacen con `is_system = true`.

Los cuatro son la misma decisión aplazada: quién ve qué. Merecen una tarea propia con su
migración de datos, no colarse en una fase de frontend.
