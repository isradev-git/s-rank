# CLAUDE.md

Guía para Claude Code (claude.ai/code) al trabajar en este repositorio.

## Idioma

Responde siempre en español. También los mensajes de commit, los comentarios del código y
cualquier texto que vaya a ver el usuario final.

## Qué es esto

**S-RANK** es una aplicación web para hábitos y progreso personal, con estética de terminal
y progresión de videojuego inspirada en *Solo Leveling*. Sustituye a FitLoop, una web
Laravel + Blade que sigue en `old/` como referencia.

- **Frontend:** React + TypeScript con Vite, en `web/`. Se instala en el móvil como PWA.
- **Backend:** Laravel API-only, en `backend/`. Terminado y en producción.

**Antes fue una app Android.** El esqueleto en Kotlin + Compose llegó a estar hecho y se
descartó el 12 de agosto de 2026, con la fase 1.1 casi cerrada: el desarrollo en Android
incomodaba lo suficiente como para poner en riesgo que la app llegara a terminarse. Lo caro
—el backend, los tres specs, las reglas de negocio— no dependía de la plataforma y no se
tocó. Aquel código sigue recuperable en la etiqueta `android-fase-1.1`.

## Por dónde empezar

Se trabaja **una fase por conversación**. El índice dice en cuál estamos y qué leer:

    docs/superpowers/fases/README.md

| Documento | Para qué |
|---|---|
| `docs/superpowers/fases/` | un fichero por fase: qué construir y cuándo está terminada |
| `docs/superpowers/specs/2026-08-10-s-rank-design.md` | el diseño aprobado: arquitectura, fórmulas, sistema de diseño, pantallas |
| `docs/superpowers/specs/2026-08-11-core-ui-design.md` | el sistema de diseño: los once colores con su contraste medido, tipografía, componentes |
| `docs/superpowers/plans/despliegue-fase-1-0.md` | qué hay montado en producción |

| Fase | Estado |
|---|---|
| 1.0 · Backend: MySQL, el Sistema, auth móvil, despliegue | **hecha**, en producción |
| 1.1 · Esqueleto web: navegación, diseño, login, PWA | **en curso**, sin desplegar |
| 1.2 · Entrenamiento, con borrador sin conexión | pendiente |
| 1.3 · Nutrición, agua, suplementos, actividad | pendiente |
| 1.4 · Progreso: historial, calendario, gráficas | pendiente |
| 1.5 · Perfil, logros, administración | pendiente |

## Las dos reglas rectoras

**1 · La estética de terminal es puramente visual.** Hay usuarios que no saben qué es una
terminal. Ninguna pantalla puede exigir vocabulario ni modelo mental de shell: todo se
hace pulsando, las palabras van en español llano, y el prompt `israel[E]@s-rank $`, los
comentarios `//` y las casillas `[✓]` son decoración sobre listas y botones normales.

> Si una pantalla solo se entiende sabiendo lo que es una terminal, está mal diseñada.

En HTML eso se traduce en una regla concreta: **la decoración va `aria-hidden` y el estado
se dice con palabras.** Una misión hecha se oye «Beber 2 litros de agua, hecha», nunca
«corchete, marca de verificación, corchete».

**Sin iconos ni emoji en ninguna pantalla.** La referencia visual de
`no_subir_referencias/diseño_buscado/` sí los lleva; aquí se descartaron a propósito. Los
caracteres de dibujo del spec —`▓ ░ ▸ ▾ [✓] $ //`— no son iconos: son decoración de
terminal y van marcados como tal. El único icono del proyecto es el del lanzador, que la
plataforma exige.

**2 · El Sistema no sabe de dominio; los módulos no saben del Sistema.** Los módulos
publican eventos y el Sistema decide qué hacer con ellos. Añadir un módulo en la fase 2
debe ser publicar eventos nuevos, nunca tocar el núcleo.

En el backend **solo se cumple la mitad**:

- **Sí:** los módulos no saben del Sistema. Los controladores publican eventos
  (`app/Events/`) y un único puente, `UpdateSystemProgress`, los traduce a llamadas al
  Sistema. Ningún controlador menciona `App\System`.
- **No:** el Sistema sí sabe de dominio. `QuestService` consulta `MealLog`, `WaterLog`,
  `SupplementLog` y `Workout` directamente, y `SystemService::afterWorkout()` recibe un
  `Workout`. Un módulo nuevo obliga a tocar el núcleo.

No se arregla: para una app personal, ese acoplamiento es más barato que la indirección
que haría falta para quitarlo.

