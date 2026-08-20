import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import {
  ErrorApi, comidasDelDia, diaDeHoy, objetivoNutricional,
  type BloqueSistema, type DiaDeComidas, type DiaDeHoy, type Usuario,
} from "../api";
import { borrar, leer } from "../borrador";
import {
  BarraBloques,
  Boton,
  Comentario,
  FilaMacros,
  FilaMision,
  InsigniaRango,
  Seccion,
  TituloPantalla,
  VentanaSistema,
} from "../componentes";
import { seriesHechas, textoAntiguedad, textoRacha, textoRestante } from "../formato";
import { SeccionActividad, SeccionAgua, SeccionPeso, SeccionSuplementos } from "./habitos";

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

  const [resumen, setResumen] = useState<DiaDeComidas | null>(null);
  const [objetivo, setObjetivo] = useState<number | null>(null);
  // Lo que abre la ventana del Sistema. Se guarda aquí y no en cada sección: si cada una
  // tuviera la suya, dos podrían salir a la vez.
  const [premio, setPremio] = useState<BloqueSistema | null>(null);

  // El borrador se lee una vez al montar. Si cambia, es porque el usuario está en la
  // sesión, y al volver aquí la pantalla se monta otra vez.
  const [borrador, setBorrador] = useState(() => leer());

  function descartar() {
    // La única forma de perder un entreno a propósito. Dos pulsaciones, a propósito.
    if (!confirm("¿Descartar el entreno a medias? No se puede recuperar.")) return;
    borrar();
    setBorrador(null);
  }

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      const dia = await diaDeHoy();
      setDatos(dia);

      const [comidas, meta] = await Promise.all([
        comidasDelDia(dia.date),
        objetivoNutricional(),
      ]);
      setResumen(comidas);
      setObjetivo(meta.has_goal ? meta.goal.daily_calories : null);
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

  // Solo abre la ventana si hay algo que anunciar. VentanaSistema ya devuelve null cuando
  // no hay motivos, pero dejarlo montado con un bloque vacío pone un diálogo invisible en
  // el árbol y el foco se va a él.
  function recogerPremio(sistema: BloqueSistema) {
    const hayAlgo =
      sistema.level_up || sistema.rank_up ||
      sistema.achievements_unlocked.length > 0 || sistema.records.length > 0;
    if (hayAlgo) setPremio(sistema);
    // Cambió el progreso: las misiones y la barra de XP de arriba ya no son las de antes.
    void cargar();
  }

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

      <Seccion titulo="Misiones de hoy" resumen={`${hechas} de ${obligatorias.length}`}>
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

      {/* Son `<Link>` y no botones con `navigate` porque son navegación: se pueden abrir en
          otra pestaña, se ven al mantener pulsado y el navegador los trata como lo que son. */}
      <Seccion titulo="Entreno de hoy" resumen={borrador ? "a medias" : "sin empezar"}>
        {borrador ? (
          <>
            <Comentario>
              {borrador.nombre} · {seriesHechas(borrador.exercises)} series ·{" "}
              {textoAntiguedad(borrador.inicio)}
            </Comentario>
            <div className="acciones">
              <Link className="boton compacto" to="/entrenar/sesion">
                <span aria-hidden="true">[ </span>SEGUIR ENTRENANDO<span aria-hidden="true"> ]</span>
              </Link>
              <Boton type="button" compacto onClick={descartar}>
                DESCARTAR
              </Boton>
            </div>
          </>
        ) : (
          <>
            <Comentario>{datos.suggested_workout.reason}</Comentario>
            <div className="acciones">
              <Link className="boton compacto" to="/entrenar">
                <span aria-hidden="true">[ </span>ENTRENAR<span aria-hidden="true"> ]</span>
              </Link>
            </div>
          </>
        )}
      </Seccion>

      {premio && <VentanaSistema sistema={premio} alCerrar={() => setPremio(null)} />}

      <Seccion
        titulo="Nutrición"
        resumen={resumen ? `${Math.round(resumen.totals.calories)} kcal` : "cargando"}
      >
        {resumen && (
          <>
            <Comentario>{textoRestante(resumen.totals.calories, objetivo)}</Comentario>
            <FilaMacros macros={resumen.totals} />
          </>
        )}

        <div className="acciones">
          <Link className="boton compacto" to="/nutricion">
            <span aria-hidden="true">[ </span>VER EL DÍA<span aria-hidden="true"> ]</span>
          </Link>
          {/* La misión de proteína solo existe si hay objetivo nutricional. Que no esté
              es la señal de que falta, y este es el momento de invitar. */}
          {!misiones.some((m) => m.key === "protein") && (
            <Link className="boton compacto" to="/nutricion/objetivo">
              <span aria-hidden="true">[ </span>CALCULAR MI OBJETIVO<span aria-hidden="true"> ]</span>
            </Link>
          )}
        </div>
      </Seccion>

      <SeccionAgua fecha={datos.date} alGanar={recogerPremio} />
      <SeccionSuplementos fecha={datos.date} alGanar={recogerPremio} />
      <SeccionActividad fecha={datos.date} alGanar={recogerPremio} />
      <SeccionPeso pesoActual={usuario.weight} alGanar={recogerPremio} />
    </>
  );
}
