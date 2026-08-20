/* La lista vive solo en este móvil, así que tiene que aguantar sola tres cosas: que no
   haya nada, que lo que haya sea de una versión anterior, y que localStorage no deje
   escribir. Ninguna de las tres puede dejar la pantalla de añadir comida rota. */

import { beforeEach, expect, test, vi } from "vitest";
import { MAXIMO, apuntar, recientes } from "./recientes";

const CAFE = { id: 1, nombre: "Café con leche", gramos: 200, tipo: "breakfast" as const, kcal100: 44 };
const TOSTADA = { id: 2, nombre: "Tostada integral", gramos: 60, tipo: "breakfast" as const, kcal100: 267 };

beforeEach(() => localStorage.clear());

test("sin nada guardado la lista está vacía y no revienta", () => {
  expect(recientes("breakfast")).toEqual([]);
});

test("lo último usado va primero", () => {
  apuntar(CAFE);
  apuntar(TOSTADA);
  expect(recientes("breakfast").map((r) => r.nombre)).toEqual([
    "Tostada integral",
    "Café con leche",
  ]);
});

test("repetir un alimento lo sube arriba en vez de duplicarlo", () => {
  apuntar(CAFE);
  apuntar(TOSTADA);
  apuntar({ ...CAFE, gramos: 250 });

  const lista = recientes("breakfast");
  expect(lista).toHaveLength(2);
  expect(lista[0].nombre).toBe("Café con leche");
  // Y se queda con la cantidad de la última vez, que es la que se ofrece.
  expect(lista[0].gramos).toBe(250);
});

test("el mismo alimento en otra comida es otra entrada", () => {
  apuntar(CAFE);
  apuntar({ ...CAFE, tipo: "snack" });

  expect(recientes("breakfast")).toHaveLength(1);
  expect(recientes("snack")).toHaveLength(1);
});

test("la lista se corta por el máximo y tira lo más viejo", () => {
  for (let i = 1; i <= MAXIMO + 5; i++) {
    apuntar({ id: i, nombre: `Alimento ${i}`, gramos: 100, tipo: "lunch", kcal100: 100 });
  }
  const lista = recientes("lunch");
  expect(lista).toHaveLength(MAXIMO);
  expect(lista[0].nombre).toBe(`Alimento ${MAXIMO + 5}`);
  expect(lista.some((r) => r.nombre === "Alimento 1")).toBe(false);
});

test("basura guardada por una versión anterior se ignora, no rompe la pantalla", () => {
  localStorage.setItem("srank.comidas-recientes", "{no es json");
  expect(recientes("breakfast")).toEqual([]);

  localStorage.setItem("srank.comidas-recientes", JSON.stringify({ v: 99, lista: [] }));
  expect(recientes("breakfast")).toEqual([]);
});

test("si localStorage no deja escribir, apuntar no lanza", () => {
  vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
    throw new Error("cuota llena");
  });
  // Perder un reciente no es perder un dato: la comida ya está guardada en el servidor.
  expect(() => apuntar(CAFE)).not.toThrow();
});
