/* Este fichero prueba lo único irrecuperable de toda la aplicación. Si algo de aquí se
   rompe, alguien pierde un entreno y ya no se acuerda de lo que levantó. */

import { beforeEach, expect, test, vi } from "vitest";
import { ahoraUtc, borrar, guardar, leer, type Sesion } from "./borrador";

const SESION: Sesion = {
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
        { weight_kg: 80, reps: 5, rpe: 8, distance_m: null, time_seconds: null, style: null, rest_seconds: 180, hecha: true },
      ],
    },
  ],
};

beforeEach(() => {
  localStorage.clear();
});

test("lo que se guarda es exactamente lo que se lee", () => {
  expect(guardar(SESION)).toBe(true);
  expect(leer()).toEqual(SESION);
});

test("sin nada guardado no hay sesión y no revienta", () => {
  expect(leer()).toBe(null);
});

test("un JSON corrupto devuelve null y NO borra lo que hay en disco", () => {
  localStorage.setItem("srank.entreno-activo", "{esto no es JSON");

  expect(leer()).toBe(null);
  // Lo que no se sabe leer no se tira: puede ser el único sitio donde está ese entreno,
  // y un despliegue posterior o una revisión a mano todavía pueden rescatarlo.
  expect(localStorage.getItem("srank.entreno-activo")).toBe("{esto no es JSON");
});

test("una versión desconocida hace lo mismo: null, y sin borrar", () => {
  localStorage.setItem("srank.entreno-activo", JSON.stringify({ ...SESION, v: 99 }));

  expect(leer()).toBe(null);
  expect(localStorage.getItem("srank.entreno-activo")).not.toBe(null);
});

test("guardar avisa cuando no ha podido escribir", () => {
  // Modo privado, o cuota llena. FitLoop se tragaba esto en silencio, y en silencio
  // significa que el usuario cree que su entreno está a salvo cuando no lo está.
  vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
    throw new DOMException("QuotaExceededError");
  });

  expect(guardar(SESION)).toBe(false);
});

test("borrar deja el hueco limpio", () => {
  guardar(SESION);
  borrar();
  expect(leer()).toBe(null);
});

test("la marca de tiempo va en UTC y sin milisegundos", () => {
  // MySQL guarda `workouts.date` con precisión de segundo. Si se mandan milisegundos, el
  // valor que devuelve el servidor no coincide nunca con el local y el deduplicado de la
  // tarea 4 no encuentra jamás su propio entreno.
  expect(ahoraUtc()).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
});
