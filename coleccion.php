<?php
require_once __DIR__ . '/includes/Funciones.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar') {
    if (!csrf_valido()) { http_response_code(400); exit('La solicitud no es válida. Actualiza la página e inténtalo otra vez.'); }
    $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
    $coleccionId = filter_var($_POST['coleccion_id'] ?? null, FILTER_VALIDATE_INT);
    if ($productoId === false || $productoId < 1 || $coleccionId === false || $coleccionId < 1) { http_response_code(400); exit('El producto seleccionado no es válido.'); }
    $stmt = $conexion->prepare('SELECT id, stock FROM productos WHERE id = :id AND coleccion_id = :coleccion_id');
    $stmt->execute(['id' => $productoId, 'coleccion_id' => $coleccionId]);
    $producto = $stmt->fetch();
    $cantidad = (int) ($_SESSION['carrito'][$productoId] ?? 0);
    if (!$producto || $cantidad >= (int) $producto['stock']) { $_SESSION['mensaje'] = 'No hay más unidades disponibles de ese producto.'; }
    else { $_SESSION['carrito'][$productoId] = $cantidad + 1; $_SESSION['mensaje'] = 'Producto agregado al carrito.'; }
    header('Location: coleccion.php?id=' . $coleccionId); exit();
}
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) { http_response_code(404); exit('No se encontró la colección solicitada.'); }
$stmt = $conexion->prepare('SELECT id, nombre, descripcion, imagen FROM colecciones WHERE id = :id');
$stmt->execute(['id' => $id]);
$coleccion = $stmt->fetch();
if (!$coleccion) { http_response_code(404); exit('No se encontró la colección solicitada.'); }
$stmt = $conexion->prepare('SELECT imagen FROM coleccion_imagenes WHERE coleccion_id = :id ORDER BY orden, id');
$stmt->execute(['id' => $id]);
$imagenes = array_column($stmt->fetchAll(), 'imagen');
if (!$imagenes) { $imagenes = [$coleccion['imagen']]; }
$stmt = $conexion->prepare("SELECT DISTINCT marca FROM productos WHERE coleccion_id = :id AND marca <> '' ORDER BY marca");
$stmt->execute(['id' => $id]);
$marcasDisponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);
$marca = is_string($_GET['marca'] ?? null) ? $_GET['marca'] : '';
if (!in_array($marca, $marcasDisponibles, true)) { $marca = ''; }
$genero = is_string($_GET['genero'] ?? null) && in_array($_GET['genero'], ['mujer', 'hombre', 'unisex'], true)
    ? $_GET['genero']
    : '';
