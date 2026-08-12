# S-RANK — Diseño de la fase 1

**Fecha:** 2026-08-10
**Estado:** aprobado, pendiente de plan de implementación
**Sustituye a:** FitLoop (Laravel + Blade)

---

## 1. Qué estamos construyendo

> ⚠️ **Este documento se escribió para Android.** El frontend pasó a web (React + Vite,
> instalable como PWA) el 12 de agosto de 2026, ya empezada la fase 1.1. Las decisiones de
> producto —fórmulas, XP, misiones, logros, pantallas, textos— no cambiaron y siguen siendo
> la referencia. Lo que sí cambió está corregido más abajo; el porqué está en
> `../fases/fase-1.1-esqueleto.md`.

S-RANK es una aplicación web que gobierna hábitos y progreso personal, con estética
de terminal y progresión de videojuego inspirada en *Solo Leveling*. La **fase 1** consiste
en portar la funcionalidad completa de FitLoop —entrenamiento, nutrición, hidratación,
suplementos, actividad, peso, estadísticas, logros e informe de salud— y construir encima
el sistema de progresión.

No es una refactorización de FitLoop. Es una aplicación nueva que conserva el backend,
los datos y las reglas de negocio ya validadas.

**Lo que hace única a la app:** el usuario registra lo que come y lo que entrena
exactamente igual que antes, y la progresión sube sola. La gamificación no añade
ni un solo paso de trabajo.

### Restricción rectora

La estética de terminal es **puramente visual**. Entre los usuarios reales hay gente que
no sabe qué es una terminal. Ninguna pantalla puede exigir vocabulario ni modelo mental
de shell. Todo se hace pulsando; las palabras van en español llano; el prompt `$`, los
comentarios `//` y las casillas `[✓]` son decoración sobre listas y botones normales.

> Si una pantalla solo se entiende sabiendo lo que es una terminal, está mal diseñada.

---

## 2. Decisiones cerradas

| Decisión | Elección | Motivo |
|---|---|---|
| Nombre | **S-RANK** | Referencia a *Solo Leveling*; se entiende sin conocer la obra |
| Plataforma | **React + TypeScript con Vite**, instalable como PWA | La UI es texto monoespaciado y filas, que es lo que un navegador hace sin pelearse con nadie. Antes fue Kotlin + Compose por el mismo argumento, y resultó valer más aquí |
| Backend | El Laravel actual, en modo API-only | Ya expone 50+ endpoints JSON con Sanctum Bearer |
| Hosting | Ginernet, subdominio `rank-s.israelzamora.es` | Deja `fitloop.israelzamora.es` vivo durante la migración |
| Base de datos | **MySQL** (hoy SQLite) | Ver §5.1 — bloqueante |
| Usuarios | Varios, cada uno en su móvil | Requiere registro y recuperación de contraseña |
| Sesión | Cookie `httpOnly`, sin token en el navegador | Hay datos de salud detrás: un token en `localStorage` convierte cualquier XSS en robo de cuenta |
| Offline | Solo la sesión de entreno en curso | Cubre el caso que duele (sótano sin cobertura) sin pagar el coste de la sincronización bidireccional |
| Progresión | XP, niveles, rangos E→S, 4 estadísticas, misiones diarias, 40 logros | §6 |
| Cálculo de XP | En el servidor, nunca en el cliente | Fuente de verdad, no trampeable, y permite reajustar el balanceo sin que nadie tenga que actualizar nada |
| Navegación | 3 pestañas de texto: `hoy · progreso · perfil` | Genéricas a propósito: siguen valiendo cuando la app deje de ser solo deporte |
| Interacción | Solo pulsar. Sin comandos escritos | Restricción rectora |

### Pantallas que se conservan y que se dejan fuera

**Dentro:** todo FitLoop, más recetas, informe de salud (con PDF compartible desde el
móvil), panel de administración y cronómetro.

**Fuera:** la calculadora de 1RM.

---

## 3. Estructura del repositorio

