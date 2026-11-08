<?php
/**
 * POST api/pedidos.php   → convierte el carrito en un pedido
 *   Cuerpo: { "items": [ { "id": 3, "talla": 27, "cantidad": 2 }, ... ] }
 *
 * GET  api/pedidos.php   → historial de pedidos del usuario con sesión
 *
 * Reglas de seguridad:
 *  - Hay que tener sesión iniciada.
 *  - El precio se toma de la BD, NO del navegador (se podría alterar con F12).
 *  - Todo ocurre dentro de una TRANSACCIÓN: o se guarda el pedido completo
 *    y se descuenta el stock, o no se guarda nada.
 */
require __DIR__ . '/nucleo/seguridad.php';

/** Errores "normales" del pedido (sin stock, producto inactivo) que sí se muestran al cliente. */
class ErrorDePedido extends Exception {}

$usuario = exigirLogin();
$pdo     = obtenerConexion();

// =====================================================================
// GET: mis pedidos
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT p.id AS pedido_id, p.total, p.estado, p.creado_en,
                d.producto_id, pr.nombre, d.talla, d.cantidad, d.precio_unitario, d.subtotal
         FROM pedidos p
         JOIN detalle_pedido d ON d.pedido_id = p.id
         JOIN productos pr     ON pr.id = d.producto_id
         WHERE p.usuario_id = :usuario
         ORDER BY p.creado_en DESC, d.id'
    );
    $stmt->execute([':usuario' => $usuario['id']]);

    $pedidos = [];
    foreach ($stmt->fetchAll() as $fila) {
        $id = (int) $fila['pedido_id'];
        if (!isset($pedidos[$id])) {
            $pedidos[$id] = [
                'id'        => $id,
                'total'     => (float) $fila['total'],
                'estado'    => $fila['estado'],
                'creado_en' => $fila['creado_en'],
                'productos' => [],
            ];
        }
        $pedidos[$id]['productos'][] = [
            'producto_id'     => (int) $fila['producto_id'],
            'nombre'          => $fila['nombre'],
            'talla'           => (int) $fila['talla'],
            'cantidad'        => (int) $fila['cantidad'],
            'precio_unitario' => (float) $fila['precio_unitario'],
            'subtotal'        => (float) $fila['subtotal'],
        ];
    }
    responder(200, ['pedidos' => array_values($pedidos)]);
}

// =====================================================================
// POST: crear pedido
// =====================================================================
exigirMetodo('POST');
verificarCsrf();

$datos = leerJson();
$items = $datos['items'] ?? null;

if (!is_array($items) || count($items) === 0) {
    responder(422, ['error' => 'El carrito está vacío.']);
}
if (count($items) > 30) {
    responder(422, ['error' => 'Demasiados productos en un solo pedido.']);
}

// ---- 1. Validar cada renglón y juntar repetidos (mismo id + talla) ----
$renglones = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        responder(422, ['error' => 'Formato de carrito inválido.']);
    }
    $id       = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $talla    = filter_var($item['talla'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 22, 'max_range' => 32]]);
    $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);

    if ($id === false || $talla === false || $cantidad === false) {
        responder(422, ['error' => 'Hay un producto con datos inválidos en el carrito (revisa talla y cantidad).']);
    }
    $clave = $id . '-' . $talla;
    $renglones[$clave] = [
        'id'       => $id,
        'talla'    => $talla,
        'cantidad' => ($renglones[$clave]['cantidad'] ?? 0) + $cantidad,
    ];
}

// ---- 2. Transacción ----
$buscarPrecio = $pdo->prepare('SELECT nombre, precio FROM productos WHERE id = :id AND activo = 1');

// UPDATE condicional: solo descuenta si alcanza el stock. Es atómico,
// así que dos compras al mismo tiempo no pueden dejar el stock negativo.
$descontar = $pdo->prepare(
    'UPDATE producto_tallas SET stock = stock - :cantidad
     WHERE producto_id = :id AND talla = :talla AND stock >= :cantidad2'
);

$pdo->beginTransaction();
try {
    $total   = 0.0;
    $detalle = [];

    foreach ($renglones as $r) {
        $buscarPrecio->execute([':id' => $r['id']]);
        $producto = $buscarPrecio->fetch();
        if (!$producto) {
            throw new ErrorDePedido('Un producto de tu carrito ya no está disponible.');
        }

        $descontar->execute([
            ':cantidad'  => $r['cantidad'],
            ':id'        => $r['id'],
            ':talla'     => $r['talla'],
            ':cantidad2' => $r['cantidad'],
        ]);
        if ($descontar->rowCount() !== 1) {
            throw new ErrorDePedido("No hay suficiente stock de {$producto['nombre']} en talla {$r['talla']}.");
        }

        $precio    = (float) $producto['precio'];
        $total    += $precio * $r['cantidad'];
        $detalle[] = $r + ['precio' => $precio];
    }

    $pdo->prepare('INSERT INTO pedidos (usuario_id, total) VALUES (:usuario, :total)')
        ->execute([':usuario' => $usuario['id'], ':total' => round($total, 2)]);
    $pedidoId = (int) $pdo->lastInsertId();

    $insertarDetalle = $pdo->prepare(
        'INSERT INTO detalle_pedido (pedido_id, producto_id, talla, cantidad, precio_unitario)
         VALUES (:pedido, :producto, :talla, :cantidad, :precio)'
    );
    foreach ($detalle as $d) {
        $insertarDetalle->execute([
            ':pedido'   => $pedidoId,
            ':producto' => $d['id'],
            ':talla'    => $d['talla'],
            ':cantidad' => $d['cantidad'],
            ':precio'   => $d['precio'],
        ]);
    }

    $pdo->commit();
} catch (ErrorDePedido $e) {
    $pdo->rollBack();                       // se deshace TODO (también el stock)
    responder(409, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;                               // lo atrapa el manejador global (500)
}

responder(201, [
    'mensaje'   => '¡Gracias por tu compra!',
    'pedido_id' => $pedidoId,
    'total'     => round($total, 2),
]);
