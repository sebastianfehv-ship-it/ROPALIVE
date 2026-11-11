<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    http_response_code(400);
    exit('La solicitud no es válida. Vuelve a la lista de clientes e inténtalo otra vez.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    http_response_code(400);
    exit('El cliente seleccionado no es válido.');
}

$stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'cliente'");
$stmt->execute(['id' => $id]);
header('Location: index.php');
exit();
