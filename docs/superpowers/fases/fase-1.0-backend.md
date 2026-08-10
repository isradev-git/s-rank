# Fase 1.0 · Backend — **hecha**

Terminada el 10 de agosto de 2026. La API está en producción en
`https://s-rank.israelzamora.es` con 273 tests en verde.

Este documento existe para que las fases siguientes sepan **qué pueden dar por hecho**.
El registro del despliegue está en `../plans/despliegue-fase-1-0.md` y el plan de
implementación completo en `../plans/2026-08-10-fase-1-0-backend.md`.

## Qué dejó montado

**`backend/`** es el Laravel de FitLoop reducido a API, sobre MySQL, con los datos reales
importados: 18 entrenos, 197 comidas, 159 registros de agua, 1.506 alimentos, 32 plantillas.

**El Sistema** vive en `backend/app/System/`, seis clases:

| Clase | Responsabilidad |
|---|---|
| `Progression` | curva de niveles, rangos, barra de XP |
| `Stats` | las cuatro estadísticas a partir de sus acumuladores |
| `XpLedger` | única puerta por la que se concede XP; aplica los topes diarios |
| `QuestService` | genera y sincroniza las misiones del día |
| `AchievementService` | los 40 logros y la definición de «día activo» |
| `SystemService` | orquesta todo y devuelve el bloque `system` |

**Los eventos** están en `backend/app/Events/`: `WorkoutStored`, `MealLogged`,
`WaterLogged`, `SupplementToggled`, `WeightLogged`. Los publica cada controlador de módulo
y los recoge `app/Listeners/UpdateSystemProgress.php`.

⚠️ Los listeners **no** se registran a mano: Laravel autodescubre los métodos `handle*` de
`app/Listeners`, incluidos los tipos unión. Registrarlos además en el provider disparaba
cada evento dos veces.

**Cuatro tablas nuevas:** `user_progress`, `daily_quests`, `user_achievements`, `xp_events`.

**Constantes de balanceo** en `backend/config/srank.php`. Se pueden reajustar sin publicar
una versión nueva de la app.

## El bloque `system`

Toda escritura que afecte al progreso devuelve esto **en la misma respuesta**, para que la
app no tenga que pedir el progreso otra vez:

```json
{
  "xp_gained": 22,
  "level_up": null,
  "rank_up": null,
  "achievements_unlocked": [{"key":"hydrated","name":"Hidratado","rarity":"common"}],
  "records": [],
  "quests_completed": ["water"],
  "progress": {
    "level": 1, "rank": "E", "xp_total": 22,
    "xp_into_level": 22, "xp_for_next": 100,
    "current_streak": 1, "longest_streak": 1,
    "stats": {"strength":0,"endurance":0,"consistency":1,"vitality":0}
  }
}
```

Lo devuelven `POST /api/workouts`, `/api/water`, `/api/meals`, `PUT /api/supplements` y
`PUT /api/user/profile` cuando cambia el peso.

`level_up` y `rank_up` son `null` o `{"from":1,"to":2}`. Son la señal para animar algo.

## Decisiones que las fases siguientes heredan

**La racha cuenta días activos, no días entrenados.** Cualquier registro la mantiene viva:
entreno, pasos, comida, agua, suplemento o peso. Los logros de racha usan la misma
definición, para que no convivan dos rachas distintas.

**Apuntar los pasos mantiene la racha pero no da XP de entrenamiento** ni cuenta para los
logros de entreno. Hay quien usa la app solo para eso.

**Vitalidad arranca en cero para todos.** Se alimenta de misiones de hábito cumplidas, y
las misiones no existían antes del despliegue.

**Fuerza arranca casi en cero** aunque el historial sea largo: solo hay 7 series con peso
registrado en todo el histórico. No es un fallo, es que esos datos nunca se guardaron.

**Las fechas viajan en UTC.** El servidor está en `Europe/Madrid`, pero la API serializa
instantes en UTC. La app tiene que convertir a Madrid antes de quedarse con un día.

**Los mensajes de validación salen en inglés.** Laravel 12 no trae traducciones al
español. Se resuelve en la fase 1.1 publicando `lang/es`.

## Los cuatro fallos que ninguna prueba detectó

Todos aparecieron al tocar el servidor real, y todos por lo mismo: la suite corre sobre
SQLite y sin red. Sirven de aviso para las fases siguientes.

1. **Sanctum no admitía UUID.** `tokenable_id` es un `bigint` por defecto; los usuarios
   tienen UUID. MySQL truncaba y nadie podía entrar. SQLite no tipa las columnas.
2. **Un 401 salía como 500**, porque el middleware redirigía a una ruta `login` inexistente.
3. **`forgot-password` delataba qué correos tienen cuenta**: 200 si no existía, 500 si sí.
4. **`MAIL_SCHEME=ssl` no existe.** Symfony solo acepta `smtp` y `smtps`.

Cada uno tiene ahora un test que falla si vuelve: `UuidSchemaTest`, `ApiAlwaysJsonTest`,
`RegisterAndResetTest`, `MailSchemeTest`.

## Si hay que tocar el backend desde otra fase

```bash
cd backend
php artisan test                    # 273 tests
php artisan srank:recalculate       # rehace el progreso desde el historial
bash build-deploy.sh                # genera deploy/ para subir por FTP
```

El despliegue es **solo FTP**: sin SSH, sin Composer ni Node en el servidor. Todo se
prepara en local. Las migraciones nuevas hay que ejecutarlas a mano por phpMyAdmin y
apuntarlas en la tabla `migrations`.
