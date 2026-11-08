// Login y registro de NexWeb conectados a la base de datos.
// El mismo archivo sirve para login.html y registro.html:
// si la página tiene el campo "confirmar", es el registro.

const formulario = document.querySelector("form");
const esRegistro = document.querySelector("#confirmar") !== null;

// Párrafo para mostrar mensajes debajo del botón
const mensajeForm = document.createElement("p");
mensajeForm.setAttribute("role", "alert");
mensajeForm.style.cssText = "margin-top: 12px; text-align: center; font-weight: bold;";
formulario.appendChild(mensajeForm);

function mostrarMensaje(texto, esError) {
    mensajeForm.textContent = texto;
    mensajeForm.style.color = esError ? "#b00020" : "#1b7f3b";
}

// A dónde ir después de entrar
function destino() {
    const volver = new URLSearchParams(location.search).get("volver");
    return volver === "carrito" ? "../Interfaces/carrito.html" : "../Interfaces/interfaces.html";
}

formulario.addEventListener("submit", async function (event) {
    event.preventDefault();

    const boton = formulario.querySelector("button[type=submit]");
    const correo = document.querySelector("#correo").value.trim();
    const contrasena = document.querySelector("#contrasena").value;

    let ruta, datos;

    if (esRegistro) {
        const nombre = document.querySelector("#nombre").value.trim();
        const confirmar = document.querySelector("#confirmar").value;

        // Validación rápida en el navegador (el servidor vuelve a validar todo)
        if (contrasena !== confirmar) {
            mostrarMensaje("Las contraseñas no coinciden.", true);
            return;
        }
        if (contrasena.length < 8 || !/[A-Za-z]/.test(contrasena) || !/\d/.test(contrasena)) {
            mostrarMensaje("La contraseña debe tener mínimo 8 caracteres, con letras y números.", true);
            return;
        }
        ruta = "registro.php";
        datos = { nombre, correo, contrasena, confirmar };
    } else {
        ruta = "login.php";
        datos = { correo, contrasena };
    }

    boton.disabled = true;
    try {
        const r = await NexWeb.api(ruta, { metodo: "POST", datos });
        mostrarMensaje(r.mensaje, false);
        setTimeout(() => { location.href = destino(); }, 700);
    } catch (error) {
        mostrarMensaje(error.message, true);
        boton.disabled = false;
    }
});
