/** Cuántos de los diez bloques de la barra de XP se encienden.
 *
 *  Vive aparte del componente porque es la única aritmética con ramas de la pantalla, y
 *  así se puede probar sin montar React. En la versión de Android este mismo cálculo
 *  llegó a tener el 10 escrito a mano en dos sitios: si se tocaba una copia y no la otra,
 *  los llenos y los vacíos contaban contra límites distintos y la barra se desbordaba. */

export const BLOQUES = 10;

export function bloquesEncendidos(porcentaje: number): number {
  // xp / xpNivel da Infinity o NaN si el servidor manda un nivel sin XP objetivo.
  if (!Number.isFinite(porcentaje)) return 0;
  const acotado = Math.min(100, Math.max(0, porcentaje));
  return Math.round((acotado / 100) * BLOQUES);
}
