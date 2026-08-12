/* La traducción de errores del spec §8.1, un caso por fila de la tabla.
   Es lo único que decide qué lee el usuario cuando algo va mal, y la regla que sostiene
   todo esto —ningún código HTTP en pantalla— se rompe sin hacer ruido. */

import { afterEach, expect, test, vi } from "vitest";
import { ErrorApi, SESION_CADUCADA, pedir } from "./api";

/** Sustituye a fetch por uno que devuelve lo que se le diga. */
function servidorQueResponde(estado: number, cuerpo: unknown = {}) {
  vi.stubGlobal(
    "fetch",
    vi.fn(async () =>
      new Response(JSON.stringify(cuerpo), {
        status: estado,
        headers: { "Content-Type": "application/json" },
      }),
    ),
  );
}

afterEach(() => {
  vi.unstubAllGlobals();
});

async function falloDe(promesa: Promise<unknown>) {
  try {
    await promesa;
  } catch (error) {
    if (error instanceof ErrorApi) return error.fallo;
    throw error;
  }
  throw new Error("se esperaba un ErrorApi y la petición salió bien");
}

test("sin red se dice que no hay conexión, no que falló una petición", async () => {
  vi.stubGlobal("fetch", vi.fn(async () => { throw new TypeError("Failed to fetch"); }));

  const fallo = await falloDe(pedir("/system/today"));

  expect(fallo.general).toBe("No hay conexión. Comprueba el wifi o los datos.");
});

test("el 429 lo escribe la aplicación, porque el limitador contesta en inglés", async () => {
  servidorQueResponde(429, { message: "Too Many Attempts." });

  const fallo = await falloDe(pedir("/auth/login", { metodo: "POST", cuerpo: {} }));

  expect(fallo.general).toBe("Demasiados intentos. Espera un momento y vuelve a probar.");
  expect(fallo.general).not.toContain("Too Many");
});

test("un 422 reparte los mensajes por campo y los capitaliza", async () => {
  // Laravel los manda en minúscula porque van debajo de un campo.
  servidorQueResponde(422, {
    message: "The given data was invalid.",
    errors: { email: ["credenciales incorrectas."], password: ["el campo es obligatorio."] },
  });

  const fallo = await falloDe(pedir("/auth/login", { metodo: "POST", cuerpo: {} }));

  expect(fallo.campos.email).toBe("Credenciales incorrectas.");
  expect(fallo.campos.password).toBe("El campo es obligatorio.");
  // Con mensajes de campo no hay mensaje general: colgarlo arriba lo diría dos veces.
  expect(fallo.general).toBeNull();
});

test("un 422 sin detalle deja igualmente algo que leer", async () => {
  servidorQueResponde(422, { message: "The given data was invalid." });

  const fallo = await falloDe(pedir("/auth/login", { metodo: "POST", cuerpo: {} }));

  expect(fallo.general).toBe("Revisa los datos e inténtalo otra vez.");
});

test("un 500 no enseña ningún número", async () => {
  servidorQueResponde(500, { message: "Server Error" });

  const fallo = await falloDe(pedir("/system/today"));

  expect(fallo.general).toBe("No hemos podido conectar. Inténtalo otra vez.");
  expect(fallo.general).not.toMatch(/\d{3}/);
});

test("un 401 avisa de que la sesión ha caducado", async () => {
  servidorQueResponde(401, { message: "Unauthenticated." });
  const avisado = vi.fn();
  window.addEventListener(SESION_CADUCADA, avisado);

  await falloDe(pedir("/system/today"));

  expect(avisado).toHaveBeenCalledOnce();
  window.removeEventListener(SESION_CADUCADA, avisado);
});

test("preguntar quién hay al arrancar no avisa de sesión caducada", async () => {
  // Un 401 ahí solo significa «todavía nadie». Avisar mandaría al login a quien ya
  // estaba en el login, y en la pantalla de entrar diría que ha caducado algo que
  // nunca existió.
  servidorQueResponde(401, { message: "Unauthenticated." });
  const avisado = vi.fn();
  window.addEventListener(SESION_CADUCADA, avisado);

  await falloDe(pedir("/user", { avisarSiCaduca: false }));

  expect(avisado).not.toHaveBeenCalled();
  window.removeEventListener(SESION_CADUCADA, avisado);
});

test("toda petición manda Accept: application/json", async () => {
  // Sin esa cabecera el servidor devuelve HTML en los errores, y el cliente intenta
  // leerlo como JSON.
  servidorQueResponde(200, { ok: true });

  await pedir("/system/today");

  const [, opciones] = vi.mocked(fetch).mock.calls[0];
  expect((opciones?.headers as Record<string, string>).Accept).toBe("application/json");
});
