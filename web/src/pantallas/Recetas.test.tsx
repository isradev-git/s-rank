import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { diaDeHoy, receta, recetas, registrarComida } from "../api";
import Recetas, { Receta } from "./Recetas";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  recetas: vi.fn(),
  receta: vi.fn(),
  registrarComida: vi.fn(),
  diaDeHoy: vi.fn(),
}));

const POLLO = {
  id: 3, name: "Pollo al horno con verduras", description: "Sencillo",
  category: "cena", image_path: null, image_url: null,
  calories_per_serving: 420, protein_per_serving: 38, carbs_per_serving: 22,
  fat_per_serving: 18, fiber_per_serving: 5, servings: 2,
  prep_time_min: 10, cook_time_min: 40, difficulty: "fácil",
  is_system: true, user_id: null,
  ingredients: [{ name: "Pechuga de pollo", quantity: "400 g" }],
  instructions: "Al horno 40 minutos.",
};

beforeEach(() => {
  vi.mocked(diaDeHoy).mockResolvedValue({ date: "2026-08-20" } as never);
  vi.mocked(recetas).mockResolvedValue([POLLO] as never);
  vi.mocked(receta).mockResolvedValue(POLLO as never);
  vi.mocked(registrarComida).mockResolvedValue({ message: "ok", meal_log: {}, system: {} } as never);
});

test("la lista enseña las recetas con sus calorías por ración", async () => {
  render(<MemoryRouter><Recetas /></MemoryRouter>);

  expect(await screen.findByText("Pollo al horno con verduras")).toBeTruthy();
  expect(screen.getByText(/420 kcal/)).toBeTruthy();
});

test("filtrar por categoría se lo pide al servidor, no se filtra aquí", async () => {
  render(<MemoryRouter><Recetas /></MemoryRouter>);
  await screen.findByText("Pollo al horno con verduras");

  fireEvent.click(screen.getByRole("button", { name: "Cena" }));

  await vi.waitFor(() =>
    expect(vi.mocked(recetas)).toHaveBeenLastCalledWith({ category: "cena" }),
  );
});

test("usar una receta la registra como entrada manual con sus macros por ración", async () => {
  render(
    <MemoryRouter initialEntries={["/nutricion/recetas/3"]}>
      <Routes><Route path="/nutricion/recetas/:id" element={<Receta />} /></Routes>
    </MemoryRouter>,
  );

  fireEvent.click(await screen.findByRole("button", { name: "REGISTRAR COMO CENA" }));

  // No hay endpoint que registre una receta: es una entrada manual con su nombre.
  await vi.waitFor(() =>
    expect(vi.mocked(registrarComida)).toHaveBeenCalledWith({
      date: "2026-08-20",
      meal_type: "dinner",
      custom_food_name: "Pollo al horno con verduras",
      calories: 420, protein: 38, carbs: 22, fat: 18,
    }),
  );
});

test("el detalle enseña los ingredientes y los pasos", async () => {
  render(
    <MemoryRouter initialEntries={["/nutricion/recetas/3"]}>
      <Routes><Route path="/nutricion/recetas/:id" element={<Receta />} /></Routes>
    </MemoryRouter>,
  );

  expect(await screen.findByText("Pechuga de pollo")).toBeTruthy();
  expect(screen.getByText("400 g")).toBeTruthy();
  expect(screen.getByText("Al horno 40 minutos.")).toBeTruthy();
});
