import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { guardarDatosCuerpo, guardarObjetivoNutricional, usuarioActual } from "../api";
import Objetivo from "./Objetivo";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  usuarioActual: vi.fn(),
  guardarObjetivoNutricional: vi.fn(),
  guardarDatosCuerpo: vi.fn(),
}));

const COMPLETO = {
  name: "Isra", email: "a@b.c", is_admin: false,
  weight: 80, height: 180, age: 30, gender: "male" as const,
  weekly_goal: 3, water_goal_ml: 2000,
};

const pintar = () => render(<MemoryRouter><Objetivo /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(usuarioActual).mockResolvedValue(COMPLETO);
  vi.mocked(guardarObjetivoNutricional).mockResolvedValue({ message: "ok", goal: {} as never });
});

test("el número se recalcula al tocar, sin pedir nada al servidor", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Mantener el peso" }));
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));
  fireEvent.click(screen.getByRole("button", { name: "Poco o nada" }));

  // 1780 × 1,2 = 2136.
  expect(screen.getByText("2.136 kcal")).toBeTruthy();

  fireEvent.click(screen.getByRole("button", { name: "Muy intenso, o deporte más trabajo físico" }));
  expect(screen.getByText("3.382 kcal")).toBeTruthy();

  // Nada de esto ha salido a la red.
  expect(vi.mocked(guardarObjetivoNutricional)).not.toHaveBeenCalled();
});

test("el tercer paso deja ajustar las cifras antes de guardar", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Mantener el peso" }));
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));
  fireEvent.click(screen.getByRole("button", { name: "Ejercicio moderado, de 3 a 5 días" }));
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));

  fireEvent.change(screen.getByLabelText("Calorías al día"), { target: { value: "2600" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR MI OBJETIVO" }));

  expect(vi.mocked(guardarObjetivoNutricional)).toHaveBeenCalledWith(
    expect.objectContaining({ daily_calories: 2600, goal_type: "maintain" }),
  );
});

test("si faltan datos del cuerpo se piden antes, en vez de calcular con inventos", async () => {
  vi.mocked(usuarioActual).mockResolvedValue({ ...COMPLETO, height: null, age: null });
  pintar();

  expect(await screen.findByLabelText("Altura en centímetros")).toBeTruthy();
  expect(screen.getByLabelText("Edad")).toBeTruthy();
  // Y no se enseña ninguna cifra todavía.
  expect(screen.queryByText(/kcal/)).toBe(null);
});

test("los datos que faltaban se guardan en el perfil antes de seguir", async () => {
  vi.mocked(usuarioActual).mockResolvedValue({ ...COMPLETO, height: null, age: null });
  vi.mocked(guardarDatosCuerpo).mockResolvedValue({ user: COMPLETO, system: null });
  pintar();

  fireEvent.change(await screen.findByLabelText("Altura en centímetros"), { target: { value: "180" } });
  fireEvent.change(screen.getByLabelText("Edad"), { target: { value: "30" } });
  fireEvent.click(screen.getByRole("button", { name: "SIGUIENTE" }));

  expect(vi.mocked(guardarDatosCuerpo)).toHaveBeenCalledWith(
    expect.objectContaining({ height: 180, age: 30 }),
  );
});
