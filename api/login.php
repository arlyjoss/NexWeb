<?php
/**
 * POST api/login.php
 * Cuerpo: { "correo": "...", "contrasena": "..." }
 */
require __DIR__ . '/nucleo/seguridad.php';
exigirMetodo('POST');
verificarCsrf();

const MAX_FALLOS_CORREO = 5;    // por correo
const MAX_FALLOS_IP     = 20;   // por IP
const VENTANA_MINUTOS   = 15;

$datos      = leerJson();
$correo     = mb_strtolower(textoDe($datos, 'correo', 150));
$contrasena = is_string($datos['contrasena'] ?? null) ? $datos['contrasena'] : '';
$ip         = ipCliente();

if ($correo === '' || $contrasena === '') {
    responder(422, ['error' => 'Escribe tu correo y contraseña.']);
}

$pdo = obtenerConexion();

// ---- 1. Protección contra fuerza bruta ----
$stmt = $pdo->prepare(
    'SELECT
        SUM(correo = :correo) AS fallos_correo,
        SUM(ip = :ip)         AS fallos_ip
     FROM intentos_login
     WHERE exito = 0
       AND fecha > (NOW() - INTERVAL ' . VENTANA_MINUTOS . ' MINUTE)
       AND (correo = :correo2 OR ip = :ip2)'
);
$stmt->execute([':correo' => $correo, ':ip' => $ip, ':correo2' => $correo, ':ip2' => $ip]);
$fallos = $stmt->fetch();

if ((int) $fallos['fallos_correo'] >= MAX_FALLOS_CORREO || (int) $fallos['fallos_ip'] >= MAX_FALLOS_IP) {
    responder(429, ['error' => 'Demasiados intentos fallidos. Espera ' . VENTANA_MINUTOS . ' minutos.']);
}

// ---- 2. Buscar usuario y comparar contraseña ----
$stmt = $pdo->prepare(
    'SELECT id, nombre, correo, contrasena_hash, rol, activo FROM usuarios WHERE correo = :correo LIMIT 1'
);
$stmt->execute([':correo' => $correo]);
$usuario = $stmt->fetch();

// Si el correo no existe se verifica contra un hash falso para que la
// respuesta tarde lo mismo (así no se puede adivinar qué correos existen).
$hashFalso = '$2y$10$X9v9O0hRo1rKwEZqCjDK2udmkGKlFQ9JjCMkNEA0sijrfS/KJ1NX2';
$ok = password_verify($contrasena, $usuario['contrasena_hash'] ?? $hashFalso)
      && $usuario
      && (int) $usuario['activo'] === 1;

$registrar = $pdo->prepare('INSERT INTO intentos_login (correo, ip, exito) VALUES (:correo, :ip, :exito)');
$registrar->execute([':correo' => $correo, ':ip' => $ip, ':exito' => $ok ? 1 : 0]);

if (!$ok) {
    // Mismo mensaje si el correo no existe o si la contraseña está mal
    responder(401, ['error' => 'Correo o contraseña incorrectos.']);
}

// ---- 3. Éxito: limpiar fallos, actualizar último acceso, abrir sesión ----
$pdo->prepare('DELETE FROM intentos_login WHERE correo = :correo AND exito = 0')
    ->execute([':correo' => $correo]);
$pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id')
    ->execute([':id' => $usuario['id']]);

abrirSesionDeUsuario($usuario);

responder(200, [
    'mensaje' => 'Bienvenido, ' . $usuario['nombre'],
    'usuario' => usuarioActual(),
    'csrf'    => tokenCsrf(),
]);
