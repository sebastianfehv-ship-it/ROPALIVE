<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$colecciones = $conexion->query('SELECT id, nombre FROM colecciones ORDER BY nombre')->fetchAll();
$errores = [];
$datos = ['nombre' => '', 'descripcion' => '', 'marca' => '', 'categoria' => '', 'precio' => '', 'stock' => '', 'genero' => 'unisex', 'coleccion_id' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = texto_post($campo);
    }
    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.';
    } else {
        $errores = validar_producto($_POST);
    }
    $coleccionId = $datos['coleccion_id'] === '' ? null : filter_var($datos['coleccion_id'], FILTER_VALIDATE_INT);
    if ($datos['coleccion_id'] !== '' && ($coleccionId === false || $coleccionId < 1)) {
        $errores[] = 'Selecciona una colección válida.';
    } elseif ($coleccionId !== null) {
        $stmt = $conexion->prepare('SELECT id FROM colecciones WHERE id = :id');
        $stmt->execute(['id' => $coleccionId]);
        if (!$stmt->fetch()) {
            $errores[] = 'La colección seleccionada no existe.';
        }
    }
    $hayImagen = isset($_FILES['imagenes']['error']) && count(array_filter($_FILES['imagenes']['error'], function ($error) {
        return $error !== UPLOAD_ERR_NO_FILE;
    })) > 0;
    if (!$hayImagen) {
        $errores[] = 'Sube al menos una imagen del producto.';
    }

    if (!$errores) {
        try {
            $imagenes = guardar_imagenes_subidas('imagenes', 'products', 3);
            if (!$imagenes) {
                throw new RuntimeException('Sube al menos una imagen del producto.');
            }
            $conexion->beginTransaction();
            $stmt = $conexion->prepare(
                'INSERT INTO productos (coleccion_id, nombre, descripcion, marca, categoria, precio, stock, genero, imagen)
                 VALUES (:coleccion_id, :nombre, :descripcion, :marca, :categoria, :precio, :stock, :genero, :imagen)'
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
                'imagen' => $imagenes[0],
            ]);
            $productoId = (int) $conexion->lastInsertId();
            $stmt = $conexion->prepare('INSERT INTO producto_imagenes (producto_id, imagen, orden) VALUES (:producto_id, :imagen, :orden)');
            foreach ($imagenes as $orden => $imagen) {
                $stmt->execute(['producto_id' => $productoId, 'imagen' => $imagen, 'orden' => $orden]);
            }
            $conexion->commit();
            header('Location: index.php');
            exit();
        } catch (RuntimeException $e) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            $errores[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) { $conexion->rollBack(); }
            error_log($e->getMessage());
            $errores[] = 'No se pudo guardar el producto. Inténtalo nuevamente.';
        }
    }
}
$adminBasePath = '../../';
$adminSection = 'productos';
$adminTitle = 'Agregar producto';
$adminExtraCss = true;
require __DIR__ . '/../layout_inicio.php';
?>
<section class="panel admin-form-panel">
    <?php if ($errores) { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h(implode(' ', $errores)); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
        <div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" value="<?php echo h($datos['nombre']); ?>" required></div>
        <div class="form-group"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" maxlength="5000"><?php echo h($datos['descripcion']); ?></textarea></div>
        <div class="modal-grid">
            <div class="form-group"><label for="marca">Marca</label><input id="marca" name="marca" maxlength="100" value="<?php echo h($datos['marca']); ?>" required></div>
            <div class="form-group"><label for="categoria">Categoría</label><input id="categoria" name="categoria" maxlength="100" placeholder="Camisas, pantalones..." value="<?php echo h($datos['categoria']); ?>" required></div>
        </div>
        <div class="modal-grid">
            <div class="form-group"><label for="precio">Precio</label><input id="precio" type="number" name="precio" min="0" max="99999999" step="0.01" value="<?php echo h($datos['precio']); ?>" required></div>
            <div class="form-group"><label for="stock">Unidades disponibles</label><input id="stock" type="number" name="stock" min="0" step="1" value="<?php echo h($datos['stock']); ?>" required></div>
        </div>
        <div class="modal-grid">
            <div class="form-group"><label for="genero">Género</label><select id="genero" name="genero"><option value="unisex" <?php echo $datos['genero'] === 'unisex' ? 'selected' : ''; ?>>Unisex</option><option value="mujer" <?php echo $datos['genero'] === 'mujer' ? 'selected' : ''; ?>>Mujer</option><option value="hombre" <?php echo $datos['genero'] === 'hombre' ? 'selected' : ''; ?>>Hombre</option></select></div>
            <div class="form-group"><label for="coleccion_id">Colección (opcional)</label><select id="coleccion_id" name="coleccion_id"><option value="">Sin colección (página principal)</option><?php foreach ($colecciones as $coleccion) { ?><option value="<?php echo (int) $coleccion['id']; ?>" <?php echo $datos['coleccion_id'] === (string) $coleccion['id'] ? 'selected' : ''; ?>><?php echo h($coleccion['nombre']); ?></option><?php } ?></select></div>
        </div>
        <div class="form-group"><label for="imagenes">Imágenes del producto (hasta 3)</label><input id="imagenes" type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple required><small>JPG, PNG o WEBP, máximo 5 MB cada imagen.</small></div>
        <div class="modal-actions"><a class="button button-light" href="index.php">Cancelar</a><button type="submit">Guardar producto</button></div>
    </form>
</section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
