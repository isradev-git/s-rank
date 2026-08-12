import { NavLink, Outlet, Route, Routes } from "react-router";
import { Comentario, TituloPantalla } from "./componentes";
import { SESION } from "./falso";
import Login from "./pantallas/Login";
import Hoy from "./pantallas/Hoy";

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
function Pendiente({
  titulo,
  fase,
  conSesion = false,
}: {
  titulo: string;
  fase: string;
  conSesion?: boolean;
}) {
  return (
    <>
      {conSesion ? (
        <TituloPantalla pantalla={titulo} usuario={SESION.usuario} rango={SESION.rango} />
      ) : (
        <TituloPantalla pantalla={titulo} />
      )}
      <Comentario>llega en la fase {fase}</Comentario>
    </>
  );
}

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/registro" element={<Pendiente titulo="crear cuenta" fase="1.1" />} />
      <Route path="/recuperar" element={<Pendiente titulo="recuperar" fase="1.1" />} />
      <Route element={<ConPestanas />}>
        <Route index element={<Hoy />} />
        <Route path="/progreso" element={<Pendiente titulo="progreso" fase="1.4" conSesion />} />
        <Route path="/perfil" element={<Pendiente titulo="perfil" fase="1.5" conSesion />} />
      </Route>
    </Routes>
  );
}
