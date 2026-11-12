<?php
require_once __DIR__ . '/includes/Funciones.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    http_response_code(404);
    exit('No se encontró el producto solicitado.');
}

$stmt = $conexion->prepare(
    'SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, p.imagen, p.genero, c.nombre AS coleccion
     FROM productos p LEFT JOIN colecciones c ON c.id = p.coleccion_id WHERE p.id = :id'
);
$stmt->execute(['id' => $id]);
$producto = $stmt->fetch();

if (!$producto) {
    http_response_code(404);
    exit('No se encontró el producto solicitado.');
}
$stmt = $conexion->prepare('SELECT imagen FROM producto_imagenes WHERE producto_id = :id ORDER BY orden, id');
$stmt->execute(['id' => $id]);
$imagenes = array_column($stmt->fetchAll(), 'imagen');
if (!$imagenes) { $imagenes = [$producto['imagen']]; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($producto['nombre']); ?> | RopaLive</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/tienda.css">
</head>
<body>
    <div class="top-note">ENVÍOS A TODO EL PAÍS · NUEVA COLECCIÓN DISPONIBLE</div>
    <header class="site-header">
        <a class="brand" href="index.php"><img class="brand-logo" src="assets/images/LOGO.png" width="120" height="50" alt="RopaLive"></a>
        <nav class="site-nav"><a href="index.php#catalogo">Volver a la colección</a></nav>
        <div class="site-actions"><a class="button button-small" href="carrito.php">Ver carrito</a></div>
    </header>
    <main class="page-width detail-layout">
        <div class="detail-gallery"><?php foreach ($imagenes as $imagen) { ?><img class="detail-image" src="<?php echo h(escapar_imagen($imagen)); ?>" alt="<?php echo h($producto['nombre']); ?>"><?php } ?></div>
        <section class="detail-copy">
            <p class="eyebrow">RopaLive / <?php echo h($producto['coleccion'] ?: ucfirst($producto['genero'])); ?></p>
            <h1><?php echo h($producto['nombre']); ?></h1>
            <div class="product-price">$<?php echo number_format((float) $producto['precio'], 2); ?></div>
            <p><?php echo nl2br(h($producto['descripcion'] ?: 'Una prenda pensada para acompañarte.')); ?></p>
            <p class="product-stock"><?php echo (int) $producto['stock']; ?> unidades disponibles</p>
            <?php if ((int) $producto['stock'] > 0) { ?>
                <form method="post" action="index.php">
                    <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>">
                    <button type="submit">Agregar al carrito</button>
                </form>
            <?php } else { ?>
                <button type="button" disabled>Agotado</button>
            <?php } ?>
        </section>
    </main>
    <footer class="site-footer"><p>RopaLive · Moda para todos los días</p></footer>
</body>
</html>
