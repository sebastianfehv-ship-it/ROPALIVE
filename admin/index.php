<?php
require_once __DIR__ . '/../includes/verificar_admin.php';
$pedidos = $conexion->query(
    'SELECT p.id, p.nombre_cliente, p.total, p.fecha, p.ciudad, COUNT(d.id) AS productos
     FROM pedidos p LEFT JOIN pedido_detalles d ON d.pedido_id = p.id
     GROUP BY p.id ORDER BY p.id DESC LIMIT 8'
)->fetchAll();
$estadisticas = [
    'productos' => (int) $conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn(),
    'clientes' => (int) $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente'")->fetchColumn(),
    'colecciones' => (int) $conexion->query('SELECT COUNT(*) FROM colecciones')->fetchColumn(),
    'pedidos' => (int) $conexion->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Administración | RopaLive</title><link rel="stylesheet" href="../assets/css/base.css"><link rel="stylesheet" href="../assets/css/admin.css"></head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand" href="../index.php">ROPALIVE</a>
        <p>Panel de administración</p>
        <nav aria-label="Administración">
            <a class="active" href="index.php">Resumen</a>
            <a href="clientes/index.php">Clientes</a>
            <a href="productos/index.php">Productos</a>
            <a href="colecciones/index.php">Colecciones</a>
            <a href="pedidos/index.php">Pedidos</a>
            <a href="cuenta.php">Mi cuenta</a>
        </nav>
        <a class="sidebar-store" href="../index.php">Ver tienda ↗</a>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar"><div><p class="eyebrow">RopaLive / administración</p><h1>Hola, <?php echo h($_SESSION['usuario'] ?? 'Admin'); ?></h1></div><form method="post" action="../logout.php"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>"><button class="button button-light button-small" type="submit">Cerrar sesión</button></form></header>
        <section class="stats-grid">
            <a class="stat-card" href="pedidos/index.php"><span>Pedidos</span><strong><?php echo $estadisticas['pedidos']; ?></strong><small>Revisar ventas registradas</small></a>
            <a class="stat-card" href="clientes/index.php"><span>Clientes</span><strong><?php echo $estadisticas['clientes']; ?></strong><small>Cuentas registradas</small></a>
            <a class="stat-card" href="productos/index.php"><span>Productos</span><strong><?php echo $estadisticas['productos']; ?></strong><small>Prendas en el catálogo</small></a>
            <a class="stat-card" href="colecciones/index.php"><span>Colecciones</span><strong><?php echo $estadisticas['colecciones']; ?></strong><small>Selecciones publicadas</small></a>
        </section>
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">Actividad de la tienda</p><h2>Pedidos recientes</h2></div><a class="button button-light button-small" href="pedidos/index.php">Ver todos</a></div>
            <div class="table-scroll"><table class="admin-table"><thead><tr><th>Pedido</th><th>Cliente</th><th>Ciudad</th><th>Prendas</th><th>Total</th><th>Fecha</th></tr></thead><tbody>
            <?php if ($pedidos) { foreach ($pedidos as $pedido) { ?><tr><td>#<?php echo (int) $pedido['id']; ?></td><td><?php echo h($pedido['nombre_cliente']); ?></td><td><?php echo h($pedido['ciudad']); ?></td><td><?php echo (int) $pedido['productos']; ?></td><td>$<?php echo number_format((float) $pedido['total'], 2); ?></td><td><?php echo h($pedido['fecha']); ?></td></tr><?php } } else { ?><tr><td colspan="6">Todavía no hay pedidos registrados.</td></tr><?php } ?>
            </tbody></table></div>
        </section>
    </main>
</div>
</body></html>
