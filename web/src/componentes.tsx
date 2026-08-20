/* Los componentes del sistema de diseño, todos en un fichero porque son pequeños.
   La regla que los sostiene: nunca se alinea con espacios, el hueco lo abre flex.
   Y la decoración —el `$`, los `[✓]`, las flechas— va marcada aria-hidden para que
   el lector de pantalla no la lea. Spec §5.1. */

import { useEffect, useRef } from "react";
import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode } from "react";
import type { BloqueSistema, Mision, SerieAnterior } from "./api";
import type { Modo, Serie } from "./borrador";
import type { Reciente } from "./recientes";
import { BLOQUES, bloquesEncendidos, textoRecord } from "./formato";
import type { Macros } from "./formato";

// A diferencia del NumberFormat de Java, Intl.NumberFormat sí se puede compartir:
// no guarda estado entre llamadas, y construirlo cada vez es caro.
//
// `useGrouping: "always"` no sobra. En español los números de cuatro cifras van sin
// separador, así que por defecto esto daría «5240», mientras la etiqueta que manda el
// servidor dice «8.000 pasos». Las dos formas juntas en la misma fila parecen un fallo.
const NUMEROS = new Intl.NumberFormat("es-ES", { useGrouping: "always" });

/** `israel[E]@s-rank $ hoy`.
 *
 *  Todo lo que hay antes del nombre de la pantalla es dibujo: va aria-hidden y el lector
 *  de pantalla solo oye «hoy». Nadie tiene que saber qué es un prompt para usar la app;
 *  quien lo reconozca, lo disfruta. El rango entre corchetes ocupa el sitio que en un
 *  prompt de verdad ocuparía el nombre del equipo. */
export function TituloPantalla({
  pantalla,
  usuario,
  rango,
}: {
  pantalla: string;
  usuario?: string;
  rango?: string;
}) {
  // «Israel Zamora» en un prompt queda raro y además parte la línea. Se usa el primer
  // nombre en minúscula, como el usuario de un terminal de verdad.
  const nombre = usuario?.trim().split(" ")[0]?.toLowerCase() || "invitado";

  return (
    <h1 className="titulo-pantalla">
      <span aria-hidden="true">
        <span className="usuario">
          {nombre}
          {rango && `[${rango}]`}
        </span>
        <span className="host">@s-rank</span>
        <span className="prompt"> $ </span>
      </span>
      {pantalla}
    </h1>
  );
}

/** `// texto`. El marcador va en apagado; el contenido, en texto, porque casi siempre
    lleva un dato y `apagado` (2,7:1) nunca puede llevar la única copia de algo. */
export function Comentario({
  children,
  decorativo = false,
}: {
  children: ReactNode;
  decorativo?: boolean;
}) {
  return (
    <p className="comentario">
      <span aria-hidden="true">// </span>
      <span className={decorativo ? undefined : "dato"}>{children}</span>
    </p>
  );
}

/** Diez bloques en línea propia. Se oyen como un porcentaje, no como veinte caracteres. */
export function BarraBloques({ porcentaje }: { porcentaje: number }) {
  const llenos = bloquesEncendidos(porcentaje);
  const pct = llenos * (100 / BLOQUES);
  return (
    <p
      className="barra-bloques"
      role="progressbar"
      aria-label="Progreso hacia el siguiente nivel"
      aria-valuenow={pct}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuetext={`${pct}%`}
    >
      <span aria-hidden="true">
        [{"▓".repeat(llenos)}
        <span className="apagados">{"░".repeat(BLOQUES - llenos)}</span>]
      </span>
    </p>
  );
}

export function InsigniaRango({ rango }: { rango: string }) {
  return <span className="insignia-rango">rango {rango}</span>;
}

/** `[✓] Beber 2 litros de agua`. Se oye «Beber 2 litros de agua, hecha». */
export function FilaMision({ mision }: { mision: Mision }) {
  const avance =
    !mision.completed && mision.progress != null && mision.target != null
      ? `${NUMEROS.format(mision.progress)} de ${NUMEROS.format(mision.target)}`
      : null;

  return (
    <li className={mision.completed ? "fila-mision hecha" : "fila-mision"}>
      <span className="marca" aria-hidden="true">
        [{mision.completed ? "✓" : " "}]
      </span>
      <span>
        {mision.label}
        <span className="solo-lectores">
          {mision.completed ? ", hecha" : ", pendiente"}
        </span>
        {avance && (
          <span className="avance">
            <Comentario>{avance}</Comentario>
          </span>
        )}
      </span>
    </li>
  );
}

