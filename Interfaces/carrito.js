// Carrito: se arma en localStorage y, al pagar, se envía a la base de datos (api/pedidos.php)

document.addEventListener("DOMContentLoaded", function () {
    mostrarCarrito();
});

function leerCarrito() {
    try {
        const carrito = JSON.parse(localStorage.getItem("carrito"));
        return Array.isArray(carrito) ? carrito : [];
    } catch (e) {
        return [];
    }
}

// Un producto es "comprable" si tiene id y talla (los agregados desde Inicio no tienen talla)
function esComprable(item) {
    return Number.isInteger(Number(item.id)) && Number(item.id) > 0 && Number(item.talla) > 0;
}

function mostrarCarrito() {
    const listaCarrito = document.getElementById("lista-carrito");
    const totalPrecio = document.getElementById("total-precio");
    const e = NexWeb.escaparHTML;

    let carrito = leerCarrito();
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

    carrito.forEach((item, indice) => {
        const precio = Number(item.precio) || 0;
        const cantidad = Number(item.cantidad) || 0;
        const subtotal = precio * cantidad;
        total += subtotal;

        const imagen = item.imagen ? NexWeb.urlImagen(String(item.imagen).split("/").pop()) : "";
        const aviso = esComprable(item)
            ? ""
            : `<p style="color:#b00020;">Elige una talla desde el <a href="interfaces.html">catálogo</a> para poder comprarlo.</p>`;

        const elemento = document.createElement("div");
        elemento.classList.add("item-carrito");
        elemento.innerHTML = `
            ${imagen ? `<img src="${e(imagen)}" alt="${e(item.nombre)}">` : ""}
            <div class="info-item">
                <h4>${e(item.nombre)}</h4>
                <p>Marca: ${e(item.marca || "—")} | Talla: <strong>${e(item.talla || "—")}</strong></p>
                <p>Precio unitario: $${precio.toLocaleString("es-MX")} MXN</p>
                <p>Cantidad: <strong>${cantidad}</strong></p>
                ${aviso}
            </div>
            <div class="subtotal-item">
                <p>Subtotal: $${subtotal.toLocaleString("es-MX")} MXN</p>
                <button class="btn-eliminar" type="button">Eliminar</button>
            </div>
        `;
        elemento.querySelector(".btn-eliminar").addEventListener("click", () => eliminarProducto(indice));
        listaCarrito.appendChild(elemento);
    });

    totalPrecio.textContent = "$" + total.toLocaleString("es-MX") + " MXN";
}

function eliminarProducto(indice) {
    let carrito = leerCarrito();
    carrito.splice(indice, 1);
    localStorage.setItem("carrito", JSON.stringify(carrito));
    mostrarCarrito();
}

document.getElementById("btn-vaciar").addEventListener("click", function () {
    localStorage.removeItem("carrito");
    mostrarCarrito();
});

// Compra real: se manda solo id, talla y cantidad. El precio lo pone el servidor.
document.getElementById("btn-comprar").addEventListener("click", async function () {
    const boton = this;
    const carrito = leerCarrito();

    if (carrito.length === 0) {
        alert("Tu carrito está vacío.");
        return;
    }
    if (!carrito.every(esComprable)) {
        alert("Hay productos sin talla. Elimínalos o agrégalos desde el catálogo eligiendo talla.");
        return;
    }

    const items = carrito.map(item => ({
        id: Number(item.id),
        talla: Number(item.talla),
        cantidad: Number(item.cantidad)
    }));

    boton.disabled = true;
    try {
        const r = await NexWeb.api("pedidos.php", { metodo: "POST", datos: { items } });
        localStorage.removeItem("carrito");
        mostrarCarrito();
        alert(`¡Gracias por tu compra! Pedido #${r.pedido_id} registrado por $${r.total.toLocaleString("es-MX")} MXN.`);
    } catch (error) {
        if (error.status === 401) {
            alert("Inicia sesión para completar tu compra. Tu carrito se conserva.");
            location.href = "../Login/login.html?volver=carrito";
            return;
        }
        alert(error.message);
    } finally {
        boton.disabled = false;
    }
});
