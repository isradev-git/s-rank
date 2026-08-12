import { useCallback, useEffect, useState } from "react";
import { ErrorApi, diaDeHoy, type DiaDeHoy, type Usuario } from "../api";
import {
  BarraBloques,
  Boton,
  Comentario,
  FilaMision,
  InsigniaRango,
  Seccion,
  TituloPantalla,
} from "../componentes";
import { textoRacha } from "../formato";

// `date` ya viene resuelto: el servidor decide en Europe/Madrid a qué día pertenecen las
// misiones, y usar la zona del navegador diría «hoy» sobre un día distinto del que puntúa.
// Aquí solo hay que escribirlo en español, así que se lee y se escribe en UTC y no hay
// conversión que pueda correr la fecha un día.
const FECHA = new Intl.DateTimeFormat("es-ES", {
  weekday: "long",
  day: "numeric",
  month: "long",
  timeZone: "UTC",
});

export default function Hoy({ usuario }: { usuario: Usuario }) {
  const [datos, setDatos] = useState<DiaDeHoy | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);
  const [cargando, setCargando] = useState(true);

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      setDatos(await diaDeHoy());
      setFallo(null);
    } catch (error) {
      // El fallo se enseña aunque haya datos viejos en pantalla. Tragárselo porque «algo
      // se ve» es peor: el usuario se queda mirando cifras de ayer creyéndolas de hoy.
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido conectar. Inténtalo otra vez.",
      );
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => {
    void cargar();
  }, [cargar]);

  // Al volver a la pestaña se recarga. Sin esto, quien deje la app abierta toda la noche
  // ve las misiones de ayer, porque el día lo decide el servidor y no el reloj de aquí.
  useEffect(() => {
    const alVolver = () => {
      if (document.visibilityState === "visible") void cargar();
    };
    document.addEventListener("visibilitychange", alVolver);
    return () => document.removeEventListener("visibilitychange", alVolver);
  }, [cargar]);

  if (!datos) {
    return (
      <>
        <TituloPantalla pantalla="hoy" usuario={usuario.name} />
        {cargando ? (
          <Comentario>cargando…</Comentario>
        ) : (
          <>
            <p className="aviso" role="alert">
              {fallo}
            </p>
            <Boton type="button" onClick={() => void cargar()}>
              REINTENTAR
            </Boton>
          </>
        )}
      </>
    );
  }

  const { progress: progreso, quests: misiones } = datos;
  const obligatorias = misiones.filter((m) => !m.is_optional);
  const opcionales = misiones.filter((m) => m.is_optional);
  const hechas = obligatorias.filter((m) => m.completed).length;

  return (
    <>
      <TituloPantalla pantalla="hoy" usuario={usuario.name} rango={progreso.rank} />
      <Comentario>{FECHA.format(new Date(`${datos.date}T00:00:00Z`))}</Comentario>

      {fallo && (
        <p className="aviso" role="alert">
          {fallo}
        </p>
      )}

      <div className="fila-nivel">
        <span className="nivel">NIVEL {progreso.level}</span>
        <InsigniaRango rango={progreso.rank} />
      </div>

      <BarraBloques porcentaje={(progreso.xp_into_level / progreso.xp_for_next) * 100} />
      <p className="xp">
        {progreso.xp_into_level} / {progreso.xp_for_next} XP
      </p>
      <Comentario>{textoRacha(progreso.current_streak)}</Comentario>

      <Seccion
        titulo="Misiones de hoy"
        resumen={`[${hechas} de ${obligatorias.length}]`}
      >
        <ul className="lista-misiones">
          {obligatorias.map((mision) => (
            <FilaMision key={mision.key} mision={mision} />
          ))}
        </ul>

        {/* Las opcionales van aparte: no fallarlas no cuesta nada, y mezclarlas con las
            demás haría creer que sí. */}
        {opcionales.length > 0 && (
          <>
            <Comentario decorativo>si te sobra tiempo</Comentario>
            <ul className="lista-misiones">
              {opcionales.map((mision) => (
                <FilaMision key={mision.key} mision={mision} />
              ))}
            </ul>
          </>
        )}
      </Seccion>
    </>
  );
}
