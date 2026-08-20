import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { ErrorApi, crearAlimento, subir } from "../api";
import { Boton, Campo, Comentario, FotoElegible, TituloPantalla } from "../componentes";
import { encoger } from "../foto";

/** Los seis macros que acepta el servidor, con su etiqueta. Solo las calorías son
 *  obligatorias; lo demás se queda en cero si no se sabe, que es mejor que obligar a
 *  inventárselo. */
const MACROS = [
  { campo: "calories_per_100g", etiqueta: "Calorías", obligatorio: true },
  { campo: "protein_per_100g", etiqueta: "Proteínas", obligatorio: false },
  { campo: "carbs_per_100g", etiqueta: "Hidratos", obligatorio: false },
  { campo: "fat_per_100g", etiqueta: "Grasas", obligatorio: false },
  { campo: "fiber_per_100g", etiqueta: "Fibra", obligatorio: false },
  { campo: "sugar_per_100g", etiqueta: "Azúcares", obligatorio: false },
] as const;

export default function CrearAlimento() {
  const navegar = useNavigate();
  const [nombre, setNombre] = useState("");
  const [marca, setMarca] = useState("");
  const [unidad, setUnidad] = useState<"g" | "ml">("g");
  const [valores, setValores] = useState<Record<string, string>>({});
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);
  const [foto, setFoto] = useState<File | null>(null);
  const [vistaPrevia, setVistaPrevia] = useState<string | null>(null);

  // Un objeto de URL se queda en memoria hasta que alguien lo suelta.
  useEffect(() => {
    if (!foto) return setVistaPrevia(null);
    const url = URL.createObjectURL(foto);
    setVistaPrevia(url);
    return () => URL.revokeObjectURL(url);
  }, [foto]);

  const numero = (campo: string) => {
    const n = Number(valores[campo] ?? "");
    return Number.isFinite(n) && n >= 0 ? n : 0;
  };

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      // ⚠️ Sin `from_ingredients`. Ese campo hace que el alimento nazca en el catálogo
      // global, con user_id a null y visible para todo el mundo. Aquí siempre es personal.
      const creado = await crearAlimento({
        name: nombre.trim(),
        brand: marca.trim() || null,
        category: null,
        unit: unidad,
        calories_per_100g: numero("calories_per_100g"),
        protein_per_100g: numero("protein_per_100g"),
        carbs_per_100g: numero("carbs_per_100g"),
        fat_per_100g: numero("fat_per_100g"),
        fiber_per_100g: numero("fiber_per_100g"),
        sugar_per_100g: numero("sugar_per_100g"),
      });

      // La foto va después y en su propia petición: el endpoint de crear no la acepta.
      // Si falla, el alimento ya está guardado — se avisa, pero no se deshace nada.
      if (foto) {
        try {
          await subir(`/foods/${creado.food.id}/image`, "image", await encoger(foto));
        } catch {
          setFallo("El alimento se ha guardado, pero la foto no se ha podido subir.");
          setGuardando(false);
          return;
        }
      }

      navegar("/nutricion");
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido guardarlo. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }

  const listo = nombre.trim() !== "" && (valores.calories_per_100g ?? "").trim() !== "";

  return (
    <>
      <TituloPantalla pantalla="crear alimento" />
      <Comentario>para lo que no está en el catálogo</Comentario>

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Nombre" name="nombre" value={nombre}
             onChange={(e) => setNombre(e.target.value)} />
      <Campo etiqueta="Marca, si la tiene" name="marca" value={marca}
             onChange={(e) => setMarca(e.target.value)} />

      <div className="acciones">
        <Boton
          type="button"
          compacto
          aria-pressed={unidad === "ml"}
          aria-label={unidad === "g" ? "Medir en mililitros" : "Medir en gramos"}
          onClick={() => setUnidad(unidad === "g" ? "ml" : "g")}
        >
          {unidad === "g" ? "MEDIR EN MILILITROS" : "MEDIR EN GRAMOS"}
        </Boton>
      </div>

      {MACROS.map(({ campo, etiqueta }) => (
        <Campo
          key={campo}
          etiqueta={`${etiqueta} por 100 ${unidad}`}
          name={campo}
          type="number"
          inputMode="decimal"
          min="0"
          value={valores[campo] ?? ""}
          onChange={(e) => setValores({ ...valores, [campo]: e.target.value })}
        />
      ))}

      <FotoElegible
        vistaPrevia={vistaPrevia}
        disabled={guardando}
        onElegir={setFoto}
        onQuitar={() => setFoto(null)}
      />

      <Boton type="button" disabled={!listo || guardando} onClick={() => void guardar()}>
        {guardando ? "GUARDANDO…" : "GUARDAR"}
      </Boton>
    </>
  );
}
