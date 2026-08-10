# CLAUDE.md

Guía para Claude Code (claude.ai/code) al trabajar en este repositorio.

## Idioma

Responde siempre en español. También los mensajes de commit, los comentarios del código y
cualquier texto que vaya a ver el usuario final.

## Qué es esto

**S-RANK** es una aplicación Android (Kotlin + Jetpack Compose) para hábitos y progreso
personal, con estética de terminal y progresión de videojuego inspirada en *Solo Leveling*.
Sustituye a FitLoop, una web Laravel + Blade que sigue en `old/` como referencia.

**El backend está terminado y en producción. La aplicación Android todavía no existe.**

## Por dónde empezar

Se trabaja **una fase por conversación**. El índice dice en cuál estamos y qué leer:

    docs/superpowers/fases/README.md

| Documento | Para qué |
|---|---|
| `docs/superpowers/fases/` | un fichero por fase: qué construir y cuándo está terminada |
| `docs/superpowers/specs/2026-08-10-s-rank-design.md` | el diseño aprobado: arquitectura, fórmulas, sistema de diseño, pantallas |
| `docs/superpowers/plans/despliegue-fase-1-0.md` | qué hay montado en producción |

| Fase | Estado |
|---|---|
| 1.0 · Backend: MySQL, el Sistema, auth móvil, despliegue | **hecha**, en producción |
| 1.1 · Esqueleto Android: navegación, diseño, login | siguiente |
| 1.2 · Entrenamiento, con borrador sin conexión | pendiente |
| 1.3 · Nutrición, agua, suplementos, actividad | pendiente |
| 1.4 · Progreso: historial, calendario, gráficas | pendiente |
| 1.5 · Perfil, logros, administración | pendiente |

## Las dos reglas rectoras

**1 · La estética de terminal es puramente visual.** Hay usuarios que no saben qué es una
terminal. Ninguna pantalla puede exigir vocabulario ni modelo mental de shell: todo se
hace pulsando, las palabras van en español llano, y el prompt `$`, los comentarios `//` y
las casillas `[✓]` son decoración sobre listas y botones normales.

> Si una pantalla solo se entiende sabiendo lo que es una terminal, está mal diseñada.

**2 · El Sistema no sabe de dominio; los módulos no saben del Sistema.** Los módulos
publican eventos y el Sistema decide qué hacer con ellos. Añadir un módulo en la fase 2
debe ser publicar eventos nuevos, nunca tocar el núcleo.

En el backend **solo se cumple la mitad**, y conviene saber cuál antes de copiar el patrón
a Android:

- **Sí:** los módulos no saben del Sistema. Los controladores publican eventos
  (`app/Events/`) y un único puente, `UpdateSystemProgress`, los traduce a llamadas al
  Sistema. Ningún controlador menciona `App\System`.
- **No:** el Sistema sí sabe de dominio. `QuestService` consulta `MealLog`, `WaterLog`,
  `SupplementLog` y `Workout` directamente, y `SystemService::afterWorkout()` recibe un
  `Workout`. Un módulo nuevo obliga a tocar el núcleo.

No se arregla: para una app personal, ese acoplamiento es más barato que la indirección
que haría falta para quitarlo. Lo que no se hace es replicarlo en Android construyendo un
bus de eventos ceremonial encima. En Android la separación que sí sale gratis es la
dirección de dependencias de Gradle: **`core/system` no declara `feature/*`**, y de eso se
encarga el compilador sin que nadie tenga que acordarse.

**El XP se calcula siempre en el servidor.** La app lo pinta, nunca lo decide.

## Estructura

```
/                    proyecto Gradle de Android (aún por crear, fase 1.1)
  app/               navegación y arranque
  core/system/       nivel, XP, rango, misiones, logros, estadísticas
  core/ui/           sistema de diseño
  data/              api · session · draft (Room, solo el borrador de entreno)
  feature/           training · nutrition · progress · profile · auth
  backend/           Laravel API-only, YA en producción
  docs/superpowers/  fases · specs · plans
  old/               FitLoop, la app anterior — SOLO LECTURA
```

## Backend

Desplegado en `https://s-rank.israelzamora.es`, con 273 tests en verde y los datos reales
en MySQL.

```bash
cd backend
php artisan test                 # la suite entera
php artisan srank:recalculate    # rehace el progreso desde el historial
bash build-deploy.sh             # genera deploy/ para subir por FTP
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

**Y lo que no tiene test no lo cubre nadie.** `/informe-salud` llevaba desde el despliegue
devolviendo 500 con la suite entera en verde: no había un solo test que pidiera esa ruta.
Antes de dar por buena una ruta, comprueba que alguien la llama.

**Las fechas viajan en UTC.** El servidor está en `Europe/Madrid` pero la API serializa
instantes en UTC. Hay que convertir antes de quedarse con un día, o «hoy» cambiará a
medianoche menos dos horas.

**Comparar fechas con `whereDate`, no con `where`.** Eloquent las guarda con hora incluida.

**Los mensajes de validación ya salen en español**, en `backend/lang/es/validation.php`.
`APP_LOCALE` por defecto es `es` en `config/app.php`, así que no hay que acordarse de
ponerlo en el `.env` del servidor. `fallback_locale` sigue en `en`: una regla sin traducir
sale en inglés, nunca como `validation.loquesea`.

**Límites de intentos por IP** que te vas a comer al probar auth: login 5/min, registro
3/hora, recuperación 3/hora. El contador vive en la tabla `cache`; `DELETE FROM cache;` lo
reinicia sin tocar datos.

## Seguridad

**El repositorio es privado y tiene que seguir siéndolo.** Contiene configuración de
despliegue de Ginernet.

**Nunca se commitea:** `*.sqlite`, `*.sql` ni `.env*`. Llevan dentro datos de salud reales,
hashes de contraseña y credenciales. Están en el `.gitignore` y `build-deploy.sh` aborta
si alguno se cuela en el paquete que se sube por web.

**No inventes mensajes que delaten cuentas.** `forgot-password` responde 200 exista o no el
correo, a propósito. Un mensaje distinto por caso convierte el endpoint en una lista de
usuarios válidos.

## Cómo trabajar aquí

**Un test que no falla sin el arreglo no vale.** Al corregir un fallo: quita el arreglo,
comprueba que el test falla, restaura. Si pasa en ambos casos, no está probando nada.

**Tests con PHPUnit, no con Pest**, aunque el spec §11 diga Pest. La suite ya estaba
escrita así y no compensa mezclar dos estilos.

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
