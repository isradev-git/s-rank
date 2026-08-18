/* La sesión activa. Se usa de pie, con una mano, con la pantalla sudada y con prisa entre
   series, así que todo son objetivos grandes y no hay ni un gesto.

   La regla de este fichero: **cada cambio escribe en disco**. No hay rebote, no se espera
   a salir y no hay un «guardar». `localStorage.setItem` es síncrono, así que cuando la
   función vuelve el dato está a salvo. */

import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { recordsPersonales, ultimaSesion, type SerieAnterior } from "../api";
import { descansoPorDefecto, guardar, leer, type Serie, type Sesion as TipoSesion } from "../borrador";
import { Aviso, Boton, Comentario, FilaSerie, TituloPantalla } from "../componentes";
import { seriesHechas, textoRecord } from "../formato";

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

  /** Segundos que quedan de descanso, o null si no hay ninguno en marcha. */
  const [descanso, setDescanso] = useState<number | null>(null);

  // La cuenta atrás. Un único intervalo por descanso, creado una vez, y no un
  // `setTimeout` que se reprograma en cada render. Reprogramar depende de que React
  // confirme un render entre cada segundo, y con temporizadores simulados —los tests de
  // esta pantalla usan `vi.useFakeTimers`— varios segundos se avanzan de golpe sin que
  // React llegue a confirmarlos todos: el descanso se queda parado. Un intervalo no tiene
  // ese problema porque no depende de ningún render para seguir contando; y como
  // `setDescanso` usa la forma con función, no hay valor de descanso capturado y rancio
  // que pueda desincronizarse. Sin sonido ni vibración, que piden permisos y no funcionan
  // igual en cada navegador.
  // ponytail: si molesta no verlo, `navigator.vibrate(200)` en el cero es una línea.
  useEffect(() => {
    if (descanso === null) return;
    const id = setInterval(() => {
      setDescanso((s) => (s === null || s <= 1 ? null : s - 1));
    }, 1000);
    return () => clearInterval(id);
    // Solo arranca en la transición de «sin descanso» a «con descanso»: si `descanso`
    // fuera la dependencia, el intervalo se destruiría y se volvería a crear cada
    // segundo, que es justo el patrón que se acaba de descartar.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [descanso === null]);

  /** El mejor peso conocido por ejercicio, y lo que se levantó la última vez.
   *
   *  Las dos son comodidades. Si no hay red no se piden y no se avisa de nada: el caso
   *  normal de esta pantalla es un sótano sin cobertura, y un aviso de «no hemos podido
   *  cargar tus récords» cada vez sería ruido en la pantalla que menos lo admite. */
  const [records, setRecords] = useState<Map<string, number>>(new Map());
  const [anterior, setAnterior] = useState<SerieAnterior[]>([]);
  const [recordBatido, setRecordBatido] = useState<string | null>(null);

  useEffect(() => {
    recordsPersonales()
      .then((lista) => setRecords(new Map(lista.map((r) => [r.name, r.max_weight]))))
      .catch(() => undefined);
  }, []);

  const nombreActual = sesion?.exercises[sesion.actual]?.name;

  useEffect(() => {
    if (!nombreActual) return;
    setAnterior([]);
    ultimaSesion(nombreActual)
      .then(setAnterior)
      .catch(() => undefined);
  }, [nombreActual]);

  if (!sesion) return null;

  const ejercicio = sesion.exercises[sesion.actual];
  const hechas = seriesHechas(sesion.exercises);

  /** El único camino por el que cambia el estado. Escribe primero y pinta después: si el
   *  disco dice que no, el usuario se entera en la misma pulsación y no un repintado más
   *  tarde.
   *
   *  Se calcula fuera de `setSesion` y no dentro de una función actualizadora. React exige
   *  que esas funciones sean puras y en desarrollo las invoca dos veces para cazar
   *  justamente esto: escribir en disco y llamar a otro `setState` desde dentro. Aquí
   *  `sesion` no puede ser nula —arriba hay un `return null`— y cada pulsación es su
   *  propio evento, así que no hace falta la forma con función. */
  function actualizar(cambio: (anterior: TipoSesion) => TipoSesion) {
    const siguiente = cambio(sesion!);
    setSinSitio(!guardar(siguiente));
    setSesion(siguiente);
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
    const serie = ejercicio.sets[indice];

    // Desmarcar es corregir un error, no terminar una serie: ahí no toca descansar.
    if (!serie.hecha) {
      setDescanso(serie.rest_seconds ?? descansoPorDefecto());

      // El récord de verdad lo decide el servidor al guardar; esto es un aviso local para
      // que el momento se celebre cuando ocurre y no diez minutos después.
      const mejor = records.get(ejercicio.name);
      if (serie.weight_kg != null && mejor != null && serie.weight_kg > mejor) {
        setRecordBatido(
          textoRecord({
            exercise: ejercicio.name,
            kind: "weight",
            value: serie.weight_kg,
            previous: mejor,
          }),
        );
      }
    }

    actualizar((s) => ({
      ...s,
      exercises: s.exercises.map((ej, i) =>
        i !== s.actual
          ? ej
          : {
              ...ej,
              sets: ej.sets.map((serie, j) =>
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

      {descanso !== null && (
        <div className="descanso">
          <p aria-live="off">
            Descanso {Math.floor(descanso / 60)}:{String(descanso % 60).padStart(2, "0")}
          </p>
          <Boton type="button" compacto onClick={() => setDescanso(null)}>
            SALTAR DESCANSO
          </Boton>
        </div>
      )}

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
              anterior={anterior[indice] ?? null}
              alCambiar={(campo, valor) => cambiarSerie(indice, campo, valor)}
              alMarcar={() => marcarSerie(indice)}
            />
          ))}
        </tbody>
      </table>

      {recordBatido && <Aviso tono="ambar">Récord. {recordBatido}</Aviso>}

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
