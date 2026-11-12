<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$productos = $conexion->query(
    'SELECT p.id, p.nombre, p.imagen, p.marca, p.categoria, p.precio, p.stock, p.genero, c.nombre AS coleccion
     FROM productos p LEFT JOIN colecciones c ON c.id = p.coleccion_id ORDER BY p.id DESC'
)->fetchAll();
$adminBasePath = '../../';
$adminSection = 'productos';
$adminTitle = 'Productos';
require __DIR__ . '/../layout_inicio.php';
?>
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Inventario</p>
                    <h1 class="page-title">Productos</h1>
                </div>
                <a class="button" href="agregar.php">Agregar producto</a>
            </div>
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr><th>Imagen</th><th>Nombre</th><th>Marca / categoría</th><th>Género</th><th>Colección</th><th>Precio</th><th>Stock</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($productos) { ?>
                            <?php foreach ($productos as $producto) { ?>
                                <tr>
                                    <td><img class="admin-thumb" src="<?php echo h('../../' . escapar_imagen($producto['imagen'])); ?>?v=<?php echo (int) $producto['id']; ?>" alt=""></td>
                                    <td><?php echo h($producto['nombre']); ?></td>
                                    <td><?php echo h($producto['marca']); ?><br><?php echo h($producto['categoria']); ?></td>
                                    <td><?php echo h(ucfirst($producto['genero'])); ?></td>
                                    <td><?php echo h($producto['coleccion'] ?: 'Sin colección'); ?></td>
                                    <td>$<?php echo number_format((float) $producto['precio'], 2); ?></td>
                                    <td><?php echo (int) $producto['stock']; ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a class="button button-light button-small" href="editar.php?id=<?php echo (int) $producto['id']; ?>">Editar</a>
                                            <form method="post" action="eliminar.php" data-confirm-message="¿Quieres eliminar este producto?" data-confirm-title="Eliminar producto" data-confirm-danger="true">
                                                <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                                                <input type="hidden" name="id" value="<?php echo (int) $producto['id']; ?>">
                                                <button class="button-danger button-small" type="submit">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="8">Todavía no hay productos en el inventario.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