```
/
  web/                  frontend React + TypeScript (Vite)
    src/
      api.ts            la única puerta a la API: peticiones y errores traducidos
      componentes.tsx   el sistema de diseño: tokens aplicados y componentes
      formato.ts        los cálculos y textos con ramas — lo que se prueba
      estilos.css       los once colores y la escala tipográfica
      pantallas/        una por pantalla
      App.tsx           rutas, pestañas y el portero de sesión
    public/             fuentes, iconos, manifiesto, trabajador de servicio
  backend/              el Laravel API-only que se sube a Ginernet
  docs/                 specs y planes
  old/                  FitLoop actual, solo lectura
```

El borrador de entreno sin conexión (fase 1.2) irá en IndexedDB, no en Room. Es el único
punto donde la web da más trabajo que Android.

`old/` se crea con `git mv` para conservar el historial y se borra al cerrar la fase 1.
Mientras dure el porte es la referencia de las reglas de negocio ya validadas: tabla MET
por modo, Mifflin-St Jeor, detección de récords, agrupación de series, cálculo de racha.

`.gitignore` usa patrones anclados (`/vendor`, `/deploy`, `/node_modules`). Tras la
reorganización dejan de aplicar a `old/vendor` y `backend/deploy`; hay que reescribirlos
sin ancla o duplicarlos por ruta.

---

## 4. Arquitectura del frontend

### 4.1 Núcleo y módulos

La aplicación son dos cosas separadas que se comunican en una sola dirección.

**El Sistema** sabe de niveles, XP, rangos, misiones, estadísticas, rachas y logros. No
sabe qué es una sentadilla ni una caloría.

**Los módulos** saben de su dominio y no saben nada del Sistema. Publican eventos; el
Sistema decide qué hacer con ellos.

⚠️ En Android esta separación la vigilaba el grafo de dependencias de Gradle:
`core/system` no podía declarar `feature/*`, y de eso se encargaba el compilador. **En web
no la vigila nadie**, y no se va a montar un sistema de módulos falso para fingirlo. Con
cinco pantallas basta la disciplina de carpetas; cuando no baste, la herramienta es
`eslint-plugin-boundaries`. Donde de verdad se calcula todo es en el backend, y allí la
separación sí existe (§5.3).

```
módulo                →  evento               →  el Sistema decide
──────────────────────────────────────────────────────────────────────
Entrenamiento         →  EntrenoCompletado    →  XP · Fuerza · misión «entrenar» · logros
Entrenamiento         →  RecordBatido         →  XP · ventana del Sistema · logros
Nutrición             →  ComidaRegistrada     →  misión de proteína / calorías · Vitalidad
Nutrición             →  AguaAñadida          →  misión de hidratación · Vitalidad
Perfil                →  PesoApuntado         →  misión diaria
(fase 2) Estudio      →  SesionEstudio        →  XP · Inteligencia
```

Añadir un módulo nuevo en la fase 2 es publicar eventos nuevos y declarar qué misiones
puede generar. **El núcleo no se toca.** Esa es la razón de que exista esta separación:
hace barata la fase 2 en lugar de convertirla en una refactorización.

La misma separación existe en el backend, donde los eventos son eventos de Laravel
(§5.3) y es donde de verdad se calcula todo.

### 4.2 Capas

- **Pantalla** — un componente de React por pantalla, con su estado de carga, su fallo y su
  botón de reintentar. Sin lógica de negocio.
- **`api.ts`** — la única puerta a la API. Traduce los errores una vez y expone funciones
  con nombre, no rutas sueltas por ahí.
- **`formato.ts`** — los cálculos y textos que tienen ramas de verdad, fuera de los
  componentes para poderse probar sin montar React. Es lo que está probado.
- **Sesión** — una cookie `httpOnly` que pone el servidor. **No hay token en el navegador**:
  con datos de salud detrás, un token en `localStorage` convierte cualquier XSS en robo de
  cuenta.

---

## 5. Backend

