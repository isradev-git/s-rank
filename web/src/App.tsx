import { useEffect, useState } from "react";
import { NavLink, Navigate, Outlet, Route, Routes } from "react-router";
import { SESION_CADUCADA, usuarioActual, type Usuario } from "./api";
import { Comentario, TituloPantalla } from "./componentes";
import Hoy from "./pantallas/Hoy";
import Login from "./pantallas/Login";

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
    return (
      <Routes>
        <Route
          path="/login"
          element={<Login alEntrar={() => usuarioActual().then(setUsuario)} />}
        />
        <Route path="/registro" element={<Pendiente titulo="crear cuenta" fase="1.1" />} />
        <Route path="/recuperar" element={<Pendiente titulo="recuperar" fase="1.1" />} />
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
        <Route
          path="/perfil"
          element={<Pendiente titulo="perfil" fase="1.5" usuario={usuario.name} />}
        />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
