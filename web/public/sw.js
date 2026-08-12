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

const CACHE = "srank-shell-v2";

/* Las fuentes viven en public/ y Vite las copia con el nombre puesto, sin hash: por eso se
   pueden nombrar aquí y no cambian de un despliegue a otro. Van aparte del resto porque
   son medio mega y no son esenciales: sin ellas la app se ve con la monoespaciada del
   sistema, que es feo pero funciona. Si fallan, el trabajador se instala igual. */
const FUENTES = [
  "/fuentes/jetbrains-mono-regular.ttf",
  "/fuentes/jetbrains-mono-bold.ttf",
];

/** Los nombres del bundle llevan hash, así que hay que leerlos del HTML recién bajado.
 *
 *  ponytail: se sacan con una expresión regular en vez de montar un paso de construcción
 *  que los inyecte. El techo es que si algún día Vite emite las etiquetas de otra forma,
 *  esto deja de encontrarlas — y entonces degrada a lo de antes, que es guardar solo el
 *  armazón: la app sigue instalándose y sin conexión sigue arrancando en la segunda
 *  visita, nunca se queda en blanco. Si eso llega a molestar, la herramienta que hace este
 *  trabajo de verdad es vite-plugin-pwa. */
function recursosDe(html) {
  return [...html.matchAll(/(?:src|href)="(\/assets\/[^"]+)"/g)].map((m) => m[1]);
}

self.addEventListener("install", (evento) => {
  evento.waitUntil(
    (async () => {
      const almacen = await caches.open(CACHE);
      const html = await fetch("/index.html", { cache: "reload" });
      const recursos = recursosDe(await html.clone().text());

      // El armazón y el bundle van juntos y son obligatorios: guardar el HTML sin el
      // JavaScript al que apunta es justo lo que produce una pantalla en blanco.
      await almacen.addAll(["/", "/index.html", ...recursos]);
      await Promise.allSettled(FUENTES.map((f) => almacen.add(f)));
      await self.skipWaiting();
    })(),
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
  //
  // Cada visita con conexión refresca la copia guardada. Hace falta porque este fichero
  // no cambia de un despliegue a otro: el navegador no vuelve a instalar el trabajador y
  // `install` no se ejecuta nunca más. Sin esto, la copia sin conexión se quedaría
  // congelada en la versión del día que se instaló.
  if (peticion.mode === "navigate") {
    evento.respondWith(
      fetch(peticion)
        .then((respuesta) => {
          const copia = respuesta.clone();
          evento.waitUntil(caches.open(CACHE).then((c) => c.put("/index.html", copia)));
          return respuesta;
        })
        .catch(() => caches.match("/index.html")),
    );
    return;
  }

  // Estáticos: se sirve lo guardado si está y se refresca por detrás. Los nombres llevan
  // hash, así que un fichero guardado nunca puede ser una versión vieja de otro contenido.
  evento.respondWith(
    caches.match(peticion).then((guardado) => {
      const red = fetch(peticion)
        .then((respuesta) => {
          if (respuesta.ok) {
            const copia = respuesta.clone();
            evento.waitUntil(caches.open(CACHE).then((c) => c.put(peticion, copia)));
          }
          return respuesta;
        })
        // Sin conexión esto rechaza. Si teníamos copia ya la hemos devuelto y el fallo
        // sobra; si no la teníamos, que siga rechazando y el navegador lo trate como
        // recurso caído.
        .catch((e) => {
          if (guardado) return guardado;
          throw e;
        });
      return guardado ?? red;
    }),
  );
});
