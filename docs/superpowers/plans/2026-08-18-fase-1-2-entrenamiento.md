# Fase 1.2 · Entrenamiento — Plan de implementación

> **Para quien ejecute esto por agentes:** SUB-SKILL OBLIGATORIA: usa
> `superpowers:subagent-driven-development` (recomendado) o
> `superpowers:executing-plans` para implementar tarea por tarea. Los pasos van con
> casilla (`- [ ]`) para poder marcarlos.

**Objetivo:** poder hacer un entreno entero desde el móvil, sin cobertura, sin perderlo
jamás, y que al guardarlo el Sistema diga qué se ha ganado.

**Arquitectura:** toda la persistencia y la subida viven en `web/src/borrador.ts`, que no
importa React y por eso se prueba sin montar nada. Las pantallas leen y escriben a través
de él y no tocan `localStorage` nunca. El cálculo de XP, récords y logros ya está hecho y
probado en el servidor desde la fase 1.0: aquí solo se manda un `POST /api/workouts` y se
pinta el bloque `system` que vuelve.

**Tecnología:** React 19 + TypeScript sobre Vite 8, tests con Vitest 4 y
`@testing-library/react` en `jsdom`. Sin dependencias nuevas.

**Diseño aprobado:** [`../specs/2026-08-18-entrenamiento-design.md`](../specs/2026-08-18-entrenamiento-design.md)

---

## Restricciones globales

Valen para **todas** las tareas. No se repiten en cada una.

1. **Todo en español:** código, comentarios, nombres de función, textos de pantalla y
   mensajes de commit. El porqué en el cuerpo del commit, no solo el qué.
2. **Sin vocabulario de terminal en pantalla.** «Serie», «repeticiones», «descanso». Los
   `[✓]`, `//`, `▸` y `$` son decoración: van `aria-hidden="true"` y el estado se dice con
   palabras. Una serie hecha se oye «serie 3, hecha», nunca «corchete, marca, corchete».
3. **Sin iconos ni emoji.** En ninguna pantalla, en ningún estado.
4. **⚠️ Ningún `--nombre: #rrggbb` nuevo en `estilos.css`.** `estilos.test.ts` extrae con
   una expresión regular todas las propiedades personalizadas que valen un hexadecimal y
   comprueba `toEqual` contra los once colores del spec. Una propiedad nueva **rompe ese
   test**. El CSS nuevo usa `var(--ambar)`, `var(--cian)`, etc. y nada más.
5. **El cian es exclusivo de la ventana del Sistema.** Si aparece en la interfaz normal,
   deja de significar «premio».
6. **Objetivos táctiles de 48 px** en todo lo que se pulse.
7. **El XP se calcula en el servidor.** La app lo pinta, nunca lo decide, nunca lo estima.
8. **Las fechas viajan en UTC**, sin milisegundos (`workouts.date` es `datetime` de MySQL,
   precisión de segundo).
9. **Ningún número de error HTTP llega a la pantalla.** `api.ts` ya traduce; las pantallas
   enseñan `error.fallo.general`.
10. **Un test que no falla sin el arreglo no vale.** En cada tarea, el paso «comprobar que
    falla» es obligatorio y no se salta.
11. Comandos: `cd web && npm test` (toda la suite), `npm test -- <fichero>` (uno solo),
    `npm run build` (comprueba tipos con `tsc -b`).

### Datos reales de la API, ya verificados

No hay que ir a mirarlos: están comprobados contra el backend de esta rama.

- `workouts.id` y `templates.id` son **`char(36)`**, o sea cadenas UUID. `template_exercises.id` es un entero.
- `POST /api/workouts` devuelve el entreno + `new_records` + `system`.
- **`new_records` y `system.records` son el mismo récord con dos formas distintas.** El de primer nivel, `new_records`, es `{name, weight_kg, previous_pr, is_first}`. El de dentro del bloque, `system.records`, pasa antes por `SystemService::formatRecords()` y sale como **`{exercise, kind, value, previous}`** — la forma del spec §5.3, más `previous`. Confirmado por `SystemRewardsTest::test_guardar_un_entreno_devuelve_el_bloque_system_con_xp`, que afirma sobre `system.records.0.exercise`. **La interfaz consume siempre `system.records`**, que es la que llega en toda respuesta con bloque `system`; `new_records` se tipa por completitud y no lo lee nadie.
- `previous == null` en `system.records` significa que era la primera marca: `formatRecords` lo pone a `null` justo cuando `previous_pr` lo era. No hace falta el `is_first` de `new_records` para distinguirlo.
- `system.progress` = `{level, rank, xp_total, xp_into_level, xp_for_next, current_streak, longest_streak, stats}`.
- `GET /api/exercises/records` devuelve `[{name, max_weight, reps, sets, date}]`. `max_weight` sale de una consulta cruda: hay que pasarlo por `Number()`.
- `GET /api/exercises/suggestions?q=` devuelve un **array de cadenas**, solo del historial del usuario.
- `GET /api/exercises` devuelve doce ejercicios fijos escritos a mano en el controlador.
- `PUT /api/templates/{id}` **solo acepta** `name`, `description`, `level`, `mode`, `duration_minutes` y `exercises[].{name,sets,reps}`. Todo lo demás lo descarta.
- Las plantillas con `user_id: null` son del sistema: editarlas o borrarlas da 403.

---

## Estructura de ficheros

| Fichero | Responsabilidad | Tareas |
|---|---|---|
| `web/src/borrador.ts` | **NUEVO.** Que el entreno no se pierda: tipos, `localStorage`, cola, payload, subida. Sin React. | 1–4 |
| `web/src/formato.ts` | Crece: volumen, duración, antigüedad y textos con ramas. | 5 |
| `web/src/api.ts` | Crece: entrenos, plantillas, ejercicios. Y el arreglo del CSRF. | 6 |
| `web/src/componentes.tsx` | Crece: `Aviso`, `VentanaSistema`, `FilaSerie`, `Boton compacto`. | 7–8 |
| `web/src/estilos.css` | Crece: las clases de lo anterior. Sin colores nuevos. | 7–8 |
| `web/src/pantallas/Sesion.tsx` | **NUEVO.** La sesión activa y el paso de terminar. | 9–11, 14 |
| `web/src/pantallas/Resumen.tsx` | **NUEVO.** Solo pinta lo que le llega por `location.state`. | 12 |
| `web/src/pantallas/Elegir.tsx` | **NUEVO.** Plantilla, repetir el último, en blanco. | 13 |
| `web/src/pantallas/Plantillas.tsx` | **NUEVO.** Crear, editar, borrar. | 15 |
| `web/src/App.tsx` | Crece: rutas nuevas y el aviso de pendientes. | 16 |
| `web/src/pantallas/Hoy.tsx` | Crece: retomar el borrador y el entreno sugerido. | 17 |
| — | Comprobación en el móvil y cierre de la fase. | 18 |

`borrador.ts` acaba en unas 180 líneas y es el único sitio del proyecto que menciona
`localStorage`. Esa es la frontera que permite cambiar a IndexedDB algún día tocando un
fichero y ninguna pantalla.

---

## Tarea 1 · `borrador.ts`: los tipos y la sesión activa

**Ficheros:**
- Crear: `web/src/borrador.ts`
- Crear: `web/src/borrador.test.ts`

**Interfaces:**
- Consume: nada.
- Produce: los tipos `Modo`, `Serie`, `Ejercicio`, `Sesion`; `VERSION`, `ahoraUtc()`,
  `guardar(sesion): boolean`, `leer(): Sesion | null`, `borrar(): void`.

- [ ] **Paso 1 · Escribir el test que falla**

Crear `web/src/borrador.test.ts`:

```ts
/* Este fichero prueba lo único irrecuperable de toda la aplicación. Si algo de aquí se
   rompe, alguien pierde un entreno y ya no se acuerda de lo que levantó. */

import { beforeEach, expect, test, vi } from "vitest";
import { ahoraUtc, borrar, guardar, leer, type Sesion } from "./borrador";

const SESION: Sesion = {
  v: 1,
  mode: "gym",
  nombre: "Torso pesado",
  inicio: "2026-08-18T17:00:00Z",
  actual: 0,
  exercises: [
    {
      name: "Press banca",
      objetivo: { sets: 4, reps: 5 },
      sets: [
        { weight_kg: 80, reps: 5, rpe: 8, distance_m: null, time_seconds: null, style: null, rest_seconds: 180, hecha: true },
      ],
    },
  ],
};

beforeEach(() => {
  localStorage.clear();
});

test("lo que se guarda es exactamente lo que se lee", () => {
  expect(guardar(SESION)).toBe(true);
  expect(leer()).toEqual(SESION);
});

test("sin nada guardado no hay sesión y no revienta", () => {
  expect(leer()).toBe(null);
});

test("un JSON corrupto devuelve null y NO borra lo que hay en disco", () => {
  localStorage.setItem("srank.entreno-activo", "{esto no es JSON");

  expect(leer()).toBe(null);
  // Lo que no se sabe leer no se tira: puede ser el único sitio donde está ese entreno,
  // y un despliegue posterior o una revisión a mano todavía pueden rescatarlo.
  expect(localStorage.getItem("srank.entreno-activo")).toBe("{esto no es JSON");
});

test("una versión desconocida hace lo mismo: null, y sin borrar", () => {
  localStorage.setItem("srank.entreno-activo", JSON.stringify({ ...SESION, v: 99 }));

  expect(leer()).toBe(null);
  expect(localStorage.getItem("srank.entreno-activo")).not.toBe(null);
});

test("guardar avisa cuando no ha podido escribir", () => {
  // Modo privado, o cuota llena. FitLoop se tragaba esto en silencio, y en silencio
  // significa que el usuario cree que su entreno está a salvo cuando no lo está.
  vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
    throw new DOMException("QuotaExceededError");
  });

  expect(guardar(SESION)).toBe(false);
});

test("borrar deja el hueco limpio", () => {
  guardar(SESION);
  borrar();
  expect(leer()).toBe(null);
});

test("la marca de tiempo va en UTC y sin milisegundos", () => {
  // MySQL guarda `workouts.date` con precisión de segundo. Si se mandan milisegundos, el
  // valor que devuelve el servidor no coincide nunca con el local y el deduplicado de la
  // tarea 4 no encuentra jamás su propio entreno.
  expect(ahoraUtc()).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- borrador`
Esperado: FALLA. No existe `./borrador`, así que ni siquiera resuelve el import.

- [ ] **Paso 3 · Escribir la implementación mínima**

Crear `web/src/borrador.ts`:

```ts
/* Que no se pierda un entreno. Es lo único irrecuperable de toda la aplicación: si se
   pierde, el usuario ya no se acuerda de lo que levantó.

   Aquí vive TODA la persistencia del módulo. Ninguna pantalla llama a `localStorage`
   directamente, y por eso cambiar algún día a IndexedDB sería este fichero y ninguna
   pantalla más.

   Por qué localStorage y no IndexedDB, que es lo que decía el spec §9: `setItem` es
   síncrono, así que cuando vuelve el dato ya está escrito. Si el sistema mata la pestaña
   en ese instante no hay nada en vuelo que perder, que es justo el fallo que esta fase
   tiene que blindar. IndexedDB es asíncrono y ahí sí se pierde la escritura en curso.
   ponytail: el techo son 5 MB por origen contra ~2 KB por sesión. Si algún día se
   guardara algo grande —fotos, historial local entero—, entonces sí IndexedDB. */

export type Modo = "gym" | "home" | "calisthenics" | "swimming";

export type Serie = {
  // Fuerza: gym · home · calisthenics. En calistenia el peso es el lastre.
  weight_kg: number | null;
  reps: number | null;
  rpe: number | null;
  // Natación.
  distance_m: number | null;
  time_seconds: number | null;
  style: string | null;
  // Comunes.
  rest_seconds: number | null;
  /** Solo del cliente: pinta el `[✓]` y cuenta el avance. Al servidor no se manda. */
  hecha: boolean;
};

export type Ejercicio = {
  name: string;
  /** Lo que pedía la plantilla, si vino de una. Es una guía, no una obligación. */
  objetivo: { sets: number | null; reps: number | null } | null;
  sets: Serie[];
};

export type Sesion = {
  v: typeof VERSION;
  mode: Modo;
  nombre: string;
  /** ISO 8601 UTC sin milisegundos. Hace tres trabajos: es la fecha que se manda, es de
   *  donde sale el cronómetro (`ahora − inicio`, así que no hay contador que persistir) y
   *  es la clave de deduplicado al reintentar. */
  inicio: string;
  exercises: Ejercicio[];
  /** Qué ejercicio se está viendo. */
  actual: number;
};

export const VERSION = 1;

const ACTIVO = "srank.entreno-activo";

/** Sin milisegundos: `workouts.date` es un `datetime` de MySQL y los pierde al guardar.
 *  Mandarlos haría que el valor devuelto no coincidiera nunca con el local. */
export function ahoraUtc(): string {
  return `${new Date().toISOString().slice(0, 19)}Z`;
}

/** Devuelve si pudo escribir. **Nadie puede tragarse un `false`**: significa que el
 *  entreno no está a salvo y el usuario tiene que enterarse (§2.6 del diseño). */
function escribir(clave: string, valor: unknown): boolean {
  try {
    localStorage.setItem(clave, JSON.stringify(valor));
    return true;
  } catch {
    // Cuota llena, o navegador en modo privado.
    return false;
  }
}

/** `getItem` también puede lanzar en algunos modos privados, no solo `setItem`. */
function crudo(clave: string): string | null {
  try {
    return localStorage.getItem(clave);
  } catch {
    return null;
  }
}

function esSesion(dato: unknown): dato is Sesion {
  const s = dato as Sesion | null;
  return (
    !!s &&
    typeof s === "object" &&
    s.v === VERSION &&
    typeof s.inicio === "string" &&
    Array.isArray(s.exercises)
  );
}

export function guardar(sesion: Sesion): boolean {
  return escribir(ACTIVO, sesion);
}

/** null si no hay nada, si no se puede leer o si no se entiende. En los tres casos **no
 *  se borra nada**: lo que no se sabe leer puede seguir siendo el único sitio donde está
 *  ese entreno. */
export function leer(): Sesion | null {
  const texto = crudo(ACTIVO);
  if (!texto) return null;
  try {
    const dato: unknown = JSON.parse(texto);
    return esSesion(dato) ? dato : null;
  } catch {
    return null;
  }
}

/** La única salida del borrador. Se llama después de subirlo o de encolarlo, y cuando el
 *  usuario lo descarta a mano. Desde ningún otro sitio. */
export function borrar(): void {
  try {
    localStorage.removeItem(ACTIVO);
  } catch {
    // Si ni siquiera se puede borrar, el borrador viejo se ofrecerá otra vez. Molesto,
    // pero no pierde nada, que es el orden de prioridades de este fichero.
  }
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- borrador`
Esperado: PASAN los 7.

- [ ] **Paso 5 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/borrador.ts web/src/borrador.test.ts
git commit -m "feat(entreno): el borrador de la sesión sobrevive a cerrar la app

localStorage y no IndexedDB, que es lo que decía el spec §9: setItem es
síncrono, así que cuando vuelve el dato está escrito. Si el sistema mata la
pestaña en ese instante no hay escritura en vuelo que perder, y ese es justo
el fallo que esta fase tiene que blindar.

Lo que no se sabe leer no se borra. Un JSON corrupto o una versión
desconocida devuelven null y dejan el dato en disco: puede ser el único
sitio donde está ese entreno.

Y guardar devuelve si pudo escribir. FitLoop se tragaba el fallo de cuota en
silencio, que es la forma de que el usuario crea que su entreno está a salvo
cuando no lo está."
```

---

## Tarea 2 · `borrador.ts`: la cola de pendientes y el descanso por defecto

**Ficheros:**
- Modificar: `web/src/borrador.ts`
- Modificar: `web/src/borrador.test.ts`

**Interfaces:**
- Consume: `Sesion`, `escribir`, `crudo`, `esSesion` de la tarea 1.
- Produce: `pendientes(): Sesion[]`, `encolar(sesion): boolean`,
  `quitarDePendientes(inicio: string): void`, `descansoPorDefecto(): number`,
  `guardarDescansoPorDefecto(segundos: number): void`.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/borrador.test.ts`:

```ts
test("la cola guarda más de un entreno y respeta el orden", () => {
  // Se puede terminar un entreno sin cobertura y empezar otro antes de recuperarla.
  // Por eso la cola es un array y no un solo registro.
  const segunda: Sesion = { ...SESION, inicio: "2026-08-18T19:30:00Z", nombre: "Piernas" };

  expect(encolar(SESION)).toBe(true);
  expect(encolar(segunda)).toBe(true);

  expect(pendientes().map((s) => s.inicio)).toEqual([
    "2026-08-18T17:00:00Z",
    "2026-08-18T19:30:00Z",
  ]);
});

test("quitar de la cola quita solo el que se sube, no la cola entera", () => {
  const segunda: Sesion = { ...SESION, inicio: "2026-08-18T19:30:00Z" };
  encolar(SESION);
  encolar(segunda);

  quitarDePendientes("2026-08-18T17:00:00Z");

  expect(pendientes().map((s) => s.inicio)).toEqual(["2026-08-18T19:30:00Z"]);
});

test("una cola con basura dentro devuelve lo que sí se entiende", () => {
  // Si un despliegue cambiara la forma, perder los buenos por culpa de uno malo sería
  // exactamente el fallo que este fichero existe para evitar.
  localStorage.setItem(
    "srank.entrenos-pendientes",
    JSON.stringify([{ v: 99, roto: true }, SESION]),
  );

  expect(pendientes()).toEqual([SESION]);
});

test("la cola es un sitio distinto del borrador", () => {
  // Mezclarlos obligaría a distinguirlos con una bandera dentro del mismo registro, que
  // es la forma habitual de subir un entreno a medias por error.
  guardar(SESION);
  encolar(SESION);

  borrar();

  expect(leer()).toBe(null);
  expect(pendientes()).toHaveLength(1);
});

test("el descanso por defecto tiene un valor razonable la primera vez", () => {
  expect(descansoPorDefecto()).toBe(90);
});

test("el descanso por defecto se recuerda entre sesiones", () => {
  guardarDescansoPorDefecto(180);
  expect(descansoPorDefecto()).toBe(180);
});

test("un descanso guardado sin sentido no se cree", () => {
  for (const basura of ["", "hola", "-30", "0"]) {
    localStorage.setItem("srank.descanso-defecto", basura);
    expect(descansoPorDefecto(), `«${basura}» dio otra cosa`).toBe(90);
  }
});
```

Y ampliar el import de la primera línea del fichero:

```ts
import {
  ahoraUtc,
  borrar,
  descansoPorDefecto,
  encolar,
  guardar,
  guardarDescansoPorDefecto,
  leer,
  pendientes,
  quitarDePendientes,
  type Sesion,
} from "./borrador";
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- borrador`
Esperado: FALLA con `does not provide an export named 'encolar'` o equivalente.

- [ ] **Paso 3 · Escribir la implementación mínima**

Añadir al final de `web/src/borrador.ts`:

```ts
const PENDIENTES = "srank.entrenos-pendientes";
const DESCANSO = "srank.descanso-defecto";

/** 90 segundos. Es lo que dura un descanso normal entre series de fuerza, y es lo que se
 *  usa mientras el usuario no diga otra cosa. */
const DESCANSO_INICIAL = 90;

/** Los entrenos terminados que todavía no se han subido.
 *
 *  Es un array y no un solo registro porque se puede terminar un entreno sin cobertura y
 *  empezar otro antes de recuperarla. Lo que no se entiende se descarta uno a uno: perder
 *  los buenos por culpa de uno malo sería el fallo que este fichero evita. */
export function pendientes(): Sesion[] {
  const texto = crudo(PENDIENTES);
  if (!texto) return [];
  try {
    const dato: unknown = JSON.parse(texto);
    return Array.isArray(dato) ? dato.filter(esSesion) : [];
  } catch {
    return [];
  }
}

export function encolar(sesion: Sesion): boolean {
  return escribir(PENDIENTES, [...pendientes(), sesion]);
}

/** Se llama al confirmar que ese entreno ya está en el servidor. `inicio` es la clave:
 *  es único por sesión y es lo mismo que el servidor guarda en `date`. */
export function quitarDePendientes(inicio: string): void {
  escribir(
    PENDIENTES,
    pendientes().filter((s) => s.inicio !== inicio),
  );
}

/** El descanso que se propone al añadir una serie. Las plantillas no lo traen: la columna
 *  `template_exercises.rest_seconds` existe pero ni el POST ni el PUT de la API la
 *  aceptan, así que siempre llega nula. Vive aquí, como en FitLoop. */
export function descansoPorDefecto(): number {
  const n = Number(crudo(DESCANSO));
  return Number.isFinite(n) && n > 0 ? n : DESCANSO_INICIAL;
}

export function guardarDescansoPorDefecto(segundos: number): void {
  escribir(DESCANSO, segundos);
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- borrador`
Esperado: PASAN los 14.

- [ ] **Paso 5 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/borrador.ts web/src/borrador.test.ts
git commit -m "feat(entreno): cola de entrenos pendientes de subir

Terminar un entreno sin cobertura no puede quedar en nada. La sesión pasa a
una cola en localStorage, que es un array y no un registro suelto porque se
puede terminar uno sin red y empezar otro antes de recuperarla.

La cola vive en una clave distinta de la del borrador a propósito: son dos
estados distintos —el activo se edita, la cola se sube— y mezclarlos
obligaría a distinguirlos con una bandera dentro del mismo registro, que es
la forma habitual de subir un entreno a medias por error.

El descanso por defecto va aquí porque las plantillas no lo traen: la
columna template_exercises.rest_seconds existe pero ni el POST ni el PUT de
la API la aceptan, así que siempre llega nula."
```

---

## Tarea 3 · `borrador.ts`: de la sesión al cuerpo del `POST`

**Ficheros:**
- Modificar: `web/src/borrador.ts`
- Modificar: `web/src/borrador.test.ts`

**Interfaces:**
- Consume: `Sesion`, `Serie`, `Modo` de la tarea 1.
- Produce: los tipos `SeriePayload` y `EntrenoPayload`; `serieVacia(serie, modo): boolean`,
  `aPayload(sesion, duracionMinutos, notas): EntrenoPayload`.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/borrador.test.ts`:

