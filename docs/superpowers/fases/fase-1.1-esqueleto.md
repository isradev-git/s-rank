# Fase 1.1 · Esqueleto web

**Objetivo:** que exista una aplicación que arranca, se ve como S-RANK, deja entrar a un
usuario y le enseña su nivel.

Es la fase que **define el aspecto de todo lo demás**. Cada componente escrito aquí lo van
a usar las cuatro fases siguientes sin volver a discutirlo.

## El cambio de plataforma

Esta fase se empezó en Android, con Kotlin y Jetpack Compose, y llegó a estar casi
terminada: doce de las trece tareas del plan, 84 tests en verde y un APK instalable. **Se
descartó el 12 de agosto de 2026** porque el desarrollo en Android incomodaba lo bastante
como para poner en riesgo que la aplicación llegara a terminarse, y eso pesa más que
cualquier ventaja técnica.

Lo caro no se tocó: el backend son 71 rutas JSON agnósticas del cliente, y los tres specs
describen una interfaz, no una tecnología. El esqueleto de Kotlin sigue recuperable en la
etiqueta `android-fase-1.1`.

Lo que se ganó al cambiar, además de la comodidad:

- **La estética sale más barata.** «Texto monoespaciado en filas» es lo que un navegador
  hace nativamente. La sección plegable es un `<details>` sin una línea de JavaScript, y las
  flechas `▸`/`▾` las pone el CSS.
- **La accesibilidad sale de HTML normal**, sin las acrobacias de `clearAndSetSemantics`.
- **Se despliega por FTP** como el backend. Sin Play Store, sin firmar, sin versiones.
- **iOS sale gratis**, y estaba fuera de alcance.

Lo que costó, y hay que tener presente en la 1.2:

- **El borrador sin conexión es más artesanal.** `service worker` + IndexedDB en vez de Room
  + WorkManager. Es el único punto donde la web da más trabajo que Android, y llega en la
  fase siguiente.
- **La notificación diaria de las 20:00** no puede ser local. Tendrá que ser un cron en el
  servidor con Web Push, que además es mejor arquitectura: el servidor ya sabe qué misiones
  quedan pendientes.

## Qué se construyó

### El proyecto

```
web/
  src/
    api.ts             la única puerta a la API: peticiones y traducción de errores
    componentes.tsx    los ocho componentes del sistema de diseño, todos juntos
    formato.ts         los cálculos y textos con ramas — es lo que está probado
    estilos.css        los once colores y la escala tipográfica
    pantallas/         Login · Registro · Recuperar · Hoy
    App.tsx            rutas, pestañas y el portero de sesión
  public/              fuentes, iconos, manifiesto y trabajador de servicio
```

**Vite y no Next.js.** Ginernet es FTP sin SSH ni Node: el renderizado en servidor no tiene
dónde ejecutarse. Vite genera ficheros estáticos y eso sí se sirve. No limita nada, porque
la aplicación va detrás de un login y no necesita SEO.

**CSS plano con variables, no Tailwind.** El sistema de diseño son once colores, una fuente
y ocho componentes; un framework de utilidades es peso muerto.

**Un fichero para todos los componentes.** Son pequeños. Ocho ficheros de veinte líneas
cuestan más de mantener que uno de ciento sesenta.

### El sistema de diseño

Está definido entero en `../specs/2026-08-11-core-ui-design.md` y se trasladó tal cual:
once colores con su contraste medido, cinco tamaños de letra, ocho componentes.

**JetBrains Mono va servida desde el propio dominio**, no desde un CDN. Así funciona sin
conexión y la CSP puede quedarse cerrada a `'self'`.

**El cian `#22d3ee` está reservado a las ventanas del Sistema.** Si aparece en la interfaz
normal deja de significar «premio». Es la única regla de color sin excepciones.

**Sin iconos ni emoji.** La referencia visual de `no_subir_referencias/diseño_buscado/` sí
los lleva; aquí se descartaron. Los caracteres de dibujo del spec no son iconos.

### Autenticación por cookie, no por token

El frontend **no guarda ningún token**. La sesión viaja en una cookie `httpOnly` que
JavaScript no puede leer, así que un XSS no tiene nada que robar — y detrás hay datos de
salud. Toda escritura manda `X-XSRF-TOKEN`.

