<?php
require_once __DIR__ . '/includes/Funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar') {
    if (!csrf_valido()) {
        http_response_code(400);
        exit('La solicitud no es válida. Actualiza la página e inténtalo otra vez.');
    }

    $id = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        http_response_code(400);
        exit('El producto seleccionado no es válido.');
    }

    $stmt = $conexion->prepare('SELECT id, stock FROM productos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $producto = $stmt->fetch();
    $cantidad = (int) ($_SESSION['carrito'][$id] ?? 0);

    if (!$producto || $cantidad >= (int) $producto['stock']) {
        $_SESSION['mensaje'] = 'No hay más unidades disponibles de ese producto.';
    } else {
        $_SESSION['carrito'][$id] = $cantidad + 1;
        $_SESSION['mensaje'] = 'Producto agregado al carrito.';
    }

    header('Location: index.php#catalogo');
    exit();
}

$genero = $_GET['genero'] ?? '';
$genero = in_array($genero, ['mujer', 'hombre', 'unisex'], true) ? $genero : '';
$busqueda = is_string($_GET['buscar'] ?? null) ? trim($_GET['buscar']) : '';
$busqueda = substr($busqueda, 0, 100);
$marcasDisponibles = $conexion->query("SELECT DISTINCT marca FROM productos WHERE marca <> '' ORDER BY marca")->fetchAll(PDO::FETCH_COLUMN);
$categoriasDisponibles = $conexion->query("SELECT DISTINCT categoria FROM productos WHERE categoria <> '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$marcas = array_values(array_intersect($marcasDisponibles, array_slice(array_filter((array) ($_GET['marcas'] ?? []), 'is_string'), 0, 20)));
$categorias = array_values(array_intersect($categoriasDisponibles, array_slice(array_filter((array) ($_GET['categorias'] ?? []), 'is_string'), 0, 20)));
$precioMin = is_numeric($_GET['precio_min'] ?? null) ? max(0, (float) $_GET['precio_min']) : null;
$precioMax = is_numeric($_GET['precio_max'] ?? null) ? max(0, (float) $_GET['precio_max']) : null;
if ($precioMin !== null && $precioMax !== null && $precioMin > $precioMax) {
    $precioMax = null;
}
$orden = is_string($_GET['orden'] ?? null) ? $_GET['orden'] : 'recientes';
$ordenes = [
    'recientes' => 'p.id DESC',
    'precio_asc' => 'p.precio ASC, p.id DESC',
    'precio_desc' => 'p.precio DESC, p.id DESC',
];
if (!isset($ordenes[$orden])) {
    $orden = 'recientes';
}
$condiciones = ['1 = 1'];
$parametros = [];
if ($genero !== '') {
    $condiciones[] = 'p.genero = :genero';
    $parametros['genero'] = $genero;
}
if ($marcas) {
    $marcadoresMarca = [];
    foreach ($marcas as $indice => $marca) {
        $marcador = 'marca_' . $indice;
        $marcadoresMarca[] = ':' . $marcador;
        $parametros[$marcador] = $marca;
    }
    $condiciones[] = 'p.marca IN (' . implode(', ', $marcadoresMarca) . ')';
}
if ($categorias) {
    $marcadoresCategoria = [];
    foreach ($categorias as $indice => $categoria) {
        $marcador = 'categoria_' . $indice;
        $marcadoresCategoria[] = ':' . $marcador;
        $parametros[$marcador] = $categoria;
    }
    $condiciones[] = 'p.categoria IN (' . implode(', ', $marcadoresCategoria) . ')';
}
if ($busqueda !== '') {
    $condiciones[] = '(p.nombre LIKE :buscar_nombre OR p.descripcion LIKE :buscar_descripcion)';
    $parametros['buscar_nombre'] = '%' . $busqueda . '%';
    $parametros['buscar_descripcion'] = '%' . $busqueda . '%';
}
if ($precioMin !== null) {
    $condiciones[] = 'p.precio >= :precio_min';
    $parametros['precio_min'] = $precioMin;
}
if ($precioMax !== null) {
    $condiciones[] = 'p.precio <= :precio_max';
    $parametros['precio_max'] = $precioMax;
}
$sqlProductos = 'SELECT p.id, p.coleccion_id, p.nombre, p.descripcion, p.marca, p.categoria, p.precio, p.stock, p.imagen, p.genero, c.nombre AS nombre_coleccion
    FROM productos p LEFT JOIN colecciones c ON c.id = p.coleccion_id WHERE '
    . implode(' AND ', $condiciones) . ' ORDER BY ' . $ordenes[$orden];
$stmt = $conexion->prepare($sqlProductos);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();
$colecciones = $conexion->query(
    'SELECT c.id, c.nombre, c.descripcion, c.imagen, COUNT(p.id) AS productos
     FROM colecciones c LEFT JOIN productos p ON p.coleccion_id = c.id
     GROUP BY c.id ORDER BY c.id DESC'
)->fetchAll();
$coleccionDestacada = $colecciones[0] ?? null;
$total_carrito = isset($_SESSION['carrito']) ? array_sum($_SESSION['carrito']) : 0;
$productos_carrito = [];
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    $stmtCarrito = $conexion->prepare('SELECT id, nombre, precio, imagen FROM productos WHERE id = :id');
    foreach ($_SESSION['carrito'] as $idCarrito => $cantidadCarrito) {
        $idCarrito = filter_var($idCarrito, FILTER_VALIDATE_INT);
        if ($idCarrito === false || $idCarrito < 1 || (int) $cantidadCarrito < 1) {
            continue;
        }
        $stmtCarrito->execute(['id' => $idCarrito]);
        $productoCarrito = $stmtCarrito->fetch();
        if ($productoCarrito) {
            $productoCarrito['cantidad'] = (int) $cantidadCarrito;
            $productos_carrito[] = $productoCarrito;
        }
    }
}
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
$hero = escapar_imagen('hero-bg.jpg');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Descubre prendas urbanas y esenciales en RopaLive.">
    <title>RopaLive | Ropa para todos los días</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/tienda.css?v=<?php echo filemtime(__DIR__ . '/assets/css/tienda.css'); ?>">
    <link rel="stylesheet" href="assets/css/confirmaciones.css">
