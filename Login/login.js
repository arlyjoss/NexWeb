const formulario = document.querySelector("form");

formulario.addEventListener("submit", function(event) {

    event.preventDefault();

    const contrasena = document.querySelector("#contrasena").value;
    const confirmar = document.querySelector("#confirmar").value;

    if (contrasena !== confirmar) {
        alert("Las contraseñas no coinciden.");
        return;
    }

    alert("¡Cuenta creada correctamente!");
});