/* Las plantillas: las del sistema y las del usuario, en la misma lista.

   La diferencia es `user_id`. Las del sistema (`null`) se ven y se usan, pero el servidor
   contesta 403 a quien intente editarlas o borrarlas, así que aquí no llevan esos botones:
   ofrecer algo que va a fallar es peor que no ofrecerlo. */

import { useEffect, useState } from "react";
import {
  ErrorApi,
  borrarPlantilla,
  crearPlantilla,
  editarPlantilla,
  plantillas,
  type Plantilla,
  type PlantillaEditable,
} from "../api";
import type { Modo } from "../borrador";
import { Aviso, Boton, Campo, Comentario, TituloPantalla } from "../componentes";

/** Lo que hay en el formulario. Series y repeticiones viven aquí como texto porque un
 *  campo vacío no es un cero: se convierten a número al mandar. */
type Borrador = {
  id: string | null;
  name: string;
  mode: Modo;
  exercises: { name: string; sets: string; reps: string }[];
};

const VACIA: Borrador = { id: null, name: "", mode: "gym", exercises: [{ name: "", sets: "", reps: "" }] };

const NOMBRE_MODO: Record<Modo, string> = {
  gym: "GIMNASIO",
  home: "CASA",
  calisthenics: "CALISTENIA",
  swimming: "NATACIÓN",
};

