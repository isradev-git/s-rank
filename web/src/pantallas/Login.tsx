import { useState } from "react";
import { Link, useNavigate } from "react-router";
import { Boton, Campo, Comentario, TituloPantalla } from "../componentes";

export default function Login() {
  const navegar = useNavigate();
  const [correo, setCorreo] = useState("");
  const [contrasena, setContrasena] = useState("");

  // ponytail: de momento entra sin preguntar a nadie. El paso 2 lo cambia por la
  // llamada real a POST /api/login contra el Laravel local.
  function entrar(evento: React.FormEvent) {
    evento.preventDefault();
    navegar("/");
  }

  return (
    <>
      <TituloPantalla pantalla="entrar" />
      <Comentario>Tu progreso te está esperando</Comentario>

      <form onSubmit={entrar}>
        <Campo
          etiqueta="correo"
          name="email"
          type="email"
          autoComplete="email"
          required
          value={correo}
          onChange={(e) => setCorreo(e.target.value)}
        />
        <Campo
          etiqueta="contraseña"
          name="password"
          type="password"
          autoComplete="current-password"
          required
          value={contrasena}
          onChange={(e) => setContrasena(e.target.value)}
        />
        <Boton type="submit">ENTRAR</Boton>
      </form>

      <nav className="enlaces">
        <Link to="/recuperar">No recuerdo mi contraseña</Link>
        <Link to="/registro">Crear cuenta</Link>
      </nav>
    </>
  );
}
