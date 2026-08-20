import { useEffect, useState } from "react";
import { Link, useNavigate, useSearchParams } from "react-router";
import {
  ErrorApi, TIPOS_COMIDA, buscarAlimentos, diaDeHoy, registrarComida,
  type Alimento, type TipoComida,
} from "../api";
import { Boton, Campo, ChipComida, Comentario, FilaMacros, TituloPantalla } from "../componentes";
import { macrosPara } from "../formato";
import { apuntar, recientes, type Reciente } from "../recientes";

const ESPERA_MS = 300;

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

  const [texto, setTexto] = useState("");
  const [encontrados, setEncontrados] = useState<Alimento[] | null>(null);
  const [elegido, setElegido] = useState<Alimento | null>(null);
  const [gramos, setGramos] = useState(100);

  const [aMano, setAMano] = useState(false);
  const [nombre, setNombre] = useState("");
  const [kcal, setKcal] = useState("");
  const [proteina, setProteina] = useState("");
  const [hidratos, setHidratos] = useState("");
  const [grasas, setGrasas] = useState("");

  useEffect(() => {
    diaDeHoy()
      .then((hoy) => setFecha(hoy.date))
      .catch(() => setFallo("No hemos podido saber qué día es hoy. Comprueba la conexión."));
  }, []);

  useEffect(() => setLista(recientes(tipo)), [tipo]);

  // Una petición por tecla contra un catálogo de 1.506 alimentos, desde una conexión de
  // móvil, es tirar datos. Se espera a que el usuario pare de escribir.
  useEffect(() => {
    const limpio = texto.trim();
    if (limpio.length < 2) {
      setEncontrados(null);
      return;
    }
    const temporizador = setTimeout(() => {
      buscarAlimentos(limpio)
        .then(setEncontrados)
        .catch(() => setFallo("No hemos podido buscar. Comprueba la conexión."));
    }, ESPERA_MS);
    return () => clearTimeout(temporizador);
  }, [texto]);

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

  const numero = (texto: string) => {
    const n = Number(texto);
    return Number.isFinite(n) && n >= 0 ? n : 0;
  };

  async function registrarAMano() {
    if (!fecha) return;
    setGuardando(true);
    setFallo(null);
    try {
      await registrarComida({
        date: fecha,
        meal_type: tipo,
        custom_food_name: nombre.trim(),
        calories: numero(kcal),
        protein: numero(proteina),
        carbs: numero(hidratos),
        fat: numero(grasas),
      });
      // A propósito, no se apunta en los recientes: sin food_item_id no se puede volver a
      // registrar de un toque, y un chip que no registra es un botón que miente.
      navegar("/nutricion");
    } catch (error) {
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

      {/* Con la entrada a mano abierta, el buscador entero se retira. Si no, habría dos
          botones «AÑADIR» en la misma pantalla y para quien navega con lector de pantalla
          serían indistinguibles. */}
      {!aMano && (
      <>
      <Comentario decorativo>buscar en el catálogo</Comentario>
      <Campo
        etiqueta="Buscar un alimento"
        name="buscar"
        type="search"
        autoComplete="off"
        value={texto}
        onChange={(e) => { setTexto(e.target.value); setElegido(null); }}
      />

      {encontrados?.length === 0 && (
        <>
          <Comentario>no hemos encontrado nada con ese nombre</Comentario>
          <div className="acciones">
            <Link className="boton compacto" to="/nutricion/alimento/nuevo">
              <span aria-hidden="true">[ </span>CREAR UN ALIMENTO<span aria-hidden="true"> ]</span>
            </Link>
          </div>
        </>
      )}

      {!elegido && encontrados && encontrados.length > 0 && (
        <ul className="lista-chips">
          {encontrados.map((alimento) => (
            <li key={alimento.id} className="chip-comida">
              <button
                type="button"
                className="chip-principal"
                aria-label={`${alimento.name}, ${Math.round(alimento.calories_per_100g)} kilocalorías por 100 ${alimento.unit}`}
                onClick={() => { setElegido(alimento); setGramos(100); }}
              >
                <span className="nombre" aria-hidden="true">{alimento.name}</span>
                <span className="kcal" aria-hidden="true">
                  {Math.round(alimento.calories_per_100g)} kcal/100 {alimento.unit}
                </span>
              </button>
            </li>
          ))}
        </ul>
      )}

      {elegido && (
        <>
          <Comentario>{elegido.name}</Comentario>
          <Campo
            etiqueta="Cantidad en gramos"
            name="gramos"
            type="number"
            inputMode="numeric"
            min="1"
            max="5000"
            value={gramos}
            onChange={(e) => setGramos(Number(e.target.value))}
          />
          <p className="xp">{Math.round(macrosPara(elegido, gramos).calories)} kcal</p>
          <FilaMacros macros={macrosPara(elegido, gramos)} />
          <Boton
            type="button"
            disabled={guardando || !fecha || gramos < 1 || gramos > 5000}
            onClick={() => void registrar(
              { id: elegido.id, nombre: elegido.name, gramos, tipo, kcal100: elegido.calories_per_100g },
              gramos,
            )}
          >
            AÑADIR
          </Boton>
        </>
      )}
      </>
      )}

      <div className="acciones">
        <Boton type="button" compacto onClick={() => setAMano((v) => !v)}>
          {aMano ? "VOLVER AL BUSCADOR" : "A MANO"}
        </Boton>
      </div>

      {aMano && (
        <>
          <Comentario decorativo>si no está en el catálogo</Comentario>
          <Campo etiqueta="Qué has comido" name="nombre" value={nombre}
                 onChange={(e) => setNombre(e.target.value)} />
          <Campo etiqueta="Calorías" name="kcal" type="number" inputMode="numeric" min="0"
                 value={kcal} onChange={(e) => setKcal(e.target.value)} />
          <Campo etiqueta="Proteínas en gramos" name="proteina" type="number"
                 inputMode="decimal" min="0" value={proteina}
                 onChange={(e) => setProteina(e.target.value)} />
          <Campo etiqueta="Hidratos en gramos" name="hidratos" type="number"
                 inputMode="decimal" min="0" value={hidratos}
                 onChange={(e) => setHidratos(e.target.value)} />
          <Campo etiqueta="Grasas en gramos" name="grasas" type="number"
                 inputMode="decimal" min="0" value={grasas}
                 onChange={(e) => setGrasas(e.target.value)} />
          <Boton
            type="button"
            disabled={guardando || !fecha || nombre.trim() === "" || kcal.trim() === ""}
            onClick={() => void registrarAMano()}
          >
            AÑADIR
          </Boton>
        </>
      )}
    </>
  );
}
