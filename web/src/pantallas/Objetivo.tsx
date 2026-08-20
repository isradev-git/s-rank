import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { ErrorApi, guardarDatosCuerpo, guardarObjetivoNutricional, usuarioActual } from "../api";
import { Boton, Campo, Comentario, FilaMacros, TituloPantalla } from "../componentes";
import {
  ACTIVIDADES, OBJETIVOS, calcularObjetivo,
  type ClaveActividad, type ClaveObjetivo, type DatosCuerpo,
} from "../formato";

const NUMEROS = new Intl.NumberFormat("es-ES", { useGrouping: "always" });

/** Una lista de opciones excluyentes. Botones con `aria-pressed` y no radios: cada toque
 *  cambia el número de arriba, no rellena un formulario que se envía luego. */
function Opciones<T extends string>({
  opciones,
  elegida,
  alElegir,
}: {
  opciones: readonly { clave: T; etiqueta: string }[];
  elegida: T;
  alElegir: (clave: T) => void;
}) {
  return (
    <ul className="lista-opciones">
      {opciones.map((o) => (
        <li key={o.clave}>
          <button
            type="button"
            className={o.clave === elegida ? "casilla marcada" : "casilla"}
            aria-pressed={o.clave === elegida}
            onClick={() => alElegir(o.clave)}
          >
            <span className="marca" aria-hidden="true">[{o.clave === elegida ? "✓" : " "}]</span>
            <span>{o.etiqueta}</span>
          </button>
        </li>
      ))}
    </ul>
  );
}

