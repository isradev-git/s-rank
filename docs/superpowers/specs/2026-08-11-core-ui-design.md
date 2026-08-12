# S-RANK — Sistema de diseño

**Fecha:** 2026-08-11 · revisado el 2026-08-12 al pasar el frontend a web
**Estado:** aprobado e implementado
**Concreta:** `2026-08-10-s-rank-design.md` §7 (sistema de diseño) y §4 (arquitectura)
**Fase:** 1.1 · Esqueleto web

> Este documento se escribió para Compose. **Las decisiones no han cambiado** —los once
> colores, sus contrastes medidos, la escala tipográfica, los ocho componentes y la regla
> de accesibilidad son las mismas—, solo la forma de construirlas. Lo implementado está en
> `web/src/estilos.css` y `web/src/componentes.tsx`.

---

## 1. Para qué existe este documento

El spec de la fase 1 fija los once colores, la escala tipográfica y la lista de
componentes. No dice **cómo se construyen**, y esa parte condiciona las cuatro fases
siguientes: cada pantalla de 1.2 a 1.5 se monta con lo que se escriba aquí.

Este documento cierra esas decisiones. No cambia ninguna del spec: las concreta.

### Lo que se hereda sin discutir

Once colores con su hexadecimal · escala de cinco tamaños · JetBrains Mono empaquetada ·
el cian reservado a las ventanas del Sistema · contraste 4,5:1 sobre el fondo · objetivos
táctiles de 48 px · la maquetación no depende de anchos fijos en caracteres.

### La restricción rectora, llevada hasta el final

> Si una pantalla solo se entiende sabiendo lo que es una terminal, está mal diseñada.

Su consecuencia menos evidente está en §5.1: **con un lector de pantalla, la decoración no
debe existir**. Si `[✓]` se lee en voz alta como «corchete, marca de verificación,
corchete», la estética ha dejado de ser puramente visual y se ha convertido en un
obstáculo.

---

## 2. Decisiones de esta sesión

| # | Decisión | Motivo |
|---|---|---|
| 1 | Pestañas **arriba**, fijas | Como las capturas de referencia. Fijas porque perderlas en un scroll largo es peor que gastar 48 px |
| 2 | Ámbar, verde y azul para el día a día; **rojo, morado y cian solo al recompensar** | El argumento que protege el cian vale igual para el récord y la rareza épica |
| 3 | **Sin iconos** en las filas | El mismo motivo por el que la fuente va empaquetada: cada fabricante dibuja los emoji a su manera |
| 4 | Se construyen **7 de los 9** componentes | La barra continua y el rombo de rareza no tienen quién los dispare en 1.1 |
| 5 | Barra de XP **como texto, en línea propia** | Cero código propio. En la misma línea que los números se desborda con la fuente al máximo |
| 6 | Tokens propios sobre esqueleto de Material 3 | Los once tokens no caben en los roles de M3, y un token que no se puede nombrar se usa mal |

---

## 3. Tema y tokens — `core/ui/theme/`

### 3.1 Por qué no `MaterialTheme.colorScheme`

Material 3 aporta lo aburrido y difícil: campos de texto, foco, teclado, selección,
`TabRow`, `Scaffold`. Se usa para eso.

Lo que no se usa es su vocabulario de color. `ColorScheme` tiene `primary`, `surface` y
`error`, y no tiene hueco para «apagado» ni para «cian del Sistema». Escribir
`MaterialTheme.colorScheme.tertiary` queriendo decir «cian» es exactamente cómo el cian
acabaría escapándose a la interfaz normal sin que nadie lo notara.

Los once tokens se llaman por su nombre del spec, en castellano y sin tildes en los
identificadores.

