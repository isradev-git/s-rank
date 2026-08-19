/* Lo que pasó, en una pantalla. No calcula nada del Sistema: recibe el bloque `system`
   que devolvió el servidor y lo pinta. */

import { useState } from "react";
import { Navigate, useLocation, useNavigate } from "react-router";
import type { EntrenoGuardado } from "../api";
import { Aviso, Boton, Comentario, TituloPantalla, VentanaSistema } from "../componentes";
import { textoRecord, textoXpGanado } from "../formato";

type EstadoResumen = {
  nombre: string;
  duracion: number;
  volumen: number;
  series: number;
  /** null = quedó en la cola. Entonces no hay bloque `system` que enseñar. */
  guardado: EntrenoGuardado | null;
};

export default function Resumen() {
  const navegar = useNavigate();
  const estado = useLocation().state as EstadoResumen | null;
  const [ventanaCerrada, setVentanaCerrada] = useState(false);

  // Una recarga pierde el estado de la navegación. No pasa nada: para cuando se llega
  // aquí el entreno ya está en el servidor o en la cola.
  if (!estado) return <Navigate to="/" replace />;

  const { nombre, duracion, volumen, series, guardado } = estado;

  return (
    <>
      <TituloPantalla pantalla="resumen" />
      <Comentario>{nombre}</Comentario>

      <dl className="cifras">
        <div>
          <dt>Duración</dt>
          <dd>{duracion} min</dd>
        </div>
        <div>
          <dt>Volumen</dt>
          <dd>{volumen.toLocaleString("es-ES")} kg</dd>
        </div>
        <div>
          <dt>Series</dt>
          <dd>{series}</dd>
        </div>
      </dl>

      {guardado ? (
        <>
          <p className="xp">{textoXpGanado(guardado.system.xp_gained, duracion)}</p>

          {/* Del bloque `system`, no de `new_records`: es la forma que lee toda la
              interfaz, y las dos traen exactamente los mismos récords.

              El récord acaba saliendo dos veces —aquí y dentro de la ventana— y es a
              propósito: la ventana se cierra y no vuelve, así que si el récord viviera
              solo en ella, cerrarla lo borraría de la única pantalla que lo cuenta. */}
          {guardado.system.records.length > 0 && (
            <ul className="lista-records">
              {guardado.system.records.map((record) => (
                <li key={record.exercise}>{textoRecord(record)}</li>
              ))}
            </ul>
          )}

          {!ventanaCerrada && (
            <VentanaSistema sistema={guardado.system} alCerrar={() => setVentanaCerrada(true)} />
          )}
        </>
      ) : (
        // Sin bloque `system`: el XP lo decide el servidor y todavía no ha hablado.
        // Inventar una cifra para tapar el hueco sería calcular el XP en el cliente.
        <Aviso tono="ambar">Guardado en el móvil. Se subirá solo en cuanto haya conexión.</Aviso>
      )}

      <Boton type="button" onClick={() => navegar("/", { replace: true })}>
        VOLVER
      </Boton>
    </>
  );
}