</head>
<body>
    <div class="top-note" id="inicio">ENVÍOS A TODO EL PAÍS · NUEVA COLECCIÓN DISPONIBLE</div>
    <header class="site-header">
        <a class="brand" href="index.php"><img class="brand-logo" src="assets/images/LOGO.png" width="120" height="50" alt="RopaLive"></a>
        <nav class="site-nav" aria-label="Navegación principal">
            <a href="#inicio">Inicio</a>
            <a href="colecciones.php">Colección</a>
            <a href="#nosotros">Nosotros</a>
        </nav>
        <div class="site-actions">
            <?php if (!empty($_SESSION['usuario'])) { ?>
                <?php if (es_admin()) { ?>
                    <a href="admin/index.php">Administración</a>
                <?php } else { ?>
                    <details class="account-menu">
                        <summary>Mi cuenta</summary>
                        <div class="account-popover">
                            <div class="account-identity">
                                <?php if (!empty($_SESSION['foto'])) { ?><img class="avatar" src="<?php echo h(escapar_imagen($_SESSION['foto'])); ?>" alt="Foto de <?php echo h($_SESSION['usuario']); ?>"><?php } else { ?><span class="avatar">RL</span><?php } ?>
                                <span><strong><?php echo h($_SESSION['usuario']); ?></strong><small><?php echo h($_SESSION['email'] ?? ''); ?></small></span>
                            </div>
                            <a href="mi_cuenta.php">Mis pedidos y perfil</a>
                            <a href="carrito.php">Carrito</a>
                            <form method="post" action="logout.php" data-confirm-message="¿Quieres cerrar tu sesión?" data-confirm-title="Cerrar sesión"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>"><button class="button button-light button-small" type="submit">Cerrar sesión</button></form>
                        </div>
                    </details>
                <?php } ?>
            <?php } else { ?>
                <a href="Login.php">Iniciar sesión</a>
            <?php } ?>
            <details class="cart-menu">
                <summary class="button button-small">Carrito (<?php echo (int) $total_carrito; ?>)</summary>
                <div class="cart-popover">
                    <?php if ($productos_carrito) { foreach ($productos_carrito as $productoCarrito) { ?>
                        <div class="cart-preview-item"><img src="<?php echo h(escapar_imagen($productoCarrito['imagen'])); ?>" alt=""><span><?php echo h($productoCarrito['nombre']); ?><small><?php echo (int) $productoCarrito['cantidad']; ?> × $<?php echo number_format((float) $productoCarrito['precio'], 2); ?></small></span></div>
                    <?php } ?>
                        <a class="button" href="carrito.php">Ver carrito y pagar</a>
                    <?php } else { ?><p>Tu carrito está vacío.</p><a href="#catalogo">Explorar prendas</a><?php } ?>
                </div>
            </details>
        </div>
    </header>

    <main>
        <section class="hero" style="background-image: linear-gradient(90deg, rgba(0,0,0,.62), rgba(0,0,0,.04)), url('<?php echo h($hero); ?>');">
            <div class="hero-content">
                <p class="eyebrow">Colección actual</p>
                <h1>Menos ruido.<br>Más estilo.</h1>
                <p>Prendas versátiles para crear un estilo propio, todos los días.</p>
                <a class="button" href="#catalogo">Ver colección</a>
            </div>
        </section>

        <section class="page-width section" id="catalogo">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">RopaLive / 01</p>
                    <h2>Encuentra tu estilo</h2>
                    <p>Prendas elegidas para acompañarte todos los días.</p>
                </div>
            </div>

            <?php if ($mensaje !== '') { ?>
                <dialog open class="notice-modal notice-success" role="status"><p><?php echo h($mensaje); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog>
            <?php } ?>

            <form class="catalog-search" method="get" action="index.php#catalogo">
                <label class="catalog-search-input"><span class="visually-hidden">Buscar productos</span><input type="search" name="buscar" placeholder="Buscar prendas..." value="<?php echo h($busqueda); ?>"></label>
                <input type="hidden" name="genero" value="<?php echo h($genero); ?>">
                <input type="hidden" name="precio_min" value="<?php echo $precioMin !== null ? h($precioMin) : ''; ?>">
                <input type="hidden" name="precio_max" value="<?php echo $precioMax !== null ? h($precioMax) : ''; ?>">
                <?php foreach ($marcas as $marca) { ?><input type="hidden" name="marcas[]" value="<?php echo h($marca); ?>"><?php } ?>
                <?php foreach ($categorias as $categoria) { ?><input type="hidden" name="categorias[]" value="<?php echo h($categoria); ?>"><?php } ?>
                <label class="catalog-sort">Ordenar por
                    <select name="orden">
                        <option value="recientes" <?php echo $orden === 'recientes' ? 'selected' : ''; ?>>Novedades</option>
                        <option value="precio_asc" <?php echo $orden === 'precio_asc' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                        <option value="precio_desc" <?php echo $orden === 'precio_desc' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                    </select>
                </label>
                <button class="button-small" type="submit">Buscar</button>
            </form>

            <nav class="category-shortcuts" aria-label="Categorías destacadas">
                <a class="<?php echo !$categorias ? 'active' : ''; ?>" href="index.php#catalogo">Todas las prendas</a>
                <?php foreach (array_slice($categoriasDisponibles, 0, 7) as $categoriaDestacada) {
                    $consultaCategoria = $_GET;
                    $consultaCategoria['categorias'] = [$categoriaDestacada];
                    unset($consultaCategoria['categoria']);
                ?>
                    <a class="<?php echo in_array($categoriaDestacada, $categorias, true) ? 'active' : ''; ?>" href="index.php?<?php echo h(http_build_query($consultaCategoria)); ?>#catalogo"><?php echo h($categoriaDestacada); ?></a>
                <?php } ?>
            </nav>

            <div class="catalog-layout">
                <aside class="catalog-sidebar">
                    <form method="get" action="index.php#catalogo">
                        <input type="hidden" name="buscar" value="<?php echo h($busqueda); ?>">
                        <input type="hidden" name="orden" value="<?php echo h($orden); ?>">
                        <div class="filter-heading"><strong>Filtros</strong><a href="index.php#catalogo">Limpiar</a></div>
                        <fieldset class="filter-group">
                            <legend>Género</legend>
                            <?php foreach (['' => 'Todos', 'mujer' => 'Mujer', 'hombre' => 'Hombre', 'unisex' => 'Unisex'] as $valorGenero => $etiquetaGenero) { ?>
                                <label><input type="radio" name="genero" value="<?php echo h($valorGenero); ?>" <?php echo $genero === $valorGenero ? 'checked' : ''; ?>><?php echo h($etiquetaGenero); ?></label>
                            <?php } ?>
                        </fieldset>
                        <fieldset class="filter-group">
                            <legend>Marca</legend>
                            <?php if (!$marcasDisponibles) { ?><p class="filter-empty">Las marcas aparecerán aquí.</p><?php } ?>
                            <?php foreach ($marcasDisponibles as $marcaDisponible) { ?>
                                <label><input type="checkbox" name="marcas[]" value="<?php echo h($marcaDisponible); ?>" <?php echo in_array($marcaDisponible, $marcas, true) ? 'checked' : ''; ?>><?php echo h($marcaDisponible); ?></label>
                            <?php } ?>
                        </fieldset>
                        <fieldset class="filter-group">
                            <legend>Categoría</legend>
                            <?php if (!$categoriasDisponibles) { ?><p class="filter-empty">Las categorías aparecerán aquí.</p><?php } ?>
                            <?php foreach ($categoriasDisponibles as $categoriaDisponible) { ?>
                                <label><input type="checkbox" name="categorias[]" value="<?php echo h($categoriaDisponible); ?>" <?php echo in_array($categoriaDisponible, $categorias, true) ? 'checked' : ''; ?>><?php echo h($categoriaDisponible); ?></label>
                            <?php } ?>
                        </fieldset>
                        <fieldset class="filter-group">
                            <legend>Precio</legend>
                            <label for="precio_min">Desde</label>
                            <input id="precio_min" type="number" name="precio_min" min="0" step="1000" placeholder="$ 0" value="<?php echo $precioMin !== null ? h($precioMin) : ''; ?>">
                            <label for="precio_max">Hasta</label>
                            <input id="precio_max" type="number" name="precio_max" min="0" step="1000" placeholder="Sin límite" value="<?php echo $precioMax !== null ? h($precioMax) : ''; ?>">
                        </fieldset>
                        <button class="filter-apply" type="submit">Aplicar filtros</button>
                        <a class="filter-reset" href="index.php#catalogo">Limpiar filtros</a>
                    </form>
                </aside>
                <div class="catalog-results">
                    <div class="catalog-results-heading"><span><?php echo count($productos); ?> productos</span><a href="colecciones.php">Ver productos por colección</a></div>
                    <?php if ($productos) { ?>
                        <div class="product-grid">
                    <?php foreach ($productos as $producto) { ?>
                        <article class="product-card">
                            <a class="product-image" href="detalle.php?id=<?php echo (int) $producto['id']; ?>">
                                <img src="<?php echo h(escapar_imagen($producto['imagen'] ?? '')); ?>" alt="<?php echo h($producto['nombre']); ?>">
                            </a>
                            <div class="product-info">
                                <h3><a href="detalle.php?id=<?php echo (int) $producto['id']; ?>"><?php echo h($producto['nombre']); ?></a></h3>
                                <p><?php echo h($producto['descripcion'] ?: 'Una prenda pensada para acompañarte.'); ?></p>
                                <div class="product-price">$<?php echo number_format((float) $producto['precio'], 2); ?></div>
                                <p class="product-gender"><?php echo h($producto['marca']); ?> · <?php echo h($producto['categoria']); ?> · <?php echo h(ucfirst($producto['genero'])); ?></p>
                                <?php if (!empty($producto['nombre_coleccion'])) { ?>
                                    <p class="product-collection">Colección: <a href="coleccion.php?id=<?php echo (int) $producto['coleccion_id']; ?>"><?php echo h($producto['nombre_coleccion']); ?></a></p>
                                <?php } ?>
                                <?php if ((int) $producto['stock'] > 0) { ?>
                                    <form method="post">
                                        <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                                        <input type="hidden" name="accion" value="agregar">
                                        <input type="hidden" name="producto_id" value="<?php echo (int) $producto['id']; ?>">
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
                        <div class="empty-state">No encontramos productos con esos filtros.<br><a href="colecciones.php">Ver colecciones</a></div>
                    <?php } ?>
                </div>
            </div>
        </section>

        <?php if ($coleccionDestacada) { ?>
            <section class="page-width featured-collection">
                <a class="featured-collection-image" href="coleccion.php?id=<?php echo (int) $coleccionDestacada['id']; ?>">
                    <img src="<?php echo h(escapar_imagen($coleccionDestacada['imagen'])); ?>" alt="">
                </a>
                <div class="featured-collection-copy">
                    <p class="eyebrow">Colecciones</p>
                    <h2><?php echo h($coleccionDestacada['nombre']); ?></h2>
                    <p><?php echo h($coleccionDestacada['descripcion'] ?: 'Descubre las prendas de nuestra colección más reciente.'); ?></p>
                    <a class="button" href="coleccion.php?id=<?php echo (int) $coleccionDestacada['id']; ?>">Explorar colección</a>
                </div>
            </section>
        <?php } ?>

        <section class="page-width section" id="nosotros">
            <div class="about-intro">
                <p class="eyebrow">RopaLive / 03 · Nosotros</p>
                <h2>Vestirse bien también es sentirse bien.</h2>
                <p>Creemos en una moda que se adapta a tu vida: prendas versátiles, diseño cuidado y una forma más cercana de elegir qué vestir.</p>
                <a class="about-link" href="colecciones.php">Conoce nuestras colecciones <span aria-hidden="true">→</span></a>
            </div>
            <div class="about-grid">
                <article><span class="about-number">01</span><span class="about-icon" aria-hidden="true">✳</span><h3>Nuestra misión</h3><p>Crear prendas cómodas y bien pensadas que acompañen tu día a día y te ayuden a expresar quién eres.</p></article>
                <article><span class="about-number">02</span><span class="about-icon" aria-hidden="true">↗</span><h3>Nuestra visión</h3><p>Crecer como una marca cercana, reconocida por su estilo, calidad y una experiencia de compra sencilla.</p></article>
                <article><span class="about-number">03</span><span class="about-icon" aria-hidden="true">♡</span><h3>Lo que nos mueve</h3><p>Autenticidad, inclusión, calidad y respeto por las personas que hacen parte de nuestra comunidad.</p></article>
            </div>
        </section>
    </main>
    <footer class="site-footer"><p>RopaLive · Moda para todos los días</p></footer>
    <script src="assets/js/confirmaciones.js" defer></script>
</body>
</html>
