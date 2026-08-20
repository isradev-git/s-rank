/* Las cuatro secciones de hábitos que viven dentro de «hoy». Van juntas en un fichero
   porque son pequeñas y todas hacen lo mismo: leer su día, tocar, reconciliar.

   El spec §8 las listaba como sub-pantallas. Aquí son secciones: beber un vaso pasa de
   tres toques —entrar, pulsar, volver— a uno, y eso en algo que se hace ocho veces al día
   se nota más que cualquier otra decisión de esta fase. */

import { useCallback, useEffect, useState } from "react";
import { ErrorApi, agua, anadirAgua, type BloqueSistema, type DiaDeAgua } from "../api";
import { BarraBloques, Boton, Comentario, Seccion } from "../componentes";
import { textoAgua } from "../formato";

/** Un vaso. FitLoop ya usaba 250 ml y es lo que cabe en un vaso normal. */
const VASO_ML = 250;
const MEDIO_LITRO_ML = 500;

export function SeccionAgua({
  fecha,
  alGanar,
}: {
  fecha: string;
  /** Sube el bloque `system` a «hoy», que es quien abre la ventana del Sistema. Las
   *  secciones no la abren: si cada una tuviera la suya, podrían salir dos a la vez. */
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [dia, setDia] = useState<DiaDeAgua | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  const cargar = useCallback(() => {
    agua(fecha).then(setDia).catch(() => setFallo("No hemos podido cargar el agua de hoy."));
  }, [fecha]);

  useEffect(() => cargar(), [cargar]);

  async function beber(ml: number) {
    if (!dia) return;
    const antes = dia;
    // Optimista: la barra sube ya y la petición va detrás. Ocho vasos al día por dos o
    // tres décimas de botón muerto cada uno se notan.
    setDia({ ...dia, total_ml: dia.total_ml + ml });
    setFallo(null);

    try {
      const respuesta = await anadirAgua(fecha, ml);
      // El total del servidor gana al optimista: otro dispositivo pudo apuntar entre medias.
      setDia({ ...antes, total_ml: respuesta.total_ml, goal_ml: respuesta.goal_ml, pct: respuesta.pct });
      alGanar(respuesta.system);
    } catch (error) {
      setDia(antes);
      setFallo(
        error instanceof ErrorApi && error.fallo.general?.includes("conexión")
          ? "No se ha podido guardar el vaso. Comprueba la conexión."
          : "No se ha podido guardar el vaso. Inténtalo otra vez.",
      );
    }
  }

  if (!dia) return <Seccion titulo="Agua" resumen="cargando"><Comentario>cargando…</Comentario></Seccion>;

  return (
    <Seccion titulo="Agua" resumen={textoAgua(dia.total_ml, dia.goal_ml)}>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      {/* El litraje va solo en el resumen de la sección: se ve igual con ella plegada, y
          repetirlo debajo de la barra era decir dos veces lo mismo. */}
      <BarraBloques porcentaje={(dia.total_ml / dia.goal_ml) * 100} etiqueta="Agua bebida hoy" />

      <div className="acciones">
        <Boton type="button" compacto onClick={() => void beber(VASO_ML)}>+1 VASO</Boton>
        <Boton type="button" compacto onClick={() => void beber(MEDIO_LITRO_ML)}>+MEDIO LITRO</Boton>
      </div>
    </Seccion>
  );
}
