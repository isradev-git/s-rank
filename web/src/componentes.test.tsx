/* La ventana del Sistema es la única recompensa visual de toda la aplicación, y la regla
   que la sostiene: aparece por cuatro cosas y por ninguna más. Si saltara por cualquier
   otra, dejaría de significar algo. */

import { fireEvent, render, screen } from "@testing-library/react";
import { expect, test, vi } from "vitest";
import type { BloqueSistema } from "./api";
import { Aviso, VentanaSistema } from "./componentes";

function sistema(campos: Partial<BloqueSistema> = {}): BloqueSistema {
  return {
    xp_gained: 80,
    level_up: null,
    rank_up: null,
    achievements_unlocked: [],
    records: [],
    quests_completed: [],
    progress: {
      level: 4, rank: "E", xp_total: 1200, xp_into_level: 240, xp_for_next: 400,
      current_streak: 12, longest_streak: 20,
      stats: { strength: 3, endurance: 5, consistency: 8, vitality: 2 },
    },
    ...campos,
  };
}

/** Lo que leería un lector de pantalla: el texto sin nada marcado como dibujo. */
function loQueSeLee(nodo: HTMLElement): string {
  const copia = nodo.cloneNode(true) as HTMLElement;
  copia.querySelectorAll('[aria-hidden="true"]').forEach((n) => n.remove());
  return copia.textContent!.replace(/\s+/g, " ").trim();
}

test("sin nivel, rango, logro ni récord no hay ventana del Sistema", () => {
  // Solo XP. Si la ventana saltara también por esto, saltaría en todos los entrenos y
  // dejaría de ser un premio.
  const { container } = render(<VentanaSistema sistema={sistema()} alCerrar={() => {}} />);
  expect(container.innerHTML).toBe("");
});

test("subir de nivel la abre", () => {
  render(<VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={() => {}} />);
  expect(screen.getByRole("dialog")).toBeTruthy();
  expect(screen.getByText("Nivel 5")).toBeTruthy();
});

test("subir de rango la abre y lo dice por su nombre", () => {
  render(<VentanaSistema sistema={sistema({ rank_up: { from: "E", to: "D" } })} alCerrar={() => {}} />);
  expect(screen.getByText("Rango D")).toBeTruthy();
});

test("un logro la abre con su nombre, no con su clave", () => {
  render(
    <VentanaSistema
      sistema={sistema({ achievements_unlocked: [{ key: "workouts_50", name: "Medio Centenar", rarity: "epic" }] })}
      alCerrar={() => {}}
    />,
  );
  expect(screen.getByText("Medio Centenar")).toBeTruthy();
  expect(document.body.textContent).not.toContain("workouts_50");
});

test("un récord la abre con su marca anterior", () => {
  // El fixture usa la forma de `RecordDelSistema` (exercise/kind/value/previous), que es
  // la que trae `sistema.records`. Es distinta de `NuevoRecord` (name/weight_kg/
  // previous_pr/is_first), la de `new_records` de primer nivel: api.ts ya avisa de esa
  // trampa justo porque el mismo récord llega en dos formas distintas.
  render(
    <VentanaSistema
      sistema={sistema({ records: [{ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }] })}
      alCerrar={() => {}}
    />,
  );
  expect(screen.getByText("Press banca: 85 kg, antes 80 kg.")).toBeTruthy();
});

test("los ángulos de la ventana no se leen", () => {
  render(<VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={() => {}} />);

  // El adorno de esquinas es dibujo de terminal. Quien no sepa qué es una terminal tiene
  // que poder usar esto igual, y quien use lector de pantalla no puede oírlo.
  const leido = loQueSeLee(screen.getByRole("dialog"));
  expect(leido).not.toContain("┐");
  expect(leido).toContain("Nivel 5");
});

test("la ventana se lleva el foco al abrirse y lo devuelve al cerrarse", () => {
  // Quien navega con teclado tiene que acabar dentro. Sin esto el foco se queda en el
  // botón de detrás y hay que tabular a ciegas por media pantalla hasta dar con CERRAR,
  // y el lector de pantalla no anuncia una ventana en la que el foco nunca entra.
  const antes = document.createElement("button");
  document.body.append(antes);
  antes.focus();

  const { unmount } = render(
    <VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={() => {}} />,
  );

  expect(document.activeElement).toBe(screen.getByRole("button", { name: "CERRAR" }));

  unmount();
  expect(document.activeElement).toBe(antes);

  // `render`/`cleanup` no tocan lo que se añade a mano al body: si no se quita, el
  // siguiente test de este fichero encuentra este botón colgado.
  antes.remove();
});

test("Escape cierra la ventana y tabular no se sale de ella", () => {
  const alCerrar = vi.fn();
  render(
    <VentanaSistema sistema={sistema({ level_up: { from: 4, to: 5 } })} alCerrar={alCerrar} />,
  );
  const ventana = screen.getByRole("dialog");

  fireEvent.keyDown(ventana, { key: "Escape" });
  expect(alCerrar).toHaveBeenCalledTimes(1);

  // Una ventana que dice `aria-modal` no puede dejar que el tabulador se escape detrás.
  const tab = fireEvent.keyDown(ventana, { key: "Tab" });
  expect(tab).toBe(false); // `preventDefault` la canceló
});

test("el aviso se anuncia solo cuando es urgente", () => {
  // Un aviso rojo es una pérdida de datos en curso: el lector de pantalla tiene que
  // interrumpir. Uno ámbar es informativo y puede esperar al turno.
  const { rerender } = render(<Aviso tono="rojo">No se puede guardar</Aviso>);
  expect(screen.getByRole("alert")).toBeTruthy();

  rerender(<Aviso tono="ambar">1 entreno pendiente de subir</Aviso>);
  expect(screen.getByRole("status")).toBeTruthy();
});
