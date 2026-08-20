/* Beber agua es lo que más veces se toca al día. Es optimista a propósito, y lo que hay
   que demostrar es lo otro: que cuando falla, la barra vuelve y se dice con palabras. */

import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, expect, test, vi } from "vitest";
import { ErrorApi, agua, anadirAgua, type BloqueSistema } from "../api";
import { SeccionAgua } from "./habitos";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  agua: vi.fn(),
  anadirAgua: vi.fn(),
  suplementos: vi.fn(),
  marcarSuplemento: vi.fn(),
  actividad: vi.fn(),
  guardarActividad: vi.fn(),
  guardarPeso: vi.fn(),
}));

const pintar = (alGanar: (sistema: BloqueSistema) => void = () => {}) =>
  render(<SeccionAgua fecha="2026-08-20" alGanar={alGanar} />);

beforeEach(() => {
  vi.mocked(agua).mockResolvedValue({
    date: "2026-08-20", total_ml: 1250, goal_ml: 2000, pct: 63, entries: [],
  });
});

test("un vaso sube la barra antes de que conteste el servidor", async () => {
  // Una petición que no resuelve nunca: si la barra espera, este test lo caza.
  vi.mocked(anadirAgua).mockReturnValue(new Promise(() => {}) as never);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,5 de 2 litros")).toBeTruthy();
});

test("si el vaso no se guarda, la barra vuelve atrás y se dice por qué", async () => {
  vi.mocked(anadirAgua).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,25 de 2 litros")).toBeTruthy();
  expect(screen.getByText("No se ha podido guardar el vaso. Comprueba la conexión.")).toBeTruthy();
});

test("el total que manda el servidor gana al optimista", async () => {
  // Otro dispositivo pudo apuntar agua entre medias.
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 1900, goal_ml: 2000, pct: 95, system: {} as never,
  });
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,9 de 2 litros")).toBeTruthy();
});

test("llegar al objetivo sube el bloque system, que es quien abre la ventana", async () => {
  const ganados: unknown[] = [];
  const bloque = { achievements_unlocked: [{ key: "hydrated", name: "Hidratado" }] };
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 2000, goal_ml: 2000, pct: 100, system: bloque as never,
  });
  pintar((s) => ganados.push(s));

  fireEvent.click(await screen.findByRole("button", { name: "+MEDIO LITRO" }));

  await vi.waitFor(() => expect(ganados).toEqual([bloque]));
});

test("la barra se oye como un porcentaje, no como veinte caracteres", async () => {
  pintar();
  const barra = await screen.findByRole("progressbar");
  expect(barra.getAttribute("aria-valuetext")).toBeTruthy();
});

import { marcarSuplemento, suplementos } from "../api";
import { SeccionSuplementos } from "./habitos";

const CUATRO = [
  { key: "multivitaminas" as const, name: "Multivitaminas", dose: "1 pastilla", taken: false },
  { key: "omega3" as const, name: "Omega 3", dose: "1 capsula", taken: false },
  { key: "vitamina_d" as const, name: "Vitamina D3", dose: "1 pastilla", taken: false },
  { key: "magnesio" as const, name: "Magnesio", dose: "1 pastilla", taken: false },
];

const pintarSuplementos = (alGanar: (sistema: BloqueSistema) => void = () => {}) =>
  render(<SeccionSuplementos fecha="2026-08-20" alGanar={alGanar} />);

test("se enseñan los cuatro y el recuento, no si la misión está cumplida", async () => {
  vi.mocked(suplementos).mockResolvedValue({ items: CUATRO, taken_count: 0, total_count: 4 });
  pintarSuplementos();

  expect(await screen.findByRole("button", { name: "Multivitaminas, sin tomar" })).toBeTruthy();
  expect(screen.getByText("0 de 4")).toBeTruthy();
  // La misión la decide el servidor. Adelantarla aquí sería que la app puntuara.
  expect(screen.queryByText(/misión/i)).toBe(null);
});

