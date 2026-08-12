# Fase 1.3 · Nutrición, agua, suplementos y actividad

**Objetivo:** cerrar el otro bloque grande de la app, el de los hábitos diarios. Es lo que
más se usa: de los datos reales, la usuaria principal tiene 182 comidas y 148 registros de
agua frente a 3 entrenos.

## Qué existe cuando empieza

De la **1.1**: el sistema de diseño y la navegación.
De la **1.2**: la sección de entreno dentro de `hoy`, y el patrón de repositorio ya
asentado.

## Qué hay que construir

### `feature/nutrition/`

**Nutrición del día** — las tres comidas más los tentempiés, con el total de macros y
cuánto queda para el objetivo.

**Añadir comida** — buscador sobre el catálogo de **1.506 alimentos** ya importados. Se
elige alimento y cantidad en gramos; los macros se calculan solos. También se puede meter
una entrada manual escribiendo los macros a mano.

**Crear alimento** — para lo que no está en el catálogo. Admite foto.

**Recetas** — crear, ver, usar. Admite foto.

**Objetivo nutricional con asistente** — Mifflin-St Jeor. La fórmula y el asistente están
resueltos en `old/resources/views/nutrition/dashboard.blade.php`; hay que portarlos, no
reinventarlos.

### Agua

Registro por vasos con incrementos rápidos, barra de bloques `[▓▓▓▓▓░░░░░]` y objetivo
configurable. Cada registro devuelve el bloque `system`: al llegar al objetivo se completa
la misión y salta el logro «Hidratado».

### Suplementos

Casillas para los cuatro: multivitaminas, omega 3, vitamina D3 y magnesio. La misión de
suplementos se cumple **con los cuatro marcados**, no con uno.

### Actividad diaria

Pasos y calorías. Ojo: **los pasos se guardan como un `workout` de modo `pasos`**, no en
una tabla aparte. No dan XP de entrenamiento ni salen en el historial de entrenos, pero sí
mantienen la racha.

### Peso

Apuntar el peso de hoy. Va por `PUT /api/user/profile`, y cuando el peso cambia la
respuesta trae el bloque `system` porque puede completar una misión.

## Endpoints que consume

| | |
|---|---|
| `GET /api/meals?date=` · `POST /api/meals` · `DELETE /api/meals/{uuid}` | comidas — el POST devuelve `system` |
| `GET /api/meals/history?days=` | historial de calorías por día |
| `GET /api/foods` · `GET /api/foods/all` · `GET /api/foods/categories` | catálogo |
| `POST /api/foods` · `PUT` · `DELETE` · `POST /api/foods/{id}/image` | alimentos propios |
| `GET /api/recipes` · `POST` · `GET/DELETE /{id}` · `POST /{id}/image` | recetas |
| `GET /api/recipes/recommended` | sugerencias |
| `GET /api/nutrition/goal` · `PUT` · `POST` | objetivo nutricional |
| `GET /api/water` · `POST` · `DELETE /{id}` · `PUT /api/water/goal` | agua — el POST devuelve `system` |
| `GET /api/supplements` · `PUT` · `DELETE` | suplementos — el PUT devuelve `system` |
| `GET /api/activity` · `PUT` | pasos y calorías |
| `PUT /api/user/profile` | peso — devuelve `system` si el peso cambia |

Las imágenes van a `public/uploads` por un disco propio, **sin symlink**: Ginernet no
permite crearlos.

## Reglas del Sistema que afectan aquí

**Misiones de hábito** — agua, proteína, peso, tres comidas y suplementos. Son las que
alimentan **Vitalidad**, la única estadística que empezó en cero para todo el mundo porque
las misiones no existían antes.

**Recompensas:** agua 20 XP · proteína 30 · peso 10 · tres comidas 20 · suplementos 15.
Completar **todas** las obligatorias del día da 40 XP más, una sola vez.

**Solo hay una misión rotativa al día** entre peso, tres comidas y suplementos, elegida de
forma determinista a partir de (usuario, fecha). No aparecen las tres a la vez.

**No hay misión de fibra**, aunque el alimento la registre.

**La misión de proteína solo existe si el usuario tiene objetivo nutricional.** Si no lo
tiene, no aparece — y ese es un buen momento para invitarle a configurarlo.

Todo esto lo calcula el servidor. La app lo pinta.

## Restricciones

**Registrar una comida tiene que costar poquísimo.** Es la acción más repetida de la app:
tres o cuatro veces al día, todos los días. Cada toque de más se paga multiplicado.

**El buscador va contra 1.506 alimentos.** Búsqueda por servidor con debounce; no te
traigas el catálogo entero al móvil.

**Nada de jerga.** «Proteínas», «grasas», «hidratos». Ni `kcal/100g` sin explicar ni
abreviaturas de tabla nutricional.

**Las fotos pesan.** Redimensionar y comprimir antes de subir: hay usuarios con datos
móviles limitados y el hosting es compartido.

## Terminado cuando

- [ ] Se registra una comida del catálogo en menos de cinco toques.
- [ ] Se puede crear un alimento propio con foto.
- [ ] El agua se suma con incrementos rápidos y la barra se mueve.
- [ ] Llegar al objetivo de agua completa la misión y lo anuncia.
- [ ] Los cuatro suplementos marcados completan su misión.
- [ ] El asistente nutricional calcula el objetivo con Mifflin-St Jeor.
- [ ] Las recetas se crean y se usan.
- [ ] Los pasos se guardan y mantienen la racha sin dar XP de entreno.
- [ ] Apuntar el peso completa su misión cuando toca.
- [ ] Hay tests de las pantallas y de las funciones con ramas.

## Prompt para arrancar el chat

```
Vamos con la fase 1.3 de S-RANK: nutrición, agua, suplementos y actividad.

Lee primero, en este orden:
  docs/superpowers/fases/fase-1.3-nutricion.md
  docs/superpowers/specs/2026-08-10-s-rank-design.md  (§6.5, §8)
  docs/superpowers/fases/fase-1.0-backend.md

Reglas de negocio ya validadas en old/ — SOLO LECTURA:
  old/resources/views/nutrition/dashboard.blade.php    Mifflin-St Jeor y el asistente
  old/app/Http/Controllers/Api/DashboardController.php tabla MET y TDEE

Registrar una comida es la acción más repetida de la app. Quiero que empecemos
por hacer esa pantalla lo más barata posible en toques.
```
