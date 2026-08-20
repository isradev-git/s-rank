/* La única puerta a la API. Traduce los errores una vez, aquí, y las pantallas enseñan
   el texto ya hecho: ningún número de error llega nunca a la interfaz.

   No hay token en ningún sitio. La sesión viaja en una cookie httpOnly que el navegador
   manda sola y que JavaScript no puede leer, así que un XSS no tiene nada que robar. */

/** Lo que una pantalla necesita saber cuando algo sale mal. */
export type Fallo = {
  /** El mensaje que va arriba del formulario. */
  general: string | null;
  /** Los de validación, uno por campo, ya capitalizados. */
  campos: Record<string, string>;
  /** Qué petición se rompió y con qué código. Va aparte del mensaje a propósito: el
   *  usuario lee `general`, que nunca lleva números, y esto es para mirarlo cuando algo
   *  se cae. Se pinta como comentario, que es donde va lo secundario.
   *
   *  ponytail: existe porque en el móvil no hay consola donde leer el 500 y el motivo
   *  solo está en el log del servidor, al que no se llega sin FTP. Se quita cuando la
   *  fase 1.3 esté comprobada, o se queda si vuelve a hacer falta. */
  detalle?: string;
};

export class ErrorApi extends Error {
  // Declarado a mano y no como propiedad del constructor: `erasableSyntaxOnly` obliga a
  // que quitar los tipos deje JavaScript válido, y esa forma abreviada genera código.
  readonly fallo: Fallo;

  constructor(fallo: Fallo) {
    super(fallo.general ?? "La petición no salió bien");
    this.name = "ErrorApi";
    this.fallo = fallo;
  }
}

/** Un 401 significa que la sesión ya no vale, venga de la pantalla que venga. Se avisa
    una vez y lo recoge la raíz de la aplicación, que es la que sabe navegar. */
export const SESION_CADUCADA = "srank:sesion-caducada";

function cookie(nombre: string): string | null {
  const prefijo = `${nombre}=`;
  const par = document.cookie.split("; ").find((c) => c.startsWith(prefijo));
  return par ? decodeURIComponent(par.slice(prefijo.length)) : null;
}

/** Laravel exige el token CSRF en toda escritura. Lo reparte en una cookie legible, que
    es distinta de la de sesión: esta se puede leer a propósito, y por sí sola no
    autentica a nadie. */
async function asegurarCsrf(): Promise<string> {
  let token = cookie("XSRF-TOKEN");
  if (!token) {
    await fetch("/sanctum/csrf-cookie", { credentials: "same-origin" });
    token = cookie("XSRF-TOKEN");
  }
  return token ?? "";
}