Se conserva el Laravel actual. Se eliminan las 20 vistas Blade, `public/assets/` y las
rutas web salvo la del informe de salud, que sigue existiendo como página para poder
pasarle un enlace al médico. El 90% de la API existente se reutiliza sin cambios.

### 5.1 Los cuatro arreglos obligatorios

Van antes que cualquier código de la aplicación. Los cuatro son bloqueantes.

**1 · SQLite → MySQL.** `build-deploy.sh` copia `database/database.sqlite` dentro de
`deploy/`, de modo que cada subida por FTP sobrescribe los datos de producción con los
del portátil. Hoy no se nota porque solo hay un usuario real; con varios es pérdida de
datos garantizada. Además, SQLite bloquea el fichero entero en cada escritura: varios
móviles registrando series a la vez producen `database is locked`. MySQL vive fuera del
árbol FTP, así que los despliegues dejan de tocar los datos.

**2 · `build-deploy.sh` está roto.** El commit `60d42c4` eliminó `.env_produccion`, y el
script sigue ejecutando `cp .env_produccion deploy/.env`. Con `set -euo pipefail`, el
build falla. Hay que regenerar ese fichero fuera de git, ahora con credenciales de MySQL.

**3 · El symlink de imágenes.** `FoodController::uploadImage` y `RecipeController` usan
`Storage::disk('public')`, que requiere `public/storage → storage/app/public`. Sin SSH no
se puede crear con `artisan storage:link`. Se resuelve cambiando el disco a
`public/uploads`, que no depende de symlinks ni de que el hosting los permita.

**4 · Alta y recuperación de cuenta.** Hoy el registro público está desactivado y no
existe recuperación de contraseña. Con usuarios reales en sus propios móviles, ambas
cosas son imprescindibles.

### 5.2 Tablas nuevas

```
user_progress            una fila por usuario
  user_id                FK única
  level                  int, por defecto 1
  xp_total               int, por defecto 0
  strength_acc           decimal   kg totales movidos
  endurance_acc          decimal   minutos de cardio y natación
  consistency_acc        decimal   días activos y misiones cumplidas
  vitality_acc           decimal   agua, proteína, fibra, suplementos
  current_streak         int
  longest_streak         int
  last_active_date       date, nullable

daily_quests
  user_id, date, quest_key          único en conjunto
  target                 decimal    p. ej. 130 (g de proteína)
  progress               decimal
  xp_reward              int
  is_optional            bool
  completed_at           datetime, nullable

user_achievements
  user_id, achievement_key          único en conjunto
  unlocked_at            datetime

xp_events                            el libro mayor
  user_id, date
  source                 workout | record | quest | quest_bonus | streak
  source_id              nullable
  amount                 int
```

`xp_events` es lo que hace que el sistema sea auditable: aplica los topes diarios (§6.3),
permite recalcular el progreso entero desde cero, y evita que un fallo de balanceo obligue
a resetear a los usuarios. El nivel y el rango **no se almacenan como verdad**: se derivan
de `xp_total`, y se cachean en `user_progress` solo para no recalcular en cada lectura.

### 5.3 Cómo se disparan los eventos

Los controladores existentes no cambian su contrato; disparan un evento de Laravel al
guardar. Un listener actualiza el progreso y devuelve las recompensas **en la misma
respuesta**, para que la app pueda mostrar la ventana del Sistema al instante sin una
segunda llamada:

```json
POST /api/workouts  →  201
{
  "workout": { ... },
  "system": {
    "xp_gained": 80,
    "level_up":  { "from": 12, "to": 13 },
    "rank_up":   null,
    "achievements_unlocked": [ { "key": "workouts_50", "name": "Medio Centenar", "rarity": "epic" } ],
    "records":   [ { "exercise": "Press banca", "kind": "weight", "value": 80 } ],
    "quests_completed": [ "train" ]
  }
}
```

El bloque `system` se añade igual a `POST /api/meals`, `POST /api/water`,
`PUT /api/supplements` y al registro de peso.

