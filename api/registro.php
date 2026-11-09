<?php
/**
 * POST api/registro.php
 * Cuerpo: { "nombre": "...", "correo": "...", "contrasena": "...", "confirmar": "..." }
 */
require __DIR__ . '/nucleo/seguridad.php';
exigirMetodo('POST');
verificarCsrf();

$datos      = leerJson();
$nombre     = textoDe($datos, 'nombre', 100);
$correo     = mb_strtolower(textoDe($datos, 'correo', 150));
$contrasena = is_string($datos['contrasena'] ?? null) ? $datos['contrasena'] : '';
$confirmar  = is_string($datos['confirmar'] ?? null) ? $datos['confirmar'] : '';

// ---- Validaciones del lado del servidor (el JS del navegador se puede saltar) ----
$errores = [];
if (mb_strlen($nombre) < 2) {
    $errores[] = 'Escribe tu nombre.';
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo no es válido.';
}
if (strlen($contrasena) < 8 || strlen($contrasena) > 72) {
    // 72 bytes es el máximo que procesa bcrypt
    $errores[] = 'La contraseña debe tener entre 8 y 72 caracteres.';
} elseif (!preg_match('/[A-Za-z]/', $contrasena) || !preg_match('/\d/', $contrasena)) {
    $errores[] = 'La contraseña debe incluir al menos una letra y un número.';
}
if ($contrasena !== $confirmar) {
    $errores[] = 'Las contraseñas no coinciden.';
}
if ($errores) {
    responder(422, ['error' => implode(' ', $errores)]);
}

// ---- Guardar ----
$pdo  = obtenerConexion();
$hash = password_hash($contrasena, PASSWORD_DEFAULT);   // bcrypt con "sal" aleatoria

try {
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES (:nombre, :correo, :hash)'
    );
    $stmt->execute([':nombre' => $nombre, ':correo' => $correo, ':hash' => $hash]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {   // violación de UNIQUE: correo repetido
        responder(409, ['error' => 'Ese correo ya está registrado. Inicia sesión.']);
    }
    throw $e;
}

// El rol siempre es 'cliente' (valor por defecto de la tabla): nadie puede
// registrarse como admin aunque mande "rol":"admin" en el JSON.
abrirSesionDeUsuario([
    'id'     => $pdo->lastInsertId(),
    'nombre' => $nombre,
    'correo' => $correo,
    'rol'    => 'cliente',
]);

responder(201, [
    'mensaje' => '¡Cuenta creada correctamente!',
    'usuario' => usuarioActual(),
    'csrf'    => tokenCsrf(),
]);
