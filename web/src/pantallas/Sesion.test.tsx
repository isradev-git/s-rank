/* La sesión activa es lo único irrecuperable de la aplicación. Estos tests comprueban lo
   que la fase pone como criterio: que matar la app a mitad no pierda nada.

   Lo que NO demuestran, y hay que probar en el móvil: que funcione de verdad en modo
   avión y que sobreviva a que el sistema mate el proceso. jsdom no tiene ni red que
   cortar ni proceso que matar. */

import { StrictMode } from "react";
import { render, screen, fireEvent } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { guardar, leer, type Sesion as TipoSesion } from "../borrador";
import { recordsPersonales } from "../api";
import Sesion from "./Sesion";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  recordsPersonales: vi.fn().mockResolvedValue([]),
  ultimaSesion: vi.fn().mockResolvedValue([]),
}));

const EN_CURSO: TipoSesion = {
  v: 1,
  mode: "gym",
  nombre: "Torso pesado",
  inicio: "2026-08-18T17:00:00Z",
  actual: 0,
  exercises: [
    {
      name: "Press banca",
      objetivo: { sets: 4, reps: 5 },
      sets: [
        { weight_kg: null, reps: null, rpe: null, distance_m: null, time_seconds: null, style: null, rest_seconds: 90, hecha: false },
      ],
    },
  ],
};

// Con `StrictMode`, igual que `main.tsx`. Aquí no es ceremonia: React invoca dos veces las
// funciones actualizadoras y monta y desmonta los efectos una vez de más, que es lo que
// caza un cronómetro sin limpiar o una escritura metida donde no toca. Esta pantalla la
// siguen tocando las tareas 10, 11 y 14, y sin esto el test no se parecería a cómo se monta
// la aplicación de verdad.
const pintar = () =>
  render(
    <StrictMode>
      <MemoryRouter>
        <Sesion />
      </MemoryRouter>
    </StrictMode>,
  );

beforeEach(() => {
  localStorage.clear();
  guardar(EN_CURSO);
});

test("apuntar un peso lo escribe en disco en el momento, no al salir", async () => {
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), {
    target: { value: "80" },
  });

  // Sin esperar a nada: si el sistema mata la pestaña ahora mismo, el 80 ya está escrito.
  expect(leer()!.exercises[0].sets[0].weight_kg).toBe(80);
});

test("marcar una serie también se escribe en el momento", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));

  expect(leer()!.exercises[0].sets[0].hecha).toBe(true);
});

test("volver a montar la pantalla recupera el estado exacto", async () => {
  const { unmount } = pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "80" } });
  fireEvent.change(screen.getByLabelText("Repeticiones, serie 1"), { target: { value: "5" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  // Matar la app y volver a abrirla.
  unmount();
  pintar();

  expect((await screen.findByLabelText("Peso en kilos, serie 1") as HTMLInputElement).value).toBe("80");
  expect((screen.getByLabelText("Repeticiones, serie 1") as HTMLInputElement).value).toBe("5");
  expect(screen.getByRole("button", { name: "Serie 1, hecha" })).toBeTruthy();
});

test("añadir una serie hereda el descanso de la anterior", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "AÑADIR SERIE" }));

  const guardado = leer()!;
  expect(guardado.exercises[0].sets).toHaveLength(2);
  expect(guardado.exercises[0].sets[1].rest_seconds).toBe(90);
});

test("si no se puede guardar, se dice en rojo y no en silencio", async () => {
  // FitLoop se tragaba este fallo. En silencio significa que el usuario cree que su
  // entreno está a salvo cuando no lo está, y es el único caso donde no avisar es peor
  // que cualquier cosa que se pueda enseñar.
  pintar();
  vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
    throw new DOMException("QuotaExceededError");
  });

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "80" } });

  expect((await screen.findByRole("alert")).textContent).toContain("no cierres");
});

test("el avance se dice con palabras, no solo con la barra", async () => {
  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));

  expect(screen.getByText("1 serie hecha")).toBeTruthy();
});

test("marcar una serie arranca la cuenta atrás del descanso", async () => {
  vi.useFakeTimers({ shouldAdvanceTime: true });
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));
  expect(screen.getByText("Descanso 1:30")).toBeTruthy();

  await vi.advanceTimersByTimeAsync(10_000);
  expect(screen.getByText("Descanso 1:20")).toBeTruthy();

  vi.useRealTimers();
});

test("el descanso se puede saltar", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "Serie 1, pendiente" }));
  fireEvent.click(screen.getByRole("button", { name: "SALTAR DESCANSO" }));

  expect(screen.queryByText(/^Descanso /)).toBe(null);
});

test("desmarcar una serie no arranca ningún descanso", async () => {
  pintar();
  const marca = await screen.findByRole("button", { name: "Serie 1, pendiente" });

  fireEvent.click(marca);                                                    // hecha
  fireEvent.click(screen.getByRole("button", { name: "SALTAR DESCANSO" }));
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, hecha" }));   // corregir un error

  expect(screen.queryByText(/^Descanso /)).toBe(null);
});

test("un récord se anuncia en el momento de batirlo", async () => {
  vi.mocked(recordsPersonales).mockResolvedValue([
    { name: "Press banca", max_weight: 80, reps: 5, sets: null, date: "2026-08-01T10:00:00.000000Z" },
  ]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "85" } });
  fireEvent.change(screen.getByLabelText("Repeticiones, serie 1"), { target: { value: "3" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  expect(await screen.findByText(/Press banca: 85 kg, antes 80 kg/)).toBeTruthy();
});

test("igualar la marca no es un récord", async () => {
  vi.mocked(recordsPersonales).mockResolvedValue([
    { name: "Press banca", max_weight: 80, reps: 5, sets: null, date: "2026-08-01T10:00:00.000000Z" },
  ]);
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "80" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  expect(screen.queryByText(/Press banca: 80 kg/)).toBe(null);
});

test("sin conexión el aviso de récord simplemente no sale, y nada se rompe", async () => {
  // Es el caso normal de esta pantalla: un sótano de gimnasio. El récord de verdad lo
  // decide el servidor al guardar, así que aquí no hay nada que reintentar ni que avisar.
  vi.mocked(recordsPersonales).mockRejectedValue(new Error("sin red"));
  pintar();

  fireEvent.change(await screen.findByLabelText("Peso en kilos, serie 1"), { target: { value: "85" } });
  fireEvent.click(screen.getByRole("button", { name: "Serie 1, pendiente" }));

  expect(screen.queryByRole("alert")).toBe(null);
  expect(screen.getByRole("button", { name: "Serie 1, hecha" })).toBeTruthy();
});
