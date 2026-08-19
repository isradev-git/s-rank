/* El resumen no calcula nada del Sistema: pinta el bloque `system` que devolvió el
   servidor. Lo que estos tests vigilan es justo eso —que no se invente un XP cuando el
   servidor no ha hablado— y que un cero no se pinte como un fallo. */

import { fireEvent, render, screen, within } from "@testing-library/react";
import { MemoryRouter, Route, Routes } from "react-router";
import { expect, test } from "vitest";
import type { BloqueSistema, EntrenoGuardado } from "../api";
import Resumen from "./Resumen";

function sistema(campos: Partial<BloqueSistema> = {}): BloqueSistema {
  return {
    xp_gained: 80, level_up: null, rank_up: null,
    achievements_unlocked: [], records: [], quests_completed: [],
    progress: {
      level: 4, rank: "E", xp_total: 1200, xp_into_level: 240, xp_for_next: 400,
      current_streak: 12, longest_streak: 20,
      stats: { strength: 3, endurance: 5, consistency: 8, vitality: 2 },
    },
    ...campos,
  };
}

function pintar(estado: unknown) {
  return render(
    <MemoryRouter initialEntries={[{ pathname: "/entrenar/resumen", state: estado }]}>
      <Routes>
        <Route path="/entrenar/resumen" element={<Resumen />} />
        <Route path="/" element={<p>hoy</p>} />
      </Routes>
    </MemoryRouter>,
  );
}

const BASE = { nombre: "Torso pesado", duracion: 45, volumen: 3200, series: 12 };

test("con conexión sale el XP que dijo el servidor", () => {
  pintar({ ...BASE, guardado: { new_records: [], system: sistema() } as unknown as EntrenoGuardado });
  expect(screen.getByText("+80 XP")).toBeTruthy();
});

test("sin conexión se dice que se subirá solo, y no se inventa ningún XP", () => {
  // El XP lo decide el servidor y aquí todavía no ha hablado. Poner una cifra para tapar
  // el hueco sería reimplementar el cálculo en el cliente.
  pintar({ ...BASE, guardado: null });

  // Sin el «se»: la frase empieza por ahí y la ese va en mayúscula.
  expect(screen.getByRole("status").textContent).toContain("Se subirá solo");
  expect(document.body.textContent).not.toContain("XP");
});

test("el tercer entreno del día se explica sin parecer un error", () => {
  pintar({
    ...BASE,
    guardado: { new_records: [], system: sistema({ xp_gained: 0 }) } as unknown as EntrenoGuardado,
  });

  expect(
    screen.getByText("Guardado. Hoy ya has llegado al máximo de XP, así que este entreno no suma."),
  ).toBeTruthy();
  // Y nada de rojo: no ha fallado nada.
  expect(screen.queryByRole("alert")).toBe(null);
});

test("un récord abre la ventana del Sistema", () => {
  pintar({
    ...BASE,
    guardado: {
      new_records: [{ name: "Press banca", weight_kg: 85, previous_pr: 80, is_first: false }],
      system: sistema({
        records: [{ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }],
      }),
    } as unknown as EntrenoGuardado,
  });

  // Dentro de la ventana, no en la pantalla entera: el récord sale dos veces a propósito
  // —en la ventana, que es el premio, y en la lista, que es lo que queda cuando se cierra—
  // así que buscarlo suelto encontraría dos y no demostraría que la ventana lo lleva.
  const ventana = screen.getByRole("dialog");
  expect(within(ventana).getByText("Press banca: 85 kg, antes 80 kg.")).toBeTruthy();
});

test("al cerrar la ventana el récord sigue en la pantalla", () => {
  // La ventana se cierra de un botón y no vuelve. Si el récord viviera solo dentro de
  // ella, cerrarla lo borraría de la única pantalla que lo cuenta.
  pintar({
    ...BASE,
    guardado: {
      new_records: [],
      system: sistema({
        records: [{ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }],
      }),
    } as unknown as EntrenoGuardado,
  });

  fireEvent.click(screen.getByRole("button", { name: "CERRAR" }));

  expect(screen.queryByRole("dialog")).toBe(null);
  expect(screen.getByText("Press banca: 85 kg, antes 80 kg.")).toBeTruthy();
});

test("recargar el resumen lleva a hoy en vez de reventar", () => {
  // El resumen es una pantalla de paso: su contenido viaja en el estado de la navegación
  // y una recarga lo pierde. El dato ya está a salvo en el servidor o en la cola.
  pintar(undefined);
  expect(screen.getByText("hoy")).toBeTruthy();
});