En el backend eso costó tres cambios pequeños: `statefulApi()`, y que `login` y `register`
abran sesión **solo si la petición viene del frontend**. Todo lo demás sigue por Bearer sin
enterarse. Está en `SesionWebTest`.

### Las pantallas

**Login** — correo y contraseña, con enlaces a recuperar y a crear cuenta.

**Registro** — nombre, correo, contraseña de 8 caracteres mínimo. Deja la sesión abierta.

**Recuperar contraseña** — dos pasos. **Avanza al paso 2 sin mirar la respuesta.**

**Hoy** — nivel, rango, barra de XP, racha y misiones del día, de solo lectura. Las
opcionales van aparte, bajo «si te sobra tiempo».

**Progreso** y **perfil** — huecos con su fase de llegada. Perfil lleva el botón de salir.

## Endpoints que consume

| | |
|---|---|
| `GET /sanctum/csrf-cookie` | reparte el token CSRF antes de la primera escritura |
| `POST /api/auth/login` | `{email, password}` → 200 y cookie de sesión |
| `POST /api/auth/register` | `{name, email, password}` → 201 y cookie de sesión |
| `POST /api/auth/forgot-password` | `{email}` → **200 siempre** |
| `POST /api/auth/reset-password` | `{email, code, password}` → 200 o 422 |
| `POST /api/auth/logout` | invalida la sesión |
| `GET /api/system/today` | progreso + misiones del día + entreno sugerido |
| `GET /api/user` | el usuario autenticado, o 401 si no hay sesión |

`forgot-password` responde 200 exista o no el correo, a propósito: decir «ese usuario no
existe» es regalar una lista de cuentas válidas. **La pantalla dice siempre lo mismo.** Si
solo avanzara cuando la cuenta existe, la fuga que el servidor evita la reabriría el cliente.

## Límites de intentos

| Ruta | Límite |
|---|---|
| `login` | 5 por minuto |
| `register` | 3 por hora |
| `forgot-password` | 3 por hora |
| `reset-password` | 5 por hora |

Son por IP. **En desarrollo, el navegador y cualquier `curl` comparten el contador**: a
través del proxy de Vite, Laravel ve todo viniendo de `127.0.0.1`. Se reinicia con
`mysql -u srank -psrank srank_local -e "DELETE FROM cache;"`.

⚠️ **El 429 llega en inglés**: `{"message":"Too Many Attempts."}`. Lo emite el limitador
antes de entrar en la ruta, así que no pasa por `lang/es`. Lo pone `api.ts`, y es el único
mensaje de la API que no viene ya en castellano.

## Restricciones

**La estética es decoración.** El prompt, los `//` y los `[✓]` van encima de listas y
botones normales. En HTML eso significa `aria-hidden` en la decoración y el estado dicho
con palabras.

**Accesibilidad.** Contraste 4,5:1 sobre el fondo, objetivos táctiles de 48 px, y la
maquetación **no puede alinear con espacios** porque respeta el tamaño de letra del
navegador: el hueco lo abre `flex`.

**Los errores se cuentan en español llano.** «No hay conexión» y no «Error 503». Ningún
código HTTP en pantalla. La traducción vive en un solo sitio, `api.ts`.

**Las fechas.** El `date` de `/api/system/today` ya llega resuelto por el servidor en
`Europe/Madrid`: se lee y se escribe en UTC, sin convertir a la zona del navegador.

## Terminado cuando

- [x] `npm run build` compila y la aplicación arranca.
- [x] Se puede crear una cuenta desde cero, salir y volver a entrar.
- [x] Se puede recuperar la contraseña con el código que llega por correo.
- [x] La sesión sobrevive a recargar y a cerrar el navegador.
- [x] Un 401 lleva al login limpiando la sesión, desde cualquier pantalla.
- [x] `hoy` enseña el nivel, el rango, la barra de XP y la racha reales.
- [x] Las misiones del día se ven, con su texto en castellano.
- [x] Sin conexión, cada pantalla dice qué pasa en español y ofrece reintentar.
- [x] Se ve bien con el tamaño de letra del navegador al máximo.
- [x] Las pantallas tienen tests.
- [x] Desplegada en `https://s-rank.israelzamora.es` y funcionando: se crea cuenta, se entra
      y se ven las misiones del día.
