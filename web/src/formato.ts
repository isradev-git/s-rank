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

import type { Ejercicio } from "./borrador";
import type { RecordDelSistema } from "./api";

/** Kilos movidos: peso × repeticiones, sumado. Es lo que alimenta la estadística de
 *  Fuerza en el servidor (spec §6.4). Una serie a medio rellenar no cuenta: 80 kg por
 *  «ninguna» repetición no son 80 kg movidos. */
export function volumenTotal(ejercicios: Ejercicio[]): number {
  let total = 0;
  for (const ejercicio of ejercicios) {
    for (const serie of ejercicio.sets) {
      if (serie.weight_kg != null && serie.reps != null) {
        total += serie.weight_kg * serie.reps;
      }
    }
  }
  return total;
}

export function seriesHechas(ejercicios: Ejercicio[]): number {
  return ejercicios.reduce(
    (total, ejercicio) => total + ejercicio.sets.filter((s) => s.hecha).length,
    0,
  );
}

/** El máximo que acepta `POST /api/workouts`. Un borrador de hace tres días daría 4.320 y
 *  el servidor lo rechazaría con un 422 justo cuando el usuario intenta salvarlo. */
const MAXIMO_MINUTOS = 600;

export function duracionMinutos(inicio: string, ahora: number = Date.now()): number {
  const minutos = Math.round((ahora - Date.parse(inicio)) / 60000);
  // Un móvil que ajusta la hora por NTP a mitad de sesión puede dejar el reloj detrás del
  // inicio, y la API rechaza los negativos.
  return Math.min(MAXIMO_MINUTOS, Math.max(0, minutos));
}

/** «de hace 12 minutos», «de ayer». Es lo que hace que retomar un borrador sea una
 *  decisión informada en vez de una sorpresa. */
export function textoAntiguedad(inicio: string, ahora: number = Date.now()): string {
  const segundos = Math.max(0, Math.floor((ahora - Date.parse(inicio)) / 1000));

  if (segundos < 60) return "de hace un momento";

  const minutos = Math.floor(segundos / 60);
  if (minutos < 60) return `de hace ${minutos} ${minutos === 1 ? "minuto" : "minutos"}`;

  const horas = Math.floor(minutos / 60);
  if (horas < 24) return `de hace ${horas} ${horas === 1 ? "hora" : "horas"}`;

  const dias = Math.floor(horas / 24);
  return dias === 1 ? "de ayer" : `de hace ${dias} días`;
}

/** El XP lo decide el servidor: aquí solo se escribe lo que ha dicho.
 *
 *  Un cero no es un error y no puede parecerlo. Puede venir de tres sitios: el entreno
 *  duró menos de 15 minutos, ya hay dos entrenos con XP hoy, o se llegó al tope de 300 XP
 *  diarios. La duración se conoce desde aquí; los otros dos no se distinguen, así que el
 *  texto dice lo que es cierto en ambos en vez de adivinar cuál fue. */
export function textoXpGanado(xpGanado: number, duracion: number): string {
  if (xpGanado > 0) return `+${xpGanado} XP`;
  if (duracion < 15) return "Guardado. Un entreno suma XP a partir de los 15 minutos.";
  return "Guardado. Hoy ya has llegado al máximo de XP, así que este entreno no suma.";
}

/** Recibe la forma del bloque `system`, que es la que lee toda la interfaz. `previous` a
 *  `null` es la primera marca de ese ejercicio. */
export function textoRecord(record: RecordDelSistema): string {
  return record.previous == null
    ? `${record.exercise}: ${record.value} kg, tu primera marca.`
    : `${record.exercise}: ${record.value} kg, antes ${record.previous} kg.`;
}

/** Los seis macros de una comida, ya en la cantidad que se comió. */
export type Macros = {
  calories: number;
  protein: number;
  carbs: number;
  fat: number;
  fiber: number;
  sugar: number;
};

/** Lo que trae un alimento del catálogo: siempre por 100 g o por 100 ml. */
export type PorCien = {
  calories_per_100g: number;
  protein_per_100g: number;
  carbs_per_100g: number;
  fat_per_100g: number;
  fiber_per_100g: number;
  sugar_per_100g: number;
};

/** Dos decimales, igual que `FoodItem::macrosForQuantity()`. Que no coincidan sería peor
 *  que no calcularlo aquí: la cifra que se enseña antes de guardar cambiaría sola al
 *  recargar la pantalla con la que devolvió el servidor. */
const dosDecimales = (n: number) => Math.round(n * 100) / 100;

export function macrosPara(porCien: PorCien, cantidad: number): Macros {
  const factor = cantidad / 100;
  return {
    calories: dosDecimales(porCien.calories_per_100g * factor),
    protein: dosDecimales(porCien.protein_per_100g * factor),
    carbs: dosDecimales(porCien.carbs_per_100g * factor),
    fat: dosDecimales(porCien.fat_per_100g * factor),
    fiber: dosDecimales(porCien.fiber_per_100g * factor),
    sugar: dosDecimales(porCien.sugar_per_100g * factor),
  };
}

/** Español, con coma decimal y sin ceros de relleno. */
const CIFRA = new Intl.NumberFormat("es-ES", { maximumFractionDigits: 2 });

/** `objetivo` a null es «este usuario no ha configurado nada», que no es lo mismo que un
 *  objetivo de cero. Inventarle un número sería darle un dato de salud falso. */
export function textoRestante(consumidas: number, objetivo: number | null): string {
  if (objetivo == null) return "todavía sin objetivo";

  const diferencia = Math.round(objetivo - consumidas);
  if (diferencia === 0) return "has llegado justo a tu objetivo";
  if (diferencia > 0) return `te quedan ${CIFRA.format(diferencia)} kcal`;
  return `te has pasado en ${CIFRA.format(-diferencia)} kcal`;
}

/** «1,5 de 2 litros». En mililitros se lee fatal y en la barra no cabe. Pasarse no se
 *  recorta: el `pct` del servidor sí topa en 100, pero el litraje es lo que se bebió. */
export function textoAgua(totalMl: number, objetivoMl: number): string {
  return `${CIFRA.format(totalMl / 1000)} de ${CIFRA.format(objetivoMl / 1000)} litros`;
}
