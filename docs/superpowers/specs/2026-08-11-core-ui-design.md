# S-RANK — Sistema de diseño de `core/ui`

**Fecha:** 2026-08-11
**Estado:** aprobado, pendiente de plan de implementación
**Concreta:** `2026-08-10-s-rank-design.md` §7 (sistema de diseño) y §4 (arquitectura)
**Fase:** 1.1 · Esqueleto Android

---

## 1. Para qué existe este documento

El spec de la fase 1 fija los once colores, la escala tipográfica y la lista de
componentes. No dice **cómo se construyen**, y esa parte condiciona las cuatro fases
siguientes: cada pantalla de 1.2 a 1.5 se monta con lo que se escriba aquí.

Este documento cierra esas decisiones. No cambia ninguna del spec: las concreta.

### Lo que se hereda sin discutir

Once colores con su hexadecimal · escala de cinco tamaños · JetBrains Mono empaquetada ·
el cian reservado a las ventanas del Sistema · contraste 4,5:1 sobre negro · objetivos
táctiles de 48 dp · la maquetación no depende de anchos fijos en caracteres.

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
| 1 | Pestañas **arriba**, fijas | Como las capturas de referencia. Fijas porque perderlas en un scroll largo es peor que gastar 48 dp |
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

```kotlin
// core/ui/theme/Color.kt
@Immutable
data class SRankColors(
    val fondo: Color,       // #000000
    val superficie: Color,  // #0d0d10
    val lineas: Color,      // #1f1f23
    val texto: Color,       // #e4e4e7
    val apagado: Color,     // #52525b
    val ambar: Color,       // #f59e0b  marca, acción, XP
    val verde: Color,       // #4ade80  completado
    val azul: Color,        // #60a5fa  información y navegación
    // ponytail: los tres siguientes solo los toca el momento de recompensa.
    // Si aparecen en una pantalla normal, el premio deja de significar nada.
    val rojo: Color,        // #f87171  récord, alerta
    val cian: Color,        // #22d3ee  EXCLUSIVO de las ventanas del Sistema
    val morado: Color,      // #a78bfa  rareza épica
)

object SRank {
    val color: SRankColors     @Composable get() = LocalSRankColors.current
    val texto: SRankTypography @Composable get() = LocalSRankTypography.current
}
```

`SRankTheme` envuelve un `MaterialTheme` cuyo `ColorScheme` se rellena con los mismos
valores, para que los componentes de M3 no aporten color propio por debajo.

### 3.2 Tipografía

JetBrains Mono en `core/ui/src/main/res/font/`. Nunca la monoespaciada del sistema.

| Nombre | Tamaño | Para qué |
|---|---|---|
| `titulo` | 20 sp | cabecera de pantalla |
| `seccion` | 16 sp | cabeceras de sección |
| `cuerpo` | 13 sp | contenido |
| `nota` | 11,5 sp | comentarios `//`, valores secundarios |
| `etiqueta` | 10,5 sp | versales |

Las versales se hacen con mayúsculas y algo de `letterSpacing`. Compose no tiene versales
de verdad y empaquetar una segunda fuente para fingirlas no compensa.

Todos los tamaños en `sp`, nunca en `dp`: la app respeta el tamaño de fuente del sistema.

---

## 4. Módulos de Gradle

Seis, los del spec §3. Ni uno más.

| Módulo | Qué lleva | De quién depende |
|---|---|---|
| `core/ui` | tokens y los siete componentes | de nadie |
| `core/system` | `Progreso`, `Mision`, `Rango`, su repositorio, la cabecera de progreso y la lista de misiones | `core/ui`, `data/api` |
| `data/api` | Retrofit, interceptor de token, traducción de errores | — |
| `data/session` | DataStore: token y usuario | — |
| `feature/auth` | login, registro, recuperar contraseña | `core/ui`, `data/api`, `data/session` |
| `app` | navegación, pestañas, «hoy», los dos huecos | todos |

**`core/system` no declara `feature/*`.** Es la mitad de la regla rectora que en Android
sale gratis, y la vigila el compilador en vez de la disciplina.

`feature/training`, `nutrition`, `progress` y `profile` **no se crean**. Un módulo vacío es
ceremonia que hay que mantener; los levanta cada fase cuando le toque. `data/draft` (Room)
tampoco: es de la fase 1.2.

«Hoy» vive en `app/` y monta secciones. Es lo que permite que un módulo de la fase 2 gane
una sección sin tocar la navegación. En 1.1 la única sección son las misiones, que las
aporta `core/system`.

### `.gitignore`

Se le añade lo de Android —`/build`, `**/build/`, `.gradle/`, `local.properties`, `*.apk`,
`*.keystore`, `.cxx/`— **sin tocar** las reglas que ya protegen `*.sqlite`, `*.sql` y
`.env*`. Esas son las que evitan que se suban datos de salud y credenciales.

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
| `BotonSRank` | borde visible, 48 dp mínimo | formularios |
| `InsigniaRango` | la letra del rango con su marco | cabecera de «hoy» |
| `VentanaSistema` | cian, esquinas en ángulo | solo `@Preview` |

### 5.1 Accesibilidad: la decoración no se lee

`Text("[✓] Beber 2 litros de agua")` lo lee TalkBack como «corchete, marca de
verificación, corchete». La fila entera va como un solo nodo semántico:

