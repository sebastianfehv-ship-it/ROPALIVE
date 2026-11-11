<?php
require_once __DIR__ . '/includes/Funciones.php';
$colecciones = $conexion->query(
    'SELECT c.id, c.nombre, c.descripcion, c.imagen, COUNT(p.id) AS productos
     FROM colecciones c LEFT JOIN productos p ON p.coleccion_id = c.id
     GROUP BY c.id ORDER BY c.id DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Colecciones | RopaLive</title><link rel="stylesheet" href="assets/css/base.css"><link rel="stylesheet" href="assets/css/tienda.css"></head>
<body><header class="site-header"><a class="brand" href="index.php">ROPALIVE</a><nav class="site-nav"><a href="index.php#catalogo">Productos</a><a href="index.php#nosotros">Nosotros</a></nav><div class="site-actions"><a class="button button-small" href="carrito.php">Carrito</a></div></header>
<main class="page-width section"><div class="section-heading"><div><p class="eyebrow">RopaLive / colecciones</p><h1 class="page-title">Todas las colecciones</h1><p>Explora cada propuesta por género y encuentra tu próximo look.</p></div></div>
<?php if ($colecciones) { ?><div class="collection-grid"><?php foreach ($colecciones as $coleccion) { ?><article class="collection-card"><a href="coleccion.php?id=<?php echo (int) $coleccion['id']; ?>"><img src="<?php echo h(escapar_imagen($coleccion['imagen'])); ?>" alt="<?php echo h($coleccion['nombre']); ?>"><span><strong><?php echo h($coleccion['nombre']); ?></strong><small><?php echo (int) $coleccion['productos']; ?> prendas</small></span></a><p><?php echo h($coleccion['descripcion'] ?: 'Descubre las prendas de esta colección.'); ?></p><a class="button button-light button-small" href="coleccion.php?id=<?php echo (int) $coleccion['id']; ?>">Ver colección</a></article><?php } ?></div><?php } else { ?><div class="empty-state">Pronto encontrarás nuevas colecciones.</div><?php } ?>
</main><footer class="site-footer"><p>RopaLive · Moda para todos los días</p></footer></body></html>
