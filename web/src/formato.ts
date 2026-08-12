/* Los cálculos y los textos que tienen ramas de verdad. Viven fuera de los componentes
   para poderse probar sin montar React, que es lo que los deja probados de hecho. */

export const BLOQUES = 10;

/** Cuántos de los diez bloques de la barra de XP se encienden.
 *
 *  En la versión de Android este mismo cálculo llegó a tener el 10 escrito a mano en dos
 *  sitios: si se tocaba una copia y no la otra, los llenos y los vacíos contaban contra
 *  límites distintos y la barra se desbordaba. */
export function bloquesEncendidos(porcentaje: number): number {
  // xp_into_level / xp_for_next da Infinity o NaN cuando xp_for_next es 0, que es lo que
  // manda el servidor a quien ya está en el nivel máximo.
  if (!Number.isFinite(porcentaje)) return 0;
  const acotado = Math.min(100, Math.max(0, porcentaje));
  return Math.round((acotado / 100) * BLOQUES);
}

/** «racha de 12 días», «racha de 1 día», y nada de «racha de 0 días». */
export function textoRacha(dias: number): string {
  if (dias <= 0) return "todavía sin racha";
  return dias === 1 ? "racha de 1 día" : `racha de ${dias} días`;
}