```css
/* web/src/estilos.css */
:root {
  /* Nunca negro puro: en OLED deja estela al desplazar y deslumbra bajo el texto.
     Lo vigila estilos.test.ts. */
  --fondo: #121216;
  --superficie: #1c1c21;
  --lineas: #2a2a30;
  --texto: #e4e4e7;
  --apagado: #595962;
  --ambar: #f59e0b;   /* marca, acción, XP */
  --verde: #4ade80;   /* completado */
  --azul: #60a5fa;    /* información y navegación */
  /* Los tres siguientes solo los toca el momento de recompensa. Si aparecen en una
     pantalla normal, el premio deja de significar nada. */
  --rojo: #f87171;    /* récord, alerta */
  --cian: #22d3ee;    /* EXCLUSIVO de las ventanas del Sistema */
  --morado: #a78bfa;  /* rareza épica */
}
```

En web no hace falta envolver nada ni pelearse con ningún tema por debajo: son variables
CSS y no hay una biblioteca de componentes aportando color propio. `color-scheme: dark`
le dice al navegador que pinte en oscuro sus propios controles.

### 3.2 Tipografía

JetBrains Mono en `core/ui/src/main/res/font/`. Nunca la monoespaciada del sistema.

| Nombre | Tamaño | Para qué |
|---|---|---|
| `titulo` | 20 sp | cabecera de pantalla |
| `seccion` | 16 sp | cabeceras de sección |
| `cuerpo` | 13 sp | contenido |
| `nota` | 11,5 sp | comentarios `//`, valores secundarios |
| `etiqueta` | 10,5 sp | versales |

Las versales se hacen con `text-transform: uppercase` y algo de `letter-spacing`. No hay
versales de verdad en la fuente y traer una segunda para fingirlas no compensa.

Todos los tamaños en `rem`, nunca en `px`: así respetan el tamaño de letra que el usuario
tenga puesto en el navegador. Es el equivalente exacto del `sp` de Android.

---

## 4. Cómo se reparte el código

Seis ficheros y una carpeta. Ni uno más.

| Fichero | Qué lleva |
|---|---|
| `src/estilos.css` | los once colores, la escala tipográfica y el aspecto de los componentes |
| `src/componentes.tsx` | los ocho componentes del sistema de diseño |
| `src/formato.ts` | los cálculos y textos con ramas — es lo que está probado |
| `src/api.ts` | la única puerta a la API: peticiones, tipos y traducción de errores |
| `src/App.tsx` | rutas, pestañas y el portero de sesión |
| `src/pantallas/` | una por pantalla |

**Todos los componentes en un fichero.** Son pequeños: ocho ficheros de veinte líneas
cuestan más de mantener que uno de ciento sesenta. Se separan cuando alguno crezca de
verdad, no antes.

⚠️ **La dirección de dependencias ya no la vigila nadie.** En Android era el grafo de
Gradle —`core/system` no podía declarar `feature/*`— y de eso se encargaba el compilador.
Aquí no hay módulos que lo impidan, y **no se monta un sistema de módulos falso para
fingirlo**. Con cinco pantallas basta la disciplina; cuando no baste, la herramienta es
`eslint-plugin-boundaries`.

«Hoy» monta secciones, y eso no cambia: es lo que permite que un módulo de la fase 2 gane
una sección sin tocar la navegación. En 1.1 la única sección son las misiones.

### `.gitignore`

Lo de Node lo trae `web/.gitignore`, que genera Vite: `node_modules`, `dist` y los
registros. **Sin tocar** las reglas de la raíz que protegen `*.sqlite`, `*.sql` y `.env*`.
Esas son las que evitan que se suban datos de salud y credenciales.

---

## 5. Los siete componentes

La regla que los sostiene a todos: **nunca se alinea con espacios**. `Row` con
`Spacer(Modifier.weight(1f))`. Es lo único que hace compatibles la estética de terminal y
la promesa de respetar el tamaño de fuente del sistema.

| Componente | Qué es | Quién lo usa en 1.1 |
|---|---|---|
| `FilaMision` | `[✓]`/`[ ]`, etiqueta y el avance parcial debajo | misiones de «hoy» |
| `CabeceraSeccion` | `▸ TÍTULO` · espacio · `[2 de 4] ▾`, plegable | «hoy» |
| `Comentario` | `// texto` | todas |
| `BarraBloques` | diez bloques, línea propia | barra de XP |
| `BotonSRank` | borde visible, 48 px mínimo | formularios |
| `InsigniaRango` | la letra del rango con su marco | cabecera de «hoy» |
| `VentanaSistema` | cian, esquinas en ángulo | solo `@Preview` |

