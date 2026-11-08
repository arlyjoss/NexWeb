-- =====================================================================
--  NexWeb — Base de datos de la tienda de tenis
--  Motor: MySQL 8.0+ o MariaDB 10.4+ (la que trae XAMPP)
--
--  Cómo ejecutarlo:
--    phpMyAdmin → pestaña "SQL" (o "Importar") → pegar/subir este archivo → Continuar
--    o en terminal:  mysql -u root -p < database.sql
--
--  El script es "idempotente": se puede correr varias veces sin duplicar
--  datos (usa IF NOT EXISTS e INSERT IGNORE).
--  Si cambias la estructura de una tabla y quieres empezar de cero,
--  descomenta la línea DROP DATABASE (¡borra TODO!).
-- =====================================================================

-- DROP DATABASE IF EXISTS nexweb;

CREATE DATABASE IF NOT EXISTS nexweb
    CHARACTER SET utf8mb4            -- soporta acentos, ñ y emojis
    COLLATE utf8mb4_unicode_ci;

USE nexweb;

-- ---------------------------------------------------------------------
-- 1. USUARIOS
--    Guarda clientes y administradores. La contraseña NUNCA se guarda
--    en texto plano: PHP la cifra con password_hash() (bcrypt/argon2)
--    y aquí solo queda el hash (60–255 caracteres).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(100)  NOT NULL,
    correo           VARCHAR(150)  NOT NULL,
    contrasena_hash  VARCHAR(255)  NOT NULL,
    rol              ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    activo           TINYINT(1)    NOT NULL DEFAULT 1,
    creado_en        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso    DATETIME      NULL,
    CONSTRAINT uq_usuarios_correo UNIQUE (correo),
    CONSTRAINT ck_usuarios_nombre CHECK (CHAR_LENGTH(nombre) >= 2)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. CATÁLOGOS: MARCAS y CATEGORÍAS
