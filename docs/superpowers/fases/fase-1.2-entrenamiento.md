# Fase 1.2 · Entrenamiento

**Objetivo:** poder hacer un entreno entero desde el móvil, en un sótano sin cobertura, y
que al terminar el Sistema te diga qué has ganado.

Es el 40% de la aplicación y lo más complejo. También es lo único que funciona sin
conexión.

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

- [ ] Se puede completar un entreno entero en modo avión y se sube solo al recuperar red.
- [ ] Matar la app a mitad de sesión no pierde nada.
- [ ] Al guardar aparece la ventana del Sistema con el XP y los récords.
- [ ] Un récord se anuncia en el momento.
- [ ] El tercer entreno del día se guarda y se explica que no puntúa.
- [ ] Las plantillas se crean, se editan y se usan.
- [ ] El historial se ve, se filtra y se puede abrir un entreno.
- [ ] Hay tests de las pantallas y del borrador: que sobrevive a recargar y a cerrar.

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
