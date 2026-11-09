<?php
/**
 * GET api/productos.php               → lista del catálogo
 * GET api/productos.php?id=3          → un producto con sus tallas y stock
 * GET api/productos.php?marca=Nike&categoria=Casual   → filtrado (opcional)
 */
require __DIR__ . '/nucleo/seguridad.php';
exigirMetodo('GET');

$pdo = obtenerConexion();

// ---- Un solo producto ----
if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        responder(400, ['error' => 'id inválido.']);
    }

    $stmt = $pdo->prepare('SELECT id, nombre, marca, categoria, precio, imagen, descripcion, stock_total
                           FROM vista_catalogo WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $producto = $stmt->fetch();
    if (!$producto) {
        responder(404, ['error' => 'Producto no encontrado.']);
    }

    $stmt = $pdo->prepare('SELECT talla, stock FROM producto_tallas WHERE producto_id = :id ORDER BY talla');
    $stmt->execute([':id' => $id]);

    $producto = normalizar($producto);
    $producto['tallas'] = array_map(function ($t) {
        return ['talla' => (int) $t['talla'], 'stock' => (int) $t['stock']];
    }, $stmt->fetchAll());

    responder(200, ['producto' => $producto]);
}

// ---- Lista, con filtros opcionales ----
$sql = 'SELECT id, nombre, marca, categoria, precio, imagen, stock_total FROM vista_catalogo WHERE 1 = 1';
$parametros = [];

if (!empty($_GET['marca']) && is_string($_GET['marca'])) {
    $sql .= ' AND marca = :marca';
    $parametros[':marca'] = mb_substr($_GET['marca'], 0, 50);
}
if (!empty($_GET['categoria']) && is_string($_GET['categoria'])) {
    $sql .= ' AND categoria = :categoria';
    $parametros[':categoria'] = mb_substr($_GET['categoria'], 0, 50);
}
$sql .= ' ORDER BY id';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

responder(200, ['productos' => array_map('normalizar', $stmt->fetchAll())]);

/** MySQL regresa números como texto; aquí se convierten a número. */
function normalizar(array $p): array
{
    $p['id']          = (int) $p['id'];
    $p['precio']      = (float) $p['precio'];
    $p['stock_total'] = (int) $p['stock_total'];
    return $p;
}
