# Fase 1.3 · Nutrición, agua, suplementos y actividad — diseño

**Fecha:** 20 de agosto de 2026
**Estado:** aprobado, pendiente de plan de implementación
**Sustituye a:** nada. Desarrolla `docs/superpowers/fases/fase-1.3-nutricion.md`.

---

## 1. Qué se construye

El bloque de hábitos diarios: nutrición, agua, suplementos, actividad y peso. Es lo que
más se usa de la aplicación —182 comidas y 148 registros de agua frente a 3 entrenos en
los datos reales— y por eso el criterio que manda es el número de toques, no el número de
funciones.

**El backend ya está entero.** Las 25 rutas que consume esta fase existen y tienen tests.
La fase es frontend, salvo tres arreglos concretos que se detallan en el §8.

---

## 2. Decisiones tomadas

| Decisión | Qué se eligió | Por qué |
|---|---|---|
| Cómo se abre «añadir comida» | Recientes primero, guardados en `localStorage` | Repetir el desayuno son 2 toques. Cero cambios en el backend |
| Dónde viven agua, suplementos, actividad y peso | Secciones dentro de «hoy», no sub-pantallas | Beber un vaso pasa de 3 toques a 1. Ahorra 4 pantallas y 4 tests |
| Alcance de recetas | Completo: ver, usar, crear y foto | Decisión del usuario. Es la mitad cara de la fase |
| Arreglos de backend | Los tres bloqueantes del §8 | Impiden cumplir criterios que la fase da por terminados |
| Dónde se calcula Mifflin-St Jeor | En el cliente, portando el de `old/` | El asistente recalcula al tocar, sin ida y vuelta |
| Qué se ve al tocar | Optimista en agua y suplementos, esperar en el resto | La rama de deshacer se escribe dos veces, no siete |

**Se aparta de la letra del spec §8** en un punto: ese documento lista agua, suplementos,
actividad y peso como sub-pantallas. Aquí van dentro de «hoy». No se aparta de su
espíritu, que es «hoy gana una sección, no una pestaña».

---

## 3. Arquitectura y ficheros

Se sigue la convención que ya tiene el proyecto. No se añade ninguna capa nueva.

```
web/src/
  api.ts              crece: ~20 funciones bajo // ── Nutrición ── y // ── Hábitos ──
  formato.ts          crece: los cálculos con ramas
  recientes.ts        NUEVO: «lo de siempre», en localStorage
  componentes.tsx     crece: Casilla, Contador, ChipComida, FotoElegible
  estilos.css         crece: rejilla de macros, casillas, chips
  pantallas/
    habitos.tsx       NUEVO: agua · suplementos · actividad · peso
    Nutricion.tsx     NUEVO: el día, las cuatro comidas y los totales
    AnadirComida.tsx  NUEVO: recientes + buscador + cantidad
    CrearAlimento.tsx NUEVO: alimento propio con foto
    Recetas.tsx       NUEVO: lista, detalle y crear
    Objetivo.tsx      NUEVO: el asistente de tres pasos
    Hoy.tsx           crece: monta las secciones de habitos.tsx
  App.tsx             crece: siete rutas nuevas
```

**`api.ts` sigue siendo un solo fichero** aunque llegue a las 700 líneas. Es la única
puerta a la API y la que traduce los errores una vez; partirla obligaría a decidir dónde
vive `pedir()` y a que cada trozo la importe. Es una lista plana de funciones tipadas con
cabeceras de sección.

**Las cuatro secciones de «hoy» van en un fichero, no en cuatro.** Cada una son 40-60
líneas y todas hacen lo mismo. Cuatro ficheros de 50 líneas con cuatro ficheros de test al
lado es más papeleo que código. Si alguna crece, se saca entonces.

**`recientes.ts` sí es fichero propio** porque es persistencia, igual que `borrador.ts`, y
tiene su propio test.

### Rutas nuevas

