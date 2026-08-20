import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { crearAlimento } from "../api";
import CrearAlimento from "./CrearAlimento";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  crearAlimento: vi.fn(),
  subir: vi.fn(),
}));

// jsdom no trae createImageBitmap ni el 2d del canvas, así que encoger() tumbaría el test
// por el entorno y no por el código. Lo que encoge ya está probado en foto.test.ts; aquí
// lo que importa es contra qué id se sube.
vi.mock("../foto", () => ({ encoger: vi.fn(async (f: File) => f) }));

const pintar = () => render(<MemoryRouter><CrearAlimento /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(crearAlimento).mockResolvedValue({ message: "ok", food: { id: 42 } } as never);
});

test("crear un alimento manda los macros por 100 g", () => {
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Bizcocho de la abuela" } });
  fireEvent.change(screen.getByLabelText("Calorías por 100 g"), { target: { value: "410" } });
  fireEvent.change(screen.getByLabelText("Proteínas por 100 g"), { target: { value: "6" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  expect(vi.mocked(crearAlimento)).toHaveBeenCalledWith({
    name: "Bizcocho de la abuela",
    brand: null,
    category: null,
    unit: "g",
    calories_per_100g: 410,
    protein_per_100g: 6,
    carbs_per_100g: 0,
    fat_per_100g: 0,
    fiber_per_100g: 0,
    sugar_per_100g: 0,
  });
});

test("nunca se manda from_ingredients, que haría el alimento visible para todos", () => {
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Algo" } });
  fireEvent.change(screen.getByLabelText("Calorías por 100 g"), { target: { value: "100" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  const enviado = vi.mocked(crearAlimento).mock.calls[0][0];
  expect("from_ingredients" in enviado).toBe(false);
});

test("sin nombre o sin calorías no se puede guardar", () => {
  pintar();
  expect((screen.getByRole("button", { name: "GUARDAR" }) as HTMLButtonElement).disabled).toBe(true);
});

test("se puede elegir mililitros para lo que se bebe", () => {
  pintar();

  // Gramos es lo de salida, y se ve que lo es.
  expect(screen.getByRole("button", { name: "Gramos" }).getAttribute("aria-pressed")).toBe("true");

  fireEvent.click(screen.getByRole("button", { name: "Mililitros" }));

  expect(screen.getByLabelText("Calorías por 100 ml")).toBeTruthy();
  expect(screen.getByRole("button", { name: "Mililitros" }).getAttribute("aria-pressed")).toBe("true");
});

test("la foto se sube después de crear el alimento, contra el id que devolvió", async () => {
  const { subir } = await import("../api");
  vi.mocked(subir).mockResolvedValue({ image_path: "nutrition/foods/x.jpg" } as never);
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Bizcocho" } });
  fireEvent.change(screen.getByLabelText("Calorías por 100 g"), { target: { value: "410" } });
  fireEvent.change(screen.getByLabelText("Foto, si quieres"), {
    target: { files: [new File(["x"], "b.jpg", { type: "image/jpeg" })] },
  });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() =>
    expect(vi.mocked(subir)).toHaveBeenCalledWith("/foods/42/image", "image", expect.any(File)),
  );
});
