/* «Hoy» es la única pantalla con estados que se pueden equivocar en silencio, y la que
   más obligaciones de la regla rectora concentra: la decoración no se lee y sin conexión
   se explica en español con un botón para volver a probar. */

import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, expect, test, vi } from "vitest";
import { MemoryRouter } from "react-router";
import {
  ErrorApi, actividad, agua, anadirAgua, comidasDelDia, diaDeHoy, objetivoNutricional,
  suplementos, type DiaDeHoy, type Usuario,
} from "../api";
import { guardar, leer, type Serie, type Sesion } from "../borrador";
import Hoy from "./Hoy";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  diaDeHoy: vi.fn(),
  comidasDelDia: vi.fn(),
  objetivoNutricional: vi.fn(),
  agua: vi.fn(),
  anadirAgua: vi.fn(),
  suplementos: vi.fn(),
  marcarSuplemento: vi.fn(),
  actividad: vi.fn(),
  guardarActividad: vi.fn(),
  guardarPeso: vi.fn(),
}));

// Los campos del cuerpo van a null: quien no ha pasado por el asistente los tiene así, y
// es el caso que más veces se abre esta pantalla.
const USUARIO: Usuario = {
  name: "Isra",
  email: "isra@local.test",
  is_admin: false,
  weight: null,
  height: null,
  age: null,
  gender: null,
  weekly_goal: null,
  water_goal_ml: null,
};

const DIA: DiaDeHoy = {
  date: "2026-08-12",
  progress: {
    level: 4,
    rank: "E",
    xp_into_level: 240,
    xp_for_next: 400,
    current_streak: 12,
  },
  quests: [
    { key: "water", label: "Beber 2 litros de agua", target: 2000, progress: 2000, xp_reward: 20, is_optional: false, completed: true },
    { key: "train", label: "Entrenar", target: 3, progress: 1, xp_reward: 20, is_optional: false, completed: false },
    { key: "steps_8000", label: "8.000 pasos", target: 8000, progress: 5240, xp_reward: 15, is_optional: true, completed: false },
  ],
  suggested_workout: {
    reason: "Te faltan 2 entrenos para tu meta de esta semana.",
    weekly_done: 1,
    weekly_goal: 3,
    template: null,
  },
};

/** Lo que leería un lector de pantalla: el texto sin nada de lo marcado como dibujo.
 *  Es la regla rectora expresada como función, y por eso se puede probar. */
function loQueSeLee(nodo: HTMLElement): string {
  const copia = nodo.cloneNode(true) as HTMLElement;
  copia.querySelectorAll('[aria-hidden="true"]').forEach((n) => n.remove());
  return copia.textContent!.replace(/\s+/g, " ").trim();
}

/** La pantalla lleva enlaces desde la tarea 17 y un `<Link>` fuera de un router revienta. */
const pintar = () =>
  render(
    <MemoryRouter>
      <Hoy usuario={USUARIO} />
    </MemoryRouter>,
  );

function serie(campos: Partial<Serie> = {}): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: null, hecha: false,
    ...campos,
  };
}

/** Un borrador que empezó hace tantos minutos.
 *
 *  Relativo al reloj de verdad y no con `vi.setSystemTime`: los temporizadores falsos de
 *  Vitest no los detecta Testing Library —solo conoce los de Jest—, así que dejaría
 *  colgada cualquier espera de `findBy…`. Los cinco segundos de más evitan que el redondeo
 *  a la baja de `textoAntiguedad` caiga en el minuto anterior. */
function borradorDe(minutos: number): Sesion {
  return {
    v: 1,
    mode: "gym",
    nombre: "Torso pesado",
    inicio: new Date(Date.now() - minutos * 60_000 - 5_000).toISOString(),
    actual: 0,
    exercises: [
      {
        name: "Press banca",
        objetivo: null,
        sets: [serie({ weight_kg: 80, reps: 5, hecha: true }), serie({ weight_kg: 80, reps: 5, hecha: true })],
      },
    ],
  };
}