```
/nutricion                  el día
/nutricion/anadir           ?tipo=breakfast|lunch|dinner|snack
/nutricion/alimento/nuevo   crear alimento propio
/nutricion/objetivo         el asistente
/nutricion/recetas          lista
/nutricion/recetas/nueva    crear
/nutricion/recetas/:id      detalle
```

Van **dentro de `ConPestanas`**, como las de entreno: son sub-pantallas de «hoy» (spec §8)
y la navegación no desaparece por estar apuntando la cena.

---

## 4. Reglas que valen para todas las pantallas

**La fecha se manda siempre explícita,** tomada del `date` de `/api/system/today`. Sin ella
el servidor cae en su `Carbon::today()`, que a las 00:30 de Madrid no es el mismo día que
el de las misiones. Se lee y se escribe en UTC, sin convertir a la zona del navegador.

**`GET /api/meals` agrupa por tipo de comida en un objeto,** y cuando el día está vacío
Laravel lo serializa como `[]` y no como `{}`. `api.ts` lo normaliza a las cuatro claves
fijas para que ninguna pantalla tenga que saberlo.

**Los números que vienen de columnas decimales se normalizan en `api.ts`.** Los modelos
`FoodItem` y `MealLog` ya castean sus macros a `float`, pero `users.weight` no está
casteado. Se aplica el mismo tratamiento que ya recibe `max_weight`: `Number()` en la
puerta, no en cada pantalla.

**El bloque `system` se trata igual que en la 1.2.** Toda escritura que puede completar una
misión lo devuelve, y la `VentanaSistema` que ya existe se abre en los cuatro momentos del
spec §6.7 y en ninguno más. `PUT /api/user/profile` devuelve `system: null` cuando el peso
no cambia: hay que contar con ese `null`.

**Nada de jerga y ningún código de error.** «Proteínas», «grasas», «hidratos». Los fallos
se cuentan con palabras, como ya hace `api.ts`.

**La decoración va `aria-hidden` y el estado se dice con palabras.** Un suplemento tomado
se oye «Multivitaminas, tomado», nunca «corchete, marca de verificación, corchete».

---

## 5. Nutrición

### 5.1 El día — `/nutricion`

Las cuatro comidas —Desayuno, Comida, Cena, Tentempié— con sus entradas, el total de
macros del día y cuánto queda para el objetivo. Cada comida tiene su botón de añadir, y
cada entrada su botón `[ QUITAR ]`, que llama a `DELETE /api/meals/{uuid}`.

**Un botón y no un gesto de deslizar.** Deslizar no se ve, no se puede teclear y no lo
anuncia ningún lector de pantalla. El endpoint es idempotente —repetir la petición responde
200, no un error—, así que un doble toque no rompe nada.

Si no hay objetivo nutricional configurado, en vez de «quedan N kcal» va la invitación al
asistente.

### 5.2 Añadir comida — `/nutricion/anadir?tipo=`

El criterio duro de la fase es «menos de cinco toques».

**Repetir algo de siempre — 2 toques**

```
hoy → ▸ Nutrición → [ + DESAYUNO ]                      (1)
      añadir comida
      [ Café con leche  200 g  88 kcal ] [ CAMBIAR ]    (2) → guardado, y vuelve
```

El chip ya lleva la cantidad de la última vez. **Al lado va un segundo botón, `[ CAMBIAR ]`,
para ajustarla**, y no un mantener pulsado: un gesto largo no se ve, no tiene equivalente
de teclado y en el móvil pelea con el menú contextual del navegador. Su `aria-label` va
completo —«Cambiar la cantidad de Café con leche»— porque el texto suelto no dice de qué
alimento habla.

Sin iconos, aquí tampoco: el proyecto no tiene ninguno y no los va a estrenar en un chip.

**Algo nuevo del catálogo — 4 toques y una escritura**

```
[ escribe un alimento… ] → «poll»            (1 + teclado)
[ Pechuga de pollo   165 kcal/100 g ]        (2)
[ 150 ] g   ← precargado a 100                (3)
[ AÑADIR ]                                    (4)
```