export default function Plantillas() {
  const [lista, setLista] = useState<Plantilla[]>([]);
  const [editando, setEditando] = useState<Borrador | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  // Tras crear, editar o borrar se vuelve a pedir la lista. Es una llamada y evita
  // mantener dos copias del mismo estado, que es de donde salen los desajustes.
  const recargar = () => plantillas().then(setLista).catch(() => undefined);
  useEffect(() => {
    void recargar();
  }, []);

  /** Nunca un número de error: la API ya manda el texto en castellano. */
  function textoDe(error: unknown): string {
    return error instanceof ErrorApi && error.fallo.general
      ? error.fallo.general
      : "No hemos podido conectar. Inténtalo otra vez.";
  }

  async function guardar() {
    if (!editando) return;
    const exercises = editando.exercises
      .filter((e) => e.name.trim() !== "")
      .map((e) => ({ name: e.name.trim(), sets: Number(e.sets) || null, reps: Number(e.reps) || null }));

    // La API valida `exercises` como required|array|min:1. Comprobarlo aquí ahorra un
    // viaje y un mensaje que llegaría de vuelta sin decir mucho más.
    if (exercises.length === 0) {
      setFallo("Una plantilla necesita al menos un ejercicio.");
      return;
    }

    // Solo lo que la API acepta escribir. El PUT descarta peso, tiempo y distancia, así
    // que ni se piden: el usuario vería desaparecer lo que acabara de escribir.
    const datos: PlantillaEditable = { name: editando.name.trim(), mode: editando.mode, exercises };
    try {
      await (editando.id ? editarPlantilla(editando.id, datos) : crearPlantilla(datos));
      setEditando(null);
      setFallo(null);
      await recargar();
    } catch (error) {
      setFallo(textoDe(error));
    }
  }

  async function borrar(plantilla: Plantilla) {
    if (!confirm(`¿Borrar «${plantilla.name}»?`)) return;
    try {
      await borrarPlantilla(plantilla.id);
      setFallo(null);
      await recargar();
    } catch (error) {
      setFallo(textoDe(error));
    }
  }

  function editar(plantilla: Plantilla) {
    setFallo(null);
    setEditando({
      id: plantilla.id,
      name: plantilla.name,
      mode: plantilla.mode ?? "gym",
      exercises: plantilla.exercises.map((e) => ({
        name: e.name,
        sets: e.sets == null ? "" : String(e.sets),
        reps: e.reps == null ? "" : String(e.reps),
      })),
    });
  }

  /** Cambia un ejercicio del borrador sin tocar los demás. */
  function cambiarEjercicio(indice: number, campo: "name" | "sets" | "reps", valor: string) {
    setEditando((b) =>
      !b ? b : { ...b, exercises: b.exercises.map((e, i) => (i === indice ? { ...e, [campo]: valor } : e)) },
    );
  }

  if (editando) {
    return (
      <>
        <TituloPantalla pantalla="plantilla" />
        <Comentario>{editando.id ? "Editar plantilla" : "Nueva plantilla"}</Comentario>

        {fallo && <Aviso tono="rojo">{fallo}</Aviso>}

        <Campo
          etiqueta="Nombre"
          name="nombre"
          type="text"
          value={editando.name}
          onChange={(e) => setEditando({ ...editando, name: e.target.value })}
        />

        <div className="modos" role="group" aria-label="Tipo de entreno">
          {(Object.keys(NOMBRE_MODO) as Modo[]).map((m) => (
            <Boton
              key={m}
              type="button"
              compacto
              aria-pressed={editando.mode === m}
              onClick={() => setEditando({ ...editando, mode: m })}
            >
              {NOMBRE_MODO[m]}
            </Boton>
          ))}
        </div>

        <ul className="lista-ejercicios-plantilla">
          {editando.exercises.map((ejercicio, indice) => (
            <li key={indice}>
              <Campo
                etiqueta={`Ejercicio ${indice + 1}`}
                name={`ejercicio-${indice}`}
                type="text"
                value={ejercicio.name}
                onChange={(e) => cambiarEjercicio(indice, "name", e.target.value)}
              />
              <Campo
                etiqueta={`Series del ejercicio ${indice + 1}`}
                name={`series-${indice}`}
                type="number"
                min="0"
                inputMode="numeric"
                value={ejercicio.sets}
                onChange={(e) => cambiarEjercicio(indice, "sets", e.target.value)}
              />
              <Campo
                etiqueta={`Repeticiones del ejercicio ${indice + 1}`}
                name={`reps-${indice}`}
                type="number"
                min="0"
                inputMode="numeric"
                value={ejercicio.reps}
                onChange={(e) => cambiarEjercicio(indice, "reps", e.target.value)}
              />
            </li>
          ))}
        </ul>

        <div className="acciones">
          <Boton
            type="button"
            compacto
            onClick={() =>
              setEditando({ ...editando, exercises: [...editando.exercises, { name: "", sets: "", reps: "" }] })
            }
          >
            AÑADIR EJERCICIO
          </Boton>
        </div>

        <Boton type="button" onClick={() => void guardar()}>
          GUARDAR
        </Boton>
        <Boton type="button" compacto onClick={() => setEditando(null)}>
          CANCELAR
        </Boton>
      </>
    );
  }

  return (
    <>
      <TituloPantalla pantalla="plantillas" />

      {fallo && <Aviso tono="rojo">{fallo}</Aviso>}

      <Boton type="button" onClick={() => { setFallo(null); setEditando(VACIA); }}>
        NUEVA PLANTILLA
      </Boton>

      <ul className="lista-plantillas">
        {lista.map((plantilla) => (
          <li key={plantilla.id}>
            <h2 className="nombre-plantilla">{plantilla.name}</h2>
            <Comentario>
              {plantilla.exercises.length} ejercicios
              {plantilla.level ? ` · ${plantilla.level}` : ""}
              {plantilla.user_id === null ? " · del sistema" : ""}
            </Comentario>

            {/* Solo las tuyas. Las del sistema dan 403 y el botón sobraría. */}
            {plantilla.user_id !== null && (
              <div className="acciones">
                <Boton type="button" compacto onClick={() => editar(plantilla)}>
                  EDITAR {plantilla.name.toUpperCase()}
                </Boton>
                <Boton type="button" compacto onClick={() => void borrar(plantilla)}>
                  BORRAR {plantilla.name.toUpperCase()}
                </Boton>
              </div>
            )}
          </li>
        ))}
      </ul>
    </>
  );
}