```ts
/** Una serie de fuerza rellena a medias, para no repetir los ocho campos cada vez. */
function serie(campos: Partial<Serie> = {}): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: null, hecha: false,
    ...campos,
  };
}

test("el cuerpo lleva una fila por serie, como pide la API", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [
      {
        name: "Press banca",
        objetivo: null,
        sets: [
          serie({ weight_kg: 80, reps: 5, rpe: 8, rest_seconds: 180, hecha: true }),
          serie({ weight_kg: 80, reps: 5, hecha: true }),
        ],
      },
    ],
  };

  expect(aPayload(sesion, 45, null)).toEqual({
    mode: "gym",
    date: "2026-08-18T17:00:00Z",
    duration_minutes: 45,
    notes: null,
    exercises: [
      {
        name: "Press banca",
        sets: [
          { weight_kg: 80, reps: 5, rpe: 8, rest_seconds: 180 },
          { weight_kg: 80, reps: 5 },
        ],
      },
    ],
  });
});

test("`hecha` no se manda: es del cliente y el servidor no tiene ese campo", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [{ name: "Sentadilla", objetivo: null, sets: [serie({ weight_kg: 100, reps: 3, hecha: true })] }],
  };

  expect(JSON.stringify(aPayload(sesion, 30, null))).not.toContain("hecha");
});

test("las series vacías no se mandan, pero las de peso corporal sí", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [
      {
        name: "Dominadas",
        objetivo: null,
        sets: [
          serie({ reps: 10 }),          // peso corporal: sin kilos pero con reps. Vale.
          serie({ weight_kg: 20 }),     // lastre apuntado y reps por rellenar. Vale.
          serie(),                      // fila que se añadió y nunca se tocó. Fuera.
        ],
      },
    ],
  };

  expect(aPayload(sesion, 30, null).exercises[0].sets).toEqual([{ reps: 10 }, { weight_kg: 20 }]);
});

test("un ejercicio que se queda sin series no se manda", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [
      { name: "Press banca", objetivo: null, sets: [serie({ weight_kg: 80, reps: 5 })] },
      { name: "Se me olvidó hacerlo", objetivo: null, sets: [serie(), serie()] },
    ],
  };

  expect(aPayload(sesion, 30, null).exercises.map((e) => e.name)).toEqual(["Press banca"]);
});

test("natación manda sus campos y ninguno de fuerza", () => {
  const sesion: Sesion = {
    ...SESION,
    mode: "swimming",
    exercises: [
      {
        name: "Crol",
        objetivo: null,
        sets: [serie({ distance_m: 100, time_seconds: 95, style: "Crol", rest_seconds: 30 })],
      },
    ],
  };

  expect(aPayload(sesion, 40, null).exercises[0].sets).toEqual([
    { distance_m: 100, time_seconds: 95, style: "Crol", rest_seconds: 30 },
  ]);
});

test("una serie de natación está vacía si no tiene ni distancia ni tiempo", () => {
  expect(serieVacia(serie(), "swimming")).toBe(true);
  expect(serieVacia(serie({ distance_m: 50 }), "swimming")).toBe(false);
  expect(serieVacia(serie({ time_seconds: 60 }), "swimming")).toBe(false);
  // Y el peso no la salva: en natación no se manda y quedaría una fila sin nada dentro.
  expect(serieVacia(serie({ weight_kg: 80 }), "swimming")).toBe(true);
});

test("las notas vacías van como null, no como cadena vacía", () => {
  expect(aPayload(SESION, 45, "").notes).toBe(null);
  expect(aPayload(SESION, 45, "  ").notes).toBe(null);
  expect(aPayload(SESION, 45, "Buen día").notes).toBe("Buen día");
});
```

Y ampliar el import de la primera línea con `aPayload`, `serieVacia` y `type Serie`.

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- borrador`
Esperado: FALLA con `does not provide an export named 'aPayload'`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Añadir al final de `web/src/borrador.ts`:

```ts
/** Lo que la API acepta en cada serie. Los campos que no van, no van: mandar `null`
 *  explícitos ensucia el cuerpo sin aportar nada, porque la validación de Laravel los
 *  tiene todos como `nullable`. */
export type SeriePayload = {
  weight_kg?: number;
  reps?: number;
  rpe?: number;
  distance_m?: number;
  time_seconds?: number;
  style?: string;
  rest_seconds?: number;
};

export type EntrenoPayload = {
  mode: Modo;
  date: string;
  duration_minutes: number;
  notes: string | null;
  exercises: { name: string; sets: SeriePayload[] }[];
};

/** Los campos que viajan en cada modo. En fuerza no se manda distancia; en natación no se
 *  manda peso. Son dos disposiciones distintas y mezclarlas guardaría filas sin sentido. */
const CAMPOS = {
  fuerza: ["weight_kg", "reps", "rpe", "rest_seconds"],
  natacion: ["distance_m", "time_seconds", "style", "rest_seconds"],
} as const;

/** Vacía = sin ningún dato **de su disposición**.
 *
 *  Una serie con repeticiones y sin peso —dominadas a peso corporal— no está vacía y se
 *  manda: es un entreno real. Una fila que se añadió y nunca se tocó, sí. */
export function serieVacia(serie: Serie, modo: Modo): boolean {
  return modo === "swimming"
    ? serie.distance_m == null && serie.time_seconds == null
    : serie.weight_kg == null && serie.reps == null;
}

export function aPayload(
  sesion: Sesion,
  duracionMinutos: number,
  notas: string | null,
): EntrenoPayload {
  const campos = sesion.mode === "swimming" ? CAMPOS.natacion : CAMPOS.fuerza;

  const exercises = sesion.exercises
    .map((ejercicio) => ({
      name: ejercicio.name,
      sets: ejercicio.sets
        .filter((s) => !serieVacia(s, sesion.mode))
        .map((s) => {
          const salida: Record<string, number | string> = {};
          for (const campo of campos) {
            const valor = s[campo];
            if (valor != null) salida[campo] = valor;
          }
          return salida as SeriePayload;
        }),
    }))
    .filter((ejercicio) => ejercicio.sets.length > 0);

  return {
    mode: sesion.mode,
    date: sesion.inicio,
    duration_minutes: duracionMinutos,
    // Una cadena vacía en `notes` guardaría una nota que no existe.
    notes: notas?.trim() || null,
    exercises,
  };
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- borrador`
Esperado: PASAN los 21.

- [ ] **Paso 5 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/borrador.ts web/src/borrador.test.ts
git commit -m "feat(entreno): traducir la sesión al cuerpo que espera la API

Una fila por serie, que es el formato desde el commit aa2d709. Se quita
\`hecha\`, que es del cliente, y se mandan solo los campos de la disposición
que toca: en fuerza no viaja la distancia y en natación no viaja el peso.

Vacía significa sin ningún dato de su disposición, no sin peso. Una serie
con repeticiones y sin kilos son dominadas a peso corporal y es un entreno
real; una fila que se añadió y nunca se tocó no lo es."
```

---

## Tarea 4 · `borrador.ts`: subir, encolar y reintentar sin duplicar

**Ficheros:**
- Modificar: `web/src/borrador.ts`
- Modificar: `web/src/borrador.test.ts`

**Interfaces:**
- Consume: `aPayload`, `encolar`, `pendientes`, `quitarDePendientes`; de `api.ts`
  (tarea 6) `guardarEntreno` y `entrenos`, y los tipos `EntrenoGuardado` y
  `EntrenoDelHistorial`.
- Produce: `yaSubido(inicio, subidos): boolean`,
  `entregar(sesion, duracion, notas): Promise<EntrenoGuardado | "encolado" | null>`,
  `subirPendientes(): Promise<EntrenoGuardado[]>`.

> **Orden:** esta tarea depende de la 6 (`api.ts`). Quien ejecute el plan por agentes
> debe hacer **la tarea 6 antes que esta**. El orden del documento sigue el fichero, no
> la dependencia.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/borrador.test.ts`:

```ts
test("el deduplicado compara instantes, no cadenas", () => {
  // Laravel serializa `2026-08-18T17:00:00.000000Z`; nosotros mandamos
  // `2026-08-18T17:00:00Z`. Son el mismo momento escrito de dos maneras, y compararlos
  // como texto haría que el reintento no reconociera nunca su propio entreno y lo
  // duplicara en cada vuelta.
  expect(yaSubido("2026-08-18T17:00:00Z", [{ date: "2026-08-18T17:00:00.000000Z" }])).toBe(true);
});

test("el deduplicado no confunde dos entrenos distintos del mismo día", () => {
  expect(
    yaSubido("2026-08-18T17:00:00Z", [
      { date: "2026-08-18T19:30:00.000000Z" },
      { date: "2026-08-17T17:00:00.000000Z" },
    ]),
  ).toBe(false);
});

test("con la lista vacía no está subido", () => {
  expect(yaSubido("2026-08-18T17:00:00Z", [])).toBe(false);
});

test("si el envío falla, la sesión acaba en la cola y no se pierde", async () => {
  vi.mocked(guardarEntreno).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );

  expect(await entregar(SESION, 45, null)).toBe("encolado");
  expect(pendientes().map((s) => s.inicio)).toEqual(["2026-08-18T17:00:00Z"]);
});

test("si además falla la escritura, entregar avisa de que NO está a salvo", async () => {
  vi.mocked(guardarEntreno).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  // El móvil no puede escribir: cuota llena o modo privado.
  vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
    throw new DOMException("QuotaExceededError");
  });

  // `null` y no `"encolado"`: es la diferencia entre que la pantalla borre el borrador o
  // lo deje donde está. Si las dos respuestas fueran la misma, aquí se perdería el entreno.
  expect(await entregar(SESION, 45, null)).toBe(null);
});

test("si el envío sale bien, no se encola nada", async () => {
  vi.mocked(guardarEntreno).mockResolvedValue(SUBIDO);

  expect(await entregar(SESION, 45, null)).toEqual(SUBIDO);
  expect(pendientes()).toEqual([]);
});

test("reintentar sube lo pendiente y lo saca de la cola", async () => {
  encolar(SESION);
  vi.mocked(entrenos).mockResolvedValue([]);
  vi.mocked(guardarEntreno).mockResolvedValue(SUBIDO);

  expect(await subirPendientes()).toEqual([SUBIDO]);
  expect(pendientes()).toEqual([]);
});

test("un entreno que ya estaba subido se saca de la cola SIN volver a mandarlo", async () => {
  // El caso real: el POST se cometió en el servidor y se perdió la respuesta. Reintentar
  // a ciegas duplicaría el entreno y quemaría uno de los dos huecos con XP del día.
  encolar(SESION);
  vi.mocked(entrenos).mockResolvedValue([{ date: "2026-08-18T17:00:00.000000Z" }]);

  expect(await subirPendientes()).toEqual([]);
  expect(pendientes()).toEqual([]);
  expect(guardarEntreno).not.toHaveBeenCalled();
});

test("si al reintentar sigue sin haber red, la cola se queda como estaba", async () => {
  encolar(SESION);
  vi.mocked(entrenos).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );

  expect(await subirPendientes()).toEqual([]);
  // Lo que no se ha podido subir sigue ahí. Vaciar la cola porque la comprobación falló
  // sería tirar el entreno por no poder preguntar si ya estaba.
  expect(pendientes()).toHaveLength(1);
});
```

Y arriba del fichero, junto a los demás imports:

```ts
import { ErrorApi, entrenos, guardarEntreno, type EntrenoGuardado } from "./api";

vi.mock("./api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("./api")>()),
  entrenos: vi.fn(),
  guardarEntreno: vi.fn(),
}));

/** Lo que devuelve `POST /api/workouts`, recortado a lo que se usa aquí. */
const SUBIDO = {
  id: "9f1c2a3e-0000-4000-8000-000000000001",
  date: "2026-08-18T17:00:00.000000Z",
  new_records: [],
  system: { xp_gained: 80 },
} as unknown as EntrenoGuardado;
```

Y ampliar el import de `./borrador` con `entregar`, `subirPendientes` y `yaSubido`.

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- borrador`
Esperado: FALLA con `does not provide an export named 'entregar'`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Añadir al final de `web/src/borrador.ts`, y el import arriba del todo:

```ts
import { entrenos, guardarEntreno, type EntrenoGuardado } from "./api";
```

```ts
/** ¿Está ya en el servidor?
 *
 *  Compara instantes con `Date.parse` y no cadenas: nosotros mandamos
 *  `2026-08-18T17:00:00Z` y Laravel devuelve `2026-08-18T17:00:00.000000Z`. Es el mismo
 *  momento escrito de dos maneras, y compararlo como texto haría que el reintento no
 *  reconociera nunca su propio entreno. */
export function yaSubido(inicio: string, subidos: { date: string }[]): boolean {
  const instante = Date.parse(inicio);
  return subidos.some((entreno) => Date.parse(entreno.date) === instante);
}

/** Cuántos entrenos recientes se miran para deduplicar.
 *
 *  ponytail: cinco cubren el caso real —un pendiente es de hace minutos u horas—. Si
 *  alguien encolara seis sin cobertura y el sexto ya estuviera subido, se duplicaría. Se
 *  sube el techo pasando `date_from` con la fecha del pendiente más antiguo. */
const RECIENTES = 5;

/** Sube el entreno o lo encola. Tres respuestas, y las tres hay que mirarlas:
 *
 *  - **el entreno del servidor:** subido y confirmado.
 *  - **`"encolado"`:** no se pudo subir, pero está escrito en el móvil.
 *  - **`null`:** ⚠️ **no está a salvo en ningún sitio.** Ni subió, ni se pudo escribir
 *    —cuota llena, modo privado—.
 *
 *  Quien llama borra el borrador con las dos primeras y **no lo borra con `null`**: ese es
 *  el único camino de toda la aplicación por el que se puede perder un entreno, y lo
 *  cierra quien llama. Devolver `null` para «encolado» y para «no cabe» sería
 *  indistinguible desde fuera, y la pantalla borraría en los dos casos. */
export async function entregar(
  sesion: Sesion,
  duracionMinutos: number,
  notas: string | null,
): Promise<EntrenoGuardado | "encolado" | null> {
  try {
    return await guardarEntreno(aPayload(sesion, duracionMinutos, notas));
  } catch {
    return encolar(sesion) ? "encolado" : null;
  }
}

/** Vacía la cola. Devuelve lo que se subió, para que quien llame pueda enseñar la ventana
 *  del Sistema de un entreno que subió solo.
 *
 *  Sin retroceso exponencial: al otro lado hay un hosting compartido con un usuario, no
 *  un servicio que haya que proteger de una estampida. Se reintenta al recuperar la
 *  conexión, al abrir la aplicación y con un botón. */
export async function subirPendientes(): Promise<EntrenoGuardado[]> {
  // Los dos disparadores del docstring pueden coincidir: se abre la aplicación justo
  // cuando vuelve la red. Sin esta guarda las dos llamadas leerían la misma cola y
  // subirían el mismo entreno dos veces, que es el duplicado que todo lo demás de esta
  // función existe para evitar.
  if (subiendo) return [];
  subiendo = true;
  try {
    return await vaciarCola();
  } finally {
    subiendo = false;
  }
}

let subiendo = false;

async function vaciarCola(): Promise<EntrenoGuardado[]> {
  const cola = pendientes();
  if (cola.length === 0) return [];

  let recientes: { date: string }[];
  try {
    recientes = await entrenos({ per_page: RECIENTES });
  } catch {
    // Sin red no se puede ni preguntar. La cola se queda tal cual: vaciarla porque la
    // comprobación falló sería tirar el entreno por no poder preguntar si ya estaba.
    return [];
  }

  const subidos: EntrenoGuardado[] = [];

  for (const sesion of cola) {
    if (yaSubido(sesion.inicio, recientes)) {
      // Se cometió y se perdió la respuesta. Ya está donde tiene que estar.
      quitarDePendientes(sesion.inicio);
      continue;
    }
    try {
      // La duración ya se decidió al terminar; se recalcula desde la sesión guardada.
      subidos.push(await guardarEntreno(aPayload(sesion, duracionDeSesion(sesion), null)));
      quitarDePendientes(sesion.inicio);
    } catch {
      // Sigue sin haber manera. Se queda en la cola y se prueba en el próximo intento.
      break;
    }
  }

  return subidos;
}
```

⚠️ **`duracionDeSesion` todavía no existe.** La duración que el usuario confirmó al
terminar hay que guardarla en la sesión encolada, o al reintentar se inventaría otra. Se
resuelve en el paso 4.

- [ ] **Paso 4 · Guardar la duración confirmada en la sesión encolada**

La duración la decide el usuario en el paso de terminar (§5.3 del diseño) y **decide el
XP**: 50 puntos a partir de 15 minutos. Recalcularla al reintentar daría una cifra
distinta —el tiempo ha seguido corriendo— y el entreno puntuaría mal.

En `web/src/borrador.ts`, añadir el campo al tipo `Sesion`:

```ts
export type Sesion = {
  v: typeof VERSION;
  mode: Modo;
  nombre: string;
  inicio: string;
  exercises: Ejercicio[];
  actual: number;
  /** Los minutos que el usuario confirmó al terminar. Solo lo tienen las sesiones que ya
   *  están en la cola: al reintentar no se puede recalcular, porque el reloj ha seguido
   *  corriendo y la duración es lo que decide el XP. */
  duracion?: number;
  /** Las notas que escribió al terminar, por el mismo motivo. */
  notas?: string | null;
};
```

Sustituir el cuerpo del `for` de `subirPendientes` por:

```ts
    try {
      subidos.push(
        await guardarEntreno(aPayload(sesion, sesion.duracion ?? 0, sesion.notas ?? null)),
      );
      quitarDePendientes(sesion.inicio);
    } catch {
      break;
    }
```

Y en `entregar`, encolar con lo que se confirmó:

```ts
  } catch {
    return encolar({ ...sesion, duracion: duracionMinutos, notas }) ? "encolado" : null;
  }
```

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- borrador`
Esperado: PASAN los 29.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/borrador.ts web/src/borrador.test.ts
git commit -m "feat(entreno): reintentar la subida sin duplicar el entreno

Un POST puede haberse cometido en el servidor y perderse la respuesta —un
túnel, un cambio de red—. Reintentar a ciegas duplicaría el entreno y
quemaría uno de los dos huecos con XP del día, así que antes de reenviar se
piden los cinco últimos y se compara por fecha.

La comparación es de instantes y no de cadenas: nosotros mandamos
2026-08-18T17:00:00Z y Laravel devuelve 2026-08-18T17:00:00.000000Z. Como
texto no coinciden y el reintento no reconocería nunca su propio entreno.

Y si la comprobación falla por falta de red, la cola se queda como está.
Vaciarla porque no se ha podido preguntar sería tirar el entreno.

La duración confirmada viaja con la sesión encolada. Recalcularla al
reintentar daría otra cifra —el reloj ha seguido corriendo— y la duración es
lo que decide el XP: 50 puntos a partir de 15 minutos."
```

---

## Tarea 5 · `formato.ts`: los cálculos y textos con ramas

**Ficheros:**
- Modificar: `web/src/formato.ts`
- Modificar: `web/src/formato.test.ts`

**Interfaces:**
- Consume: los tipos `Ejercicio` y `Serie` de `borrador.ts` (tarea 1), y `RecordDelSistema`
  de `api.ts` (tarea 6) — **no `NuevoRecord`**: la interfaz lee siempre `system.records`.
- Produce: `volumenTotal(ejercicios): number`, `seriesHechas(ejercicios): number`,
  `duracionMinutos(inicio, ahora?): number`, `textoAntiguedad(inicio, ahora?): string`,
  `textoXpGanado(xpGanado, duracionMinutos): string`, `textoRecord(record): string`.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/formato.test.ts`:

```ts
import {
  duracionMinutos,
  seriesHechas,
  textoAntiguedad,
  textoRecord,
  textoXpGanado,
  volumenTotal,
} from "./formato";
import type { Ejercicio, Serie } from "./borrador";

function serie(campos: Partial<Serie> = {}): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: null, hecha: false,
    ...campos,
  };
}

const EJERCICIOS: Ejercicio[] = [
  {
    name: "Press banca",
    objetivo: null,
    sets: [
      serie({ weight_kg: 80, reps: 5, hecha: true }),
      serie({ weight_kg: 80, reps: 5, hecha: true }),
      serie({ weight_kg: 80, reps: 3 }),
    ],
  },
  {
    name: "Dominadas",
    objetivo: null,
    // Sin peso: no suma volumen, pero la serie está hecha igual.
    sets: [serie({ reps: 10, hecha: true })],
  },
];

test("el volumen es peso por repeticiones, sumado", () => {
  // El spec §6.4: Fuerza sube con los kilos movidos, peso × reps × series. Por eso
  // registrar el peso de cada serie importa: sin él la estadística no se mueve.
  expect(volumenTotal(EJERCICIOS)).toBe(80 * 5 + 80 * 5 + 80 * 3);
});

test("una serie a medio rellenar no suma volumen a medias", () => {
  // Ni 80 kg por «ninguna» repetición, ni repeticiones por «ningún» peso.
  expect(volumenTotal([{ name: "X", objetivo: null, sets: [serie({ weight_kg: 80 })] }])).toBe(0);
  expect(volumenTotal([{ name: "X", objetivo: null, sets: [serie({ reps: 5 })] }])).toBe(0);
});

test("sin ejercicios el volumen es cero y no NaN", () => {
  expect(volumenTotal([])).toBe(0);
});

test("las series hechas se cuentan aunque no lleven peso", () => {
  expect(seriesHechas(EJERCICIOS)).toBe(3);
});

test("la duración se redondea a minutos enteros", () => {
  const inicio = "2026-08-18T17:00:00Z";
  expect(duracionMinutos(inicio, Date.parse("2026-08-18T17:45:00Z"))).toBe(45);
  expect(duracionMinutos(inicio, Date.parse("2026-08-18T17:45:40Z"))).toBe(46);
});

test("un reloj que va hacia atrás no da una duración negativa", () => {
  // La API rechaza duration_minutes < 0, y un móvil que ajusta la hora por NTP a mitad de
  // sesión puede dejar el reloj detrás del inicio.
  expect(duracionMinutos("2026-08-18T17:00:00Z", Date.parse("2026-08-18T16:00:00Z"))).toBe(0);
});

test("la duración se corta en el máximo que acepta la API", () => {
  // duration_minutes va de 0 a 600. Un borrador de hace tres días daría 4.320 y el
  // servidor lo rechazaría con un 422 justo cuando el usuario intenta salvarlo.
  expect(duracionMinutos("2026-08-15T17:00:00Z", Date.parse("2026-08-18T17:00:00Z"))).toBe(600);
});

