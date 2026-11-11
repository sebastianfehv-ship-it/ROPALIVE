<?php
require_once __DIR__ . '/includes/Funciones.php';
requiere_cliente();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    http_response_code(400);
    exit('La solicitud no es válida. Vuelve a tu cuenta e inténtalo otra vez.');
}

$stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'cliente'");
$stmt->execute(['id' => $_SESSION['usuario_id']]);

$_SESSION = [];
session_destroy();
header('Location: index.php');
exit();