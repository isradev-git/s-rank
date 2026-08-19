/* Las plantillas son del usuario o del sistema, y esa diferencia es toda la pantalla: las
   del sistema se ven y se usan, pero el servidor responde 403 a quien intente tocarlas. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { ErrorApi, borrarPlantilla, crearPlantilla, plantillas } from "../api";
import Plantillas from "./Plantillas";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  plantillas: vi.fn(),
  crearPlantilla: vi.fn(),
  editarPlantilla: vi.fn(),
  borrarPlantilla: vi.fn(),
}));

const DEL_SISTEMA = {
  id: "t-1", user_id: null, name: "Torso pesado", description: null, level: "Intermedio",
  mode: "gym" as const, duration_minutes: 60,
  exercises: [{ id: 1, name: "Press banca", sets: 4, reps: 5 }],
};

const MIA = { ...DEL_SISTEMA, id: "t-2", user_id: "u-1", name: "La mía" };

const pintar = () => render(<MemoryRouter><Plantillas /></MemoryRouter>);

beforeEach(() => {
  vi.mocked(plantillas).mockResolvedValue([DEL_SISTEMA, MIA]);
});

test("las plantillas del sistema se ven pero no se pueden editar ni borrar", async () => {
  pintar();
  await screen.findByText("Torso pesado");

  // El servidor responde 403. Enseñar el botón sería ofrecer algo que va a fallar.
  expect(screen.queryByRole("button", { name: "EDITAR TORSO PESADO" })).toBe(null);
  expect(screen.queryByRole("button", { name: "BORRAR TORSO PESADO" })).toBe(null);
  expect(screen.getByRole("button", { name: "EDITAR LA MÍA" })).toBeTruthy();
});

test("crear una plantilla manda solo lo que la API acepta", async () => {
  vi.mocked(crearPlantilla).mockResolvedValue({ message: "ok", template: MIA });
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "NUEVA PLANTILLA" }));
  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Pierna" } });
  fireEvent.change(screen.getByLabelText("Ejercicio 1"), { target: { value: "Sentadilla" } });
  fireEvent.change(screen.getByLabelText("Series del ejercicio 1"), { target: { value: "5" } });
  fireEvent.change(screen.getByLabelText("Repeticiones del ejercicio 1"), { target: { value: "5" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  await vi.waitFor(() => expect(crearPlantilla).toHaveBeenCalled());
  expect(vi.mocked(crearPlantilla).mock.calls[0][0]).toEqual({
    name: "Pierna",
    mode: "gym",
    exercises: [{ name: "Sentadilla", sets: 5, reps: 5 }],
  });
});

test("una plantilla sin ejercicios no se manda: la API la rechaza", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "NUEVA PLANTILLA" }));
  fireEvent.change(screen.getByLabelText("Nombre"), { target: { value: "Vacía" } });
  fireEvent.click(screen.getByRole("button", { name: "GUARDAR" }));

  expect(crearPlantilla).not.toHaveBeenCalled();
  expect((await screen.findByRole("alert")).textContent).toContain("al menos un ejercicio");
});

test("borrar pide confirmación", async () => {
  vi.spyOn(window, "confirm").mockReturnValue(false);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "BORRAR LA MÍA" }));
  expect(borrarPlantilla).not.toHaveBeenCalled();
});

test("un fallo al borrar se dice en español y la plantilla sigue en la lista", async () => {
  vi.spyOn(window, "confirm").mockReturnValue(true);
  vi.mocked(borrarPlantilla).mockRejectedValue(
    new ErrorApi({ general: "No hemos podido conectar. Inténtalo otra vez.", campos: {} }),
  );
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "BORRAR LA MÍA" }));

  expect((await screen.findByRole("alert")).textContent).toBe("No hemos podido conectar. Inténtalo otra vez.");
  expect(screen.getByText("La mía")).toBeTruthy();
});