test("la antigüedad del borrador se dice en español llano", () => {
  const inicio = "2026-08-18T17:00:00Z";
  const en = (cuando: string) => textoAntiguedad(inicio, Date.parse(cuando));

  expect(en("2026-08-18T17:00:30Z")).toBe("de hace un momento");
  expect(en("2026-08-18T17:01:00Z")).toBe("de hace 1 minuto");
  expect(en("2026-08-18T17:12:00Z")).toBe("de hace 12 minutos");
  expect(en("2026-08-18T18:00:00Z")).toBe("de hace 1 hora");
  expect(en("2026-08-18T20:00:00Z")).toBe("de hace 3 horas");
  expect(en("2026-08-19T18:00:00Z")).toBe("de ayer");
  expect(en("2026-08-21T18:00:00Z")).toBe("de hace 3 días");
});

test("el XP ganado se dice tal cual cuando lo hay", () => {
  expect(textoXpGanado(80, 45)).toBe("+80 XP");
});

test("un entreno corto explica por qué no puntúa, sin parecer un error", () => {
  // El spec §6.2: 50 XP a partir de 15 minutos. Por debajo no da XP de entrenamiento.
  expect(textoXpGanado(0, 9)).toBe("Guardado. Un entreno suma XP a partir de los 15 minutos.");
});

test("el tercer entreno del día se explica sin decir por qué exactamente", () => {
  // El servidor puede haber devuelto 0 por el tope de dos entrenos con XP o por el de 300
  // XP diarios (spec §6.3), y desde aquí no se distinguen. Decir «ya has llegado al
  // máximo» es cierto en los dos casos; elegir uno sería adivinar.
  expect(textoXpGanado(0, 45)).toBe(
    "Guardado. Hoy ya has llegado al máximo de XP, así que este entreno no suma.",
  );
});

test("un récord se anuncia con su marca anterior, o dice que es la primera", () => {
  // La forma es la de `system.records`, que es la que llega en toda respuesta con bloque
  // `system` y la única que lee la interfaz.
  expect(textoRecord({ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }))
    .toBe("Press banca: 85 kg, antes 80 kg.");
  expect(textoRecord({ exercise: "Peso muerto", kind: "weight", value: 100, previous: null }))
    .toBe("Peso muerto: 100 kg, tu primera marca.");
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- formato`
Esperado: FALLA con `does not provide an export named 'volumenTotal'`.

- [ ] **Paso 3 · Escribir la implementación mínima**

Añadir al final de `web/src/formato.ts`:

```ts
import type { Ejercicio } from "./borrador";
import type { RecordDelSistema } from "./api";

/** Kilos movidos: peso × repeticiones, sumado. Es lo que alimenta la estadística de
 *  Fuerza en el servidor (spec §6.4). Una serie a medio rellenar no cuenta: 80 kg por
 *  «ninguna» repetición no son 80 kg movidos. */
export function volumenTotal(ejercicios: Ejercicio[]): number {
  let total = 0;
  for (const ejercicio of ejercicios) {
    for (const serie of ejercicio.sets) {
      if (serie.weight_kg != null && serie.reps != null) {
        total += serie.weight_kg * serie.reps;
      }
    }
  }
  return total;
}

export function seriesHechas(ejercicios: Ejercicio[]): number {
  return ejercicios.reduce(
    (total, ejercicio) => total + ejercicio.sets.filter((s) => s.hecha).length,
    0,
  );
}

/** El máximo que acepta `POST /api/workouts`. Un borrador de hace tres días daría 4.320 y
 *  el servidor lo rechazaría con un 422 justo cuando el usuario intenta salvarlo. */
const MAXIMO_MINUTOS = 600;

export function duracionMinutos(inicio: string, ahora: number = Date.now()): number {
  const minutos = Math.round((ahora - Date.parse(inicio)) / 60000);
  // Un móvil que ajusta la hora por NTP a mitad de sesión puede dejar el reloj detrás del
  // inicio, y la API rechaza los negativos.
  return Math.min(MAXIMO_MINUTOS, Math.max(0, minutos));
}

/** «de hace 12 minutos», «de ayer». Es lo que hace que retomar un borrador sea una
 *  decisión informada en vez de una sorpresa. */
export function textoAntiguedad(inicio: string, ahora: number = Date.now()): string {
  const segundos = Math.max(0, Math.floor((ahora - Date.parse(inicio)) / 1000));

  if (segundos < 60) return "de hace un momento";

  const minutos = Math.floor(segundos / 60);
  if (minutos < 60) return `de hace ${minutos} ${minutos === 1 ? "minuto" : "minutos"}`;

  const horas = Math.floor(minutos / 60);
  if (horas < 24) return `de hace ${horas} ${horas === 1 ? "hora" : "horas"}`;

  const dias = Math.floor(horas / 24);
  return dias === 1 ? "de ayer" : `de hace ${dias} días`;
}

/** El XP lo decide el servidor: aquí solo se escribe lo que ha dicho.
 *
 *  Un cero no es un error y no puede parecerlo. Puede venir de tres sitios: el entreno
 *  duró menos de 15 minutos, ya hay dos entrenos con XP hoy, o se llegó al tope de 300 XP
 *  diarios. La duración se conoce desde aquí; los otros dos no se distinguen, así que el
 *  texto dice lo que es cierto en ambos en vez de adivinar cuál fue. */
export function textoXpGanado(xpGanado: number, duracion: number): string {
  if (xpGanado > 0) return `+${xpGanado} XP`;
  if (duracion < 15) return "Guardado. Un entreno suma XP a partir de los 15 minutos.";
  return "Guardado. Hoy ya has llegado al máximo de XP, así que este entreno no suma.";
}

/** Recibe la forma del bloque `system`, que es la que lee toda la interfaz. `previous` a
 *  `null` es la primera marca de ese ejercicio. */
export function textoRecord(record: RecordDelSistema): string {
  return record.previous == null
    ? `${record.exercise}: ${record.value} kg, tu primera marca.`
    : `${record.exercise}: ${record.value} kg, antes ${record.previous} kg.`;
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- formato`
Esperado: PASAN todos, los 8 antiguos y los 12 nuevos.

- [ ] **Paso 5 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/formato.ts web/src/formato.test.ts
git commit -m "feat(entreno): los cálculos y textos del resumen, fuera de los componentes

Volumen, series hechas, duración, antigüedad del borrador y los textos con
ramas. Viven en formato.ts para poderse probar sin montar React, que es lo
que los deja probados de hecho.

Dos decisiones que el test fija:

La duración se corta en 600 minutos, que es el máximo que acepta la API. Un
borrador de hace tres días daría 4.320 y el servidor lo rechazaría con un
422 justo cuando el usuario intenta salvarlo.

Y un xp_gained de cero no puede parecer un error. Puede venir de tres
sitios: entreno de menos de 15 minutos, tope de dos entrenos con XP al día o
tope de 300 XP diarios. La duración se conoce desde el cliente; los otros
dos no se distinguen, así que el texto dice lo que es cierto en ambos en vez
de adivinar cuál fue."
```

---

## Tarea 6 · `api.ts`: entrenos, plantillas, ejercicios — y el CSRF de las escrituras sin cuerpo

**Ficheros:**
- Modificar: `web/src/api.ts`
- Modificar: `web/src/api.test.ts`
- Modificar: `web/src/pantallas/Hoy.test.tsx` (el fixture, ver paso 6)

**Interfaces:**
- Consume: `pedir`, `ErrorApi` y `DiaDeHoy`, ya en el fichero. `Modo` y `EntrenoPayload`
  de `borrador.ts` (tareas 1 y 3).
- Produce: los tipos `NuevoRecord`, `RecordDelSistema`, `BloqueSistema`, `EntrenoGuardado`, `Plantilla`,
  `EjercicioPlantilla`, `EntrenoSugerido`, `RecordPersonal`, `SerieAnterior`; y las
  funciones `entrenos`, `guardarEntreno`, `plantillas`, `crearPlantilla`,
  `editarPlantilla`, `borrarPlantilla`, `sugerenciasEjercicio`, `catalogoEjercicios`,
  `ultimaSesion`, `recordsPersonales`.

### 6.1 · El arreglo del CSRF

`pedir()` solo añade `X-XSRF-TOKEN` **cuando hay cuerpo** (`api.ts:70-73`). Toda escritura
sin cuerpo sale sin token y Laravel la rechaza con 419.

Ya está pasando: `salir()` es un `POST` sin cuerpo, y `App.tsx:46` se traga el error, así
que la sesión se cierra en el navegador y **sigue abierta en el servidor**. Con datos de
salud detrás, eso no es un detalle. `DELETE /api/templates/{id}` de la tarea 14 se comería
lo mismo.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/api.test.ts`:

```ts
test("una escritura sin cuerpo también manda el token CSRF", async () => {
  // Salir es un POST sin cuerpo. Sin esta cabecera Laravel contesta 419 y la sesión se
  // queda abierta en el servidor mientras el navegador cree haberla cerrado.
  document.cookie = "XSRF-TOKEN=un-token";
  const fetchFalso = vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ message: "ok" }), { status: 200 }),
  );
  vi.stubGlobal("fetch", fetchFalso);

  await salir();

  const cabeceras = fetchFalso.mock.calls[0][1].headers as Record<string, string>;
  expect(cabeceras["X-XSRF-TOKEN"]).toBe("un-token");
});

test("una lectura no arrastra el token ni el tipo de contenido", async () => {
  // Un GET no modifica nada y no necesita token. Mandar Content-Type en un GET sin cuerpo
  // además convierte la petición en una que algunos servidores rechazan.
  document.cookie = "XSRF-TOKEN=un-token";
  const fetchFalso = vi.fn().mockResolvedValue(
    new Response(JSON.stringify({ name: "Isra" }), { status: 200 }),
  );
  vi.stubGlobal("fetch", fetchFalso);

  await pedir("/user");

  const cabeceras = fetchFalso.mock.calls[0][1].headers as Record<string, string>;
  expect(cabeceras["X-XSRF-TOKEN"]).toBeUndefined();
  expect(cabeceras["Content-Type"]).toBeUndefined();
});
```

Comprobar que los imports del fichero incluyen `pedir` y `salir`, y añadirlos si no.

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- api`
Esperado: FALLA el primero, con `expected undefined to be 'un-token'`. El segundo pasa ya.

- [ ] **Paso 3 · Arreglarlo**

En `web/src/api.ts`, sustituir el bloque de `pedir`:

```ts
  if (cuerpo !== undefined) {
    cabeceras["Content-Type"] = "application/json";
    cabeceras["X-XSRF-TOKEN"] = await asegurarCsrf();
  }
```

por:

```ts
  // El token va en toda escritura, tenga cuerpo o no. Atarlo a que haya cuerpo dejaba sin
  // él a `salir()` —un POST sin cuerpo—, que Laravel rechazaba con 419: el navegador
  // creía haber cerrado la sesión y en el servidor seguía abierta. Y ahora también a
  // DELETE, que nunca lleva cuerpo.
  if (metodo !== "GET") {
    cabeceras["X-XSRF-TOKEN"] = await asegurarCsrf();
  }

  if (cuerpo !== undefined) {
    cabeceras["Content-Type"] = "application/json";
  }
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- api`
Esperado: PASAN los dos nuevos y los que ya había.

- [ ] **Paso 5 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/api.ts web/src/api.test.ts
git commit -m "fix(web): salir no cerraba la sesión en el servidor

pedir() solo añadía X-XSRF-TOKEN cuando la petición llevaba cuerpo, y
salir() es un POST sin cuerpo. Laravel lo rechazaba con 419, App.tsx se
tragaba el error a propósito —el usuario ya se iba— y la sesión se cerraba
en el navegador mientras seguía abierta en el servidor. Con datos de salud
detrás eso no es un detalle.

No se veía porque nada lo probaba y porque el único síntoma está en el otro
lado. Ahora el token va en toda escritura, tenga cuerpo o no, que además es
lo que necesitan los DELETE de plantillas de esta fase."
```

### 6.2 · Los tipos y las funciones del módulo

- [ ] **Paso 6 · Añadir los tipos y las funciones**

Añadir al final de `web/src/api.ts`:

```ts
// ── Entrenamiento ───────────────────────────────────────────────────────────

import type { EntrenoPayload, Modo } from "./borrador";

/** Un récord recién batido, en el campo de primer nivel `new_records`.
 *
 *  ⚠️ **Dentro del bloque `system` el mismo récord llega con otra forma**, la de
 *  `RecordDelSistema`: `SystemService::formatRecords()` lo traduce antes de meterlo ahí.
 *  Este tipo se declara por completitud de la respuesta; **la interfaz lee siempre
 *  `system.records`**, que es la que viene en toda respuesta con bloque `system`. */
export type NuevoRecord = {
  name: string;
  weight_kg: number;
  previous_pr: number | null;
  is_first: boolean;
};

/** El mismo récord tal y como sale dentro del bloque `system`. `previous` a `null`
 *  significa que era la primera marca: no hace falta un `is_first` aparte. */
export type RecordDelSistema = {
  exercise: string;
  kind: string;
  value: number;
  previous: number | null;
};

export type Logro = { key: string; name: string; rarity: string };

export type BloqueSistema = {
  xp_gained: number;
  level_up: { from: number; to: number } | null;
  rank_up: { from: string; to: string } | null;
  achievements_unlocked: Logro[];
  records: RecordDelSistema[];
  quests_completed: string[];
  progress: Progreso & {
    xp_total: number;
    longest_streak: number;
    stats: { strength: number; endurance: number; consistency: number; vitality: number };
  };
};

export type EntrenoGuardado = {
  id: string;
  date: string;
  mode: Modo;
  duration_minutes: number;
  notes: string | null;
  new_records: NuevoRecord[];
  system: BloqueSistema;
};

/** Lo justo que hace falta para deduplicar y para repetir el último entreno. */
export type EntrenoDelHistorial = {
  id: string;
  date: string;
  mode: Modo;
  duration_minutes: number;
  sets: {
    name: string;
    weight_kg: number | null;
    reps: number | null;
    rest_seconds: number | null;
    distance_m: number | null;
    time_seconds: number | null;
    style: string | null;
  }[];
};

/** `GET /api/workouts` devuelve un paginador de Laravel salvo con `all=true`. Aquí se
 *  desenvuelve, para que ninguna pantalla tenga que saber que existe `data`. */
export async function entrenos(
  filtros: { mode?: Modo; per_page?: number } = {},
): Promise<EntrenoDelHistorial[]> {
  const parametros = new URLSearchParams();
  if (filtros.mode) parametros.set("mode", filtros.mode);
  parametros.set("per_page", String(filtros.per_page ?? 25));

  const pagina = await pedir<{ data: EntrenoDelHistorial[] }>(`/workouts?${parametros}`);
  return pagina.data;
}

export function guardarEntreno(payload: EntrenoPayload) {
  return pedir<EntrenoGuardado>("/workouts", { metodo: "POST", cuerpo: payload });
}

// ── Plantillas ──────────────────────────────────────────────────────────────

export type EjercicioPlantilla = {
  id: number;
  name: string;
  sets: number | null;
  reps: number | null;
};

export type Plantilla = {
  id: string;
  /** null = del sistema. Editarla o borrarla da 403, así que esos botones no se enseñan. */
  user_id: string | null;
  name: string;
  description: string | null;
  level: string | null;
  mode: Modo | null;
  duration_minutes: number | null;
  exercises: EjercicioPlantilla[];
};

/** Lo único que la API acepta escribir. El `PUT` descarta peso, tiempo y distancia, y el
 *  `POST` ni siquiera los valida: montar campos para datos que desaparecen al primer
 *  guardado es enseñar al usuario algo que se le va a borrar solo. */
export type PlantillaEditable = {
  name: string;
  description?: string | null;
  level?: string | null;
  mode?: Modo;
  duration_minutes?: number | null;
  exercises: { name: string; sets?: number | null; reps?: number | null }[];
};

export function plantillas() {
  return pedir<Plantilla[]>("/templates");
}

export function crearPlantilla(datos: PlantillaEditable) {
  return pedir<{ message: string; template: Plantilla }>("/templates", {
    metodo: "POST",
    cuerpo: datos,
  });
}

export function editarPlantilla(id: string, datos: PlantillaEditable) {
  return pedir<Plantilla>(`/templates/${id}`, { metodo: "PUT", cuerpo: datos });
}

export function borrarPlantilla(id: string) {
  return pedir<{ message: string }>(`/templates/${id}`, { metodo: "DELETE" });
}

// ── Ejercicios ──────────────────────────────────────────────────────────────

export type RecordPersonal = {
  name: string;
  max_weight: number;
  reps: number | null;
  sets: number | null;
  date: string;
};

export type SerieAnterior = {
  weight_kg: number | null;
  reps: number | null;
  rpe: number | null;
  time_seconds: number | null;
  distance_m: number | null;
};

/** Solo mira el historial del usuario, así que con historial vacío no devuelve nada. Quien
 *  lo use tiene que unirlo al catálogo fijo (`catalogoEjercicios`) o el buscador aparece
 *  mudo el primer día. */
export function sugerenciasEjercicio(q: string) {
  return pedir<string[]>(`/exercises/suggestions?q=${encodeURIComponent(q)}`);
}

/** Los doce ejercicios escritos a mano en el controlador. Es el fondo de armario para
 *  quien todavía no tiene historial. */
export function catalogoEjercicios() {
  return pedir<{ name: string; category: string; muscle_group: string }[]>("/exercises");
}

export function ultimaSesion(nombre: string) {
  return pedir<SerieAnterior[]>(`/exercises/last-session?name=${encodeURIComponent(nombre)}`);
}

/** ⚠️ `max_weight` sale de una consulta cruda y puede llegar como cadena. Se normaliza
 *  aquí y no en cada pantalla: si una sola se olvida, «85» > «100» y el aviso de récord
 *  salta cuando no toca. */
export async function recordsPersonales(): Promise<RecordPersonal[]> {
  const crudos = await pedir<RecordPersonal[]>("/exercises/records");
  return crudos.map((r) => ({ ...r, max_weight: Number(r.max_weight) }));
}
```

- [ ] **Paso 7 · Declarar el entreno sugerido, que ya viene y no se estaba leyendo**

`GET /api/system/today` devuelve `suggested_workout` desde la fase 1.0, pero `DiaDeHoy` no
lo declara. En `web/src/api.ts`, añadir el tipo y el campo:

```ts
export type EntrenoSugerido = {
  /** Ya viene escrito en castellano: «Te faltan 2 entrenos para tu meta de esta semana». */
  reason: string;
  weekly_done: number;
  weekly_goal: number;
  template: Plantilla | null;
};
```

Y dentro de `DiaDeHoy`:

```ts
export type DiaDeHoy = {
  date: string;
  progress: Progreso;
  quests: Mision[];
  suggested_workout: EntrenoSugerido;
};
```

⚠️ **Esto rompe la compilación de `web/src/pantallas/Hoy.test.tsx`**: la constante `DIA`
deja de cumplir el tipo. Añadirle el campo:

```ts
  suggested_workout: {
    reason: "Te faltan 2 entrenos para tu meta de esta semana.",
    weekly_done: 1,
    weekly_goal: 3,
    template: null,
  },
```

- [ ] **Paso 8 · Comprobar que compila y que la suite sigue verde**

Ejecutar: `cd web && npm run build && npm test`
Esperado: `tsc -b` sin errores, y toda la suite en verde.

- [ ] **Paso 9 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/api.ts web/src/pantallas/Hoy.test.tsx
git commit -m "feat(entreno): la puerta a los endpoints de entrenamiento

Entrenos, plantillas y ejercicios, con sus tipos. Tres cosas que se han
comprobado contra el backend y que no coinciden con lo que uno esperaría:

El mismo récord llega dos veces y con dos formas: new_records, el campo de
primer nivel, es {name, weight_kg, previous_pr, is_first}; system.records
pasa antes por SystemService::formatRecords() y sale como {exercise, kind,
value, previous}. La interfaz lee siempre el del bloque system, que es el
que viene en toda respuesta que lo trae.

max_weight de /exercises/records sale de una consulta cruda y puede llegar
como cadena. Se normaliza en api.ts y no en cada pantalla: si una sola se
olvidara, «85» > «100» y el aviso de récord saltaría cuando no toca.

PUT /api/templates descarta todo lo que no sea nombre, series y reps, así
que el tipo editable no ofrece más: montar campos para datos que la API tira
al primer guardado es enseñar algo que se va a borrar solo.

Y suggested_workout ya venía de /system/today desde la fase 1.0 sin que
nadie lo leyera. Ahora está declarado."
```

---

## Tarea 7 · `componentes.tsx`: `Aviso`, `VentanaSistema` y el botón compacto

**Ficheros:**
- Modificar: `web/src/componentes.tsx`
- Modificar: `web/src/estilos.css`
- Crear: `web/src/componentes.test.tsx`

**Interfaces:**
- Consume: `BloqueSistema`, `RecordDelSistema` de `api.ts` (tarea 6); `textoRecord` de
  `formato.ts` (tarea 5).
- Produce: `<Aviso tono="rojo" | "ambar">`, `<VentanaSistema sistema={} alCerrar={} />`,
  y la propiedad `compacto` de `<Boton>`.

- [ ] **Paso 1 · Escribir el test que falla**

Crear `web/src/componentes.test.tsx`:

```tsx
/* La ventana del Sistema es la única recompensa visual de toda la aplicación, y la regla
   que la sostiene: aparece por cuatro cosas y por ninguna más. Si saltara por cualquier
   otra, dejaría de significar algo. */

import { render, screen } from "@testing-library/react";
import { expect, test } from "vitest";
import type { BloqueSistema } from "./api";
import { Aviso, VentanaSistema } from "./componentes";

function sistema(campos: Partial<BloqueSistema> = {}): BloqueSistema {
  return {
    xp_gained: 80,
    level_up: null,
    rank_up: null,
    achievements_unlocked: [],
    records: [],
    quests_completed: [],
    progress: {
      level: 4, rank: "E", xp_total: 1200, xp_into_level: 240, xp_for_next: 400,
      current_streak: 12, longest_streak: 20,
      stats: { strength: 3, endurance: 5, consistency: 8, vitality: 2 },
    },
    ...campos,
  };
}

/** Lo que leería un lector de pantalla: el texto sin nada marcado como dibujo. */
function loQueSeLee(nodo: HTMLElement): string {
  const copia = nodo.cloneNode(true) as HTMLElement;
  copia.querySelectorAll('[aria-hidden="true"]').forEach((n) => n.remove());
  return copia.textContent!.replace(/\s+/g, " ").trim();
}

test("sin nivel, rango, logro ni récord no hay ventana del Sistema", () => {
  // Solo XP. Si la ventana saltara también por esto, saltaría en todos los entrenos y
  // dejaría de ser un premio.
  const { container } = render(<VentanaSistema sistema={sistema()} alCerrar={() => {}} />);
  expect(container.innerHTML).toBe("");
});

test("subir de nivel la abre", () => {
  render(<VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={() => {}} />);
  expect(screen.getByRole("dialog")).toBeTruthy();
  expect(screen.getByText("Nivel 5")).toBeTruthy();
});

test("subir de rango la abre y lo dice por su nombre", () => {
  render(<VentanaSistema sistema={sistema({ rank_up: { from: "E", to: "D" } })} alCerrar={() => {}} />);
  expect(screen.getByText("Rango D")).toBeTruthy();
});

test("un logro la abre con su nombre, no con su clave", () => {
  render(
    <VentanaSistema
      sistema={sistema({ achievements_unlocked: [{ key: "workouts_50", name: "Medio Centenar", rarity: "epic" }] })}
      alCerrar={() => {}}
    />,
  );
  expect(screen.getByText("Medio Centenar")).toBeTruthy();
  expect(document.body.textContent).not.toContain("workouts_50");
});

test("un récord la abre con su marca anterior", () => {
  render(
    <VentanaSistema
      sistema={sistema({
        records: [{ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }],
      })}
      alCerrar={() => {}}
    />,
  );
  expect(screen.getByText("Press banca: 85 kg, antes 80 kg.")).toBeTruthy();
});

test("los ángulos de la ventana no se leen", () => {
  render(<VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={() => {}} />);

  // El adorno de esquinas es dibujo de terminal. Quien no sepa qué es una terminal tiene
  // que poder usar esto igual, y quien use lector de pantalla no puede oírlo.
  const leido = loQueSeLee(screen.getByRole("dialog"));
  expect(leido).not.toContain("┐");
  expect(leido).toContain("Nivel 5");
});

test("la ventana se lleva el foco al abrirse y lo devuelve al cerrarse", () => {
  // Quien navega con teclado tiene que acabar dentro. Sin esto el foco se queda en el
  // botón de detrás y hay que tabular a ciegas por media pantalla hasta dar con CERRAR,
  // y el lector de pantalla no anuncia una ventana en la que el foco nunca entra.
  const antes = document.createElement("button");
  document.body.append(antes);
  antes.focus();

  const { unmount } = render(
    <VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={() => {}} />,
  );

  expect(document.activeElement).toBe(screen.getByRole("button", { name: "CERRAR" }));

  unmount();
  expect(document.activeElement).toBe(antes);
});

test("Escape cierra la ventana y tabular no se sale de ella", () => {
  const alCerrar = vi.fn();
  render(
    <VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={alCerrar} />,
  );
  const ventana = screen.getByRole("dialog");

  fireEvent.keyDown(ventana, { key: "Escape" });
  expect(alCerrar).toHaveBeenCalledTimes(1);

  // Una ventana que dice `aria-modal` no puede dejar que el tabulador se escape detrás.
  const tab = fireEvent.keyDown(ventana, { key: "Tab" });
  expect(tab).toBe(false); // `preventDefault` la canceló
});

test("el aviso se anuncia solo cuando es urgente", () => {
  // Un aviso rojo es una pérdida de datos en curso: el lector de pantalla tiene que
  // interrumpir. Uno ámbar es informativo y puede esperar al turno.
  const { rerender } = render(<Aviso tono="rojo">No se puede guardar</Aviso>);
  expect(screen.getByRole("alert")).toBeTruthy();

  rerender(<Aviso tono="ambar">1 entreno pendiente de subir</Aviso>);
  expect(screen.getByRole("status")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- componentes`
Esperado: FALLA con `does not provide an export named 'VentanaSistema'`.

- [ ] **Paso 3 · Escribir los componentes**

Añadir al final de `web/src/componentes.tsx`, y ampliar los imports de arriba con
`import type { BloqueSistema } from "./api";` y `textoRecord` de `./formato`:

```tsx
/** Un aviso con dos urgencias.
 *
 *  `rojo` es una pérdida de datos en curso y va como `alert`: el lector de pantalla
 *  interrumpe lo que esté diciendo. `ambar` es informativo y va como `status`, que espera
 *  al turno. Usar `alert` para todo enseña a ignorarlo. */
export function Aviso({
  tono,
  children,
}: {
  tono: "rojo" | "ambar";
  children: ReactNode;
}) {
  return (
    <p className={`aviso ${tono}`} role={tono === "rojo" ? "alert" : "status"}>
      {children}
    </p>
  );
}

/** La ventana del Sistema. Spec §6.7: aparece sola y en exactamente cuatro momentos
 *  —subir de nivel, subir de rango, desbloquear un logro y batir un récord—. Si saltara
 *  por cualquier otra cosa dejaría de significar algo, y el cian dejaría de ser un premio.
 *
 *  Ganar XP a secas **no** la abre: pasa en todos los entrenos. */
export function VentanaSistema({
  sistema,
  alCerrar,
}: {
  sistema: BloqueSistema;
  alCerrar: () => void;
}) {
  const ventana = useRef<HTMLDivElement>(null);

  // Una ventana que se declara `aria-modal` tiene que cumplirlo. Sin esto, quien navega
  // con teclado se queda con el foco detrás de ella y tiene que tabular a ciegas hasta
  // tropezar con CERRAR, y quien usa lector de pantalla no oye que se haya abierto nada:
  // la única recompensa de toda la aplicación pasaría muda.
  //
  // No se usa `<dialog>` con `showModal()`, que haría todo esto solo, porque jsdom no lo
  // implementa y dejaría este comportamiento sin ningún test. Se revisa si algún día lo
  // implementa: sería borrar este efecto entero.
  useEffect(() => {
    if (!ventana.current) return;
    const anterior = document.activeElement as HTMLElement | null;
    ventana.current.querySelector<HTMLElement>("button")?.focus();
    return () => anterior?.focus();
  }, []);

  const motivos = [
    sistema.level_up && `Nivel ${sistema.level_up.to}`,
    sistema.rank_up && `Rango ${sistema.rank_up.to}`,
    ...sistema.achievements_unlocked.map((logro) => logro.name),
    ...sistema.records.map(textoRecord),
  ].filter((texto): texto is string => !!texto);

  if (motivos.length === 0) return null;

  return (
    <div
      ref={ventana}
      className="ventana-sistema"
      role="dialog"
      aria-modal="true"
      aria-label="El Sistema"
      onKeyDown={(evento) => {
        if (evento.key === "Escape") alCerrar();
        // Dentro solo hay un botón, así que tabular solo puede sacar de una ventana que
        // dice ser modal. El foco se queda aquí hasta que se cierre.
        if (evento.key === "Tab") evento.preventDefault();
      }}
    >
      {/* Las esquinas en ángulo son dibujo de terminal, como los corchetes. */}
      <span className="angulo superior" aria-hidden="true" />

      <p className="titulo-ventana">EL SISTEMA</p>
      <ul className="motivos">
        {motivos.map((texto) => (
          <li key={texto}>{texto}</li>
        ))}
      </ul>

      <span className="angulo inferior" aria-hidden="true" />

      <Boton type="button" onClick={alCerrar}>
        CERRAR
      </Boton>
    </div>
  );
}
```

Y cambiar `Boton` para que admita la variante compacta. El botón de ahora ocupa el ancho
entero y lleva `margin-top: 1.75rem`, que es lo que quieren los formularios de entrar y
registrarse pero no lo que quiere una barra de acciones:

```tsx
export function Boton({
  children,
  compacto = false,
  ...resto
}: {
  children: ReactNode;
  /** Para las barras de acciones, donde caben varios en una fila. */
  compacto?: boolean;
} & ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button className={compacto ? "boton compacto" : "boton"} {...resto}>
      <span aria-hidden="true">[ </span>
      {children}
      <span aria-hidden="true"> ]</span>
    </button>
  );
}
```

- [ ] **Paso 4 · Añadir el CSS**

⚠️ Sin ningún `--nombre: #rrggbb` nuevo: `estilos.test.ts` compara la lista de colores con
`toEqual` y una propiedad nueva rompe ese test.

Añadir al final de `web/src/estilos.css`:

```css
/* El cian es exclusivo de esta ventana. Si aparece en la interfaz normal, el momento de
   recompensa se desactiva (spec §7). */
.ventana-sistema {
  position: relative;
  margin: 1.5rem 0;
  padding: 1.25rem 1rem;
  border: 1px solid var(--cian);
  background: var(--superficie);
  box-shadow: 0 0 12px rgb(34 211 238 / 25%);
}

/* Las esquinas en ángulo del spec §7, con bordes y no con caracteres: así no hay nada que
   un lector de pantalla pueda leer ni que se desalinee al cambiar el tamaño de fuente. */
.ventana-sistema .angulo {
  position: absolute;
  width: 12px;
  height: 12px;
  border: 2px solid var(--cian);
}

.ventana-sistema .angulo.superior {
  top: -1px;
  left: -1px;
  border-right: none;
  border-bottom: none;
}

.ventana-sistema .angulo.inferior {
  right: -1px;
  bottom: -1px;
  border-top: none;
  border-left: none;
}

.titulo-ventana {
  color: var(--cian);
  font-size: var(--etiqueta);
  letter-spacing: 0.2em;
  margin-bottom: 0.75rem;
}

.ventana-sistema .motivos {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.ventana-sistema .motivos li {
  color: var(--texto);
  font-size: var(--seccion);
}

.aviso.ambar {
  color: var(--ambar);
}

.boton.compacto {
  width: auto;
  margin-top: 0;
  padding: 0 0.75rem;
}
```

El `.aviso` que ya existe se queda en rojo por defecto, así que las pantallas de la fase
1.1 no cambian de aspecto.

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test`
Esperado: PASAN los 7 nuevos, y `estilos.test.ts` sigue verde (si se ha colado un color
nuevo, falla justo ahí).

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/componentes.tsx web/src/componentes.test.tsx web/src/estilos.css
git commit -m "feat(diseño): la ventana del Sistema, el aviso y el botón compacto

La ventana se abre por cuatro cosas y por ninguna más: subir de nivel, subir
de rango, desbloquear un logro y batir un récord (spec §6.7). Ganar XP a
secas no la abre, porque pasa en todos los entrenos y entonces dejaría de
ser un premio.

Las esquinas en ángulo se dibujan con bordes y no con caracteres: así no hay
nada que un lector de pantalla pueda leer ni que se desalinee cuando el
usuario sube el tamaño de fuente del sistema.

El aviso tiene dos urgencias. Rojo es una pérdida de datos en curso y va
como alert, que interrumpe al lector de pantalla; ámbar es informativo y va
como status, que espera al turno. Usar alert para todo enseña a ignorarlo."
```

---

## Tarea 8 · `componentes.tsx`: la fila de serie

**Ficheros:**
- Modificar: `web/src/componentes.tsx`
- Modificar: `web/src/estilos.css`
- Modificar: `web/src/componentes.test.tsx`

**Interfaces:**
- Consume: `Serie`, `Modo` de `borrador.ts` (tarea 1).
- Produce: `<FilaSerie numero indice serie modo anterior alCambiar alMarcar />`, con
  `alCambiar(campo: keyof Serie, valor: number | string | null)` y `alMarcar()`.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/componentes.test.tsx`:

```tsx
import { fireEvent } from "@testing-library/react";
import { FilaSerie } from "./componentes";
import type { Serie } from "./borrador";

function serie(campos: Partial<Serie> = {}): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: null, hecha: false,
    ...campos,
  };
}

const NADA = { alCambiar: () => {}, alMarcar: () => {} };

test("una serie hecha se oye con su estado, no con los corchetes", () => {
  render(
    <table><tbody>
      <FilaSerie numero={3} serie={serie({ weight_kg: 80, reps: 5, hecha: true })} modo="gym" anterior={null} {...NADA} />
    </tbody></table>,
  );

  // El `[✓]` es decoración. Se oye «serie 3, hecha», que es lo que la regla rectora pide.
  expect(screen.getByRole("button").getAttribute("aria-label")).toBe("Serie 3, hecha");
});

test("una serie pendiente dice que lo está", () => {
  render(
    <table><tbody>
      <FilaSerie numero={1} serie={serie()} modo="gym" anterior={null} {...NADA} />
    </tbody></table>,
  );
  expect(screen.getByRole("button").getAttribute("aria-label")).toBe("Serie 1, pendiente");
});

test("en calistenia la columna de peso se llama lastre", () => {
  render(
    <table><tbody>
      <FilaSerie numero={1} serie={serie()} modo="calisthenics" anterior={null} {...NADA} />
    </tbody></table>,
  );
  expect(screen.getByLabelText("Lastre en kilos, serie 1")).toBeTruthy();
});

test("en natación se piden distancia y tiempo, no kilos", () => {
  render(
    <table><tbody>
      <FilaSerie numero={1} serie={serie()} modo="swimming" anterior={null} {...NADA} />
    </tbody></table>,
  );

  expect(screen.getByLabelText("Distancia en metros, serie 1")).toBeTruthy();
  expect(screen.getByLabelText("Tiempo en segundos, serie 1")).toBeTruthy();
  expect(screen.queryByLabelText(/kilos/)).toBe(null);
});

test("un campo vaciado vuelve a null, no a cero", () => {
  // Un cero es un dato: «he levantado 0 kg». Vaciar el campo es no haberlo apuntado, y la
  // diferencia decide si la serie está vacía y no se manda.
  const cambios: [string, unknown][] = [];
  render(
    <table><tbody>
      <FilaSerie
        numero={1}
        serie={serie({ weight_kg: 80 })}
        modo="gym"
        anterior={null}
        alCambiar={(campo, valor) => cambios.push([campo, valor])}
        alMarcar={() => {}}
      />
    </tbody></table>,
  );

  fireEvent.change(screen.getByLabelText("Peso en kilos, serie 1"), { target: { value: "" } });
  expect(cambios).toEqual([["weight_kg", null]]);
});

test("lo que se levantó la última vez se enseña como pista", () => {
  render(
    <table><tbody>
      <FilaSerie numero={1} serie={serie()} modo="gym" anterior={{ weight_kg: 75, reps: 5, rpe: null, time_seconds: null, distance_m: null }} {...NADA} />
    </tbody></table>,
  );
  expect(screen.getByText("75×5")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- componentes`
Esperado: FALLA con `does not provide an export named 'FilaSerie'`.

- [ ] **Paso 3 · Escribir el componente**

Añadir al final de `web/src/componentes.tsx`, con `import type { Modo, Serie } from "./borrador";`
y `import type { SerieAnterior } from "./api";` arriba:

```tsx
/** Un número que puede no estar. Vaciar el campo devuelve null y no 0: un cero es un dato
 *  —«he levantado 0 kg»— y no haberlo apuntado es otra cosa. De esa diferencia depende que
 *  la serie se considere vacía y no se mande. */
function aNumero(texto: string): number | null {
  if (texto.trim() === "") return null;
  const n = Number(texto);
  return Number.isFinite(n) ? n : null;
}

/** Una serie. Se usa de pie, con una mano, con la pantalla sudada y con prisa: los
 *  objetivos táctiles son de 48 px y no hay ningún gesto, solo pulsar y teclear. */
export function FilaSerie({
  numero,
  serie,
  modo,
  anterior,
  alCambiar,
  alMarcar,
}: {
  numero: number;
  serie: Serie;
  modo: Modo;
  /** Lo que levantó la última vez en esta misma serie, si se sabe. */
  anterior: SerieAnterior | null;
  alCambiar: (campo: keyof Serie, valor: number | string | null) => void;
  alMarcar: () => void;
}) {
  // En calistenia el peso es el lastre que se cuelga, no el del cuerpo.
  const etiquetaPeso = modo === "calisthenics" ? "Lastre" : "Peso";

  const numerico = (
    campo: keyof Serie,
    etiqueta: string,
    paso: "0.5" | "1",
  ) => (
    <td>
      <input
        type="number"
        step={paso}
        min="0"
        // El teclado numérico del móvil sale solo. Decimal donde hay medios kilos y
        // medios metros; entero donde no tiene sentido una coma.
        inputMode={paso === "0.5" ? "decimal" : "numeric"}
        aria-label={`${etiqueta}, serie ${numero}`}
        value={(serie[campo] as number | null) ?? ""}
        onChange={(e) => alCambiar(campo, aNumero(e.target.value))}
      />
    </td>
  );

  return (
    <tr className={serie.hecha ? "fila-serie hecha" : "fila-serie"}>
      <td className="numero" aria-hidden="true">
        {numero}
      </td>

      {modo === "swimming" ? (
        <>
          {numerico("distance_m", "Distancia en metros", "0.5")}
          {numerico("time_seconds", "Tiempo en segundos", "1")}
          <td>
            <input
              type="text"
              aria-label={`Estilo, serie ${numero}`}
              value={serie.style ?? ""}
              onChange={(e) => alCambiar("style", e.target.value || null)}
            />
          </td>
        </>
      ) : (
        <>
          <td className="anterior" aria-hidden="true">
            {anterior?.weight_kg != null && anterior.reps != null
              ? `${anterior.weight_kg}×${anterior.reps}`
              : ""}
          </td>
          {numerico("weight_kg", `${etiquetaPeso} en kilos`, "0.5")}
          {numerico("reps", "Repeticiones", "1")}
          {numerico("rpe", "Esfuerzo del 1 al 10", "1")}
        </>
      )}

      <td>
        <button
          type="button"
          className="marca-serie"
          // El `[✓]` es dibujo: lo que se oye es el estado, con estas palabras.
          aria-label={`Serie ${numero}, ${serie.hecha ? "hecha" : "pendiente"}`}
          aria-pressed={serie.hecha}
          onClick={alMarcar}
        >
          <span aria-hidden="true">[{serie.hecha ? "✓" : " "}]</span>
        </button>
      </td>
    </tr>
  );
}
```

- [ ] **Paso 4 · Añadir el CSS**

Añadir al final de `web/src/estilos.css`:

```css
.tabla-series {
  width: 100%;
  border-collapse: collapse;
  margin-top: 0.75rem;
}

.tabla-series th {
  color: var(--apagado);
  font-size: var(--etiqueta);
  font-weight: normal;
  letter-spacing: 0.1em;
  text-align: left;
  padding-bottom: 0.35rem;
  border-bottom: 1px solid var(--lineas);
}

.fila-serie td {
  padding: 0.25rem 0.2rem;
  border-bottom: 1px solid var(--lineas);
}

.fila-serie .numero,
.fila-serie .anterior {
  color: var(--apagado);
  font-size: var(--nota);
  white-space: nowrap;
}

.fila-serie input {
  width: 100%;
  min-width: 3.5ch;
  min-height: 48px;
  background: var(--superficie);
  color: var(--texto);
  border: 1px solid var(--lineas);
  font-family: inherit;
  font-size: var(--cuerpo);
  text-align: center;
}

.fila-serie input:focus {
  outline: none;
  border-color: var(--ambar);
}

.marca-serie {
  min-width: 48px;
  min-height: 48px;
  background: transparent;
  border: none;
  color: var(--apagado);
  font-family: inherit;
  font-size: var(--seccion);
  cursor: pointer;
}

.fila-serie.hecha .marca-serie {
  color: var(--verde);
}

.marca-serie:focus-visible {
  outline: 2px solid var(--ambar);
  outline-offset: -2px;
}
```

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test`
Esperado: toda la suite en verde.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/componentes.tsx web/src/componentes.test.tsx web/src/estilos.css
git commit -m "feat(entreno): la fila de serie, con sus dos disposiciones

Fuerza pide kilos, repeticiones y esfuerzo; natación pide distancia, tiempo
y estilo. En calistenia la columna de peso se titula «Lastre», que es lo que
de verdad se apunta ahí.

Vaciar un campo devuelve null y no cero. Un cero es un dato —«he levantado 0
kg»— y no haberlo apuntado es otra cosa: de esa diferencia depende que la
serie se considere vacía y no se mande al servidor.

El `[✓]` es dibujo y va aria-hidden; lo que se oye es «serie 3, hecha». Los
objetivos táctiles son de 48 px porque esta pantalla se usa de pie, con una
mano y con la pantalla sudada."
```

---

## Tarea 9 · `Sesion.tsx`: el estado que se escribe en cada cambio

Es la pantalla que de verdad importa. Se construye en tres tareas: primero el estado y la
persistencia, después el descanso y el aviso de récord, y por último terminar.

**Ficheros:**
- Crear: `web/src/pantallas/Sesion.tsx`
- Crear: `web/src/pantallas/Sesion.test.tsx`

**Interfaces:**
- Consume: `borrar`, `descansoPorDefecto`, `guardar`, `leer`, tipos `Sesion`/`Serie` de
  `borrador.ts`; `FilaSerie`, `Aviso`, `Boton`, `Comentario`, `TituloPantalla` de
  `componentes.tsx`; `seriesHechas` de `formato.ts`.
- Produce: `export default function Sesion()`, montada en la ruta `/entrenar/sesion`.

- [ ] **Paso 1 · Escribir el test que falla**

Crear `web/src/pantallas/Sesion.test.tsx`:

```tsx
/* La sesión activa es lo único irrecuperable de la aplicación. Estos tests comprueban lo
   que la fase pone como criterio: que matar la app a mitad no pierda nada.

   Lo que NO demuestran, y hay que probar en el móvil: que funcione de verdad en modo
   avión y que sobreviva a que el sistema mate el proceso. jsdom no tiene ni red que
   cortar ni proceso que matar. */

import { render, screen, fireEvent } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { guardar, leer, type Sesion as TipoSesion } from "../borrador";
import Sesion from "./Sesion";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  recordsPersonales: vi.fn().mockResolvedValue([]),
  ultimaSesion: vi.fn().mockResolvedValue([]),
}));

