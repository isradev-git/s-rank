import { useState } from "react";
import { Link } from "react-router";
import { ErrorApi, entrar, type Fallo } from "../api";
import { Boton, Campo, Comentario, TituloPantalla } from "../componentes";

export default function Login({ alEntrar }: { alEntrar: () => Promise<void> }) {
  const [correo, setCorreo] = useState("");
  const [contrasena, setContrasena] = useState("");
  const [fallo, setFallo] = useState<Fallo | null>(null);
  const [enviando, setEnviando] = useState(false);

  async function enviar(evento: React.FormEvent) {
    evento.preventDefault();
    setEnviando(true);
    setFallo(null);

    try {
      await entrar(correo, contrasena);
      // Con `await`: si la sesión no ha quedado abierta, el error cae en el catch de abajo
      // y se ve. Sin él, la promesa se pierde y el botón se queda en «ENTRANDO…».
      await alEntrar();
    } catch (error) {
      // El error ya viene traducido de api.ts. Aquí solo se decide dónde ponerlo.
      setFallo(
        error instanceof ErrorApi
          ? error.fallo
          : { general: "No hemos podido conectar. Inténtalo otra vez.", campos: {} },
      );
      setEnviando(false);
    }
  }

  return (
    <>
      <TituloPantalla pantalla="entrar" />
      <Comentario>Tu progreso te está esperando</Comentario>

      <form onSubmit={enviar}>
        <Campo
          etiqueta="correo"
          name="email"
          type="email"
          autoComplete="email"
          required
          value={correo}
          onChange={(e) => setCorreo(e.target.value)}
          error={fallo?.campos.email}
        />
        <Campo
          etiqueta="contraseña"
          name="password"
          type="password"
          autoComplete="current-password"
          required
          value={contrasena}
          onChange={(e) => setContrasena(e.target.value)}
          error={fallo?.campos.password}
        />

        {/* El error general va aparte del de campo: un «demasiados intentos» no es culpa
            del correo que hay escrito, y colgárselo debajo haría pensar que sí. */}
        {fallo?.general && (
          <p className="aviso" role="alert">
            {fallo.general}
          </p>
        )}

        <Boton type="submit" disabled={enviando}>
          {enviando ? "ENTRANDO…" : "ENTRAR"}
        </Boton>
      </form>

      <nav className="enlaces">
        <Link to="/recuperar">No recuerdo mi contraseña</Link>
        <Link to="/registro">Crear cuenta</Link>
      </nav>
    </>
  );
}
