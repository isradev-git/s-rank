import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router";
import {
  ErrorApi, TIPOS_COMIDA, diaDeHoy, registrarComida, type TipoComida,
} from "../api";
import { ChipComida, Comentario, TituloPantalla } from "../componentes";
import { apuntar, recientes, type Reciente } from "../recientes";

/** El tipo viene de la URL, o sea de fuera. Cualquier cosa que no sea una de las cuatro
 *  claves del servidor cae en desayuno: un 422 por un parámetro mal escrito no es algo
 *  que el usuario pueda arreglar. */
function tipoDeLaUrl(valor: string | null): TipoComida {
  return TIPOS_COMIDA.some((t) => t.clave === valor) ? (valor as TipoComida) : "breakfast";
}

export default function AnadirComida() {
  const [parametros] = useSearchParams();
  const navegar = useNavigate();
  const tipo = tipoDeLaUrl(parametros.get("tipo"));
  const etiqueta = TIPOS_COMIDA.find((t) => t.clave === tipo)!.etiqueta;

  const [fecha, setFecha] = useState<string | null>(null);
  const [lista, setLista] = useState<Reciente[]>(() => recientes(tipo));
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    diaDeHoy()
      .then((hoy) => setFecha(hoy.date))
      .catch(() => setFallo("No hemos podido saber qué día es hoy. Comprueba la conexión."));
  }, []);

  useEffect(() => setLista(recientes(tipo)), [tipo]);

  async function registrar(reciente: Reciente, gramos: number) {
    if (!fecha) return;
    setGuardando(true);
    setFallo(null);
    try {
      await registrarComida({
        date: fecha,
        meal_type: tipo,
        food_item_id: reciente.id,
        quantity_grams: gramos,
      });
      apuntar({ ...reciente, gramos });
      navegar("/nutricion");
    } catch (error) {
      // El chip vuelve a poderse pulsar: el usuario querrá reintentar sin recargar.
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido registrarla. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }

  return (
    <>
      <TituloPantalla pantalla="añadir comida" />
      <Comentario>{etiqueta}</Comentario>

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Comentario decorativo>lo de siempre</Comentario>
      {lista.length === 0 ? (
        <Comentario>todavía no has registrado nada aquí</Comentario>
      ) : (
        <ul className="lista-chips">
          {lista.map((reciente) => (
            <ChipComida
              key={`${reciente.id}-${reciente.tipo}`}
              reciente={reciente}
              kcal={(reciente.kcal100 * reciente.gramos) / 100}
              disabled={guardando || !fecha}
              onRegistrar={() => void registrar(reciente, reciente.gramos)}
              // ponytail: ajustar la cantidad usa prompt(). Es feo y no se puede estilar,
              // pero es nativo, accesible y son cero líneas de diálogo propio. El techo:
              // si molesta cómo se ve, se sustituye por un campo desplegable dentro del
              // propio chip.
              onAjustar={() => {
                const escrito = prompt(`¿Cuántos gramos de ${reciente.nombre}?`, String(reciente.gramos));
                const gramos = Number(escrito);
                if (Number.isFinite(gramos) && gramos >= 1 && gramos <= 5000) {
                  void registrar(reciente, gramos);
                }
              }}
            />
          ))}
        </ul>
      )}
    </>
  );
}
