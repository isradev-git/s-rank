<?php

/*
 * Puente para la raíz del subdominio.
 *
 * En Ginernet el subdominio apunta a la carpeta raíz del proyecto, no a public/. El
 * .htaccess de al lado reescribe todo a public/index.php en un solo salto, y con eso
 * basta... mientras mod_rewrite llegue a verlo. Si Apache resuelve antes el
 * DirectoryIndex de la raíz y encuentra un index.php, ejecuta ese y el rewrite no llega
 * a aplicarse nunca.
 *
 * Ahí estaba el fallo: en la raíz del subdominio quedaba el index.php de FitLoop, que
 * busca la aplicación en ../../data/fitloop. Esa carpeta ya no existe, así que el sitio
 * entero contestaba «No se pudo localizar la raiz de Laravel». Y como el paquete de
 * despliegue no traía ningún index.php de raíz, subirlo por FTP no lo sobrescribía:
 * había que borrarlo a mano, sabiendo que estaba.
 *
 * Este fichero lo sobrescribe y entrega la petición donde toca. `__DIR__` dentro de
 * public/index.php sigue siendo .../public aunque se llegue por aquí —es del fichero
 * incluido, no del que incluye—, así que su `dirname(__DIR__)` cae en esta misma carpeta
 * y encuentra vendor/ y bootstrap/ sin más.
 */

require __DIR__.'/public/index.php';
