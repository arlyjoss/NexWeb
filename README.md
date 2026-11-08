# NexWeb 👟

Tienda en línea de tenis donde los usuarios pueden ver el catálogo, filtrar por marca y categoría, elegir talla, agregar productos al carrito, crear una cuenta, iniciar sesión, comprar y escribir al chat de soporte.

## Funciones

- **Catálogo** con filtros por marca y categoría.
- **Detalle de producto** con tallas y stock reales (las tallas agotadas no se pueden elegir).
- **Carrito de compras**: al pagar, el pedido se guarda en la base de datos y se descuenta el stock.
- **Registro e inicio de sesión** con contraseñas cifradas.
- **Chat de soporte** con respuestas automáticas; los mensajes se guardan en la base de datos.
- **Roles**: cliente y administrador.

## Tecnologías

| Parte | Tecnología |
|---|---|
| Interfaz | HTML, CSS, JavaScript |
| Servidor | PHP 8 |
| Base de datos | MySQL / MariaDB |
| Entorno local | XAMPP (Apache + MySQL + PHP + phpMyAdmin) |

## Estructura

```
NexWeb/
├── Inicio/            Página principal y chat flotante
├── Interfaces/        Catálogo, detalle del producto y carrito
├── Login/             Inicio de sesión y registro
├── Chat de Tienda/    Vista del chat de soporte
├── api/               Archivos PHP que conectan la página con la base de datos
├── js/                Funciones compartidas (nexweb.js)
├── Base de datos/     Script SQL, modelo ER, consultas de prueba y guía
└── img/               Imágenes de los productos
```

## Cómo ejecutarlo

1. Instalar [XAMPP](https://www.apachefriends.org) y encender **Apache** y **MySQL**.
2. Copiar el proyecto en `C:\xampp\htdocs\NexWeb`.
3. En `Base de datos/database.sql`, cambiar la contraseña `CambiaEsta_Contrasena_2026` por una propia.
4. Entrar a `http://localhost/phpmyadmin` → **Importar** → subir `Base de datos/database.sql`.
5. Copiar `api/config.example.php` como `api/config.php` y escribir la misma contraseña.
6. Abrir `http://localhost/NexWeb/Inicio/index.html`.

> La página debe abrirse desde `http://localhost/...`, no con doble clic al archivo.
> `api/config.php` no se sube a GitHub; cada integrante crea el suyo.

La guía completa (seguridad, pruebas y solución de problemas) está en
[`Base de datos/GUIA_BASE_DE_DATOS.md`](Base%20de%20datos/GUIA_BASE_DE_DATOS.md).

## Equipo

Proyecto desarrollado por estudiantes de la Facultad de Ingeniería Mecánica y Eléctrica (FIME), UANL.
