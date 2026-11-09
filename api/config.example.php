<?php
/**
 * PLANTILLA DE CONFIGURACIÓN
 *
 * 1. Copia este archivo y renómbralo a  config.php  (en esta misma carpeta).
 * 2. Pon tus datos reales en config.php.
 * 3. config.php está en .gitignore: tus contraseñas NO se suben a GitHub.
 *    Cada integrante del equipo tiene su propio config.php.
 */
return [
    'db_host' => 'localhost',
    // XAMPP usa 3306. Si en tu XAMPP MySQL arranca en otro puerto (p. ej. 3307,
    // como en "Chat de Tienda/index.php"), cámbialo aquí.
    'db_port' => 3306,
    'db_name' => 'nexweb',
    'db_user' => 'nexweb_app',                  // NO uses root
    'db_pass' => 'CambiaEsta_Contrasena_2026',  // la misma que pusiste en database.sql

    // true  = muestra el detalle de los errores de conexión (útil en tu compu)
    // false = mensajes genéricos (úsalo si el sitio se publica en internet)
    'modo_desarrollo' => true,
];