### 5.1 Accesibilidad: la decoración no se lee

`Text("[✓] Beber 2 litros de agua")` lo lee TalkBack como «corchete, marca de
verificación, corchete». La fila entera va como un solo nodo semántico:

```jsx
<li className="fila-mision">
  <span className="marca" aria-hidden="true">[{hecha ? "✓" : " "}]</span>
  <span>
    {mision.label}
    <span className="solo-lectores">{hecha ? ", hecha" : ", pendiente"}</span>
  </span>
</li>
```

Un usuario ciego oye «Beber 2 litros de agua, hecha». Lo mismo con el prompt de las
cabeceras, que va entero en `aria-hidden`, y con el `▸`/`▾` de las secciones: al ser un
`<details>` nativo, el navegador ya anuncia «desplegado» o «plegado» sin que haya que
escribirlo.

Es la mitad de la regla rectora, y en HTML sale sin acrobacias: `aria-hidden` en el dibujo
y el estado dicho con palabras en un `<span>` que solo existe para el lector de pantalla.

### 5.2 `FilaMision`

El servidor manda cada misión sin icono y con avance parcial:

```json
{ "key": "water", "label": "Beber 2 litros de agua",
  "target": 2000, "progress": 1250, "xp_reward": 10,
  "is_optional": false, "completed": false }
```

Ocho claves fijas: `train`, `water`, `protein`, `weight`, `meals_3`, `supplements`,
`pushups_50`, `steps_8000`.

El avance parcial va debajo como comentario. Es de solo lectura en 1.1: las filas no son
objetivos táctiles y no necesitan los 48 px. Cuando la fase 1.2 traiga las opcionales
marcables a mano —hay un `POST /api/system/quests/{key}/complete` esperando— es añadir un
`onClick` nullable, no rediseñar.

### 5.3 `Comentario` y el límite del gris

`#595962` da **2,70:1** sobre el fondo. Está por debajo de 4,5:1 a propósito, y por eso el
spec §7 lo limita a «texto secundario nunca esencial». Subió de `#52525b` cuando el fondo
dejó de ser negro puro: el número es el mismo justamente porque es el que sostiene todo el
razonamiento de este apartado, y dejarlo caer a 2,4:1 lo habría vaciado en silencio.

De ahí sale una regla que no estaba escrita: **`apagado` nunca lleva la única copia de un
dato**. Un comentario decorativo puede ir entero en apagado; uno que lleva información
—`// 1.250 de 2.000 ml`— parte el color:

```
//  →  apagado      el marcador es decoración
texto →  texto       el dato se lee (14,7:1)
```

Sigue leyéndose como comentario: lo dicen el marcador y el tamaño `nota`, no el gris.

### 5.4 `BarraBloques`

Diez bloques como texto, en **línea propia**:

```
[▓▓▓▓▓▓░░░░]
240 / 400 XP
```

Compartir línea con los números da unos 24 caracteres, que con la fuente al máximo no
caben en un móvil de 320 px. Separadas, la barra ocupa unos 187 px de los 288 disponibles.

Avanza a saltos del 10 %. Para una estética de terminal se lee como intención, y al subir
de nivel los bloques se encienden de uno en uno.

⚠️ `▓` y `░` son U+2593 y U+2591. JetBrains Mono los trae, comprobado leyendo la tabla
`cmap` del TTF. En web el riesgo es además menor que en Android: si a una fuente le faltara
un glifo, el navegador cae a otra **solo para ese carácter**, no para todo el texto.

### 5.5 Contrastes medidos

No son números escritos a mano: `web/src/estilos.test.ts` los recalcula con la fórmula de
WCAG 1.4.3 leyendo `estilos.css`, y falla si alguno baja de 4,5:1.