--    Se separan en tablas propias (normalización) para no repetir
--    "Nike" en cada producto y para llenar los filtros del catálogo.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marcas (
    id      SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(50) NOT NULL,
    CONSTRAINT uq_marcas_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id      SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(50) NOT NULL,
    CONSTRAINT uq_categorias_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. PRODUCTOS
--    El precio vive SOLO aquí. Al comprar, el servidor toma el precio
--    de esta tabla (nunca el que manda el navegador, que se puede alterar).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(120)  NOT NULL,
    marca_id      SMALLINT UNSIGNED NOT NULL,
    categoria_id  SMALLINT UNSIGNED NOT NULL,
    precio        DECIMAL(10,2) NOT NULL,
    imagen        VARCHAR(150)  NOT NULL,      -- solo el nombre del archivo dentro de /img
    descripcion   VARCHAR(500)  NULL,
    activo        TINYINT(1)    NOT NULL DEFAULT 1,  -- 0 = oculto del catálogo sin borrarlo
    creado_en     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_productos_nombre UNIQUE (nombre),
    CONSTRAINT ck_productos_precio CHECK (precio > 0),
    CONSTRAINT fk_productos_marca     FOREIGN KEY (marca_id)     REFERENCES marcas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX ix_productos_marca (marca_id),
    INDEX ix_productos_categoria (categoria_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. INVENTARIO POR TALLA
--    Un mismo tenis tiene stock distinto en cada talla (23 a 29 en la
--    página de detalle). Llave primaria compuesta: (producto, talla).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS producto_tallas (
    producto_id  INT UNSIGNED     NOT NULL,
    talla        TINYINT UNSIGNED NOT NULL,
    stock        INT UNSIGNED     NOT NULL DEFAULT 0,   -- UNSIGNED: nunca negativo
    PRIMARY KEY (producto_id, talla),
    CONSTRAINT ck_talla_rango CHECK (talla BETWEEN 22 AND 32),
    CONSTRAINT fk_tallas_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. PEDIDOS y DETALLE DEL PEDIDO
--    pedidos = encabezado (quién, cuándo, total, estado)
--    detalle_pedido = renglones (qué producto, talla, cantidad, precio)
--    precio_unitario se COPIA al momento de comprar para que el
--    historial no cambie si después sube el precio del producto.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED  NOT NULL,
    total       DECIMAL(10,2) NOT NULL,
    estado      ENUM('pendiente','pagado','enviado','entregado','cancelado')
                NOT NULL DEFAULT 'pendiente',
    creado_en   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_pedidos_total CHECK (total >= 0),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX ix_pedidos_usuario (usuario_id, creado_en)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detalle_pedido (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id        INT UNSIGNED     NOT NULL,
    producto_id      INT UNSIGNED     NOT NULL,
    talla            TINYINT UNSIGNED NOT NULL,
    cantidad         SMALLINT UNSIGNED NOT NULL,
    precio_unitario  DECIMAL(10,2)    NOT NULL,
    subtotal         DECIMAL(10,2) AS (cantidad * precio_unitario) STORED,  -- calculado por la BD
    CONSTRAINT ck_detalle_cantidad CHECK (cantidad BETWEEN 1 AND 20),
    CONSTRAINT ck_detalle_precio   CHECK (precio_unitario > 0),
    CONSTRAINT fk_detalle_pedido   FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX ix_detalle_pedido (pedido_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. CHAT DE SOPORTE
--    Reemplaza la BD "tienda_simple" de la carpeta "Chat de Tienda".
--    usuario_id es opcional: un visitante sin cuenta también puede escribir.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mensajes_chat (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NULL,
    tipo        ENUM('usuario','bot','soporte') NOT NULL DEFAULT 'usuario',
    mensaje     VARCHAR(500) NOT NULL,
    creado_en   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_chat_mensaje CHECK (CHAR_LENGTH(mensaje) >= 1),
    CONSTRAINT fk_chat_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX ix_chat_fecha (creado_en)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. INTENTOS DE INICIO DE SESIÓN (seguridad)
--    Sirve para bloquear ataques de fuerza bruta: si un correo o una IP
--    acumula 5 fallos en 15 minutos, el login se bloquea temporalmente.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS intentos_login (
    id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    correo  VARCHAR(150) NOT NULL,
    ip      VARCHAR(45)  NOT NULL,      -- 45 caracteres alcanzan para IPv6
    exito   TINYINT(1)   NOT NULL,
    fecha   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX ix_intentos_correo (correo, fecha),
    INDEX ix_intentos_ip (ip, fecha)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. VISTA DEL CATÁLOGO
--    Junta producto + marca + categoría + stock total en una sola
--    consulta. La API lee de aquí para no repetir los JOIN.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW vista_catalogo AS
SELECT  p.id,
        p.nombre,
        m.nombre       AS marca,
        c.nombre       AS categoria,
        p.precio,
        p.imagen,
        p.descripcion,
        COALESCE(SUM(t.stock), 0) AS stock_total
FROM productos p
JOIN marcas m          ON m.id = p.marca_id
JOIN categorias c      ON c.id = p.categoria_id
LEFT JOIN producto_tallas t ON t.producto_id = p.id
WHERE p.activo = 1
GROUP BY p.id, p.nombre, m.nombre, c.nombre, p.precio, p.imagen, p.descripcion;

-- =====================================================================
--  DATOS INICIALES (los mismos 12 tenis que tenía productos.js)
-- =====================================================================
INSERT IGNORE INTO marcas (id, nombre) VALUES
    (1,'Adidas'), (2,'Asics'), (3,'Converse'), (4,'Fila'),
    (5,'New Balance'), (6,'Nike'), (7,'Vans');

INSERT IGNORE INTO categorias (id, nombre) VALUES
    (1,'Deportivo'), (2,'Casual'), (3,'Dama'), (4,'Caballero');

INSERT IGNORE INTO productos (id, nombre, marca_id, categoria_id, precio, imagen) VALUES
    ( 1,'Tenis Adidas Superstar',        1, 1, 1299.00, 'prod-adidas.jpg'),
    ( 2,'Tenis Converse Chuck Taylor',   3, 2,  999.00, 'prod-converse.jpg'),
    ( 3,'Tenis Nike Air Max',            6, 1, 1499.00, 'prod-nike.jpg'),
    ( 4,'Tenis Vans Old Skool',          7, 2, 1099.00, 'prod-vans.jpg'),
    ( 5,'Tenis Nike Dunk Low Blue',      6, 2, 1899.00, 'cat-caballero-a.jpg'),
    ( 6,'Tenis Vans Sk8-Hi Black',       7, 2, 1150.00, 'cat-caballero-b.jpg'),
    ( 7,'Tenis Nike Vaporfly Pro',       6, 1, 2199.00, 'cat-deportivo-a.jpg'),
    ( 8,'Tenis Nike Air Force Pastel',   6, 2, 1599.00, 'cat-dama-a.jpg'),
    ( 9,'Tenis Fila Disruptor White',    4, 2, 1250.00, 'cat-dama-b.jpg'),
    (10,'Tenis Asics Gel-Kayano',        2, 1, 1799.00, 'cat-deportivo-b.jpg'),
    (11,'Tenis New Balance 550 Green',   5, 2, 1650.00, 'cat-casual-a.jpg'),
    (12,'Tenis Adidas Ultraboost Black', 1, 1, 1799.00, 'cat-deportivo-c.jpg');

-- 10 pares de cada talla 23–29 para cada producto (producto cartesiano)
INSERT IGNORE INTO producto_tallas (producto_id, talla, stock)
SELECT p.id, t.talla, 10
FROM productos p
CROSS JOIN (SELECT 23 AS talla UNION ALL SELECT 24 UNION ALL SELECT 25 UNION ALL
            SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29) t;

-- Para poder probar el mensaje de "sin stock": Vaporfly talla 29 agotada
UPDATE producto_tallas SET stock = 0 WHERE producto_id = 7 AND talla = 29;

-- =====================================================================
--  USUARIO DE LA APLICACIÓN (principio de mínimo privilegio)
--
--  La página web NO debe conectarse como root. Se crea un usuario que
--  solo puede hacer lo que la tienda necesita: no puede borrar tablas,
--  ni borrar productos, ni cambiar precios, ni volverse admin.
--
--  ⚠ CAMBIA la contraseña de abajo por una propia (y pon la misma en
--    api/config.php). Se crea para 'localhost' y '127.0.0.1' porque
--    según el sistema PHP se conecta por uno u otro.
-- =====================================================================
CREATE USER IF NOT EXISTS 'nexweb_app'@'localhost' IDENTIFIED BY 'CambiaEsta_Contrasena_2026';
CREATE USER IF NOT EXISTS 'nexweb_app'@'127.0.0.1' IDENTIFIED BY 'CambiaEsta_Contrasena_2026';

-- Leer todo el esquema
GRANT SELECT ON nexweb.* TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';
-- Insertar solo donde la página crea registros
GRANT INSERT ON nexweb.usuarios       TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';
GRANT INSERT ON nexweb.pedidos        TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';
GRANT INSERT ON nexweb.detalle_pedido TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';
GRANT INSERT ON nexweb.mensajes_chat  TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';
GRANT INSERT, DELETE ON nexweb.intentos_login TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';
-- Actualizar solo columnas concretas (privilegio por columna)
GRANT UPDATE (ultimo_acceso) ON nexweb.usuarios        TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';
GRANT UPDATE (stock)         ON nexweb.producto_tallas TO 'nexweb_app'@'localhost', 'nexweb_app'@'127.0.0.1';

FLUSH PRIVILEGES;

-- =====================================================================
--  CREAR UN ADMINISTRADOR
--  1) Regístrate normalmente desde la página (registro.html).
--  2) Como root, en phpMyAdmin ejecuta:
--       UPDATE nexweb.usuarios SET rol = 'admin' WHERE correo = 'tu@correo.com';
--  (No se incluye un admin con contraseña fija porque quedaría pública en GitHub.)
-- =====================================================================
