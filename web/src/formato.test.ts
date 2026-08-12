import assert from "node:assert/strict";
import { test } from "node:test";
import { BLOQUES, bloquesEncendidos, textoRacha } from "./formato.ts";

test("los extremos dan barra vacía y barra llena", () => {
  assert.equal(bloquesEncendidos(0), 0);
  assert.equal(bloquesEncendidos(100), BLOQUES);
});

test("nunca se sale de los diez bloques, pase lo que pase", () => {
  for (const fuera of [-1, -999, 101, 1000, Infinity, -Infinity, NaN]) {
    const n = bloquesEncendidos(fuera);
    assert.ok(n >= 0 && n <= BLOQUES, `${fuera} dio ${n}`);
  }
});

test("los vacíos son siempre el resto: los dos lados cuentan contra el mismo límite", () => {
  for (let pct = 0; pct <= 100; pct++) {
    const llenos = bloquesEncendidos(pct);
    assert.equal(llenos + (BLOQUES - llenos), BLOQUES);
  }
});

test("avanza a saltos del diez por ciento", () => {
  assert.equal(bloquesEncendidos(60), 6);
  // 240 de 400 XP, el ejemplo del spec §6.
  assert.equal(bloquesEncendidos((240 / 400) * 100), 6);
});

test("el nivel máximo no revienta la barra", () => {
  // El servidor manda xp_for_next = 0 a quien ya no tiene siguiente nivel.
  assert.equal(bloquesEncendidos((0 / 0) * 100), 0);
  assert.equal(bloquesEncendidos((50 / 0) * 100), 0);
});

test("la racha se dice en español y en singular cuando toca", () => {
  assert.equal(textoRacha(0), "todavía sin racha");
  assert.equal(textoRacha(1), "racha de 1 día");
  assert.equal(textoRacha(2), "racha de 2 días");
  assert.equal(textoRacha(12), "racha de 12 días");
});

test("una racha imposible no enseña un número negativo", () => {
  assert.equal(textoRacha(-3), "todavía sin racha");
});
