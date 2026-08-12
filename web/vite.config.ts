import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
  plugins: [react()],
  server: {
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
