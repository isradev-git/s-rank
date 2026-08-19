/* El aviso de pendientes vive encima de las pestañas y se ve desde cualquier pantalla,
   porque es una advertencia de pérdida de datos y esas no pueden depender de dónde estés. */

import { render, screen, fireEvent } from "@testing-library/react";
import { beforeEach, expect, test, vi } from "vitest";
import { BrowserRouter } from "react-router";
import App from "./App";
import { encolar, subirPendientes, type Sesion } from "./borrador";
import { usuarioActual } from "./api";

vi.mock("./api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("./api")>()),
  usuarioActual: vi.fn(),
  diaDeHoy: vi.fn().mockRejectedValue(new Error("aquí da igual")),
}));

vi.mock("./borrador", async (importOriginal) => ({
  ...(await importOriginal<typeof import("./borrador")>()),
  subirPendientes: vi.fn().mockResolvedValue([]),
}));

const PENDIENTE: Sesion = {
  v: 1, mode: "gym", nombre: "Torso pesado", inicio: "2026-08-18T17:00:00Z",
  actual: 0, exercises: [], duracion: 45, notas: null,
};

beforeEach(() => {
  localStorage.clear();
  vi.mocked(usuarioActual).mockResolvedValue({ name: "Isra", email: "isra@local.test", is_admin: false });
});

const pintar = () => render(<BrowserRouter><App /></BrowserRouter>);

test("un entreno sin subir se avisa, en singular", async () => {
  encolar(PENDIENTE);
  pintar();

  expect((await screen.findByRole("status")).textContent).toContain("1 entreno pendiente de subir");
});

test("sin nada pendiente no hay aviso que estorbe", async () => {
  pintar();
  await screen.findByRole("navigation");
  expect(screen.queryByRole("status")).toBe(null);
});

test("al recuperar la conexión se reintenta solo", async () => {
  encolar(PENDIENTE);
  pintar();
  await screen.findByRole("status");
  // Al abrir la aplicación ya se ha intentado una vez. Si esto solo comprobara
  // `toHaveBeenCalled()`, pasaría sin escuchar nada y el reintento automático podría no
  // existir: hay que contar las llamadas para que el test hable del oyente y no del montaje.
  expect(subirPendientes).toHaveBeenCalledTimes(1);

  window.dispatchEvent(new Event("online"));

  await vi.waitFor(() => expect(subirPendientes).toHaveBeenCalledTimes(2));
});

test("si al reintentar el entreno traía un récord, sale su ventana del Sistema", async () => {
  encolar(PENDIENTE);
  vi.mocked(subirPendientes).mockResolvedValue([
    {
      new_records: [],
      system: {
        xp_gained: 80, level_up: { from: 4, to: 5 }, rank_up: null,
        achievements_unlocked: [], records: [], quests_completed: [],
        progress: {} as never,
      },
    } as never,
  ]);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "SUBIR AHORA" }));

  expect(await screen.findByRole("dialog")).toBeTruthy();
  expect(screen.getByText("Nivel 5")).toBeTruthy();
});
