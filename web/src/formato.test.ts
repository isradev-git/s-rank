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

import {
  duracionMinutos,
  seriesHechas,
  textoAntiguedad,
  textoRecord,
  textoXpGanado,
  volumenTotal,
} from "./formato";
import type { Ejercicio, Serie } from "./borrador";

function serie(campos: Partial<Serie> = {}): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: null, hecha: false,
    ...campos,
  };
}

const EJERCICIOS: Ejercicio[] = [
  {
    name: "Press banca",
    objetivo: null,
    sets: [
      serie({ weight_kg: 80, reps: 5, hecha: true }),
      serie({ weight_kg: 80, reps: 5, hecha: true }),
      serie({ weight_kg: 80, reps: 3 }),
    ],
  },
  {
    name: "Dominadas",
    objetivo: null,
    // Sin peso: no suma volumen, pero la serie está hecha igual.
    sets: [serie({ reps: 10, hecha: true })],
  },
];

test("el volumen es peso por repeticiones, sumado", () => {
  // El spec §6.4: Fuerza sube con los kilos movidos, peso × reps × series. Por eso
  // registrar el peso de cada serie importa: sin él la estadística no se mueve.
  expect(volumenTotal(EJERCICIOS)).toBe(80 * 5 + 80 * 5 + 80 * 3);
});

test("una serie a medio rellenar no suma volumen a medias", () => {
  // Ni 80 kg por «ninguna» repetición, ni repeticiones por «ningún» peso.
  expect(volumenTotal([{ name: "X", objetivo: null, sets: [serie({ weight_kg: 80 })] }])).toBe(0);
  expect(volumenTotal([{ name: "X", objetivo: null, sets: [serie({ reps: 5 })] }])).toBe(0);
});

test("sin ejercicios el volumen es cero y no NaN", () => {
  expect(volumenTotal([])).toBe(0);
});

test("las series hechas se cuentan aunque no lleven peso", () => {
  expect(seriesHechas(EJERCICIOS)).toBe(3);
});

test("la duración se redondea a minutos enteros", () => {
  const inicio = "2026-08-18T17:00:00Z";
  expect(duracionMinutos(inicio, Date.parse("2026-08-18T17:45:00Z"))).toBe(45);
  expect(duracionMinutos(inicio, Date.parse("2026-08-18T17:45:40Z"))).toBe(46);
});

test("un reloj que va hacia atrás no da una duración negativa", () => {
  // La API rechaza duration_minutes < 0, y un móvil que ajusta la hora por NTP a mitad de
  // sesión puede dejar el reloj detrás del inicio.
  expect(duracionMinutos("2026-08-18T17:00:00Z", Date.parse("2026-08-18T16:00:00Z"))).toBe(0);
});

test("la duración se corta en el máximo que acepta la API", () => {
  // duration_minutes va de 0 a 600. Un borrador de hace tres días daría 4.320 y el
  // servidor lo rechazaría con un 422 justo cuando el usuario intenta salvarlo.
  expect(duracionMinutos("2026-08-15T17:00:00Z", Date.parse("2026-08-18T17:00:00Z"))).toBe(600);
});

test("la antigüedad del borrador se dice en español llano", () => {
  const inicio = "2026-08-18T17:00:00Z";
  const en = (cuando: string) => textoAntiguedad(inicio, Date.parse(cuando));

  expect(en("2026-08-18T17:00:30Z")).toBe("de hace un momento");
  expect(en("2026-08-18T17:01:00Z")).toBe("de hace 1 minuto");
  expect(en("2026-08-18T17:12:00Z")).toBe("de hace 12 minutos");
  expect(en("2026-08-18T18:00:00Z")).toBe("de hace 1 hora");
  expect(en("2026-08-18T20:00:00Z")).toBe("de hace 3 horas");
  expect(en("2026-08-19T18:00:00Z")).toBe("de ayer");
  expect(en("2026-08-21T18:00:00Z")).toBe("de hace 3 días");
});

test("el XP ganado se dice tal cual cuando lo hay", () => {
  expect(textoXpGanado(80, 45)).toBe("+80 XP");
});

test("un entreno corto explica por qué no puntúa, sin parecer un error", () => {
  // El spec §6.2: 50 XP a partir de 15 minutos. Por debajo no da XP de entrenamiento.
  expect(textoXpGanado(0, 9)).toBe("Guardado. Un entreno suma XP a partir de los 15 minutos.");
});

test("el tercer entreno del día se explica sin decir por qué exactamente", () => {
  // El servidor puede haber devuelto 0 por el tope de dos entrenos con XP o por el de 300
  // XP diarios (spec §6.3), y desde aquí no se distinguen. Decir «ya has llegado al
  // máximo» es cierto en los dos casos; elegir uno sería adivinar.
  expect(textoXpGanado(0, 45)).toBe(
    "Guardado. Hoy ya has llegado al máximo de XP, así que este entreno no suma.",
  );
});

