/* «Menos de cinco toques» es el criterio duro de la fase. Este fichero es el que lo
   demuestra: si alguien añade un paso de más al camino corto, aquí falla. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { afterEach, beforeEach, expect, test, vi } from "vitest";
import { diaDeHoy, registrarComida } from "../api";
import { apuntar } from "../recientes";
import AnadirComida from "./AnadirComida";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  diaDeHoy: vi.fn(),
  registrarComida: vi.fn(),
  buscarAlimentos: vi.fn(),
}));

// 44 kcal/100 g × 200 g = 88 kcal, que es lo que tiene que decir el chip.
const CAFE = { id: 1, nombre: "Café con leche", gramos: 200, tipo: "breakfast" as const, kcal100: 44 };

const pintar = (tipo = "breakfast") =>
  render(
    <MemoryRouter initialEntries={[`/nutricion/anadir?tipo=${tipo}`]}>
      <AnadirComida />
    </MemoryRouter>,
  );

beforeEach(() => {
  localStorage.clear();
  vi.mocked(diaDeHoy).mockResolvedValue({ date: "2026-08-20" } as never);
  vi.mocked(registrarComida).mockResolvedValue({
    message: "ok", meal_log: {}, system: {},
  } as never);
});

// Aquí y no al final del test que los pone: si ese test falla antes de restaurarlos, los
// temporizadores falsos se quedan puestos y todos los siguientes agotan su tiempo sin que
// haya nada roto en el código.
afterEach(() => vi.useRealTimers());

test("registrar un reciente es un solo toque dentro de la pantalla", async () => {
  apuntar(CAFE);
  pintar();

  const chip = await screen.findByRole("button", {
    name: "Café con leche, 200 g, 88 kilocalorías",
  });
  fireEvent.click(chip);

  expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
    date: "2026-08-20",
    meal_type: "breakfast",
    food_item_id: 1,
    quantity_grams: 200,
  });
});

test("los recientes son los de esta comida, no los de todas", async () => {
  apuntar(CAFE);
  apuntar({ id: 9, nombre: "Yogur", gramos: 125, tipo: "dinner", kcal100: 61 });

  pintar("breakfast");

  expect(await screen.findByText("Café con leche")).toBeTruthy();
  expect(screen.queryByText("Yogur")).toBe(null);
});

test("sin recientes no se enseña una lista vacía y muda", async () => {
  pintar();
  expect(await screen.findByText("todavía no has registrado nada aquí")).toBeTruthy();
});

test("si el registro falla se dice con palabras y el chip vuelve a poderse pulsar", async () => {
  const { ErrorApi } = await import("../api");
  vi.mocked(registrarComida).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  apuntar(CAFE);
  pintar();

  // El nombre entero y no un trozo: «Café con leche» a secas casa también con el botón
  // de cambiar la cantidad, y la consulta encontraría dos.
  const nombreDelChip = "Café con leche, 200 g, 88 kilocalorías";
  fireEvent.click(await screen.findByRole("button", { name: nombreDelChip }));

  expect(await screen.findByText("No hay conexión. Comprueba el wifi o los datos.")).toBeTruthy();
  const chip = screen.getByRole("button", { name: nombreDelChip }) as HTMLButtonElement;
  expect(chip.disabled).toBe(false);
});

test("un tipo de comida que no existe cae en desayuno en vez de romperse", async () => {
  pintar("cualquiercosa");
  expect(await screen.findByText(/Desayuno/)).toBeTruthy();
});

import { buscarAlimentos } from "../api";

const POLLO = {
  id: 7, name: "Pechuga de pollo", brand: null, category: "carnes", unit: "g" as const,
  is_verified: true,
  calories_per_100g: 165, protein_per_100g: 31, carbs_per_100g: 0,
  fat_per_100g: 3.6, fiber_per_100g: 0, sugar_per_100g: 0,
};

test("el buscador espera antes de pedir, no una petición por tecla", async () => {
  vi.useFakeTimers();
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  // getByLabelText y no findByLabelText: las consultas «find» esperan con un reloj que
  // aquí no avanza solo, así que se quedarían colgadas hasta el tiempo límite.
  const campo = screen.getByLabelText("Buscar un alimento");
  fireEvent.change(campo, { target: { value: "p" } });
  fireEvent.change(campo, { target: { value: "po" } });
  fireEvent.change(campo, { target: { value: "pol" } });

  // 1.506 alimentos y una conexión de móvil: una petición por tecla es tirar datos.
  expect(vi.mocked(buscarAlimentos)).not.toHaveBeenCalled();

  await vi.advanceTimersByTimeAsync(300);

  expect(vi.mocked(buscarAlimentos)).toHaveBeenCalledTimes(1);
  expect(vi.mocked(buscarAlimentos)).toHaveBeenCalledWith("pol");
});

test("elegir un alimento precarga 100 g y enseña los macros de esa cantidad", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "pollo" },
  });
  fireEvent.click(await screen.findByRole("button", { name: /Pechuga de pollo/ }));

  const cantidad = screen.getByLabelText("Cantidad en gramos") as HTMLInputElement;
  expect(cantidad.value).toBe("100");
  expect(screen.getByText("165 kcal")).toBeTruthy();
});

test("cambiar la cantidad recalcula los macros al vuelo", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "pollo" },
  });
  fireEvent.click(await screen.findByRole("button", { name: /Pechuga de pollo/ }));
  fireEvent.change(screen.getByLabelText("Cantidad en gramos"), { target: { value: "150" } });

  expect(screen.getByText("248 kcal")).toBeTruthy();
});

test("añadir manda el id y los gramos, y deja el alimento en los recientes", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([POLLO]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "pollo" },
  });
  fireEvent.click(await screen.findByRole("button", { name: /Pechuga de pollo/ }));
  fireEvent.change(screen.getByLabelText("Cantidad en gramos"), { target: { value: "150" } });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR" }));

  expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
    date: "2026-08-20", meal_type: "breakfast", food_item_id: 7, quantity_grams: 150,
  });
});

test("una búsqueda sin resultados lo dice y ofrece crear el alimento", async () => {
  vi.mocked(buscarAlimentos).mockResolvedValue([]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Buscar un alimento"), {
    target: { value: "bizcocho de la abuela" },
  });

  expect(await screen.findByText("no hemos encontrado nada con ese nombre")).toBeTruthy();
  expect(screen.getByRole("link", { name: /CREAR UN ALIMENTO/ })).toBeTruthy();
});

test("a mano se manda el nombre escrito y los macros, sin id de catálogo", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "A MANO" }));
  fireEvent.change(screen.getByLabelText("Qué has comido"), { target: { value: "Menú del día" } });
  fireEvent.change(screen.getByLabelText("Calorías"), { target: { value: "780" } });
  fireEvent.change(screen.getByLabelText("Proteínas en gramos"), { target: { value: "35" } });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR" }));

  expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
    date: "2026-08-20",
    meal_type: "breakfast",
    custom_food_name: "Menú del día",
    calories: 780,
    protein: 35,
    carbs: 0,
    fat: 0,
  });
});

test("a mano no se puede añadir sin nombre ni sin calorías", async () => {
  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "A MANO" }));

  const anadir = screen.getByRole("button", { name: "AÑADIR" }) as HTMLButtonElement;
  expect(anadir.disabled).toBe(true);

  fireEvent.change(screen.getByLabelText("Qué has comido"), { target: { value: "Menú" } });
  expect((screen.getByRole("button", { name: "AÑADIR" }) as HTMLButtonElement).disabled).toBe(true);

  fireEvent.change(screen.getByLabelText("Calorías"), { target: { value: "780" } });
  expect((screen.getByRole("button", { name: "AÑADIR" }) as HTMLButtonElement).disabled).toBe(false);
});

test("lo escrito a mano no entra en los recientes", async () => {
  const { recientes } = await import("../recientes");
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "A MANO" }));
  fireEvent.change(screen.getByLabelText("Qué has comido"), { target: { value: "Menú del día" } });
  fireEvent.change(screen.getByLabelText("Calorías"), { target: { value: "780" } });
  fireEvent.click(screen.getByRole("button", { name: "AÑADIR" }));

  // Sin food_item_id no se puede volver a registrar de un toque, así que un chip suyo
  // sería un botón que no hace lo que promete.
  await vi.waitFor(() => expect(recientes("breakfast")).toEqual([]));
});
