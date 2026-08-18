/* Los componentes del sistema de diseño, todos en un fichero porque son pequeños.
   La regla que los sostiene: nunca se alinea con espacios, el hueco lo abre flex.
   Y la decoración —el `$`, los `[✓]`, las flechas— va marcada aria-hidden para que
   el lector de pantalla no la lea. Spec §5.1. */

import type { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode } from "react";
import type { BloqueSistema, Mision } from "./api";
import { BLOQUES, bloquesEncendidos, textoRecord } from "./formato";

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
  const motivos = [
    sistema.level_up && `Nivel ${sistema.level_up.to}`,
    sistema.rank_up && `Rango ${sistema.rank_up.to}`,
    ...sistema.achievements_unlocked.map((logro) => logro.name),
    ...sistema.records.map(textoRecord),
  ].filter((texto): texto is string => !!texto);

  if (motivos.length === 0) return null;

  return (
    <div className="ventana-sistema" role="dialog" aria-modal="true" aria-label="El Sistema">
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
