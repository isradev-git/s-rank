/* Este fichero prueba lo único irrecuperable de toda la aplicación. Si algo de aquí se
   rompe, alguien pierde un entreno y ya no se acuerda de lo que levantó. */

import { beforeEach, expect, test, vi } from "vitest";
import {
  ahoraUtc,
  borrar,
  descansoPorDefecto,
  encolar,
  guardar,
  guardarDescansoPorDefecto,
  leer,
  pendientes,
  quitarDePendientes,
  type Sesion,
} from "./borrador";

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

test("la cola guarda más de un entreno y respeta el orden", () => {
  // Se puede terminar un entreno sin cobertura y empezar otro antes de recuperarla.
  // Por eso la cola es un array y no un solo registro.
  const segunda: Sesion = { ...SESION, inicio: "2026-08-18T19:30:00Z", nombre: "Piernas" };

  expect(encolar(SESION)).toBe(true);
  expect(encolar(segunda)).toBe(true);

  expect(pendientes().map((s) => s.inicio)).toEqual([
    "2026-08-18T17:00:00Z",
    "2026-08-18T19:30:00Z",
  ]);
});

test("quitar de la cola quita solo el que se sube, no la cola entera", () => {
  const segunda: Sesion = { ...SESION, inicio: "2026-08-18T19:30:00Z" };
  encolar(SESION);
  encolar(segunda);

  quitarDePendientes("2026-08-18T17:00:00Z");

  expect(pendientes().map((s) => s.inicio)).toEqual(["2026-08-18T19:30:00Z"]);
});

test("una cola con basura dentro devuelve lo que sí se entiende", () => {
  // Si un despliegue cambiara la forma, perder los buenos por culpa de uno malo sería
  // exactamente el fallo que este fichero existe para evitar.
  localStorage.setItem(
    "srank.entrenos-pendientes",
    JSON.stringify([{ v: 99, roto: true }, SESION]),
  );

  expect(pendientes()).toEqual([SESION]);
});

test("la cola es un sitio distinto del borrador", () => {
  // Mezclarlos obligaría a distinguirlos con una bandera dentro del mismo registro, que
  // es la forma habitual de subir un entreno a medias por error.
  guardar(SESION);
  encolar(SESION);

  borrar();

  expect(leer()).toBe(null);
  expect(pendientes()).toHaveLength(1);
});

test("el descanso por defecto tiene un valor razonable la primera vez", () => {
  expect(descansoPorDefecto()).toBe(90);
});

test("el descanso por defecto se recuerda entre sesiones", () => {
  guardarDescansoPorDefecto(180);
  expect(descansoPorDefecto()).toBe(180);
});

test("un descanso guardado sin sentido no se cree", () => {
  for (const basura of ["", "hola", "-30", "0"]) {
    localStorage.setItem("srank.descanso-defecto", basura);
    expect(descansoPorDefecto(), `«${basura}» dio otra cosa`).toBe(90);
  }
});
