/// <reference types="node" />

/* La paleta es lo único del sistema de diseño que se puede romper sin que falle nada:
   un hexadecimal mal puesto compila igual y solo se ve mirando la pantalla. Y hay dos
   cosas concretas que se rompen solas con el tiempo:

   1. Que alguien vuelva a poner el fondo en negro puro. Es tentador —es «más oscuro»—
      y tiene dos problemas que no se ven en el monitor de un escritorio: en OLED el
      píxel se apaga del todo y al desplazar deja estela, y el contraste contra el texto
      se dispara hasta deslumbrar.
   2. Que el color de fondo deje de coincidir en los tres sitios donde vive: el CSS, el
      <meta theme-color> del HTML y el manifiesto. El síntoma es una franja de otro color
      alrededor de la aplicación instalada, y solo se ve en el móvil.

   Los contrastes se calculan aquí a partir del CSS de verdad, así que la tabla del spec
   §5.5 deja de ser un número escrito a mano que nadie vuelve a comprobar.

   Los tres ficheros se leen del disco y no con `import ... ?raw`: sobre un .css eso
   devuelve cadena vacía, porque el plugin de CSS de Vite lo intercepta antes. La
   referencia de tipos de arriba deja los de Node en este fichero y solo en este; el
   código del navegador sigue viendo únicamente `vite/client`. */

import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";

const leer = (ruta: string) => readFileSync(new URL(ruta, import.meta.url), "utf8");

const css = leer("./estilos.css");
const html = leer("../index.html");
const manifiesto = leer("../public/manifest.webmanifest");

const paleta: Record<string, string> = Object.fromEntries(
  [...css.matchAll(/--([\w-]+):\s*(#[0-9a-f]{6})\b/g)].map((m) => [m[1], m[2]]),
);

/** WCAG 2.1, fórmula 1.4.3. */
function luminancia(hex: string): number {
  const canal = (v: number) => {
    const s = v / 255;
    return s <= 0.04045 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
  };
  const n = parseInt(hex.slice(1), 16);
  return (
    0.2126 * canal((n >> 16) & 255) +
    0.7152 * canal((n >> 8) & 255) +
    0.0722 * canal(n & 255)
  );
}

function contraste(a: string, b: string): number {
  const [claro, oscuro] = [luminancia(a), luminancia(b)].sort((x, y) => y - x);
  return (claro + 0.05) / (oscuro + 0.05);
}

describe("la paleta", () => {
  it("define los once colores del spec", () => {
    expect(Object.keys(paleta)).toEqual([
      "fondo",
      "superficie",
      "lineas",
      "texto",
      "apagado",
      "ambar",
      "verde",
      "azul",
      "rojo",
      "cian",
      "morado",
    ]);
  });

  it("no usa negro puro en ningún sitio", () => {
    expect(css).not.toMatch(/#000\b|#000000\b/i);
    expect(luminancia(paleta.fondo)).toBeGreaterThan(0);
  });

  // Los seis de acento más el texto. `apagado` queda fuera a propósito: el spec §5.3 lo
  // limita a texto secundario nunca esencial, y por eso puede estar por debajo.
  it.each(["texto", "ambar", "verde", "azul", "rojo", "cian", "morado"])(
    "%s llega a 4,5:1 sobre el fondo",
    (nombre) => {
      expect(contraste(paleta[nombre], paleta.fondo)).toBeGreaterThanOrEqual(4.5);
    },
  );

  it("mantiene `apagado` en los 2,7:1 que el spec da por medidos", () => {
    expect(contraste(paleta.apagado, paleta.fondo)).toBeCloseTo(2.7, 1);
  });

  it("sube de fondo a superficie a líneas, en ese orden", () => {
    expect(luminancia(paleta.fondo)).toBeLessThan(luminancia(paleta.superficie));
    expect(luminancia(paleta.superficie)).toBeLessThan(luminancia(paleta.lineas));
  });
});

describe("el color de fondo", () => {
  it("es el mismo en el CSS, en el <meta theme-color> y en el manifiesto", () => {
    expect(html).toContain(`content="${paleta.fondo}"`);
    const { theme_color, background_color } = JSON.parse(manifiesto);
    expect(theme_color).toBe(paleta.fondo);
    expect(background_color).toBe(paleta.fondo);
  });
});
