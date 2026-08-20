import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router";
import {
  COMIDA_DE_CATEGORIA, ErrorApi, diaDeHoy, receta as pedirReceta, recetas,
  registrarComida, type CategoriaReceta, type Receta as TipoReceta,
} from "../api";
import { Boton, Comentario, FilaMacros, Seccion, TituloPantalla } from "../componentes";

const CATEGORIAS: { clave: CategoriaReceta; etiqueta: string }[] = [
  { clave: "desayuno", etiqueta: "Desayuno" },
  { clave: "almuerzo", etiqueta: "Comida" },
  { clave: "cena", etiqueta: "Cena" },
  { clave: "snack", etiqueta: "Tentempié" },
];

export default function Recetas() {
  const [lista, setLista] = useState<TipoReceta[] | null>(null);
  const [categoria, setCategoria] = useState<CategoriaReceta | null>(null);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    // El filtro va al servidor: filtrar aquí obligaría a bajarse el catálogo entero.
    recetas(categoria ? { category: categoria } : {})
      .then(setLista)
      .catch(() => setFallo("No hemos podido cargar las recetas. Comprueba la conexión."));
  }, [categoria]);

  return (
    <>
      <TituloPantalla pantalla="recetas" />
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <div className="acciones">
        {CATEGORIAS.map((c) => (
          <Boton
            key={c.clave}
            type="button"
            compacto
            aria-pressed={categoria === c.clave}
            aria-label={c.etiqueta}
            onClick={() => setCategoria(categoria === c.clave ? null : c.clave)}
          >
            {c.etiqueta.toUpperCase()}
          </Boton>
        ))}
      </div>

      {lista === null ? (
        <Comentario>cargando…</Comentario>
      ) : lista.length === 0 ? (
        <Comentario>no hay recetas de esa clase todavía</Comentario>
      ) : (
        <ul className="lista-entradas">
          {lista.map((r) => (
            <li key={r.id}>
              <Link className="nombre" to={`/nutricion/recetas/${r.id}`}>{r.name}</Link>
              <span className="kcal">{Math.round(r.calories_per_serving)} kcal</span>
            </li>
          ))}
        </ul>
      )}

      <div className="acciones">
        <Link className="boton compacto" to="/nutricion/recetas/nueva">
          <span aria-hidden="true">[ </span>NUEVA RECETA<span aria-hidden="true"> ]</span>
        </Link>
      </div>
    </>
  );
}

export function Receta() {
  const { id } = useParams();
  const navegar = useNavigate();
  const [datos, setDatos] = useState<TipoReceta | null>(null);
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    pedirReceta(Number(id))
      .then(setDatos)
      .catch(() => setFallo("No hemos podido cargar la receta."));
  }, [id]);

  async function registrar() {
    if (!datos) return;
    setGuardando(true);
    setFallo(null);
    try {
      const hoy = await diaDeHoy();
      // No hay endpoint que registre una receta como comida: es una entrada manual con el
      // nombre de la receta y sus macros por ración. Funciona con la API tal y como está.
      await registrarComida({
        date: hoy.date,
        meal_type: COMIDA_DE_CATEGORIA[datos.category],
        custom_food_name: datos.name,
        calories: datos.calories_per_serving,
        protein: datos.protein_per_serving,
        carbs: datos.carbs_per_serving,
        fat: datos.fat_per_serving,
      });
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

  if (!datos) {
    return (
      <>
        <TituloPantalla pantalla="receta" />
        {fallo ? <p className="aviso" role="alert">{fallo}</p> : <Comentario>cargando…</Comentario>}
      </>
    );
  }

  const etiqueta = CATEGORIAS.find((c) => c.clave === datos.category)!.etiqueta;

  return (
    <>
      <TituloPantalla pantalla="receta" />
      <Comentario>{datos.name}</Comentario>
      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      {datos.image_path && (
        <img className="vista-previa" src={`/uploads/${datos.image_path}`} alt={datos.name} />
      )}

      <p className="xp">{Math.round(datos.calories_per_serving)} kcal por ración</p>
      <FilaMacros macros={{
        calories: datos.calories_per_serving, protein: datos.protein_per_serving,
        carbs: datos.carbs_per_serving, fat: datos.fat_per_serving,
        fiber: datos.fiber_per_serving, sugar: 0,
      }} />
      <Comentario>
        {datos.servings} raciones · {datos.prep_time_min + datos.cook_time_min} minutos · {datos.difficulty}
      </Comentario>

      <Seccion titulo="Ingredientes" resumen={`${datos.ingredients?.length ?? 0}`}>
        <ul className="lista-entradas">
          {(datos.ingredients ?? []).map((i) => (
            <li key={`${i.name}-${i.quantity}`}>
              <span className="nombre">{i.name}</span>
              <span className="cantidad">{i.quantity}</span>
            </li>
          ))}
        </ul>
      </Seccion>

      <Seccion titulo="Cómo se hace" resumen="pasos">
        <p className="instrucciones">{datos.instructions}</p>
      </Seccion>

      <Boton type="button" disabled={guardando} onClick={() => void registrar()}>
        {guardando ? "REGISTRANDO…" : `REGISTRAR COMO ${etiqueta.toUpperCase()}`}
      </Boton>
    </>
  );
}
