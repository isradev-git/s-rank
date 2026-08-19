# Fase 1.2 — Diseño del módulo de entrenamiento

**Fecha:** 2026-08-18
**Estado:** aprobado, pendiente de plan de implementación
**Depende de:** [fase-1.2-entrenamiento.md](../fases/fase-1.2-entrenamiento.md) ·
[s-rank-design](2026-08-10-s-rank-design.md) §4 §6 §8 §9 ·
[core-ui-design](2026-08-11-core-ui-design.md)

---

## 1. Qué se construye

Poder hacer un entreno entero desde el móvil, en un sótano sin cobertura, y que al
terminar el Sistema diga qué se ha ganado.

Todo el cálculo —XP, récords, estadísticas, logros— ya está hecho y probado en el
servidor desde la fase 1.0. **Esta fase no reimplementa ni una fórmula.** Manda un
`POST /api/workouts` y pinta lo que vuelve.

Lo único que se diseña de verdad aquí es **que no se pierda un entreno a medias**. Es lo
único irrecuperable de toda la aplicación: si se pierde, el usuario ya no se acuerda de lo
que levantó. El §2 es el núcleo de este documento; el resto es pantalla.

---

## 2. Que no se pierda un entreno

### 2.1 Por qué `localStorage` y no IndexedDB

El spec §3 y §9 dicen IndexedDB. **Se cambia a `localStorage`**, y el motivo es
exactamente el fallo que hay que blindar.

`localStorage.setItem` es **síncrono**: cuando la llamada vuelve, el dato está escrito.
Si el sistema operativo mata la pestaña en ese instante, no hay nada en vuelo que perder.
IndexedDB es asíncrono: una escritura en curso cuando el navegador mata el proceso se
pierde, y ese es justo el escenario —matar la app a mitad de serie— que la fase pone como
criterio de terminación.

Lo que IndexedDB da de más no hace falta aquí:

| Ventaja de IndexedDB | Por qué no importa |
|---|---|
| Cuota grande | La sesión son ~2 KB. El peor caso admitido por la API (50 ejercicios × 50 series) son ~150 KB, sobre un techo de 5 MB |
| Clonado estructurado | El estado es JSON plano: números, cadenas y booleanos |
| Transacciones | Hay un solo registro. No hay nada que coordinar |
| Índices y consultas | No se consulta nada: se lee entero o no se lee |

Coste: ocho líneas contra unas setenta de envoltorio a mano sobre eventos, o una
dependencia nueva.

**El techo, y cuándo se sube:** 5 MB por origen, y la escritura bloquea el hilo principal.
Con ~2 KB por sesión ninguna de las dos cosas se nota. Si algún día se guardara algo
grande —fotos de la sesión, historial local completo— entonces sí toca IndexedDB, y la
frontera está aislada en `borrador.ts`: cambia el fichero, no las pantallas.

### 2.2 Los ocho fallos y su respuesta

| Fallo | Respuesta |
|---|---|
| Matar la app a mitad de serie | `setItem` síncrono en **cada** cambio, sin rebote |
| Cerrar la pestaña, navegar fuera, recargar | lo mismo |
| Terminar sin cobertura | la sesión pasa a una cola en `localStorage` y un aviso permanente lo dice |
| El `POST` se cometió pero la respuesta se perdió | antes de reintentar se comprueba con `GET /api/workouts?per_page=5` si ya está subido |
| `localStorage` lleno, o navegador en modo privado | aviso rojo permanente: no se puede guardar, no cierres la app |
| Borrador ilegible tras un despliegue | campo `v`; si no se puede leer **no se borra**, se avisa |
| Borrador de hace dos días | se ofrece con su antigüedad escrita. Nunca se descarta solo |
| Dos pestañas con sesiones distintas | gana la última que escriba. `ponytail:` con su techo |

**La regla que lo sostiene:** `borrar()` solo se llama después de subir con éxito, o
cuando el usuario descarta el borrador a mano. Ninguna otra rama del código lo llama.
Un fallo al leer, un JSON corrupto o una versión desconocida **no borran nada**: dejan el
dato en disco y avisan.

### 2.3 Las dos claves

```
srank.entreno-activo        la sesión en curso, un solo objeto
srank.entrenos-pendientes   los terminados que aún no se han subido, un array
srank.descanso-defecto      un número de segundos, la preferencia del usuario
```

Las dos primeras son dos porque son dos estados distintos: el activo se edita y la cola se
sube. Mezclarlos
obligaría a distinguirlos con una bandera dentro del mismo registro, que es la forma
habitual de subir un entreno a medias por error.

