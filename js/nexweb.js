/**
 * NexWeb — puente entre las páginas y la API PHP.
 * Inclúyelo ANTES de los demás scripts de cada página:
 *     <script src="../js/nexweb.js"></script>
 *
 * Expone el objeto global  NexWeb  con:
 *   NexWeb.api(ruta, { metodo, datos })  → hace fetch a /api y regresa el JSON
 *   NexWeb.sesion()                      → { autenticado, usuario, csrf }
 *   NexWeb.escaparHTML(texto)            → evita XSS al usar innerHTML
 *   NexWeb.urlImagen(archivo)            → ruta correcta a /img
 *   NexWeb.cerrarSesion()
 */
const NexWeb = (function () {
    // Rutas calculadas a partir de dónde está este archivo (/js/nexweb.js),
    // así funcionan desde Inicio/, Interfaces/ o Login/.
    const base = new URL("../", document.currentScript.src);
    const URL_API = new URL("api/", base).href;
    const URL_IMG = new URL("img/", base).href;

    let estadoSesion = null;

    async function api(ruta, opciones = {}) {
        if (location.protocol === "file:") {
            throw new Error("Abre la página desde http://localhost/NexWeb/... (con XAMPP encendido), no con doble clic en el archivo.");
        }

        const metodo = opciones.metodo || "GET";
        const cabeceras = { "Accept": "application/json" };
        let cuerpo;

        if (metodo !== "GET") {
            const s = await sesion();
            cabeceras["Content-Type"] = "application/json";
            cabeceras["X-CSRF-Token"] = s.csrf;
            cuerpo = JSON.stringify(opciones.datos || {});
        }

        let respuesta;
        try {
            respuesta = await fetch(URL_API + ruta, {
                method: metodo,
                headers: cabeceras,
                body: cuerpo,
                credentials: "same-origin"
            });
        } catch (e) {
            throw new Error("No hay conexión con el servidor. ¿Está encendido Apache en XAMPP?");
        }

        let json = {};
        try { json = await respuesta.json(); } catch (e) { /* respuesta sin JSON */ }

        // Si el servidor entrega un token nuevo (login/registro), lo guardamos
        if (json.csrf && estadoSesion) {
            estadoSesion.csrf = json.csrf;
        }

        if (!respuesta.ok) {
            const error = new Error(json.error || ("Error " + respuesta.status));
            error.status = respuesta.status;
            throw error;
        }
        return json;
    }

    async function sesion(forzar = false) {
        if (!estadoSesion || forzar) {
            estadoSesion = await api("sesion.php");
        }
        return estadoSesion;
    }

    async function cerrarSesion() {
        await api("logout.php", { metodo: "POST" });
        estadoSesion = null;
        location.href = new URL("Inicio/index.html", base).href;
    }

    function escaparHTML(texto) {
        return String(texto)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function urlImagen(archivo) {
        return URL_IMG + encodeURIComponent(archivo);
    }

    // Cambia el enlace "Iniciar sesión" del menú por "Hola, Nombre · Salir"
    async function pintarMenuSesion() {
        const enlaceLogin = document.querySelector('header a[href$="login.html"]');
        if (!enlaceLogin || location.protocol === "file:") return;

        try {
            const s = await sesion();
            if (!s.autenticado) return;

            const salir = document.createElement("a");
            salir.href = "#";
            salir.textContent = "Salir";
            salir.addEventListener("click", function (e) {
                e.preventDefault();
                cerrarSesion();
            });

            enlaceLogin.textContent = "Hola, " + s.usuario.nombre.split(" ")[0];
            enlaceLogin.removeAttribute("href");
            enlaceLogin.after(salir);
        } catch (e) {
            console.warn("NexWeb:", e.message);
        }
    }

    document.addEventListener("DOMContentLoaded", pintarMenuSesion);

    return { api, sesion, cerrarSesion, escaparHTML, urlImagen };
})();