La búsqueda va contra `GET /api/foods?search=&limit=20` con **300 ms de espera entre
teclas**. No se descarga el catálogo de 1.506 alimentos al móvil.

**Entrada manual** — el mismo formulario con una pestaña «a mano»: nombre y calorías
obligatorios, los tres macros si se saben. Va con `custom_food_name`.

### 5.3 Los recientes — `recientes.ts`

Un array en `localStorage` de `{ id, nombre, gramos, tipo }`, reordenado al usarse y
cortado por diez. Se escribe después de cada `POST /api/meals` que responde 201.

```
ponytail: la lista vive solo en este móvil. El techo es que en un dispositivo nuevo
sale vacía y se rellena sola en unos días. Si algún día hace falta que sincronice,
el sitio es GET /api/foods/recent en el backend.
```

Tiene que aguantar tres cosas sin romperse: que no haya nada guardado, que lo guardado sea
basura de una versión anterior, y que `localStorage` esté lleno.

### 5.4 Crear alimento — `/nutricion/alimento/nuevo`

Nombre, marca, categoría, unidad y los macros por 100 g. Admite foto (§7).

**No se manda nunca `from_ingredients`.** Ese campo hace que el alimento nazca en el
catálogo global, con `user_id = null` y visible para todos. La pantalla crea siempre
alimentos personales.

### 5.5 El objetivo nutricional — `/nutricion/objetivo`

Tres pasos, con el número recalculándose al tocar cada opción:

```
paso 1   tu objetivo        perder peso · mantener · ganar músculo
paso 2   cuánto te mueves   las cinco opciones de FitLoop
paso 3   revisar            las cuatro cifras, editables antes de guardar
```

La fórmula se porta de `old/resources/views/nutrition/dashboard.blade.php:1222-1247`, con
su tabla de factores de actividad y sus ratios de macros por objetivo, y vive en
`formato.ts` con su test.

```
ponytail: Mifflin-St Jeor queda escrita en dos sitios, aquí y en
NutritionGoal::calculateRecommended. El techo es que se separen. Si pasa, el sitio
donde unificarlas es GET /api/nutrition/goal aceptando activity y goal_type.
```

**Los datos que necesita —peso, altura, edad y sexo— ya están en memoria:** `GET /api/user`
devuelve el modelo entero desde que se abre la aplicación. Solo hay que ensanchar el tipo
`Usuario`, que hoy declara tres campos de los once que llegan. Si falta alguno, el
asistente abre un paso 0 que los pide y los guarda con `PUT /api/user/profile`.

Se guarda con `PUT /api/nutrition/goal`, que exige `daily_calories`, `target_protein`,
`target_carbs`, `target_fat` y `goal_type`.

---

## 6. Las cuatro secciones de «hoy»

Todas comparten forma: leen su estado con la fecha de `/api/system/today` y se pliegan
solas cuando ya están hechas.

### Agua

`POST /api/water` acepta de 1 a 2000 ml; el objetivo va de 500 a 6000 y por defecto son
2000. Dos botones: **+1 vaso** (250 ml) y **+medio litro**.

**Optimista.** La `BarraBloques` que ya existe sube al pulsar y la petición va detrás. Si
falla, vuelve atrás y sale el aviso con palabras. Al cruzar el objetivo la respuesta trae
el bloque `system` con el logro «Hidratado».

### Suplementos

Cuatro casillas contra `PUT /api/supplements`, una petición por casilla, optimista igual.
Las claves son `multivitaminas`, `omega3`, `vitamina_d` y `magnesio`, y el endpoint exige
`date`.

La misión solo se completa con las cuatro, **y eso lo decide el servidor**: la sección
enseña «2 de 4» y no adelanta nada.

### Actividad

Dos campos, pasos y calorías, contra `PUT /api/activity`.

