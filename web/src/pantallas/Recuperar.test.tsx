/* La regla de seguridad de esta pantalla: avanza al paso 2 pase lo que pase.
 *
 * El servidor responde 200 exista o no el correo, a propósito, para no regalar una lista
 * de cuentas válidas. Si esta pantalla mirase la respuesta para decidir si avanza, la fuga
 * que el servidor evita la reabriría el cliente. Por eso hay tests aquí y no en cualquier
 * otra pantalla: es la única cuyo comportamiento correcto parece un error. */

import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { beforeEach, expect, test, vi } from "vitest";
import { ErrorApi, pedirCodigo } from "../api";
import Recuperar from "./Recuperar";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  pedirCodigo: vi.fn(),
  cambiarContrasena: vi.fn(),
}));

function pintar() {
  render(
    <MemoryRouter>
      <Recuperar />
    </MemoryRouter>,
  );
}

function pedirPara(correo: string) {
  fireEvent.change(screen.getByLabelText("correo"), { target: { value: correo } });
  fireEvent.click(screen.getByRole("button", { name: "ENVIAR CÓDIGO" }));
}

const AVISO = /Si ese correo está registrado/;

beforeEach(() => {
  vi.mocked(pedirCodigo).mockReset();
});

test("con un correo registrado avanza al paso 2", async () => {
  vi.mocked(pedirCodigo).mockResolvedValue({
    message: "Si ese correo está registrado, te hemos enviado un código.",
  });
  pintar();

  pedirPara("isra@local.test");

  expect(await screen.findByText(AVISO)).toBeTruthy();
  expect(screen.getByLabelText("código")).toBeTruthy();
});

test("avanza igual aunque el servidor conteste otra cosa", async () => {
  // Este es el test que importa. Si alguien escribiera «avanza solo si el mensaje dice
  // que se ha enviado», este caso se quedaría en el paso 1 y delataría que la cuenta no
  // existe. El componente no puede mirar el cuerpo de la respuesta.
  vi.mocked(pedirCodigo).mockResolvedValue({ message: "cualquier otra cosa" });
  pintar();

  pedirPara("nadie-de-nada@local.test");

  expect(await screen.findByText(AVISO)).toBeTruthy();
});

test("el texto del paso 2 es el mismo en los dos casos", async () => {
  vi.mocked(pedirCodigo).mockResolvedValue({ message: "registrado" });
  pintar();
  pedirPara("existe@local.test");
  const conCuenta = (await screen.findByText(AVISO)).textContent;

  cleanup();

  vi.mocked(pedirCodigo).mockResolvedValue({ message: "no registrado" });
  pintar();
  pedirPara("noexiste@local.test");
  const sinCuenta = (await screen.findByText(AVISO)).textContent;

  expect(sinCuenta).toBe(conCuenta);
});

test("un 429 no avanza, porque no dice nada sobre si la cuenta existe", async () => {
  vi.mocked(pedirCodigo).mockRejectedValue(
    new ErrorApi({
      general: "Demasiados intentos. Espera un momento y vuelve a probar.",
      campos: {},
    }),
  );
  pintar();

  pedirPara("isra@local.test");

  const aviso = await screen.findByRole("alert");
  expect(aviso.textContent).toBe("Demasiados intentos. Espera un momento y vuelve a probar.");
  expect(screen.queryByText(AVISO)).toBeNull();
});

test("los corchetes del botón no forman parte de su nombre", () => {
  // `[ ENVIAR CÓDIGO ]` se leería «corchete ENVIAR CÓDIGO corchete». Los corchetes son
  // dibujo, y esta consulta por nombre exacto falla si dejan de estar aria-hidden.
  pintar();

  expect(screen.getByRole("button", { name: "ENVIAR CÓDIGO" })).toBeTruthy();
});