test("un récord se anuncia con su marca anterior, o dice que es la primera", () => {
  // La forma es la de `system.records`, que es la que llega en toda respuesta con bloque
  // `system` y la única que lee la interfaz.
  expect(textoRecord({ exercise: "Press banca", kind: "weight", value: 85, previous: 80 }))
    .toBe("Press banca: 85 kg, antes 80 kg.");
  expect(textoRecord({ exercise: "Peso muerto", kind: "weight", value: 100, previous: null }))
    .toBe("Peso muerto: 100 kg, tu primera marca.");
});

import { macrosPara, textoAgua, textoRestante } from "./formato";

const POLLO = {
  calories_per_100g: 165,
  protein_per_100g: 31,
  carbs_per_100g: 0,
  fat_per_100g: 3.6,
  fiber_per_100g: 0,
  sugar_per_100g: 0,
};

test("los macros se calculan por regla de tres sobre 100 g", () => {
  expect(macrosPara(POLLO, 150)).toEqual({
    calories: 247.5,
    protein: 46.5,
    carbs: 0,
    fat: 5.4,
    fiber: 0,
    sugar: 0,
  });
});

test("los macros se redondean a dos decimales, como el servidor", () => {
  // 3,6 × 0,33 = 1,188. El servidor hace round(..., 2) y devuelve 1.19: si aquí
  // saliera 1.188, la cifra cambiaría sola al recargar la pantalla.
  expect(macrosPara(POLLO, 33).fat).toBe(1.19);
});

test("una cantidad de cero da todo a cero y no NaN", () => {
  expect(macrosPara(POLLO, 0).calories).toBe(0);
});

test("el texto de lo que queda distingue quedarse corto de pasarse", () => {
  expect(textoRestante(1360, 2000)).toBe("te quedan 640 kcal");
  expect(textoRestante(2120, 2000)).toBe("te has pasado en 120 kcal");
  expect(textoRestante(2000, 2000)).toBe("has llegado justo a tu objetivo");
});

test("sin objetivo nutricional el texto lo dice en vez de inventarse un número", () => {
  expect(textoRestante(1360, null)).toBe("todavía sin objetivo");
});

test("el agua se cuenta en litros con coma, que es como se dice en español", () => {
  expect(textoAgua(1500, 2000)).toBe("1,5 de 2 litros");
  expect(textoAgua(0, 2000)).toBe("0 de 2 litros");
  // Pasarse está bien y no se recorta: bebiste lo que bebiste.
  expect(textoAgua(2250, 2000)).toBe("2,25 de 2 litros");
});

import { calcularObjetivo } from "./formato";

// Hombre de 80 kg, 180 cm, 30 años.
// BMR = 10×80 + 6,25×180 − 5×30 + 5 = 800 + 1125 − 150 + 5 = 1780
const HOMBRE = { weight: 80, height: 180, age: 30, gender: "male" as const };
// Mujer de 65 kg, 165 cm, 30 años.
// BMR = 650 + 1031,25 − 150 − 161 = 1370,25
const MUJER = { weight: 65, height: 165, age: 30, gender: "female" as const };

test("la constante de sexo cambia el resultado", () => {
  // 1780 × 1,55 = 2759 → mantener no ajusta nada.
  expect(calcularObjetivo(HOMBRE, "moderate", "maintain").daily_calories).toBe(2759);
  // 1370,25 × 1,55 = 2123,8875 → 2124.
  expect(calcularObjetivo(MUJER, "moderate", "maintain").daily_calories).toBe(2124);
});

test("perder resta 500 y ganar suma 300", () => {
  expect(calcularObjetivo(HOMBRE, "moderate", "lose_weight").daily_calories).toBe(2259);
  expect(calcularObjetivo(HOMBRE, "moderate", "gain_muscle").daily_calories).toBe(3059);
});

test("nunca baja de 1200 kcal", () => {
  // Persona pequeña y sedentaria con déficit: el cálculo se iría por debajo de lo que
  // ninguna dieta debería recomendar sin supervisión.
  const menuda = { weight: 45, height: 150, age: 60, gender: "female" as const };
  expect(calcularObjetivo(menuda, "sedentary", "lose_weight").daily_calories).toBe(1200);
});

test("los macros salen de los ratios del objetivo y cuadran con las calorías", () => {
  const objetivo = calcularObjetivo(HOMBRE, "moderate", "maintain");
  // maintain reparte 30 % proteína, 45 % hidratos, 25 % grasa.
  expect(objetivo.target_protein).toBe(Math.round((2759 * 0.3) / 4));
  expect(objetivo.target_carbs).toBe(Math.round((2759 * 0.45) / 4));
  expect(objetivo.target_fat).toBe(Math.round((2759 * 0.25) / 9));
  expect(objetivo.goal_type).toBe("maintain");
});

test("el factor de actividad se aplica", () => {
  // 1780 × 1,2 = 2136.
  expect(calcularObjetivo(HOMBRE, "sedentary", "maintain").daily_calories).toBe(2136);
  // 1780 × 1,9 = 3382.
  expect(calcularObjetivo(HOMBRE, "very_active", "maintain").daily_calories).toBe(3382);
});
