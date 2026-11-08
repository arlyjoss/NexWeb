// Detalle de producto: los datos y las tallas disponibles vienen de la base de datos

// Obtener el identificador del producto desde la URL (?id=1)
const parametros = new URLSearchParams(window.location.search);
const idProducto = Number(parametros.get("id"));

let producto = null;   // se llena con la respuesta de api/productos.php?id=...

const selectTalla = document.getElementById("talla-producto");
const inputCantidad = document.getElementById("cantidad-producto");
const mensaje = document.getElementById("mensaje");

async function cargarProducto() {
    if (!Number.isInteger(idProducto) || idProducto < 1) {
        document.getElementById("nombre-producto").textContent = "Producto no encontrado";
        return;
    }

    try {
        const respuesta = await NexWeb.api("productos.php?id=" + idProducto);
        producto = respuesta.producto;
    } catch (error) {
        document.getElementById("nombre-producto").textContent =
            error.status === 404 ? "Producto no encontrado" : error.message;
        return;
    }

    // textContent (no innerHTML) = el texto nunca se interpreta como HTML
    document.getElementById("imagen-producto").src = NexWeb.urlImagen(producto.imagen);
    document.getElementById("imagen-producto").alt = producto.nombre;
    document.getElementById("nombre-producto").textContent = producto.nombre;
    document.getElementById("marca-producto").textContent = producto.marca;
    document.getElementById("categoria-producto").textContent = producto.categoria;
    document.getElementById("precio-producto").textContent = "$" + producto.precio.toLocaleString("es-MX") + " MXN";

    // Rellenar las tallas con el stock real
    selectTalla.innerHTML = '<option value="">Selecciona una talla</option>';
    producto.tallas.forEach(t => {
        const opcion = document.createElement("option");
        opcion.value = t.talla;
        opcion.textContent = t.stock > 0 ? t.talla : t.talla + " (agotada)";
        opcion.disabled = t.stock === 0;
        opcion.dataset.stock = t.stock;
        selectTalla.appendChild(opcion);
    });
}

// La cantidad máxima depende del stock de la talla elegida
selectTalla.addEventListener("change", function () {
    const opcion = selectTalla.selectedOptions[0];
    const stock = Number(opcion && opcion.dataset.stock) || 1;
    inputCantidad.max = Math.min(stock, 20);
});

// Agregar al carrito (el carrito sigue en localStorage hasta que se paga)
document.getElementById("agregar-carrito").addEventListener("click", function () {
    const talla = Number(selectTalla.value);
    const cantidad = Number(inputCantidad.value);

    if (!producto) {
        mensaje.textContent = "No se encontró el producto.";
        return;
    }
    if (!talla) {
        mensaje.textContent = "Por favor, selecciona una talla.";
        return;
    }
    if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > Number(inputCantidad.max || 20)) {
        mensaje.textContent = "Ingresa una cantidad válida (máximo " + (inputCantidad.max || 20) + ").";
        return;
    }

    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    const existente = carrito.find(item => item.id === producto.id && Number(item.talla) === talla);

    if (existente) {
        existente.cantidad += cantidad;
    } else {
        carrito.push({
            id: producto.id,
            nombre: producto.nombre,
            marca: producto.marca,
            precio: producto.precio,   // solo para mostrar; al pagar el servidor usa el precio de la BD
            imagen: producto.imagen,
            talla: talla,
            cantidad: cantidad
        });
    }

    localStorage.setItem("carrito", JSON.stringify(carrito));
    mensaje.textContent = "¡Producto agregado al carrito con éxito!";
});

cargarProducto();
