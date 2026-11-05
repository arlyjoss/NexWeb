// Cargar los productos guardados en localStorage al iniciar la página
document.addEventListener("DOMContentLoaded", function () {
    mostrarCarrito();
});

function mostrarCarrito() {
    const listaCarrito = document.getElementById("lista-carrito");
    const totalPrecio = document.getElementById("total-precio");
    
    // Obtener datos de localStorage
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];

    // Limpiar el contenedor
    listaCarrito.innerHTML = "";

    if (carrito.length === 0) {
        listaCarrito.innerHTML = `
            <div class="carrito-vacio">
                <p>Tu carrito está vacío 👟</p>
                <a href="interfaces.html" class="volver">Ir al catálogo para agregar tenis</a>
            </div>
        `;
        totalPrecio.textContent = "$0 MXN";
        return;
    }

    let total = 0;

    // Recorrer los productos del carrito y mostrarlos
    carrito.forEach((item, indice) => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;

        const elemento = document.createElement("div");
        elemento.classList.add("item-carrito");
        elemento.innerHTML = `
            <img src="${item.imagen}" alt="${item.nombre}">
            <div class="info-item">
                <h4>${item.nombre}</h4>
                <p>Marca: ${item.marca} | Talla: <strong>${item.talla}</strong></p>
                <p>Precio unitario: $${item.precio.toLocaleString("es-MX")} MXN</p>
                <p>Cantidad: <strong>${item.cantidad}</strong></p>
            </div>
            <div class="subtotal-item">
                <p>Subtotal: $${subtotal.toLocaleString("es-MX")} MXN</p>
                <button class="btn-eliminar" onclick="eliminarProducto(${indice})">Eliminar</button>
            </div>
        `;
        listaCarrito.appendChild(elemento);
    });

    // Actualizar el total general
    totalPrecio.textContent = "$" + total.toLocaleString("es-MX") + " MXN";
}

// Función para eliminar un solo producto
function eliminarProducto(indice) {
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    carrito.splice(indice, 1); // Quitar el producto según su posición
    localStorage.setItem("carrito", JSON.stringify(carrito));
    mostrarCarrito(); // Volver a dibujar la lista
}

// Evento para vaciar todo el carrito
document.getElementById("btn-vaciar").addEventListener("click", function () {
    localStorage.removeItem("carrito");
    mostrarCarrito();
});

// Evento simulado de compra
document.getElementById("btn-comprar").addEventListener("click", function () {
    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    if (carrito.length === 0) {
        alert("Tu carrito está vacío.");
    } else {
        alert("¡Gracias por tu compra! Esta sección se conectará próximamente con la base de datos.");
        localStorage.removeItem("carrito");
        mostrarCarrito();
    }
});