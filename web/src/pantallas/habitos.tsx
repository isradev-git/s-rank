/* Las cuatro secciones de hábitos que viven dentro de «hoy». Van juntas en un fichero
   porque son pequeñas y todas hacen lo mismo: leer su día, tocar, reconciliar.

   El spec §8 las listaba como sub-pantallas. Aquí son secciones: beber un vaso pasa de
   tres toques —entrar, pulsar, volver— a uno, y eso en algo que se hace ocho veces al día
   se nota más que cualquier otra decisión de esta fase. */

import { useCallback, useEffect, useState } from "react";
import {
  ErrorApi, actividad, agua, anadirAgua, guardarActividad, guardarPeso,
  marcarSuplemento, suplementos,
  type BloqueSistema, type ClaveSuplemento, type DiaDeAgua, type Suplemento,
} from "../api";
import { BarraBloques, Boton, Campo, Casilla, Comentario, Seccion } from "../componentes";
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

export function SeccionSuplementos({
  fecha,
  alGanar,
}: {
  fecha: string;
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [items, setItems] = useState<Suplemento[] | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    suplementos(fecha)
      .then((r) => setItems(r.items))
      .catch(() => setFallo("No hemos podido cargar los suplementos de hoy."));
  }, [fecha]);

  async function marcar(clave: ClaveSuplemento, tomado: boolean) {
    if (!items) return;
    const antes = items;
    setItems(items.map((s) => (s.key === clave ? { ...s, taken: tomado } : s)));
    setFallo(null);

    try {
      // ⚠️ El servidor NO devuelve el estado actualizado, solo el bloque `system`. Lo que
      // se ve es lo que pintamos aquí.
      const respuesta = await marcarSuplemento(fecha, clave, tomado);
      alGanar(respuesta.system);
    } catch (error) {
      setItems(antes);
      setFallo(
        error instanceof ErrorApi && error.fallo.general?.includes("conexión")
          ? "No se ha podido guardar. Comprueba la conexión."
          : "No se ha podido guardar. Inténtalo otra vez.",
      );
    }
  }

  if (!items) {
    return <Seccion titulo="Suplementos" resumen="cargando"><Comentario>cargando…</Comentario></Seccion>;
  }

  const tomados = items.filter((s) => s.taken).length;

  return (
    // El recuento y nada más. Si la misión está cumplida lo decide el servidor: la misión
    // solo se completa con los cuatro, y adelantarlo aquí sería que la app puntuara.
    <Seccion titulo="Suplementos" resumen={`${tomados} de ${items.length}`}>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <ul className="rejilla-casillas">
        {items.map((s) => (
          <li key={s.key}>
            <Casilla
              etiqueta={s.name}
              marcada={s.taken}
              onCambiar={(marcada) => void marcar(s.key, marcada)}
            />
          </li>
        ))}
      </ul>
    </Seccion>
  );
}

const aNumero = (texto: string, porDefecto = 0) => {
  const n = Number(texto);
  return Number.isFinite(n) && n >= 0 ? n : porDefecto;
};

export function SeccionActividad({
  fecha,
  alGanar,
}: {
  fecha: string;
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [pasos, setPasos] = useState("");
  const [calorias, setCalorias] = useState("");
  const [guardado, setGuardado] = useState<number | null>(null);
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    actividad(fecha)
      .then((a) => {
        setGuardado(a.steps);
        if (a.steps > 0) setPasos(String(a.steps));
        if (a.calories_burned > 0) setCalorias(String(a.calories_burned));
      })
      .catch(() => setFallo("No hemos podido cargar la actividad de hoy."));
  }, [fecha]);

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      // ⚠️ El servidor exige las dos cifras, pero mucha gente solo conoce sus pasos. Las
      // calorías vacías van a 0. NO se estiman a partir de los pasos: sería un número
      // inventado presentado como dato de salud.
      const r = await guardarActividad(fecha, aNumero(pasos), aNumero(calorias));
      setGuardado(r.steps);
      if (r.system) alGanar(r.system);
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No se ha podido guardar. Inténtalo otra vez.",
      );
    } finally {
      setGuardando(false);
    }
  }

  return (
    // Plegada de salida: son dos campos que se rellenan una vez al día, y abiertos
    // empujaban el peso media pantalla hacia abajo. El resumen sigue diciendo los pasos.
    <Seccion
      titulo="Actividad"
      resumen={guardado && guardado > 0 ? `${guardado} pasos` : "sin apuntar"}
      abierta={false}
    >
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Pasos" name="pasos" type="number" inputMode="numeric" min="0"
             max="150000" value={pasos} onChange={(e) => setPasos(e.target.value)} />
      <Campo etiqueta="Calorías quemadas, si tu reloj te las da" name="calorias"
             type="number" inputMode="numeric" min="0" max="10000" value={calorias}
             onChange={(e) => setCalorias(e.target.value)} />

      {/* Dentro de `acciones` para que tenga aire por arriba: un botón compacto suelto
          lleva margen cero y salía pegado al campo de encima. */}
      <div className="acciones">
        <Boton type="button" compacto disabled={guardando} onClick={() => void guardar()}>
          {/* «GUARDAR» a secas lo dice también el peso, y en «hoy» están los dos. */}
          {guardando ? "GUARDANDO…" : "GUARDAR LOS PASOS"}
        </Boton>
      </div>
    </Seccion>
  );
}

export function SeccionPeso({
  pesoActual,
  alGanar,
}: {
  pesoActual: number | null;
  alGanar: (sistema: BloqueSistema) => void;
}) {
  const [kilos, setKilos] = useState(pesoActual == null ? "" : String(pesoActual));
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      const r = await guardarPeso(aNumero(kilos));
      // ⚠️ `system` llega a null cuando el peso no cambió. No hay nada que anunciar.
      if (r.system) alGanar(r.system);
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No se ha podido guardar. Inténtalo otra vez.",
      );
    } finally {
      setGuardando(false);
    }
  }

  return (
    <Seccion titulo="Peso" resumen={pesoActual == null ? "sin apuntar" : `${pesoActual} kg`}
             abierta={false}>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Peso en kilos" name="peso" type="number" inputMode="decimal"
             step="0.1" min="0" value={kilos} onChange={(e) => setKilos(e.target.value)} />

      <div className="acciones">
        <Boton type="button" compacto disabled={guardando} onClick={() => void guardar()}>
          {guardando ? "GUARDANDO…" : "GUARDAR EL PESO"}
        </Boton>
      </div>
    </Seccion>
  );
}
