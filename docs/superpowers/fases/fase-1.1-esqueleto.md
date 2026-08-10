# Fase 1.1 · Esqueleto Android

**Objetivo:** que exista un proyecto Android que arranca, se ve como S-RANK, deja entrar a
un usuario y le enseña su nivel.

Es la fase que **define el aspecto de todo lo demás**. Cada componente que se escriba aquí
lo van a usar las cuatro fases siguientes sin volver a discutirlo, así que vale la pena
pelearse ahora con el sistema de diseño y no después.

## Qué existe cuando empieza

**No hay ni una línea de Kotlin.** El repositorio tiene `backend/`, `docs/` y `old/`.

La API está en producción y verificada: `https://s-rank.israelzamora.es`. Todo lo que esta
fase necesita del servidor ya funciona.

## Qué hay que construir

### El proyecto Gradle

La estructura la fija el spec §3:

```
settings.gradle.kts  build.gradle.kts  gradlew  gradle/
app/                  navegación y arranque
core/system/          nivel, XP, rango, misiones, logros, estadísticas
core/ui/              sistema de diseño
data/api/             Retrofit + interceptor de token
data/session/         DataStore: token y usuario
feature/auth/         login, registro, recuperar contraseña
```

Los módulos `feature/training`, `nutrition`, `progress` y `profile` se crean vacíos o no
se crean: los levanta cada fase cuando le toca.

⚠️ El `.gitignore` de la raíz está escrito para un proyecto Laravel. Hay que añadirle lo
de Android (`/build`, `.gradle/`, `local.properties`, `*.apk`, `*.keystore`) **sin romper**
las reglas que ya protegen `*.sqlite`, `*.sql` y `.env*`, que son las que evitan que se
suban datos de salud y credenciales.

### El sistema de diseño — `core/ui/`

Está definido entero en el spec §7 y hay que trasladarlo tal cual: once colores, la escala
tipográfica de cinco tamaños, y los componentes.

**JetBrains Mono va empaquetada dentro de la app.** Nunca la monoespaciada del sistema:
cada fabricante trae una distinta y la estética se rompe en cuanto sales de tu móvil.

**El cian `#22d3ee` está reservado a las ventanas del Sistema.** Si aparece en la interfaz
normal deja de significar «premio» y el momento de recompensa se desactiva. Es la única
regla de color que no admite excepciones.

Componentes que quedan escritos aquí y ya no se rediscuten:

- Fila de lista con `[✓]` / `[ ]`
- Cabecera de sección plegable `▸ TÍTULO   [2 de 4] ▾`
- Comentario `// …` en color apagado
- Barra de bloques `[▓▓▓▓▓░░░░░]` para XP y agua
- Barra continua para estadísticas y macros
- Botón con borde visible, 48 dp de alto mínimo
- Ventana del Sistema con esquinas en ángulo
- Insignia de rango y rombo de rareza `◆` / `◇`

### La capa de datos — `data/`

`data/api/` es Retrofit con un interceptor que mete `Authorization: Bearer …` y
`Accept: application/json` en toda petición.

⚠️ **`Accept: application/json` no es opcional.** Sin esa cabecera el servidor devuelve
HTML en los errores. Se arregló para que no dé un 500, pero la app debe mandarla siempre.

`data/session/` es DataStore: token y datos del usuario. Un 401 tiene que limpiar la
sesión y llevar al login, desde cualquier pantalla.

### Navegación — `app/`

Tres pestañas: **hoy · progreso · perfil**. Fuera de pestañas: login, registro y recuperar
contraseña.

En esta fase `progreso` y `perfil` pueden ser pantallas vacías con un texto. Lo que tiene
que funcionar es la navegación y que `hoy` pinte la cabecera de progreso real.

### Las pantallas de esta fase

**Login** — correo y contraseña. Un enlace a «No recuerdo mi contraseña» y otro a «Crear
cuenta».

**Registro** — nombre, correo, contraseña. Mínimo 8 caracteres.

**Recuperar contraseña** — dos pasos: pedir el código y luego introducir el código de seis
cifras más la contraseña nueva. El código caduca a los 30 minutos.

**Hoy**, versión mínima — la cabecera de progreso con nivel, rango, XP y racha, y las
misiones del día como lista de solo lectura. Las secciones de entreno y nutrición llegan
en 1.2 y 1.3.

