# Guía de Archivos del Proyecto FitLoop

Este documento explica **qué hace cada archivo importante** del proyecto para que sepas dónde tocar si quieres cambiar algo.

---

## 🎨 Frontend (Lo que se ve)
Todo lo relacionado con el diseño y el HTML está en la carpeta `resources`.

### Vistas (Pantallas)
Ruta: `resources/views/`
Aquí están las páginas de la aplicación.
-   **`dashboard.blade.php`**: La página de inicio (con el resumen semanal y accesos directos).
-   **`log.blade.php`**: La pantalla de "Registrar Entrenamiento". Aquí está el formulario, el modal de añadir ejercicios y el selector de plantillas.
-   **`history.blade.php`**: La lista de entrenamientos pasados.
-   **`profile.blade.php`**: Perfil de usuario, gráfico de peso y botón de borrar cuenta.
-   **`layouts/app.blade.php`**: El "esqueleto" principal. Contiene la cabecera HTML, la carga de CSS/JS global y el sistema de **Notificaciones (Toasts)**.
-   **`layouts/navbar.blade.php`**: La barra de menú inferior (Inicio, Historial, Perfil).

### Herramientas
Ruta: `resources/views/tools/`
-   **`1rm.blade.php`**: Calculadora de repetición máxima.
-   **`timer.blade.php`**: Temporizador de descanso.
-   **`explore.blade.php`**: Diccionario de ejercicios.

### Estilos (CSS)
Ruta: `public/assets/css/`
Aquí cambiamos colores, tamaños y diseño.
-   **`variables.css`**: **¡IMPORTANTE!** Aquí están los colores (Amarillo FitLoop, negro fondo), fuentes y tamaños. Cambia esto para cambiar el look de toda la app.
-   **`base.css`**: Estilos básicos (títulos h1, resets).
-   **`layout.css`**: Utilidades de estructura (`flex`, `grid`, `hidden`, márgenes).
-   **`components.css`**: Diseño de botones, tarjetas, inputs y modales.

---

## 🧠 Backend (Lógica y Datos)
Todo lo que procesa datos está en la carpeta `app`.

### Controladores (El Cerebro)
Ruta: `app/Http/Controllers/Api/`
Aquí es donde se reciben las peticiones de la web y se decide qué hacer.
-   **`AuthController.php`**: Gestiona el Registro y Login de usuarios.
-   **`WorkoutController.php`**: Guardar, listar y borrar entrenamientos.
-   **`TemplateController.php`**: Gestionar las plantillas (rutinas guardadas).
-   **`ProfileController.php`**: Actualizar perfil, cambiar peso y borrar cuenta.
-   **`ExercisesController.php`**: Devolver la lista de ejercicios disponibles.

### Modelos (La Estructura de Datos)
Ruta: `app/Models/`
Definen cómo son los datos en la base de datos.
-   **`User.php`**: El usuario (nombre, email, peso...).
-   **`Workout.php`**: Un entrenamiento completado.
-   **`Template.php`**: Una plantilla de rutina.

---

## 🛣 Rutas (Direcciones Web)
Ruta: `routes/`
-   **`web.php`**: Define las URL que ves en el navegador (`/login`, `/history`, etc.) y qué vista cargan.
-   **`api.php`**: Define las URL "invisibles" que usa la app para guardar/cargar datos (`/api/workouts`, `/api/login`).

---

## ⚙️ Configuración
-   **`.env`**: Archivo de configuración "secreto" (Claves, conexión a Base de Datos). **No se comparte**.
-   **`database/migrations/`**: Archivos que definen cómo se crean las tablas de la base de datos.
-   **`database/database.sqlite`**: El archivo donde se guardan todos los datos (si usas SQLite).

---
**Resumen Rápido:**
- ¿Quieres cambiar un **color**? -> `public/assets/css/variables.css`
- ¿Quieres cambiar un **texto** de una página? -> `resources/views/`
- ¿Quieres cambiar cómo se **guarda** algo? -> `app/Http/Controllers/Api/`