/** Sección plegable. Es un <details> nativo: el navegador ya sabe plegar y ya anuncia
    «desplegado» o «plegado», así que aquí no hace falta ni estado ni JavaScript. */
export function Seccion({
  titulo,
  resumen,
  children,
}: {
  titulo: string;
  /** Sin corchetes: los pone el componente, y van aria-hidden. */
  resumen: string;
  children: ReactNode;
}) {
  return (
    <details className="seccion" open>
      <summary>
        <span className="flecha inicial" aria-hidden="true" />
        <span className="titulo-seccion">{titulo}</span>
        <span className="resumen">
          <span aria-hidden="true">[</span>
          {resumen}
          <span aria-hidden="true">]</span>
        </span>
        <span className="flecha final" aria-hidden="true" />
      </summary>
      {children}
    </details>
  );
}

export function Boton({
  children,
  compacto = false,
  ...resto
}: {
  children: ReactNode;
  /** Para las barras de acciones, donde caben varios en una fila. */
  compacto?: boolean;
} & ButtonHTMLAttributes<HTMLButtonElement>) {
  // Los corchetes van aria-hidden o el nombre accesible del botón pasa a ser
  // «corchete ENTRAR corchete». Son dibujo, como todo lo demás.
  //
  // El spread va detrás de className a propósito: una className que llegara por fuera
  // machacaría la clase «boton» entera en vez de sumarse. Por eso la variante compacta
  // es una propiedad y no una clase que alguien pase desde fuera.
  return (
    <button className={compacto ? "boton compacto" : "boton"} {...resto}>
      <span aria-hidden="true">[ </span>
      {children}
      <span aria-hidden="true"> ]</span>
    </button>
  );
}

export function Campo({
  etiqueta,
  error,
  ...resto
}: { etiqueta: string; error?: string } & InputHTMLAttributes<HTMLInputElement>) {
  const idError = error ? `${resto.name}-error` : undefined;
  return (
    <label className="campo">
      <span>{etiqueta}</span>
      <input
        {...resto}
        aria-invalid={error ? true : undefined}
        aria-describedby={idError}
      />
      {error && (
        <span className="error-campo" id={idError}>
          {error}
        </span>
      )}
    </label>
  );
}

/** Un aviso con dos urgencias.
 *
 *  `rojo` es una pérdida de datos en curso y va como `alert`: el lector de pantalla
 *  interrumpe lo que esté diciendo. `ambar` es informativo y va como `status`, que espera
 *  al turno. Usar `alert` para todo enseña a ignorarlo. */
export function Aviso({
  tono,
  children,
}: {
  tono: "rojo" | "ambar";
  children: ReactNode;
}) {
  return (
    <p className={`aviso ${tono}`} role={tono === "rojo" ? "alert" : "status"}>
      {children}
    </p>
  );
}

/** La ventana del Sistema. Spec §6.7: aparece sola y en exactamente cuatro momentos
 *  —subir de nivel, subir de rango, desbloquear un logro y batir un récord—. Si saltara
 *  por cualquier otra cosa dejaría de significar algo, y el cian dejaría de ser un premio.
 *
 *  Ganar XP a secas **no** la abre: pasa en todos los entrenos. */
