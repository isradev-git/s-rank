/* Una foto de móvil son 4 MB y el servidor acepta 2. Sin encoger, subir una foto falla
   siempre y el usuario no sabe por qué. Y hay quien tiene los datos contados. */

import { expect, test, vi } from "vitest";
import { LADO_MAXIMO, encoger } from "./foto";

/* jsdom no implementa ni createImageBitmap ni el 2d de canvas, así que se sustituyen por
   los mínimos que esta función usa. Lo que se prueba es la decisión —qué tamaño elige y
   qué formato pide—, no el dibujado, que es del navegador. */
function navegadorConImagenDe(ancho: number, alto: number) {
  vi.stubGlobal("createImageBitmap", vi.fn(async () => ({ width: ancho, height: alto, close() {} })));

  const dibujado: { w: number; h: number }[] = [];
  const pedido: { tipo: string; calidad: number }[] = [];

  vi.spyOn(HTMLCanvasElement.prototype, "getContext").mockReturnValue({
    drawImage: (_img: unknown, _x: number, _y: number, w: number, h: number) =>
      dibujado.push({ w, h }),
  } as unknown as CanvasRenderingContext2D);

  vi.spyOn(HTMLCanvasElement.prototype, "toBlob").mockImplementation(
    (cb: BlobCallback, tipo?: string, calidad?: number) => {
      pedido.push({ tipo: tipo ?? "", calidad: calidad ?? 0 });
      cb(new Blob(["x"], { type: "image/jpeg" }));
    },
  );

  return { dibujado, pedido };
}

test("una foto grande se encoge por el lado mayor y mantiene la proporción", async () => {
  const { dibujado } = navegadorConImagenDe(4032, 3024);

  await encoger(new File(["x"], "foto.jpg", { type: "image/jpeg" }));

  expect(dibujado[0].w).toBe(LADO_MAXIMO);
  expect(dibujado[0].h).toBe(Math.round((3024 / 4032) * LADO_MAXIMO));
});

test("una foto vertical se encoge por el alto, que es su lado mayor", async () => {
  const { dibujado } = navegadorConImagenDe(3024, 4032);

  await encoger(new File(["x"], "foto.jpg", { type: "image/jpeg" }));

  expect(dibujado[0].h).toBe(LADO_MAXIMO);
});

test("una foto que ya es pequeña no se agranda", async () => {
  const { dibujado } = navegadorConImagenDe(600, 400);

  await encoger(new File(["x"], "foto.jpg", { type: "image/jpeg" }));

  expect(dibujado[0]).toEqual({ w: 600, h: 400 });
});

test("sale siempre como JPEG comprimido, aunque entre un PNG", async () => {
  const { pedido } = navegadorConImagenDe(2000, 2000);

  const salida = await encoger(new File(["x"], "captura.png", { type: "image/png" }));

  expect(pedido[0].tipo).toBe("image/jpeg");
  expect(pedido[0].calidad).toBe(0.8);
  expect(salida.type).toBe("image/jpeg");
  expect(salida.name.endsWith(".jpg")).toBe(true);
});
