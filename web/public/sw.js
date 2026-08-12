/* Trabajador de servicio. Hace dos cosas y ninguna más:
 *
 *   1. Que la app se pueda instalar en el móvil. El navegador exige un fichero como este
 *      con un manejador de `fetch` antes de ofrecer «añadir a la pantalla de inicio».
 *   2. Que abrirla sin conexión enseñe la app diciendo «No hay conexión» en español, y no
 *      la página de error del navegador. El spec §8.4 no admite lo segundo.
 *
 * Lo que NO hace, a propósito: guardar respuestas de la API. Servir el progreso de ayer
 * como si fuera el de hoy es peor que decir que no hay conexión. El borrador sin conexión
 * del entreno es de la fase 1.2 y se diseña entonces, aquí no se improvisa.
 */

const CACHE = "srank-shell-v1";

self.addEventListener("install", (evento) => {
  evento.waitUntil(
    caches
      .open(CACHE)
      .then((c) => c.addAll(["/", "/index.html"]))
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener("activate", (evento) => {
  evento.waitUntil(
    caches
      .keys()
      .then((claves) => Promise.all(claves.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener("fetch", (evento) => {
  const peticion = evento.request;
  const url = new URL(peticion.url);

  if (peticion.method !== "GET" || url.origin !== self.location.origin) return;
  if (url.pathname.startsWith("/api") || url.pathname.startsWith("/sanctum")) return;

  // Navegar: red primero, y si no hay, el armazón guardado. Así la app arranca sin
  // conexión y es ella la que explica lo que pasa, con sus palabras y su botón.
  if (peticion.mode === "navigate") {
    evento.respondWith(fetch(peticion).catch(() => caches.match("/index.html")));
    return;
  }

  // Estáticos: se sirve lo guardado si está y se refresca por detrás. Los nombres llevan
  // hash, así que un fichero guardado nunca puede ser una versión vieja de otro contenido.
  evento.respondWith(
    caches.match(peticion).then((guardado) => {
      const red = fetch(peticion).then((respuesta) => {
        if (respuesta.ok) {
          const copia = respuesta.clone();
          caches.open(CACHE).then((c) => c.put(peticion, copia));
        }
        return respuesta;
      });
      return guardado ?? red;
    }),
  );
});
