<?php

$conexion = new mysqli("localhost", "root", "", "tienda_simple", 3307);


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mensaje'])) {
    $msg = $conexion->real_escape_string($_POST['mensaje']);
    $conexion->query("INSERT INTO chat (mensaje) VALUES ('$msg')");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Tienda en Línea</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .contenedor { max-width: 600px; background: white; padding: 20px; border-radius: 8px; }
        .chat-box { border: 1px solid #ccc; height: 150px; overflow-y: scroll; padding: 10px; margin-bottom: 10px; }
        input[type="text"] { width: 75%; padding: 8px; }
        button { padding: 8px 12px; background: #28a745; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>

<div class="contenedor">
    <h1> Mi Tienda en Línea</h1>
    <p>¡Bienvenida a la tienda!</p>
    <hr>

    <h3> Chat de Soporte</h3>
    <div class="chat-box">
        <?php
        if (!$conexion->connect_error) {
            $resultado = $conexion->query("SELECT * FROM chat ORDER BY fecha DESC");
            while ($row = $resultado->fetch_assoc()) {
                echo "<p><strong>Usuario:</strong> " . htmlspecialchars($row['mensaje']) . "</p>";
            }
        } else {
            echo "<p style='color:red;'>Crea la base de datos para ver los mensajes.</p>";
        }
        ?>
    </div>

    <form method="POST">
        <input type="text" name="mensaje" placeholder="Escribe un mensaje..." required>
        <button type="submit">Enviar</button>
    </form>
</div>

</body>
</html>