export function VentanaSistema({
  sistema,
  alCerrar,
}: {
  sistema: BloqueSistema;
  alCerrar: () => void;
}) {
  const ventana = useRef<HTMLDivElement>(null);

  // Una ventana que se declara `aria-modal` tiene que cumplirlo. Sin esto, quien navega
  // con teclado se queda con el foco detrás de ella y tiene que tabular a ciegas hasta
  // tropezar con CERRAR, y quien usa lector de pantalla no oye que se haya abierto nada:
  // la única recompensa de toda la aplicación pasaría muda.
  //
  // No se usa `<dialog>` con `showModal()`, que haría todo esto solo, porque jsdom no lo
  // implementa y dejaría este comportamiento sin ningún test. Se revisa si algún día lo
  // implementa: sería borrar este efecto entero.
  useEffect(() => {
    if (!ventana.current) return;
    const anterior = document.activeElement as HTMLElement | null;
    ventana.current.querySelector<HTMLElement>("button")?.focus();
    return () => anterior?.focus();
  }, []);

  const motivos = [
    sistema.level_up && `Nivel ${sistema.level_up.to}`,
    sistema.rank_up && `Rango ${sistema.rank_up.to}`,
    ...sistema.achievements_unlocked.map((logro) => logro.name),
    ...sistema.records.map(textoRecord),
  ].filter((texto): texto is string => !!texto);

  if (motivos.length === 0) return null;

  return (
    <div
      ref={ventana}
      className="ventana-sistema"
      role="dialog"
      aria-modal="true"
      aria-label="El Sistema"
      onKeyDown={(evento) => {
        if (evento.key === "Escape") alCerrar();
        // Dentro solo hay un botón, así que tabular solo puede sacar de una ventana que
        // dice ser modal. El foco se queda aquí hasta que se cierre.
        if (evento.key === "Tab") evento.preventDefault();
      }}
    >
      {/* Las esquinas en ángulo son dibujo de terminal, como los corchetes. */}
      <span className="angulo superior" aria-hidden="true" />

      <p className="titulo-ventana">EL SISTEMA</p>
      <ul className="motivos">
        {motivos.map((texto) => (
          <li key={texto}>{texto}</li>
        ))}
      </ul>

      <span className="angulo inferior" aria-hidden="true" />

      <Boton type="button" onClick={alCerrar}>
        CERRAR
      </Boton>
    </div>
  );
}

/** Un número que puede no estar. Vaciar el campo devuelve null y no 0: un cero es un dato
 *  —«he levantado 0 kg»— y no haberlo apuntado es otra cosa. De esa diferencia depende que
 *  la serie se considere vacía y no se mande. */
function aNumero(texto: string): number | null {
  if (texto.trim() === "") return null;
  const n = Number(texto);
  return Number.isFinite(n) ? n : null;
}

/** Una fila de la tabla de series. Se usa de pie, con una mano, con la pantalla sudada y
 *  con prisa entre series: los objetivos táctiles son de 48 px y no hay ningún gesto, solo
 *  pulsar y teclear. Fuerza pide kilos, repeticiones y esfuerzo; natación pide distancia,
 *  tiempo y estilo, no kilos. */
export function FilaSerie({
  numero,
  serie,
  modo,
  anterior,
  alCambiar,
  alMarcar,
}: {
  numero: number;
  serie: Serie;
  modo: Modo;
  /** Lo que se apuntó la última vez en esta misma serie, si se sabe. */
  anterior: SerieAnterior | null;
  alCambiar: (campo: keyof Serie, valor: number | string | null) => void;
  alMarcar: () => void;
}) {
  // En calistenia el peso es el lastre que se cuelga, no el del cuerpo.
  const etiquetaPeso = modo === "calisthenics" ? "Lastre" : "Peso";

  const numerico = (
    campo: keyof Serie,
    etiqueta: string,
    paso: "0.5" | "1",
  ) => (
    <td>
      <input
        type="number"
        step={paso}
        min="0"
        // El teclado numérico del móvil sale solo. Decimal donde hay medios kilos y
        // medios metros; entero donde no tiene sentido una coma.
        inputMode={paso === "0.5" ? "decimal" : "numeric"}
        aria-label={`${etiqueta}, serie ${numero}`}
        value={(serie[campo] as number | null) ?? ""}
        onChange={(e) => alCambiar(campo, aNumero(e.target.value))}
      />
    </td>
  );

  return (
    <tr className={serie.hecha ? "fila-serie hecha" : "fila-serie"}>
      <td className="numero" aria-hidden="true">
        {numero}
      </td>

      {modo === "swimming" ? (
        <>
          {numerico("distance_m", "Distancia en metros", "0.5")}
          {numerico("time_seconds", "Tiempo en segundos", "1")}
          <td>
            <input
              type="text"
              aria-label={`Estilo, serie ${numero}`}
              value={serie.style ?? ""}
              onChange={(e) => alCambiar("style", e.target.value || null)}
            />
          </td>
        </>
      ) : (
        <>
          <td className="anterior" aria-hidden="true">
            {anterior?.weight_kg != null && anterior.reps != null
              ? `${anterior.weight_kg}×${anterior.reps}`
              : ""}
          </td>
          {numerico("weight_kg", `${etiquetaPeso} en kilos`, "0.5")}
          {numerico("reps", "Repeticiones", "1")}
          {numerico("rpe", "Esfuerzo del 1 al 10", "1")}
        </>
      )}

      <td>
        <button
          type="button"
          className="marca-serie"
          // El `[✓]` es dibujo: lo que se oye es el estado, con estas palabras.
          aria-label={`Serie ${numero}, ${serie.hecha ? "hecha" : "pendiente"}`}
          aria-pressed={serie.hecha}
          onClick={alMarcar}
        >
          <span aria-hidden="true">[{serie.hecha ? "✓" : " "}]</span>
        </button>
      </td>
    </tr>
  );
}

