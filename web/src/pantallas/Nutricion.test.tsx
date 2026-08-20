/* La pantalla que se abre tres o cuatro veces al día. Lo que no puede pasar: que un día
   vacío la rompa, y que se enseñe un objetivo que el usuario no ha configurado. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { borrarComida, comidasDelDia, diaDeHoy, objetivoNutricional } from "../api";
import Nutricion from "./Nutricion";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  comidasDelDia: vi.fn(),
  borrarComida: vi.fn(),
  objetivoNutricional: vi.fn(),
  diaDeHoy: vi.fn(),
}));

const VACIAS = {
  items: [] as never[],
  calories: 0,
};

const DIA_VACIO = {
  date: "2026-08-20",
  meals: { breakfast: VACIAS, lunch: VACIAS, dinner: VACIAS, snack: VACIAS },
  totals: { calories: 0, protein: 0, carbs: 0, fat: 0, fiber: 0, sugar: 0 },
  count: 0,
  calories_burned: 0,
};

const CAFE = {
  uuid: "c-1", meal_type: "breakfast" as const, food_item_id: 1,
  custom_food_name: null, quantity_grams: 200,
  calories: 88, protein: 4, carbs: 6, fat: 4, fiber: 0, sugar: 6,
  food_item: { id: 1, name: "Café con leche" },
};

const pintar = () => render(<MemoryRouter><Nutricion /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(diaDeHoy).mockResolvedValue({ date: "2026-08-20" } as never);
  vi.mocked(comidasDelDia).mockResolvedValue(DIA_VACIO as never);
  vi.mocked(objetivoNutricional).mockResolvedValue({
    goal: { daily_calories: 2000 }, has_goal: true,
  } as never);
});

test("un día sin nada registrado se ve entero y no se rompe", async () => {
  pintar();

  expect(await screen.findByText("Desayuno")).toBeTruthy();
  expect(screen.getByText("Comida")).toBeTruthy();
  expect(screen.getByText("Cena")).toBeTruthy();
  expect(screen.getByText("Tentempié")).toBeTruthy();
});

test("la fecha sale de /api/system/today y no del reloj del navegador", async () => {
  pintar();
  await screen.findByText("Desayuno");

  // A las 00:30 de Madrid el día del navegador ya no es el que puntúa.
  expect(vi.mocked(comidasDelDia)).toHaveBeenCalledWith("2026-08-20");
});

test("sin objetivo configurado se invita a configurarlo en vez de inventar un número", async () => {
  vi.mocked(objetivoNutricional).mockResolvedValue({
    goal: { daily_calories: 2400 }, has_goal: false,
  } as never);

  pintar();

  expect(await screen.findByText("todavía sin objetivo")).toBeTruthy();
  expect(screen.getByRole("link", { name: /CALCULAR MI OBJETIVO/ })).toBeTruthy();
  // La sugerencia del servidor no se enseña como si fuera suya.
  expect(screen.queryByText(/2.400/)).toBe(null);
});

test("con objetivo se dice cuánto queda", async () => {
  vi.mocked(comidasDelDia).mockResolvedValue({
    ...DIA_VACIO,
    meals: { ...DIA_VACIO.meals, breakfast: { items: [CAFE], calories: 88 } },
    totals: { calories: 1360, protein: 92, carbs: 140, fat: 45, fiber: 18, sugar: 30 },
    count: 1,
  } as never);

  pintar();

  expect(await screen.findByText("te quedan 640 kcal")).toBeTruthy();
});

test("quitar una entrada la borra y recarga el día", async () => {
  vi.mocked(comidasDelDia).mockResolvedValue({
    ...DIA_VACIO,
    meals: { ...DIA_VACIO.meals, breakfast: { items: [CAFE], calories: 88 } },
    count: 1,
  } as never);
  vi.mocked(borrarComida).mockResolvedValue({ message: "ok" });

  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "Quitar Café con leche" }));

  expect(vi.mocked(borrarComida)).toHaveBeenCalledWith("c-1");
});
