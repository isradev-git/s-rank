# S-RANK · Las fases, una por chat

Cada fase se trabaja en una conversación nueva. Este directorio contiene un documento por
fase con todo lo que hace falta para arrancar de cero: qué existe ya, qué hay que
construir, qué endpoints se consumen y cuándo se puede dar por terminada.

## Estado

| Fase | Contenido | Estado |
|---|---|---|
| [1.0](fase-1.0-backend.md) | Backend: MySQL, el Sistema, auth móvil, despliegue | **hecha** — en producción |
| [1.1](fase-1.1-esqueleto.md) | Esqueleto Android: navegación, diseño, login y registro | siguiente |
| [1.2](fase-1.2-entrenamiento.md) | Entrenamiento, con borrador sin conexión | pendiente |
| [1.3](fase-1.3-nutricion.md) | Nutrición, agua, suplementos, actividad, recetas | pendiente |
| [1.4](fase-1.4-progreso.md) | Historial, calendario, gráficas, récords | pendiente |
| [1.5](fase-1.5-perfil.md) | Perfil, logros, informe de salud, administración | pendiente |

El orden no es negociable: 1.1 define el aspecto de todo lo demás, y 1.4 vive de los datos
que producen 1.2 y 1.3.

## Cómo arrancar un chat de fase

Abre una conversación nueva en la raíz del proyecto y pega el prompt que hay al final del
documento de esa fase. Cada uno lleva ya las rutas de lo que hay que leer primero.

Lo que Claude leerá siempre, sin que se lo pidas, está en `CLAUDE.md`. Los tres documentos
que gobiernan el proyecto entero son:

| Documento | Qué contiene |
|---|---|
| `docs/superpowers/specs/2026-08-10-s-rank-design.md` | El diseño aprobado: arquitectura, fórmulas, sistema de diseño, mapa de pantallas |
| `docs/superpowers/plans/despliegue-fase-1-0.md` | Qué hay montado en producción y con qué credenciales |
| Este directorio | Qué toca en cada fase |

## Las tres reglas que no cambian entre fases

**1 · La estética de terminal es decoración.** Ninguna pantalla puede exigir vocabulario
ni modelo mental de shell. El `$`, los `//` y los `[✓]` van encima de listas y botones
normales. Si una pantalla solo se entiende sabiendo qué es una terminal, está mal
diseñada. Hay usuarios que no saben lo que es una terminal.

**2 · El Sistema no sabe de dominio y los módulos no saben del Sistema.** Los módulos
publican eventos; el Sistema decide qué hacer con ellos. Añadir un módulo en la fase 2
debe ser publicar eventos nuevos, no tocar el núcleo. Esta separación ya existe en el
backend (`backend/app/System/` y `backend/app/Events/`) y hay que repetirla en Android
(`core/system/` y `feature/*`).

**3 · Todo el XP se calcula en el servidor.** La app nunca decide cuánto XP vale algo:
lo pinta. El cliente enseña lo que el bloque `system` de la respuesta le diga.

## Cómo se escribe aquí

En español llano. Sin anglicismos innecesarios y sin jerga de terminal en nada que vea el
usuario. Los textos de la interfaz se escriben pensando en quien no sabe qué es una API:
«No hay conexión, lo guardamos y lo subimos luego», nunca «Error 503».

## Convenciones heredadas de la fase 1.0

- **Tests con PHPUnit, no con Pest**, aunque el spec §11 diga Pest. El proyecto ya tenía
  la suite escrita en PHPUnit y no compensa mezclar dos estilos.
- **Un test que no falla sin el arreglo no vale.** Cuando se corrige un fallo, se quita el
  arreglo, se comprueba que el test falla, y se restaura. La fase 1.0 tuvo cuatro fallos
  que la suite entera no detectaba.
- **Commits en español**, con el porqué en el cuerpo y no solo el qué.
- **`ponytail:`** marca una simplificación deliberada y nombra su techo.
