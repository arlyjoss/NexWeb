// Catálogo NexWeb: los productos ahora vienen de la base de datos (api/productos.php)

let productos = [];   // se llena con lo que responde el servidor

// Referencias a los elementos HTML
const listaProductos = document.getElementById("lista-productos");
const selectMarca = document.getElementById("filtro-marca");
const selectCategoria = document.getElementById("filtro-categoria");

// Pide los productos a la API
async function cargarProductos() {
    listaProductos.innerHTML = "<p style='grid-column: 1/-1; text-align: center; padding: 20px;'>Cargando productos...</p>";
    try {
        const respuesta = await NexWeb.api("productos.php");
        productos = respuesta.productos;
        filtrarProductos();
    } catch (error) {
        listaProductos.innerHTML = "";
        const aviso = document.createElement("p");
        aviso.style.cssText = "grid-column: 1/-1; text-align: center; padding: 20px; color: #b00020;";
        aviso.textContent = error.message;
        listaProductos.appendChild(aviso);
    }
}

// Función para dibujar las tarjetas en pantalla
function renderizarLista(lista) {
    listaProductos.innerHTML = "";

    if (lista.length === 0) {
        listaProductos.innerHTML = "<p style='grid-column: 1/-1; text-align: center; padding: 20px;'>No se encontraron tenis con esos filtros.</p>";
        return;
    }

    const e = NexWeb.escaparHTML;   // todo dato que venga de la BD se escapa (protección XSS)

    lista.forEach(producto => {
        const agotado = producto.stock_total === 0;
        const tarjeta = document.createElement("article");
        tarjeta.classList.add("tarjeta-producto");
        tarjeta.innerHTML = `
            <img src="${e(NexWeb.urlImagen(producto.imagen))}" alt="${e(producto.nombre)}">
            <div class="informacion-producto">
                <span class="marca">${e(producto.marca)}</span>
                <h3>${e(producto.nombre)}</h3>
                <p>${e(producto.categoria)}${agotado ? " · <strong>Agotado</strong>" : ""}</p>
                <strong>$${producto.precio.toLocaleString("es-MX")} MXN</strong>
            </div>
            <button type="button">Ver producto</button>
        `;
        tarjeta.querySelector("button").addEventListener("click", () => {
            window.location.href = "detalle.html?id=" + encodeURIComponent(producto.id);
        });
        listaProductos.appendChild(tarjeta);
    });
}

// Función para filtrar los productos según lo elegido en los select
function filtrarProductos() {
    const marcaSeleccionada = selectMarca.value;
    const categoriaSeleccionada = selectCategoria.value;

    const productosFiltrados = productos.filter(producto => {
        const coincideMarca = (marcaSeleccionada === "todas") || (producto.marca === marcaSeleccionada);
        const coincideCategoria = (categoriaSeleccionada === "todas") || (producto.categoria === categoriaSeleccionada);
        return coincideMarca && coincideCategoria;
    });

    renderizarLista(productosFiltrados);
}

// Función para actualizar el número en "Carrito (X)" en el menú
function actualizarContadorNav() {
    const navCarrito = document.getElementById("nav-carrito");
    if (!navCarrito) return;

    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    let totalItems = carrito.reduce((acum, item) => acum + (Number(item.cantidad) || 0), 0);
    navCarrito.textContent = `Carrito (${totalItems})`;
}

// Escuchar los eventos de cambio en los menús desplegables
if (selectMarca && selectCategoria) {
    selectMarca.addEventListener("change", filtrarProductos);
    selectCategoria.addEventListener("change", filtrarProductos);
}

// Ejecutar al cargar el catálogo
if (listaProductos) {
    cargarProductos();
    actualizarContadorNav();
}