$condiciones = ['p.coleccion_id = :coleccion_id'];
$parametros = ['coleccion_id' => $id];
if ($marca !== '') {
    $condiciones[] = 'p.marca = :marca';
    $parametros['marca'] = $marca;
}
if ($genero !== '') {
    $condiciones[] = 'p.genero = :genero';
    $parametros['genero'] = $genero;
}
$stmt = $conexion->prepare(
    'SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, p.imagen, p.genero, p.marca, p.categoria
     FROM productos p WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY p.id DESC'
);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();
$mensaje = $_SESSION['mensaje'] ?? ''; unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo h($coleccion['nombre']); ?> | RopaLive</title><link rel="stylesheet" href="assets/css/base.css"><link rel="stylesheet" href="assets/css/tienda.css?v=<?php echo filemtime(__DIR__ . '/assets/css/tienda.css'); ?>"></head>
<body><header class="site-header"><a class="brand" href="index.php"><img class="brand-logo" src="assets/images/LOGO.png" width="120" height="50" alt="RopaLive"></a><nav class="site-nav"><a href="colecciones.php">Colecciones</a><a href="index.php#nosotros">Nosotros</a></nav><div class="site-actions"><a class="button button-small" href="carrito.php">Carrito</a></div></header>
<main class="page-width section">
    <div class="collection-hero"><img src="<?php echo h(escapar_imagen($imagenes[0])); ?>" alt="<?php echo h($coleccion['nombre']); ?>"><div><p class="eyebrow">Colección</p><h1><?php echo h($coleccion['nombre']); ?></h1><p><?php echo nl2br(h($coleccion['descripcion'] ?: 'Una selección pensada para descubrir y combinar.')); ?></p><a class="button button-light" href="#prendas">Explorar prendas</a></div></div>
    <?php if (count($imagenes) > 1) { ?><div class="collection-gallery"><?php foreach (array_slice($imagenes, 1) as $imagen) { ?><img src="<?php echo h(escapar_imagen($imagen)); ?>" alt="Imagen de la colección <?php echo h($coleccion['nombre']); ?>"><?php } ?></div><?php } ?>
    <?php if ($mensaje !== '') { ?><dialog open class="notice-modal notice-success"><p><?php echo h($mensaje); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
    <section class="section collection-products" id="prendas">
        <div class="section-heading">
            <div><p class="eyebrow">Prendas de la colección</p><h2><?php echo count($productos); ?> productos</h2></div>
        </div>
        <form class="collection-product-filters" method="get" action="coleccion.php">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <label>Género
                <select name="genero">
                    <option value="">Todos</option>
                    <option value="mujer" <?php echo $genero === 'mujer' ? 'selected' : ''; ?>>Mujer</option>
                    <option value="hombre" <?php echo $genero === 'hombre' ? 'selected' : ''; ?>>Hombre</option>
                    <option value="unisex" <?php echo $genero === 'unisex' ? 'selected' : ''; ?>>Unisex</option>
                </select>
            </label>
            <label>Marca
                <select name="marca">
                    <option value="">Todas</option>
                    <?php foreach ($marcasDisponibles as $marcaDisponible) { ?>
                        <option value="<?php echo h($marcaDisponible); ?>" <?php echo $marca === $marcaDisponible ? 'selected' : ''; ?>><?php echo h($marcaDisponible); ?></option>
                    <?php } ?>
                </select>
            </label>
            <button type="submit">Filtrar</button>
            <?php if ($marca !== '' || $genero !== '') { ?><a class="button button-light" href="coleccion.php?id=<?php echo $id; ?>#prendas">Quitar filtros</a><?php } ?>
        </form>
        <?php if ($productos) { ?>
            <div class="product-grid">
                <?php foreach ($productos as $producto) { ?>
                    <article class="product-card">
                        <a class="product-image" href="detalle.php?id=<?php echo (int) $producto['id']; ?>"><img src="<?php echo h(escapar_imagen($producto['imagen'])); ?>" alt="<?php echo h($producto['nombre']); ?>"></a>
                        <div class="product-info">
                            <h3><a href="detalle.php?id=<?php echo (int) $producto['id']; ?>"><?php echo h($producto['nombre']); ?></a></h3>
                            <p><?php echo h($producto['descripcion'] ?: 'Una prenda pensada para acompañarte.'); ?></p>
                            <div class="product-price">$<?php echo number_format((float) $producto['precio'], 2); ?></div>
                            <p class="product-gender"><?php echo h($producto['marca']); ?> · <?php echo h($producto['categoria']); ?> · <?php echo h(ucfirst($producto['genero'])); ?></p>
                            <p class="product-collection">Colección: <a href="coleccion.php?id=<?php echo $id; ?>"><?php echo h($coleccion['nombre']); ?></a></p>
                            <?php if ((int) $producto['stock'] > 0) { ?>
                                <form method="post">
                                    <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                                    <input type="hidden" name="accion" value="agregar">
                                    <input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>">
                                    <input type="hidden" name="coleccion_id" value="<?php echo $id; ?>">
                                    <button type="submit">Agregar al carrito</button>
                                </form>
                                <p class="product-stock"><?php echo (int) $producto['stock']; ?> disponibles</p>
                            <?php } else { ?>
                                <button type="button" disabled>Agotado</button>
                            <?php } ?>
                        </div>
                    </article>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="empty-state">No hay productos que coincidan con esos filtros en esta colección.</div>
        <?php } ?>
    </section>
</main><footer class="site-footer"><p>RopaLive · Moda para todos los días</p></footer></body></html>
