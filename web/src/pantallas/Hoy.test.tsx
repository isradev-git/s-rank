/* «Hoy» es la única pantalla con estados que se pueden equivocar en silencio, y la que
   más obligaciones de la regla rectora concentra: la decoración no se lee y sin conexión
   se explica en español con un botón para volver a probar. */

import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, expect, test, vi } from "vitest";
import { ErrorApi, diaDeHoy, type DiaDeHoy, type Usuario } from "../api";
import Hoy from "./Hoy";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  diaDeHoy: vi.fn(),
}));

const USUARIO: Usuario = { name: "Isra", email: "isra@local.test", is_admin: false };

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
};

/** Lo que leería un lector de pantalla: el texto sin nada de lo marcado como dibujo.
 *  Es la regla rectora expresada como función, y por eso se puede probar. */
function loQueSeLee(nodo: HTMLElement): string {
  const copia = nodo.cloneNode(true) as HTMLElement;
  copia.querySelectorAll('[aria-hidden="true"]').forEach((n) => n.remove());
  return copia.textContent!.replace(/\s+/g, " ").trim();
}

beforeEach(() => {
  vi.mocked(diaDeHoy).mockReset();
});

test("sin conexión lo dice en español y deja volver a probar", async () => {
  vi.mocked(diaDeHoy).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  render(<Hoy usuario={USUARIO} />);

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
  render(<Hoy usuario={USUARIO} />);

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
  render(<Hoy usuario={USUARIO} />);

  const titulo = await screen.findByRole("heading");

  // En pantalla pone `isra[E]@s-rank $ hoy`. Se oye «hoy», y nada más: nadie tiene que
  // saber lo que es un prompt para usar la aplicación.
  expect(titulo.textContent).toContain("isra[E]@s-rank");
  expect(loQueSeLee(titulo)).toBe("hoy");
});

test("la barra de XP se anuncia como un porcentaje, no como veinte caracteres", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  render(<Hoy usuario={USUARIO} />);

  const barra = await screen.findByRole("progressbar");

  // 240 de 400 son el 60%.
  expect(barra.getAttribute("aria-valuetext")).toBe("60%");
  expect(barra.getAttribute("aria-valuenow")).toBe("60");
});

test("las opcionales van aparte y no cuentan en el marcador", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  render(<Hoy usuario={USUARIO} />);

  expect(await screen.findByText("si te sobra tiempo")).toBeTruthy();

  // Una hecha de dos obligatorias. Si «8.000 pasos» contara, diría «1 de 3» y daría la
  // impresión de que dejarla sin hacer cuesta algo.
  expect(screen.getByText("1 de 2")).toBeTruthy();
});

test("la fecha sale del servidor, no del reloj del navegador", async () => {
  vi.mocked(diaDeHoy).mockResolvedValue(DIA);
  render(<Hoy usuario={USUARIO} />);

  // El 12 de agosto de 2026 fue miércoles. Leído y escrito en UTC, así que ninguna zona
  // horaria puede correrlo un día.
  expect(await screen.findByText(/miércoles, 12 de agosto/)).toBeTruthy();
});
