import { expect, test } from "vitest";
import { BLOQUES, bloquesEncendidos, textoRacha } from "./formato";

test("los extremos dan barra vacía y barra llena", () => {
  expect(bloquesEncendidos(0)).toBe(0);
  expect(bloquesEncendidos(100)).toBe(BLOQUES);
});

test("nunca se sale de los diez bloques, pase lo que pase", () => {
  for (const fuera of [-1, -999, 101, 1000, Infinity, -Infinity, NaN]) {
    const n = bloquesEncendidos(fuera);
    expect(n, `${fuera} dio ${n}`).toBeGreaterThanOrEqual(0);
    expect(n, `${fuera} dio ${n}`).toBeLessThanOrEqual(BLOQUES);
  }
});

test("los vacíos son siempre el resto: los dos lados cuentan contra el mismo límite", () => {
  for (let pct = 0; pct <= 100; pct++) {
    const llenos = bloquesEncendidos(pct);
    expect(llenos + (BLOQUES - llenos)).toBe(BLOQUES);
  }
});

test("avanza a saltos del diez por ciento", () => {
  expect(bloquesEncendidos(60)).toBe(6);
  // 240 de 400 XP, el ejemplo del spec §6.
  expect(bloquesEncendidos((240 / 400) * 100)).toBe(6);
});

test("el nivel máximo no revienta la barra", () => {
  // El servidor manda xp_for_next = 0 a quien ya no tiene siguiente nivel.
  expect(bloquesEncendidos((0 / 0) * 100)).toBe(0);
  expect(bloquesEncendidos((50 / 0) * 100)).toBe(0);
});

test("la racha se dice en español y en singular cuando toca", () => {
  expect(textoRacha(0)).toBe("todavía sin racha");
  expect(textoRacha(1)).toBe("racha de 1 día");
  expect(textoRacha(2)).toBe("racha de 2 días");
  expect(textoRacha(12)).toBe("racha de 12 días");
});

test("una racha imposible no enseña un número negativo", () => {
  expect(textoRacha(-3)).toBe("todavía sin racha");
});
