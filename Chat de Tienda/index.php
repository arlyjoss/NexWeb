<?php
// Usa la misma conexión y seguridad que el resto de la API (BD "nexweb").
require __DIR__ . '/../api/nucleo/seguridad.php';
header('Content-Type: text/html; charset=utf-8');   // esta página es HTML, no JSON

$pdo     = obtenerConexion();
$usuario = usuarioActual();
$aviso   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Token CSRF del formulario
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(tokenCsrf(), $token)) {
        $aviso = 'La página expiró, vuelve a intentarlo.';
    } else {
        $msg = trim((string) ($_POST['mensaje'] ?? ''));
        if ($msg !== '' && mb_strlen($msg) <= 500) {
            // Consulta preparada: el texto nunca se mezcla con el SQL
            $stmt = $pdo->prepare('INSERT INTO mensajes_chat (usuario_id, tipo, mensaje) VALUES (:u, :t, :m)');
            $stmt->execute([
                ':u' => $usuario['id'] ?? null,
                ':t' => ($usuario['rol'] ?? '') === 'admin' ? 'soporte' : 'usuario',
                ':m' => $msg,
            ]);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));  // evita reenviar al recargar
            exit;
        }
        $aviso = 'El mensaje debe tener entre 1 y 500 caracteres.';
    }
}

// Privacidad: el admin ve todo; un cliente solo sus mensajes; un visitante, nada.
$mensajes = [];
if ($usuario && $usuario['rol'] === 'admin') {
    $mensajes = $pdo->query(
        'SELECT m.tipo, m.mensaje, m.creado_en, COALESCE(u.nombre, \'Visitante\') AS autor
         FROM mensajes_chat m LEFT JOIN usuarios u ON u.id = m.usuario_id
         ORDER BY m.id DESC LIMIT 100'
    )->fetchAll();
} elseif ($usuario) {
    $stmt = $pdo->prepare(
        'SELECT tipo, mensaje, creado_en, :nombre AS autor FROM mensajes_chat
         WHERE usuario_id = :id ORDER BY id DESC LIMIT 50'
    );
    $stmt->execute([':nombre' => $usuario['nombre'], ':id' => $usuario['id']]);
    $mensajes = $stmt->fetchAll();
}

function e($texto) { return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chat de soporte | NexWeb</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .contenedor { max-width: 600px; background: white; padding: 20px; border-radius: 8px; }
        .chat-box { border: 1px solid #ccc; height: 250px; overflow-y: scroll; padding: 10px; margin-bottom: 10px; }
        .chat-box small { color: #777; }
        .aviso { color: #b00020; }
        input[type="text"] { width: 75%; padding: 8px; }
        button { padding: 8px 12px; background: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
<div class="contenedor">
    <h1>Chat de Soporte NexWeb</h1>
    <p>
        <?php if ($usuario): ?>
            Sesión: <strong><?= e($usuario['nombre']) ?></strong> (<?= e($usuario['rol']) ?>)
        <?php else: ?>
            Estás como visitante. <a href="../Login/login.html">Inicia sesión</a> para ver tu historial.
        <?php endif; ?>
    </p>
    <hr>

    <div class="chat-box">
        <?php if (!$mensajes): ?>
            <p><em>No hay mensajes para mostrar.</em></p>
        <?php endif; ?>
        <?php foreach ($mensajes as $row): ?>
            <p>
                <strong><?= e($row['autor']) ?> (<?= e($row['tipo']) ?>):</strong>
                <?= e($row['mensaje']) ?><br>
                <small><?= e($row['creado_en']) ?></small>
            </p>
        <?php endforeach; ?>
    </div>

    <?php if ($aviso): ?><p class="aviso"><?= e($aviso) ?></p><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
        <input type="text" name="mensaje" maxlength="500" placeholder="Escribe un mensaje..." required>
        <button type="submit">Enviar</button>
    </form>
</div>
</body>
</html>