⚠️ **En el frontend esta regla no la vigila nadie.** En Android la garantizaba el grafo de
dependencias de Gradle: `core/system` no podía declarar `feature/*` y de eso se encargaba
el compilador. En una aplicación de Vite no hay módulos que lo impidan, y **no se va a
montar un sistema de módulos falso para fingirlo**. Es disciplina de carpetas y nada más.
Con cinco pantallas eso basta; si algún día no basta, se pone `eslint-plugin-boundaries`,
que es la herramienta que hace ese trabajo de verdad.

**El XP se calcula siempre en el servidor.** La app lo pinta, nunca lo decide.

## Estructura

```
/
  web/               frontend React + TypeScript (Vite)
    src/
      api.ts             la única puerta a la API: peticiones y traducción de errores
      componentes.tsx    los componentes del sistema de diseño, todos juntos
      formato.ts         los cálculos y textos con ramas — es lo que está probado
      estilos.css        los once colores y la escala tipográfica
      pantallas/         Login · Registro · Recuperar · Hoy
      App.tsx            rutas, pestañas y el portero de sesión
    public/          fuentes, iconos, manifiesto y el trabajador de servicio
  backend/           Laravel API-only, YA en producción
  docs/superpowers/  fases · specs · plans
  old/               FitLoop, la app anterior — SOLO LECTURA
```

## Frontend

```bash
cd web
npm run dev        # servidor de desarrollo en localhost:5173
npm test           # los tests
npm run build      # genera dist/ para producción
```

**El proyecto vive en `/mnt/c` y WSL no entrega eventos de `inotify` para el disco de
Windows.** Sin `server.watch.usePolling` en `vite.config.ts`, el servidor de desarrollo no
se entera de ningún cambio y sigue sirviendo el código de cuando arrancó **sin avisar de
nada**. Ya costó un rato: se ve como «la app no aplica cambios» que sí están en disco y sí
compilan. Esa línea no se quita mientras el proyecto siga en `/mnt/c`.

**En desarrollo, Vite hace de proxy** de `/api` y `/sanctum` hacia Laravel en el 8000. Así
el navegador ve un solo origen igual que en producción, y la cookie de sesión funciona sin
CORS. En producción el `dist/` se copia dentro de `backend/public/` y es literalmente el
mismo sitio.

**No hay token en el navegador.** La sesión viaja en una cookie `httpOnly` que JavaScript
no puede leer. Un XSS aquí no tiene nada que robar, y eso importa porque hay datos de salud
detrás. Toda escritura manda `X-XSRF-TOKEN`, que `api.ts` obtiene de `/sanctum/csrf-cookie`.

**El trabajador de servicio solo se registra en el build de producción.** En desarrollo
serviría lo que tuviera guardado y convertiría cualquier cambio en «esto no se aplica».

### Laravel en local

```bash
cd backend
php artisan serve --port=8000
```

Base de datos local `srank_local` en MariaDB, usuario `srank`. El usuario de pruebas es
`isra@local.test` / `contrasena8`. **`backend/.env` es el local**; el de producción vive en
`.env.produccion` y solo lo lee `build-deploy.sh`.

## Backend

Desplegado en `https://s-rank.israelzamora.es`, con 285 tests en verde y los datos reales
en MySQL.

```bash
cd backend
php artisan test                 # la suite entera
php artisan srank:recalculate    # rehace el progreso desde el historial
bash build-deploy.sh             # construye el frontend y genera deploy/ para el FTP
```

Se despliega **solo por FTP** en Ginernet: sin SSH, sin Composer ni Node en el servidor.
Todo se prepara en local. Las migraciones nuevas se ejecutan a mano por phpMyAdmin y se
apuntan en la tabla `migrations`.

Toda escritura que afecte al progreso devuelve un bloque `system` en la misma respuesta,
con el XP ganado, las subidas de nivel, los logros desbloqueados y el progreso actualizado.

## Trampas que ya han costado tiempo

**La suite corre sobre SQLite y sin red.** Los cuatro fallos de la fase 1.0 —incluido uno
que impedía iniciar sesión a todo el mundo— pasaron los 254 tests y solo aparecieron al
tocar el servidor. Si algo depende del tipo de una columna, del transporte de correo o de
la red, un test verde no demuestra nada.

**Y corre con `SESSION_DRIVER=array`,** que no guarda nada entre peticiones. Ningún test
puede demostrar que la cookie de sesión viaje: `SesionWebTest` comprueba a quién se le abre
sesión y a quién no, que es lo único que decide ese código. El ida y vuelta se verifica en
el navegador.

**Y lo que no tiene test no lo cubre nadie.** `/informe-salud` llevaba desde el despliegue
devolviendo 500 con la suite entera en verde: no había un solo test que pidiera esa ruta.
Antes de dar por buena una ruta, comprueba que alguien la llama.