| Token | Sobre `#121216` | |
|---|---|---|
| texto `#e4e4e7` | 14,7:1 | ✓ |
| verde `#4ade80` | 10,7:1 | ✓ |
| cian `#22d3ee` | 10,3:1 | ✓ |
| ámbar `#f59e0b` | 8,7:1 | ✓ |
| azul `#60a5fa` | 7,3:1 | ✓ |
| morado `#a78bfa` | 6,9:1 | ✓ |
| rojo `#f87171` | 6,8:1 | ✓ |
| apagado `#595962` | 2,7:1 | solo secundario, §5.3 |
| líneas `#2a2a30` | 1,3:1 | ver abajo |

Todos bajaron algo al levantarse el fondo —el más justo pasa de 7,6:1 a 6,8:1— y ninguno
se acerca al mínimo. Ese descenso **es el objetivo**, no un efecto colateral: 16,5:1 de
texto sobre negro puro es más contraste del que ayuda a nadie.

**La desviación conocida.** WCAG 1.4.11 pide 3:1 para el borde de un control que hace
falta para identificarlo. `líneas` da 1,3:1 y `superficie` 1,1:1: un campo de texto cuyo
único límite sea ese borde no se percibe.

Los bordes de lo interactivo —campos y botones— usan `apagado` en reposo y `ámbar` al
enfocarse. `apagado` da 2,7:1, todavía por debajo de 3:1, pero el control se identifica
por su etiqueta en `texto` a 14,7:1, que siempre está visible.

```
// ponytail: borde interactivo en apagado, 2,7:1 contra los 3:1 de WCAG 1.4.11.
// El techo es ese. Si en el aparato cuesta ver dónde se escribe, sube a un token
// nuevo #6b7280, que da 4,34:1 y pasa de sobra.
```

`líneas` se queda para separadores decorativos, que no tienen que cumplir nada.

---

## 6. Marco de pantalla y navegación

Pestañas **arriba y fijas**: `hoy · progreso · perfil`, en minúscula monoespaciada, con
subrayado en la activa. Fuera de pestañas: login, registro y recuperar contraseña.

Cada pantalla abre con una línea de prompt —`$ hoy`— que es dibujo: el `$` no se lee y no
hace falta saber qué significa.

```
   hoy      progreso    perfil
   ▔▔▔
─────────────────────────────────
 $ hoy
 // martes, 11 de agosto

 NIVEL 4              RANGO  E
 [▓▓▓▓▓▓░░░░]
 240 / 400 XP
 racha 12 días

 ▸ MISIONES DE HOY  [1 de 4] ▾
 [✓] Beber 2 litros de agua
 [ ] Entrenar
     // 1 de 3 esta semana
 [ ] Apuntar el peso
 [ ] 8.000 pasos
     // 5.240 de 8.000
```

### Color por sección

| Sección | Cabecera |
|---|---|
| misiones, entreno | ámbar |
| nutrición, agua | verde |
| progreso | azul |

Dentro de una fila el color solo dice estado: `texto` pendiente, `verde` hecha.

---

## 7. Las pantallas de la fase 1.1

**Login** — correo, contraseña, `[ ENTRAR ]`, y enlaces a «No recuerdo mi contraseña» y
«Crear cuenta».

**Registro** — nombre, correo, contraseña de 8 caracteres mínimo.

**Recuperar contraseña** — dos pasos. Al enviar el correo **se avanza al paso 2 siempre**,
con el mismo texto pase lo que pase:

> Si ese correo está registrado, te hemos enviado un código de 6 cifras. Caduca en 30 minutos.

Es la mitad que le toca al cliente. El servidor responde 200 exista o no la cuenta; si la
pantalla solo avanzase cuando existe, volvería a delatar qué correos están registrados.

**Hoy** — cabecera de progreso y la lista de misiones, de solo lectura.

**Progreso** y **perfil** — un `Comentario` diciendo qué llegará y en qué fase.

---

## 8. Errores, fechas y sesión

### 8.1 Traducción, en un solo sitio

`data/api` traduce una vez y las pantallas enseñan el texto ya hecho.

