# Fase 1.5 · Perfil, logros y lo que queda

**Objetivo:** cerrar la fase 1. La ficha del usuario, los 40 logros, los ajustes, la
administración y el cronómetro.

Es la fase donde el juego se hace visible: hasta ahora el usuario ganaba XP sin ver dónde
se acumulaba.

## Qué existe cuando empieza

Todo lo demás. Esta es la última.

## Qué hay que construir

### `feature/profile/`

**Ficha** — nivel, rango, barra de XP, racha actual y la más larga, las cuatro
estadísticas y los módulos activos. Es la pantalla que da sentido a todo lo anterior.

**Logros** — los 40, con su estado. Diez comunes, doce raros, diez épicos y ocho
legendarios. Los bloqueados se ven, porque saber qué te falta es la mitad de la gracia.

**Editar perfil** — nombre, edad, sexo, altura, meta semanal, objetivo principal.

**Ajustes** — cerrar sesión, cambiar contraseña, borrar cuenta.

**Panel de administración** — solo si `is_admin`. Listar usuarios, crearlos, borrarlos y
cambiarles la contraseña.

**Cronómetro** — si no lo adelantaste a la 1.2, aquí es donde toca.

### La ventana del Sistema

El spec la define en §6.7 y esta es la fase donde se pule. Es el momento de recompensa:
subir de nivel, cambiar de rango, desbloquear un logro.

**El cian `#22d3ee` es exclusivamente suyo.** Si ha aparecido en cualquier otra parte de
la interfaz durante las fases anteriores, quítalo ahora: es lo que hace que esta ventana
signifique algo.

## Endpoints que consume

| | |
|---|---|
| `GET /api/system/profile` | progreso completo y módulos activos |
| `GET /api/system/achievements` | los 40 con `unlocked`, `rarity`, `description` |
| `POST /api/system/quests/{key}/complete` | marcar a mano una misión opcional |
| `GET /api/profile` · `PUT /api/user/profile` | ficha y edición |
| `PUT /api/user/password` | cambiar contraseña |
| `DELETE /api/user` | borrar la cuenta |
| `GET /api/achievements` | los 12 logros antiguos de FitLoop |
| `GET /api/admin/users` · `POST` · `DELETE /{user}` · `PUT /{user}/password` | administración |

⚠️ `GET /api/achievements` y `GET /api/system/achievements` **no son lo mismo**. El primero
son los 12 logros heredados de FitLoop; el segundo son los 40 del Sistema. Decide cuál
enseñas —seguramente solo el segundo— y si el otro se retira.

## Las reglas de progresión, para pintarlas bien

Las calcula el servidor; la app las enseña. Están aquí para que la ficha se entienda.

**Nivel:** pasar del nivel N al N+1 cuesta `100 + 40 × (N − 1)` XP.

**Rangos:** E del 1 al 14 · D del 15 al 24 · C del 25 al 34 · B del 35 al 44 ·
A del 45 al 54 · S del 55 en adelante.

**Estadísticas:** `⌊√(acumulador / K)⌋`, con K distinto en cada una — Fuerza 1.500 kg,
Resistencia 25 min, Constancia 1,5, Vitalidad 2,5. Crecen despacio a propósito: subir un
punto tiene que costar.

**Las misiones opcionales** («50 flexiones», «8.000 pasos») no se pueden medir solas, así
que las confirma el usuario pulsando. Dan 15 XP. Ese es el único sitio donde el
`POST /api/system/quests/{key}/complete` tiene sentido, y el servidor rechaza con 422
cualquier intento de marcar a mano una obligatoria.

## Qué va a ver el usuario real la primera vez

**Vitalidad en cero**, porque se alimenta de misiones de hábito y las misiones no existían
antes del despliegue. Sube desde el primer día de uso.

**Fuerza casi en cero**, porque su acumulador son los kilos movidos y solo hay 7 series
registradas en todo el histórico.

**Constancia y Resistencia sí reconstruidas**, porque dependen de días activos y duración,
que sí están.

La ficha debería decirlo en una línea. Ver dos estadísticas a cero sin explicación parece
un fallo, y no lo es.

## Restricciones

**Los logros bloqueados se enseñan.** Con su descripción. Un logro oculto no motiva a
nadie.

**El panel de administración solo si `is_admin`.** El servidor lo protege con su propio
middleware, pero la app no debe ni enseñar la entrada.

**Borrar la cuenta pide confirmación de verdad.** Es irreversible y se lleva por delante
todo el historial.

**Nada de jerga.** «Rango», «nivel», «racha» son palabras normales. «Endpoint», «token» y
«caché» no aparecen en pantalla jamás.

## Terminado cuando

- [ ] La ficha enseña nivel, rango, XP, racha y las cuatro estadísticas.
- [ ] Los 40 logros se ven, desbloqueados y bloqueados, con su rareza.
- [ ] Desbloquear un logro dispara la ventana del Sistema.
- [ ] Subir de nivel y cambiar de rango se anuncian.
- [ ] El perfil se edita y se guarda.
- [ ] La contraseña se cambia desde la app.
- [ ] Borrar la cuenta funciona y avisa antes.
- [ ] El panel de administración solo aparece para administradores.
- [ ] El cronómetro funciona.
- [ ] El cian no aparece fuera de las ventanas del Sistema.
- [ ] Hay tests de ViewModel.

## Al cerrar la fase 1

Con esto la aplicación está completa. Quedan tres cosas de limpieza:

**Borrar `old/`.** Es la app Laravel anterior y ya no hace falta. Antes hay que **respaldar
`old/database/database.sqlite` y `old/database/database.2026-05-backup.sqlite` fuera del
proyecto**: son la única copia de los datos originales y no están en git.

**Borrar las cuentas de prueba** que quedaron del despliegue.

**Publicar en Play Store**, que es fase aparte y no está en el spec.

## Prompt para arrancar el chat

```
Vamos con la fase 1.5 de S-RANK, la última: perfil, logros, ajustes y administración.

Lee primero, en este orden:
  docs/superpowers/fases/fase-1.5-perfil.md
  docs/superpowers/specs/2026-08-10-s-rank-design.md  (§6, §7, §8)
  docs/superpowers/fases/fase-1.0-backend.md

Esta es la fase donde el juego se hace visible, así que la ficha y la ventana
del Sistema son lo que más importa. Empecemos por ahí.
```
