import { useEffect, useState } from "react";
import { useNavigate } from "react-router";
import { ErrorApi, crearReceta, subir, type CategoriaReceta } from "../api";
import { Aviso, Boton, Campo, Comentario, FotoElegible, TituloPantalla } from "../componentes";
import { encoger } from "../foto";

const CATEGORIAS: { clave: CategoriaReceta; etiqueta: string }[] = [
  { clave: "desayuno", etiqueta: "Desayuno" },
  { clave: "almuerzo", etiqueta: "Comida" },
  { clave: "cena", etiqueta: "Cena" },
  { clave: "snack", etiqueta: "Tentempié" },
];

type Ingrediente = { name: string; quantity: string };

export default function CrearReceta() {
  const navegar = useNavigate();
  const [nombre, setNombre] = useState("");
  const [categoria, setCategoria] = useState<CategoriaReceta>("almuerzo");
  const [kcal, setKcal] = useState("");
  const [raciones, setRaciones] = useState("1");
  const [instrucciones, setInstrucciones] = useState("");
  const [ingredientes, setIngredientes] = useState<Ingrediente[]>([]);
  const [foto, setFoto] = useState<File | null>(null);
  const [vistaPrevia, setVistaPrevia] = useState<string | null>(null);
  const [guardando, setGuardando] = useState(false);
  const [fallo, setFallo] = useState<string | null>(null);

  useEffect(() => {
    if (!foto) return setVistaPrevia(null);
    const url = URL.createObjectURL(foto);
    setVistaPrevia(url);
    return () => URL.revokeObjectURL(url);
  }, [foto]);

  const numero = (texto: string, porDefecto = 0) => {
    const n = Number(texto);
    return Number.isFinite(n) && n >= 0 ? n : porDefecto;
  };

  async function guardar() {
    setGuardando(true);
    setFallo(null);
    try {
      const creada = await crearReceta({
        name: nombre.trim(),
        category: categoria,
        calories_per_serving: numero(kcal),
        servings: numero(raciones, 1),
        ingredients: ingredientes.filter((i) => i.name.trim() !== ""),
        instructions: instrucciones.trim(),
      });

      if (foto) {
        try {
          await subir(`/recipes/${creada.id}/image`, "image", await encoger(foto));
        } catch {
          // La receta ya está guardada. Se dice tal cual y no se deshace nada.
          setFallo("La receta se ha guardado, pero la foto no se ha podido subir.");
          setGuardando(false);
          return;
        }
      }

      navegar("/nutricion/recetas");
    } catch (error) {
      setFallo(
        error instanceof ErrorApi && error.fallo.general
          ? error.fallo.general
          : "No hemos podido guardarla. Inténtalo otra vez.",
      );
      setGuardando(false);
    }
  }

  return (
    <>
      <TituloPantalla pantalla="nueva receta" />

      {/* El servidor guarda toda receta de usuario con is_system = true, o sea visible
          para el resto de personas de la instancia. El arreglo queda fuera de esta fase,
          pero callarlo sería peor que el propio fallo. */}
      <Aviso tono="ambar">Tus recetas las verá el resto de personas que usan S-RANK.</Aviso>

      {fallo && <p className="aviso" role="alert">{fallo}</p>}

      <Campo etiqueta="Nombre" name="nombre" value={nombre}
             onChange={(e) => setNombre(e.target.value)} />

      <div className="acciones">
        {CATEGORIAS.map((c) => (
          <Boton key={c.clave} type="button" compacto aria-pressed={categoria === c.clave}
                 aria-label={c.etiqueta} onClick={() => setCategoria(c.clave)}>
            {c.etiqueta.toUpperCase()}
          </Boton>
        ))}
      </div>

      <Campo etiqueta="Calorías por ración" name="kcal" type="number" inputMode="numeric"
             min="0" value={kcal} onChange={(e) => setKcal(e.target.value)} />
      <Campo etiqueta="Raciones" name="raciones" type="number" inputMode="numeric" min="1"
             value={raciones} onChange={(e) => setRaciones(e.target.value)} />

      <Comentario decorativo>ingredientes</Comentario>
      {ingredientes.map((ing, i) => (
        <div key={i} className="acciones">
          <Campo etiqueta={`Ingrediente ${i + 1}`} name={`ing-${i}`} value={ing.name}
                 onChange={(e) => setIngredientes(
                   ingredientes.map((v, j) => (j === i ? { ...v, name: e.target.value } : v)),
                 )} />
          <Campo etiqueta={`Cantidad del ingrediente ${i + 1}`} name={`cant-${i}`} value={ing.quantity}
                 onChange={(e) => setIngredientes(
                   ingredientes.map((v, j) => (j === i ? { ...v, quantity: e.target.value } : v)),
                 )} />
          <Boton type="button" compacto aria-label={`Quitar el ingrediente ${i + 1}`}
                 onClick={() => setIngredientes(ingredientes.filter((_, j) => j !== i))}>
            QUITAR
          </Boton>
        </div>
      ))}
      <Boton type="button" compacto
             onClick={() => setIngredientes([...ingredientes, { name: "", quantity: "" }])}>
        AÑADIR INGREDIENTE
      </Boton>

      <Campo etiqueta="Cómo se hace" name="instrucciones" value={instrucciones}
             onChange={(e) => setInstrucciones(e.target.value)} />

      <FotoElegible vistaPrevia={vistaPrevia} disabled={guardando}
                    onElegir={setFoto} onQuitar={() => setFoto(null)} />

      <Boton type="button"
             disabled={guardando || nombre.trim() === "" || kcal.trim() === ""}
             onClick={() => void guardar()}>
        {guardando ? "GUARDANDO…" : "GUARDAR"}
      </Boton>
    </>
  );
}
