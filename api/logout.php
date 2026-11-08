<?php
/**
 * POST api/logout.php   — cierra la sesión
 */
require __DIR__ . '/nucleo/seguridad.php';
exigirMetodo('POST');
verificarCsrf();

$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => $p['path'],
    'secure'   => $p['secure'],
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_destroy();

responder(200, ['mensaje' => 'Sesión cerrada.']);
