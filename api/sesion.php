<?php
/**
 * GET api/sesion.php
 * Dice si hay alguien con sesión iniciada y entrega el token CSRF
 * que el frontend necesita para hacer peticiones POST.
 */
require __DIR__ . '/nucleo/seguridad.php';
exigirMetodo('GET');

$u = usuarioActual();
responder(200, [
    'autenticado' => $u !== null,
    'usuario'     => $u,
    'csrf'        => tokenCsrf(),
]);