La cola es un array y no un solo registro porque se puede terminar un entreno sin
cobertura y empezar otro antes de recuperarla.

### 2.4 El estado de la sesión

```ts
type Serie = {
  // fuerza: gym · home · calisthenics
  weight_kg: number | null;      // en calistenia es el lastre
  reps: number | null;
  rpe: number | null;
  // natación
  distance_m: number | null;
  time_seconds: number | null;
  style: string | null;
  // comunes
  rest_seconds: number | null;
  hecha: boolean;                // solo del cliente: no se manda
};

type Ejercicio = {
  name: string;
  objetivo: { sets: number | null; reps: number | null } | null;  // de la plantilla
  sets: Serie[];
};

type Sesion = {
  v: 1;                          // si no cuadra, no se lee y no se borra
  mode: "gym" | "home" | "calisthenics" | "swimming";
  nombre: string;                // de la plantilla, o «Entreno libre»
  inicio: string;                // ISO 8601 UTC sin milisegundos
  exercises: Ejercicio[];
  actual: number;                // qué ejercicio se está viendo
};
```

**`inicio` hace tres trabajos:** es la fecha que se manda al servidor, es de donde sale el
cronómetro de tiempo transcurrido (`ahora − inicio`, así que no hay contador que persistir
y sobrevive a cerrar la app), y es la clave de deduplicado del §2.5.

Se escribe sin milisegundos —`new Date().toISOString().slice(0, 19) + "Z"`— porque la
columna `date` de MySQL tiene precisión de segundo y los milisegundos se pierden al
guardar. Mandarlos haría que el valor devuelto nunca coincidiera con el local, y el
deduplicado no encontraría nunca su propio entreno.

`hecha` es del cliente. Marca qué serie está registrada para pintar el `[✓]` y calcular el
avance; el servidor no tiene ese campo y no le hace falta.

### 2.5 Subir, fallar y reintentar

Al pulsar terminar:

1. Se construye el cuerpo del `POST` desde la `Sesion` (§4.1).
2. Se intenta `POST /api/workouts`.
3. **Si sale bien:** se borra el borrador y se va al resumen con el bloque `system`.
4. **Si falla:** la sesión entra en la cola de pendientes, se borra el borrador y se va al
   resumen sin bloque `system`, diciendo que se subirá solo.

El borrador se borra en los dos casos porque en los dos el dato ya está a salvo: subido, o
encolado. Es la única transición que lo borra.

**Cuándo se reintenta:** al recibir el evento `online`, al abrir la aplicación, y con un
botón en el aviso. Nada más.

Sin retroceso exponencial: lo que hay al otro lado es un hosting compartido con un
usuario, no un servicio que haya que proteger de una estampida. `WorkManager` lo traía
puesto en Android; escribirlo aquí sería pagar por un problema que no existe.

**El deduplicado.** Un `POST` puede haberse cometido en el servidor y perderse la
respuesta —túnel, cambio de red—. Reintentar a ciegas duplicaría el entreno. Antes de
reenviar, la cola pide `GET /api/workouts?per_page=5` y descarta la sesión si ya hay uno
con el mismo `date`, comparando instantes con `Date.parse`, no cadenas: el servidor
serializa en UTC con su propio formato.

Cinco basta: son los cinco últimos por fecha descendente, y un entreno pendiente es de
hace minutos u horas. Si alguien encolara seis entrenos sin cobertura y el sexto ya
estuviera subido, se duplicaría. `ponytail:` ese techo se sube pasando `date_from` con la
fecha del pendiente más antiguo, cuando alguien se lo encuentre.

**Y si aun así se duplica, se acepta.** Un entreno duplicado se ve y se borra; uno perdido
no se recupera. Ante la duda, el código duplica.

### 2.6 Cuando ni siquiera se puede guardar

`setItem` lanza si la cuota está llena o si el navegador va en modo privado. FitLoop se lo
tragaba en silencio (`old/resources/views/training.blade.php:1230`), y en silencio
significa que el usuario cree que su entreno está a salvo cuando no lo está.

Aquí `guardar` devuelve si pudo escribir. A la primera negativa la pantalla saca un aviso
rojo permanente —«no se puede guardar el entreno en este navegador, no cierres la app
hasta terminarlo»— y no lo quita hasta que una escritura vuelva a salir bien.

Es la única simplificación que no se hace: sin este aviso, el fallo que la fase pone como
criterio de terminación ocurre sin que nadie se entere.

---

## 3. Ficheros

