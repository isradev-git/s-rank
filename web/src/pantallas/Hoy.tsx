import {
  BarraBloques,
  Comentario,
  FilaMision,
  InsigniaRango,
  Seccion,
  TituloPantalla,
} from "../componentes";
import { MISIONES, PROGRESO, SESION } from "../falso";

// Europe/Madrid fijo, no la zona del navegador: el servidor decide a qué día pertenecen
// las misiones con esa zona, y en un viaje «hoy» diría un día distinto del que puntúa.
const FECHA = new Intl.DateTimeFormat("es-ES", {
  weekday: "long",
  day: "numeric",
  month: "long",
  timeZone: "Europe/Madrid",
});

export default function Hoy() {
  const hechas = MISIONES.filter((m) => m.completed).length;

  return (
    <>
      <TituloPantalla pantalla="hoy" usuario={SESION.usuario} rango={SESION.rango} />
      <Comentario>{FECHA.format(new Date())}</Comentario>

      <div className="fila-nivel">
        <span className="nivel">NIVEL {PROGRESO.nivel}</span>
        <InsigniaRango rango={SESION.rango} />
      </div>

      <BarraBloques porcentaje={(PROGRESO.xp / PROGRESO.xpNivel) * 100} />
      <p className="xp">
        {PROGRESO.xp} / {PROGRESO.xpNivel} XP
      </p>
      <Comentario>racha de {PROGRESO.racha} días</Comentario>

      <Seccion titulo="Misiones de hoy" resumen={`[${hechas} de ${MISIONES.length}]`}>
        <ul className="lista-misiones">
          {MISIONES.map((mision) => (
            <FilaMision key={mision.key} mision={mision} />
          ))}
        </ul>
      </Seccion>
    </>
  );
}