El endpoint marca `calories_burned` como obligatorio, pero mucha gente solo conoce sus
pasos. En la interfaz las calorías son opcionales —«si tu reloj te las da»— y se manda `0`
cuando está vacío. **No se inventa una fórmula de pasos a calorías:** sería un número falso
presentado como dato de salud.

Con el arreglo 3 del §8, esta sección enseña la racha sin una segunda petición.

### Peso

Un campo y un botón contra `PUT /api/user/profile`. La respuesta trae `system` **solo si el
peso cambió**; si se manda el mismo de ayer llega `null` y no se abre nada.

### Y encima de las cuatro: el resumen de nutrición

Calorías del día contra el objetivo, los tres macros y un enlace a `/nutricion`. **Si
`/api/system/today` no trae la misión `protein`** es que no hay objetivo configurado, y ahí
va la invitación al asistente.

---

## 7. Recetas

### Ver y usar

`/nutricion/recetas` lista con filtro por categoría y búsqueda contra `GET /api/recipes`.
Hay **26 recetas del sistema** ya sembradas, así que la pantalla tiene contenido desde el
primer día.

**Usar una receta es una entrada manual disfrazada.** No hay endpoint que registre una
receta como comida, así que `[ REGISTRAR COMO CENA ]` hace un `POST /api/meals` con
`custom_food_name` = el nombre de la receta y los macros por ración. Funciona con la API
tal y como está.

### Crear

Formulario con nombre, categoría, raciones, tiempos, dificultad, ingredientes
(`{name, quantity}`), instrucciones y macros por ración.

**Avisa de que la receta se comparte.** `RecipeController::store` guarda toda receta de
usuario con `is_system = true`, o sea visible para el resto de personas de la instancia.
Como ese arreglo queda fuera de esta fase, la pantalla lo dice antes de guardar:

> Tus recetas las verá el resto de personas que usan S-RANK.

Callarlo sería peor que el propio fallo.

### Las fotos

`<input type="file" accept="image/*">`, y antes de subir se redimensiona a **1280 px de
lado mayor** en un `<canvas>` y se comprime a **JPEG 0.8**. Una foto de móvil pasa de unos
4 MB a unos 200 KB, dentro del límite de 2 MB del servidor y de lo que pide la restricción
de datos móviles.

Obliga a **una función nueva en `api.ts`**: `pedir()` manda siempre JSON, y una subida
necesita `FormData` **sin `Content-Type`** —lo pone el navegador con su `boundary`— pero
**con el `X-XSRF-TOKEN`**. Se llama `subir()` y comparte la traducción de errores.

La imagen guardada se lee de `image_path` y se pinta desde `/uploads/{image_path}`.

---

## 8. Los tres arreglos de backend

Cualquiera de ellos obliga a regenerar el paquete y subirlo por FTP.

### 1 · La URL de la imagen del alimento

`backend/app/Http/Controllers/Api/FoodController.php:274` devuelve
`asset('storage/'.$path)`, pero el disco `uploads` apunta a `public_path('uploads')` y en
Ginernet no hay symlink. Esa URL da 404. `RecipeController.php:220` sí lo hace bien.

```php
'image_url' => Storage::disk('uploads')->url($path),
```

**Test:** que la URL devuelta empiece por `/uploads` y no por `/storage`. `UploadDiskTest`
hoy solo comprueba que el fichero aterriza en disco.

### 2 · La CSP bloquea la vista previa de la foto

`backend/public/.htaccess:13` lleva `img-src 'self'`, que **también bloquea `blob:` y
`data:`**. La vista previa antes de subir usa exactamente eso.

```
img-src 'self' blob:
```

**Sin test posible:** la suite corre sin Apache. Se comprueba en el servidor con una foto
de verdad. Es el mismo patrón que el `Referrer-Policy` del 18 de agosto: que la cabecera
salga en el `curl` demuestra que está puesta, no que la aplicación funcione.

### 3 · Los pasos no mueven la racha

