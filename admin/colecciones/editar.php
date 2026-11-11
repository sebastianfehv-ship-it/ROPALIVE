<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) { http_response_code(404); exit('No se encontró la colección solicitada.'); }
$stmt = $conexion->prepare('SELECT id, nombre, descripcion, imagen FROM colecciones WHERE id = :id');
$stmt->execute(['id' => $id]);
$coleccion = $stmt->fetch();
if (!$coleccion) { http_response_code(404); exit('No se encontró la colección solicitada.'); }
$productos = $conexion->query('SELECT id, nombre, coleccion_id FROM productos ORDER BY nombre')->fetchAll();
$stmt = $conexion->prepare('SELECT id FROM productos WHERE coleccion_id = :id');
$stmt->execute(['id' => $id]);
$seleccionados = array_map('intval', array_column($stmt->fetchAll(), 'id'));
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(texto_post('nombre'));
    $descripcion = trim(texto_post('descripcion'));
    $seleccionados = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['productos'] ?? [])), function ($productoId) { return $productoId > 0; })));
    if (!csrf_valido()) { $errores[] = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.'; }
    if ($nombre === '' || strlen($nombre) > 150 || strlen($descripcion) > 5000) { $errores[] = 'Revisa el nombre y la descripción de la colección.'; }
    if (!$seleccionados) { $errores[] = 'La colección debe tener al menos un producto.'; }
    $idsDisponibles = array_map('intval', array_column($productos, 'id'));
    if (array_diff($seleccionados, $idsDisponibles)) { $errores[] = 'Uno o más productos seleccionados no existen.'; }
    if (!$errores) {
        try {
            $imagenes = guardar_imagenes_subidas('imagenes', 'collections', 3);
            $conexion->beginTransaction();
            $imagen = $imagenes[0] ?? $coleccion['imagen'];
            $stmt = $conexion->prepare('UPDATE colecciones SET nombre = :nombre, descripcion = :descripcion, imagen = :imagen WHERE id = :id');
            $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion, 'imagen' => $imagen, 'id' => $id]);
            if ($imagenes) {
                $stmt = $conexion->prepare('DELETE FROM coleccion_imagenes WHERE coleccion_id = :id');
                $stmt->execute(['id' => $id]);
                $stmt = $conexion->prepare('INSERT INTO coleccion_imagenes (coleccion_id, imagen, orden) VALUES (:id, :imagen, :orden)');
                foreach ($imagenes as $orden => $ruta) { $stmt->execute(['id' => $id, 'imagen' => $ruta, 'orden' => $orden]); }
            }
            $stmt = $conexion->prepare('UPDATE productos SET coleccion_id = NULL WHERE coleccion_id = :id');
            $stmt->execute(['id' => $id]);
            $stmt = $conexion->prepare('UPDATE productos SET coleccion_id = :coleccion_id WHERE id = :id');
            foreach ($seleccionados as $productoId) { $stmt->execute(['coleccion_id' => $id, 'id' => $productoId]); }
            $conexion->commit();
            header('Location: index.php'); exit();
        } catch (RuntimeException $e) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            $errores[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            error_log($e->getMessage());
            $errores[] = 'No se pudo actualizar la colección. Inténtalo nuevamente.';
        }
    }
    $coleccion['nombre'] = $nombre;
    $coleccion['descripcion'] = $descripcion;
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Editar colección | RopaLive</title><link rel="stylesheet" href="../../assets/css/base.css"><link rel="stylesheet" href="../../assets/css/formularios.css"><link rel="stylesheet" href="../../assets/css/admin.css"></head>
<body class="modal-page"><main class="modal-card"><a class="brand" href="index.php">ROPALIVE</a><p class="eyebrow">Catálogo</p><h1 class="page-title">Editar colección</h1>
<?php if ($errores) { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h(implode(' ', $errores)); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
<div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" value="<?php echo h($coleccion['nombre']); ?>" required></div>
<div class="form-group"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" maxlength="5000"><?php echo h($coleccion['descripcion'] ?? ''); ?></textarea></div>
<div class="form-group"><label for="imagenes">Reemplazar imágenes (opcional, hasta 3)</label><input id="imagenes" type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple></div>
<fieldset class="product-picker"><legend>Productos de la colección</legend><?php foreach ($productos as $producto) { ?><label><input type="checkbox" name="productos[]" value="<?php echo (int) $producto['id']; ?>" <?php echo in_array((int) $producto['id'], $seleccionados, true) ? 'checked' : ''; ?>> <?php echo h($producto['nombre']); ?></label><?php } ?></fieldset>
<div class="modal-actions"><a class="button button-light" href="index.php">Cancelar</a><button type="submit">Guardar cambios</button></div></form></main></body></html>
