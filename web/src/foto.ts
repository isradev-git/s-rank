/* Una foto de un móvil actual son 3 o 4 MB y el servidor acepta 2. Sin encoger, subir una
   foto falla siempre y el mensaje no explica nada. Además hay gente con los datos
   contados: 200 KB frente a 4 MB es la diferencia entre subirla y no hacerlo.

   Se hace con <canvas>, que es del navegador: ninguna dependencia nueva. */

/** El lado mayor. A 1280 una foto de plato se ve perfectamente en un móvil y pesa unos
 *  200 KB en JPEG 0.8. */
export const LADO_MAXIMO = 1280;

const CALIDAD = 0.8;

export async function encoger(fichero: File): Promise<File> {
  const imagen = await createImageBitmap(fichero);

  // Una foto que ya es pequeña no se agranda: interpolar hacia arriba solo añade peso y
  // le quita nitidez.
  const escala = Math.min(1, LADO_MAXIMO / Math.max(imagen.width, imagen.height));
  const ancho = Math.round(imagen.width * escala);
  const alto = Math.round(imagen.height * escala);

  const lienzo = document.createElement("canvas");
  lienzo.width = ancho;
  lienzo.height = alto;
  lienzo.getContext("2d")!.drawImage(imagen, 0, 0, ancho, alto);
  imagen.close?.();

  const blob = await new Promise<Blob | null>((resolver) =>
    // Siempre JPEG, aunque entre un PNG: una captura de pantalla en PNG puede pesar más
    // que la foto original, y el servidor acepta los tres formatos igual.
    lienzo.toBlob(resolver, "image/jpeg", CALIDAD),
  );

  if (!blob) throw new Error("el navegador no pudo convertir la imagen");

  const nombre = fichero.name.replace(/\.[^.]+$/, "") + ".jpg";
  return new File([blob], nombre, { type: "image/jpeg" });
}
