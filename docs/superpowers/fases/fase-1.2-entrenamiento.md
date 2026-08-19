# Fase 1.2 · Entrenamiento

**Objetivo:** poder hacer un entreno entero desde el móvil, en un sótano sin cobertura, y
que al terminar el Sistema te diga qué has ganado.

Es el 40% de la aplicación y lo más complejo. También es lo único que funciona sin
conexión.

📐 **El diseño aprobado está en
[2026-08-18-entrenamiento-design.md](../specs/2026-08-18-entrenamiento-design.md).** Manda
él donde discrepe de este documento: el borrador va en `localStorage` y no en IndexedDB, y
el historial de entrenos se mueve a la fase 1.4. El §7 del spec lista las cuatro
desviaciones con su motivo.

## Qué existe cuando empieza

De la **1.0**: la API completa, el Sistema calculando XP y récords en el servidor.

De la **1.1**: el proyecto de Vite en `web/`, el sistema de diseño con todos los
componentes, `api.ts` con la traducción de errores, la sesión por cookie, la navegación de
tres pestañas y las pantallas de autenticación.

⚠️ **El frontend dejó de ser Android el 12 de agosto de 2026.** Todo lo que este documento
diga de Room, WorkManager o módulos de Gradle hay que leerlo como el equivalente en web,
que se detalla más abajo. El porqué está en [fase-1.1](fase-1.1-esqueleto.md).

## Qué hay que construir

### Las pantallas (`web/src/pantallas/`)

**Elegir entreno** — arrancar de una plantilla, repetir el último, o empezar en blanco.
`GET /api/system/today` ya devuelve un `suggested_workout` con el motivo escrito en
castellano («Te faltan 2 entrenos para tu meta de esta semana») y la plantilla sugerida.

**Sesión activa** — la pantalla que de verdad importa. Ejercicios, series, peso,
repeticiones, RPE y descanso. Debe poder usarse con una mano, sudando y con prisa.

**Resumen post-entreno** — duración, volumen, récords batidos y la ventana del Sistema con
el XP ganado.

**Editor de plantillas** — crear, editar y borrar.

**Cronómetro de descanso** — el spec lo pone en la 1.5, pero durante la sesión hace falta.
Decide en el momento si lo adelantas; si lo haces, apúntalo como desviación.

### El borrador sin conexión

**IndexedDB** con un solo registro que guarda el estado completo de la sesión. Se escribe
en cada cambio, no al final.

Al terminar, la sesión entera se manda en un único `POST /api/workouts`. Si el envío falla,
se reintenta al recuperar la conexión y la aplicación avisa de que hay un entreno pendiente
de subir.

⚠️ **Esta es la parte que se complicó al pasar a web, y hay que presupuestarla.** En Android
la resolvían Room y WorkManager, que ya traen persistencia y reintento con retroceso
exponencial. Aquí hay que escribir las dos cosas. Es el único punto de todo el porte donde
la web da más trabajo que Android.

El trabajador de servicio de la fase 1.1 **no sirve para esto** y no hay que estirarlo: a
propósito no guarda ninguna respuesta de la API, porque servir el progreso de ayer como si
fuera el de hoy es peor que decir que no hay conexión.

**El resto de la aplicación requiere conexión.** No se guarda nada más en local. Esto es
deliberado: sincronizar dos fuentes de verdad es donde mueren estos proyectos, y el único
caso que duele de verdad es el gimnasio sin cobertura.

## Endpoints que consume

| | |
|---|---|
| `GET /api/workouts` | historial, con filtros y paginación |
| `POST /api/workouts` | guardar la sesión — **devuelve el bloque `system`** |
| `GET/PUT/DELETE /api/workouts/{id}` | detalle, editar notas y duración, borrar |
| `GET /api/templates` · `POST` · `PUT` · `DELETE /api/templates/{id}` | plantillas |
| `GET /api/exercises` | catálogo |
| `GET /api/exercises/suggestions` | sugerencias al escribir |
| `GET /api/exercises/last-session` | qué levantaste la última vez de ese ejercicio |
| `GET /api/exercises/records` | récords personales |
| `GET /api/system/today` | incluye `suggested_workout` |

### Cómo se manda un entreno

El cuerpo lleva **una fila por serie**, no un número de series. Este formato cambió en el
commit `aa2d709` y es la fuente de todos los payloads antiguos que ya no valen:

```json
{
  "mode": "gym",
  "date": "2026-08-10 18:00:00",
  "duration_minutes": 45,
  "notes": null,
  "exercises": [
    {
      "name": "Press banca",
      "sets": [
        {"weight_kg": 80, "reps": 5, "rpe": 8, "rest_seconds": 180},
        {"weight_kg": 80, "reps": 5}
      ]
    }
  ]
}
```

Modos válidos: `gym`, `home`, `calisthenics`, `swimming`, `pasos`.

La respuesta trae el entreno guardado, `new_records` y el bloque `system`.

## Reglas del Sistema que afectan a esta pantalla