```
web/src/
  borrador.ts        NUEVO   sesión activa · cola · payload · deduplicado    ~90 líneas
  formato.ts         crece   volumen, duración, textos de XP y de récord
  api.ts             crece   entrenos, plantillas, ejercicios y sus tipos
  componentes.tsx    crece   VentanaSistema · FilaSerie · Aviso
  pantallas/
    Elegir.tsx       NUEVO   plantilla · repetir el último · en blanco
    Sesion.tsx       NUEVO   la que de verdad importa
    Resumen.tsx      NUEVO   duración, volumen, récords, ventana del Sistema
    Plantillas.tsx   NUEVO   crear, editar y borrar
  App.tsx            crece   rutas nuevas y el aviso de pendientes
```

`borrador.ts` **no importa React**. Es lo que lo deja probado sin montar nada, igual que
`formato.ts`. Toda la persistencia vive ahí dentro: ninguna pantalla llama a
`localStorage` directamente, y por eso cambiar a IndexedDB algún día sería un fichero.

Las pantallas no calculan nada del Sistema. Reciben el bloque `system` y lo pintan.

---

## 4. Contratos con la API

Todos los endpoints existen ya. Esta fase no toca el backend.

### 4.1 Guardar el entreno

`POST /api/workouts`, **una fila por serie**:

```json
{
  "mode": "gym",
  "date": "2026-08-18T17:00:00Z",
  "duration_minutes": 45,
  "notes": null,
  "exercises": [
    { "name": "Press banca", "sets": [
      { "weight_kg": 80, "reps": 5, "rpe": 8, "rest_seconds": 180 },
      { "weight_kg": 80, "reps": 5 }
    ]}
  ]
}
```

De la `Sesion` al cuerpo: se quita `hecha`, se quitan los campos nulos de la disposición
que no toca —una serie de fuerza no manda `distance_m`— y se descartan las series vacías.

**Vacía significa sin ningún dato de su disposición:** en fuerza, ni peso ni reps; en
natación, ni distancia ni tiempo. Una serie con reps y sin peso —dominadas a peso
corporal— sí se manda. Un ejercicio que se queda sin ninguna serie tampoco se manda.

La respuesta trae el entreno guardado, `new_records`
(`{name, weight_kg, previous_pr, is_first}`) y el bloque `system` de la fase 1.0
(`xp_gained`, `level_up`, `rank_up`, `achievements_unlocked`, `records`,
`quests_completed`, `progress`).

`duration_minutes` es obligatorio y va de 0 a 600. Se calcula de `inicio` a ahora, **y se
puede corregir antes de guardar** (§5.3): un borrador retomado al día siguiente daría una
duración absurda, y quien deja la app abierta mientras descansa una hora tampoco entrenó
esa hora.

### 4.2 Plantillas

`GET /api/templates` devuelve las del sistema (`user_id` nulo) y las del usuario en una
sola lista. **Las del sistema no se pueden editar ni borrar**: el servidor responde 403.
La pantalla no enseña esos botones en ellas.

⚠️ **`PUT /api/templates/{id}` solo acepta `name`, `sets` y `reps` por ejercicio.**
`POST` admite además `weight`, `time` y `distance` —con esos nombres, no `weight_kg`— pero
el `PUT` los descarta y el `POST` no los valida. El editor gestiona **nombre · series ·
reps** y nada más: montar campos para datos que la API tira al primer guardado es enseñar
al usuario un dato que va a desaparecer solo.

Las plantillas **no guardan descanso**. El descanso por defecto vive en `localStorage`
—como en FitLoop— y se ajusta desde la sesión.

### 4.3 Ejercicios

| | |
|---|---|
| `GET /api/exercises` | doce ejercicios fijos, escritos en el controlador |
| `GET /api/exercises/suggestions?q=` | hasta ocho nombres del **historial del usuario**, por uso |
| `GET /api/exercises/last-session?name=` | las series de la última vez que hizo ese ejercicio |
| `GET /api/exercises/records` | los récords personales |

Las sugerencias salen solo del historial, así que **con historial vacío no sugieren
nada**. El buscador de ejercicio une las dos fuentes: las sugerencias primero, y detrás
los doce fijos que no estén ya. Y siempre se puede escribir un nombre libre, que es como
entran los ejercicios nuevos.

`last-session` alimenta la columna «Anterior» de la sesión. Es una comodidad, no un
requisito: si falla o no hay conexión, la columna sale vacía y no se avisa de nada. Que no
haya red es el caso normal de esta pantalla.

### 4.4 El entreno sugerido