const EN_CURSO: TipoSesion = {
  v: 1,
  mode: "gym",
  nombre: "Torso pesado",
  inicio: "2026-08-18T17:00:00Z",
  actual: 0,
  exercises: [
    {
      name: "Press banca",
      objetivo: { sets: 4, reps: 5 },
      sets: [
        { weight_kg: null, reps: null, rpe: null, distance_m: null, time_seconds: null, style: null, rest_seconds: 90, hecha: false },
      ],
    },
  ],
};

// Con `StrictMode`, igual que `main.tsx`. Aquí no es ceremonia: React invoca dos veces las
// funciones actualizadoras y monta y desmonta los efectos una vez de más, que es lo que
// caza un cronómetro sin limpiar o una escritura metida donde no toca. Esta pantalla la
// siguen tocando las tareas 10, 11 y 14, y sin esto el test no se parecería a cómo se monta
// la aplicación de verdad.
const pintar = () =>
  render(
    <StrictMode>
      <MemoryRouter>
        <Sesion />
      </MemoryRouter>
    </StrictMode>,
  );

beforeEach(() => {
  localStorage.clear();
  guardar(EN_CURSO);
});

test("apuntar un peso lo escribe en disco en el momento, no al salir", async () => {
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), {
    target: { value: "80" },
  });

  // Sin esperar a nada: si el sistema mata la pestaña ahora mismo, el 80 ya está escrito.
  expect(leer()!.exercises[0].sets[0].weight_kg).toBe(80);
});

test("marcar una serie también se escribe en el momento", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));

  expect(leer()!.exercises[0].sets[0].hecha).toBe(true);
});

test("volver a montar la pantalla recupera el estado exacto", async () => {
  const { unmount } = pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "80" } });
  fireEvent.change(screen.getByLabelText("Repeticiones, serie 1"), { target: { value: "5" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  // Matar la app y volver a abrirla.
  unmount();
  pintar();

  expect((await screen.findByLabelText("Peso en kilos, serie 1") as HTMLInputElement).value).toBe("80");
  expect((screen.getByLabelText("Repeticiones, serie 1") as HTMLInputElement).value).toBe("5");
  expect(screen.getByRole("button", { name: "Serie 1, hecha" })).toBeTruthy();
});

test("añadir una serie hereda el descanso de la anterior", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "AÑADIR SERIE" }));

  const guardado = leer()!;
  expect(guardado.exercises[0].sets).toHaveLength(2);
  expect(guardado.exercises[0].sets[1].rest_seconds).toBe(90);
});

test("si no se puede guardar, se dice en rojo y no en silencio", async () => {
  // FitLoop se tragaba este fallo. En silencio significa que el usuario cree que su
  // entreno está a salvo cuando no lo está, y es el único caso donde no avisar es peor
  // que cualquier cosa que se pueda enseñar.
  pintar();
  vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
    throw new DOMException("QuotaExceededError");
  });

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "80" } });

  expect((await screen.findByRole("alert")).textContent).toContain("no cierres");
});

test("el avance se dice con palabras, no solo con la barra", async () => {
  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));

  expect(screen.getByText("1 serie hecha")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: FALLA, no existe `./Sesion`.

- [ ] **Paso 3 · Escribir la pantalla**

Crear `web/src/pantallas/Sesion.tsx`:

```tsx
/* La sesión activa. Se usa de pie, con una mano, con la pantalla sudada y con prisa entre
   series, así que todo son objetivos grandes y no hay ni un gesto.

   La regla de este fichero: **cada cambio escribe en disco**. No hay rebote, no se espera
   a salir y no hay un «guardar». `localStorage.setItem` es síncrono, así que cuando la
   función vuelve el dato está a salvo. */

import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { descansoPorDefecto, guardar, leer, type Serie, type Sesion as TipoSesion } from "../borrador";
import { Aviso, Boton, Comentario, FilaSerie, TituloPantalla } from "../componentes";
import { seriesHechas } from "../formato";

/** Las columnas de cada disposición. La de fuerza vale para gimnasio, casa y calistenia;
    en calistenia el peso se titula «Lastre», que es lo que de verdad se apunta. */
function cabeceras(modo: TipoSesion["mode"]): string[] {
  if (modo === "swimming") return ["Serie", "Distancia", "Tiempo", "Estilo", "Hecha"];
  return ["Serie", "Anterior", modo === "calisthenics" ? "Lastre" : "Kg", "Reps", "RPE", "Hecha"];
}

function serieNueva(descanso: number | null): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: descanso, hecha: false,
  };
}

export default function Sesion() {
  const navegar = useNavigate();
  const [sesion, setSesion] = useState<TipoSesion | null>(() => leer());
  const [sinSitio, setSinSitio] = useState(false);

  // Sin borrador no hay nada que enseñar: alguien ha llegado aquí por la URL.
  useEffect(() => {
    if (!sesion) navegar("/entrenar", { replace: true });
  }, [sesion, navegar]);

  if (!sesion) return null;

  /** El único camino por el que cambia el estado. Escribe primero y pinta después: si el
   *  disco dice que no, el usuario se entera en la misma pulsación y no un repintado más
   *  tarde.
   *
   *  Se calcula fuera de `setSesion` y no dentro de una función actualizadora. React exige
   *  que esas funciones sean puras y en desarrollo las invoca dos veces para cazar
   *  justamente esto: escribir en disco y llamar a otro `setState` desde dentro. Aquí
   *  `sesion` no puede ser nula —arriba hay un `return null`— y cada pulsación es su
   *  propio evento, así que no hace falta la forma con función. */
  function actualizar(cambio: (anterior: TipoSesion) => TipoSesion) {
    const siguiente = cambio(sesion!);
    setSinSitio(!guardar(siguiente));
    setSesion(siguiente);
  }

  function cambiarSerie(indice: number, campo: keyof Serie, valor: number | string | null) {
    actualizar((s) => ({
      ...s,
      exercises: s.exercises.map((ejercicio, i) =>
        i !== s.actual
          ? ejercicio
          : {
              ...ejercicio,
              sets: ejercicio.sets.map((serie, j) =>
                j === indice ? { ...serie, [campo]: valor } : serie,
              ),
            },
      ),
    }));
  }

  function marcarSerie(indice: number) {
    actualizar((s) => ({
      ...s,
      exercises: s.exercises.map((ejercicio, i) =>
        i !== s.actual
          ? ejercicio
          : {
              ...ejercicio,
              sets: ejercicio.sets.map((serie, j) =>
                j === indice ? { ...serie, hecha: !serie.hecha } : serie,
              ),
            },
      ),
    }));
  }

  function anadirSerie() {
    actualizar((s) => ({
      ...s,
      exercises: s.exercises.map((ejercicio, i) => {
        if (i !== s.actual) return ejercicio;
        // El descanso se hereda de la última serie: quien lo cambió una vez lo quiere
        // igual en la siguiente, y volver a teclearlo entre series es justo lo que esta
        // pantalla no puede pedir.
        const ultima = ejercicio.sets.at(-1);
        return { ...ejercicio, sets: [...ejercicio.sets, serieNueva(ultima?.rest_seconds ?? descansoPorDefecto())] };
      }),
    }));
  }

  const ejercicio = sesion.exercises[sesion.actual];
  const hechas = seriesHechas(sesion.exercises);

  return (
    <>
      <TituloPantalla pantalla="entreno" />

      {sinSitio && (
        <Aviso tono="rojo">
          Este navegador no está guardando el entreno: no cierres la aplicación hasta
          terminarlo y subirlo.
        </Aviso>
      )}

      <h2 className="nombre-ejercicio">{ejercicio.name}</h2>
      <Comentario>
        ejercicio {sesion.actual + 1} de {sesion.exercises.length}
        {ejercicio.objetivo?.sets ? ` · objetivo ${ejercicio.objetivo.sets} series` : ""}
        {ejercicio.objetivo?.reps ? ` de ${ejercicio.objetivo.reps}` : ""}
      </Comentario>

      <table className="tabla-series">
        <thead>
          <tr>
            {cabeceras(sesion.mode).map((titulo) => (
              <th key={titulo}>{titulo}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {ejercicio.sets.map((serie, indice) => (
            <FilaSerie
              key={indice}
              numero={indice + 1}
              serie={serie}
              modo={sesion.mode}
              anterior={null}
              alCambiar={(campo, valor) => cambiarSerie(indice, campo, valor)}
              alMarcar={() => marcarSerie(indice)}
            />
          ))}
        </tbody>
      </table>

      {/* El avance con palabras y no solo con la barra: quien use lector de pantalla
          necesita oírlo, y quien no, agradece la cifra. */}
      <Comentario>{hechas === 1 ? "1 serie hecha" : `${hechas} series hechas`}</Comentario>

      <div className="acciones">
        <Boton type="button" compacto onClick={anadirSerie}>
          AÑADIR SERIE
        </Boton>
      </div>
    </>
  );
}
```

- [ ] **Paso 4 · Añadir el CSS**

Añadir al final de `web/src/estilos.css`:

```css
.nombre-ejercicio {
  font-size: var(--titulo);
  color: var(--texto);
  margin-top: 1.25rem;
}

.acciones {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1.25rem;
}
```

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: PASAN los 6.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/pantallas/Sesion.tsx web/src/pantallas/Sesion.test.tsx web/src/estilos.css
git commit -m "feat(entreno): la sesión activa escribe en disco en cada cambio

Sin rebote, sin esperar a salir y sin un botón de guardar. setItem es
síncrono, así que cuando la función vuelve el dato está a salvo: si el
sistema mata la pestaña en ese instante, lo tecleado ya está escrito.

Todo el estado pasa por una sola función, que escribe primero y pinta
después. Si el disco dice que no —cuota llena, modo privado— el usuario lo
ve en la misma pulsación, en rojo y diciéndole que no cierre la aplicación.
FitLoop se tragaba ese fallo, que es la forma de que alguien crea que su
entreno está a salvo cuando no lo está.

El descanso de una serie nueva se hereda de la anterior: volver a teclearlo
entre series es justo lo que esta pantalla no puede pedir."
```

---

## Tarea 10 · `Sesion.tsx`: el descanso, el ejercicio anterior y el aviso de récord

**Ficheros:**
- Modificar: `web/src/pantallas/Sesion.tsx`
- Modificar: `web/src/pantallas/Sesion.test.tsx`
- Modificar: `web/src/estilos.css`

**Interfaces:**
- Consume: `recordsPersonales`, `ultimaSesion` de `api.ts` (tarea 6);
  `guardarDescansoPorDefecto` de `borrador.ts` (tarea 2); `textoRecord` de `formato.ts`.
- Produce: nada nuevo hacia fuera.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/pantallas/Sesion.test.tsx`:

```tsx
test("marcar una serie arranca la cuenta atrás del descanso", async () => {
  vi.useFakeTimers({ shouldAdvanceTime: true });
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));
  expect(screen.getByText("Descanso 1:30")).toBeTruthy();

  await vi.advanceTimersByTimeAsync(10_000);
  expect(screen.getByText("Descanso 1:20")).toBeTruthy();

  vi.useRealTimers();
});

test("el descanso se puede saltar", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));
  fireEvent.click(screen.getByRole("button", { name: "SALTAR DESCANSO" }));

  expect(screen.queryByText(/^Descanso /)).toBe(null);
});

test("desmarcar una serie no arranca ningún descanso", async () => {
  pintar();
  const marca = await screen.findByRole("button", { name: "Serie 1, pendiente" });

  fireEvent.click(marca);                                                    // hecha
  fireEvent.click(screen.getByRole("button", { name: "SALTAR DESCANSO" }));
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, hecha" }));   // corregir un error

  expect(screen.queryByText(/^Descanso /)).toBe(null);
});

test("un récord se anuncia en el momento de batirlo", async () => {
  vi.mocked(recordsPersonales).mockResolvedValue([
    { name: "Press banca", max_weight: 80, reps: 5, date: "2026-08-01T10:00:00.000000Z" },
  ]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "85" } });
  fireEvent.change(screen.getByLabelText("Repeticiones, serie 1"), { target: { value: "3" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  expect(await screen.findByText(/Press banca: 85 kg, antes 80 kg/)).toBeTruthy();
});

test("igualar la marca no es un récord", async () => {
  vi.mocked(recordsPersonales).mockResolvedValue([
    { name: "Press banca", max_weight: 80, reps: 5, date: "2026-08-01T10:00:00.000000Z" },
  ]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "80" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  expect(screen.queryByText(/Press banca: 80 kg/)).toBe(null);
});

test("sin conexión el aviso de récord simplemente no sale, y nada se rompe", async () => {
  // Es el caso normal de esta pantalla: un sótano de gimnasio. El récord de verdad lo
  // decide el servidor al guardar, así que aquí no hay nada que reintentar ni que avisar.
  vi.mocked(recordsPersonales).mockRejectedValue(new Error("sin red"));
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "85" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  expect(screen.queryByRole("alert")).toBe(null);
  expect(screen.getByRole("button", { name: "Serie 1, hecha" })).toBeTruthy();
});
```

Y ampliar los imports del fichero con `recordsPersonales` de `../api`.

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: FALLAN los 6 nuevos; los 6 de la tarea 9 siguen pasando.

- [ ] **Paso 3 · Añadir el descanso**

En `web/src/pantallas/Sesion.tsx`, añadir dentro del componente:

```tsx
  /** Segundos que quedan de descanso, o null si no hay ninguno en marcha. */
  const [descanso, setDescanso] = useState<number | null>(null);

  // La cuenta atrás. Un intervalo y nada más: el cronómetro de la fase 1.5 es otra cosa.
  // Sin sonido ni vibración, que piden permisos y no funcionan igual en cada navegador.
  // ponytail: si molesta no verlo, `navigator.vibrate(200)` en el cero es una línea.
  useEffect(() => {
    if (descanso === null) return;
    if (descanso <= 0) {
      setDescanso(null);
      return;
    }
    const id = setTimeout(() => setDescanso((s) => (s === null ? null : s - 1)), 1000);
    return () => clearTimeout(id);
  }, [descanso]);
```

Y en `marcarSerie`, arrancarlo **solo al marcar**, no al desmarcar:

```tsx
  function marcarSerie(indice: number) {
    const serie = sesion!.exercises[sesion!.actual].sets[indice];

    // Desmarcar es corregir un error, no terminar una serie: ahí no toca descansar.
    if (!serie.hecha) {
      setDescanso(serie.rest_seconds ?? descansoPorDefecto());
    }

    actualizar((s) => ({ /* …igual que en la tarea 9… */ }));
  }
```

Y pintarlo justo encima de la tabla:

```tsx
      {descanso !== null && (
        <div className="descanso">
          <p aria-live="off">
            Descanso {Math.floor(descanso / 60)}:{String(descanso % 60).padStart(2, "0")}
          </p>
          <Boton type="button" compacto onClick={() => setDescanso(null)}>
            SALTAR DESCANSO
          </Boton>
        </div>
      )}
```

`aria-live="off"` a propósito: un contador que se anuncia cada segundo tapa todo lo demás.

- [ ] **Paso 4 · Añadir el ejercicio anterior y el aviso de récord**

Añadir dentro del componente:

```tsx
  /** El mejor peso conocido por ejercicio, y lo que se levantó la última vez.
   *
   *  Las dos son comodidades. Si no hay red no se piden y no se avisa de nada: el caso
   *  normal de esta pantalla es un sótano sin cobertura, y un aviso de «no hemos podido
   *  cargar tus récords» cada vez sería ruido en la pantalla que menos lo admite. */
  const [records, setRecords] = useState<Map<string, number>>(new Map());
  const [anterior, setAnterior] = useState<SerieAnterior[]>([]);
  const [recordBatido, setRecordBatido] = useState<string | null>(null);

  useEffect(() => {
    recordsPersonales()
      .then((lista) => setRecords(new Map(lista.map((r) => [r.name, r.max_weight]))))
      .catch(() => undefined);
  }, []);

  const nombreActual = sesion?.exercises[sesion.actual]?.name;

  useEffect(() => {
    if (!nombreActual) return;
    setAnterior([]);

    // `vigente` evita pintar el «anterior» de un ejercicio que ya no se está mirando: con
    // la navegación de la tarea 11, cambiar de ejercicio con la red lenta haría llegar la
    // respuesta del primero cuando ya se ve el segundo, y los kilos de referencia serían
    // de otro ejercicio sin que nada lo delatara.
    let vigente = true;
    ultimaSesion(nombreActual)
      .then((series) => {
        if (vigente) setAnterior(series);
      })
      .catch(() => undefined);
    return () => {
      vigente = false;
    };
  }, [nombreActual]);
```

En `marcarSerie`, dentro del `if (!serie.hecha)`, después de arrancar el descanso:

```tsx
      // El récord de verdad lo decide el servidor al guardar; esto es un aviso local para
      // que el momento se celebre cuando ocurre y no diez minutos después.
      const mejor = records.get(ejercicioActual.name);
      if (serie.weight_kg != null && mejor != null && serie.weight_kg > mejor) {
        setRecordBatido(
          textoRecord({
            exercise: ejercicioActual.name,
            kind: "weight",
            value: serie.weight_kg,
            previous: mejor,
          }),
        );
      }
```

Pasar `anterior[indice] ?? null` a la propiedad `anterior` de `FilaSerie`, y pintar el
aviso debajo de la tabla:

```tsx
      {recordBatido && <Aviso tono="ambar">Récord. {recordBatido}</Aviso>}
```

Ampliar los imports: `recordsPersonales`, `ultimaSesion`, `type SerieAnterior` de
`../api`, y `textoRecord` de `../formato`.

- [ ] **Paso 5 · Añadir el CSS**

```css
.descanso {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-top: 1rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--azul);
  color: var(--azul);
  font-size: var(--seccion);
}
```

- [ ] **Paso 6 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: PASAN los 12.

- [ ] **Paso 7 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/pantallas/Sesion.tsx web/src/pantallas/Sesion.test.tsx web/src/estilos.css
git commit -m "feat(entreno): descanso entre series y aviso de récord en el momento

El cronómetro de descanso lo ponía el spec en la fase 1.5, pero entre series
hace falta y la fase 1.2 autoriza a adelantarlo. Va solo dentro de esta
pantalla: la pantalla suelta de cronómetro sigue siendo de la 1.5.

Arranca al marcar una serie y no al desmarcarla: desmarcar es corregir un
error, no terminar una serie. Y no se anuncia cada segundo (aria-live off),
porque un contador hablando tapa todo lo demás.

El aviso de récord y la columna «Anterior» son comodidades: si no hay red no
se piden y no se avisa de nada. El caso normal de esta pantalla es un sótano
sin cobertura, y un «no hemos podido cargar tus récords» cada vez sería
ruido justo donde menos se admite. El récord de verdad lo decide el servidor
al guardar."
```

---

## Tarea 11 · `Sesion.tsx`: cambiar de ejercicio y terminar

**Ficheros:**
- Modificar: `web/src/pantallas/Sesion.tsx`
- Modificar: `web/src/pantallas/Sesion.test.tsx`

**Interfaces:**
- Consume: `entregar`, `borrar` de `borrador.ts` (tareas 1 y 4); `duracionMinutos` de
  `formato.ts` (tarea 5); `Campo` de `componentes.tsx`.
- Produce: la navegación a `/entrenar/resumen` con este `state`:

```ts
type EstadoResumen = {
  nombre: string;
  duracion: number;
  volumen: number;
  series: number;
  /** null = quedó en la cola, sin subir. Entonces no hay bloque `system`. */
  guardado: EntrenoGuardado | null;
};
```

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/pantallas/Sesion.test.tsx`:

```tsx
test("se pasa de ejercicio y el anterior conserva lo apuntado", async () => {
  guardar({
    ...EN_CURSO,
    exercises: [
      EN_CURSO.exercises[0],
      { name: "Remo", objetivo: null, sets: [{ ...EN_CURSO.exercises[0].sets[0] }] },
    ],
  });
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "80" } });
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));

  expect(await screen.findByRole("heading", { name: "Remo" })).toBeTruthy();
  expect(leer()!.exercises[0].sets[0].weight_kg).toBe(80);
  expect(leer()!.actual).toBe(1);
});

test("terminar propone la duración transcurrida y deja corregirla", async () => {
  vi.setSystemTime(new Date("2026-08-18T17:45:00Z"));
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "TERMINAR" }));

  // 17:00 a 17:45. La cifra viene puesta; corregirla es lo que salva a un borrador que se
  // retoma al día siguiente, y la duración es lo que decide el XP.
  expect((screen.getByLabelText("Duración en minutos") as HTMLInputElement).value).toBe("45");
});

test("al guardar sin red la sesión queda en la cola y el borrador se limpia", async () => {
  vi.setSystemTime(new Date("2026-08-18T17:45:00Z"));
  vi.mocked(guardarEntreno).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "TERMINAR" }));
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  // El dato está a salvo en los dos casos —subido o encolado—, y esta es la única
  // transición que borra el borrador.
  await vi.waitFor(() => expect(pendientes()).toHaveLength(1));
  expect(leer()).toBe(null);
  expect(pendientes()[0].duracion).toBe(45);
});

test("al guardar con red no queda nada pendiente", async () => {
  vi.setSystemTime(new Date("2026-08-18T17:45:00Z"));
  vi.mocked(guardarEntreno).mockResolvedValue(SUBIDO);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "TERMINAR" }));
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() => expect(leer()).toBe(null));
  expect(pendientes()).toEqual([]);
});

test("mientras se guarda no se puede pulsar dos veces", async () => {
  vi.setSystemTime(new Date("2026-08-18T17:45:00Z"));
  vi.mocked(guardarEntreno).mockImplementation(() => new Promise(() => {}));  // nunca resuelve
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "TERMINAR" }));
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  // Dos POST del mismo entreno con un segundo de diferencia son dos entrenos distintos:
  // el deduplicado los distingue por `date` y ahí las dos fechas serían la misma... pero
  // el segundo saldría antes de que el primero conteste y no hay a quién preguntar.
  expect((await screen.findByRole("button", { name: "GUARDANDO…" })).hasAttribute("disabled")).toBe(true);
});
```

Ampliar los imports con `pendientes` de `../borrador`, `guardarEntreno` y `ErrorApi` de
`../api`, añadir `guardarEntreno: vi.fn()` al `vi.mock("../api")`, y declarar la constante
`SUBIDO` igual que en la tarea 4.

⚠️ **Y añadir `clearMocks: true` al bloque `test` de `web/vite.config.ts`.** Es la primera
tarea con un `navegar` compartido entre tests, y sin eso el último de todos —el que
comprueba `expect(navegar).not.toHaveBeenCalled()`— falla con la pantalla ya correcta. El
`restoreMocks: true` que hay puesto solo toca los espías de `vi.spyOn`: los `vi.fn()` que
devuelve una factoría de `vi.mock` no los limpia nadie y acumulan llamadas de un test al
siguiente. **`mockReset` no sirve**: borraría también implementaciones como el
`vi.fn().mockResolvedValue([])` de `recordsPersonales`, que se declara una vez para todo
el fichero, y rompería los tests de las tareas 9 y 10.

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: FALLAN los 5 nuevos.

- [ ] **Paso 3 · Escribir la navegación entre ejercicios**

En `web/src/pantallas/Sesion.tsx`, dentro del componente:

```tsx
  function irAEjercicio(indice: number) {
    actualizar((s) => ({ ...s, actual: Math.min(Math.max(0, indice), s.exercises.length - 1) }));
    setDescanso(null);
    setRecordBatido(null);
  }
```

Y en la barra de acciones, junto a AÑADIR SERIE:

```tsx
        <Boton
          type="button"
          compacto
          disabled={sesion.actual === 0}
          onClick={() => irAEjercicio(sesion.actual - 1)}
        >
          ANTERIOR
        </Boton>
        <Boton
          type="button"
          compacto
          disabled={sesion.actual === sesion.exercises.length - 1}
          onClick={() => irAEjercicio(sesion.actual + 1)}
        >
          SIGUIENTE
        </Boton>
        <Boton type="button" compacto onClick={() => setTerminando(true)}>
          TERMINAR
        </Boton>
```

- [ ] **Paso 4 · Escribir el paso de terminar**

Es el único paso intermedio de todo el flujo, y existe por dos motivos concretos: la
duración decide el XP (50 puntos a partir de 15 minutos) y un borrador retomado tarde daría
una cifra imposible.

Añadir al componente:

```tsx
  const [terminando, setTerminando] = useState(false);
  const [minutos, setMinutos] = useState("");
  const [notas, setNotas] = useState("");
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  // La duración se propone al abrir el paso, no en cada repintado: si se recalculara sola
  // borraría lo que el usuario acabara de corregir.
  useEffect(() => {
    if (terminando && sesion) setMinutos(String(duracionMinutos(sesion.inicio)));
  }, [terminando, sesion?.inicio]);

  async function guardarEntrenoEntero() {
    if (!sesion || guardando) return;
    setGuardando(true);

    const duracion = Number(minutos) || 0;
    const volumen = volumenTotal(sesion.exercises);
    const series = seriesHechas(sesion.exercises);

    const resultado = await entregar(sesion, duracion, notas);

    // `null` significa que no subió Y tampoco se pudo escribir en el móvil. El entreno
    // solo existe en esta pantalla: borrar el borrador aquí sería perderlo, que es lo
    // único irrecuperable de toda la aplicación. Se deja donde está y se avisa.
    if (resultado === null) {
      setGuardando(false);
      setFallo(
        "No hemos podido guardar el entreno y tampoco cabe en el móvil. " +
          "No cierres esta pantalla: libera espacio y vuelve a darle a guardar.",
      );
      return;
    }

    // Subido o encolado: en los dos casos el dato está a salvo fuera de esta pantalla, y
    // esta es la única transición que borra el borrador.
    borrar();

    navegar("/entrenar/resumen", {
      replace: true,
      state: {
        nombre: sesion.nombre,
        duracion,
        volumen,
        series,
        // `"encolado"` no trae bloque `system`: el XP lo calcula el servidor cuando por
        // fin reciba el entreno, y el resumen ya sabe pintar el caso sin él.
        guardado: resultado === "encolado" ? null : resultado,
      },
    });
  }
```

Y pintarlo en lugar de la tabla cuando `terminando` es cierto:

```tsx
  if (terminando) {
    return (
      <>
        <TituloPantalla pantalla="terminar" />
        <Comentario>{sesion.nombre}</Comentario>

        <Campo
          etiqueta="Duración en minutos"
          name="duracion"
          type="number"
          min="0"
          max="600"
          inputMode="numeric"
          value={minutos}
          onChange={(e) => setMinutos(e.target.value)}
        />

        <Campo
          etiqueta="Notas (opcional)"
          name="notas"
          type="text"
          value={notas}
          onChange={(e) => setNotas(e.target.value)}
        />

        {fallo && <Aviso tono="rojo">{fallo}</Aviso>}

        <Boton type="button" onClick={() => void guardarEntrenoEntero()} disabled={guardando}>
          {guardando ? "GUARDANDO…" : "GUARDAR"}
        </Boton>
        <Boton type="button" compacto onClick={() => setTerminando(false)} disabled={guardando}>
          SEGUIR ENTRENANDO
        </Boton>
      </>
    );
  }
```

Ampliar los imports con `entregar`, `borrar` de `../borrador`, `duracionMinutos`,
`volumenTotal` de `../formato` y `Campo`, `Aviso` de `../componentes`.

⚠️ **Y añadir el test que cierra el único camino por el que se pierde un entreno.** Sin
él nadie comprueba que la pantalla respeta el `null` de `entregar`:

```tsx
test("si no se puede ni subir ni guardar, el borrador NO se borra", async () => {
  // `...Once` y no `mockResolvedValue`: `clearMocks` borra las llamadas, no las
  // implementaciones, y un `null` que se quedara puesto se comería al primer test que se
  // añadiera detrás —la tarea 14 vuelve a esta pantalla— sin que se viera por qué.
  vi.mocked(entregar).mockResolvedValueOnce(null);
  pintarEnSesion();

  fireEvent.click(screen.getByRole("button", { name: "TERMINAR" }));
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  // Se avisa en español, sin número de error…
  expect((await screen.findByRole("alert")).textContent).toContain("No cierres esta pantalla");
  expect(document.body.textContent).not.toMatch(/\b[45]\d\d\b/);

  // …y sobre todo: el entreno sigue en el móvil y no se ha ido a ninguna parte.
  expect(leer()).not.toBe(null);
  expect(navegar).not.toHaveBeenCalled();
});
```

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: PASAN los 18.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/vite.config.ts web/src/pantallas/Sesion.tsx web/src/pantallas/Sesion.test.tsx
git commit -m "feat(entreno): cambiar de ejercicio y terminar la sesión

Terminar es el único paso intermedio de todo el flujo y existe por dos
motivos concretos: la duración decide el XP —50 puntos a partir de 15
minutos— y un borrador retomado al día siguiente daría una cifra imposible.
Viene rellena con el tiempo transcurrido y se puede corregir.

El borrador se borra después de entregar en dos de los tres casos: subido y
encolado, que son los que dejan el dato a salvo fuera de esta pantalla. Es
la única transición que lo borra.

El tercero es el que importa. Si entregar devuelve null no subió y tampoco
cupo en el móvil, así que el entreno solo existe en esta pantalla: se deja
donde está, se avisa, y no se navega a ninguna parte. Es el único camino de
toda la aplicación por el que se puede perder un entreno.

Y el botón se bloquea mientras se guarda. Dos POST del mismo entreno con un
segundo de diferencia crearían dos entrenos, y el deduplicado no puede
salvar eso: el segundo sale antes de que el primero conteste, así que no hay
a quién preguntar."
```

---

## Tarea 12 · `Resumen.tsx`

**Ficheros:**
- Crear: `web/src/pantallas/Resumen.tsx`
- Crear: `web/src/pantallas/Resumen.test.tsx`

**Interfaces:**
- Consume: el `state` que manda `Sesion.tsx` (tarea 11); `VentanaSistema`, `Aviso` de
  `componentes.tsx`; `textoXpGanado`, `textoRecord` de `formato.ts`.
- Produce: `export default function Resumen()`, en `/entrenar/resumen`.

- [ ] **Paso 1 · Escribir el test que falla**

Crear `web/src/pantallas/Resumen.test.tsx`:

```tsx
import { fireEvent, render, screen, within } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router";
import { expect, test } from "vitest";
import type { BloqueSistema, EntrenoGuardado } from "../api";
import Resumen from "./Resumen";

function sistema(campos: Partial<BloqueSistema> = {}): BloqueSistema {
  return {
    xp_gained: 80, level_up: null, rank_up: null,
    achievements_unlocked: [], records: [], quests_completed: [],
    progress: {
      level: 4, rank: "E", xp_total: 1200, xp_into_level: 240, xp_for_next: 400,
      current_streak: 12, longest_streak: 20,
      stats: { strength: 3, endurance: 5, consistency: 8, vitality: 2 },
    },
    ...campos,
  };
}

function pintar(estado: unknown) {
  return render(
    <MemoryRouter initialEntries={[{ pathname: "/entrenar/resumen", state: estado }]}>
      <Routes>
        <Route path="/entrenar/resumen" element={<Resumen />} />
        <Route path="/" element={<p>hoy</p>} />
      </Routes>
    </MemoryRouter>,
  );
}

const BASE = { nombre: "Torso pesado", duracion: 45, volumen: 3200, series: 12 };

test("con conexión sale el XP que dijo el servidor", () => {
  pintar({ ...BASE, guardado: { new_records: [], system: sistema() } as unknown as EntrenoGuardado });
  expect(screen.getByText("+80 XP")).toBeTruthy();
});

test("sin conexión se dice que se subirá solo, y no se inventa ningún XP", () => {
  // El XP lo decide el servidor y aquí todavía no ha hablado. Poner una cifra para tapar
  // el hueco sería reimplementar el cálculo en el cliente.
  pintar({ ...BASE, guardado: null });

  // Sin el «se»: la frase del aviso empieza ahí y la ese va en mayúscula.
  expect(screen.getByRole("status").textContent).toContain("Se subirá solo");
  expect(document.body.textContent).not.toContain("XP");
});

test("el tercer entreno del día se explica sin parecer un error", () => {
  pintar({
    ...BASE,
    guardado: { new_records: [], system: sistema({ xp_gained: 0 }) } as unknown as EntrenoGuardado,
  });

  expect(
    screen.getByText("Guardado. Hoy ya has llegado al máximo de XP, así que este entreno no suma."),
  ).toBeTruthy();
  // Y nada de rojo: no ha fallado nada.
  expect(screen.queryByRole("alert")).toBe(null);
});

test("un récord abre la ventana del Sistema", () => {
  pintar({
    ...BASE,
    guardado: {
      new_records: [{ name: "Press banca", weight_kg: 85, previous_pr: 80, is_first: false }],
      system: sistema({
        records: [{ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }],
      }),
    } as unknown as EntrenoGuardado,
  });

  // Dentro de la ventana, no en la pantalla entera: el récord sale dos veces a propósito
  // —en la ventana, que es el premio, y en la lista, que es lo que queda cuando se cierra—
  // así que buscarlo suelto encontraría dos y no demostraría que la ventana lo lleva.
  const ventana = screen.getByRole("dialog");
  expect(within(ventana).getByText("Press banca: 85 kg, antes 80 kg.")).toBeTruthy();
});

test("al cerrar la ventana el récord sigue en la pantalla", () => {
  // La ventana se cierra de un botón y no vuelve. Si el récord viviera solo dentro de
  // ella, cerrarla lo borraría de la única pantalla que lo cuenta.
  pintar({
    ...BASE,
    guardado: {
      new_records: [],
      system: sistema({
        records: [{ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }],
      }),
    } as unknown as EntrenoGuardado,
  });

  fireEvent.click(screen.getByRole("button", { name: "CERRAR" }));

  expect(screen.queryByRole("dialog")).toBe(null);
  expect(screen.getByText("Press banca: 85 kg, antes 80 kg.")).toBeTruthy();
});

test("recargar el resumen lleva a hoy en vez de reventar", () => {
  // El resumen es una pantalla de paso: su contenido viaja en el estado de la navegación
  // y una recarga lo pierde. El dato ya está a salvo en el servidor o en la cola.
  pintar(undefined);
  expect(screen.getByText("hoy")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Resumen`
Esperado: FALLA, no existe `./Resumen`.

- [ ] **Paso 3 · Escribir la pantalla**

Crear `web/src/pantallas/Resumen.tsx`:

```tsx
/* Lo que pasó, en una pantalla. No calcula nada del Sistema: recibe el bloque `system`
   que devolvió el servidor y lo pinta. */

import { useState } from "react";
import { Navigate, useLocation, useNavigate } from "react-router";
import type { EntrenoGuardado } from "../api";
import { Aviso, Boton, Comentario, TituloPantalla, VentanaSistema } from "../componentes";
import { textoRecord, textoXpGanado } from "../formato";

type EstadoResumen = {
  nombre: string;
  duracion: number;
  volumen: number;
  series: number;
  /** null = quedó en la cola. Entonces no hay bloque `system` que enseñar. */
  guardado: EntrenoGuardado | null;
};

export default function Resumen() {
  const navegar = useNavigate();
  const estado = useLocation().state as EstadoResumen | null;
  const [ventanaCerrada, setVentanaCerrada] = useState(false);

  // Una recarga pierde el estado de la navegación. No pasa nada: para cuando se llega
  // aquí el entreno ya está en el servidor o en la cola.
  if (!estado) return <Navigate to="/" replace />;

  const { nombre, duracion, volumen, series, guardado } = estado;

  return (
    <>
      <TituloPantalla pantalla="resumen" />
      <Comentario>{nombre}</Comentario>

      <dl className="cifras">
        <div>
          <dt>Duración</dt>
          <dd>{duracion} min</dd>
        </div>
        <div>
          <dt>Volumen</dt>
          <dd>{volumen.toLocaleString("es-ES")} kg</dd>
        </div>
        <div>
          <dt>Series</dt>
          <dd>{series}</dd>
        </div>
      </dl>

      {guardado ? (
        <>
          <p className="xp">{textoXpGanado(guardado.system.xp_gained, duracion)}</p>

          {/* Del bloque `system`, no de `new_records`: es la forma que lee toda la
              interfaz, y las dos traen exactamente los mismos récords. */}
          {guardado.system.records.length > 0 && (
            <ul className="lista-records">
              {guardado.system.records.map((record) => (
                <li key={record.exercise}>{textoRecord(record)}</li>
              ))}
            </ul>
          )}

          {!ventanaCerrada && (
            <VentanaSistema sistema={guardado.system} alCerrar={() => setVentanaCerrada(true)} />
          )}
        </>
      ) : (
        // Sin bloque `system`: el XP lo decide el servidor y todavía no ha hablado.
        // Inventar una cifra para tapar el hueco sería calcular el XP en el cliente.
        <Aviso tono="ambar">
          Guardado en el móvil. Se subirá solo en cuanto haya conexión.
        </Aviso>
      )}

      <Boton type="button" onClick={() => navegar("/", { replace: true })}>
        VOLVER
      </Boton>
    </>
  );
}
```

- [ ] **Paso 4 · Añadir el CSS**

```css
.cifras {
  display: flex;
  gap: 1.5rem;
  margin-top: 1.25rem;
}

.cifras dt {
  color: var(--apagado);
  font-size: var(--etiqueta);
  letter-spacing: 0.1em;
}

.cifras dd {
  color: var(--texto);
  font-size: var(--seccion);
}

.lista-records {
  list-style: none;
  margin-top: 0.75rem;
  color: var(--rojo);
  font-size: var(--cuerpo);
}
```

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- Resumen`
Esperado: PASAN los 6.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/pantallas/Resumen.tsx web/src/pantallas/Resumen.test.tsx web/src/estilos.css
git commit -m "feat(entreno): el resumen post-entreno

Duración, volumen y series siempre; el XP y la ventana del Sistema solo si
el servidor llegó a contestar. Sin conexión se dice que se subirá solo y no
se enseña ninguna cifra de XP: la decide el servidor y aquí todavía no ha
hablado, así que ponerla para tapar el hueco sería calcularla en el cliente.

Un xp_gained de cero no es un error y no se pinta como tal: se explica con
palabras y sin nada en rojo.

Y recargar esta pantalla lleva a hoy en vez de reventar. El contenido viaja
en el estado de la navegación y una recarga lo pierde, pero para cuando se
llega aquí el entreno ya está en el servidor o en la cola."
```

---

## Tarea 13 · `Elegir.tsx`: de dónde sale el entreno

**Ficheros:**
- Crear: `web/src/pantallas/Elegir.tsx`
- Crear: `web/src/pantallas/Elegir.test.tsx`

**Interfaces:**
- Consume: `plantillas`, `entrenos`, `diaDeHoy` de `api.ts` (tarea 6); `ahoraUtc`,
  `guardar`, `descansoPorDefecto`, tipos de `borrador.ts`.
- Produce: `export default function Elegir()`, en `/entrenar`. Deja una `Sesion` guardada
  y navega a `/entrenar/sesion`.

- [ ] **Paso 1 · Escribir el test que falla**

Crear `web/src/pantallas/Elegir.test.tsx`:

```tsx
import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { diaDeHoy, entrenos, plantillas } from "../api";
import { guardar, leer } from "../borrador";
import Elegir from "./Elegir";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  diaDeHoy: vi.fn(),
  entrenos: vi.fn(),
  plantillas: vi.fn(),
}));

// Se envuelve la implementación real —escribe de verdad en localStorage, que es lo que
// comprueban casi todos los tests de aquí— y solo el que mira el disco lleno la sustituye,
// con `...Once` para que no se quede puesta.
vi.mock("../borrador", async (importOriginal) => {
  const real = await importOriginal<typeof import("../borrador")>();
  return { ...real, guardar: vi.fn(real.guardar) };
});

const PLANTILLA = {
  id: "t-1", user_id: null, name: "Torso pesado", description: null,
  level: "Intermedio", mode: "gym" as const, duration_minutes: 60,
  exercises: [
    { id: 1, name: "Press banca", sets: 4, reps: 5 },
    { id: 2, name: "Remo con barra", sets: 4, reps: 8 },
  ],
};

function pintar() {
  return render(
    <MemoryRouter initialEntries={["/entrenar"]}>
      <Routes>
        <Route path="/entrenar" element={<Elegir />} />
        <Route path="/entrenar/sesion" element={<p>sesión</p>} />
      </Routes>
    </MemoryRouter>,
  );
}

beforeEach(() => {
  localStorage.clear();
  vi.mocked(plantillas).mockResolvedValue([PLANTILLA]);
  vi.mocked(entrenos).mockResolvedValue([]);
  vi.mocked(diaDeHoy).mockResolvedValue({
    date: "2026-08-18",
    progress: { level: 4, rank: "E", xp_into_level: 240, xp_for_next: 400, current_streak: 12 },
    quests: [],
    suggested_workout: {
      reason: "Te faltan 2 entrenos para tu meta de esta semana.",
      weekly_done: 1, weekly_goal: 3, template: PLANTILLA,
    },
  });
});

test("el motivo lo escribe el servidor y se enseña tal cual", async () => {
  pintar();
  // Ya viene en castellano desde /system/today. Reescribirlo aquí sería tener el mismo
  // texto en dos sitios y que uno se quedara viejo.
  expect(await screen.findByText("Te faltan 2 entrenos para tu meta de esta semana.")).toBeTruthy();
});

test("empezar desde una plantilla deja una serie por ejercicio, lista para rellenar", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR TORSO PESADO" }));

  const sesion = leer()!;
  expect(sesion.nombre).toBe("Torso pesado");
  expect(sesion.mode).toBe("gym");
  expect(sesion.exercises.map((e) => e.name)).toEqual(["Press banca", "Remo con barra"]);
  // Una serie semilla, no cuatro vacías: se añaden según se hacen, que es como se entrena.
  expect(sesion.exercises[0].sets).toHaveLength(1);
  // El objetivo de la plantilla se guarda como guía, no como obligación.
  expect(sesion.exercises[0].objetivo).toEqual({ sets: 4, reps: 5 });
  expect(await screen.findByText("sesión")).toBeTruthy();
});

