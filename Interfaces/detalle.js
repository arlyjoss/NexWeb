// Obtener el identificador del producto desde la URL (?id=1)
const parametros = new URLSearchParams(window.location.search);
const idProducto = Number(parametros.get("id"));

// Buscar el producto en el arreglo 'productos' de productos.js
const producto = productos.find(p => p.id === idProducto);

// Si existe el producto, cargamos sus datos en la pantalla
if (producto) {
    document.getElementById("imagen-producto").src = producto.imagen;
    document.getElementById("imagen-producto").alt = producto.nombre;
    document.getElementById("nombre-producto").textContent = producto.nombre;
    document.getElementById("marca-producto").textContent = producto.marca;
    document.getElementById("categoria-producto").textContent = producto.categoria;
    document.getElementById("precio-producto").textContent = "$" + producto.precio.toLocaleString("es-MX") + " MXN";
} else {
    document.getElementById("nombre-producto").textContent = "Producto no encontrado";
}

// Agregar al carrito con localStorage
document.getElementById("agregar-carrito").addEventListener("click", function () {
    const talla = document.getElementById("talla-producto").value;
    const cantidad = Number(document.getElementById("cantidad-producto").value);
    const mensaje = document.getElementById("mensaje");

    if (!producto) {
        mensaje.textContent = "No se encontró el producto.";
        return;
    }

    if (!talla) {
        mensaje.textContent = "Por favor, selecciona una talla.";
        return;
    }

    if (!Number.isInteger(cantidad) || cantidad < 1) {
        mensaje.textContent = "Ingresa una cantidad válida.";
        return;
    }

    // Obtener el carrito actual del localStorage o crear uno nuevo vacío
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

    // Buscar si este mismo producto con esta misma talla ya está en el carrito
    const existente = carrito.find(item => item.id === producto.id && item.talla === talla);

    if (existente) {
        existente.cantidad += cantidad;
    } else {
        carrito.push({
            id: producto.id,
            nombre: producto.nombre,
            marca: producto.marca,
            precio: producto.precio,
            imagen: producto.imagen,
            talla: talla,
            cantidad: cantidad
        });
    }

    // Guardar los datos actualizados en localStorage
    localStorage.setItem("carrito", JSON.stringify(carrito));
    mensaje.textContent = "¡Producto agregado al carrito con éxito!";
});