| Situación | Qué se lee |
|---|---|
| Sin red (`IOException`) | «No hay conexión. Comprueba el wifi o los datos.» |
| 401 | limpia la sesión y lleva al login |
| 422 | el mensaje del servidor, ya en español, con la inicial en mayúscula |
| 429 | «Demasiados intentos. Espera un momento y vuelve a probar.» |
| Cualquier otra | «No hemos podido conectar. Inténtalo otra vez.» |

**Ningún código HTTP en pantalla.** El 429 es el único texto que pone la app, porque el
limitador de Laravel responde `Too Many Attempts.` antes de entrar en la ruta y no pasa
por `lang/es`.

Los mensajes de 422 llegan en minúscula porque van debajo de un campo; se capitalizan al
pintarlos.

**El login con credenciales malas devuelve 422**, no 401: `errors.email[0]` =
«Credenciales incorrectas.». Por eso el 401 solo puede venir de las rutas con token y el
manejador global no necesita excepciones para auth.

### 8.2 Interceptor

Toda petición lleva `Accept: application/json` y, si hay sesión,
`Authorization: Bearer …`.

⚠️ **`Accept: application/json` no es opcional.** Sin esa cabecera el servidor devuelve
HTML en los errores.

El 401 lo emite el interceptor a un `SharedFlow` que recoge la raíz de navegación en
`app/`: un solo sitio, y funciona desde cualquier pantalla.

### 8.3 Fechas

`Europe/Madrid` fijo, **no la zona del móvil**. El servidor decide a qué día pertenecen
las misiones con `config('srank.timezone')`, que es `Europe/Madrid`. Si la app usara la
zona del dispositivo, en un viaje diría «hoy» sobre un día distinto del que el servidor
está puntuando.

El `date` de `/api/system/today` ya llega como fecha suelta calculada por el servidor. El
resto de instantes viajan en UTC y se convierten antes de quedarse con un día.

### 8.4 Sin conexión

En 1.1, todo requiere conexión. Cada pantalla dice qué pasa en español llano y ofrece
reintentar. El borrador sin conexión es de la fase 1.2.

---

## 9. Qué se prueba

Las piezas con ramas de verdad, con Vitest. **Un test que no falla sin el arreglo no
vale**: se quita el arreglo, se comprueba que el test cae, se restaura.

- El traductor de errores: un caso por fila de la tabla de §8.1.
- Recuperar contraseña: **mismo texto y mismo avance** exista o no el correo.
- Login: entra bien · credenciales malas (422) · 429.
- Hoy: carga · error · que reintentar vuelve a pedir.
- La conversión a `Europe/Madrid`, con un instante de las 23:30 UTC que en Madrid ya es
  el día siguiente.

### Lo que ningún test cubre

La fase 1.0 dejó cuatro fallos que pasaron 254 tests y solo aparecieron al tocar el
servidor. Estas dos son de la misma familia y se comprueban **en un móvil real**:

1. Que la cookie de sesión viaje. La suite del backend corre con `SESSION_DRIVER=array`,
   que no guarda nada entre peticiones, así que el ida y vuelta no se puede reproducir ahí.
2. Que todo se lee con el tamaño de letra del navegador al máximo.
3. Que la CSP cerrada no bloquee nada. Si bloquea, la aplicación sale en blanco.
4. Que se pueda instalar en el móvil, que solo se ofrece sobre HTTPS.

---

## 10. Fuera de alcance

**Componentes que esperan** — la barra continua (fase 1.3/1.4, cuando haya estadísticas y
macros que enseñar) y el rombo de rareza `◆`/`◇` (fase 1.5, con la pantalla de logros).
No se escriben ahora: en 1.1 no hay datos con los que juzgar si están bien.

La `VentanaSistema` se aplaza a la 1.2, que es cuando algo la dispara. En Android se
escribió por adelantado para poder verla en un `@Preview`; aquí eso no aporta lo mismo,
porque cualquier pantalla se ve entera pulsando F5.

**Tema claro.** La app es negra. No hay variante.

**Animaciones**, más allá de que los bloques de XP se enciendan de uno en uno.