test("marcar uno manda su clave, la del servidor y no la que se ve", async () => {
  vi.mocked(suplementos).mockResolvedValue({ items: CUATRO, taken_count: 0, total_count: 4 });
  vi.mocked(marcarSuplemento).mockResolvedValue({ message: "ok", system: {} as never });
  pintarSuplementos();

  fireEvent.click(await screen.findByRole("button", { name: "Vitamina D3, sin tomar" }));

  // En pantalla es «Vitamina D3»; la clave del servidor es vitamina_d, sin el 3.
  expect(vi.mocked(marcarSuplemento)).toHaveBeenCalledWith("2026-08-20", "vitamina_d", true);
});

test("la casilla se marca al instante y vuelve si la petición falla", async () => {
  vi.mocked(suplementos).mockResolvedValue({ items: CUATRO, taken_count: 0, total_count: 4 });
  vi.mocked(marcarSuplemento).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  pintarSuplementos();

  fireEvent.click(await screen.findByRole("button", { name: "Magnesio, sin tomar" }));

  expect(await screen.findByRole("button", { name: "Magnesio, sin tomar" })).toBeTruthy();
  expect(screen.getByText("No se ha podido guardar. Comprueba la conexión.")).toBeTruthy();
});

test("el recuento se mueve con la casilla", async () => {
  vi.mocked(suplementos).mockResolvedValue({
    items: [{ ...CUATRO[0], taken: true }, ...CUATRO.slice(1)], taken_count: 1, total_count: 4,
  });
  vi.mocked(marcarSuplemento).mockResolvedValue({ message: "ok", system: {} as never });
  pintarSuplementos();

  fireEvent.click(await screen.findByRole("button", { name: "Omega 3, sin tomar" }));

  expect(await screen.findByText("2 de 4")).toBeTruthy();
});

import { actividad, guardarActividad, guardarPeso } from "../api";
import { SeccionActividad, SeccionPeso } from "./habitos";

test("los pasos se guardan y las calorías, si no se saben, van a cero", async () => {
  vi.mocked(actividad).mockResolvedValue({ date: "2026-08-20", steps: 0, calories_burned: 0 });
  vi.mocked(guardarActividad).mockResolvedValue({
    date: "2026-08-20", steps: 8200, calories_burned: 0, system: null,
  });
  render(<SeccionActividad fecha="2026-08-20" alGanar={() => {}} />);

  fireEvent.change(await screen.findByLabelText("Pasos"), { target: { value: "8200" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR LOS PASOS" }));

  // El servidor exige las dos cifras. Mucha gente solo conoce sus pasos, y estimar las
  // calorías sería inventarse un dato de salud.
  expect(vi.mocked(guardarActividad)).toHaveBeenCalledWith("2026-08-20", 8200, 0);
});

test("las calorías se dicen opcionales y de dónde salen", async () => {
  vi.mocked(actividad).mockResolvedValue({ date: "2026-08-20", steps: 0, calories_burned: 0 });
  render(<SeccionActividad fecha="2026-08-20" alGanar={() => {}} />);

  expect(await screen.findByLabelText("Calorías quemadas, si tu reloj te las da")).toBeTruthy();
});

test("apuntar el peso lo manda en kilos y sube el bloque system", async () => {
  const ganados: unknown[] = [];
  const bloque = { quests_completed: ["weight"] };
  vi.mocked(guardarPeso).mockResolvedValue({ user: {} as never, system: bloque as never });
  render(<SeccionPeso pesoActual={78} alGanar={(s) => ganados.push(s)} />);

  fireEvent.change(screen.getByLabelText("Peso en kilos"), { target: { value: "77.5" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR EL PESO" }));

  expect(vi.mocked(guardarPeso)).toHaveBeenCalledWith(77.5);
  await vi.waitFor(() => expect(ganados).toEqual([bloque]));
});

test("si el peso no cambia el servidor manda system a null y no se sube nada", async () => {
  const ganados: unknown[] = [];
  vi.mocked(guardarPeso).mockResolvedValue({ user: {} as never, system: null });
  render(<SeccionPeso pesoActual={78} alGanar={(s) => ganados.push(s)} />);

  fireEvent.click(screen.getByRole("button", { name: "GUARDAR EL PESO" }));

  await vi.waitFor(() => expect(vi.mocked(guardarPeso)).toHaveBeenCalled());
  expect(ganados).toEqual([]);
});
