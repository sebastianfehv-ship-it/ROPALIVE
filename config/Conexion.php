<?php
$host = getenv('DB_HOST') ?: 'localhost';
$base_de_datos = getenv('DB_NAME') ?: 'ropa_live';
$usuario = getenv('DB_USER') ?: 'root';
$contrasena = getenv('DB_PASSWORD') ?: '';

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$base_de_datos;charset=utf8mb4",
        $usuario,
        $contrasena,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa la configuración del proyecto.');
}