## Endpoints que consume

| | |
|---|---|
| `POST /api/auth/login` | `{email, password}` → `{access_token, token_type, user_name, is_admin}` |
| `POST /api/auth/register` | `{name, email, password}` → 201, mismo cuerpo |
| `POST /api/auth/forgot-password` | `{email}` → **200 siempre** |
| `POST /api/auth/reset-password` | `{email, code, password}` → 200 o 422 |
| `POST /api/auth/logout` | invalida el token actual |
| `GET /api/system/today` | progreso + misiones del día + entreno sugerido |
| `GET /api/system/profile` | progreso + módulos activos |
| `GET /api/user` | el usuario autenticado |

`forgot-password` responde 200 exista o no el correo, a propósito: decir «ese usuario no
existe» es regalar una lista de cuentas válidas. **La pantalla tiene que decir siempre lo
mismo**, algo como «Si ese correo está registrado, te hemos enviado un código». No inventes
un mensaje distinto para cada caso: reintroducirías la fuga desde el cliente.

## Límites de intentos que vas a encontrar probando

| Ruta | Límite |
|---|---|
| `login` | 5 por minuto |
| `register` | 3 por hora |
| `forgot-password` | 3 por hora |
| `reset-password` | 5 por hora |

Son por IP. Al desarrollar te los vas a comer. El contador vive en la tabla `cache` de
MySQL; `DELETE FROM cache;` lo reinicia sin tocar ningún dato.

## Restricciones

**La estética es decoración.** El `$`, los `//` y los `[✓]` van encima de listas y botones
normales. Un usuario que no sepa qué es una terminal tiene que poder usar la app entera
pulsando. Si una pantalla solo se entiende sabiendo lo que es una terminal, está mal.

**Accesibilidad, del spec §7.** Contraste 4,5:1 sobre negro, objetivos táctiles de 48 dp,
y la maquetación **no puede depender de anchos fijos en caracteres** porque la app respeta
el tamaño de fuente del sistema. Esto choca de frente con la estética de terminal, que
invita a alinear con espacios. No lo hagas.

**Los errores se cuentan en español llano.** «No hay conexión» y no «Error 503». Nada de
códigos HTTP en pantalla.

**Las fechas llegan en UTC.** Convertir a `Europe/Madrid` antes de quedarse con un día, o
las misiones de hoy aparecerán como las de ayer a partir de medianoche.

## Terminado cuando

- [ ] `./gradlew assembleDebug` compila y la app arranca en un móvil real.
- [ ] Se puede crear una cuenta desde cero, salir y volver a entrar.
- [ ] Se puede recuperar la contraseña con el código que llega por correo.
- [ ] La sesión sobrevive a cerrar y abrir la app.
- [ ] Un 401 lleva al login limpiando la sesión, desde cualquier pantalla.
- [ ] `hoy` enseña el nivel, el rango, la barra de XP y la racha reales.
- [ ] Las misiones del día se ven, con su texto en castellano.
- [ ] Sin conexión, cada pantalla dice qué pasa en español y ofrece reintentar.
- [ ] Los ViewModel tienen tests.
- [ ] Se ve bien con el tamaño de fuente del sistema al máximo.

## Tarea suelta que arrastra la fase 1.0

Publicar `lang/es` en el backend, para que los errores de validación dejen de salir en
inglés. Es `php artisan lang:publish` y traducir `validation.php`. Sin esto, un registro
con el correo repetido responde *«The email has already been taken»*.

## Prompt para arrancar el chat

```
Vamos con la fase 1.1 de S-RANK: el esqueleto Android.

Lee primero, en este orden:
  docs/superpowers/fases/fase-1.1-esqueleto.md
  docs/superpowers/specs/2026-08-10-s-rank-design.md  (§3, §4, §7, §8, §9)
  docs/superpowers/fases/fase-1.0-backend.md

Hay capturas de referencia de la estética en /no_subir_referencias.

La API ya está en producción y verificada: https://s-rank.israelzamora.es

Antes de escribir código quiero repasar contigo cómo va a quedar el sistema
de diseño, que es lo que condiciona las cuatro fases siguientes.
```