### 5.4 Endpoints nuevos

```
GET  /api/system/today          progreso + misiones del día + entreno sugerido
GET  /api/system/profile        progreso + estadísticas + módulos activos
GET  /api/system/achievements   los 40, con estado y rareza
POST /api/auth/register
POST /api/auth/forgot-password
POST /api/auth/reset-password
```

`GET /api/system/today` genera las misiones del día si aún no existen, de forma
idempotente: la primera petición de cada día las crea, las siguientes las leen.
No hace falta cron, que en un hosting compartido es poco fiable.

---

## 6. Las reglas del Sistema

### 6.1 Niveles y rangos

**XP para pasar del nivel N al N+1 = `100 + 40 × (N − 1)`**

Lineal, no exponencial, a propósito: con curva exponencial a partir del nivel 30 haría
falta un mes por nivel y la app se abandona. Así se mantiene un nivel cada 5–10 días
indefinidamente.

XP acumulado necesario para alcanzar el nivel N: `100(N−1) + 20(N−1)(N−2)`.

| Rango | Niveles | XP acumulado | Se alcanza en |
|---|---|---|---|
| E | 1 – 14 | 0 | de salida |
| D | 15 – 24 | 5.040 | ~7 semanas |
| C | 25 – 34 | 13.440 | ~4,5 meses |
| B | 35 – 44 | 25.840 | ~8,5 meses |
| A | 45 – 54 | 42.240 | ~14 meses |
| S | 55 y superiores | 62.640 | ~21 meses |

La columna de tiempo asume **una media de 100 XP diarios**, que corresponde a un usuario
constante contando los días de descanso (un día activo típico da entre 130 y 215; un día
flojo, entre 20 y 40). Es la cifra a revisar si en algún momento se reajusta el balanceo.

Subir de rango es raro y se celebra a lo grande; subir de nivel ocurre a menudo y da un
chispazo pequeño.

### 6.2 XP por acción

| Acción | XP |
|---|---|
| Entreno completado de 15 minutos o más | 50 |
| Duración: +1 por cada 5 minutos por encima de 15 | hasta +30 |
| Récord personal | 30 |
| Cada misión diaria | 10 – 30 |
| Completar todas las misiones obligatorias del día | +40 |
| Bonus de racha: +2 por día consecutivo | hasta +30 |

### 6.3 Topes anti-abuso

Se aplican en el servidor sobre `xp_events`:

- **Máximo 2 entrenos con XP por día.** El tercero y siguientes se guardan normalmente
  pero no otorgan XP ni estadísticas.
- **Máximo 300 XP por día**, sumando todas las fuentes.

Sin estos topes, registrar entrenos falsos rompe la progresión y la app deja de
significar nada.

### 6.4 Estadísticas

Cuatro, y **suben solas**. No hay puntos que repartir: pedirle a alguien que no juega a
RPGs que elija entre Fuerza y Vitalidad es un muro, y además el sentido de la ficha es
reflejar lo que de verdad se ha hecho.

| Estadística | Acumulador | Alimentada por |
|---|---|---|
| **Fuerza** | kg totales movidos (peso × reps × series) | gimnasio, calistenia |
| **Resistencia** | minutos de natación, cardio y duración acumulada | natación, casa, cardio |
| **Constancia** | días activos + misiones cumplidas | todos los módulos |
| **Vitalidad** | agua, proteína, fibra, suplementos, peso apuntado | nutrición, hábitos |

**`valor = ⌊√(acumulador / K)⌋`**, con `K` propio de cada estadística.

La raíz cuadrada hace que suba rápido al principio —motiva desde el primer día— y cueste
más después, sin llegar nunca a estancarse del todo.

| Estadística | Unidad del acumulador | `K` | Referencia de calibración |
|---|---|---|---|
| Fuerza | kilogramos | 1.500 | 100 entrenos de gimnasio ≈ 18 |
| Resistencia | minutos | 25 | 100 sesiones de 45 min ≈ 13 |
| Constancia | días activos + misiones cumplidas | 1,5 | 200 días y 600 misiones ≈ 23 |
| Vitalidad | objetivos de hábito cumplidos | 2,5 | 300 objetivos ≈ 10 |

