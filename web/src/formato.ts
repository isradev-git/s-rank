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

/* El asistente nutricional, portado de FitLoop
   (old/resources/views/nutrition/dashboard.blade.php:1037-1049 y :1222-1247).

   ponytail: Mifflin-St Jeor queda escrita en dos sitios, aquí y en
   NutritionGoal::calculateRecommended. Se acepta para que el asistente recalcule al
   tocar, sin una petición por opción. El techo es que se separen; si pasa, el sitio
   donde unificarlas es GET /api/nutrition/goal aceptando activity y goal_type. */

export const ACTIVIDADES = [
  { clave: "sedentary", etiqueta: "Poco o nada", factor: 1.2 },
  { clave: "light", etiqueta: "Ejercicio ligero, de 1 a 3 días por semana", factor: 1.375 },
  { clave: "moderate", etiqueta: "Ejercicio moderado, de 3 a 5 días", factor: 1.55 },
  { clave: "active", etiqueta: "Ejercicio intenso, casi a diario", factor: 1.725 },
  { clave: "very_active", etiqueta: "Muy intenso, o deporte más trabajo físico", factor: 1.9 },
] as const;

export const OBJETIVOS = [
  { clave: "lose_weight", etiqueta: "Perder peso", ajuste: -500, ratios: [0.35, 0.4, 0.25] },
  { clave: "maintain", etiqueta: "Mantener el peso", ajuste: 0, ratios: [0.3, 0.45, 0.25] },
  { clave: "gain_muscle", etiqueta: "Ganar músculo", ajuste: 300, ratios: [0.3, 0.5, 0.2] },
] as const;

export type ClaveActividad = (typeof ACTIVIDADES)[number]["clave"];
export type ClaveObjetivo = (typeof OBJETIVOS)[number]["clave"];

export type DatosCuerpo = {
  weight: number;
  height: number;
  age: number;
  gender: "male" | "female";
};

/** Lo que acepta `PUT /api/nutrition/goal`. */
export type ObjetivoNutricional = {
  daily_calories: number;
  target_protein: number;
  target_carbs: number;
  target_fat: number;
  target_fiber: number;
  goal_type: ClaveObjetivo;
};

/** Por debajo de esto no se recomienda una dieta sin que la vigile alguien. FitLoop ya
 *  tenía este suelo y se conserva: el cálculo de una persona menuda y sedentaria con
 *  déficit se va por debajo sin ningún aviso. */
const MINIMO_KCAL = 1200;

export function calcularObjetivo(
  cuerpo: DatosCuerpo,
  actividad: ClaveActividad,
  objetivo: ClaveObjetivo,
): ObjetivoNutricional {
  const factor = ACTIVIDADES.find((a) => a.clave === actividad)!.factor;
  const meta = OBJETIVOS.find((o) => o.clave === objetivo)!;

  // Mifflin-St Jeor. La constante de sexo es +5 en hombres y −161 en mujeres.
  const constante = cuerpo.gender === "female" ? -161 : 5;
  const bmr = 10 * cuerpo.weight + 6.25 * cuerpo.height - 5 * cuerpo.age + constante;
  const tdee = Math.round(bmr * factor);
  const calorias = Math.max(MINIMO_KCAL, tdee + meta.ajuste);

  const [proteina, hidratos, grasa] = meta.ratios;
  return {
    daily_calories: calorias,
    // 4 kcal por gramo de proteína y de hidratos, 9 por gramo de grasa.
    target_protein: Math.round((calorias * proteina) / 4),
    target_carbs: Math.round((calorias * hidratos) / 4),
    target_fat: Math.round((calorias * grasa) / 9),
    target_fiber: 25,
    goal_type: objetivo,
  };
}
