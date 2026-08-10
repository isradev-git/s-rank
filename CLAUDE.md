# CLAUDE.md

Guía para Claude Code (claude.ai/code) al trabajar en este repositorio.

## Idioma
Responde siempre en español.

## Qué es esto

**S-RANK** es una aplicación Android (Kotlin + Jetpack Compose) para hábitos y progreso
personal, con estética de terminal y progresión de videojuego inspirada en *Solo Leveling*.

**El backend está terminado y en producción**; la aplicación Android todavía no existe.

Se trabaja **una fase por conversación**. Empieza siempre por el índice de fases, que dice
en cuál estamos y qué hay que leer:

    docs/superpowers/fases/README.md

El diseño completo y aprobado —arquitectura, fórmulas del sistema de progresión, sistema
de diseño y mapa de pantallas— está en:

    docs/superpowers/specs/2026-08-10-s-rank-design.md

## Restricción rectora

La estética de terminal es **puramente visual**. Hay usuarios que no saben qué es una
terminal. Ninguna pantalla puede exigir vocabulario ni modelo mental de shell: todo se
hace pulsando, las palabras van en español llano, y el prompt `$`, los comentarios `//` y
las casillas `[✓]` son decoración sobre listas y botones normales.

> Si una pantalla solo se entiende sabiendo lo que es una terminal, está mal diseñada.

## Estructura

```
/                    proyecto Gradle de Android (aún por crear, fase 1.1)
  app/               módulo de aplicación
  core/system/       nivel, XP, rango, misiones, logros, estadísticas
  core/ui/           sistema de diseño
  data/              API, sesión, borrador local
  feature/           training · nutrition · progress · profile · auth
  backend/           Laravel API-only, YA en producción
  docs/
    superpowers/fases/   un documento por fase — empieza aquí
    superpowers/specs/   el diseño aprobado
    superpowers/plans/   planes de implementación y registro del despliegue
  old/               FitLoop, la app Laravel anterior — SOLO LECTURA
```

## `old/` — qué es y cómo usarlo

`old/` es FitLoop, la aplicación Laravel + Blade que S-RANK sustituye. **No se modifica.**
Está ahí como referencia de las reglas de negocio ya validadas durante el porte:

| Qué buscar | Dónde |
|---|---|
| Tabla MET por modo, TDEE, racha real | `old/app/Http/Controllers/Api/DashboardController.php` |
| Detección de récords, series por ejercicio | `old/app/Http/Controllers/Api/WorkoutController.php` |
| Mifflin-St Jeor y el asistente nutricional | `old/resources/views/nutrition/dashboard.blade.php` |
| Los 12 logros actuales | `old/app/Http/Controllers/Api/AchievementController.php` |
| Flujo completo de sesión de entreno | `old/resources/views/training.blade.php` |
| Catálogo de 1.506 alimentos (JSON fuente) | `old/alimentos/` |
| Plantillas de entrenamiento | `old/database/seeders/TemplatesTableSeeder.php` |

Se borra entero al cerrar la fase 1.

⚠️ `old/database/database.sqlite` contiene los datos reales de producción y está
excluido de git. Es la única copia: hay que respaldarlo fuera del proyecto antes de
migrar a MySQL.

## Backend

`backend/` es el Laravel de FitLoop reducido a API. **Está terminado y desplegado** en
`https://s-rank.israelzamora.es`, con 273 tests en verde y los datos reales migrados a
MySQL. Qué hay montado y con qué credenciales:

    docs/superpowers/plans/despliegue-fase-1-0.md

Se despliega **solo por FTP** en Ginernet: sin SSH, sin Composer ni Node en el servidor.
Todo se prepara en local con `bash build-deploy.sh` y se sube ya listo. Las migraciones
nuevas se ejecutan a mano por phpMyAdmin y se apuntan en la tabla `migrations`.

Toda escritura que afecte al progreso devuelve un bloque `system` en la misma respuesta,
con el XP ganado, las subidas de nivel, los logros y el progreso actualizado. **El XP se
calcula siempre en el servidor**: la app lo pinta, nunca lo decide.

## Estado

| Fase | Estado |
|---|---|
| 1.0 · Backend | **hecha**, en producción |
| 1.1 · Esqueleto Android | siguiente |
| 1.2 a 1.5 | pendientes |

Los detalles de cada una, en `docs/superpowers/fases/`.