`GET /api/system/today` ya devuelve `suggested_workout` con `reason` (ya escrito en
castellano), `weekly_done`, `weekly_goal` y `template`. En `api.ts` el tipo `DiaDeHoy`
todavía no lo declara: hay que añadirlo.

---

## 5. Pantallas

Sub-pantallas de `hoy`, según el §8 del spec: `/entrenar`, `/entrenar/sesion`,
`/entrenar/resumen` y `/plantillas`.

Todas se montan con los componentes que ya existen. Los tres nuevos —`VentanaSistema`,
`FilaSerie` y `Aviso`— entran en `componentes.tsx` con el resto.

### 5.1 Elegir entreno · `/entrenar`

El motivo del servidor arriba —«Te faltan 2 entrenos para tu meta de esta semana»— y tres
formas de empezar: la plantilla sugerida, repetir el último entreno de ese modo, o en
blanco. Debajo, el resto de plantillas del modo elegido.

Repetir el último es `GET /api/workouts?mode=X&per_page=1&sort=desc`, agrupando las series
por nombre en el orden en que vienen. Los pesos se copian y ninguna serie llega marcada.

Si hay un borrador, esta pantalla no es la primera: manda el §5.5.

### 5.2 Sesión activa · `/entrenar/sesion`

La que se usa de pie, con una mano, con la pantalla sudada y con prisa.

Un ejercicio en pantalla. Arriba, el nombre, «Ejercicio 2 de 5» y el tiempo transcurrido.
En medio, las series en filas. Abajo, añadir serie, pasar de ejercicio y terminar.

**Dos disposiciones de fila**, por modo:

| Modo | Columnas |
|---|---|
| `gym` · `home` | Serie · Anterior · Kg · Reps · RPE · `[✓]` |
| `calisthenics` | igual, pero la columna de peso se titula **Lastre** |
| `swimming` | Serie · Distancia (m) · Tiempo (s) · Estilo · `[✓]` |

`pasos` no aparece: no es entrenamiento, va con actividad diaria en la fase 1.3.

Los campos son `<input type="number">`, que en el móvil abre el teclado numérico sin que
haya que montar nada, con `inputMode="decimal"` en peso, lastre y distancia —admiten
medios kilos— y `inputMode="numeric"` en reps, RPE y tiempo, que son enteros. El `[✓]` es
un botón de 48 px; el corchete y la marca van `aria-hidden` y el estado se dice con
palabras: «serie 3, hecha».

Al empezar la sesión se pide una vez `GET /api/exercises/records` y se guarda en memoria.
Es lo que permite avisar de un récord en el momento (§5.4). Si no hay red no se pide y no
se avisa: el aviso es una comodidad, y el récord de verdad lo decide el servidor al
guardar.

**Cronómetro de descanso.** El spec lo pone en la 1.5, pero entre series hace falta y la
fase autoriza a decidirlo. Se adelanta **solo dentro de esta pantalla**: al marcar una
serie arranca una cuenta atrás desde el descanso por defecto, con `setInterval` y un botón
para saltarla. La pantalla suelta de cronómetro sigue siendo de la 1.5.

Sin sonido ni vibración: hacen falta permisos y no funcionan igual en cada navegador, y
esta fase no se va a colgar de eso. `ponytail:` si molesta no verlo, `navigator.vibrate`
en el cero es una línea.

**Cada cambio escribe.** Marcar una serie, teclear un peso, añadir un ejercicio, pasar de
ejercicio: todo pasa por `guardar()`. Sin rebote y sin esperar a salir.

### 5.3 Terminar

Antes de mandar nada: la duración, ya rellena con el tiempo transcurrido y **editable**, y
un campo de notas. Después, guardar.

Es el único paso intermedio de todo el flujo y existe porque `duration_minutes` decide el
XP (50 a partir de 15 minutos) y porque un borrador retomado tarde daría una cifra
imposible.

### 5.4 Resumen · `/entrenar/resumen`

Duración, volumen total (peso × reps, sumado) y series hechas. Y luego, según haya habido
red o no:

- **Subido:** la ventana del Sistema en cian con el XP ganado, la subida de nivel o de
  rango, los logros y los récords. Aparece sola y solo por esas cuatro cosas (§6.7).
- **Encolado:** «guardado en el móvil, se subirá solo en cuanto haya conexión». Sin
  ventana del Sistema: el XP lo decide el servidor y aquí todavía no ha hablado. Inventar
  una cifra para tapar el hueco sería reimplementar el cálculo en el cliente.

