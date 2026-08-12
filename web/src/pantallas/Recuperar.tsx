import { useState } from "react";
import { Link } from "react-router";
import { ErrorApi, cambiarContrasena, pedirCodigo, type Fallo } from "../api";
import { Boton, Campo, Comentario, TituloPantalla } from "../componentes";

/** El texto del paso 2. Es el mismo exista o no la cuenta: si la pantalla dijera algo
 *  distinto en cada caso, volvería a delatar qué correos están registrados, que es
 *  justo lo que el servidor evita respondiendo 200 siempre. */
const AVISO_ENVIADO =
  "Si ese correo está registrado, te hemos enviado un código de 6 cifras. Caduca en 30 minutos.";

function comoFallo(error: unknown): Fallo {
  return error instanceof ErrorApi
    ? error.fallo
    : { general: "No hemos podido conectar. Inténtalo otra vez.", campos: {} };
}

export default function Recuperar() {
  const [paso, setPaso] = useState<1 | 2 | 3>(1);
  const [correo, setCorreo] = useState("");
  const [codigo, setCodigo] = useState("");
  const [contrasena, setContrasena] = useState("");
  const [fallo, setFallo] = useState<Fallo | null>(null);
  const [enviando, setEnviando] = useState(false);

  async function pedir(evento: React.FormEvent) {
    evento.preventDefault();
    setEnviando(true);
    setFallo(null);

    try {
      await pedirCodigo(correo);
      // Se avanza sin mirar la respuesta. Solo un 422 —correo mal escrito— o un 429
      // dejan aquí, y ninguno de los dos dice nada sobre si la cuenta existe.
      setPaso(2);
    } catch (error) {
      setFallo(comoFallo(error));
    } finally {
      setEnviando(false);
    }
  }

  async function cambiar(evento: React.FormEvent) {
    evento.preventDefault();
    setEnviando(true);
    setFallo(null);

    try {
      await cambiarContrasena(correo, codigo, contrasena);
      setPaso(3);
    } catch (error) {
      setFallo(comoFallo(error));
    } finally {
      setEnviando(false);
    }
  }

  if (paso === 3) {
    return (
      <>
        <TituloPantalla pantalla="listo" />
        <Comentario>Contraseña cambiada. Ya puedes entrar con la nueva.</Comentario>
        <nav className="enlaces">
          <Link to="/login">Entrar</Link>
        </nav>
      </>
    );
  }

  return (
    <>
      <TituloPantalla pantalla="recuperar" />

      {paso === 1 ? (
        <>
          <Comentario>Te mandamos un código al correo</Comentario>
          <form onSubmit={pedir}>
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
            {fallo?.general && (
              <p className="aviso" role="alert">
                {fallo.general}
              </p>
            )}
            <Boton type="submit" disabled={enviando}>
              {enviando ? "ENVIANDO…" : "ENVIAR CÓDIGO"}
            </Boton>
          </form>
        </>
      ) : (
        <>
          <Comentario>{AVISO_ENVIADO}</Comentario>
          <form onSubmit={cambiar}>
            <Campo
              etiqueta="código"
              name="code"
              inputMode="numeric"
              autoComplete="one-time-code"
              maxLength={6}
              required
              value={codigo}
              onChange={(e) => setCodigo(e.target.value)}
              error={fallo?.campos.code}
            />
            <Campo
              etiqueta="contraseña nueva"
              name="password"
              type="password"
              autoComplete="new-password"
              minLength={8}
              required
              value={contrasena}
              onChange={(e) => setContrasena(e.target.value)}
              error={fallo?.campos.password}
            />
            <Comentario decorativo>ocho caracteres como mínimo</Comentario>
            {fallo?.general && (
              <p className="aviso" role="alert">
                {fallo.general}
              </p>
            )}
            <Boton type="submit" disabled={enviando}>
              {enviando ? "CAMBIANDO…" : "CAMBIAR CONTRASEÑA"}
            </Boton>
          </form>
        </>
      )}

      <nav className="enlaces">
        <Link to="/login">Volver a entrar</Link>
      </nav>
    </>
  );
}
