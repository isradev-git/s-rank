/// <reference types="node" />

/* El trabajador de servicio no lo ejecuta nadie hasta que la aplicación está instalada en
   un móvil y sin conexión, que es el peor sitio para descubrir un fallo. Y tiene una pieza
   que se rompe sola: `recursosDe` saca del HTML los nombres del bundle, que llevan hash,
   con una expresión regular. Si Vite cambia cómo emite esas etiquetas, deja de encontrar
   nada — sin error, sin aviso — y lo que queda guardado es un HTML que apunta a un
   JavaScript que no está. Eso es una pantalla en blanco.

   Aquí se carga el fichero de verdad en un entorno de mentira y se ejecuta su `install`
   contra el HTML que Vite genera hoy. */

import { describe, expect, it } from "vitest";
import { readFileSync } from "node:fs";

// El index.html tal y como lo emite `vite build`, con los dos nombres con hash.
const HTML = `<!doctype html>
<html lang="es">
  <head>
    <meta name="theme-color" content="#121216" />
    <link rel="manifest" href="/manifest.webmanifest" />
    <script type="module" crossorigin src="/assets/index-VozNxD8a.js"></script>
    <link rel="stylesheet" crossorigin href="/assets/index-BUFrQFQQ.css">
  </head>
  <body><div id="root"></div></body>
</html>`;

/** Ejecuta el sw.js real y devuelve lo que su `install` deja guardado. */
async function instalar(html: string): Promise<string[]> {
  // Ruta de texto, no `new URL(...)`: dentro de jsdom el URL que se construye no es el de
  // Node y `readFileSync` lo rechaza con «The URL must be of scheme file».
  const codigo = readFileSync(`${import.meta.dirname}/../public/sw.js`, "utf8");

  const oyentes: Record<string, (e: unknown) => void> = {};
  const guardado: string[] = [];

  const almacen = {
    addAll: async (rutas: string[]) => void guardado.push(...rutas),
    add: async (ruta: string) => void guardado.push(ruta),
    put: async () => {},
  };
  const cachesFalso = {
    open: async () => almacen,
    keys: async () => [],
    match: async () => undefined,
  };
  const selfFalso = {
    addEventListener: (nombre: string, f: (e: unknown) => void) => void (oyentes[nombre] = f),
    skipWaiting: async () => {},
    clients: { claim: async () => {} },
    location: { origin: "https://s-rank.israelzamora.es" },
  };
  const fetchFalso = async () => ({ clone: () => ({ text: async () => html }) });

  new Function("self", "caches", "fetch", codigo)(selfFalso, cachesFalso, fetchFalso);

  let terminado: Promise<unknown> = Promise.resolve();
  oyentes.install({ waitUntil: (p: Promise<unknown>) => void (terminado = p) } as never);
  await terminado;

  return guardado;
}

describe("la instalación del trabajador de servicio", () => {
  it("guarda el armazón y el bundle al que apunta", async () => {
    const guardado = await instalar(HTML);

    expect(guardado).toContain("/index.html");
    // Los dos que, si faltan, dejan la pantalla en blanco sin conexión.
    expect(guardado).toContain("/assets/index-VozNxD8a.js");
    expect(guardado).toContain("/assets/index-BUFrQFQQ.css");
  });

  it("guarda las dos fuentes, para que sin conexión no salga la del sistema", async () => {
    const guardado = await instalar(HTML);

    expect(guardado).toContain("/fuentes/jetbrains-mono-regular.ttf");
    expect(guardado).toContain("/fuentes/jetbrains-mono-bold.ttf");
  });

  it("se instala igual si el HTML no trae ningún bundle reconocible", async () => {
    // Degradar tiene que ser quedarse en el comportamiento de antes —guardar solo el
    // armazón— y nunca reventar la instalación: un trabajador que no instala es una app
    // que ya ni se puede añadir a la pantalla de inicio.
    const guardado = await instalar("<!doctype html><html><body>vacío</body></html>");

    expect(guardado).toContain("/index.html");
    expect(guardado.filter((r) => r.startsWith("/assets/"))).toEqual([]);
  });
});