Los cuatro valores de `K` viven en configuración del servidor, no en el código de la
app: reajustar el balanceo no puede exigir publicar una versión nueva en Play Store.

### 6.5 Misiones diarias

Se generan a partir de los objetivos reales del usuario, nunca inventadas. **Máximo 4
obligatorias y 1 opcional:** una lista larga desmotiva más de lo que empuja.

```
condicional Entrenar                      +20   ver más abajo
siempre     Beber N litros de agua        +20   su objetivo de hidratación
si aplica   Llegar a N g de proteína      +30   si tiene objetivo nutricional
rotativa    Apuntar el peso               +10
            Registrar las 3 comidas       +20
            Tomar los suplementos         +15
opcional    50 flexiones · 8.000 pasos    +15   bajo el epígrafe «si te sobra tiempo»
```

**El XP de misión se suma al de la acción, no lo sustituye.** Entrenar un día en que la
misión está activa da 50 por el entreno más 20 por la misión. Los topes de §6.3 son lo que
impide que esa suma se desmadre.

**Cuándo aparece la misión «Entrenar»:** cuando el usuario aún no ha alcanzado su meta
semanal (`users.weekly_goal`) en la semana en curso. No hay días concretos asignados
—FitLoop nunca los ha guardado— así que la misión empuja a completar la cuota, no a
entrenar en una fecha determinada.

**La rotativa** elige una de las tres de forma determinista a partir de `(user_id, fecha)`,
para que el resultado sea estable si la petición se repite.

**Zona horaria:** todas las fechas del Sistema —el día de las misiones, la racha, los topes
diarios— se calculan en `Europe/Madrid`, fijada en el servidor. No hay zona horaria por
usuario; si algún día hace falta, se añade sin tocar el resto del diseño.

**No hay castigo por fallar.** Fallar rompe la racha, y eso ya duele lo suficiente. El
castigo funciona con un usuario motivado, pero a alguien que empieza le hace desinstalar
la app la primera semana mala.

### 6.6 Logros

Cuarenta, en cuatro rarezas con color propio. Los 12 actuales se conservan y se
reclasifican.

**Comunes — verde (10).** Primer Paso · Primera Comida · Hidratado · En la Báscula ·
Superación · Rutina Propia · Tres al Día · Constante (3 días seguidos) · En Racha
(10 entrenos) · Suplementado.

**Raras — azul (12).** Constancia (25 entrenos) · Semana de Fuego (7 días seguidos) ·
Nadador · Gym Rat · Casero · Peso Corporal · Explorador (las 4 modalidades) · Semana
Perfecta · Diana (7 días cumpliendo calorías) · Proteico (14 días de proteína) · Bien
Regado (30 días de agua) · Cinco Récords.

**Épicas — morado (10).** Medio Centenar (50 entrenos) · Mes Épico (30 días seguidos) ·
Tonelada (100.000 kg movidos) · Maratón de Piscina (10 km acumulados) · Madrugador
(20 entrenos antes de las 8:00) · Noctámbulo (20 después de las 22:00) · Variado (30
ejercicios distintos) · Chef (10 recetas propias) · Mes Impecable (20 días de un mes con
todas las misiones) · Rango C.

**Legendarias — ámbar (8).** Centurión (100 entrenos) · Veterano (365 entrenos) ·
Imparable (100 días seguidos) · Año Completo (12 meses seguidos con actividad) · Titán
(1.000.000 kg movidos) · Rango A · Rango S · Coleccionista (los otros 39).

Los bloqueados se muestran con su pista visible, no ocultos: saber qué falta es lo que
tira.

### 6.7 La ventana del Sistema

Aparece sola, en cian con brillo, en exactamente cuatro momentos: **subir de nivel**,
**subir de rango**, **desbloquear un logro** y **batir un récord personal**. Si salta por
cualquier otra cosa deja de significar algo.

