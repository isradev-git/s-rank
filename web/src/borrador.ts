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

import { entrenos, guardarEntreno, type EntrenoGuardado } from "./api";

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
  /** Los minutos que el usuario confirmó al terminar. Solo lo tienen las sesiones que ya
   *  están en la cola: al reintentar no se puede recalcular, porque el reloj ha seguido
   *  corriendo y la duración es lo que decide el XP. */
  duracion?: number;
  /** Las notas que escribió al terminar, por el mismo motivo. */
  notas?: string | null;
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
 *  es único por sesión y es lo mismo que el servidor guarda en `date`.
 *
 *  Descarta el resultado de `escribir()` porque si falla, el entreno se queda en la cola
 *  y el siguiente reintento de subida, con la deduplicación de la tarea 4, lo encuentra
 *  ya en el servidor y lo descarta. Se cura solo. */
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

/** Descarta el resultado de `escribir()` porque es una preferencia: si falla la escritura,
 *  la próxima serie propone el descanso inicial (`DESCANSO_INICIAL`) en vez del último
 *  usado. Es molesto, pero no pierde ningún dato ni entreno. */
export function guardarDescansoPorDefecto(segundos: number): void {
  escribir(DESCANSO, segundos);
}

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

/** Sube el entreno o lo encola. Devuelve lo que respondió el servidor, o null si no hubo
 *  manera y quedó guardado en el móvil.
 *
 *  En los dos casos el dato está a salvo: subido, o en la cola. Por eso quien llama puede
 *  borrar el borrador después, y es la única transición que lo borra. */
export async function entregar(
  sesion: Sesion,
  duracionMinutos: number,
  notas: string | null,
): Promise<EntrenoGuardado | null> {
  try {
    return await guardarEntreno(aPayload(sesion, duracionMinutos, notas));
  } catch {
    encolar({ ...sesion, duracion: duracionMinutos, notas });
    return null;
  }
}

/** Vacía la cola. Devuelve lo que se subió, para que quien llame pueda enseñar la ventana
 *  del Sistema de un entreno que subió solo.
 *
 *  Sin retroceso exponencial: al otro lado hay un hosting compartido con un usuario, no
 *  un servicio que haya que proteger de una estampida. Se reintenta al recuperar la
 *  conexión, al abrir la aplicación y con un botón. */
export async function subirPendientes(): Promise<EntrenoGuardado[]> {
  const cola = pendientes();
  if (cola.length === 0) return [];

  let recientes: { date: string }[];
  try {
    recientes = await entrenos({ per_page: RECIENTES });
    // Una respuesta que no es un array no dice nada fiable sobre lo que ya está subido.
    // Tratarla como fallo evita una excepción sin capturar en el `.some` de `yaSubido` y,
    // sobre todo, evita vaciar la cola sin haber podido comprobar nada.
    if (!Array.isArray(recientes)) throw new Error("respuesta inesperada de /workouts");
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
      // La duración y las notas son las que se confirmaron al terminar, no una
      // recalculada ahora: el reloj ha seguido corriendo y la duración decide el XP.
      subidos.push(
        await guardarEntreno(aPayload(sesion, sesion.duracion ?? 0, sesion.notas ?? null)),
      );
      quitarDePendientes(sesion.inicio);
    } catch {
      // Sigue sin haber manera. Se queda en la cola y se prueba en el próximo intento.
      break;
    }
  }

  return subidos;
}
