/* ponytail: todo lo de aquí es inventado, copiado del ejemplo del spec §6. Está junto en
   un solo fichero para que el paso 2 lo borre de una vez y no queden restos sueltos por
   las pantallas. Las formas ya son las que devuelve el servidor. */

import type { Mision } from "./componentes";

export const SESION = { usuario: "israel", rango: "E" };

export const PROGRESO = { nivel: 4, xp: 240, xpNivel: 400, racha: 12 };

export const MISIONES: Mision[] = [
  { key: "water", label: "Beber 2 litros de agua", target: 2000, progress: 2000, completed: true },
  { key: "train", label: "Entrenar", target: 3, progress: 1, completed: false },
  { key: "weight", label: "Apuntar el peso", target: null, progress: null, completed: false },
  { key: "steps_8000", label: "8.000 pasos", target: 8000, progress: 5240, completed: false },
];