export default function Objetivo() {
  const navegar = useNavigate();
  const [cuerpo, setCuerpo] = useState<Partial<DatosCuerpo> | null>(null);
  const [paso, setPaso] = useState(1);
  const [objetivo, setObjetivo] = useState<ClaveObjetivo>("maintain");
  const [actividad, setActividad] = useState<ClaveActividad>("moderate");
  const [ajustes, setAjustes] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    usuarioActual().then((u) =>
      setCuerpo(
        u ? { weight: u.weight ?? undefined, height: u.height ?? undefined,
              age: u.age ?? undefined, gender: u.gender ?? undefined } : {},
      ),
    );
  }, []);

  if (!cuerpo) return <><TituloPantalla pantalla="mi objetivo" /><Comentario>cargando…</Comentario></>;

  const completo = (c: Partial<DatosCuerpo>): c is DatosCuerpo =>
    c.weight != null && c.height != null && c.age != null && c.gender != null;

  // Paso 0: sin peso, altura, edad y sexo no hay fórmula que valga. Se piden antes de
  // enseñar ninguna cifra, en vez de calcular con valores por defecto que serían mentira.
  if (!completo(cuerpo)) {
    return (
      <>
        <TituloPantalla pantalla="mi objetivo" />
        <Comentario>necesitamos cuatro datos para calcularlo</Comentario>
        {fallo && <p className="aviso" role="alert">{fallo}</p>}

        {cuerpo.weight == null && (
          <Campo etiqueta="Peso en kilos" name="peso" type="number" inputMode="decimal" step="0.1"
                 value={ajustes.weight ?? ""} onChange={(e) => setAjustes({ ...ajustes, weight: e.target.value })} />
        )}
        {cuerpo.height == null && (
          <Campo etiqueta="Altura en centímetros" name="altura" type="number" inputMode="numeric"
                 value={ajustes.height ?? ""} onChange={(e) => setAjustes({ ...ajustes, height: e.target.value })} />
        )}
        {cuerpo.age == null && (
          <Campo etiqueta="Edad" name="edad" type="number" inputMode="numeric"
                 value={ajustes.age ?? ""} onChange={(e) => setAjustes({ ...ajustes, age: e.target.value })} />
        )}
        {cuerpo.gender == null && (
          <Opciones
            opciones={[{ clave: "male", etiqueta: "Hombre" }, { clave: "female", etiqueta: "Mujer" }] as const}
            elegida={(ajustes.gender as "male" | "female") ?? "male"}
            alElegir={(g) => setAjustes({ ...ajustes, gender: g })}
          />
        )}

        <Boton
          type="button"
          disabled={guardando}
          onClick={() => {
            const nuevos: Partial<DatosCuerpo> = {};
            if (ajustes.weight) nuevos.weight = Number(ajustes.weight);
            if (ajustes.height) nuevos.height = Number(ajustes.height);
            if (ajustes.age) nuevos.age = Number(ajustes.age);
            nuevos.gender = (ajustes.gender as "male" | "female") ?? cuerpo.gender ?? "male";
            setGuardando(true);
            guardarDatosCuerpo(nuevos)
              .then(() => { setCuerpo({ ...cuerpo, ...nuevos }); setAjustes({}); })
              .catch(() => setFallo("No hemos podido guardar tus datos. Inténtalo otra vez."))
              .finally(() => setGuardando(false));
          }}
        >
          SIGUIENTE
        </Boton>
      </>
    );
  }

  const calculado = calcularObjetivo(cuerpo, actividad, objetivo);
  const cifra = (campo: keyof typeof calculado) =>
    ajustes[campo] !== undefined ? Number(ajustes[campo]) : (calculado[campo] as number);

  return (
    <>
      <TituloPantalla pantalla="mi objetivo" />
      <Comentario>paso {paso} de 3</Comentario>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      {paso === 1 && (
        <>
          <Comentario decorativo>qué quieres conseguir</Comentario>
          <Opciones opciones={OBJETIVOS} elegida={objetivo} alElegir={setObjetivo} />
        </>
      )}

      {paso === 2 && (
        <>
          <Comentario decorativo>cuánto te mueves</Comentario>
          <Opciones opciones={ACTIVIDADES} elegida={actividad} alElegir={setActividad} />
          <p className="xp">{NUMEROS.format(calculado.daily_calories)} kcal</p>
          <FilaMacros macros={{
            calories: calculado.daily_calories, protein: calculado.target_protein,
            carbs: calculado.target_carbs, fat: calculado.target_fat, fiber: 0, sugar: 0,
          }} />
        </>
      )}

      {paso === 3 && (
        <>
          <Comentario decorativo>puedes ajustarlo antes de guardar</Comentario>
          <Campo etiqueta="Calorías al día" name="daily_calories" type="number" inputMode="numeric"
                 value={cifra("daily_calories")}
                 onChange={(e) => setAjustes({ ...ajustes, daily_calories: e.target.value })} />
          <Campo etiqueta="Proteínas al día en gramos" name="target_protein" type="number"
                 inputMode="numeric" value={cifra("target_protein")}
                 onChange={(e) => setAjustes({ ...ajustes, target_protein: e.target.value })} />
          <Campo etiqueta="Hidratos al día en gramos" name="target_carbs" type="number"
                 inputMode="numeric" value={cifra("target_carbs")}
                 onChange={(e) => setAjustes({ ...ajustes, target_carbs: e.target.value })} />
          <Campo etiqueta="Grasas al día en gramos" name="target_fat" type="number"
                 inputMode="numeric" value={cifra("target_fat")}
                 onChange={(e) => setAjustes({ ...ajustes, target_fat: e.target.value })} />
        </>
      )}

      <div className="acciones">
        {paso > 1 && (
          <Boton type="button" compacto onClick={() => setPaso(paso - 1)}>ATRÁS</Boton>
        )}
        {paso < 3 ? (
          <Boton type="button" compacto onClick={() => setPaso(paso + 1)}>SIGUIENTE</Boton>
        ) : (
          <Boton
            type="button"
            disabled={guardando}
            onClick={() => {
              setGuardando(true);
              setFallo(null);
              guardarObjetivoNutricional({
                daily_calories: cifra("daily_calories"),
                target_protein: cifra("target_protein"),
                target_carbs: cifra("target_carbs"),
                target_fat: cifra("target_fat"),
                target_fiber: calculado.target_fiber,
                goal_type: objetivo,
              })
                .then(() => navegar("/nutricion"))
                .catch((error: unknown) => {
                  setFallo(
                    error instanceof ErrorApi && error.fallo.general
                      ? error.fallo.general
                      : "No hemos podido guardarlo. Inténtalo otra vez.",
                  );
                  setGuardando(false);
                });
            }}
          >
            {guardando ? "GUARDANDO…" : "GUARDAR MI OBJETIVO"}
          </Boton>
        )}
      </div>
    </>
  );
}
