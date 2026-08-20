/* «Menos de cinco toques» es el criterio duro de la fase. Este fichero es el que lo
   demuestra: si alguien añade un paso de más al camino corto, aquí falla. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
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
