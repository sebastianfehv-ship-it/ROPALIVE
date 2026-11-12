<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$productos = $conexion->query('SELECT id, nombre, coleccion_id FROM productos ORDER BY nombre')->fetchAll();
$errores = [];
$nombre = '';
$descripcion = '';
$seleccionados = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(texto_post('nombre'));
    $descripcion = trim(texto_post('descripcion'));
    $seleccionados = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['productos'] ?? [])), function ($id) {
        return $id > 0;
    })));
    if (!csrf_valido()) {
        $errores[] = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.';
    }
    if ($nombre === '' || strlen($nombre) > 150) {
        $errores[] = 'El nombre es obligatorio y debe tener máximo 150 caracteres.';
    }
    if (strlen($descripcion) > 5000) {
        $errores[] = 'La descripción no puede superar 5000 caracteres.';
    }
    if (!$seleccionados) {
        $errores[] = 'Selecciona al menos un producto para crear la colección.';
    }
    if (count($seleccionados) !== count(array_unique(array_column(array_filter($productos, function ($producto) use ($seleccionados) {
        return in_array((int) $producto['id'], $seleccionados, true);
    }), 'id')))) {
        $errores[] = 'Uno o más productos seleccionados no existen.';
    }
    $hayImagen = isset($_FILES['imagenes']['error']) && count(array_filter($_FILES['imagenes']['error'], function ($error) {
        return $error !== UPLOAD_ERR_NO_FILE;
    })) > 0;
    if (!$hayImagen) {
        $errores[] = 'Sube al menos una imagen para la colección.';
    }

    if (!$errores) {
        try {
            $imagenes = guardar_imagenes_subidas('imagenes', 'collections', 3);
            if (!$imagenes) {
                throw new RuntimeException('Sube al menos una imagen para la colección.');
            }
            $conexion->beginTransaction();
            $stmt = $conexion->prepare('INSERT INTO colecciones (nombre, descripcion, imagen) VALUES (:nombre, :descripcion, :imagen)');
            $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion, 'imagen' => $imagenes[0]]);
            $coleccionId = (int) $conexion->lastInsertId();
            $imagenStmt = $conexion->prepare('INSERT INTO coleccion_imagenes (coleccion_id, imagen, orden) VALUES (:coleccion_id, :imagen, :orden)');
            foreach ($imagenes as $orden => $imagen) {
                $imagenStmt->execute(['coleccion_id' => $coleccionId, 'imagen' => $imagen, 'orden' => $orden]);
            }
            $productoStmt = $conexion->prepare('UPDATE productos SET coleccion_id = :coleccion_id WHERE id = :id');
            foreach ($seleccionados as $productoId) {
                $productoStmt->execute(['coleccion_id' => $coleccionId, 'id' => $productoId]);
            }
            $conexion->commit();
            header('Location: index.php');
            exit();
        } catch (RuntimeException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $errores[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log($e->getMessage());
            $errores[] = 'No se pudo guardar la colección. Inténtalo nuevamente.';
        }
    }
}
$adminBasePath = '../../';
$adminSection = 'colecciones';
$adminTitle = 'Crear colección';
$adminExtraCss = true;
require __DIR__ . '/../layout_inicio.php';
?>
    <section class="panel admin-form-panel">
        <?php if ($errores) { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h(implode(' ', $errores)); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
        <?php if (!$productos) { ?><div class="notice">Primero crea al menos un producto para poder crear una colección.</div><?php } ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
            <div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" value="<?php echo h($nombre); ?>" required></div>
            <div class="form-group"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" maxlength="5000"><?php echo h($descripcion); ?></textarea></div>
            <div class="form-group"><label for="imagenes">Imágenes (hasta 3)</label><input id="imagenes" type="file" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple required><small>JPG, PNG o WEBP, máximo 5 MB cada imagen.</small></div>
            <fieldset class="product-picker"><legend>Productos de la colección</legend>
                <?php foreach ($productos as $producto) { ?>
                    <label><input type="checkbox" name="productos[]" value="<?php echo (int) $producto['id']; ?>" <?php echo in_array((int) $producto['id'], $seleccionados, true) ? 'checked' : ''; ?>> <?php echo h($producto['nombre']); ?><?php echo $producto['coleccion_id'] ? ' · se moverá de su colección actual' : ''; ?></label>
                <?php } ?>
            </fieldset>
            <div class="modal-actions"><a class="button button-light" href="index.php">Cancelar</a><button type="submit" <?php echo !$productos ? 'disabled' : ''; ?>>Guardar colección</button></div>
        </form>
    </section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
