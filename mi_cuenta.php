<?php
require_once __DIR__ . '/includes/Funciones.php';
requiere_cliente();
$idCliente = (int) $_SESSION['usuario_id'];
$error = '';
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(texto_post('nombre'));
    $direccion = trim(texto_post('direccion'));
    $ciudad = trim(texto_post('ciudad'));
    $password = texto_post('password');
    if (!csrf_valido()) { $error = 'La sesión del formulario venció. Actualiza la página.'; }
    elseif ($nombre === '' || strlen($nombre) > 150) { $error = 'El nombre es obligatorio y debe tener máximo 150 caracteres.'; }
    elseif (strlen($direccion) > 255 || strlen($ciudad) > 100) { $error = 'La dirección o la ciudad supera la longitud permitida.'; }
    elseif ($password !== '' && (strlen($password) < 8 || strlen($password) > 72)) { $error = 'La contraseña debe tener entre 8 y 72 caracteres.'; }
    else {
        try {
            $fotos = guardar_imagenes_subidas('foto', 'users', 1);
            $sql = 'UPDATE usuarios SET nombre = :nombre, direccion = :direccion, ciudad = :ciudad';
            $parametros = ['nombre' => $nombre, 'direccion' => $direccion !== '' ? $direccion : null, 'ciudad' => $ciudad !== '' ? $ciudad : null, 'id' => $idCliente];
            if ($password !== '') {
                $sql .= ', password = :password';
                $parametros['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if ($fotos) {
                $sql .= ', foto = :foto';
                $parametros['foto'] = $fotos[0];
                $_SESSION['foto'] = $fotos[0];
            }
            $sql .= " WHERE id = :id AND rol = 'cliente'";
            $stmt = $conexion->prepare($sql);
            $stmt->execute($parametros);
            $_SESSION['usuario'] = $nombre;
            $mensaje = 'Tu perfil se actualizó correctamente.';
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}
$stmt = $conexion->prepare('SELECT nombre, email, direccion, ciudad, foto, creado_en FROM usuarios WHERE id = :id AND rol = :rol');
$stmt->execute(['id' => $idCliente, 'rol' => 'cliente']);
$perfil = $stmt->fetch();
if (!$perfil) { http_response_code(404); exit('No se encontró la cuenta.'); }
$stmt = $conexion->prepare('SELECT id, total, estado, fecha, direccion, ciudad FROM pedidos WHERE usuario_id = :id ORDER BY fecha DESC');
$stmt->execute(['id' => $idCliente]);
$pedidos = $stmt->fetchAll();
$pedidoIds = array_map('intval', array_column($pedidos, 'id'));
$detallesPorPedido = [];
if ($pedidoIds) {
    $marcadores = implode(',', array_fill(0, count($pedidoIds), '?'));
    $stmt = $conexion->prepare('SELECT pedido_id, nombre_producto, imagen_producto, cantidad, subtotal FROM pedido_detalles WHERE pedido_id IN (' . $marcadores . ') ORDER BY id');
    $stmt->execute($pedidoIds);
    foreach ($stmt->fetchAll() as $detalle) { $detallesPorPedido[(int) $detalle['pedido_id']][] = $detalle; }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi cuenta | RopaLive</title><link rel="stylesheet" href="assets/css/base.css"><link rel="stylesheet" href="assets/css/formularios.css"><link rel="stylesheet" href="assets/css/admin.css"><link rel="stylesheet" href="assets/css/confirmaciones.css"></head>
<body><header class="site-header"><a class="brand" href="index.php"><img class="brand-logo" src="assets/images/LOGO.png" width="120" height="50" alt="RopaLive"></a><nav class="site-nav"><a href="index.php">Seguir comprando</a><a href="carrito.php">Carrito</a></nav><div class="site-actions"><form method="post" action="logout.php" data-confirm-message="¿Quieres cerrar tu sesión?" data-confirm-title="Cerrar sesión"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>"><button class="button button-light button-small" type="submit">Cerrar sesión</button></form></div></header>
<main class="page-width account-page">
    <?php if ($error !== '') { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h($error); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
    <?php if ($mensaje !== '') { ?><dialog open class="notice-modal notice-success" role="status"><p><?php echo h($mensaje); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
    <section class="account-profile panel">
        <div class="account-profile-head"><?php if ($perfil['foto']) { ?><img class="account-avatar" src="<?php echo h(escapar_imagen($perfil['foto'])); ?>" alt="Foto de <?php echo h($perfil['nombre']); ?>"><?php } else { ?><span class="account-avatar account-avatar-fallback">RL</span><?php } ?><div><p class="eyebrow">Mi cuenta</p><h1 class="page-title">Hola, <?php echo h($perfil['nombre']); ?></h1><p><?php echo h($perfil['email']); ?></p></div></div>
        <form method="post" enctype="multipart/form-data" class="profile-form"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
            <div class="profile-fields">
                <label>Nombre<input name="nombre" maxlength="150" value="<?php echo h($_POST['nombre'] ?? $perfil['nombre']); ?>" required></label>
                <label>Email<input value="<?php echo h($perfil['email']); ?>" disabled></label>
                <label>Dirección<input name="direccion" maxlength="255" autocomplete="street-address" value="<?php echo h($_POST['direccion'] ?? $perfil['direccion']); ?>"></label>
                <label>Ciudad<input name="ciudad" maxlength="100" autocomplete="address-level2" value="<?php echo h($_POST['ciudad'] ?? $perfil['ciudad']); ?>"></label>
                <label>Foto de perfil<input type="file" name="foto[]" accept="image/jpeg,image/png,image/webp"></label>
                <label>Cambiar contraseña (opcional)<input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password"></label>
            </div><button type="submit">Guardar perfil</button>
        </form>
        <div class="account-links"><a class="button button-light" href="carrito.php">Ver mi carrito</a><a class="button button-light" href="#mis-pedidos">Mis pedidos (<?php echo count($pedidos); ?>)</a><form method="post" action="eliminar_cuenta.php" data-confirm-message="Esta acción es permanente. ¿Quieres eliminar tu cuenta?" data-confirm-title="Eliminar cuenta" data-confirm-danger="true"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>"><button class="button-danger" type="submit">Eliminar cuenta</button></form></div>
    </section>
    <section class="panel" id="mis-pedidos"><div class="panel-heading"><div><p class="eyebrow">Compras</p><h2>Mis pedidos</h2></div></div>
        <?php if ($pedidos) { ?><div class="customer-orders"><?php foreach ($pedidos as $pedido) { $pedidoId = (int) $pedido['id']; ?><article class="customer-order"><div class="customer-order-head"><div><strong>Pedido #<?php echo $pedidoId; ?></strong><small><?php echo h($pedido['fecha']); ?></small></div><span class="order-status"><?php echo h(ucfirst($pedido['estado'])); ?></span><strong>$<?php echo number_format((float) $pedido['total'], 2); ?></strong></div><p>Entrega: <?php echo h($pedido['direccion']); ?>, <?php echo h($pedido['ciudad']); ?></p><div class="customer-order-items"><?php foreach ($detallesPorPedido[$pedidoId] ?? [] as $detalle) { ?><div><img src="<?php echo h(escapar_imagen($detalle['imagen_producto'])); ?>" alt=""><span><?php echo h($detalle['nombre_producto']); ?><small><?php echo (int) $detalle['cantidad']; ?> unidad(es)</small></span></div><?php } ?></div></article><?php } ?></div><?php } else { ?><div class="empty-state">Todavía no tienes pedidos. <a href="index.php#catalogo">Explora el catálogo</a>.</div><?php } ?>
    </section>
</main><footer class="site-footer"><p>RopaLive</p></footer><script src="assets/js/confirmaciones.js" defer></script></body></html>
