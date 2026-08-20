import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { crearReceta, subir } from "../api";
import CrearReceta from "./CrearReceta";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  crearReceta: vi.fn(),
  subir: vi.fn(),
}));
vi.mock("../foto", () => ({ encoger: vi.fn(async (f: File) => f), LADO_MAXIMO: 1280 }));

const pintar = () => render(<MemoryRouter><CrearReceta /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(crearReceta).mockResolvedValue({ id: 77, name: "La mía" } as never);
  vi.mocked(subir).mockResolvedValue({ image_path: "nutrition/recipes/x.jpg" } as never);
});

test("se avisa de que la receta la verá el resto de gente, antes de guardar", () => {
  pintar();

  // El servidor guarda toda receta de usuario con is_system = true. El arreglo queda
  // fuera de esta fase, pero callarlo sería peor que el propio fallo.
  expect(
    screen.getByText("Tus recetas las verá el resto de personas que usan S-RANK."),
  ).toBeTruthy();
});

test("los ingredientes se añaden y se quitan de uno en uno", () => {
  pintar();

  fireEvent.click(screen.getByRole("button", { name: "AÑADIR INGREDIENTE" }));
  fireEvent.change(screen.getByLabelText("Ingrediente 1"), { target: { value: "Arroz" } });
  fireEvent.change(screen.getByLabelText("Cantidad del ingrediente 1"), { target: { value: "80 g" } });

  fireEvent.click(screen.getByRole("button", { name: "AÑADIR INGREDIENTE" }));
  expect(screen.getByLabelText("Ingrediente 2")).toBeTruthy();

  fireEvent.click(screen.getByRole("button", { name: "Quitar el ingrediente 2" }));
  expect(screen.queryByLabelText("Ingrediente 2")).toBe(null);
});

test("guardar manda la receta y después la foto contra el id devuelto", async () => {
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "La mía" } });
  fireEvent.change(screen.getByLabelText("Calorías por ración"), { target: { value: "520" } });
  fireEvent.change(screen.getByLabelText("Foto, si quieres"), {
    target: { files: [new File(["x"], "r.jpg", { type: "image/jpeg" })] },
  });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() =>
    expect(vi.mocked(crearReceta)).toHaveBeenCalledWith(
      expect.objectContaining({ name: "La mía", calories_per_serving: 520, category: "almuerzo" }),
    ),
  );
  await vi.waitFor(() =>
    expect(vi.mocked(subir)).toHaveBeenCalledWith("/recipes/77/image", "image", expect.any(File)),
  );
});

test("si la foto falla, la receta ya está guardada y se dice tal cual", async () => {
  const { ErrorApi } = await import("../api");
  vi.mocked(subir).mockRejectedValue(
    new ErrorApi({ general: "No hemos podido subir la foto. Inténtalo otra vez.", campos: {} }),
  );
  pintar();

  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "La mía" } });
  fireEvent.change(screen.getByLabelText("Calorías por ración"), { target: { value: "520" } });
  fireEvent.change(screen.getByLabelText("Foto, si quieres"), {
    target: { files: [new File(["x"], "r.jpg", { type: "image/jpeg" })] },
  });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  expect(
    await screen.findByText("La receta se ha guardado, pero la foto no se ha podido subir."),
  ).toBeTruthy();
});