beforeEach(() => {
  vi.mocked(diaDeHoy).mockReset();
  localStorage.clear();

  // Las secciones de hábitos piden lo suyo al montarse. Sin estos valores se quedarían
  // en «cargando…» y cualquier consulta por su contenido agotaría el tiempo.
  vi.mocked(comidasDelDia).mockResolvedValue({
    date: "2026-08-12",
    meals: {
      breakfast: { items: [], calories: 0 },
      lunch: { items: [], calories: 0 },
      dinner: { items: [], calories: 0 },
      snack: { items: [], calories: 0 },
    },
    totals: { calories: 0, protein: 0, carbs: 0, fat: 0, fiber: 0, sugar: 0 },
    count: 0,
    calories_burned: 0,
  });
  vi.mocked(objetivoNutricional).mockResolvedValue({
    goal: { daily_calories: 2000 } as never,
    has_goal: true,
  });
  vi.mocked(agua).mockResolvedValue({
    date: "2026-08-12", total_ml: 1250, goal_ml: 2000, pct: 63, entries: [],
  });
  vi.mocked(suplementos).mockResolvedValue({ items: [], taken_count: 0, total_count: 0 });
  vi.mocked(actividad).mockResolvedValue({
    date: "2026-08-12", steps: 0, calories_burned: 0,
  });
});

test("sin conexión lo dice en español y deja volver a probar", async () => {
  vi.mocked(diaDeHoy).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  pintar();

  const aviso = await screen.findByRole("alert");
  expect(aviso.textContent).toBe("No hay conexión. Comprueba el wifi o los datos.");
  // Ningún número de error en pantalla, nunca.
  expect(document.body.textContent).not.toMatch(/\b[45]\d\d\b/);

  // Y al volver la conexión, reintentar tiene que traer los datos de verdad.
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  fireEvent.click(screen.getByRole("button", { name: "REINTENTAR" }));

  expect(await screen.findByText("NIVEL 4")).toBeTruthy();
});

test("una misión hecha se oye con su estado, no con los corchetes", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  const hecha = (await screen.findByText("Beber 2 litros de agua")).closest("li")!;
  const pendiente = screen.getByText("Entrenar").closest("li")!;

  // Si los `[✓]` dejaran de estar aria-hidden se oiría «corchete, marca de verificación,
  // corchete, Beber 2 litros de agua», que es exactamente lo que la regla rectora prohíbe.
  expect(loQueSeLee(hecha)).toBe("Beber 2 litros de agua, hecha");

  // El avance parcial va en su propio elemento; el lector hace una pausa ahí aunque
  // `textContent` los pegue, así que se comprueban por separado.
  const leido = loQueSeLee(pendiente);
  expect(leido.startsWith("Entrenar, pendiente")).toBe(true);
  expect(leido).toContain("1 de 3");
});

test("el prompt del título no se lee", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  const titulo = await screen.findByRole("heading");

  // En pantalla pone `isra[E]@s-rank $ hoy`. Se oye «hoy», y nada más: nadie tiene que
  // saber lo que es un prompt para usar la aplicación.
  expect(titulo.textContent).toContain("isra[E]@s-rank");
  expect(loQueSeLee(titulo)).toBe("hoy");
});

test("la barra de XP se anuncia como un porcentaje, no como veinte caracteres", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  const barra = await screen.findByRole("progressbar");

  // 240 de 400 son el 60%.
  expect(barra.getAttribute("aria-valuetext")).toBe("60%");
  expect(barra.getAttribute("aria-valuenow")).toBe("60");
});

test("las opcionales van aparte y no cuentan en el marcador", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  expect(await screen.findByText("si te sobra tiempo")).toBeTruthy();

  // Una hecha de dos obligatorias. Si «8.000 pasos» contara, diría «1 de 3» y daría la
  // impresión de que dejarla sin hacer cuesta algo.
  expect(screen.getByText("1 de 2")).toBeTruthy();
});

test("la fecha sale del servidor, no del reloj del navegador", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  // El 12 de agosto de 2026 fue miércoles. Leído y escrito en UTC, así que ninguna zona
  // horaria puede correrlo un día.
  expect(await screen.findByText(/miércoles, 12 de agosto/)).toBeTruthy();
});

test("un entreno a medias se ofrece con su antigüedad y lo que lleva", async () => {
  guardar(borradorDe(12));
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  expect(await screen.findByText(/Torso pesado/)).toBeTruthy();
  expect(screen.getByText(/2 series/)).toBeTruthy();
  expect(screen.getByText(/de hace 12 minutos/)).toBeTruthy();
  expect(screen.getByRole("link", { name: "SEGUIR ENTRENANDO" })).toBeTruthy();
});

test("un borrador de anteayer se ofrece igual, no se tira solo", async () => {
  guardar(borradorDe(2 * 24 * 60));
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  // Que sea viejo no lo hace basura: puede ser justo el que hay que recuperar.
  expect(await screen.findByText(/de hace 2 días/)).toBeTruthy();
  expect(screen.getByRole("link", { name: "SEGUIR ENTRENANDO" })).toBeTruthy();
});