Fuera de la aplicación, **una única notificación diaria a las 20:00**, y solo si quedan
misiones pendientes. Desactivable desde ajustes.

---

## 7. Sistema de diseño

Definido una vez en `core/ui/`; todas las pantallas se montan con estos componentes.

### Color

| Token | Hex | Uso |
|---|---|---|
| fondo | `#000000` | negro puro |
| superficie | `#0d0d10` | tarjetas y campos |
| líneas | `#1f1f23` | separadores y bordes |
| texto | `#e4e4e7` | contenido |
| apagado | `#52525b` | comentarios `//`, valores secundarios |
| ámbar | `#f59e0b` | marca, acción, XP |
| verde | `#4ade80` | completado |
| azul | `#60a5fa` | información y navegación |
| rojo | `#f87171` | récord, alerta |
| cian | `#22d3ee` | **exclusivo de las ventanas del Sistema** |
| morado | `#a78bfa` | rareza épica |

El cian está reservado. Si aparece en la interfaz normal, deja de significar «premio» y
el momento de recompensa se desactiva.

### Tipografía

JetBrains Mono, **empaquetada dentro de la app**. Nunca la fuente monoespaciada del
sistema: cada fabricante trae una distinta y la estética se rompe.

Escala: 20 título · 16 sección · 13 cuerpo · 11,5 nota · 10,5 etiqueta en versales.

### Componentes

Fila de lista con `[✓]` / `[ ]` · cabecera de sección `▸ TÍTULO   [2 de 4] ▾` ·
comentario `// …` · barra de bloques `[▓▓▓▓▓░░░░░]` para XP y agua · barra continua para
estadísticas y macros · botón con borde visible y 48 px de alto mínimo · ventana del
Sistema con esquinas en ángulo · insignia de rango · rombo de rareza `◆` / `◇`.

### Accesibilidad

Contraste mínimo 4,5:1 sobre negro para todo el texto de contenido (los seis colores de
acento lo cumplen; `#52525b` solo se usa en texto secundario nunca esencial). Objetivos
táctiles de 48 px. La app respeta el tamaño de fuente del sistema: la maquetación no
puede depender de anchos fijos en caracteres.

---

## 8. Mapa de pantallas

**Fuera de pestañas:** login · registro · recuperar contraseña.

**`hoy`** — un único scroll con secciones plegables, como en las capturas de referencia.
Cabecera de progreso (nivel, rango, XP, racha), misiones del día, entreno de hoy,
resumen de nutrición y hábitos.
Sub-pantallas: sesión de entreno activa · elegir entreno · editor de plantillas · resumen
post-entreno · nutrición del día · añadir comida · crear alimento · recetas · agua ·
suplementos · actividad diaria · peso · cronómetro.

**`progreso`** — historial de entrenos con filtros · detalle de entreno · calendario
semana/mes/año · heatmap anual · progreso por ejercicio · récords personales · historial
nutricional · gráfica de peso · informe de salud con PDF compartible.

**`perfil`** — ficha (nivel, rango, estadísticas, módulos activos) · logros · editar
perfil · objetivo nutricional con asistente · ajustes · panel de administración.

Cuando llegue un módulo en la fase 2, **`hoy` gana una sección, no una pestaña.**

---

## 9. Comportamiento sin conexión

Solo la sesión de entreno en curso. Es el caso que duele de verdad: los sótanos de
gimnasio no tienen cobertura.

El borrador se guarda en IndexedDB en cada cambio, en un solo registro con el estado
completo de la sesión. Al terminar se envía entero en un `POST /api/workouts`. Si el envío
falla, se reintenta al recuperar la conexión y la aplicación avisa de que hay un entreno
pendiente de subir.