function capitalizar(texto: string): string {
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

type Opciones = {
  metodo?: "GET" | "POST" | "PUT" | "DELETE";
  cuerpo?: unknown;
  /** Al abrir la app se pregunta quién hay, y un 401 ahí solo significa «todavía nadie».
      No es una sesión que haya caducado, así que esa petición no avisa. */
  avisarSiCaduca?: boolean;
};

export async function pedir<T>(
  ruta: string,
  { metodo = "GET", cuerpo, avisarSiCaduca = true }: Opciones = {},
): Promise<T> {
  const cabeceras: Record<string, string> = {
    // Sin esta cabecera el servidor devuelve HTML en los errores en vez de JSON.
    Accept: "application/json",
  };

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

  let respuesta: Response;
  try {
    respuesta = await fetch(`/api${ruta}`, {
      method: metodo,
      credentials: "same-origin",
      headers: cabeceras,
      body: cuerpo === undefined ? undefined : JSON.stringify(cuerpo),
    });
  } catch {
    // fetch solo rechaza si la petición no llegó a salir: no hay red, o el servidor
    // no responde. Cualquier respuesta del servidor, del 400 al 500, resuelve.
    throw new ErrorApi({
      general: "No hay conexión. Comprueba el wifi o los datos.",
      campos: {},
    });
  }

  if (respuesta.ok) {
    return respuesta.status === 204 ? (undefined as T) : ((await respuesta.json()) as T);
  }

  if (respuesta.status === 401) {
    if (avisarSiCaduca) window.dispatchEvent(new Event(SESION_CADUCADA));
    throw new ErrorApi({ general: "Tu sesión ha caducado. Vuelve a entrar.", campos: {} });
  }

  if (respuesta.status === 429) {
    // El limitador de Laravel responde «Too Many Attempts.» antes de entrar en la ruta,
    // así que no pasa por lang/es y el texto lo pone la aplicación.
    throw new ErrorApi({
      general: "Demasiados intentos. Espera un momento y vuelve a probar.",
      campos: {},
    });
  }

  if (respuesta.status === 422) {
    const datos = (await respuesta.json()) as {
      message?: string;
      errors?: Record<string, string[]>;
    };
    const campos: Record<string, string> = {};
    for (const [campo, mensajes] of Object.entries(datos.errors ?? {})) {
      if (mensajes[0]) campos[campo] = capitalizar(mensajes[0]);
    }
    // Un 422 sin `errors` no debería pasar, pero si pasa el usuario merece un texto.
    const general = Object.keys(campos).length > 0 ? null : "Revisa los datos e inténtalo otra vez.";
    throw new ErrorApi({ general, campos });
  }

  throw new ErrorApi({
    general: "No hemos podido conectar. Inténtalo otra vez.",
    campos: {},
    detalle: `${metodo} ${ruta} → ${respuesta.status}`,
  });
}

// ── Autenticación ───────────────────────────────────────────────────────────

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

// ── El Sistema ──────────────────────────────────────────────────────────────

export type Progreso = {
  level: number;
  rank: string;
  xp_into_level: number;
  xp_for_next: number;
  current_streak: number;
};

export type Mision = {
  key: string;
  label: string;
  target: number | null;
  progress: number | null;
  xp_reward: number;
  is_optional: boolean;
  completed: boolean;
};

export type EntrenoSugerido = {
  /** Ya viene escrito en castellano: «Te faltan 2 entrenos para tu meta de esta semana». */
  reason: string;
  weekly_done: number;
  weekly_goal: number;
  template: Plantilla | null;
};

export type DiaDeHoy = {
  /** Ya viene como fecha suelta, calculada por el servidor en Europe/Madrid. */
  date: string;
  progress: Progreso;
  quests: Mision[];
  suggested_workout: EntrenoSugerido;
};

export function diaDeHoy() {
  return pedir<DiaDeHoy>("/system/today");
}

export function entrar(email: string, password: string) {
  return pedir<{ user_name: string }>("/auth/login", {
    metodo: "POST",
    cuerpo: { email, password },
  });
}

export function registrar(name: string, email: string, password: string) {
  return pedir<{ user_name: string }>("/auth/register", {
    metodo: "POST",
    cuerpo: { name, email, password },
  });
}

/** Pide el código de seis cifras.
 *
 *  Responde 200 exista o no el correo, a propósito: un mensaje distinto por caso
 *  convertiría este endpoint en una lista de cuentas válidas. La pantalla tiene que
 *  avanzar igual en los dos casos, así que aquí no hay nada que mirar en la respuesta. */
export function pedirCodigo(email: string) {
  return pedir<{ message: string }>("/auth/forgot-password", {
    metodo: "POST",
    cuerpo: { email },
  });
}

export function cambiarContrasena(email: string, code: string, password: string) {
  return pedir<{ message: string }>("/auth/reset-password", {
    metodo: "POST",
    cuerpo: { email, code, password },
  });
}

export function salir() {
  return pedir<{ message: string }>("/auth/logout", { metodo: "POST" });
}

/** Quién hay al otro lado, o null si no hay sesión. Es lo que decide, al abrir la app,
    si se enseña «hoy» o el login. */
export async function usuarioActual(): Promise<Usuario | null> {
  try {
    const crudo = await pedir<Usuario>("/user", { avisarSiCaduca: false });
    return { ...crudo, weight: crudo.weight == null ? null : Number(crudo.weight) };
  } catch {
    return null;
  }
}

/** Igual que `usuarioActual`, pero para después de entrar o de registrarse: ahí el
 *  servidor tiene que reconocernos, y que diga que no hay nadie no es «todavía nadie»,
 *  es un fallo.
 *
 *  Pasa cuando el navegador no guarda la cookie de sesión que el login acaba de dar. Se
 *  vio en producción el 18 de agosto con `Referrer-Policy: no-referrer` en el servidor:
 *  login 200 y el /api/user siguiente 401. Sin este error, la pantalla de entrar no tiene
 *  nada que enseñar y se queda con el botón puesto en «ENTRANDO…» para siempre. */
export async function sesionAbierta(): Promise<Usuario> {
  const quien = await usuarioActual();
  if (!quien) {
    throw new ErrorApi({
      general:
        "Has entrado, pero tu navegador no ha guardado la sesión. Comprueba que permite cookies y vuelve a intentarlo.",
      campos: {},
    });
  }
  return quien;
}

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
 *  2. **El `X-XSRF-TOKEN` sí va**, como en toda escritura, o Laravel contesta 419. */
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
