# CLAUDE.md

Guía para Claude Code (claude.ai/code) al trabajar en este repositorio.

## Idioma
Responde siempre en español.

## Qué es esto

**S-RANK** es una aplicación Android (Kotlin + Jetpack Compose) para hábitos y progreso
personal, con estética de terminal y progresión de videojuego inspirada en *Solo Leveling*.

El proyecto está **en fase de diseño**: todavía no hay código de Android. El diseño
completo y aprobado está en:

    docs/superpowers/specs/2026-08-10-s-rank-design.md

**Léelo antes de escribir una línea de código.** Contiene la arquitectura, las reglas del
sistema de progresión con sus fórmulas y constantes, el sistema de diseño y el mapa de
pantallas.

## Restricción rectora

La estética de terminal es **puramente visual**. Hay usuarios que no saben qué es una
terminal. Ninguna pantalla puede exigir vocabulario ni modelo mental de shell: todo se
hace pulsando, las palabras van en español llano, y el prompt `$`, los comentarios `//` y
las casillas `[✓]` son decoración sobre listas y botones normales.

> Si una pantalla solo se entiende sabiendo lo que es una terminal, está mal diseñada.

## Estructura

```
/                    proyecto Gradle de Android (aún por crear)
  app/               módulo de aplicación
  core/system/       nivel, XP, rango, misiones, logros, estadísticas
  core/ui/           sistema de diseño
  data/              API, sesión, borrador local
  feature/           training · nutrition · progress · profile · auth
  backend/           Laravel API-only que se despliega en Ginernet
  docs/              specs y planes
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

`backend/` es el Laravel de FitLoop reducido a API. Se despliega **solo por FTP** en
Ginernet (sin SSH, sin Composer ni Node en el servidor), así que todo se prepara en local
y se sube ya listo. Los cuatro arreglos bloqueantes pendientes están en el §5.1 del spec.

## Estado

Fase 1.0 sin empezar. El orden de las sub-fases está en el §13 del spec.
