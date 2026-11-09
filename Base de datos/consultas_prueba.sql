-- =====================================================================
--  NexWeb — Consultas y pruebas de la base de datos
--  Ejecútalas en phpMyAdmin (pestaña SQL) como root, DESPUÉS de
--  database.sql y de haber hecho al menos una compra desde la página.
-- =====================================================================
USE nexweb;

-- ---------------------------------------------------------------------
-- A. CONSULTAS DE NEGOCIO
-- ---------------------------------------------------------------------

-- A1. Catálogo completo con marca, categoría y stock total (usa la vista)
SELECT * FROM vista_catalogo ORDER BY marca, nombre;

-- A2. Productos de una marca y categoría (lo mismo que hacen los filtros)
SELECT nombre, precio FROM vista_catalogo
WHERE marca = 'Nike' AND categoria = 'Casual';

-- A3. Tallas con poco inventario (3 pares o menos) — para resurtir
SELECT p.nombre, t.talla, t.stock
FROM producto_tallas t
JOIN productos p ON p.id = t.producto_id
WHERE t.stock <= 3
ORDER BY t.stock, p.nombre;

-- A4. Productos más vendidos (pares vendidos e ingreso)
SELECT p.nombre,
       SUM(d.cantidad) AS pares_vendidos,
       SUM(d.subtotal) AS ingreso
FROM detalle_pedido d
JOIN productos p ON p.id = d.producto_id
JOIN pedidos pe  ON pe.id = d.pedido_id
WHERE pe.estado <> 'cancelado'
GROUP BY p.id, p.nombre
ORDER BY pares_vendidos DESC
LIMIT 5;

-- A5. Ventas por marca
SELECT m.nombre AS marca, COUNT(DISTINCT d.pedido_id) AS pedidos, SUM(d.subtotal) AS ventas
FROM detalle_pedido d
JOIN productos p ON p.id = d.producto_id
JOIN marcas m    ON m.id = p.marca_id
GROUP BY m.nombre
ORDER BY ventas DESC;

-- A6. Pedidos de cada cliente y cuánto ha gastado
SELECT u.nombre, u.correo, COUNT(pe.id) AS pedidos, COALESCE(SUM(pe.total), 0) AS gastado
FROM usuarios u
LEFT JOIN pedidos pe ON pe.usuario_id = u.id
GROUP BY u.id, u.nombre, u.correo
ORDER BY gastado DESC;

-- A7. Detalle completo de un pedido (cambia el 1 por el número de pedido)
SELECT pe.id AS pedido, u.nombre AS cliente, p.nombre AS producto,
       d.talla, d.cantidad, d.precio_unitario, d.subtotal, pe.total, pe.estado
FROM pedidos pe
JOIN usuarios u       ON u.id = pe.usuario_id
JOIN detalle_pedido d ON d.pedido_id = pe.id
JOIN productos p      ON p.id = d.producto_id
WHERE pe.id = 1;

-- A8. Comprobación de integridad: el total de cada pedido debe ser
--     igual a la suma de sus renglones (debe regresar 0 filas)
SELECT pe.id, pe.total, SUM(d.subtotal) AS suma_renglones
FROM pedidos pe
JOIN detalle_pedido d ON d.pedido_id = pe.id
GROUP BY pe.id, pe.total
HAVING pe.total <> SUM(d.subtotal);

-- A9. Intentos de login fallidos en la última hora (detectar ataques)
SELECT correo, ip, COUNT(*) AS fallos, MAX(fecha) AS ultimo
FROM intentos_login
WHERE exito = 0 AND fecha > NOW() - INTERVAL 1 HOUR
GROUP BY correo, ip
ORDER BY fallos DESC;

-- A10. Últimos mensajes del chat con su autor
SELECT m.creado_en, COALESCE(u.nombre, 'Visitante') AS autor, m.tipo, m.mensaje
FROM mensajes_chat m
LEFT JOIN usuarios u ON u.id = m.usuario_id
ORDER BY m.id DESC
LIMIT 20;

-- ---------------------------------------------------------------------
-- B. PRUEBAS DE RESTRICCIONES
--    Cada una DEBE FALLAR con error. Si alguna se ejecuta sin error,
--    la restricción no está funcionando. (Ejecútalas una por una.)
-- ---------------------------------------------------------------------

-- B1. Correo repetido → error de UNIQUE (cambia el correo por uno que exista)
-- INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES ('Copia', 'o@test.com', 'x');

-- B2. Precio negativo → error de CHECK ck_productos_precio
-- INSERT INTO productos (nombre, marca_id, categoria_id, precio, imagen) VALUES ('Malo', 1, 1, -50, 'x.jpg');

-- B3. Talla fuera de rango → error de CHECK ck_talla_rango
-- INSERT INTO producto_tallas (producto_id, talla, stock) VALUES (1, 45, 5);

-- B4. Stock negativo → error (columna UNSIGNED)
-- UPDATE producto_tallas SET stock = stock - 1000 WHERE producto_id = 1 AND talla = 25;

-- B5. Producto con marca que no existe → error de llave foránea
-- INSERT INTO productos (nombre, marca_id, categoria_id, precio, imagen) VALUES ('Fantasma', 99, 1, 100, 'x.jpg');

-- B6. Borrar un producto que ya se vendió → error (ON DELETE RESTRICT)
-- DELETE FROM productos WHERE id = (SELECT producto_id FROM detalle_pedido LIMIT 1);

-- B7. Rol inventado → error (ENUM en modo estricto)
-- UPDATE usuarios SET rol = 'superadmin' WHERE id = 1;

-- ---------------------------------------------------------------------
-- C. PRUEBAS DE PERMISOS DEL USUARIO DE LA APLICACIÓN
--    Entra a phpMyAdmin (o a la terminal) como nexweb_app y prueba.
--    Todas DEBEN dar "command denied".
-- ---------------------------------------------------------------------
-- UPDATE productos SET precio = 1 WHERE id = 1;     -- no puede cambiar precios
-- UPDATE usuarios SET rol = 'admin';                -- no puede hacerse admin
-- DELETE FROM pedidos;                              -- no puede borrar pedidos
-- DROP TABLE usuarios;                              -- no puede borrar tablas
