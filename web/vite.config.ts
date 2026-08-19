/// <reference types="vitest/config" />
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
  plugins: [react()],
  test: {
    // Las pantallas necesitan un DOM; las funciones de formato.ts no, pero tener dos
    // ejecutores de tests para eso sería peor que pagar el arranque de jsdom.
    environment: "jsdom",
    // Desmonta lo pintado entre test y test: sin esto el segundo encuentra dos botones.
    setupFiles: ["./src/tests-preparacion.ts"],
    restoreMocks: true,
    // `restoreMocks` solo toca los espías de `vi.spyOn`; los `vi.fn()` que devuelve una
    // factoría de `vi.mock` no los limpia nadie, y sus llamadas se van acumulando de un
    // test al siguiente hasta que un `not.toHaveBeenCalled()` falla sin que se vea por
    // qué. `clearMocks` borra el historial de todos. No pone `mockReset`: eso borraría
    // también implementaciones como el `vi.fn().mockResolvedValue([])` de los mocks de
    // api.ts, que se declaran una vez para todo el fichero.
    clearMocks: true,
  },
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
