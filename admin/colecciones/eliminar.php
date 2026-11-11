<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) { http_response_code(400); exit('La solicitud no es válida. Actualiza la página e inténtalo otra vez.'); }
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) { http_response_code(400); exit('La colección seleccionada no es válida.'); }
$stmt = $conexion->prepare('DELETE FROM colecciones WHERE id = :id');
$stmt->execute(['id' => $id]);
header('Location: index.php');
exit();
