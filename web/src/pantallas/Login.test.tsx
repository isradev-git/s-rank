/* El caso que dejó la aplicación colgada en producción el 18 de agosto: el login responde
 * 200 y la sesión no queda abierta. Si esta pantalla no espera a saber quién ha entrado, el
 * botón se queda en «ENTRANDO…» para siempre y no hay nada escrito que explique por qué. */

import { fireEvent, render, screen } from "@testing-library/react";
import { MemoryRouter } from "react-router";
import { expect, test, vi } from "vitest";
import { ErrorApi, entrar } from "../api";
import Login from "./Login";

vi.mock("../api", async (importOriginal) => ({
  ...(await importOriginal<typeof import("../api")>()),
  entrar: vi.fn(),
}));

function entrarCon(alEntrar: () => Promise<void>) {
  render(
    <MemoryRouter>
      <Login alEntrar={alEntrar} />
    </MemoryRouter>,
  );
  fireEvent.change(screen.getByLabelText("correo"), { target: { value: "isra@local.test" } });
  fireEvent.change(screen.getByLabelText("contraseña"), { target: { value: "contrasena8" } });
  fireEvent.click(screen.getByRole("button", { name: "ENTRAR" }));
}

test("si la sesión no queda abierta, lo dice y deja volver a intentarlo", async () => {
  vi.mocked(entrar).mockResolvedValue({ user_name: "Isra" });

  entrarCon(() =>
    Promise.reject(
      new ErrorApi({
        general: "Has entrado, pero tu navegador no ha guardado la sesión.",
        campos: {},
      }),
    ),
  );

  const aviso = await screen.findByRole("alert");
  expect(aviso.textContent).toMatch(/no ha guardado la sesión/);
  // Y el botón vuelve a estar disponible: quedarse en «ENTRANDO…» es la trampa de la que
  // sale este test.
  expect(screen.getByRole("button", { name: "ENTRAR" })).toBeTruthy();
});