test("la plantilla sugerida no sale también en la lista de abajo", async () => {
  // Dos botones con el mismo nombre accesible son dos botones idénticos para quien usa
  // lector de pantalla, y no hay forma de saber que hacen lo mismo.
  pintar();
  await screen.findByRole("button", { name: "EMPEZAR TORSO PESADO" });

  expect(screen.getAllByRole("button", { name: "EMPEZAR TORSO PESADO" })).toHaveLength(1);
});

test("empezar en blanco no inventa ningún ejercicio", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR EN BLANCO" }));

  expect(leer()!.exercises).toEqual([]);
  expect(leer()!.nombre).toBe("Entreno libre");
});

test("si no cabe en el móvil se avisa y no se pasa a una pantalla vacía", async () => {
  // `guardar` devuelve si pudo escribir, y tragárselo es lo peor que se puede hacer aquí:
  // la sesión no existiría en disco, `Sesion.tsx` no pinta nada sin ella, y el usuario se
  // quedaría mirando una pantalla en blanco sin saber por qué.
  vi.mocked(guardar).mockReturnValueOnce(false);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR EN BLANCO" }));

  expect((await screen.findByRole("alert")).textContent).toContain("libera espacio");
  expect(screen.queryByText("sesión")).toBe(null);
});

test("repetir el último copia los pesos y ninguna serie llega marcada", async () => {
  vi.mocked(entrenos).mockResolvedValue([
    {
      id: "w-1", date: "2026-08-15T17:00:00.000000Z", mode: "gym", duration_minutes: 45,
      sets: [
        { name: "Press banca", weight_kg: 80, reps: 5, rest_seconds: 180, distance_m: null, time_seconds: null, style: null },
        { name: "Press banca", weight_kg: 80, reps: 5, rest_seconds: 180, distance_m: null, time_seconds: null, style: null },
        { name: "Remo con barra", weight_kg: 60, reps: 8, rest_seconds: 120, distance_m: null, time_seconds: null, style: null },
      ],
    },
  ]);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "REPETIR EL ÚLTIMO" }));

  const sesion = leer()!;
  expect(sesion.exercises.map((e) => e.name)).toEqual(["Press banca", "Remo con barra"]);
  expect(sesion.exercises[0].sets).toHaveLength(2);
  expect(sesion.exercises[0].sets[0].weight_kg).toBe(80);
  // Marcadas no: son el plan de hoy, no el entreno de hoy.
  expect(sesion.exercises[0].sets.every((s) => !s.hecha)).toBe(true);
});

test("sin un entreno anterior de ese modo se dice en español y no se rompe nada", async () => {
  vi.mocked(entrenos).mockResolvedValue([]);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "REPETIR EL ÚLTIMO" }));

  expect((await screen.findByRole("alert")).textContent).toContain("No hay un entreno anterior");
  expect(leer()).toBe(null);
});

