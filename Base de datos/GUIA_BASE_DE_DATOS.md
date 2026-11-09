# Guía de la base de datos de NexWeb

Esta guía cubre la parte de **Base de datos (Persona 4)**: modelo ER, script SQL, conexión con el proyecto, consultas y pruebas.

---

## 1. ¿Qué cambió en el proyecto?

**Antes:** todo vivía en el navegador (`localStorage`). Los productos estaban escritos a mano en `productos.js`, el registro solo mostraba un `alert`, el login no hacía nada y "Proceder al pago" no guardaba la compra.

**Ahora:**

```
 Navegador (HTML + JS)            Servidor (Apache + PHP)              MySQL / MariaDB
┌──────────────────────┐  fetch  ┌───────────────────────────┐  PDO  ┌──────────────────┐
│ interfaces.html      │ ──────► │ api/productos.php         │ ────► │ BD  nexweb       │
│ detalle.html         │  JSON   │ api/login.php / registro  │       │  usuarios        │
│ carrito.html         │ ◄────── │ api/pedidos.php           │ ◄──── │  productos ...   │
│ login / registro     │         │ api/chat.php              │       │  pedidos ...     │
└──────────────────────┘         └───────────────────────────┘       └──────────────────┘
```

El navegador **nunca** se conecta directo a MySQL. Siempre pasa por PHP, que valida todo y usa un usuario de BD con permisos limitados.

| Función | Antes | Ahora |
|---|---|---|
| Catálogo | arreglo fijo en JS | tabla `productos` (vía `vista_catalogo`) |
| Detalle | todas las tallas disponibles | tallas y stock reales; las agotadas salen deshabilitadas |
| Registro | `alert` | se guarda en `usuarios` con la contraseña cifrada |
| Login | no hacía nada | verifica contra la BD, abre sesión y el menú muestra "Hola, Nombre · Salir" |
| Pagar | `alert` y vaciar | crea el pedido en `pedidos` + `detalle_pedido` y descuenta stock |
| Chat | solo `localStorage` | además se guarda en `mensajes_chat` |

El carrito se sigue armando en `localStorage` (así funciona sin cuenta). Solo al pagar se manda a la BD.

---

## 2. Programas necesarios

