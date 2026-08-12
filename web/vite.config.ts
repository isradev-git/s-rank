import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
  plugins: [react()],
  server: {
    // El proyecto vive en /mnt/c, y WSL no entrega eventos de inotify para el disco de
    // Windows: sin esto Vite no se entera de ningún cambio y sirve el código de cuando
    // arrancó, sin avisar de nada. Sondear cuesta algo de CPU y es el precio de tener el
    // proyecto en /mnt/c. Si algún día se mueve a ~/, esta línea se puede quitar.
    watch: { usePolling: true, interval: 400 },

    // En desarrollo el navegador habla solo con localhost:5173 y Vite reenvía a Laravel.
    // Así front y API comparten origen igual que en producción, y la cookie de sesión
    // funciona sin CORS y sin bajar SameSite. En producción no hace falta nada de esto:
    // el `dist/` se copia dentro de backend/public/ y ya es literalmente el mismo sitio.
    proxy: {
      "/api": "http://localhost:8000",
      "/sanctum": "http://localhost:8000",
    },
  },
});
