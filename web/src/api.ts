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

  if (cuerpo !== undefined) {
    cabeceras["Content-Type"] = "application/json";
    cabeceras["X-XSRF-TOKEN"] = await asegurarCsrf();
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
  });
}

// ── Autenticación ───────────────────────────────────────────────────────────

export type Usuario = { name: string; email: string; is_admin: boolean };

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

export type DiaDeHoy = {
  /** Ya viene como fecha suelta, calculada por el servidor en Europe/Madrid. */
  date: string;
  progress: Progreso;
  quests: Mision[];
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
    return await pedir<Usuario>("/user", { avisarSiCaduca: false });
  } catch {
    return null;
  }
}
