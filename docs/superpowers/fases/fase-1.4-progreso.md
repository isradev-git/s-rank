# Fase 1.4 · Progreso

**Objetivo:** enseñar lo que el usuario lleva hecho. Historial, calendario, gráficas y
récords.

Va después de 1.2 y 1.3 porque **vive de los datos que producen ellas**. Antes de esas dos
fases no hay nada que dibujar.

## Qué existe cuando empieza

De la **1.2**: entrenos y récords.
De la **1.3**: comidas, agua, suplementos, pasos y pesos.
De la **1.1**: la pestaña `progreso`, que hasta ahora estaba vacía.

## Qué hay que construir

### `feature/progress/`

**Historial de entrenos** — con filtros por modo, rango de fechas y búsqueda por nombre de
ejercicio o notas. Paginado.

**Detalle de entreno** — sus series, sus récords si los hubo, sus notas.

**Calendario** — vistas de semana, mes y año.

**Heatmap anual** — un cuadro por día activo, al estilo del de GitHub. Aquí «día activo»
significa lo mismo que en el resto del Sistema: cualquier registro, no solo entrenar.

**Progreso por ejercicio** — cómo ha evolucionado el peso de un ejercicio concreto.

**Récords personales** — el mejor peso de cada ejercicio y cuándo se logró.

**Historial nutricional** — calorías y macros por día.

**Gráfica de peso** — la evolución del peso corporal.

**Informe de salud con PDF compartible** — existe en FitLoop como página web
(`backend/routes/web.php` la conservó). Decide si la app la abre en un navegador embebido
o si se reimplementa nativa. Abrirla es mucho más barato y probablemente suficiente.

## Endpoints que consume

| | |
|---|---|
| `GET /api/workouts` | historial con `mode`, `date_from`, `date_to`, `search`, `per_page`, `all` |
| `GET /api/workouts/{id}` | detalle con sus series |
| `GET /api/stats` | resumen general |
| `GET /api/stats/calendar` | datos del calendario |
| `GET /api/stats/heatmap` | heatmap anual |
| `GET /api/stats/reports` | informe de salud |
| `GET /api/exercises/progress` | evolución de un ejercicio |
| `GET /api/exercises/records` | récords personales |
| `GET /api/exercises/history` | historial por ejercicio |
| `GET /api/meals/history?days=` | historial nutricional |
| `GET /api/user/weight-history` | evolución del peso |

Todos son de lectura: esta fase no escribe nada.

## Lo que vas a encontrar en los datos reales, y no es un fallo

Conviene saberlo antes de dibujar nada, porque una gráfica vacía parece un error de la app
cuando en realidad es un dato que nunca existió.

**Solo hay 7 series con peso en todo el histórico.** El almacenamiento por serie llegó a
FitLoop muy tarde (commit `aa2d709`). Los entrenos anteriores guardaron duración y modo,
nunca peso ni repeticiones. Consecuencia: **la gráfica de progreso por ejercicio va a
estar casi vacía** y los récords personales son poquísimos.

**De los 18 entrenos del histórico, 15 son de modo `pasos`.** Solo hay 3 entrenamientos de
verdad, y dos duran 1 y 3 minutos. El historial de entrenos se va a ver muy corto.

**El grueso de los datos es nutricional**: 197 comidas y 159 registros de agua. El
calendario y el heatmap sí van a estar llenos, porque cuentan días activos.

**Fuerza está casi en cero** por lo mismo. Si en la ficha aparece baja, no es un fallo del
cálculo: son kilos que nunca se registraron.

Todo esto pide **estados vacíos que expliquen**, no un hueco en blanco. «Todavía no hay
suficientes datos para dibujar esto» dice mucho más que una gráfica sin líneas.

## Restricciones

**Solo lectura, y todo requiere conexión.** Nada de esto se guarda en local: el único caso
sin conexión de la app es la sesión de entreno.

**Las gráficas también obedecen al sistema de diseño.** Sin librerías que traigan su propia
paleta. El cian sigue reservado a las ventanas del Sistema; no aparece en una gráfica.

**Cuidado con la paginación.** El historial puede crecer mucho. `per_page` está limitado a
100 en el servidor.

**Las fechas llegan en UTC.** Agrupar por día después de convertir a `Europe/Madrid`, o el
calendario y el heatmap saldrán descuadrados en las horas de la noche.

## Terminado cuando

- [ ] El historial se filtra por modo, fechas y búsqueda, y pagina.
- [ ] Se abre el detalle de un entreno con sus series.
- [ ] El calendario funciona en semana, mes y año.
- [ ] El heatmap anual pinta los días activos.
- [ ] La gráfica de un ejercicio se ve, y explica cuando no hay datos.
- [ ] Los récords personales se listan con su fecha.
- [ ] El historial nutricional y la gráfica de peso se ven.
- [ ] El informe de salud se abre y se puede compartir.
- [ ] Cada pantalla tiene un estado vacío que explica en castellano.
- [ ] Hay tests de ViewModel.

## Prompt para arrancar el chat

```
Vamos con la fase 1.4 de S-RANK: progreso, historial y gráficas.

Lee primero, en este orden:
  docs/superpowers/fases/fase-1.4-progreso.md
  docs/superpowers/specs/2026-08-10-s-rank-design.md  (§7, §8, §10)
  docs/superpowers/fases/fase-1.0-backend.md

Ten presente que el histórico real tiene muy pocos datos de series: 7 en total.
Los estados vacíos importan tanto como las gráficas, porque casi todo va a
estar vacío al principio y no quiero que parezca un fallo.
```