| Programa | ¿Para qué? | Obligatorio |
|---|---|---|
| **XAMPP** (https://www.apachefriends.org) | Trae todo junto: **Apache** (servidor web), **MySQL/MariaDB** (base de datos), **PHP** (la API) y **phpMyAdmin** (administrar la BD desde el navegador). Usa la versión con PHP 8.x. | Sí |
| **Navegador** (Chrome, Edge, Firefox) | Ver la tienda y phpMyAdmin. | Sí |
| **Visual Studio Code** | Editar el código. | Recomendado |
| **Git** o **GitHub Desktop** | Bajar y subir cambios al repositorio del equipo. | Recomendado |
| MySQL Workbench o dbdiagram.io | Solo si quieren dibujar el modelo ER con otra herramienta (el ER ya viene en `modelo_er.md`). | Opcional |

> En Mac se puede usar XAMPP o MAMP; en Linux, XAMPP o `apache2 + mariadb-server + php-mysql`.

---

## 3. Instalación paso a paso (Windows + XAMPP)

### Paso 1 — Instalar y arrancar XAMPP
1. Instala XAMPP (deja marcados Apache, MySQL, PHP y phpMyAdmin).
2. Abre el **XAMPP Control Panel** y pulsa **Start** en **Apache** y en **MySQL**. Ambos deben ponerse en verde.
3. Fíjate en el puerto que muestra MySQL (normalmente **3306**; si alguien del equipo lo cambió, puede ser **3307**).

### Paso 2 — Copiar el proyecto a `htdocs`
Apache solo sirve archivos que estén dentro de `C:\xampp\htdocs`. Clona o copia el repo ahí:

```
C:\xampp\htdocs\NexWeb\
    api\  Base de datos\  Inicio\  Interfaces\  Login\  js\  img\ ...
```

Con Git: abre una terminal en `C:\xampp\htdocs` y ejecuta `git clone https://github.com/arlyjoss/NexWeb.git`.

### Paso 3 — Elegir la contraseña del usuario de la BD
Abre `Base de datos/database.sql`, busca `CambiaEsta_Contrasena_2026` (aparece 2 veces, al final) y cámbiala por una tuya, por ejemplo `NexWeb#Tienda2026`.

### Paso 4 — Crear la base de datos
1. Entra a **http://localhost/phpmyadmin**
2. Pestaña **Importar** → *Seleccionar archivo* → `Base de datos/database.sql` → **Importar** (o pestaña **SQL**, pega el contenido y **Continuar**).
3. A la izquierda debe aparecer la BD **nexweb** con 8 tablas y 1 vista.

### Paso 5 — Configurar la conexión
1. En la carpeta `api/`, copia `config.example.php` y renombra la copia a **`config.php`**.
2. Edita `config.php`:
   - `db_pass` → la misma contraseña del Paso 3.
   - `db_port` → 3306 (o 3307 si tu MySQL usa ese).

`config.php` está en `.gitignore`: **no se sube a GitHub**. Cada integrante crea el suyo.

### Paso 6 — Abrir la tienda
Abre **http://localhost/NexWeb/Inicio/index.html**

> ⚠ Abre siempre por `http://localhost/...`. Si das doble clic al `.html` (la barra dice `file:///C:/...`) la página no puede hablar con PHP y verás: *"Abre la página desde http://localhost/NexWeb/..."*.

### Paso 7 — Crear un administrador (opcional)
Regístrate en la página y luego, en phpMyAdmin (pestaña SQL):

```sql
UPDATE nexweb.usuarios SET rol = 'admin' WHERE correo = 'tu@correo.com';
```

Cierra sesión y vuelve a entrar. Como admin puedes abrir `http://localhost/NexWeb/api/admin/pedidos.php` (todos los pedidos) y `http://localhost/NexWeb/Chat de Tienda/index.php` (todos los mensajes del chat).

---

## 4. Archivos del proyecto

### Nuevos
| Archivo | Qué hace |
|---|---|
| `Base de datos/database.sql` | Crea la BD, tablas, relaciones, restricciones, vista, datos iniciales y el usuario `nexweb_app`. |
| `Base de datos/modelo_er.md` | Diagrama ER (GitHub lo dibuja solo), cardinalidades y normalización. |
| `Base de datos/consultas_prueba.sql` | Consultas de negocio y pruebas de restricciones y permisos. |
| `api/config.example.php` | Plantilla de configuración (se copia a `config.php`). |
| `api/nucleo/conexion.php` | Conexión PDO única. |
| `api/nucleo/seguridad.php` | Sesión segura, CSRF, validación, roles, cabeceras de seguridad. |
| `api/sesion.php` | `GET` — ¿quién está conectado? + token CSRF. |
| `api/registro.php` | `POST` — crear cuenta. |
| `api/login.php` / `api/logout.php` | `POST` — entrar / salir. |
| `api/productos.php` | `GET` — catálogo, o un producto con sus tallas (`?id=3`). |
| `api/pedidos.php` | `POST` — pagar el carrito; `GET` — mis pedidos. |
| `api/chat.php` | `POST` — guardar mensaje; `GET` — mis mensajes. |
| `api/admin/pedidos.php` | `GET` — todos los pedidos (solo admin). |
| `js/nexweb.js` | Funciones compartidas del frontend para llamar a la API. |
| `.gitignore`, `api/.htaccess`, `api/nucleo/.htaccess` | Protegen la configuración y los archivos internos. |

### Modificados
`Interfaces/productos.js`, `detalle.js`, `carrito.js`, `Login/login.js`, los `<script>` de las páginas HTML, el chat de `Inicio/index.html` (2 líneas) y `Chat de Tienda/index.php` (ahora usa la BD `nexweb`). `Chat de Tienda/esquema.sql` quedó reemplazado por `database.sql`.

---

## 5. Cómo funciona una compra (de punta a punta)

1. **detalle.html** pide `api/productos.php?id=7`. PHP consulta `vista_catalogo` y `producto_tallas` y responde JSON. La talla 29 del Vaporfly tiene stock 0, así que aparece *"29 (agotada)"* y no se puede elegir.
2. Al agregar, el producto se guarda en `localStorage` (id, talla, cantidad).
3. En **carrito.html**, "Proceder al pago" manda a `api/pedidos.php` **solo** `id`, `talla` y `cantidad`.
4. Si no hay sesión → responde `401` → la página lleva a `login.html?volver=carrito` y, después de entrar, regresa al carrito.
5. Con sesión, PHP abre una **transacción**:
   - lee el precio **de la BD** (si alguien cambia el precio con F12, se ignora);
   - descuenta stock con `UPDATE ... WHERE stock >= cantidad` (si no alcanza, no descuenta);
   - inserta `pedidos` y `detalle_pedido`;
   - si **cualquier** paso falla → `ROLLBACK`: no se guarda nada y el stock queda igual.
6. Responde `¡Gracias por tu compra! Pedido #N…` y se vacía el carrito.

---

## 6. Seguridad aplicada

| Riesgo | Qué podría pasar | Cómo se evita |
|---|---|---|
| **Inyección SQL** | Escribir `' OR 1=1 --` en un campo y leer o borrar datos. | 100 % consultas preparadas con PDO (`prepare` + `execute`), con `EMULATE_PREPARES = false`. Nunca se concatena texto del usuario en el SQL. |
| **Contraseñas robadas** | Si se filtra la BD, se leen las contraseñas. | `password_hash()` (bcrypt con sal aleatoria). En la tabla solo hay `$2y$10$...`, irreversible. |
| **Fuerza bruta** | Probar miles de contraseñas. | Tabla `intentos_login`: 5 fallos por correo (o 20 por IP) en 15 min bloquean el login. |
| **Adivinar qué correos existen** | El login dice "ese correo no existe". | Mismo mensaje y mismo tiempo de respuesta en ambos casos (se verifica contra un hash falso). |
| **Robo de sesión** | Robar la cookie y entrar como otro. | Cookie `HttpOnly` (JS no la lee), `SameSite=Strict`, `Secure` con HTTPS, `session_regenerate_id()` al entrar, cierre a los 30 min sin actividad. |
| **CSRF** | Otra página hace compras con tu sesión. | Token CSRF aleatorio obligatorio en cada `POST` (cabecera `X-CSRF-Token`) + `SameSite=Strict`. |
| **XSS** | Un nombre o mensaje con `<script>` se ejecuta en otra pantalla. | En JS: `textContent` o `NexWeb.escaparHTML()` antes de `innerHTML`. En PHP: `htmlspecialchars()`. |
| **Precio manipulado** | Cambiar el precio en `localStorage` y pagar $1. | El servidor ignora el precio del navegador y usa el de la tabla. |
| **Volverse admin** | Mandar `"rol":"admin"` al registrarse. | El rol nunca se lee de la petición; siempre es `cliente`. |
| **Stock negativo / sobreventa** | Dos compras al mismo tiempo de la última talla. | `UPDATE` condicional atómico + transacción + columna `UNSIGNED`. |
| **Daño si hackean PHP** | Con `root`, el atacante podría borrar toda la BD. | **Mínimo privilegio:** `nexweb_app` no puede borrar tablas, borrar pedidos, cambiar precios ni cambiar roles (permisos por tabla y por columna). |
| **Contraseña de BD en GitHub** | Cualquiera la lee en el repo. | Va en `api/config.php`, excluido con `.gitignore`. |
| **Mostrar errores internos** | El error revela rutas y SQL. | Manejador global: mensaje genérico. El detalle solo sale con `modo_desarrollo = true`. |
| **Datos basura** | Talla 45, precio negativo, cantidad −5. | Validación en JS **y** en PHP, más `CHECK`, `UNIQUE`, `ENUM`, `UNSIGNED` y llaves foráneas en la BD (tres capas). |
| **Privacidad del chat** | Cualquiera lee los mensajes de todos (pasaba en el `index.php` original). | Un cliente solo ve los suyos; el admin ve todos; un visitante no ve ninguno. |

---

## 7. Pruebas que se hicieron (y que puedes repetir)

Todo se probó en MariaDB 10.11 + PHP 8.3, con un navegador real automatizado:

- ✅ El script corre dos veces seguidas sin errores ni datos duplicados.
- ✅ Catálogo: 12 productos; filtro Nike → 4.
- ✅ Detalle: la talla 29 del Vaporfly sale deshabilitada.
- ✅ Pagar sin sesión → manda al login y regresa al carrito.
- ✅ Registro → sesión abierta → menú "Hola, Ana · Salir".
- ✅ Compra → pedido guardado, stock descontado, total calculado por el servidor.
- ✅ Pedido con un renglón sin stock → rechazado y **todo** revertido (el otro renglón no se descontó).
- ✅ Precio alterado a `1` desde el cliente → ignorado.
- ✅ `?id=1 OR 1=1` y `marca=Nike' OR '1'='1` → no regresan datos de más.
- ✅ 6.º intento de contraseña incorrecta → bloqueado 15 min.
- ✅ Cliente pidiendo `api/admin/pedidos.php` → 403.
- ✅ `<script>` en el chat → se guarda como texto y se muestra escapado.
- ✅ `nexweb_app` intentando `UPDATE productos`, `UPDATE usuarios SET rol` y `DROP TABLE` → *command denied*.
- ✅ Las 7 pruebas de restricciones de `consultas_prueba.sql` (sección B) → todas fallan como deben.
- ✅ Cero errores de JavaScript en consola.

---

## 8. Problemas comunes

| Mensaje / síntoma | Causa | Solución |
|---|---|---|
| *"Abre la página desde http://localhost/NexWeb/..."* | Abriste el `.html` con doble clic. | Entra por `http://localhost/NexWeb/Inicio/index.html`. |
| *"No hay conexión con el servidor"* | Apache apagado. | Start en Apache (XAMPP). |
| *"Falta api/config.php"* | No copiaste la plantilla. | Paso 5. |
| *"Access denied for user 'nexweb_app'"* | Contraseña distinta en `database.sql` y `config.php`, o no corriste el script. | Que sean iguales; vuelve a importar `database.sql`. |
| *"Connection refused"* o *"No connection could be made"* | MySQL apagado o puerto equivocado. | Start en MySQL; revisa `db_port` (3306 o 3307). |
| *"Unknown database 'nexweb'"* | No se importó el script. | Paso 4. |
| El catálogo carga pero no hay imágenes | El nombre del archivo en `productos.imagen` no existe en `/img`. | Revisa que coincidan. |
| *"Token de seguridad inválido"* | La sesión expiró (30 min). | Recarga la página. |
| *"Demasiados intentos fallidos"* | 5 contraseñas mal. | Espera 15 min, o como root: `DELETE FROM nexweb.intentos_login;` |
| Apache no arranca (puerto 80 ocupado) | Skype/IIS usan el puerto 80. | Ciérralos o cambia el puerto de Apache en XAMPP → Config → `httpd.conf`. |

---

## 9. Pendientes y recomendaciones para el equipo

- **Botones de "Productos destacados" en Inicio:** agregan al carrito sin `id` ni talla, así que no se pueden pagar (el carrito lo avisa). Sugerencia: que esos botones lleven a `../Interfaces/detalle.html?id=2` (Converse), `?id=4` (Vans), `?id=3` (Nike), `?id=1` (Adidas). Además, sus precios no coinciden con los de la BD.
- **Persona de permisos/sesiones:** la base ya existe (`rol` cliente/admin, `exigirLogin()`, `exigirRol('admin')` en `api/nucleo/seguridad.php`). Para un panel de admin que edite productos, conviene crear un segundo usuario de BD con permisos de escritura solo para esas rutas.
- **Publicar en internet:** usar HTTPS (la cookie se vuelve `Secure` sola), poner `modo_desarrollo => false` y usar una contraseña larga para `nexweb_app`.
- **Pago real:** hoy el pedido queda en estado `pendiente`; una pasarela (Stripe, Mercado Pago) lo cambiaría a `pagado`.

---

## 10. Referencia rápida de la API

| Método | Ruta | Sesión | Cuerpo / parámetros | Respuesta |
|---|---|---|---|---|
| GET | `api/sesion.php` | — | — | `{autenticado, usuario, csrf}` |
| POST | `api/registro.php` | — | `{nombre, correo, contrasena, confirmar}` | 201 / 409 / 422 |
| POST | `api/login.php` | — | `{correo, contrasena}` | 200 / 401 / 429 |
| POST | `api/logout.php` | — | `{}` | 200 |
| GET | `api/productos.php` | — | `?marca=&categoria=` opcionales | `{productos:[...]}` |
| GET | `api/productos.php?id=N` | — | — | `{producto:{..., tallas:[{talla, stock}]}}` |
| POST | `api/pedidos.php` | cliente | `{items:[{id, talla, cantidad}]}` | 201 / 401 / 409 |
| GET | `api/pedidos.php` | cliente | — | `{pedidos:[...]}` |
| POST | `api/chat.php` | — | `{tipo:"usuario"\|"bot", mensaje}` | 201 |
| GET | `api/chat.php` | cliente | — | `{mensajes:[...]}` |
| GET | `api/admin/pedidos.php` | admin | — | `{pedidos:[...]}` |

Todos los `POST` requieren la cabecera `X-CSRF-Token` (la pone `js/nexweb.js` automáticamente).