**La ruta de reserva no puede tragarse los 404 de la API.** `routes/web.php` devuelve el
`index.html` para cualquier ruta que no sea un fichero, porque las URLs del frontend las
resuelve React. Si eso alcanzara a `/api/*`, el cliente recibiría una página entera donde
espera JSON. Está probado que sin esa guarda el test cae.

**Las fechas viajan en UTC.** El servidor está en `Europe/Madrid` pero la API serializa
instantes en UTC. Hay que convertir antes de quedarse con un día, o «hoy» cambiará a
medianoche menos dos horas. El `date` de `/api/system/today` ya llega resuelto: se lee y se
escribe en UTC, sin convertir a la zona del navegador.

**Comparar fechas con `whereDate`, no con `where`.** Eloquent las guarda con hora incluida.

**Los mensajes de validación ya salen en español**, en `backend/lang/es/validation.php`.
`APP_LOCALE` por defecto es `es` en `config/app.php`, así que no hay que acordarse de
ponerlo en el `.env` del servidor. `fallback_locale` sigue en `en`: una regla sin traducir
sale en inglés, nunca como `validation.loquesea`.

**Límites de intentos por IP** que te vas a comer al probar auth: login 5/min, registro
3/hora, recuperación 3/hora. En desarrollo, **el navegador y cualquier `curl` comparten el
contador**, porque a través del proxy de Vite Laravel ve todo viniendo de `127.0.0.1`. Se
reinicia sin tocar datos:

```bash
mysql -u srank -psrank srank_local -e "DELETE FROM cache;"
```

## Seguridad

**El repositorio es privado y tiene que seguir siéndolo.** Contiene configuración de
despliegue de Ginernet.

**Nunca se commitea:** `*.sqlite`, `*.sql` ni `.env*`. Llevan dentro datos de salud reales,
hashes de contraseña y credenciales. Están en el `.gitignore` y `build-deploy.sh` aborta
si alguno se cuela en el paquete que se sube por web.

**No inventes mensajes que delaten cuentas.** `forgot-password` responde 200 exista o no el
correo, a propósito, y paga el mismo bcrypt en las dos ramas para que tampoco lo delate el
reloj. La pantalla de recuperar **avanza al paso 2 sin mirar la respuesta**: si solo
avanzara cuando la cuenta existe, la fuga que el servidor evita la reabriría el cliente.

**La CSP de `backend/public/.htaccess` está cerrada a `'self'`.** Sobrescribe la que
inyecta Ginernet, que si no bloquea la aplicación. Si algún día se añade una fuente, un
icono o una librería de fuera, hay que abrirle hueco ahí o el navegador lo bloqueará sin
que se vea nada en la interfaz.

## Cómo trabajar aquí

**Un test que no falla sin el arreglo no vale.** Al corregir un fallo: quita el arreglo,
comprueba que el test falla, restaura. Si pasa en ambos casos, no está probando nada.

**Tests del backend con PHPUnit, no con Pest**, aunque el spec §11 diga Pest. La suite ya
estaba escrita así y no compensa mezclar dos estilos.

**Tests del frontend con Vitest.** Las funciones puras de `formato.ts` no necesitan nada
más que `node:test`, pero las pantallas sí, y tener dos ejecutores sería peor.

**Commits en español**, con el porqué en el cuerpo y no solo el qué.

**`ponytail:`** marca una simplificación deliberada y nombra su techo.

## `old/` — qué es y cómo usarlo

`old/` es FitLoop. **No se modifica.** Es la referencia de las reglas de negocio ya
validadas durante el porte:

| Qué buscar | Dónde |
|---|---|
| Tabla MET por modo, TDEE, racha real | `old/app/Http/Controllers/Api/DashboardController.php` |
| Detección de récords, series por ejercicio | `old/app/Http/Controllers/Api/WorkoutController.php` |
| Mifflin-St Jeor y el asistente nutricional | `old/resources/views/nutrition/dashboard.blade.php` |
| Los 12 logros antiguos | `old/app/Http/Controllers/Api/AchievementController.php` |
| Flujo completo de sesión de entreno | `old/resources/views/training.blade.php` |
| Catálogo de 1.506 alimentos (JSON fuente) | `old/alimentos/` |
| Plantillas de entrenamiento | `old/database/seeders/TemplatesTableSeeder.php` |

Se borra entero al cerrar la fase 1.

⚠️ `old/database/database.sqlite` y `old/database/database.2026-05-backup.sqlite` son la
**única copia** de los datos originales de FitLoop y están excluidos de git. Ya se
migraron a MySQL, pero **hay que respaldarlos fuera del proyecto antes de borrar `old/`**.