```kotlin
Row(
    Modifier.clearAndSetSemantics {
        text = AnnotatedString(mision.etiqueta)
        stateDescription = if (mision.completada) "hecha" else "pendiente"
    }
) { /* [✓], la etiqueta y el // son dibujo, no contenido */ }
```

Un usuario ciego oye «Beber 2 litros de agua, hecha». Lo mismo con el `$` de las
cabeceras y con el `▸`/`▾` de las secciones, que se sustituyen por el estado
plegado/desplegado que Compose ya sabe anunciar.

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
objetivos táctiles y no necesitan los 48 dp. Cuando la fase 1.2 traiga las opcionales
marcables a mano —hay un `POST /api/system/quests/{key}/complete` esperando— es añadir un
`onClick` nullable, no rediseñar.

### 5.3 `Comentario` y el límite del gris

`#52525b` da **2,72:1** sobre negro. Está por debajo de 4,5:1 a propósito, y por eso el
spec §7 lo limita a «texto secundario nunca esencial».

De ahí sale una regla que no estaba escrita: **`apagado` nunca lleva la única copia de un
dato**. Un comentario decorativo puede ir entero en apagado; uno que lleva información
—`// 1.250 de 2.000 ml`— parte el color:

```
//  →  apagado      el marcador es decoración
texto →  texto       el dato se lee (16,5:1)
```

Sigue leyéndose como comentario: lo dicen el marcador y el tamaño `nota`, no el gris.

### 5.4 `BarraBloques`

Diez bloques como texto, en **línea propia**:

```
[▓▓▓▓▓▓░░░░]
240 / 400 XP
```

Compartir línea con los números da unos 24 caracteres, que con la fuente al máximo no
caben en un móvil de 320 dp. Separadas, la barra ocupa unos 187 dp de los 288 disponibles.

Avanza a saltos del 10 %. Para una estética de terminal se lee como intención, y al subir
de nivel los bloques se encienden de uno en uno.

⚠️ `▓` y `░` son U+2593 y U+2591. **Si JetBrains Mono no trae esos glifos, Android se cae
a otra fuente y la barra se descuadra.** Se comprueba contra el TTF antes de dar la barra
por buena; si faltan, se dibuja con `Canvas` (§9).

### 5.5 Contrastes medidos

| Token | Sobre negro | |
|---|---|---|
| texto `#e4e4e7` | 16,5:1 | ✓ |
| ámbar `#f59e0b` | 9,8:1 | ✓ |
| verde `#4ade80` | 12,1:1 | ✓ |
| azul `#60a5fa` | 8,3:1 | ✓ |
| rojo `#f87171` | 7,6:1 | ✓ |
| cian `#22d3ee` | 11,6:1 | ✓ |
| morado `#a78bfa` | 7,7:1 | ✓ |
| apagado `#52525b` | 2,7:1 | solo secundario, §5.3 |
| líneas `#1f1f23` | 1,3:1 | ver abajo |

**La desviación conocida.** WCAG 1.4.11 pide 3:1 para el borde de un control que hace
falta para identificarlo. `líneas` da 1,3:1 y `superficie` 1,1:1: un campo de texto cuyo
único límite sea ese borde no se percibe.

Los bordes de lo interactivo —campos y botones— usan `apagado` en reposo y `ámbar` al
enfocarse. `apagado` da 2,7:1, todavía por debajo de 3:1, pero el control se identifica
por su etiqueta en `texto` a 16,5:1, que siempre está visible.

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
 // lunes, 11 de agosto

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

Los ViewModel, como pide la fase, y las dos piezas con ramas de verdad. **Un test que no
falla sin el arreglo no vale**: se quita el arreglo, se comprueba que el test cae, se
restaura.

- El traductor de errores: un caso por fila de la tabla de §8.1.
- Recuperar contraseña: **mismo texto y mismo avance** exista o no el correo.
- Login: entra bien · credenciales malas (422) · 429.
- Hoy: carga · error · que reintentar vuelve a pedir.
- La conversión a `Europe/Madrid`, con un instante de las 23:30 UTC que en Madrid ya es
  el día siguiente.

### Lo que ningún test cubre

La fase 1.0 dejó cuatro fallos que pasaron 254 tests y solo aparecieron al tocar el
servidor. Estas dos son de la misma familia y se comprueban **en un móvil real**:

1. Que JetBrains Mono trae `▓` y `░`. Si no, la barra de XP se descuadra y hay que
   dibujarla con `Canvas`.
2. Que todo se lee con el tamaño de fuente del sistema al máximo.

---

## 10. Fuera de alcance

**Componentes que esperan** — la barra continua (fase 1.3/1.4, cuando haya estadísticas y
macros que enseñar) y el rombo de rareza `◆`/`◇` (fase 1.5, con la pantalla de logros).
No se escriben ahora: en 1.1 no hay datos con los que juzgar si están bien.

La `VentanaSistema` sí se escribe, aunque nada la dispare todavía. Es el momento de
recompensa y define la identidad de la app; verla ahora, aunque sea en un `@Preview`,
evita descubrir en 1.2 que obliga a retocar tokens ya cerrados.

**Tema claro.** La app es negra. No hay variante.

**Animaciones**, más allá de que los bloques de XP se enciendan de uno en uno.