/** `[✓] Multivitaminas`. Es un botón con `aria-pressed` y no un `<input type=checkbox>`
 *  porque no vive dentro de ningún formulario: cada toque es una petición, no un campo que
 *  se envía luego. Lo que se oye es «Multivitaminas, tomado». */
export function Casilla({
  etiqueta,
  marcada,
  onCambiar,
  disabled,
}: {
  etiqueta: string;
  marcada: boolean;
  onCambiar: (marcada: boolean) => void;
  disabled?: boolean;
}) {
  return (
    <button
      type="button"
      className={marcada ? "casilla marcada" : "casilla"}
      aria-pressed={marcada}
      aria-label={`${etiqueta}, ${marcada ? "tomado" : "sin tomar"}`}
      disabled={disabled}
      onClick={() => onCambiar(!marcada)}
    >
      <span className="marca" aria-hidden="true">[{marcada ? "✓" : " "}]</span>
      <span aria-hidden="true">{etiqueta}</span>
    </button>
  );
}

/** Los tres macros en fila, con las palabras enteras. Nada de «P/H/G» ni de abreviaturas
 *  de tabla nutricional: hay quien no ha visto una en su vida. La fibra y el azúcar se
 *  registran pero no se pintan aquí — no hay misión de fibra y la fila se vuelve ilegible
 *  con cinco columnas en un móvil. */
export function FilaMacros({ macros }: { macros: Macros }) {
  const columnas = [
    { etiqueta: "Proteínas", valor: macros.protein },
    { etiqueta: "Hidratos", valor: macros.carbs },
    { etiqueta: "Grasas", valor: macros.fat },
  ];

  return (
    <ul className="fila-macros">
      {columnas.map((c) => (
        <li key={c.etiqueta}>
          <span className="valor">{NUMEROS.format(Math.round(c.valor))} g</span>
          <span className="etiqueta-macro">{c.etiqueta}</span>
        </li>
      ))}
    </ul>
  );
}

/** «Lo de siempre». Pulsar el chip registra con la cantidad de la última vez; el segundo
 *  botón abre la cantidad. Dos botones y no un mantener pulsado: un gesto largo no se ve,
 *  no tiene equivalente de teclado y en el móvil pelea con el menú contextual. */
export function ChipComida({
  reciente,
  kcal,
  onRegistrar,
  onAjustar,
  disabled,
}: {
  reciente: Reciente;
  kcal: number;
  onRegistrar: () => void;
  onAjustar: () => void;
  disabled?: boolean;
}) {
  return (
    <li className="chip-comida">
      <button
        type="button"
        className="chip-principal"
        // «kilocalorías» entero: «kcal» leído por un lector de pantalla suena a nada.
        aria-label={`${reciente.nombre}, ${reciente.gramos} g, ${Math.round(kcal)} kilocalorías`}
        disabled={disabled}
        onClick={onRegistrar}
      >
        <span aria-hidden="true">[ </span>
        <span className="nombre" aria-hidden="true">{reciente.nombre}</span>
        <span className="cantidad" aria-hidden="true">{reciente.gramos} g</span>
        <span className="kcal" aria-hidden="true">{NUMEROS.format(Math.round(kcal))} kcal</span>
        <span aria-hidden="true"> ]</span>
      </button>
      <button
        type="button"
        className="chip-ajustar"
        // El texto suelto «CAMBIAR» no dice de qué alimento habla.
        aria-label={`Cambiar la cantidad de ${reciente.nombre}`}
        disabled={disabled}
        onClick={onAjustar}
      >
        <span aria-hidden="true">[ CAMBIAR ]</span>
      </button>
    </li>
  );
}
