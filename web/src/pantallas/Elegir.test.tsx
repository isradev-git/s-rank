/* De dónde sale el entreno de hoy. Lo que estos tests vigilan es que la pantalla deje
   una `Sesion` bien formada en disco antes de pasar a la siguiente, y que no pase si no
   pudo dejarla. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { diaDeHoy, entrenos, plantillas } from "../api";
import { guardar, leer } from "../borrador";
import Elegir from "./Elegir";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  diaDeHoy: vi.fn(),
  entrenos: vi.fn(),
  plantillas: vi.fn(),
}));

// Se envuelve la implementación real —escribe de verdad en localStorage, que es lo que
// comprueban casi todos los tests de aquí— y solo el que mira el disco lleno la sustituye,
// con `...Once` para que no se quede puesta: `clearMocks` borra llamadas, no
// implementaciones.
vi.mock("../borrador", async (importOriginal) => {
  const real = await importOriginal<typeof import("../borrador")>();
  return { ...real, guardar: vi.fn(real.guardar) };
});

const PLANTILLA = {
  id: "t-1", user_id: null, name: "Torso pesado", description: null,
  level: "Intermedio", mode: "gym" as const, duration_minutes: 60,
  exercises: [
    { id: 1, name: "Press banca", sets: 4, reps: 5 },
    { id: 2, name: "Remo con barra", sets: 4, reps: 8 },
  ],
};

function pintar() {
  return render(
    <MemoryRouter initialEntries={["/entrenar"]}>
      <Routes>
        <Route path="/entrenar" element={<Elegir />} />
        <Route path="/entrenar/sesion" element={<p>sesión</p>} />
      </Routes>
    </MemoryRouter>,
  );
}

beforeEach(() => {
  localStorage.clear();
  vi.mocked(plantillas).mockResolvedValue([PLANTILLA]);
  vi.mocked(entrenos).mockResolvedValue([]);
  vi.mocked(diaDeHoy).mockResolvedValue({
    date: "2026-08-18",
    progress: { level: 4, rank: "E", xp_into_level: 240, xp_for_next: 400, current_streak: 12 },
    quests: [],
    suggested_workout: {
      reason: "Te faltan 2 entrenos para tu meta de esta semana.",
      weekly_done: 1, weekly_goal: 3, template: PLANTILLA,
    },
  } as unknown as Awaited<ReturnType<typeof diaDeHoy>>);
});

test("el motivo lo escribe el servidor y se enseña tal cual", async () => {
  pintar();
  // Ya viene en castellano desde /system/today. Reescribirlo aquí sería tener el mismo
  // texto en dos sitios y que uno se quedara viejo.
  expect(await screen.findByText("Te faltan 2 entrenos para tu meta de esta semana.")).toBeTruthy();
});

test("empezar desde una plantilla deja una serie por ejercicio, lista para rellenar", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR TORSO PESADO" }));

  const sesion = leer()!;
  expect(sesion.nombre).toBe("Torso pesado");
  expect(sesion.mode).toBe("gym");
  expect(sesion.exercises.map((e) => e.name)).toEqual(["Press banca", "Remo con barra"]);
  // Una serie semilla, no cuatro vacías: se añaden según se hacen, que es como se entrena.
  expect(sesion.exercises[0].sets).toHaveLength(1);
  // El objetivo de la plantilla se guarda como guía, no como obligación.
  expect(sesion.exercises[0].objetivo).toEqual({ sets: 4, reps: 5 });
  expect(await screen.findByText("sesión")).toBeTruthy();
});

test("la plantilla sugerida no sale también en la lista de abajo", async () => {
  // Dos botones con el mismo nombre accesible son dos botones idénticos para quien usa
  // lector de pantalla, y no hay forma de saber que hacen lo mismo.
  pintar();
  await screen.findByRole("button", { name: "EMPEZAR TORSO PESADO" });

  expect(screen.getAllByRole("button", { name: "EMPEZAR TORSO PESADO" })).toHaveLength(1);
});

test("empezar en blanco no inventa ningún ejercicio", async () => {
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR EN BLANCO" }));

  expect(leer()!.exercises).toEqual([]);
  expect(leer()!.nombre).toBe("Entreno libre");
});

test("repetir el último copia los pesos y ninguna serie llega marcada", async () => {
  vi.mocked(entrenos).mockResolvedValue([
    {
      id: "w-1", date: "2026-08-15T17:00:00.000000Z", mode: "gym", duration_minutes: 45,
      sets: [
        { name: "Press banca", weight_kg: 80, reps: 5, rest_seconds: 180, distance_m: null, time_seconds: null, style: null },
        { name: "Press banca", weight_kg: 80, reps: 5, rest_seconds: 180, distance_m: null, time_seconds: null, style: null },
        { name: "Remo con barra", weight_kg: 60, reps: 8, rest_seconds: 120, distance_m: null, time_seconds: null, style: null },
      ],
    },
  ]);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "REPETIR EL ÚLTIMO" }));

  await vi.waitFor(() => expect(leer()).not.toBe(null));
  const sesion = leer()!;
  expect(sesion.exercises.map((e) => e.name)).toEqual(["Press banca", "Remo con barra"]);
  expect(sesion.exercises[0].sets).toHaveLength(2);
  expect(sesion.exercises[0].sets[0].weight_kg).toBe(80);
  // Marcadas no: son el plan de hoy, no el entreno de hoy.
  expect(sesion.exercises[0].sets.every((s) => !s.hecha)).toBe(true);
});

test("sin un entreno anterior de ese modo se dice en español y no se rompe nada", async () => {
  vi.mocked(entrenos).mockResolvedValue([]);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "REPETIR EL ÚLTIMO" }));

  expect((await screen.findByRole("alert")).textContent).toContain("No hay un entreno anterior");
  expect(leer()).toBe(null);
});

test("si no cabe en el móvil se avisa y no se pasa a una pantalla vacía", async () => {
  // `guardar` devuelve si pudo escribir, y tragárselo es lo peor que se puede hacer aquí:
  // la sesión no existiría en disco, `Sesion.tsx` no pinta nada sin ella, y el usuario se
  // quedaría mirando una pantalla en blanco sin saber por qué.
  vi.mocked(guardar).mockReturnValueOnce(false);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR EN BLANCO" }));

  expect((await screen.findByRole("alert")).textContent).toContain("libera espacio");
  expect(screen.queryByText("sesión")).toBe(null);
});

test("el entreno empieza con la hora en UTC y sin milisegundos", async () => {
  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "EMPEZAR EN BLANCO" }));

  expect(leer()!.inicio).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
});
