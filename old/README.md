# Manual de Usuario: FitLoop

Este documento explica cómo poner en marcha la aplicación **FitLoop**, tanto en tu ordenador como en internet (DonDominio).

**Nivel Requerido:** Principiante (No hace falta saber programar).

---

## PARTE 1: En tu Ordenador (Local)

Antes de nada, necesitas tener instalado en tu PC/Mac programas básicos para que funcione una web moderna. Si no los tienes, descárgalos e instálalos (todo siguiente, siguiente, finalizar):

1.  **XAMPP (o Herd/Valet en Mac)**: Para tener PHP.
2.  **Composer**: Busca "Composer Setup" en Google e instálalo.
3.  **Terminal**: En Mac usa la aplicación "Terminal". En Windows usa "PowerShell".

### 1. Preparar la Aplicación
1.  Abre la carpeta del proyecto.
2.  Haz clic derecho en un espacio vacío -> "Abrir en Terminal" (o abre la terminal y arrastra la carpeta dentro escribiendo primero `cd ` y un espacio).
3.  Escribe esto y pulsa ENTER:
    ```bash
    composer install
    ```
    *(Verás letras verdes/blancas descargando cosas. Espera a que termine).*

4.  Configurar el archivo "secreto":
    -   Busca el archivo llamado `.env.example`.
    -   Cópialo y pégalo ahí mismo con el nombre `.env`.
    -   Vuelve a la terminal, escribe esto y pulsa ENTER:
        ```bash
        php artisan key:generate
        ```
    -   *(Debería decir: "Application key set successfully")*.

### 2. Crear la "Memoria" (Base de Datos)
Para que la app recuerde usuarios y entrenamientos:
1.  Ve a la carpeta `database` del proyecto.
2.  Crea un archivo nuevo vacío llamado `database.sqlite` (si no existe ya).
3.  En la terminal, escribe y pulsa ENTER:
    ```bash
    php artisan migrate --seed
    ```
    *(Esto crea las tablas y usuarios de prueba. Si sale todo en verde, perfecto).*

### 3. ¡Arrancar!
En la terminal escribe:
```bash
php artisan serve
```
Te dará una dirección web (ej: `http://127.0.0.1:8000`). Cópiala y pégala en tu navegador. ¡Ya estás dentro!

---

## PARTE 2: Subir a Internet (DonDominio)

Esta es la parte de "publicar" tu web. Necesitarás los datos de acceso a tu panel de DonDominio.

### Paso 1: Preparar el Paquete
En tu ordenador:
1.  Borra (si existen) las carpetas `vendor` y `node_modules` para limpiar.
2.  En la terminal, escribe: `composer install --no-dev --optimize-autoloader`.
3.  Selecciona TODOS los archivos de la carpeta del proyecto y haz un **ZIP** (Click derecho -> Comprimir). Llama al archivo `web.zip`.

### Paso 2: Subir los Archivos
1.  Entra al panel de gestión de archivos de tu hosting (o usa FileZilla si sabes usarlo).
2.  Busca la carpeta llamada `public_html` (o `httpdocs`). **NO LO SUBAS AHÍ DENTRO TODAVÍA**.
3.  Crea una carpeta **AL LADO** de `public_html`, llámala `fitloop_app`.
4.  Entra en `fitloop_app` y sube tu `web.zip`.
5.  Descomprime el ZIP ahí mismo (suele haber un botón "Extraer").

### Paso 3: Hacerla Visible
1.  Entra en tu carpeta `fitloop_app` y busca la carpeta `public`.
2.  Entra en `public`, selecciona **TODO el contenido** y muévelo a la carpeta `public_html` de verdad (la que ve todo el mundo).
3.  Ahora, en `public_html`, busca el archivo `index.php` y dale a "Editar".
4.  Cambia estas dos líneas (al principio del archivo) para decirle dónde se quedaron el resto de archivos:

    **Donde pone:**
    ```php
    require __DIR__.'/../vendor/autoload.php';
    ...
    $app = require __DIR__.'/../bootstrap/app.php';
    ```

    **Cámbialo por:**
    ```php
    require __DIR__.'/../fitloop_app/vendor/autoload.php';
    ...
    $app = require __DIR__.'/../fitloop_app/bootstrap/app.php';
    ```
    *(Básicamente añadimos `/fitloop_app` en medio)*.

### Paso 4: La Base de Datos (Muy Importante)
Aquí es donde guardaremos los datos reales.

**A) En el Panel del Hosting:**
1.  Busca "Bases de Datos MySQL".
2.  Crea una nueva. Apunta estos datos en un papel:
    -   **Nombre BD**: (ej: `dondominio_fitloop`)
    -   **Usuario**: (ej: `usuario_fitloop`)
    -   **Contraseña**: (la que pongas)
3.  Busca "phpMyAdmin" en tu panel y entra.
4.  Selecciona tu base de datos a la izquierda.
5.  Ve a la pestaña **"Importar"**.

**B) Conseguir el archivo para importar:**
1.  En tu ordenador, abre el archivo `.env` y cambia donde pone `DB_CONNECTION=sqlite` por `DB_CONNECTION=mysql` (solo temporalmente).
2.  En la terminal escribe: `php artisan migrate:refresh --seed` (Dará error si no tienes MySQL local, no pasa nada).
    *   *Truco fácil:* Si no tienes MySQL en tu PC, simplemente pide a tu programador el archivo `.sql` de estructura.
    *   *Si tienes MySQL en tu PC:* Exporta tu base de datos local a un archivo `.sql`.
3.  Sube ese archivo `.sql` en el paso A-5 (phpMyAdmin).

### Paso 5: Conectar todo
1.  Ve al gestor de archivos de tu hosting, carpeta `fitloop_app`.
2.  Crea un archivo nuevo llamado `.env`.
3.  Pega dentro esto (rellenando con TUS datos del Paso 4A):

```ini
APP_NAME=FitLoop
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=PON_AQUI_EL_NOMBRE_BD
DB_USERNAME=PON_AQUI_EL_USUARIO
DB_PASSWORD=PON_AQUI_LA_CONTRASEÑA

APP_KEY= (Copia aquí la clave larga que tienes en tu archivo .env local)
```

¡Guardar y listo! Tu web debería funcionar.
