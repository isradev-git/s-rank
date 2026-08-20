import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router";
import {
  ErrorApi, TIPOS_COMIDA, borrarComida, comidasDelDia, diaDeHoy,
  objetivoNutricional, type DiaDeComidas,
} from "../api";
import { Boton, Comentario, FilaMacros, Seccion, TituloPantalla } from "../componentes";
import { textoRestante } from "../formato";

export default function Nutricion() {
  const [dia, setDia] = useState<DiaDeComidas | null>(null);
  // null = todavía no se sabe; number = el objetivo guardado. La sugerencia que manda el
  // servidor cuando has_goal es false NO se usa: enseñarla sería darle al usuario un
  // objetivo que él no ha decidido.
  const [objetivo, setObjetivo] = useState<number | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);
  const [cargando, setCargando] = useState(true);

  const cargar = useCallback(async () => {
    setCargando(true);
    try {
      // La fecha la decide el servidor en Europe/Madrid. Usar la del navegador diría
      // «hoy» sobre un día distinto del que puntúa.
      const hoy = await diaDeHoy();
      const [comidas, meta] = await Promise.all([
        comidasDelDia(hoy.date),
        objetivoNutricional(),
      ]);
      setDia(comidas);
      setObjetivo(meta.has_goal ? meta.goal.daily_calories : null);
      setFallo(null);
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido conectar. Inténtalo otra vez.",
      );
    } finally {
      setCargando(false);
    }
  }, []);

  useEffect(() => { void cargar(); }, [cargar]);

  async function quitar(uuid: string) {
    try {
      await borrarComida(uuid);
      await cargar();
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido quitarla. Inténtalo otra vez.",
      );
    }
  }

  if (!dia) {
    return (
      <>
        <TituloPantalla pantalla="nutrición" />
        {cargando ? (
          <Comentario>cargando…</Comentario>
        ) : (
          <>
            <p className="aviso" role="alert">{fallo}</p>
            <Boton type="button" onClick={() => void cargar()}>REINTENTAR</Boton>
          </>
        )}
      </>
    );
  }

  return (
    <>
      <TituloPantalla pantalla="nutrición" />

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <p className="xp">{Math.round(dia.totals.calories)} kcal</p>
      <Comentario>{textoRestante(dia.totals.calories, objetivo)}</Comentario>
      <FilaMacros macros={dia.totals} />

      {objetivo === null && (
        <div className="acciones">
          <Link className="boton compacto" to="/nutricion/objetivo">
            <span aria-hidden="true">[ </span>CALCULAR MI OBJETIVO<span aria-hidden="true"> ]</span>
          </Link>
        </div>
      )}

      {TIPOS_COMIDA.map(({ clave, etiqueta }) => {
        const comida = dia.meals[clave];
        return (
          <Seccion
            key={clave}
            titulo={etiqueta}
            resumen={comida.items.length === 0 ? "sin registrar" : `${Math.round(comida.calories)} kcal`}
          >
            <ul className="lista-entradas">
              {comida.items.map((entrada) => {
                const nombre = entrada.food_item?.name ?? entrada.custom_food_name ?? "Sin nombre";
                return (
                  <li key={entrada.uuid}>
                    <span className="nombre">{nombre}</span>
                    <span className="cantidad">{Math.round(entrada.quantity_grams)} g</span>
                    <span className="kcal">{Math.round(entrada.calories)} kcal</span>
                    <button
                      type="button"
                      className="quitar"
                      // Un botón y no un gesto de deslizar: deslizar no se ve, no se puede
                      // teclear y ningún lector de pantalla lo anuncia.
                      aria-label={`Quitar ${nombre}`}
                      onClick={() => void quitar(entrada.uuid)}
                    >
                      <span aria-hidden="true">[ QUITAR ]</span>
                    </button>
                  </li>
                );
              })}
            </ul>

            <div className="acciones">
              <Link className="boton compacto" to={`/nutricion/anadir?tipo=${clave}`}>
                <span aria-hidden="true">[ </span>+ {etiqueta.toUpperCase()}
                <span aria-hidden="true"> ]</span>
              </Link>
            </div>
          </Seccion>
        );
      })}

      <div className="acciones">
        <Link className="boton compacto" to="/nutricion/recetas">
          <span aria-hidden="true">[ </span>RECETAS<span aria-hidden="true"> ]</span>
        </Link>
        <Link className="boton compacto" to="/nutricion/objetivo">
          <span aria-hidden="true">[ </span>MI OBJETIVO<span aria-hidden="true"> ]</span>
        </Link>
      </div>
    </>
  );
}
