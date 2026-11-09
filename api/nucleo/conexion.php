<?php
/**
 * Conexión única a MySQL usando PDO.
 *
 * ¿Por qué PDO y no mysqli con real_escape_string?
 *  - Con consultas preparadas los datos del usuario viajan SEPARADOS del SQL,
 *    así que la inyección SQL es imposible aunque escriban  ' OR 1=1 --
 *  - ATTR_EMULATE_PREPARES = false obliga a que MySQL haga la preparación real.
 */

function configuracion(): array
{
    static $config = null;
    if ($config === null) {
        $ruta = __DIR__ . '/../config.php';
        if (!is_file($ruta)) {
            responder(500, ['error' => 'Falta api/config.php. Copia api/config.example.php como config.php y pon tus datos.']);
        }
        $config = require $ruta;
    }
    return $config;
}

function obtenerConexion(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $c = configuracion();
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $c['db_host'],
        (int) $c['db_port'],
        $c['db_name']
    );

    try {
        $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores como excepciones
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // filas como arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES   => false,                  // preparación real en el servidor
        ]);
    } catch (PDOException $e) {
        error_log('NexWeb - error de conexión: ' . $e->getMessage());
        $mensaje = 'No se pudo conectar con la base de datos.';
        if (!empty($c['modo_desarrollo'])) {
            $mensaje .= ' Detalle: ' . $e->getMessage();
        }
        responder(503, ['error' => $mensaje]);
    }

    return $pdo;
}
