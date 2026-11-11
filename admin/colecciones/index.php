<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$colecciones = $conexion->query(
    'SELECT c.id, c.nombre, c.imagen, COUNT(p.id) AS productos
     FROM colecciones c LEFT JOIN productos p ON p.coleccion_id = c.id
     GROUP BY c.id ORDER BY c.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Colecciones | RopaLive</title>
    <link rel="stylesheet" href="../../assets/css/base.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <header class="site-header"><a class="brand" href="../../index.php">ROPALIVE</a><nav class="site-nav"><a href="../index.php">Panel</a><a href="../../colecciones.php">Ver tienda</a></nav></header>
    <main class="page-width">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">Catálogo</p><h1 class="page-title">Colecciones</h1></div><a class="button" href="agregar.php">Crear colección</a></div>
            <p class="muted-copy">Cada colección debe incluir al menos un producto. Los productos también pueden publicarse sin colección.</p>
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Imagen</th><th>Nombre</th><th>Productos</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php if ($colecciones) { foreach ($colecciones as $coleccion) { ?>
                    <tr>
                        <td><img class="admin-thumb" src="<?php echo h(escapar_imagen($coleccion['imagen'])); ?>" alt=""></td>
                        <td><?php echo h($coleccion['nombre']); ?></td>
                        <td><?php echo (int) $coleccion['productos']; ?></td>
                        <td><div class="admin-actions">
                            <a class="button button-light button-small" href="editar.php?id=<?php echo (int) $coleccion['id']; ?>">Editar</a>
                            <form method="post" action="eliminar.php" onsubmit="return confirm('¿Eliminar esta colección? Los productos quedarán sin colección.');">
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
    </main>
</body>
</html>