Las calcula el servidor. La app **no las reimplementa**, solo pinta el resultado. Están
aquí para que entiendas lo que ves y no lo tomes por un fallo.

**XP por entreno:** 50 a partir de 15 minutos, +1 por cada 5 minutos de más hasta +30.
Menos de 15 minutos no da XP de entrenamiento.

**Récord:** 30 XP por cada ejercicio en el que superes tu mejor peso.

**Tope: dos entrenos con XP al día.** El tercero se guarda pero no puntúa. Si el usuario
mete tres, el bloque `system` vendrá con `xp_gained: 0` y hay que enseñarlo sin que
parezca un error.

**Tope de 300 XP al día**, todas las fuentes juntas. La última concesión se recorta, no se
rechaza entera.

**Los pasos (`mode: "pasos"`) no son entrenamiento.** No dan XP de entreno, no cuentan
para los logros de entreno y no salen en el historial de entrenos. Sí mantienen la racha.

**Fuerza sube con los kilos movidos:** peso × repeticiones × series. Por eso registrar el
peso de cada serie importa: sin él, la estadística no se mueve.

## Restricciones

**La pantalla de sesión activa se usa en malas condiciones.** De pie, con una mano, con la
pantalla sudada y con prisa entre series. Objetivos táctiles generosos, nada de gestos
finos, y que se vea de un vistazo qué serie toca.

**Sin vocabulario de terminal.** «Serie», «repeticiones», «descanso». Los `[✓]` y los `//`
son decoración encima de controles normales.

**Que no se pierda un entreno jamás.** Es lo único irrecuperable de toda la aplicación: si
se pierde, el usuario ya no se acuerda de lo que levantó. Escribir en IndexedDB en cada
cambio, no al salir.

**Las fechas van en UTC.** Mandar la fecha del entreno en la zona correcta y convertir al
leer.

## Terminado cuando

- [x] Se puede completar un entreno entero en modo avión y se sube solo al recuperar red.
- [x] Matar la app a mitad de sesión no pierde nada.
- [x] Al guardar aparece la ventana del Sistema con el XP y los récords.
- [x] Un récord se anuncia en el momento.
- [x] El tercer entreno del día se guarda y se explica que no puntúa.
- [x] Las plantillas se crean, se editan y se usan.
- [ ] ~~El historial se ve, se filtra y se puede abrir un entreno.~~ **Movido a la 1.4**
      por el §7 del spec: el historial vive de los datos que producen la 1.2 y la 1.3, y
      partirlo en dos fases obligaba a escribir dos veces la misma pantalla.
- [x] Hay tests de las pantallas y del borrador: que sobrevive a recargar y a cerrar.

## Hecha · 19 de agosto de 2026

**155 tests del frontend** y los 285 del backend en verde, `tsc -b`, `vite build` y
`oxlint` limpios. Diecisiete tareas de código en
[el plan](../plans/2026-08-18-fase-1-2-entrenamiento.md).

**Comprobado en el móvil con la PWA instalada**, que es lo único que demuestra los dos
criterios que de verdad importan: `jsdom` no tiene red que cortar ni proceso que matar, así
que la suite en verde no decía nada de ellos. Un entreno entero en modo avión que sube solo
al recuperar la conexión, y matar la aplicación a media sesión sin perder nada.

### Lo que se desvió del plan

Seis correcciones, escritas cada una en su commit `fix(plan 1.2)`. Dos que habrían dolido:

- **El aviso de entrenos pendientes entraba en bucle infinito de peticiones.** El
  `useCallback` de reintentar dependía de `subiendo` y el efecto dependía de él: cada
  cambio de estado relanzaba el efecto mientras quedara algo en la cola.
- **Los tests de la tarea 17 rompían los seis que ya había en `Hoy.test.tsx`.** Un `<Link>`
  fuera de un router revienta al pintar, y `vi.setSystemTime` deja colgados los `findBy…`
  porque Testing Library solo sabe detectar los temporizadores falsos de Jest.

Las otras cuatro: `system.records` no tenía la forma que decía el plan, `entregar` no
distinguía «encolado» de «no se pudo escribir», la ventana del Sistema decía ser modal sin
serlo, y dos botones distintos se llamaban `REINTENTAR` en la misma pantalla.

## Prompt para arrancar el chat

```
Vamos con la fase 1.2 de S-RANK: el módulo de entrenamiento.

Lee primero, en este orden:
  docs/superpowers/fases/fase-1.2-entrenamiento.md
  docs/superpowers/specs/2026-08-10-s-rank-design.md  (§4, §6, §8, §9)
  docs/superpowers/fases/fase-1.0-backend.md

Como referencia de las reglas de negocio ya validadas, old/ tiene la app Laravel
anterior — SOLO LECTURA:
  old/resources/views/training.blade.php                     flujo completo de sesión
  old/app/Http/Controllers/Api/WorkoutController.php         récords y series

Lo que más me importa es que no se pierda nunca un entreno a medias.
Empecemos por ahí.
```
