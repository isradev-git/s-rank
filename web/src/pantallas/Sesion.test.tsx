/* La sesión activa es lo único irrecuperable de la aplicación. Estos tests comprueban lo
   que la fase pone como criterio: que matar la app a mitad no pierda nada.

   Lo que NO demuestran, y hay que probar en el móvil: que funcione de verdad en modo
   avión y que sobreviva a que el sistema mate el proceso. jsdom no tiene ni red que
   cortar ni proceso que matar. */

import { StrictMode } from "react";
import { render, screen, fireEvent } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { entregar, guardar, leer, pendientes, type Sesion as TipoSesion } from "../borrador";
import {
  ErrorApi,
  catalogoEjercicios,
  guardarEntreno,
  recordsPersonales,
  sugerenciasEjercicio,
  type EntrenoGuardado,
} from "../api";
import Sesion from "./Sesion";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  recordsPersonales: vi.fn().mockResolvedValue([]),
  ultimaSesion: vi.fn().mockResolvedValue([]),
  guardarEntreno: vi.fn(),
  sugerenciasEjercicio: vi.fn().mockResolvedValue([]),
  catalogoEjercicios: vi.fn().mockResolvedValue([]),
}));

// `entregar` es el único camino por el que se puede perder un entreno (`borrador.ts`), así
// que el test que cierra ese agujero necesita sustituirlo por un `null` directo. El resto
// de tests de esta pantalla no lo tocan: se envuelve la implementación real —sube o encola
// de verdad— para que solo el test que lo pide vea el `null` que no está a salvo en ningún
// sitio. Nada restaura esa implementación entre test y test —`clearMocks` borra llamadas,
// no implementaciones—, así que el `null` se pide con `...Once`: se gasta en la única
// llamada de ese test y el siguiente vuelve a encontrarse el `entregar` de verdad.
vi.mock("../borrador", async (importOriginal) => {
  const real = await importOriginal<typeof import("../borrador")>();
  return { ...real, entregar: vi.fn(real.entregar) };
});

// El brief comprueba `navegar` a secas: sin este mock no habría ningún identificador al
// que apuntar esa aserción. Se sustituye solo `useNavigate`; `MemoryRouter` sigue siendo
// el real, que es el que ya usaban los tests anteriores.
const { navegar } = vi.hoisted(() => ({ navegar: vi.fn() }));
vi.mock("react-router", async (importOriginal) => ({
  ...(await importOriginal<typeof import("react-router")>()),
  useNavigate: () => navegar,
}));

/** Lo que devuelve `POST /api/workouts`, recortado a lo que se usa aquí. Igual que en la
 *  tarea 4 (`borrador.test.ts`). */
const SUBIDO = {
  id: "9f1c2a3e-0000-4000-8000-000000000001",
  date: "2026-08-18T17:00:00.000000Z",
  new_records: [],
  system: { xp_gained: 80 },
} as unknown as EntrenoGuardado;

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

  // Los valores por defecto se vuelven a poner aquí y no solo en la factoría del mock.
  // `clearMocks` borra las llamadas, no las implementaciones, así que un
  // `mockResolvedValue` puesto dentro de un test se queda puesto para todos los que vengan
  // detrás: el orden de los tests pasaría a ser parte de lo que comprueban.
  vi.mocked(recordsPersonales).mockResolvedValue([]);
  vi.mocked(sugerenciasEjercicio).mockResolvedValue([]);
  vi.mocked(catalogoEjercicios).mockResolvedValue([]);
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
    { name: "Press banca", max_weight: 80, reps: 5, sets: null, date: "2026-08-01T10:00:00.000000Z" },
  ]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "85" } });
  fireEvent.change(screen.getByLabelText("Repeticiones, serie 1"), { target: { value: "3" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  expect(await screen.findByText(/Press banca: 85 kg, antes 80 kg/)).toBeTruthy();
});

test("igualar la marca no es un récord", async () => {
  vi.mocked(recordsPersonales).mockResolvedValue([
    { name: "Press banca", max_weight: 80, reps: 5, sets: null, date: "2026-08-01T10:00:00.000000Z" },
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

test("si no se puede ni subir ni guardar, el borrador NO se borra", async () => {
  vi.mocked(entregar).mockResolvedValueOnce(null);
  pintar();

  fireEvent.click(screen.getByRole("button", { name: "TERMINAR" }));
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  // Se avisa en español, sin número de error…
  expect((await screen.findByRole("alert")).textContent).toContain("No cierres esta pantalla");
  expect(document.body.textContent).not.toMatch(/\b[45]\d\d\b/);

  // …y sobre todo: el entreno sigue en el móvil y no se ha ido a ninguna parte.
  expect(leer()).not.toBe(null);
  expect(navegar).not.toHaveBeenCalled();
});

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
  // que hay que pedirle a la consulta que mire también lo oculto.
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
