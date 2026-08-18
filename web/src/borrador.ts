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
