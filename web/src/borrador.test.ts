/* Este fichero prueba lo único irrecuperable de toda la aplicación. Si algo de aquí se
   rompe, alguien pierde un entreno y ya no se acuerda de lo que levantó. */

import { beforeEach, expect, test, vi } from "vitest";
import {
  ahoraUtc,
  aPayload,
  borrar,
  descansoPorDefecto,
  encolar,
  guardar,
  guardarDescansoPorDefecto,
  leer,
  pendientes,
  quitarDePendientes,
  serieVacia,
  type Sesion,
  type Serie,
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

/** Una serie de fuerza rellena a medias, para no repetir los ocho campos cada vez. */
function serie(campos: Partial<Serie> = {}): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: null, hecha: false,
    ...campos,
  };
}

test("el cuerpo lleva una fila por serie, como pide la API", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [
      {
        name: "Press banca",
        objetivo: null,
        sets: [
          serie({ weight_kg: 80, reps: 5, rpe: 8, rest_seconds: 180, hecha: true }),
          serie({ weight_kg: 80, reps: 5, hecha: true }),
        ],
      },
    ],
  };

  expect(aPayload(sesion, 45, null)).toEqual({
    mode: "gym",
    date: "2026-08-18T17:00:00Z",
    duration_minutes: 45,
    notes: null,
    exercises: [
      {
        name: "Press banca",
        sets: [
          { weight_kg: 80, reps: 5, rpe: 8, rest_seconds: 180 },
          { weight_kg: 80, reps: 5 },
        ],
      },
    ],
  });
});

test("`hecha` no se manda: es del cliente y el servidor no tiene ese campo", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [{ name: "Sentadilla", objetivo: null, sets: [serie({ weight_kg: 100, reps: 3, hecha: true })] }],
  };

  expect(JSON.stringify(aPayload(sesion, 30, null))).not.toContain("hecha");
});

test("las series vacías no se mandan, pero las de peso corporal sí", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [
      {
        name: "Dominadas",
        objetivo: null,
        sets: [
          serie({ reps: 10 }),          // peso corporal: sin kilos pero con reps. Vale.
          serie({ weight_kg: 20 }),     // lastre apuntado y reps por rellenar. Vale.
          serie(),                      // fila que se añadió y nunca se tocó. Fuera.
        ],
      },
    ],
  };

  expect(aPayload(sesion, 30, null).exercises[0].sets).toEqual([{ reps: 10 }, { weight_kg: 20 }]);
});

test("un ejercicio que se queda sin series no se manda", () => {
  const sesion: Sesion = {
    ...SESION,
    exercises: [
      { name: "Press banca", objetivo: null, sets: [serie({ weight_kg: 80, reps: 5 })] },
      { name: "Se me olvidó hacerlo", objetivo: null, sets: [serie(), serie()] },
    ],
  };

  expect(aPayload(sesion, 30, null).exercises.map((e) => e.name)).toEqual(["Press banca"]);
});

test("natación manda sus campos y ninguno de fuerza", () => {
  const sesion: Sesion = {
    ...SESION,
    mode: "swimming",
    exercises: [
      {
        name: "Crol",
        objetivo: null,
        sets: [serie({ distance_m: 100, time_seconds: 95, style: "Crol", rest_seconds: 30 })],
      },
    ],
  };

  expect(aPayload(sesion, 40, null).exercises[0].sets).toEqual([
    { distance_m: 100, time_seconds: 95, style: "Crol", rest_seconds: 30 },
  ]);
});

test("una serie de natación está vacía si no tiene ni distancia ni tiempo", () => {
  expect(serieVacia(serie(), "swimming")).toBe(true);
  expect(serieVacia(serie({ distance_m: 50 }), "swimming")).toBe(false);
  expect(serieVacia(serie({ time_seconds: 60 }), "swimming")).toBe(false);
  // Y el peso no la salva: en natación no se manda y quedaría una fila sin nada dentro.
  expect(serieVacia(serie({ weight_kg: 80 }), "swimming")).toBe(true);
});

test("las notas vacías van como null, no como cadena vacía", () => {
  expect(aPayload(SESION, 45, "").notes).toBe(null);
  expect(aPayload(SESION, 45, "  ").notes).toBe(null);
  expect(aPayload(SESION, 45, "Buen día").notes).toBe("Buen día");
});
