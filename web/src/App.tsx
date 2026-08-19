import { useCallback, useEffect, useState } from "react";
import { NavLink, Navigate, Outlet, Route, Routes } from "react-router";
import {
  SESION_CADUCADA,
  salir,
  sesionAbierta,
  usuarioActual,
  type BloqueSistema,
  type Usuario,
} from "./api";
import { pendientes, subirPendientes } from "./borrador";
import { Aviso, Boton, Comentario, TituloPantalla, VentanaSistema } from "./componentes";
import Elegir from "./pantallas/Elegir";
import Hoy from "./pantallas/Hoy";
import Login from "./pantallas/Login";
import Plantillas from "./pantallas/Plantillas";
import Recuperar from "./pantallas/Recuperar";
import Registro from "./pantallas/Registro";
import Resumen from "./pantallas/Resumen";
import Sesion from "./pantallas/Sesion";

/** Vive encima de las pestañas y no dentro de una pantalla: es una advertencia de pérdida
 *  de datos y esas se ven desde donde se esté. (El borrador a medias es otra cosa y va en
 *  «hoy», porque se retoma yendo a entrenar.)
 *
 *  Se reintenta al recuperar la conexión, al abrir la aplicación y con el botón. Sin
 *  retroceso exponencial: al otro lado hay un hosting compartido con un usuario. */
function AvisoPendientes() {
  const [cuantos, setCuantos] = useState(() => pendientes().length);
  const [premio, setPremio] = useState<BloqueSistema | null>(null);
  const [subiendo, setSubiendo] = useState(false);

  // Estable a propósito. El efecto de abajo lo tiene como dependencia, así que una función
  // que cambiara con `subiendo` volvería a lanzar el efecto en cada cambio —empieza a
  // subir, termina, se vuelve a lanzar— y sería una petición detrás de otra mientras
  // quedara algo en la cola. La guarda de concurrencia está dentro de subirPendientes,
  // que es donde tiene que estar: allí cubre también los dos disparadores coincidiendo.
  const reintentar = useCallback(async () => {
    if (pendientes().length === 0) return;
    setSubiendo(true);
    const subidos = await subirPendientes();
    // Si algo de lo que subió solo traía nivel, rango, logro o récord, se celebra igual
    // que si se hubiera subido en el momento. Es el mismo componente.
    const conPremio = subidos.find(
      (e) =>
        e.system.level_up ||
        e.system.rank_up ||
        e.system.achievements_unlocked.length ||
        e.system.records.length,
    );
    if (conPremio) setPremio(conPremio.system);
    setCuantos(pendientes().length);
    setSubiendo(false);
  }, []);

  useEffect(() => {
    // La referencia se guarda: `() => void reintentar()` crea una función distinta cada
    // vez, y la limpieza intentaría quitar un oyente que nunca se registró.
    const alVolverLaRed = () => void reintentar();
    void reintentar();
    window.addEventListener("online", alVolverLaRed);
    return () => window.removeEventListener("online", alVolverLaRed);
  }, [reintentar]);

  if (cuantos === 0 && !premio) return null;

  return (
    <>
      {cuantos > 0 && (
        <div className="pendientes">
          <Aviso tono="ambar">
            {cuantos === 1
              ? "1 entreno pendiente de subir"
              : `${cuantos} entrenos pendientes de subir`}
          </Aviso>
          {/* «SUBIR AHORA» y no «REINTENTAR»: sin conexión, «hoy» ya enseña su propio
              REINTENTAR y quedarían dos botones con el mismo nombre en la misma pantalla,
              que para quien navega con lector no se distinguen. Este dice lo que hace. */}
          <Boton type="button" compacto disabled={subiendo} onClick={() => void reintentar()}>
            {subiendo ? "SUBIENDO…" : "SUBIR AHORA"}
          </Boton>
        </div>
      )}
      {premio && <VentanaSistema sistema={premio} alCerrar={() => setPremio(null)} />}
    </>
  );
}

