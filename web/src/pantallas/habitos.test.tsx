/* Beber agua es lo que más veces se toca al día. Es optimista a propósito, y lo que hay
   que demostrar es lo otro: que cuando falla, la barra vuelve y se dice con palabras. */

import { fireEvent, render, screen } from "@testing-library/react";
import { beforeEach, expect, test, vi } from "vitest";
import { ErrorApi, agua, anadirAgua } from "../api";
import { SeccionAgua } from "./habitos";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  agua: vi.fn(),
  anadirAgua: vi.fn(),
}));

const pintar = (alGanar = () => {}) =>
  render(<SeccionAgua fecha="2026-08-20" alGanar={alGanar} />);

beforeEach(() => {
  vi.mocked(agua).mockResolvedValue({
    date: "2026-08-20", total_ml: 1250, goal_ml: 2000, pct: 63, entries: [],
  });
});

test("un vaso sube la barra antes de que conteste el servidor", async () => {
  // Una petición que no resuelve nunca: si la barra espera, este test lo caza.
  vi.mocked(anadirAgua).mockReturnValue(new Promise(() => {}) as never);
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,5 de 2 litros")).toBeTruthy();
});

test("si el vaso no se guarda, la barra vuelve atrás y se dice por qué", async () => {
  vi.mocked(anadirAgua).mockRejectedValue(
    new ErrorApi({ general: "No hay conexión. Comprueba el wifi o los datos.", campos: {} }),
  );
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,25 de 2 litros")).toBeTruthy();
  expect(screen.getByText("No se ha podido guardar el vaso. Comprueba la conexión.")).toBeTruthy();
});

test("el total que manda el servidor gana al optimista", async () => {
  // Otro dispositivo pudo apuntar agua entre medias.
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 1900, goal_ml: 2000, pct: 95, system: {} as never,
  });
  pintar();

  fireEvent.click(await screen.findByRole("button", { name: "+1 VASO" }));

  expect(await screen.findByText("1,9 de 2 litros")).toBeTruthy();
});

test("llegar al objetivo sube el bloque system, que es quien abre la ventana", async () => {
  const ganados: unknown[] = [];
  const bloque = { achievements_unlocked: [{ key: "hydrated", name: "Hidratado" }] };
  vi.mocked(anadirAgua).mockResolvedValue({
    total_ml: 2000, goal_ml: 2000, pct: 100, system: bloque as never,
  });
  pintar((s) => ganados.push(s));

  fireEvent.click(await screen.findByRole("button", { name: "+MEDIO LITRO" }));

  await vi.waitFor(() => expect(ganados).toEqual([bloque]));
});

test("la barra se oye como un porcentaje, no como veinte caracteres", async () => {
  pintar();
  const barra = await screen.findByRole("progressbar");
  expect(barra.getAttribute("aria-valuetext")).toBeTruthy();
});