**El tercer entreno del día.** El tope son dos entrenos con XP; el tercero se guarda y
viene con `xp_gained: 0`. Se explica con palabras —«guardado. Hoy ya has ganado XP con dos
entrenos, así que este no puntúa»— y no se pinta como un error, porque no lo es.

Los récords se anuncian aquí y en el momento de batirlos: al marcar una serie que supera
el mejor peso conocido de ese ejercicio, la fila lo dice. El servidor sigue siendo quien
decide; esto es un aviso local sobre `GET /api/exercises/records`, y si no hay red no sale.

### 5.5 Retomar el borrador

Al abrir la aplicación con un borrador guardado, `hoy` enseña arriba de todo qué entreno
es, cuántas series lleva y de cuándo es —«hace 12 minutos», «de ayer»—, con dos botones:
seguir o descartar.

Va en `hoy` y no encima de las pestañas como el aviso del §5.6, y la diferencia no es
estética: un borrador es trabajo a medias que se retoma yendo a entrenar, y el §8 ya le
tiene sitio en la sección «entreno de hoy». El aviso de pendientes es una advertencia de
pérdida de datos, y esas se ven desde donde se esté.

Descartar pide confirmación. Es la única forma de perder un entreno a propósito y tiene
que costar dos pulsaciones.

Un borrador viejo no se descarta solo. Que sea de anteayer no lo hace basura: puede ser
justo el que hay que recuperar.

### 5.6 El aviso de pendientes

Vive en `App.tsx`, encima de las pestañas, para que se vea desde cualquier pantalla: «1
entreno pendiente de subir» y un botón de reintentar. Desaparece cuando la cola se vacía.

Si el reintento sube algo que traía subida de nivel, récord o logro, sale su ventana del
Sistema. Es el mismo componente y una llamada más.

### 5.7 Plantillas · `/plantillas`

Crear, editar y borrar: nombre, modo, nivel y una lista de ejercicios con series y reps
(§4.2). Las del sistema se ven y se usan, pero no se editan ni se borran.

---

## 6. Qué se prueba

Con Vitest, y solo lo que tiene ramas que se puedan equivocar (§11 del spec).

**`borrador.test.ts`** — la sesión sobrevive a una vuelta completa de escritura y lectura ·
un JSON corrupto devuelve null **y no borra nada** · una `v` desconocida hace lo mismo ·
la cola entra, sale y respeta el orden · `guardar` devuelve falso cuando `setItem` lanza ·
el payload quita `hecha` y las series vacías · el deduplicado descarta por `date` con dos
formatos de fecha distintos y el mismo instante.

**`formato.test.ts`** — volumen con series a medio rellenar · duración y su texto · el
texto de `xp_gained: 0` · el texto de récord con y sin marca anterior.

**`Sesion.test.tsx`** — apuntar una serie escribe en `localStorage` · desmontar y volver a
montar recupera el estado exacto · terminar sin red deja la sesión en la cola y no en el
borrador · un `setItem` que lanza saca el aviso rojo.

No se prueba el resto de la interfaz. La API está probada en el backend con 285 tests y no
se toca en esta fase.

⚠️ Lo que ninguno de estos tests demuestra: que la aplicación funcione de verdad en modo
avión en el móvil. `jsdom` no tiene ni red que cortar ni proceso que matar. Los dos
criterios de la fase —completar un entreno en modo avión, y matar la app a media sesión—
se comprueban **en el móvil, con la PWA instalada**, y no dan por buena la fase hasta que
alguien lo haga.

---

## 7. Desviaciones del spec

| Qué | Dónde estaba | Qué se hace | Por qué |
|---|---|---|---|
| Borrador en `localStorage` | §3, §9: IndexedDB | `localStorage` | Síncrono: no hay escritura en vuelo que perder al matar la app (§2.1) |
| Cronómetro de descanso | fase 1.5 | dentro de la sesión | Entre series hace falta. La pantalla suelta sigue en 1.5 |
| Historial de entrenos | checklist de la 1.2 | a la fase 1.4 | El §8 ya lo pone en `progreso`. En 1.2 queda solo «repetir el último» |
| `pasos` | modo válido de la API | fuera de esta fase | No es entrenamiento. Va con actividad diaria, en la 1.3 |

---

## 8. Fuera de alcance

Sincronización bidireccional · Background Sync (no existe en iOS y el trabajador de
servicio de la 1.1 a propósito no guarda respuestas de la API) · retroceso exponencial ·
editar o borrar entrenos ya subidos · el calendario, el mapa de calor y las gráficas de la
1.4 · sonido y vibración al acabar el descanso.
