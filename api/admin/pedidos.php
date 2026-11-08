<?php
/**
 * GET api/admin/pedidos.php  → todos los pedidos de la tienda (solo rol admin)
 *
 * Ejemplo de cómo proteger una ruta por rol. Un cliente recibe 403
 * y alguien sin sesión recibe 401.
 */
require __DIR__ . '/../nucleo/seguridad.php';
exigirMetodo('GET');
exigirRol('admin');

$stmt = obtenerConexion()->query(
    'SELECT p.id, u.nombre AS cliente, u.correo, p.total, p.estado, p.creado_en,
            SUM(d.cantidad) AS pares
     FROM pedidos p
     JOIN usuarios u       ON u.id = p.usuario_id
     JOIN detalle_pedido d ON d.pedido_id = p.id
     GROUP BY p.id, u.nombre, u.correo, p.total, p.estado, p.creado_en
     ORDER BY p.creado_en DESC
     LIMIT 200'
);

responder(200, ['pedidos' => $stmt->fetchAll()]);