/** Las tres pestañas, arriba y fijas. Fuera de ellas: login, registro y recuperar. */
function ConPestanas() {
  return (
    <>
      <nav className="pestanas">
        <NavLink to="/" end>
          hoy
        </NavLink>
        <NavLink to="/progreso">progreso</NavLink>
        <NavLink to="/perfil">perfil</NavLink>
      </nav>
      <AvisoPendientes />
      <Outlet />
    </>
  );
}

/** Un hueco con su fecha de llegada, que es lo que la fase 1.1 pide para estas dos. */
function Pendiente({ titulo, fase, usuario }: { titulo: string; fase: string; usuario?: string }) {
  return (
    <>
      <TituloPantalla pantalla={titulo} usuario={usuario} />
      <Comentario>llega en la fase {fase}</Comentario>
    </>
  );
}

/** Perfil es de la fase 1.5, pero salir tiene que poderse desde ya. */
function Perfil({ usuario, alSalir }: { usuario: Usuario; alSalir: () => void }) {
  const [saliendo, setSaliendo] = useState(false);

  async function cerrar() {
    setSaliendo(true);
    // Si la petición falla, la sesión de aquí se cierra igual: dejar al usuario dentro
    // porque el servidor no contestó es lo contrario de lo que acaba de pedir. La cookie
    // caduca sola, y volver a entrar la sustituye.
    try {
      await salir();
    } catch {
      // Sin nada que hacer: el usuario ya se va.
    }
    alSalir();
  }

  return (
    <>
      <TituloPantalla pantalla="perfil" usuario={usuario.name} />
      <Comentario>llega en la fase 1.5</Comentario>
      <Boton type="button" onClick={() => void cerrar()} disabled={saliendo}>
        {saliendo ? "SALIENDO…" : "SALIR"}
      </Boton>
    </>
  );
}

export default function App() {
  // undefined mientras se pregunta al servidor, null si no hay nadie. Distinguirlos evita
  // el parpadeo de enseñar el login medio segundo a quien ya tenía la sesión abierta.
  const [usuario, setUsuario] = useState<Usuario | null | undefined>(undefined);

  useEffect(() => {
    usuarioActual().then(setUsuario);
  }, []);

  // El 401 se maneja aquí y solo aquí: da igual desde qué pantalla salte, la sesión se
  // limpia y las rutas de abajo llevan al login solas.
  useEffect(() => {
    const alCaducar = () => setUsuario(null);
    window.addEventListener(SESION_CADUCADA, alCaducar);
    return () => window.removeEventListener(SESION_CADUCADA, alCaducar);
  }, []);

  if (usuario === undefined) {
    return <Comentario>comprobando la sesión…</Comentario>;
  }

  if (usuario === null) {
    // Nada de tragarse el resultado: si después de entrar el servidor dice que no hay
    // nadie, `sesionAbierta` lanza un error con su texto y lo enseña la propia pantalla.
    const entrar = () => sesionAbierta().then(setUsuario);

    return (
      <Routes>
        <Route path="/login" element={<Login alEntrar={entrar} />} />
        <Route path="/registro" element={<Registro alEntrar={entrar} />} />
        <Route path="/recuperar" element={<Recuperar />} />
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    );
  }

  return (
    <Routes>
      <Route element={<ConPestanas />}>
        <Route index element={<Hoy usuario={usuario} />} />
        <Route
          path="/progreso"
          element={<Pendiente titulo="progreso" fase="1.4" usuario={usuario.name} />}
        />
        <Route path="/perfil" element={<Perfil usuario={usuario} alSalir={() => setUsuario(null)} />} />
        {/* Dentro de ConPestanas: son sub-pantallas de «hoy» (spec §8) y la navegación no
            desaparece por estar entrenando. */}
        <Route path="/entrenar" element={<Elegir />} />
        <Route path="/entrenar/sesion" element={<Sesion />} />
        <Route path="/entrenar/resumen" element={<Resumen />} />
        <Route path="/plantillas" element={<Plantillas />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
