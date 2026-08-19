/* De dónde sale el entreno de hoy: una plantilla, el último que se hizo, o nada.
   Lo único que hace esta pantalla es dejar una `Sesion` guardada y pasar a la siguiente. */

import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { diaDeHoy, entrenos, plantillas, type EntrenoSugerido, type Plantilla } from "../api";
import {
  ahoraUtc,
  descansoPorDefecto,
  guardar,
  type Ejercicio,
  type Modo,
  type Serie,
  type Sesion,
} from "../borrador";
import { Aviso, Boton, Comentario, Seccion, TituloPantalla } from "../componentes";

const NOMBRE_MODO: Record<Modo, string> = {
  gym: "GIMNASIO",
  home: "CASA",
  calisthenics: "CALISTENIA",
  swimming: "NATACIÓN",
};

function serieSemilla(): Serie {
  return {
    weight_kg: null, reps: null, rpe: null,
    distance_m: null, time_seconds: null, style: null,
    rest_seconds: descansoPorDefecto(), hecha: false,
  };
}

export default function Elegir() {
  const navegar = useNavigate();
  const [sugerido, setSugerido] = useState<EntrenoSugerido | null>(null);
  const [lista, setLista] = useState<Plantilla[]>([]);
  const [modo, setModo] = useState<Modo>("gym");
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    // Las dos son comodidades: sin ellas se puede entrenar igual empezando en blanco, así
    // que un fallo aquí no bloquea la pantalla.
    diaDeHoy().then((dia) => setSugerido(dia.suggested_workout)).catch(() => undefined);
    plantillas().then(setLista).catch(() => undefined);
  }, []);

  function empezar(sesion: Sesion) {
    // `guardar` devuelve si pudo escribir y aquí no se puede tragar. Sin la sesión en
    // disco, la pantalla siguiente no tiene nada que pintar y se queda en blanco: el
    // usuario creería que la aplicación se ha roto sin saber que lo que pasa es que no
    // cabe. Se avisa y no se pasa de aquí.
    if (!guardar(sesion)) {
      setFallo(
        "No hemos podido empezar el entreno porque no cabe en el móvil. " +
          "Cierra alguna aplicación o libera espacio y vuelve a intentarlo.",
      );
      return;
    }
    navegar("/entrenar/sesion");
  }

  function desdePlantilla(plantilla: Plantilla) {
    empezar({
      v: 1,
      mode: plantilla.mode ?? "gym",
      nombre: plantilla.name,
      inicio: ahoraUtc(),
      actual: 0,
      // Una serie semilla por ejercicio, no las cuatro que pide la plantilla: las series
      // se añaden según se hacen, que es como se entrena de verdad. El objetivo se guarda
      // aparte como guía.
      exercises: plantilla.exercises.map<Ejercicio>((ejercicio) => ({
        name: ejercicio.name,
        objetivo: { sets: ejercicio.sets, reps: ejercicio.reps },
        sets: [serieSemilla()],
      })),
    });
  }

  function enBlanco() {
    empezar({
      v: 1, mode: modo, nombre: "Entreno libre",
      inicio: ahoraUtc(), actual: 0, exercises: [],
    });
  }

  async function repetirElUltimo() {
    setFallo(null);
    let ultimos;
    try {
      ultimos = await entrenos({ mode: modo, per_page: 1 });
    } catch {
      setFallo("No hemos podido cargar tu último entreno. Puedes empezar en blanco.");
      return;
    }

    const ultimo = ultimos[0];
    if (!ultimo || ultimo.sets.length === 0) {
      setFallo("No hay un entreno anterior de este tipo. Empieza desde una plantilla o en blanco.");
      return;
    }

    // Las series vienen en una sola lista, una fila por serie. Se agrupan por nombre
    // respetando el orden en que aparecen, que es el orden en que se hicieron.
    const porNombre = new Map<string, Ejercicio>();
    for (const serie of ultimo.sets) {
      if (!porNombre.has(serie.name)) {
        porNombre.set(serie.name, { name: serie.name, objetivo: null, sets: [] });
      }
      porNombre.get(serie.name)!.sets.push({
        weight_kg: serie.weight_kg,
        reps: serie.reps,
        rpe: null,
        distance_m: serie.distance_m,
        time_seconds: serie.time_seconds,
        style: serie.style,
        rest_seconds: serie.rest_seconds,
        // Sin marcar: es el plan de hoy, no el entreno de hoy.
        hecha: false,
      });
    }

    empezar({
      v: 1, mode: ultimo.mode, nombre: "Repetir el último",
      inicio: ahoraUtc(), actual: 0, exercises: [...porNombre.values()],
    });
  }

  // La sugerida se cae de la lista: ya tiene su propio botón arriba, y dos botones con el
  // mismo nombre son dos botones idénticos para quien usa un lector de pantalla.
  const delModo = lista.filter((p) => p.mode === modo && p.id !== sugerido?.template?.id);

  return (
    <>
      <TituloPantalla pantalla="entrenar" />
      {sugerido && <Comentario>{sugerido.reason}</Comentario>}
      {fallo && <Aviso tono="rojo">{fallo}</Aviso>}

      <div className="modos" role="group" aria-label="Tipo de entreno">
        {(Object.keys(NOMBRE_MODO) as Modo[]).map((m) => (
          <Boton key={m} type="button" compacto aria-pressed={modo === m} onClick={() => setModo(m)}>
            {NOMBRE_MODO[m]}
          </Boton>
        ))}
      </div>

      {sugerido?.template && (
        <Boton type="button" onClick={() => desdePlantilla(sugerido.template!)}>
          EMPEZAR {sugerido.template.name.toUpperCase()}
        </Boton>
      )}

      <Boton type="button" onClick={() => void repetirElUltimo()}>
        REPETIR EL ÚLTIMO
      </Boton>
      <Boton type="button" onClick={enBlanco}>
        EMPEZAR EN BLANCO
      </Boton>

      <Seccion titulo="Plantillas" resumen={`${delModo.length}`}>
        <ul className="lista-plantillas">
          {delModo.map((plantilla) => (
            <li key={plantilla.id}>
              <Boton type="button" compacto onClick={() => desdePlantilla(plantilla)}>
                EMPEZAR {plantilla.name.toUpperCase()}
              </Boton>
              <Comentario>
                {plantilla.exercises.length} ejercicios
                {plantilla.level ? ` · ${plantilla.level}` : ""}
              </Comentario>
            </li>
          ))}
        </ul>
      </Seccion>
    </>
  );
}
