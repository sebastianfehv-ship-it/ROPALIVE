<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$colecciones = $conexion->query(
    'SELECT c.id, c.nombre, c.imagen, COUNT(p.id) AS productos
     FROM colecciones c LEFT JOIN productos p ON p.coleccion_id = c.id
     GROUP BY c.id ORDER BY c.id DESC'
)->fetchAll();
$adminBasePath = '../../';
$adminSection = 'colecciones';
$adminTitle = 'Colecciones';
require __DIR__ . '/../layout_inicio.php';
?>
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">Catálogo</p><h1 class="page-title">Colecciones</h1></div><a class="button" href="agregar.php">Crear colección</a></div>
            <p class="muted-copy">Cada colección debe incluir al menos un producto. Los productos también pueden publicarse sin colección.</p>
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Imagen</th><th>Nombre</th><th>Productos</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php if ($colecciones) { foreach ($colecciones as $coleccion) { ?>
                    <tr>
                        <td><img class="admin-thumb" src="<?php echo h('../../' . escapar_imagen($coleccion['imagen'])); ?>?v=<?php echo (int) $coleccion['id']; ?>" alt=""></td>
                        <td><?php echo h($coleccion['nombre']); ?></td>
                        <td><?php echo (int) $coleccion['productos']; ?></td>
                        <td><div class="admin-actions">
                            <a class="button button-light button-small" href="editar.php?id=<?php echo (int) $coleccion['id']; ?>">Editar</a>
                            <form method="post" action="eliminar.php" data-confirm-message="¿Quieres eliminar esta colección? Los productos quedarán sin colección." data-confirm-title="Eliminar colección" data-confirm-danger="true">
                                <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                                <input type="hidden" name="id" value="<?php echo (int) $coleccion['id']; ?>">
                                <button class="button-danger button-small" type="submit">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                <?php } } else { ?>
                    <tr><td colspan="4">No hay colecciones. Crea productos primero y luego agrúpalos.</td></tr>
                <?php } ?>
                </tbody>
            </table></div>
        </section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