⚠️ Esto lo diseña la fase 1.2 y **es la parte que se complicó al pasar a web**: en Android
lo resolvían Room y WorkManager, que ya traen persistencia y reintento con retroceso
exponencial. Aquí hay que escribirlo. No se improvisa desde el trabajador de servicio de la
fase 1.1, que a propósito no guarda ninguna respuesta de la API: servir el progreso de ayer
como si fuera el de hoy es peor que decir que no hay conexión.

El resto de la aplicación requiere conexión y muestra un estado de error legible —nunca
un código HTTP— con opción de reintentar.

---

## 10. Migración de datos

Los datos actuales se conservan íntegros: se exportan de SQLite y se importan en MySQL.

Después, un comando de Artisan recalcula el progreso histórico de cada usuario
reproduciendo el libro mayor `xp_events` a partir de los entrenos, comidas, registros de
agua, suplementos y pesos ya existentes. Con 148 entrenos y 197 comidas registradas, al
abrir S-RANK por primera vez el usuario ya está en el nivel que le corresponde, con sus
estadísticas y sus logros desbloqueados.

El comando es idempotente y se puede volver a ejecutar entero si cambia el balanceo.

### Limitación conocida: no hay detalle de series histórico

El almacenamiento por serie se añadió a FitLoop muy tarde (commit `aa2d709`). El conjunto
de datos real tiene **18 entrenos pero solo 7 series registradas**: los entrenos
anteriores guardaron duración y modo, nunca peso ni repeticiones.

Consecuencia para el recálculo: **Fuerza arranca prácticamente en cero** aunque el
historial sea largo, porque su acumulador son los kilos movidos y esos datos no existen.
Resistencia, Constancia y el XP total sí se reconstruyen bien, porque dependen de la
duración y de los días activos, que sí están.

No es un error y no se corrige inventando datos. Pero el comando de recálculo debe
**registrar cuántos entrenos no aportaron kilos**, para que al ver la ficha por primera
vez quede claro que Fuerza está baja por falta de datos antiguos y no por un fallo.

---

## 11. Qué se prueba

**Backend, con Pest.** La curva de nivel y el cálculo de rango en las fronteras · la
generación de misiones (idempotencia, rotación determinista, respeto de los objetivos del
usuario) · los topes diarios de XP · el recálculo histórico contra un conjunto de datos
conocido · el desbloqueo de logros en sus umbrales exactos.

Es la lógica donde un error silencioso corrompe la progresión de todos los usuarios y no
se detecta hasta meses después.

**Frontend, con Vitest.** Los cálculos y textos con ramas —`formato.ts`— y las pantallas
que tienen estados que se pueden equivocar: la de recuperar contraseña, que debe avanzar
igual exista la cuenta o no, y la sesión de entreno con su borrador.

No se prueba el resto de la interfaz. No compensa.

---

## 12. Fuera de alcance

La calculadora de 1RM · los módulos de la fase 2 · la sincronización bidireccional
offline · iOS · widgets de pantalla de inicio · integración con Health Connect · el
castigo por fallar misiones · la paleta de comandos escritos.

Varios de estos se pueden añadir después sin rehacer nada; se dejan fuera para que la
fase 1 termine.

---

## 13. Fases

| | Contenido | Por qué en ese orden |
|---|---|---|
| **1.0** | Reorganizar el repositorio · MySQL · los 4 arreglos · tablas y endpoints del Sistema · auth móvil | Sin esto no hay nada que pintar |
| **1.1** | Esqueleto web: navegación, sistema de diseño, login, registro y PWA | Define el aspecto de todo lo demás |
| **1.2** | Módulo de entrenamiento, incluido el borrador offline | Es el 40% de la app y lo más complejo |
| **1.3** | Nutrición, agua, suplementos, actividad, recetas | El segundo bloque grande |
| **1.4** | Progreso: historial, calendario, gráficas, récords | Vive de los datos de 1.2 y 1.3 |
| **1.5** | Perfil, logros, informe de salud, administración, cronómetro | El resto |

Cada sub-fase tiene su propio plan de implementación.

**Fase 2 en adelante:** módulos nuevos sobre el mismo núcleo. Fuera del alcance de este
documento.
