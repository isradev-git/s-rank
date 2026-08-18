/* La sesión activa. Se usa de pie, con una mano, con la pantalla sudada y con prisa entre
   series, así que todo son objetivos grandes y no hay ni un gesto.

   La regla de este fichero: **cada cambio escribe en disco**. No hay rebote, no se espera
   a salir y no hay un «guardar». `localStorage.setItem` es síncrono, así que cuando la
   función vuelve el dato está a salvo. */

import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { descansoPorDefecto, guardar, leer, type Serie, type Sesion as TipoSesion } from "../borrador";
import { Aviso, Boton, Comentario, FilaSerie, TituloPantalla } from "../componentes";
import { seriesHechas } from "../formato";

/** Las columnas de cada disposición. La de fuerza vale para gimnasio, casa y calistenia;
    en calistenia el peso se titula «Lastre», que es lo que de verdad se apunta. */
function cabeceras(modo: TipoSesion["mode"]): string[] {
  if (modo === "swimming") return ["Serie", "Distancia", "Tiempo", "Estilo", "Hecha"];
  return ["Serie", "Anterior", modo === "calisthenics" ? "Lastre" : "Kg", "Reps", "RPE", "Hecha"];
}

function serieNueva(descanso: number | null): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: descanso, hecha: false,
  };
}

export default function Sesion() {
  const navegar = useNavigate();
  const [sesion, setSesion] = useState<TipoSesion | null>(() => leer());
  const [sinSitio, setSinSitio] = useState(false);

  // Sin borrador no hay nada que enseñar: alguien ha llegado aquí por la URL.
  useEffect(() => {
    if (!sesion) navegar("/entrenar", { replace: true });
  }, [sesion, navegar]);

  if (!sesion) return null;

  /** El único camino por el que cambia el estado. Escribe primero y pinta después: si el
   *  disco dice que no, el usuario se entera en la misma pulsación. */
  function actualizar(cambio: (anterior: TipoSesion) => TipoSesion) {
    setSesion((anterior) => {
      const siguiente = cambio(anterior!);
      setSinSitio(!guardar(siguiente));
      return siguiente;
    });
  }

  function cambiarSerie(indice: number, campo: keyof Serie, valor: number | string | null) {
    actualizar((s) => ({
      ...s,
      exercises: s.exercises.map((ejercicio, i) =>
        i !== s.actual
          ? ejercicio
          : {
              ...ejercicio,
              sets: ejercicio.sets.map((serie, j) =>
                j === indice ? { ...serie, [campo]: valor } : serie,
              ),
            },
      ),
    }));
  }

  function marcarSerie(indice: number) {
    actualizar((s) => ({
      ...s,
      exercises: s.exercises.map((ejercicio, i) =>
        i !== s.actual
          ? ejercicio
          : {
              ...ejercicio,
              sets: ejercicio.sets.map((serie, j) =>
                j === indice ? { ...serie, hecha: !serie.hecha } : serie,
              ),
            },
      ),
    }));
  }

  function anadirSerie() {
    actualizar((s) => ({
      ...s,
      exercises: s.exercises.map((ejercicio, i) => {
        if (i !== s.actual) return ejercicio;
        // El descanso se hereda de la última serie: quien lo cambió una vez lo quiere
        // igual en la siguiente, y volver a teclearlo entre series es justo lo que esta
        // pantalla no puede pedir.
        const ultima = ejercicio.sets.at(-1);
        return { ...ejercicio, sets: [...ejercicio.sets, serieNueva(ultima?.rest_seconds ?? descansoPorDefecto())] };
      }),
    }));
  }

  const ejercicio = sesion.exercises[sesion.actual];
  const hechas = seriesHechas(sesion.exercises);

  return (
    <>
      <TituloPantalla pantalla="entreno" />

      {sinSitio && (
        <Aviso tono="rojo">
          Este navegador no está guardando el entreno: no cierres la aplicación hasta
          terminarlo y subirlo.
        </Aviso>
      )}

      <h2 className="nombre-ejercicio">{ejercicio.name}</h2>
      <Comentario>
        ejercicio {sesion.actual + 1} de {sesion.exercises.length}
        {ejercicio.objetivo?.sets ? ` · objetivo ${ejercicio.objetivo.sets} series` : ""}
        {ejercicio.objetivo?.reps ? ` de ${ejercicio.objetivo.reps}` : ""}
      </Comentario>

      <table className="tabla-series">
        <thead>
          <tr>
            {cabeceras(sesion.mode).map((titulo) => (
              <th key={titulo}>{titulo}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {ejercicio.sets.map((serie, indice) => (
            <FilaSerie
              key={indice}
              numero={indice + 1}
              serie={serie}
              modo={sesion.mode}
              anterior={null}
              alCambiar={(campo, valor) => cambiarSerie(indice, campo, valor)}
              alMarcar={() => marcarSerie(indice)}
            />
          ))}
        </tbody>
      </table>

      {/* El avance con palabras y no solo con la barra: quien use lector de pantalla
          necesita oírlo, y quien no, agradece la cifra. */}
      <Comentario>{hechas === 1 ? "1 serie hecha" : `${hechas} series hechas`}</Comentario>

      <div className="acciones">
        <Boton type="button" compacto onClick={anadirSerie}>
          AÑADIR SERIE
        </Boton>
      </div>
    </>
  );
}
