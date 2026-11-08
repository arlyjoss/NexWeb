<?php
/**
 * POST api/chat.php   → guarda un mensaje del chat de soporte
 *   Cuerpo: { "tipo": "usuario" | "bot", "mensaje": "..." }
 * GET  api/chat.php   → últimos 50 mensajes del usuario con sesión
 *                       (un visitante sin cuenta no puede leer mensajes de nadie)
 */
require __DIR__ . '/nucleo/seguridad.php';

$pdo     = obtenerConexion();
$usuario = usuarioActual();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $u = exigirLogin();
    $stmt = $pdo->prepare(
        'SELECT tipo, mensaje, creado_en FROM mensajes_chat
         WHERE usuario_id = :id ORDER BY id DESC LIMIT 50'
    );
    $stmt->execute([':id' => $u['id']]);
    responder(200, ['mensajes' => array_reverse($stmt->fetchAll())]);
}

exigirMetodo('POST');
verificarCsrf();

// Límite anti-spam: máximo 20 mensajes por minuto por sesión
$ahora = time();
$_SESSION['chat_envios'] = array_filter($_SESSION['chat_envios'] ?? [], function ($t) use ($ahora) {
    return $t > $ahora - 60;
});
if (count($_SESSION['chat_envios']) >= 20) {
    responder(429, ['error' => 'Estás enviando mensajes muy rápido.']);
}
$_SESSION['chat_envios'][] = $ahora;

$datos   = leerJson();
$mensaje = textoDe($datos, 'mensaje', 500);
$tipo    = in_array($datos['tipo'] ?? '', ['usuario', 'bot'], true) ? $datos['tipo'] : 'usuario';

if ($mensaje === '') {
    responder(422, ['error' => 'El mensaje está vacío.']);
}

// Se guarda el texto tal cual; al MOSTRARLO se escapa (textContent / htmlspecialchars).
$pdo->prepare('INSERT INTO mensajes_chat (usuario_id, tipo, mensaje) VALUES (:usuario, :tipo, :mensaje)')
    ->execute([
        ':usuario' => $usuario['id'] ?? null,
        ':tipo'    => $tipo,
        ':mensaje' => $mensaje,
    ]);

responder(201, ['mensaje' => 'Guardado.']);