`ActivityController::upsert()` crea el `Workout` de modo `pasos` pero **no publica ningún
evento**, así que `SystemService::afterWorkout()` no llega a correr y `current_streak` no
se mueve. `AchievementService` sí cuenta los pasos como día activo, pero eso solo se aplica
al pasar `srank:recalculate`.

`afterWorkout()` ya se salta el XP cuando el modo es `pasos` y llama a `touchStreak()`
igual, así que basta con publicar el evento y devolver las recompensas:

```php
$event = new WorkoutStored($log);
event($event);
// … 'system' => $event->rewards
```

Respeta la regla 2: el módulo publica el evento y no menciona al Sistema.

**Test:** guardar pasos mueve `current_streak` y **no** da XP de entrenamiento.

---

## 9. Qué se prueba

Con Vitest en el frontend y PHPUnit en el backend, siguiendo lo de la 1.2. **Un test que no
falla sin el arreglo no vale:** se quita el arreglo, se comprueba que el test falla, se
restaura.

**Funciones puras — `formato.ts`**
- macros para una cantidad en gramos
- Mifflin-St Jeor con las dos constantes de sexo y los tres tipos de objetivo
- textos de agua, de macros y de «cuánto queda»

**Persistencia — `recientes.ts`**
- reordena al usar y corta por diez
- aguanta que no haya nada, que haya basura y que `localStorage` esté lleno

**Pantallas**
- añadir comida desde un reciente son dos toques
- el buscador espera antes de pedir
- el agua optimista se deshace cuando la petición falla
- los cuatro suplementos completan la misión
- la decoración va `aria-hidden` y el estado se dice con palabras

**La puerta — `api.ts`**
- `meals` vacío se normaliza a las cuatro claves
- `subir()` no pone `Content-Type` a mano

**Backend**
- la URL de la imagen empieza por `/uploads`
- guardar pasos mueve la racha y no da XP de entreno

---

## 10. Terminado cuando

- [ ] Se registra una comida del catálogo en menos de cinco toques.
- [ ] Repetir un alimento reciente son dos toques.
- [ ] Se puede crear un alimento propio con foto, y la foto se ve en el servidor.
- [ ] El agua se suma con incrementos rápidos y la barra se mueve al instante.
- [ ] Llegar al objetivo de agua completa la misión y lo anuncia.
- [ ] Los cuatro suplementos marcados completan su misión.
- [ ] El asistente nutricional calcula el objetivo con Mifflin-St Jeor.
- [ ] Las recetas se listan, se abren, se usan y se crean con foto.
- [ ] Los pasos se guardan, mueven la racha y no dan XP de entreno.
- [ ] Apuntar el peso completa su misión cuando toca.
- [ ] Hay tests de las pantallas y de las funciones con ramas.
- [ ] Comprobado en el móvil, con la CSP del servidor de por medio.

---

## 11. Lo que queda anotado y fuera

**`RecipeController::store` marca las recetas de usuario con `is_system = true`,** o sea
visibles para toda la instancia. El `show()` guarda contra eso con
`if (!$recipe->is_system && …)`, condición que para una receta de usuario nunca se cumple:
es código muerto. Esta fase lo avisa en la interfaz pero no lo arregla, porque cambia la
visibilidad de filas que ya están en producción y hay que decidir qué pasa con las
existentes.

**`POST /api/foods` acepta `from_ingredients`,** que escribe en el catálogo global. La
aplicación no lo manda nunca, pero el endpoint lo sigue aceptando de quien lo llame a mano.

**`FoodController::update` deja editar los alimentos del sistema a cualquier usuario.** Está
puesto a propósito —«que la comunidad complete los macros»— y con dos usuarios es más
riesgo de integridad que ventaja.

**El historial nutricional y las gráficas** son de la 1.4. `GET /api/meals/history` existe y
no se toca aquí.

**El logro «Chef · 10 recetas propias»** se vuelve alcanzable con esta fase, pero la
pantalla de logros es de la 1.5.
