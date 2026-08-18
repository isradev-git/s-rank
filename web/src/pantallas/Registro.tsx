import { useState } from "react";
import { Link } from "react-router";
import { ErrorApi, registrar, type Fallo } from "../api";
import { Boton, Campo, Comentario, TituloPantalla } from "../componentes";

export default function Registro({ alEntrar }: { alEntrar: () => Promise<void> }) {
  const [nombre, setNombre] = useState("");
  const [correo, setCorreo] = useState("");
  const [contrasena, setContrasena] = useState("");
  const [fallo, setFallo] = useState<Fallo | null>(null);
  const [enviando, setEnviando] = useState(false);

  async function enviar(evento: React.FormEvent) {
    evento.preventDefault();
    setEnviando(true);
    setFallo(null);

    try {
      // El servidor deja la sesión abierta al registrar, así que se entra directo.
      await registrar(nombre, correo, contrasena);
      await alEntrar();
    } catch (error) {
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
      <TituloPantalla pantalla="crear cuenta" />
      <Comentario>Se empieza en el nivel 1, como todo el mundo</Comentario>

      <form onSubmit={enviar}>
        <Campo
          etiqueta="nombre"
          name="name"
          autoComplete="given-name"
          maxLength={60}
          required
          value={nombre}
          onChange={(e) => setNombre(e.target.value)}
          error={fallo?.campos.name}
        />
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
          {enviando ? "CREANDO…" : "CREAR CUENTA"}
        </Boton>
      </form>

      <nav className="enlaces">
        <Link to="/login">Ya tengo cuenta</Link>
      </nav>
    </>
  );
}
