<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';
$estados = ['pendiente', 'preparando', 'enviado', 'entregado', 'cancelado'];
$mensaje = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
    $estado = texto_post('estado');
    if (!csrf_valido() || $id === false || $id < 1 || !in_array($estado, $estados, true)) {
        http_response_code(400);
        exit('La actualización del pedido no es válida.');
    }
    $stmt = $conexion->prepare('UPDATE pedidos SET estado = :estado WHERE id = :id');
    $stmt->execute(['estado' => $estado, 'id' => $id]);
    $mensaje = 'El estado del pedido quedó actualizado.';
}
$pedidos = $conexion->query('SELECT id, usuario_id, nombre_cliente, direccion, ciudad, total, estado, fecha FROM pedidos ORDER BY fecha DESC')->fetchAll();
$detalles = $conexion->query('SELECT pedido_id, nombre_producto, imagen_producto, cantidad, precio_unitario, subtotal FROM pedido_detalles ORDER BY id')->fetchAll();
$detallesPorPedido = [];
foreach ($detalles as $detalle) { $detallesPorPedido[(int) $detalle['pedido_id']][] = $detalle; }
$adminBasePath = '../../';
$adminSection = 'pedidos';
$adminTitle = 'Pedidos';
require __DIR__ . '/../layout_inicio.php';
?>
<section class="panel"><div class="panel-heading"><div><p class="eyebrow">Ventas</p><h2>Pedidos recientes</h2></div></div>
<?php if ($mensaje !== '') { ?><div class="notice notice-success" role="status"><?php echo h($mensaje); ?></div><?php } ?>
<div class="table-scroll"><table class="admin-table"><thead><tr><th>Pedido</th><th>Cliente y entrega</th><th>Detalle</th><th>Total</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>
<?php if ($pedidos) { foreach ($pedidos as $pedido) { $pedidoId = (int) $pedido['id']; ?><tr>
<td>#<?php echo $pedidoId; ?></td><td><strong><?php echo h($pedido['nombre_cliente']); ?></strong><br><?php echo h($pedido['direccion']); ?><br><?php echo h($pedido['ciudad']); ?></td>
<td><details class="order-details"><summary><?php echo count($detallesPorPedido[$pedidoId] ?? []); ?> productos</summary><?php foreach ($detallesPorPedido[$pedidoId] ?? [] as $detalle) { ?><div class="order-line"><img src="<?php echo h('../../' . escapar_imagen($detalle['imagen_producto'])); ?>" alt=""><span><?php echo h($detalle['nombre_producto']); ?><small><?php echo (int) $detalle['cantidad']; ?> × $<?php echo number_format((float) $detalle['precio_unitario'], 2); ?></small></span><strong>$<?php echo number_format((float) $detalle['subtotal'], 2); ?></strong></div><?php } ?></details></td>
<td>$<?php echo number_format((float) $pedido['total'], 2); ?></td>
<td><form method="post" class="status-form"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>"><input type="hidden" name="pedido_id" value="<?php echo $pedidoId; ?>"><select name="estado"><?php foreach ($estados as $estado) { ?><option value="<?php echo h($estado); ?>" <?php echo $pedido['estado'] === $estado ? 'selected' : ''; ?>><?php echo h(ucfirst($estado)); ?></option><?php } ?></select><button class="button-small" type="submit">Guardar</button></form></td><td><?php echo h($pedido['fecha']); ?></td>
</tr><?php } } else { ?><tr><td colspan="6">Todavía no hay pedidos registrados.</td></tr><?php } ?>
</tbody></table></div></section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
