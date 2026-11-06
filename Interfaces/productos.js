// Productos de prueba para el catálogo NexWeb
console.log("JavaScript de NexWeb funcionando");

const productos = [
    {
        id: 1,
        nombre: "Tenis Adidas Superstar",
        marca: "Adidas",
        categoria: "Deportivo",
        precio: 1299,
        imagen: "../img/prod-adidas.jpg"
    },
    {
        id: 2,
        nombre: "Tenis Converse Chuck Taylor",
        marca: "Converse",
        categoria: "Casual",
        precio: 999,
        imagen: "../img/prod-converse.jpg"
    },
    {
        id: 3,
        nombre: "Tenis Nike Air Max",
        marca: "Nike",
        categoria: "Deportivo",
        precio: 1499,
        imagen: "../img/prod-nike.jpg"
    },
    {
        id: 4,
        nombre: "Tenis Vans Old Skool",
        marca: "Vans",
        categoria: "Casual",
        precio: 1099,
        imagen: "../img/prod-vans.jpg"
    },
    {
        id: 5,
        nombre: "Tenis Nike Dunk Low Blue",
        marca: "Nike",
        categoria: "Casual",
        precio: 1899,
        imagen: "../img/cat-caballero-a.jpg"
    },
    {
        id: 6,
        nombre: "Tenis Vans Sk8-Hi Black",
        marca: "Vans",
        categoria: "Casual",
        precio: 1150,
        imagen: "../img/cat-caballero-b.jpg"
    },
    {
        id: 7,
        nombre: "Tenis Nike Vaporfly Pro",
        marca: "Nike",
        categoria: "Deportivo",
        precio: 2199,
        imagen: "../img/cat-deportivo-a.jpg"
    },
    {
        id: 8,
        nombre: "Tenis Nike Air Force Pastel",
        marca: "Nike",
        categoria: "Casual",
        precio: 1599,
        imagen: "../img/cat-dama-a.jpg"
    },
    {
        id: 9,
        nombre: "Tenis Fila Disruptor White",
        marca: "Fila",
        categoria: "Casual",
        precio: 1250,
        imagen: "../img/cat-dama-b.jpg"
    },
    {
        id: 10,
        nombre: "Tenis Asics Gel-Kayano",
        marca: "Asics",
        categoria: "Deportivo",
        precio: 1799,
        imagen: "../img/cat-deportivo-b.jpg"
    },
    {
        id: 11,
        nombre: "Tenis New Balance 550 Green",
        marca: "New Balance",
        categoria: "Casual",
        precio: 1650,
        imagen: "../img/cat-casual-a.jpg"
    },
    {
        id: 12,
        nombre: "Tenis Adidas Ultraboost Black",
        marca: "Adidas",
        categoria: "Deportivo",
        precio: 1799,
        imagen: "../img/cat-deportivo-c.jpg"
    }
];

// Referencias a los elementos HTML
const listaProductos = document.getElementById("lista-productos");
const selectMarca = document.getElementById("filtro-marca");
const selectCategoria = document.getElementById("filtro-categoria");

// Función para dibujar las tarjetas en pantalla
function renderizarLista(lista) {
    listaProductos.innerHTML = "";

    if (lista.length === 0) {
        listaProductos.innerHTML = "<p style='grid-column: 1/-1; text-align: center; padding: 20px;'>No se encontraron tenis con esos filtros.</p>";
        return;
    }

    lista.forEach(producto => {
        const tarjeta = document.createElement("article");
        tarjeta.classList.add("tarjeta-producto");
        tarjeta.innerHTML = `
            <img src="${producto.imagen}" alt="${producto.nombre}">
            <div class="informacion-producto">
                <span class="marca">${producto.marca}</span>
                <h3>${producto.nombre}</h3>
                <p>${producto.categoria}</p>
                <strong>$${producto.precio.toLocaleString("es-MX")} MXN</strong>
            </div>
            <button onclick="window.location.href='detalle.html?id=${producto.id}'">
                Ver producto
            </button>
        `;
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
    let totalItems = carrito.reduce((acum, item) => acum + item.cantidad, 0);
    navCarrito.textContent = `Carrito (${totalItems})`;
}

// Escuchar los eventos de cambio en los menús desplegables
if (selectMarca && selectCategoria) {
    selectMarca.addEventListener("change", filtrarProductos);
    selectCategoria.addEventListener("change", filtrarProductos);
}

// Ejecutar al cargar el archivo en el catálogo
if (listaProductos) {
    renderizarLista(productos);
    actualizarContadorNav();
}