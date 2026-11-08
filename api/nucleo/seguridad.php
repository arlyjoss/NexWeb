<?php
/**
 * Funciones comunes de seguridad para todos los endpoints de la API.
 * Cada endpoint empieza con:   require __DIR__ . '/nucleo/seguridad.php';
 */

require_once __DIR__ . '/conexion.php';

// ---------------------------------------------------------------------
// Cabeceras HTTP de seguridad (se envían en cada respuesta de la API)
// ---------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');   // el navegador no "adivina" el tipo de archivo
header('X-Frame-Options: DENY');             // evita que metan la API en un iframe
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');           // no guardar respuestas con datos personales

// Si algo truena, no mostrar el error interno (rutas, SQL) al usuario.
set_exception_handler(function (Throwable $e) {
    error_log('NexWeb - excepción: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    $datos = ['error' => 'Ocurrió un error interno. Intenta de nuevo.'];
    $c = configuracion();
    if (!empty($c['modo_desarrollo'])) {
        $datos['detalle'] = $e->getMessage();
    }
    responder(500, $datos);
});

// ---------------------------------------------------------------------
// Sesión con cookie segura
// ---------------------------------------------------------------------
function iniciarSesion(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $esHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');     // rechaza IDs de sesión inventados
    ini_set('session.use_only_cookies', '1');    // nunca aceptar el ID por la URL
    session_name('NEXWEBSESSID');
    session_set_cookie_params([
        'lifetime' => 0,           // se borra al cerrar el navegador
        'path'     => '/',
        'secure'   => $esHttps,    // solo por HTTPS cuando el sitio lo tenga
        'httponly' => true,        // JavaScript no puede leer la cookie (protege contra XSS)
        'samesite' => 'Strict',    // otros sitios no pueden usar tu sesión (protege contra CSRF)
    ]);
    session_start();

    // Cierre por inactividad: 30 minutos
    $ahora = time();
    if (isset($_SESSION['ultima_actividad']) && $ahora - $_SESSION['ultima_actividad'] > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['ultima_actividad'] = $ahora;
}

// ---------------------------------------------------------------------
// Respuestas y lectura de datos
// ---------------------------------------------------------------------
function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function exigirMetodo(string $metodo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        header('Allow: ' . $metodo);
        responder(405, ['error' => 'Método no permitido.']);
    }
}

/** Lee el cuerpo JSON de la petición (máx. 16 KB). */
function leerJson(): array
{
    $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($tipo, 'application/json') !== 0) {
        responder(415, ['error' => 'Se esperaba JSON.']);
    }
    $cuerpo = file_get_contents('php://input', false, null, 0, 16384);
    $datos = json_decode($cuerpo, true);
    if (!is_array($datos)) {
        responder(400, ['error' => 'JSON inválido.']);
    }
    return $datos;
}

/** Obtiene un texto del JSON, recortado y con longitud máxima. */
function textoDe(array $datos, string $campo, int $maximo): string
{
    $valor = $datos[$campo] ?? '';
    if (!is_string($valor)) {
        return '';
    }
    $valor = trim($valor);
    if (mb_strlen($valor) > $maximo) {
        responder(422, ['error' => "El campo $campo es demasiado largo."]);
    }
    return $valor;
}

function ipCliente(): string
{
    // REMOTE_ADDR es la única IP confiable (X-Forwarded-For se puede falsificar)
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

// ---------------------------------------------------------------------
// Token CSRF
//  Toda petición que modifica datos (POST) debe mandar la cabecera
//  X-CSRF-Token con el valor que entrega api/sesion.php.
// ---------------------------------------------------------------------
function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verificarCsrf(): void
{
    $recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf']) || !is_string($recibido) || !hash_equals($_SESSION['csrf'], $recibido)) {
        responder(403, ['error' => 'Token de seguridad inválido. Recarga la página.']);
    }
}

// ---------------------------------------------------------------------
// Usuario en sesión y permisos (cliente / admin)
// ---------------------------------------------------------------------
function usuarioActual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function exigirLogin(): array
{
    $u = usuarioActual();
    if ($u === null) {
        responder(401, ['error' => 'Debes iniciar sesión.']);
    }
    return $u;
}

function exigirRol(string $rol): array
{
    $u = exigirLogin();
    if ($u['rol'] !== $rol) {
        responder(403, ['error' => 'No tienes permiso para esto.']);
    }
    return $u;
}

/** Guarda al usuario en la sesión después de un login/registro exitoso. */
function abrirSesionDeUsuario(array $fila): void
{
    session_regenerate_id(true);   // nuevo ID: evita "fijación de sesión"
    $_SESSION['usuario'] = [
        'id'     => (int) $fila['id'],
        'nombre' => $fila['nombre'],
        'correo' => $fila['correo'],
        'rol'    => $fila['rol'],
    ];
    unset($_SESSION['csrf']);      // token nuevo para la sesión nueva
    tokenCsrf();
}

iniciarSesion();