test("el entreno empieza con la hora en UTC y sin milisegundos", async () => {
  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR EN BLANCO" }));

  expect(leer()!.inicio).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Elegir`
Esperado: FALLA, no existe `./Elegir`.

- [ ] **Paso 3 · Escribir la pantalla**

Crear `web/src/pantallas/Elegir.tsx`:

```tsx
/* De dónde sale el entreno de hoy: una plantilla, el último que se hizo, o nada.
   Lo único que hace esta pantalla es dejar una `Sesion` guardada y pasar a la siguiente. */

import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { diaDeHoy, entrenos, plantillas, type EntrenoSugerido, type Plantilla } from "../api";
// `Modo` sale de borrador.ts, no de api.ts: api.ts lo importa de ahí y no lo reexporta.
import {
  ahoraUtc, descansoPorDefecto, guardar,
  type Ejercicio, type Modo, type Serie, type Sesion,
} from "../borrador";
import { Aviso, Boton, Comentario, Seccion, TituloPantalla } from "../componentes";

function serieSemilla(): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: descansoPorDefecto(), hecha: false,
  };
}

export default function Elegir() {
  const navegar = useNavigate();
  const [sugerido, setSugerido] = useState<EntrenoSugerido | null>(null);
  const [lista, setLista] = useState<Plantilla[]>([]);
  const [modo, setModo] = useState<Modo>("gym");
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    // Las dos son comodidades: sin ellas se puede entrenar igual empezando en blanco, así
    // que un fallo aquí no bloquea la pantalla.
    diaDeHoy().then((dia) => setSugerido(dia.suggested_workout)).catch(() => undefined);
    plantillas().then(setLista).catch(() => undefined);
  }, []);

  function empezar(sesion: Sesion) {
    // `guardar` devuelve si pudo escribir y aquí no se puede tragar. Sin la sesión en
    // disco, la pantalla siguiente no tiene nada que pintar y se queda en blanco: el
    // usuario creería que la aplicación se ha roto sin saber que lo que pasa es que no
    // cabe. Se avisa y no se pasa de aquí.
    if (!guardar(sesion)) {
      setFallo(
        "No hemos podido empezar el entreno porque no cabe en el móvil. " +
          "Cierra alguna aplicación o libera espacio y vuelve a intentarlo.",
      );
      return;
    }
    navegar("/entrenar/sesion");
  }

  function desdePlantilla(plantilla: Plantilla) {
    empezar({
      v: 1,
      mode: plantilla.mode ?? "gym",
      nombre: plantilla.name,
      inicio: ahoraUtc(),
      actual: 0,
      // Una serie semilla por ejercicio, no las cuatro que pide la plantilla: las series
      // se añaden según se hacen, que es como se entrena de verdad. El objetivo se guarda
      // aparte como guía.
      exercises: plantilla.exercises.map<Ejercicio>((ejercicio) => ({
        name: ejercicio.name,
        objetivo: { sets: ejercicio.sets, reps: ejercicio.reps },
        sets: [serieSemilla()],
      })),
    });
  }

  function enBlanco() {
    empezar({
      v: 1, mode: modo, nombre: "Entreno libre",
      inicio: ahoraUtc(), actual: 0, exercises: [],
    });
  }

  async function repetirElUltimo() {
    setFallo(null);
    let ultimos;
    try {
      ultimos = await entrenos({ mode: modo, per_page: 1 });
    } catch {
      setFallo("No hemos podido cargar tu último entreno. Puedes empezar en blanco.");
      return;
    }

    const ultimo = ultimos[0];
    if (!ultimo || ultimo.sets.length === 0) {
      setFallo("No hay un entreno anterior de este tipo. Empieza desde una plantilla o en blanco.");
      return;
    }

    // Las series vienen en una sola lista, una fila por serie. Se agrupan por nombre
    // respetando el orden en que aparecen, que es el orden en que se hicieron.
    const porNombre = new Map<string, Ejercicio>();
    for (const serie of ultimo.sets) {
      if (!porNombre.has(serie.name)) {
        porNombre.set(serie.name, { name: serie.name, objetivo: null, sets: [] });
      }
      porNombre.get(serie.name)!.sets.push({
        weight_kg: serie.weight_kg,
        reps: serie.reps,
        rpe: null,
        distance_m: serie.distance_m,
        time_seconds: serie.time_seconds,
        style: serie.style,
        rest_seconds: serie.rest_seconds,
        // Sin marcar: es el plan de hoy, no el entreno de hoy.
        hecha: false,
      });
    }

    empezar({
      v: 1, mode: ultimo.mode, nombre: "Repetir el último",
      inicio: ahoraUtc(), actual: 0, exercises: [...porNombre.values()],
    });
  }

  // La sugerida se cae de la lista: ya tiene su propio botón arriba, y dos botones con el
  // mismo nombre son dos botones idénticos para quien usa un lector de pantalla.
  const delModo = lista.filter((p) => p.mode === modo && p.id !== sugerido?.template?.id);

  return (
    <>
      <TituloPantalla pantalla="entrenar" />
      {sugerido && <Comentario>{sugerido.reason}</Comentario>}
      {fallo && <Aviso tono="rojo">{fallo}</Aviso>}

      <div className="modos" role="group" aria-label="Tipo de entreno">
        {(["gym", "home", "calisthenics", "swimming"] as const).map((m) => (
          <Boton key={m} type="button" compacto aria-pressed={modo === m} onClick={() => setModo(m)}>
            {{ gym: "GIMNASIO", home: "CASA", calisthenics: "CALISTENIA", swimming: "NATACIÓN" }[m]}
          </Boton>
        ))}
      </div>

      {sugerido?.template && (
        <Boton type="button" onClick={() => desdePlantilla(sugerido.template!)}>
          EMPEZAR {sugerido.template.name.toUpperCase()}
        </Boton>
      )}

      <Boton type="button" onClick={() => void repetirElUltimo()}>
        REPETIR EL ÚLTIMO
      </Boton>
      <Boton type="button" onClick={enBlanco}>
        EMPEZAR EN BLANCO
      </Boton>

      <Seccion titulo="Plantillas" resumen={`${delModo.length}`}>
        <ul className="lista-plantillas">
          {delModo.map((plantilla) => (
            <li key={plantilla.id}>
              <Boton type="button" compacto onClick={() => desdePlantilla(plantilla)}>
                EMPEZAR {plantilla.name.toUpperCase()}
              </Boton>
              <Comentario>
                {plantilla.exercises.length} ejercicios
                {plantilla.level ? ` · ${plantilla.level}` : ""}
              </Comentario>
            </li>
          ))}
        </ul>
      </Seccion>
    </>
  );
}
```

- [ ] **Paso 4 · Añadir el CSS**

```css
.modos {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1rem;
}

.modos .boton[aria-pressed="true"] {
  background: var(--ambar);
  color: var(--fondo);
}

.lista-plantillas {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 0.5rem;
}
```

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- Elegir`
Esperado: PASAN los 8.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/pantallas/Elegir.tsx web/src/pantallas/Elegir.test.tsx web/src/estilos.css
git commit -m "feat(entreno): elegir de dónde sale el entreno de hoy

Plantilla, repetir el último o en blanco. El motivo —«te faltan 2 entrenos
para tu meta de esta semana»— lo escribe el servidor y se enseña tal cual:
reescribirlo aquí sería tener el mismo texto en dos sitios y que uno se
quedara viejo.

Una plantilla de cuatro series deja una sola serie semilla por ejercicio,
no cuatro filas vacías. Las series se añaden según se hacen, que es como se
entrena; lo que pedía la plantilla se guarda aparte como guía.

Repetir el último agrupa las series por nombre respetando el orden en que
vienen, copia los pesos y no marca ninguna: es el plan de hoy, no el entreno
de hoy.

Las dos llamadas que alimentan esta pantalla pueden fallar sin bloquearla.
Sin ellas se entrena igual empezando en blanco, y esta fase es justamente la
que tiene que funcionar sin cobertura."
```

---

## Tarea 14 · `Sesion.tsx`: añadir y quitar ejercicios

Sin esto, «empezar en blanco» deja una sesión con cero ejercicios y la pantalla intenta
pintar `exercises[0]`, que no existe.

**Ficheros:**
- Modificar: `web/src/pantallas/Sesion.tsx`
- Modificar: `web/src/pantallas/Sesion.test.tsx`
- Modificar: `web/src/estilos.css`

**Interfaces:**
- Consume: `sugerenciasEjercicio`, `catalogoEjercicios` de `api.ts` (tarea 6).
- Produce: nada nuevo hacia fuera.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/pantallas/Sesion.test.tsx`:

```tsx
test("una sesión en blanco pide el primer ejercicio en vez de romperse", async () => {
  guardar({ ...EN_CURSO, nombre: "Entreno libre", exercises: [] });
  pintar();

  expect(await screen.findByLabelText("Nombre del ejercicio")).toBeTruthy();
  expect(screen.getByText("Añade el primer ejercicio para empezar.")).toBeTruthy();
});

test("se puede escribir un ejercicio que no está en ninguna lista", async () => {
  guardar({ ...EN_CURSO, exercises: [] });
  pintar();

  fireEvent.change(await screen.findByLabelText("Nombre del ejercicio"), {
    target: { value: "Hip thrust en multipower" },
  });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR EJERCICIO" }));

  // Escribir libre es como entran los ejercicios nuevos: el catálogo son doce nombres
  // fijos y las sugerencias solo salen del historial.
  expect(leer()!.exercises[0].name).toBe("Hip thrust en multipower");
  expect(leer()!.exercises[0].sets).toHaveLength(1);
});

test("un ejercicio sin nombre no se añade", async () => {
  guardar({ ...EN_CURSO, exercises: [] });
  pintar();

  fireEvent.change(await screen.findByLabelText("Nombre del ejercicio"), { target: { value: "   " } });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR EJERCICIO" }));

  expect(leer()!.exercises).toEqual([]);
});

test("las sugerencias del historial salen antes que el catálogo, y sin repetirse", async () => {
  vi.mocked(sugerenciasEjercicio).mockResolvedValue(["Press banca", "Hip thrust"]);
  vi.mocked(catalogoEjercicios).mockResolvedValue([
    { name: "Sentadilla", category: "Pierna", muscle_group: "Cuádriceps" },
    { name: "Press banca", category: "Empuje", muscle_group: "Pecho" },
  ]);
  guardar({ ...EN_CURSO, exercises: [] });
  pintar();

  // El <datalist> lleva `hidden` de serie —no se pinta, lo despliega el navegador—, así
  // que hay que pedirle a la consulta que mire también lo oculto. Sin eso no encuentra
  // ninguna opción y el test falla con la pantalla ya correcta.
  const opciones = await screen.findAllByRole("option", { hidden: true });
  expect(opciones.map((o) => o.getAttribute("value"))).toEqual([
    "Press banca",
    "Hip thrust",
    "Sentadilla",
  ]);
});

test("quitar un ejercicio pide confirmación y no deja el índice fuera de sitio", async () => {
  guardar({
    ...EN_CURSO,
    actual: 1,
    exercises: [EN_CURSO.exercises[0], { name: "Remo", objetivo: null, sets: [] }],
  });
  vi.spyOn(window, "confirm").mockReturnValue(true);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "QUITAR EJERCICIO" }));

  expect(leer()!.exercises.map((e) => e.name)).toEqual(["Press banca"]);
  // Si `actual` se quedara en 1 la pantalla intentaría pintar un ejercicio que ya no está.
  expect(leer()!.actual).toBe(0);
});

test("si se dice que no, no se quita nada", async () => {
  vi.spyOn(window, "confirm").mockReturnValue(false);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "QUITAR EJERCICIO" }));

  expect(leer()!.exercises).toHaveLength(1);
});
```

Añadir `sugerenciasEjercicio: vi.fn().mockResolvedValue([])` y
`catalogoEjercicios: vi.fn().mockResolvedValue([])` al `vi.mock("../api")`, e importarlos.

Y volver a poner esos valores por defecto en el `beforeEach`, junto con los de
`recordsPersonales`:

```tsx
  vi.mocked(recordsPersonales).mockResolvedValue([]);
  vi.mocked(sugerenciasEjercicio).mockResolvedValue([]);
  vi.mocked(catalogoEjercicios).mockResolvedValue([]);
```

`clearMocks` borra las llamadas, no las implementaciones: un `mockResolvedValue` puesto
dentro de un test se queda puesto para todos los que vengan detrás. Ya pasaba antes de
esta tarea —el `mockRejectedValue` de «sin conexión el aviso de récord simplemente no
sale» lo heredaban los cinco tests siguientes— y no rompía nada de milagro, porque la
llamada está dentro de un `catch`. Con dos mocks más y el orden de los tests decidiendo
qué ve cada uno, deja de ser cuestión de suerte.

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: FALLAN los 6 nuevos. El primero probablemente revienta al pintar, que es
exactamente el fallo que esta tarea arregla.

- [ ] **Paso 3 · Escribir el buscador de ejercicio**

En `web/src/pantallas/Sesion.tsx`, añadir al componente:

```tsx
  const [nombreNuevo, setNombreNuevo] = useState("");
  const [sugerencias, setSugerencias] = useState<string[]>([]);
  const [catalogo, setCatalogo] = useState<string[]>([]);

  useEffect(() => {
    // El catálogo son doce nombres fijos escritos en el controlador; las sugerencias
    // salen solo del historial del usuario, así que el primer día vienen vacías. Se unen
    // porque por separado ninguna de las dos basta.
    catalogoEjercicios().then((lista) => setCatalogo(lista.map((e) => e.name))).catch(() => undefined);
  }, []);

  useEffect(() => {
    sugerenciasEjercicio(nombreNuevo).then(setSugerencias).catch(() => undefined);
  }, [nombreNuevo]);

  // Las del historial primero: son las que esta persona usa de verdad.
  const opciones = [...new Set([...sugerencias, ...catalogo])];

  function anadirEjercicio() {
    const nombre = nombreNuevo.trim();
    if (!nombre) return;

    actualizar((s) => ({
      ...s,
      exercises: [...s.exercises, { name: nombre, objetivo: null, sets: [serieNueva(descansoPorDefecto())] }],
      actual: s.exercises.length,
    }));
    setNombreNuevo("");
  }

  function quitarEjercicio() {
    if (!confirm("¿Quitar este ejercicio y todas sus series?")) return;

    actualizar((s) => {
      const exercises = s.exercises.filter((_, i) => i !== s.actual);
      return {
        ...s,
        exercises,
        // Sin esto, quitar el último dejaría `actual` apuntando a un ejercicio que ya no
        // está y la pantalla intentaría pintar `undefined`.
        actual: Math.max(0, Math.min(s.actual, exercises.length - 1)),
      };
    });
  }
```

El bloque de añadir, que se pinta siempre:

```tsx
      <div className="anadir-ejercicio">
        <Campo
          etiqueta="Nombre del ejercicio"
          name="ejercicio"
          type="text"
          list="ejercicios-conocidos"
          autoComplete="off"
          value={nombreNuevo}
          onChange={(e) => setNombreNuevo(e.target.value)}
        />
        {/* <datalist> nativo: el navegador ya sabe filtrar y desplegar, y funciona con el
            teclado del móvil sin que haya que escribir un desplegable a mano. */}
        <datalist id="ejercicios-conocidos">
          {opciones.map((nombre) => (
            <option key={nombre} value={nombre} />
          ))}
        </datalist>
        <Boton type="button" compacto onClick={anadirEjercicio}>
          AÑADIR EJERCICIO
        </Boton>
      </div>
```

- [ ] **Paso 4 · Tratar la sesión sin ejercicios**

Sustituir el bloque que pinta el ejercicio actual por:

```tsx
  const ejercicio = sesion.exercises[sesion.actual];

  if (!ejercicio) {
    return (
      <>
        <TituloPantalla pantalla="entreno" />
        {sinSitio && <Aviso tono="rojo">…</Aviso>}
        <Comentario>Añade el primer ejercicio para empezar.</Comentario>
        {/* el bloque de añadir */}
      </>
    );
  }
```

Y añadir `QUITAR EJERCICIO` a la barra de acciones.

- [ ] **Paso 5 · Añadir el CSS**

```css
.anadir-ejercicio {
  display: flex;
  align-items: flex-end;
  gap: 0.5rem;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--lineas);
}

.anadir-ejercicio .campo {
  flex: 1;
}
```

- [ ] **Paso 6 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- Sesion`
Esperado: PASAN los 24.

- [ ] **Paso 7 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/pantallas/Sesion.tsx web/src/pantallas/Sesion.test.tsx web/src/estilos.css
git commit -m "feat(entreno): añadir y quitar ejercicios durante la sesión

Sin esto, empezar en blanco dejaba una sesión con cero ejercicios y la
pantalla intentaba pintar exercises[0], que no existe.

El buscador une dos fuentes porque por separado ninguna basta: las
sugerencias salen solo del historial del usuario y el primer día vienen
vacías, y el catálogo son doce nombres fijos escritos en el controlador. Y
siempre se puede escribir un nombre libre, que es como entran los
ejercicios nuevos.

Es un <datalist> nativo: el navegador ya sabe filtrar y desplegar, y
funciona con el teclado del móvil sin escribir un desplegable a mano.

Quitar un ejercicio recoloca el índice. Sin eso, quitar el último dejaba
`actual` apuntando a un ejercicio que ya no está."
```

---

## Tarea 15 · `Plantillas.tsx`

**Ficheros:**
- Crear: `web/src/pantallas/Plantillas.tsx`
- Crear: `web/src/pantallas/Plantillas.test.tsx`

**Interfaces:**
- Consume: `plantillas`, `crearPlantilla`, `editarPlantilla`, `borrarPlantilla`,
  `PlantillaEditable` de `api.ts` (tarea 6).
- Produce: `export default function Plantillas()`, en `/plantillas`.

- [ ] **Paso 1 · Escribir el test que falla**

Crear `web/src/pantallas/Plantillas.test.tsx`:

```tsx
import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { borrarPlantilla, crearPlantilla, plantillas } from "../api";
import Plantillas from "./Plantillas";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  plantillas: vi.fn(),
  crearPlantilla: vi.fn(),
  editarPlantilla: vi.fn(),
  borrarPlantilla: vi.fn(),
}));

const DEL_SISTEMA = {
  id: "t-1", user_id: null, name: "Torso pesado", description: null, level: "Intermedio",
  mode: "gym" as const, duration_minutes: 60,
  exercises: [{ id: 1, name: "Press banca", sets: 4, reps: 5 }],
};

const MIA = { ...DEL_SISTEMA, id: "t-2", user_id: "u-1", name: "La mía" };

const pintar = () => render(<MemoryRouter><Plantillas /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(plantillas).mockResolvedValue([DEL_SISTEMA, MIA]);
});

test("las plantillas del sistema se ven pero no se pueden editar ni borrar", async () => {
  pintar();
  await screen.findByText("Torso pesado");

  // El servidor responde 403. Enseñar el botón sería ofrecer algo que va a fallar.
  expect(screen.queryByRole("button", { name: "EDITAR TORSO PESADO" })).toBe(null);
  expect(screen.queryByRole("button", { name: "BORRAR TORSO PESADO" })).toBe(null);
  expect(screen.getByRole("button", { name: "EDITAR LA MÍA" })).toBeTruthy();
});

test("crear una plantilla manda solo lo que la API acepta", async () => {
  vi.mocked(crearPlantilla).mockResolvedValue({ message: "ok", template: MIA });
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "NUEVA PLANTILLA" }));
  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Pierna" } });
  fireEvent.change(screen.getByLabelText("Ejercicio 1"), { target: { value: "Sentadilla" } });
  fireEvent.change(screen.getByLabelText("Series del ejercicio 1"), { target: { value: "5" } });
  fireEvent.change(screen.getByLabelText("Repeticiones del ejercicio 1"), { target: { value: "5" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() => expect(crearPlantilla).toHaveBeenCalled());
  expect(vi.mocked(crearPlantilla).mock.calls[0][0]).toEqual({
    name: "Pierna",
    mode: "gym",
    exercises: [{ name: "Sentadilla", sets: 5, reps: 5 }],
  });
});

test("una plantilla sin ejercicios no se manda: la API la rechaza", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "NUEVA PLANTILLA" }));
  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Vacía" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  expect(crearPlantilla).not.toHaveBeenCalled();
  expect((await screen.findByRole("alert")).textContent).toContain("al menos un ejercicio");
});

test("borrar pide confirmación", async () => {
  vi.spyOn(window, "confirm").mockReturnValue(false);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "BORRAR LA MÍA" }));
  expect(borrarPlantilla).not.toHaveBeenCalled();
});

test("un fallo al borrar se dice en español y la plantilla sigue en la lista", async () => {
  vi.spyOn(window, "confirm").mockReturnValue(true);
  vi.mocked(borrarPlantilla).mockRejectedValue(
    new (await import("../api")).ErrorApi({ general: "No hemos podido conectar. Inténtalo otra vez.", campos: {} }),
  );
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "BORRAR LA MÍA" }));

  expect((await screen.findByRole("alert")).textContent).toBe("No hemos podido conectar. Inténtalo otra vez.");
  expect(screen.getByText("La mía")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Plantillas`
Esperado: FALLA, no existe `./Plantillas`.

- [ ] **Paso 3 · Escribir la pantalla**

Crear `web/src/pantallas/Plantillas.tsx`. Puntos que el test fija y que no se pueden
perder de vista:

- **`user_id === null` significa del sistema.** Ni EDITAR ni BORRAR: el servidor responde
  403 y ofrecer un botón que va a fallar es peor que no ofrecerlo.
- **Solo nombre, modo, nivel, duración y `exercises[].{name, sets, reps}`.** `PUT` descarta
  lo demás. No se pintan campos de peso, tiempo ni distancia.
- **Al menos un ejercicio**, que es lo que valida la API (`required|array|min:1`). Se
  comprueba antes de mandar para no gastar un viaje en un 422 previsible.
- Los errores salen de `error.fallo.general`, nunca un número.
- Tras crear, editar o borrar se vuelve a pedir la lista: es una llamada y evita mantener
  dos copias del mismo estado.

