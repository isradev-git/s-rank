import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter } from "react-router";
import App from "./App";
import "./estilos.css";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </StrictMode>,
);

// Solo en el build de verdad. En desarrollo, un trabajador de servicio sirviendo por su
// cuenta lo que tenía guardado convierte cualquier cambio en «esto no se aplica», que es
// justo el rato que ya se ha perdido una vez con la vigilancia de ficheros.
if (import.meta.env.PROD && "serviceWorker" in navigator) {
  window.addEventListener("load", () => {
    void navigator.serviceWorker.register("/sw.js");
  });
}