test("descartar el borrador pide confirmación", async () => {
  guardar(borradorDe(12));
  vi.spyOn(window, "confirm").mockReturnValue(false);
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "DESCARTAR" }));

  // Es la única forma de perder un entreno a propósito, y tiene que costar dos pulsaciones.
  expect(leer()).not.toBe(null);
});

test("sin borrador se ofrece empezar, con el motivo que manda el servidor", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();

  expect(await screen.findByText("Te faltan 2 entrenos para tu meta de esta semana.")).toBeTruthy();
  expect(screen.getByRole("link", { name: "ENTRENAR" })).toBeTruthy();
});

const PROGRESO = DIA.progress;

test("«hoy» monta las cuatro secciones de hábitos y el resumen de nutrición", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();
  await screen.findByText("Misiones de hoy");

  expect(screen.getByText("Nutrición")).toBeTruthy();
  expect(screen.getByText("Agua")).toBeTruthy();
  expect(screen.getByText("Suplementos")).toBeTruthy();
  expect(screen.getByText("Actividad")).toBeTruthy();
  expect(screen.getByText("Peso")).toBeTruthy();
});

test("sin misión de proteína se invita a configurar el objetivo", async () => {
  // La misión de proteína solo existe si el usuario tiene objetivo nutricional. Que no
  // esté es la señal de que no lo tiene, y el momento de invitarle.
  vi.mocked(diaDeHoy).mockResolvedValue({
    date: "2026-08-20",
    progress: PROGRESO,
    quests: [{ key: "water", label: "Beber 2 litros de agua", target: 2000, progress: 0,
               xp_reward: 20, is_optional: false, completed: false }],
    suggested_workout: { reason: "", weekly_done: 0, weekly_goal: 3, template: null },
  } as never);

  pintar();

  expect(await screen.findByRole("link", { name: /CALCULAR MI OBJETIVO/ })).toBeTruthy();
});

test("con misión de proteína no se invita a nada", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue({
    date: "2026-08-20",
    progress: PROGRESO,
    quests: [{ key: "protein", label: "Llegar a 150 g de proteína", target: 150, progress: 40,
               xp_reward: 30, is_optional: false, completed: false }],
    suggested_workout: { reason: "", weekly_done: 0, weekly_goal: 3, template: null },
  } as never);

  pintar();
  await screen.findByText("Misiones de hoy");

  expect(screen.queryByRole("link", { name: /CALCULAR MI OBJETIVO/ })).toBe(null);
});

test("un logro que llega desde una sección abre la ventana del Sistema", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 2000, goal_ml: 2000, pct: 100,
    system: {
      xp_gained: 20, level_up: null, rank_up: null,
      achievements_unlocked: [{ key: "hydrated", name: "Hidratado", rarity: "common" }],
      records: [], quests_completed: ["water"], progress: PROGRESO,
    } as never,
  });

  pintar();
  fireEvent.click(await screen.findByRole("button", { name: "+MEDIO LITRO" }));

  expect(await screen.findByRole("dialog", { name: "El Sistema" })).toBeTruthy();
  expect(screen.getByText("Hidratado")).toBeTruthy();
});

test("no hay dos botones con el mismo nombre en la pantalla", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  pintar();
  await screen.findByText("Misiones de hoy");

  // Actividad y Peso tenían los dos un botón «GUARDAR». Se ven distintos porque están en
  // sitios distintos, pero quien navega con lector de pantalla oye la misma palabra dos
  // veces y no sabe cuál guarda qué.
  const nombres = screen.getAllByRole("button").map((b) => b.textContent);
  expect(new Set(nombres).size).toBe(nombres.length);
});

test("si el resumen de nutrición falla, las misiones se siguen viendo", async () => {
  // «Hoy» pasó de depender de una petición a depender de tres. Encadenadas en el mismo
  // try, un fallo en el resumen de nutrición dejaba la pantalla entera en el aviso de
  // error: sin misiones, sin nivel y sin racha, que ya habían llegado bien.
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  vi.mocked(comidasDelDia).mockRejectedValue(
    new ErrorApi({ general: "No hemos podido conectar. Inténtalo otra vez.", campos: {} }),
  );

  pintar();

  expect(await screen.findByText("Beber 2 litros de agua")).toBeTruthy();
  expect(screen.getByText("NIVEL 4")).toBeTruthy();
  // Y la sección de nutrición dice lo suyo, sin números inventados.
  expect(screen.getByText("no hemos podido cargar lo que llevas comido")).toBeTruthy();
});