```tsx
import { useEffect, useState } from "react";
import {
  borrarPlantilla, crearPlantilla, editarPlantilla, plantillas,
  ErrorApi, type Modo, type Plantilla, type PlantillaEditable,
} from "../api";
import { Aviso, Boton, Campo, Comentario, TituloPantalla } from "../componentes";

type Borrador = { id: string | null; name: string; mode: Modo; exercises: { name: string; sets: string; reps: string }[] };

const VACIA: Borrador = { id: null, name: "", mode: "gym", exercises: [{ name: "", sets: "", reps: "" }] };

export default function Plantillas() {
  const [lista, setLista] = useState<Plantilla[]>([]);
  const [editando, setEditando] = useState<Borrador | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  const recargar = () => plantillas().then(setLista).catch(() => undefined);
  useEffect(() => { void recargar(); }, []);

  function textoDe(error: unknown): string {
    return error instanceof ErrorApi && error.fallo.general
      ? error.fallo.general
      : "No hemos podido conectar. Inténtalo otra vez.";
  }

  async function guardar() {
    if (!editando) return;
    const exercises = editando.exercises
      .filter((e) => e.name.trim() !== "")
      .map((e) => ({ name: e.name.trim(), sets: Number(e.sets) || null, reps: Number(e.reps) || null }));

    // La API valida `exercises` como required|array|min:1. Comprobarlo aquí ahorra un
    // viaje y un mensaje que llegaría de vuelta sin decir mucho más.
    if (exercises.length === 0) {
      setFallo("Una plantilla necesita al menos un ejercicio.");
      return;
    }

    const datos: PlantillaEditable = { name: editando.name.trim(), mode: editando.mode, exercises };
    try {
      await (editando.id ? editarPlantilla(editando.id, datos) : crearPlantilla(datos));
      setEditando(null);
      setFallo(null);
      await recargar();
    } catch (error) {
      setFallo(textoDe(error));
    }
  }

  async function borrar(plantilla: Plantilla) {
    if (!confirm(`¿Borrar «${plantilla.name}»?`)) return;
    try {
      await borrarPlantilla(plantilla.id);
      setFallo(null);
      await recargar();
    } catch (error) {
      setFallo(textoDe(error));
    }
  }

  // …el formulario y la lista, con los nombres accesibles que fijan los tests:
  // «Nombre», «Ejercicio N», «Series del ejercicio N», «Repeticiones del ejercicio N»,
  // «NUEVA PLANTILLA», «GUARDAR», «EDITAR <NOMBRE>», «BORRAR <NOMBRE>».
  // Los botones de editar y borrar solo cuando `plantilla.user_id !== null`.
}
```

- [ ] **Paso 4 · Comprobar que pasa**

Ejecutar: `cd web && npm test -- Plantillas`
Esperado: PASAN los 5.

- [ ] **Paso 5 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/pantallas/Plantillas.tsx web/src/pantallas/Plantillas.test.tsx
git commit -m "feat(entreno): crear, editar y borrar plantillas

Las del sistema (user_id null) se ven y se usan pero no enseñan botón de
editar ni de borrar: el servidor responde 403 y ofrecer un botón que va a
fallar es peor que no ofrecerlo.

El formulario solo pide nombre, modo y los ejercicios con sus series y
repeticiones, que es lo único que acepta PUT /api/templates. Los campos de
peso, tiempo y distancia no se pintan porque la API los descarta al primer
guardado y el usuario vería desaparecer lo que acaba de escribir.

Y una plantilla sin ejercicios se para aquí. La API la rechaza igual con un
422, pero gastar el viaje para recibir un mensaje que no aclara mucho más no
tiene sentido."
```

---

## Tarea 16 · `App.tsx`: las rutas y el aviso de pendientes

**Ficheros:**
- Modificar: `web/src/App.tsx`
- Crear: `web/src/App.test.tsx`

**Interfaces:**
- Consume: `pendientes`, `subirPendientes` de `borrador.ts` (tareas 2 y 4); las cuatro
  pantallas nuevas; `Aviso`, `Boton`, `VentanaSistema` de `componentes.tsx`.
- Produce: las rutas `/entrenar`, `/entrenar/sesion`, `/entrenar/resumen` y `/plantillas`.

- [ ] **Paso 1 · Escribir el test que falla**

Crear `web/src/App.test.tsx`:

```tsx
/* El aviso de pendientes vive encima de las pestañas y se ve desde cualquier pantalla,
   porque es una advertencia de pérdida de datos y esas no pueden depender de dónde estés. */

import { render, screen, fireEvent } from "@testing-library/react";
import { beforeEach, expect, test, vi } from "vitest";
import { BrowserRouter } from "react-router";
import App from "./App";
import { encolar, type Sesion } from "./borrador";
import { subirPendientes, usuarioActual } from "./api";

vi.mock("./api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("./api")>()),
  usuarioActual: vi.fn(),
  diaDeHoy: vi.fn().mockRejectedValue(new Error("aquí da igual")),
}));

vi.mock("./borrador", async (importOriginal) => ({
  ...(await importOriginal<typeof import("./borrador")>()),
  subirPendientes: vi.fn().mockResolvedValue([]),
}));

const PENDIENTE: Sesion = {
  v: 1, mode: "gym", nombre: "Torso pesado", inicio: "2026-08-18T17:00:00Z",
  actual: 0, exercises: [], duracion: 45, notas: null,
};

beforeEach(() => {
  localStorage.clear();
  vi.mocked(usuarioActual).mockResolvedValue({ name: "Isra", email: "isra@local.test", is_admin: false });
});

const pintar = () => render(<BrowserRouter><App /></BrowserRouter>);

test("un entreno sin subir se avisa, en singular", async () => {
  encolar(PENDIENTE);
  pintar();

  expect((await screen.findByRole("status")).textContent).toContain("1 entreno pendiente de subir");
});

test("sin nada pendiente no hay aviso que estorbe", async () => {
  pintar();
  await screen.findByRole("navigation");
  expect(screen.queryByRole("status")).toBe(null);
});

test("al recuperar la conexión se reintenta solo", async () => {
  encolar(PENDIENTE);
  pintar();
  await screen.findByRole("status");

  window.dispatchEvent(new Event("online"));

  await vi.waitFor(() => expect(subirPendientes).toHaveBeenCalled());
});

test("si al reintentar el entreno traía un récord, sale su ventana del Sistema", async () => {
  encolar(PENDIENTE);
  vi.mocked(subirPendientes).mockResolvedValue([
    {
      new_records: [],
      system: {
        xp_gained: 80, level_up: { from: 4, to: 5 }, rank_up: null,
        achievements_unlocked: [], records: [], quests_completed: [],
        progress: {} as never,
      },
    } as never,
  ]);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "REINTENTAR" }));

  expect(await screen.findByRole("dialog")).toBeTruthy();
  expect(screen.getByText("Nivel 5")).toBeTruthy();
});
```

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- App`
Esperado: FALLA: no existe el aviso.

- [ ] **Paso 3 · Añadir el aviso y el reintento**

En `web/src/App.tsx`, un componente nuevo encima de `App`:

```tsx
/** Vive encima de las pestañas y no dentro de una pantalla: es una advertencia de pérdida
 *  de datos y esas se ven desde donde se esté. (El borrador a medias es otra cosa y va en
 *  «hoy», porque se retoma yendo a entrenar.)
 *
 *  Se reintenta al recuperar la conexión, al abrir la aplicación y con el botón. Sin
 *  retroceso exponencial: al otro lado hay un hosting compartido con un usuario. */
function AvisoPendientes() {
  const [cuantos, setCuantos] = useState(() => pendientes().length);
  const [premio, setPremio] = useState<BloqueSistema | null>(null);
  const [subiendo, setSubiendo] = useState(false);

  const reintentar = useCallback(async () => {
    if (subiendo || pendientes().length === 0) return;
    setSubiendo(true);
    const subidos = await subirPendientes();
    // Si algo de lo que subió solo traía nivel, rango, logro o récord, se celebra igual
    // que si se hubiera subido en el momento. Es el mismo componente.
    const conPremio = subidos.find(
      (e) => e.system.level_up || e.system.rank_up || e.system.achievements_unlocked.length || e.system.records.length,
    );
    if (conPremio) setPremio(conPremio.system);
    setCuantos(pendientes().length);
    setSubiendo(false);
  }, [subiendo]);

  useEffect(() => {
    void reintentar();
    window.addEventListener("online", () => void reintentar());
    return () => window.removeEventListener("online", () => void reintentar());
  }, []);

  if (cuantos === 0 && !premio) return null;

  return (
    <>
      {cuantos > 0 && (
        <div className="pendientes">
          <Aviso tono="ambar">
            {cuantos === 1 ? "1 entreno pendiente de subir" : `${cuantos} entrenos pendientes de subir`}
          </Aviso>
          <Boton type="button" compacto disabled={subiendo} onClick={() => void reintentar()}>
            {subiendo ? "SUBIENDO…" : "REINTENTAR"}
          </Boton>
        </div>
      )}
      {premio && <VentanaSistema sistema={premio} alCerrar={() => setPremio(null)} />}
    </>
  );
}
```

⚠️ **El `removeEventListener` de arriba no quita nada**: `() => void reintentar()` crea una
función distinta cada vez. Guardar la referencia:

```tsx
  useEffect(() => {
    const alVolverLaRed = () => void reintentar();
    void reintentar();
    window.addEventListener("online", alVolverLaRed);
    return () => window.removeEventListener("online", alVolverLaRed);
  }, [reintentar]);
```

- [ ] **Paso 4 · Montar las rutas**

Dentro de `ConPestanas`, encima del `<Outlet />`:

```tsx
      <AvisoPendientes />
```

Y en el bloque de rutas con sesión abierta:

```tsx
        <Route path="/entrenar" element={<Elegir />} />
        <Route path="/entrenar/sesion" element={<Sesion />} />
        <Route path="/entrenar/resumen" element={<Resumen />} />
        <Route path="/plantillas" element={<Plantillas />} />
```

Van **dentro** de `ConPestanas`: son sub-pantallas de `hoy` (spec §8) y la navegación no
desaparece por estar entrenando.

- [ ] **Paso 5 · Añadir el CSS**

```css
.pendientes {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0 0.25rem;
}

.pendientes .aviso {
  margin-top: 0;
}
```

- [ ] **Paso 6 · Comprobar que pasa**

Ejecutar: `cd web && npm test`
Esperado: toda la suite en verde.

- [ ] **Paso 7 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/App.tsx web/src/App.test.tsx web/src/estilos.css
git commit -m "feat(entreno): las rutas del módulo y el aviso de entrenos sin subir

El aviso vive encima de las pestañas y no dentro de una pantalla: es una
advertencia de pérdida de datos y esas se ven desde donde se esté. El
borrador a medias es otra cosa y va en «hoy», porque se retoma yendo a
entrenar.

Se reintenta al abrir la aplicación, al recuperar la conexión y con el
botón. Sin retroceso exponencial: al otro lado hay un hosting compartido con
un usuario, no un servicio que haya que proteger de una estampida.

Y si algo de lo que sube solo traía nivel, rango, logro o récord, se celebra
igual que si se hubiera subido en el momento. Es el mismo componente y una
llamada más."
```

---

## Tarea 17 · `Hoy.tsx`: retomar el borrador y el entreno de hoy

**Ficheros:**
- Modificar: `web/src/pantallas/Hoy.tsx`
- Modificar: `web/src/pantallas/Hoy.test.tsx`

**Interfaces:**
- Consume: `leer`, `borrar` de `borrador.ts`; `textoAntiguedad`, `seriesHechas` de
  `formato.ts`; el `suggested_workout` que ya trae `diaDeHoy`.
- Produce: nada nuevo hacia fuera.

- [ ] **Paso 1 · Escribir el test que falla**

Añadir al final de `web/src/pantallas/Hoy.test.tsx`:

```tsx
test("un entreno a medias se ofrece con su antigüedad y lo que lleva", async () => {
  vi.setSystemTime(new Date("2026-08-18T17:12:00Z"));
  guardar({
    v: 1, mode: "gym", nombre: "Torso pesado", inicio: "2026-08-18T17:00:00Z", actual: 0,
    exercises: [{
      name: "Press banca", objetivo: null,
      sets: [
        { weight_kg: 80, reps: 5, rpe: null, distance_m: null, time_seconds: null, style: null, rest_seconds: 90, hecha: true },
        { weight_kg: 80, reps: 5, rpe: null, distance_m: null, time_seconds: null, style: null, rest_seconds: 90, hecha: true },
      ],
    }],
  });
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  render(<Hoy usuario={USUARIO} />);

  expect(await screen.findByText(/Torso pesado/)).toBeTruthy();
  expect(screen.getByText(/2 series/)).toBeTruthy();
  expect(screen.getByText(/de hace 12 minutos/)).toBeTruthy();
  expect(screen.getByRole("link", { name: "SEGUIR ENTRENANDO" })).toBeTruthy();
});

test("un borrador de anteayer se ofrece igual, no se tira solo", async () => {
  vi.setSystemTime(new Date("2026-08-20T10:00:00Z"));
  guardar({ /* …el mismo, inicio 2026-08-18T17:00:00Z… */ } as never);
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  render(<Hoy usuario={USUARIO} />);

  // Que sea viejo no lo hace basura: puede ser justo el que hay que recuperar.
  expect(await screen.findByText(/de hace 1 día|de ayer|de hace 2 días/)).toBeTruthy();
  expect(screen.getByRole("link", { name: "SEGUIR ENTRENANDO" })).toBeTruthy();
});

test("descartar el borrador pide confirmación", async () => {
  guardar({ /* …uno cualquiera… */ } as never);
  vi.spyOn(window, "confirm").mockReturnValue(false);
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  render(<Hoy usuario={USUARIO} />);

  fireEvent.click(await screen.findByRole("button", { name: "DESCARTAR" }));

  // Es la única forma de perder un entreno a propósito, y tiene que costar dos pulsaciones.
  expect(leer()).not.toBe(null);
});

test("sin borrador se ofrece empezar, con el motivo que manda el servidor", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  render(<Hoy usuario={USUARIO} />);

  expect(await screen.findByText("Te faltan 2 entrenos para tu meta de esta semana.")).toBeTruthy();
  expect(screen.getByRole("link", { name: "ENTRENAR" })).toBeTruthy();
});
```

Ampliar los imports con `guardar`, `leer` de `../borrador` y `fireEvent`.

- [ ] **Paso 2 · Comprobar que falla**

Ejecutar: `cd web && npm test -- Hoy`
Esperado: FALLAN los 4 nuevos; los 6 de la fase 1.1 siguen pasando.

- [ ] **Paso 3 · Añadir la sección**

En `web/src/pantallas/Hoy.tsx`, dentro del componente:

```tsx
  // El borrador se lee una vez al montar. Si cambia, es porque el usuario está en la
  // sesión, y al volver aquí la pantalla se monta otra vez.
  const [borrador, setBorrador] = useState(() => leer());

  function descartar() {
    // La única forma de perder un entreno a propósito. Dos pulsaciones, a propósito.
    if (!confirm("¿Descartar el entreno a medias? No se puede recuperar.")) return;
    borrar();
    setBorrador(null);
  }
```

Y una sección debajo de las misiones:

```tsx
      <Seccion titulo="Entreno de hoy" resumen={borrador ? "a medias" : "sin empezar"}>
        {borrador ? (
          <>
            <Comentario>
              {borrador.nombre} · {seriesHechas(borrador.exercises)} series ·{" "}
              {textoAntiguedad(borrador.inicio)}
            </Comentario>
            <div className="acciones">
              <Link className="boton compacto" to="/entrenar/sesion">
                <span aria-hidden="true">[ </span>SEGUIR ENTRENANDO<span aria-hidden="true"> ]</span>
              </Link>
              <Boton type="button" compacto onClick={descartar}>
                DESCARTAR
              </Boton>
            </div>
          </>
        ) : (
          <>
            <Comentario>{datos.suggested_workout.reason}</Comentario>
            <div className="acciones">
              <Link className="boton compacto" to="/entrenar">
                <span aria-hidden="true">[ </span>ENTRENAR<span aria-hidden="true"> ]</span>
              </Link>
            </div>
          </>
        )}
      </Seccion>
```

Son `<Link>` y no botones con `navigate` porque son navegación: así se pueden abrir en otra
pestaña, se ven en la barra de estado al mantener pulsado y el navegador los trata como lo
que son.

- [ ] **Paso 4 · Añadir el CSS**

```css
a.boton {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
}
```

- [ ] **Paso 5 · Comprobar que pasa**

Ejecutar: `cd web && npm test`
Esperado: toda la suite en verde.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add web/src/pantallas/Hoy.tsx web/src/pantallas/Hoy.test.tsx web/src/estilos.css
git commit -m "feat(entreno): «hoy» ofrece retomar el entreno a medias

Con su nombre, las series que lleva y de cuándo es. Un borrador viejo se
ofrece igual: que sea de anteayer no lo hace basura, puede ser justo el que
hay que recuperar, así que no se descarta solo nunca.

Descartar pide confirmación. Es la única forma de perder un entreno a
propósito y tiene que costar dos pulsaciones.

Sin borrador, la sección enseña el motivo que ya manda /system/today y el
enlace para empezar. Son enlaces y no botones porque son navegación: se
pueden abrir en otra pestaña y el navegador los trata como lo que son."
```

---

## Tarea 18 · Comprobarlo en el móvil y cerrar la fase

Los dos criterios más importantes de la fase **no los demuestra ningún test**: `jsdom` no
tiene red que cortar ni proceso que matar. Esta tarea no es opcional y la fase no está
cerrada sin ella.

**Ficheros:**
- Modificar: `docs/superpowers/fases/fase-1.2-entrenamiento.md`
- Modificar: `docs/superpowers/fases/README.md`
- Modificar: `CLAUDE.md`

- [ ] **Paso 1 · Toda la suite y los tipos**

```bash
cd web && npm test && npm run build && npm run lint
```

Esperado: verde en los tres. Si `npm run build` se queja, es `tsc -b`: los tests que pasan
no demuestran que compile.

- [ ] **Paso 2 · Probarlo en local con el backend de verdad**

```bash
cd backend && php artisan serve --port=8000    # en una terminal
cd web && npm run dev                          # en otra
```

Entrar con `isra@local.test` / `contrasena8`. Si sale un 429, el limitador cuenta por IP y
a través del proxy de Vite todo viene de `127.0.0.1`:

```bash
mysql -u srank -psrank srank_local -e "DELETE FROM cache;"
```

⚠️ La base local no tiene ni un entreno, solo las 32 plantillas sembradas. «Repetir el
último» dirá que no hay entreno anterior hasta que se guarde uno.

- [ ] **Paso 3 · Las seis comprobaciones a mano, en el navegador**

1. Empezar desde una plantilla, apuntar tres series, **recargar la página**. Sigue todo.
2. Guardar un entreno de más de 15 minutos. Sale el XP y, si toca, la ventana del Sistema.
3. Guardar un tercer entreno el mismo día. Se guarda y explica que no puntúa, sin rojo.
4. Batir un récord. Se anuncia al marcar la serie y otra vez en el resumen.
5. Crear, editar y borrar una plantilla propia. Y comprobar que una del sistema **no**
   enseña esos botones.
6. **Salir de la aplicación** y comprobar en la base que la sesión se cerró de verdad
   (tarea 6): `SELECT COUNT(*) FROM sessions;` antes y después.

- [ ] **Paso 4 · Y en el móvil, con la PWA instalada**

Esto es lo que decide si la fase está terminada:

```bash
cd web && npm run build
cd ../backend && bash build-deploy.sh
```

Subir `deploy/` por FTP y, desde el móvil con la aplicación instalada:

1. **Modo avión.** Empezar un entreno, apuntar cuatro series de dos ejercicios, terminar
   y guardar. Tiene que decir que se subirá solo.
2. **Quitar el modo avión.** El entreno sube solo, el aviso desaparece y si traía premio
   sale la ventana del Sistema.
3. **Matar la aplicación** desde el conmutador de apps con una sesión a medias. Volver a
   abrirla: «hoy» ofrece retomarla con todo lo apuntado.
4. Comprobar que **no queda ningún entreno duplicado** en el historial de la base.

- [ ] **Paso 5 · Apuntar lo que se haya desviado y cerrar la fase**

En `docs/superpowers/fases/fase-1.2-entrenamiento.md`, marcar el estado como **hecha** con
la fecha, y anotar cualquier desviación nueva sobre las cuatro que ya recoge el §7 del
spec. En `docs/superpowers/fases/README.md` y en la tabla de `CLAUDE.md`, pasar la 1.2 a
**hecha** y la 1.3 a **la siguiente**.

Si algo de los pasos 3 o 4 falló, **no se cierra**: se arregla con su test y se vuelve a
probar en el móvil.

- [ ] **Paso 6 · Commit**

```bash
cd /mnt/c/Users/pc2/Documents/1_propio/f01
git add docs/ CLAUDE.md
git commit -m "docs: la fase 1.2 queda cerrada

Comprobado en el móvil con la PWA instalada, que es lo único que demuestra
los dos criterios que importan: un entreno entero en modo avión que sube
solo al recuperar la red, y matar la aplicación a media sesión sin perder
nada. jsdom no tiene ni red que cortar ni proceso que matar, así que la
suite en verde no decía nada de eso."
```

---

## Repaso del plan

**Cobertura del spec.** Las nueve secciones tienen tarea: §2.1–2.4 → tareas 1 y 2;
§2.5 → tarea 4; §2.6 → tareas 1 y 9; §4.1 → tarea 3; §4.2–4.4 → tarea 6; §5.1 → tarea 13;
§5.2 → tareas 9, 10 y 14; §5.3 → tarea 11; §5.4 → tarea 12; §5.5 → tarea 17;
§5.6 → tarea 16; §5.7 → tarea 15; §6 → repartido; §7 y §8 → tarea 18.

**Dos avisos para quien ejecute:**

1. **La tarea 4 depende de la 6.** `borrador.ts` importa `guardarEntreno` y `entrenos` de
   `api.ts`. Hacer la 6 antes que la 4.
2. **La tarea 6 rompe `Hoy.test.tsx` a propósito** al declarar `suggested_workout` como
   obligatorio en `DiaDeHoy`. El arreglo está en el paso 7 de esa misma tarea.

**Tres cosas que este plan da por ciertas y conviene no volver a discutir:** el bloque
`system` devuelve los récords con la forma `{exercise, kind, value, previous}` del spec
§5.3 y no con la de `new_records`, y es esa la que lee la interfaz;
`max_weight` puede llegar como cadena; y `PUT /api/templates` descarta todo lo que no sea
nombre, series y reps.

