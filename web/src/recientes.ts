/* «Lo de siempre»: los últimos alimentos registrados, para que repetir el desayuno sean
   dos toques y no cinco. Es la acción más repetida de la aplicación —182 comidas frente a
   3 entrenos en los datos reales— y ni el backend ni FitLoop tenían nada parecido.

   Junto a borrador.ts, es el único sitio del proyecto que menciona localStorage.

   ponytail: la lista vive solo en este móvil. El techo es que en un dispositivo nuevo
   sale vacía y se rellena sola en unos días, y que no se comparte entre dispositivos. Si
   algún día hace falta que sincronice, el sitio es GET /api/foods/recent en el backend.

   Perder esta lista no pierde ningún dato: las comidas están en el servidor. Por eso aquí
   los fallos se tragan en silencio, al revés que en borrador.ts, donde un `false` sin
   mirar significaba perder un entreno. */

export type TipoComida = "breakfast" | "lunch" | "dinner" | "snack";

export type Reciente = {
  /** `food_items.id`, que es un entero. Los alimentos a mano no entran aquí: sin id no
   *  se pueden volver a registrar de un toque. */
  id: number;
  nombre: string;
  gramos: number;
  tipo: TipoComida;
  /** Las calorías por 100 g del alimento. Se guardan aquí para que el chip pueda decir
   *  cuántas calorías son sin pedir nada: si no, abrir la pantalla serían cuatro
   *  peticiones al catálogo solo para pintar cuatro números. */
  kcal100: number;
};

/** Diez llenan la pantalla sin obligar a desplazar. Más es una lista que hay que leer, y
 *  leer cuesta más que buscar. */
export const MAXIMO = 10;

const VERSION = 1;
const CLAVE = "srank.comidas-recientes";

type Guardado = { v: number; lista: Reciente[] };

function leerTodo(): Reciente[] {
  try {
    const texto = localStorage.getItem(CLAVE);
    if (!texto) return [];
    const dato = JSON.parse(texto) as Guardado | null;
    if (!dato || dato.v !== VERSION || !Array.isArray(dato.lista)) return [];
    return dato.lista;
  } catch {
    // JSON roto, versión anterior, o localStorage que no deja leer en modo privado.
    return [];
  }
}

/** Los de una comida concreta, del más reciente al más antiguo.
 *
 *  El corte por MAXIMO va aquí y no en `apuntar`: lo guardado es común a las cuatro
 *  comidas, así que cortarlo antes dejaría a la cena sin recientes en cuanto el desayuno
 *  llenara la lista. */
export function recientes(tipo: TipoComida): Reciente[] {
  return leerTodo()
    .filter((r) => r.tipo === tipo)
    .slice(0, MAXIMO);
}

/** Sube el alimento al principio con la cantidad de esta vez. Un mismo alimento en dos
 *  comidas distintas son dos entradas: el café de la mañana y el de media tarde no llevan
 *  la misma cantidad. */
export function apuntar(reciente: Reciente): void {
  const resto = leerTodo().filter(
    (r) => !(r.id === reciente.id && r.tipo === reciente.tipo),
  );
  // MAXIMO × 4: cabe el máximo de cada una de las cuatro comidas en el peor caso.
  const lista = [reciente, ...resto].slice(0, MAXIMO * 4);

  try {
    localStorage.setItem(CLAVE, JSON.stringify({ v: VERSION, lista } satisfies Guardado));
  } catch {
    // Cuota llena o modo privado. Se pierde el atajo, no la comida.
  }
}
