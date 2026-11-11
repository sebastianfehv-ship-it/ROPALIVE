<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$productos = $conexion->query(
    'SELECT p.id, p.nombre, p.imagen, p.marca, p.categoria, p.precio, p.stock, p.genero, c.nombre AS coleccion
     FROM productos p LEFT JOIN colecciones c ON c.id = p.coleccion_id ORDER BY p.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Productos | Administración RopaLive</title>
    <link rel="stylesheet" href="../../assets/css/base.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <div class="top-note">GESTIÓN DE LA TIENDA</div>
    <header class="site-header">
        <a class="brand" href="../../index.php">ROPALIVE</a>
        <nav class="site-nav">
            <a href="../index.php">Panel principal</a>
            <a href="../clientes/index.php">Clientes</a>
            <a href="../colecciones/index.php">Colecciones</a>
            <a href="../../index.php">Ver tienda</a>
        </nav>
    </header>
    <main class="page-width">
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
                                    <td><img class="admin-thumb" src="<?php echo h(escapar_imagen($producto['imagen'])); ?>" alt=""></td>
                                    <td><?php echo h($producto['nombre']); ?></td>
                                    <td><?php echo h($producto['marca']); ?><br><?php echo h($producto['categoria']); ?></td>
                                    <td><?php echo h(ucfirst($producto['genero'])); ?></td>
                                    <td><?php echo h($producto['coleccion'] ?: 'Sin colección'); ?></td>
                                    <td>$<?php echo number_format((float) $producto['precio'], 2); ?></td>
                                    <td><?php echo (int) $producto['stock']; ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a class="button button-light button-small" href="editar.php?id=<?php echo (int) $producto['id']; ?>">Editar</a>
                                            <form method="post" action="eliminar.php" onsubmit="return confirm('¿Eliminar este producto?');">
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
    </main>
    <footer class="site-footer"><p>Inventario de RopaLive</p></footer>
</body>
</html>
