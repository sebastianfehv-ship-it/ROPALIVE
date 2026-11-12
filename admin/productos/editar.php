<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) { http_response_code(404); exit('No se encontró el producto solicitado.'); }
$stmt = $conexion->prepare('SELECT id, coleccion_id, nombre, descripcion, marca, categoria, precio, stock, genero, imagen FROM productos WHERE id = :id');
$stmt->execute(['id' => $id]);
$producto = $stmt->fetch();
if (!$producto) { http_response_code(404); exit('No se encontró el producto solicitado.'); }
$colecciones = $conexion->query('SELECT id, nombre FROM colecciones ORDER BY nombre')->fetchAll();
$stmt = $conexion->prepare('SELECT imagen FROM producto_imagenes WHERE producto_id = :id ORDER BY orden, id');
$stmt->execute(['id' => $id]);
$imagenesActuales = array_column($stmt->fetchAll(), 'imagen');
if (!$imagenesActuales && $producto['imagen']) { $imagenesActuales = [$producto['imagen']]; }
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => texto_post('nombre'),
        'descripcion' => texto_post('descripcion'),
        'marca' => texto_post('marca'),
        'categoria' => texto_post('categoria'),
        'precio' => texto_post('precio'),
        'stock' => texto_post('stock'),
        'genero' => texto_post('genero'),
        'coleccion_id' => texto_post('coleccion_id'),
    ];
    if (!csrf_valido()) { $errores[] = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.'; }
    else { $errores = validar_producto($datos); }
    $coleccionId = $datos['coleccion_id'] === '' ? null : filter_var($datos['coleccion_id'], FILTER_VALIDATE_INT);
    if ($datos['coleccion_id'] !== '' && ($coleccionId === false || $coleccionId < 1)) {
        $errores[] = 'Selecciona una colección válida.';
    } elseif ($coleccionId !== null) {
        $stmt = $conexion->prepare('SELECT id FROM colecciones WHERE id = :id');
        $stmt->execute(['id' => $coleccionId]);
        if (!$stmt->fetch()) { $errores[] = 'La colección seleccionada no existe.'; }
    }
    if (!$errores) {
        try {
            $imagenesNuevas = guardar_imagenes_subidas('imagenes', 'products', 3);
            $imagenesGuardar = $imagenesNuevas ?: $imagenesActuales;
            if (!$imagenesGuardar) { throw new RuntimeException('El producto debe tener al menos una imagen.'); }
            $conexion->beginTransaction();
            $stmt = $conexion->prepare(
                'UPDATE productos SET coleccion_id = :coleccion_id, nombre = :nombre, descripcion = :descripcion, marca = :marca, categoria = :categoria,
                    precio = :precio, stock = :stock, genero = :genero, imagen = :imagen WHERE id = :id'
            );
            $stmt->execute([
                'coleccion_id' => $coleccionId,
                'nombre' => trim($datos['nombre']),
                'descripcion' => trim($datos['descripcion']),
                'marca' => trim($datos['marca']),
                'categoria' => trim($datos['categoria']),
                'precio' => $datos['precio'],
                'stock' => $datos['stock'],
                'genero' => $datos['genero'],
                'imagen' => $imagenesGuardar[0],
                'id' => $id,
            ]);
            if ($imagenesNuevas) {
                $stmt = $conexion->prepare('DELETE FROM producto_imagenes WHERE producto_id = :id');
                $stmt->execute(['id' => $id]);
                $stmt = $conexion->prepare('INSERT INTO producto_imagenes (producto_id, imagen, orden) VALUES (:producto_id, :imagen, :orden)');
                foreach ($imagenesNuevas as $orden => $imagen) {
                    $stmt->execute(['producto_id' => $id, 'imagen' => $imagen, 'orden' => $orden]);
                }
            }
            $conexion->commit();
            header('Location: index.php'); exit();
        } catch (RuntimeException $e) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            $errores[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            error_log($e->getMessage());
            $errores[] = 'No se pudo actualizar el producto. Inténtalo nuevamente.';
        }
    }
    $producto = array_merge($producto, $datos);
}
$adminBasePath = '../../';
$adminSection = 'productos';
$adminTitle = 'Editar producto';
$adminExtraCss = true;
require __DIR__ . '/../layout_inicio.php';
?>
<section class="panel admin-form-panel">
<?php if ($errores) { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h(implode(' ', $errores)); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
<div class="current-images"><?php foreach ($imagenesActuales as $imagen) { ?><img src="<?php echo h('../../' . escapar_imagen($imagen)); ?>" alt="Imagen actual de <?php echo h($producto['nombre']); ?>"><?php } ?></div>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
<div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" value="<?php echo h($producto['nombre']); ?>" required></div>
<div class="form-group"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" maxlength="5000"><?php echo h($producto['descripcion'] ?? ''); ?></textarea></div>
<div class="modal-grid"><div class="form-group"><label for="marca">Marca</label><input id="marca" name="marca" maxlength="100" value="<?php echo h($producto['marca']); ?>" required></div><div class="form-group"><label for="categoria">Categoría</label><input id="categoria" name="categoria" maxlength="100" value="<?php echo h($producto['categoria']); ?>" required></div></div>
<div class="modal-grid"><div class="form-group"><label for="precio">Precio</label><input id="precio" type="number" name="precio" min="0" max="99999999" step="0.01" value="<?php echo h($producto['precio']); ?>" required></div><div class="form-group"><label for="stock">Unidades disponibles</label><input id="stock" type="number" name="stock" min="0" step="1" value="<?php echo h($producto['stock']); ?>" required></div></div>
<div class="modal-grid"><div class="form-group"><label for="genero">Género</label><select id="genero" name="genero"><option value="unisex" <?php echo $producto['genero'] === 'unisex' ? 'selected' : ''; ?>>Unisex</option><option value="mujer" <?php echo $producto['genero'] === 'mujer' ? 'selected' : ''; ?>>Mujer</option><option value="hombre" <?php echo $producto['genero'] === 'hombre' ? 'selected' : ''; ?>>Hombre</option></select></div><div class="form-group"><label for="coleccion_id">Colección</label><select id="coleccion_id" name="coleccion_id"><option value="">Sin colección</option><?php foreach ($colecciones as $coleccion) { ?><option value="<?php echo (int) $coleccion['id']; ?>" <?php echo (string) $producto['coleccion_id'] === (string) $coleccion['id'] ? 'selected' : ''; ?>><?php echo h($coleccion['nombre']); ?></option><?php } ?></select></div></div>
<div class="form-group"><label for="imagenes">Reemplazar imágenes (opcional, hasta 3)</label><input id="imagenes" type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple></div>
<div class="modal-actions"><a class="button button-light" href="index.php">Cancelar</a><button type="submit">Guardar cambios</button></div></form>
</section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