- [x] Instalada en el móvil desde el dominio real, y probada ahí.

## Lo que solo aparece fuera de los tests

La fase 1.0 dejó cuatro fallos que pasaron 254 tests y solo aparecieron al tocar el
servidor. Lo de esta lista es de la misma familia.

**Verificado ya, contra el Laravel local:**

- El ciclo entero de cookie: `csrf` → `login` → `/api/user` sin `Authorization` → `logout`
  → 401. La suite **no puede** demostrarlo, porque corre con `SESSION_DRIVER=array`, que no
  guarda nada entre peticiones.
- `forgot-password` responde igual, byte a byte, con un correo registrado y con uno
  inventado.
- El código de seis cifras sirve una vez; al reutilizarlo, 422.

**Verificado ya, en producción** (12 de agosto de 2026, primer despliegue web):

- **La CSP cerrada no bloquea nada.** Se pinta la pantalla entera, con la fuente incluida.
  Era el riesgo gordo: si hubiera fallado, la aplicación sale en blanco.
- **La cookie de sesión viaja sobre el dominio real.** `/api/system/today` devuelve datos sin
  `Authorization`. Es lo que la suite **no puede** demostrar por el `SESSION_DRIVER=array`.
- **`X-XSRF-TOKEN` sobrevive a LiteSpeed.** Se creó una cuenta desde la propia aplicación, y
  eso es un POST: sin el reenvío del `.htaccess` de la raíz habría contestado 419.
- **`/api/*` inexistente devuelve 404 JSON**, no el `index.html`. La guarda del fallback
  aguanta fuera de los tests.
- **Los ficheros internos no se sirven**: `.env`, `.git/`, `vendor/`, `storage/logs/`,
  `composer.json`, `artisan`, `phpunit.xml` y compañía dan 403.
- **La fecha sale bien**: «miércoles, 12 de agosto», sin desfase de zona.

**Verificado en el móvil el 18 de agosto:** se instala desde el dominio real y se usa sin
problemas. Los navegadores solo ofrecen la instalación sobre HTTPS, así que contra el
servidor de desarrollo no salía la opción; el manifiesto se sirve con
`application/manifest+json`, que es el MIME que exige el instalador.

**Dos comprobaciones que se dan por buenas sin haberse hecho**, y conviene saber cuáles son
antes de fiarse de ellas en la 1.2:

- **El lector de pantalla.** Que una misión hecha se oiga «Beber 2 litros de agua, hecha»,
  que el prompt no se lea y que la barra de XP se oiga como un porcentaje. Hay tests que
  cubren lo que se puede cubrir sin voz —los corchetes fuera del nombre accesible del
  botón, el `aria-hidden` de la decoración—, pero TalkBack en la mano no se pasó.
- **Modo avión con la aplicación instalada.** Tiene que abrirse y explicarlo ella, no la
  página de error del navegador. Es lo que arregló `cbee014` y lo que el trabajador de
  servicio existe para conseguir; se probó en el escritorio, no en el móvil.

Ninguna de las dos bloquea la 1.2. Son un minuto cada una el día que se quiera cerrar.

## Prompt para arrancar el chat

```
Seguimos con la fase 1.1 de S-RANK. El esqueleto web ya está desplegado y funcionando
en https://s-rank.israelzamora.es; queda comprobar en el móvil lo que ningún test cubre:
instalarla como PWA, el lector de pantalla y el modo avión.

Lee primero:
  docs/superpowers/fases/fase-1.1-esqueleto.md      ← qué falta y cuándo está terminada
  docs/superpowers/specs/2026-08-11-core-ui-design.md

El frontend está en web/ (React + Vite), el backend en backend/ (Laravel, en
producción). `bash backend/build-deploy.sh` construye el frontend y arma el
paquete que se sube por FTP.

Tres cosas que conviene no olvidar:
  · Un test que no falla sin el arreglo no vale.
  · La pantalla de recuperar contraseña avanza siempre, exista la cuenta o no.
    Es una regla de seguridad, no una decisión de redacción.
  · La CSP está cerrada a 'self'. Cualquier recurso externo que se añada hay que
    declararlo en backend/public/.htaccess o el navegador lo bloquea en silencio.
```
