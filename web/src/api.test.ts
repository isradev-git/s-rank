/* La traducción de errores del spec §8.1, un caso por fila de la tabla.
   Es lo único que decide qué lee el usuario cuando algo va mal, y la regla que sostiene
   todo esto —ningún código HTTP en pantalla— se rompe sin hacer ruido. */

import { afterEach, expect, test, vi } from "vitest";
import { ErrorApi, SESION_CADUCADA, pedir, salir, sesionAbierta } from "./api";

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

test("entrar bien y que el servidor siga diciendo que no hay nadie es un fallo con nombre", async () => {
  // Pasó en producción el 18 de agosto: el login contestaba 200 y el /api/user siguiente
  // 401, porque el navegador había dejado de mandar la cabecera Referer y Sanctum ya no
  // reconocía la petición como del frontend. Aquello se arregló en el .htaccess, pero el
  // silencio no: la pantalla se quedaba con el botón en «ENTRANDO…» para siempre.
  servidorQueResponde(401, { message: "Unauthenticated." });

  const fallo = await falloDe(sesionAbierta());

  expect(fallo.general).toMatch(/no ha guardado la sesión/);
  expect(fallo.general).not.toMatch(/\d{3}/);
});

test("toda petición manda Accept: application/json", async () => {
  // Sin esa cabecera el servidor devuelve HTML en los errores, y el cliente intenta
  // leerlo como JSON.
  servidorQueResponde(200, { ok: true });

  await pedir("/system/today");

  const [, opciones] = vi.mocked(fetch).mock.calls[0];
  expect((opciones?.headers as Record<string, string>).Accept).toBe("application/json");
});

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
  servidorQueResponde(200, { foods: [{ id: 1, name: "Pollo" }] });

  const encontrados = await buscarAlimentos("aceite & sal");

  expect(encontrados).toHaveLength(1);
  // Sin escapar, un «&» en el texto cortaría el parámetro y la búsqueda saldría con
  // otra cosa. `vi.mocked(fetch)` y no el mock suelto: así los argumentos vienen
  // tipados como los de fetch y `calls[0][0]` existe.
  expect(vi.mocked(fetch).mock.calls[0][0]).toContain("aceite%20%26%20sal");
});

import { subir } from "./api";

test("subir no pone Content-Type a mano: lo pone el navegador con su boundary", async () => {
  servidorQueResponde(200, { image_path: "nutrition/foods/x.jpg" });
  document.cookie = "XSRF-TOKEN=abc123";

  await subir("/foods/1/image", "image", new File(["x"], "x.jpg", { type: "image/jpeg" }));

  const opciones = vi.mocked(fetch).mock.calls[0][1] as RequestInit;
  const cabeceras = opciones.headers as Record<string, string>;

  // Con Content-Type puesto a mano, el boundary se pierde y el servidor no encuentra
  // ningún fichero: responde 422 diciendo que «image» es obligatorio.
  expect(cabeceras["Content-Type"]).toBeUndefined();
  // Y el token sí tiene que ir, o toda escritura contesta 419.
  expect(cabeceras["X-XSRF-TOKEN"]).toBe("abc123");
  expect(opciones.body).toBeInstanceOf(FormData);
});
