<?php
require_once __DIR__ . '/includes/Funciones.php';

$error = '';
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        http_response_code(400);
        exit('La solicitud no es válida. Actualiza la página e inténtalo otra vez.');
    }

    $accion = $_POST['accion'] ?? '';
    if ($accion === 'vaciar') {
        unset($_SESSION['carrito']);
        $mensaje = 'El carrito quedó vacío.';
    } elseif ($accion === 'actualizar') {
        $id = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || $cantidad === false || $cantidad < 0) {
            $error = 'La cantidad indicada no es válida.';
        } else {
            $stmt = $conexion->prepare('SELECT stock FROM productos WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $producto = $stmt->fetch();
            if (!$producto) {
                unset($_SESSION['carrito'][$id]);
                $mensaje = 'El producto ya no está disponible y se quitó del carrito.';
            } elseif ($cantidad > (int) $producto['stock']) {
                $error = 'La cantidad supera las unidades disponibles.';
            } elseif ($cantidad === 0) {
                unset($_SESSION['carrito'][$id]);
                $mensaje = 'Producto eliminado del carrito.';
            } else {
                $_SESSION['carrito'][$id] = $cantidad;
                $mensaje = 'Cantidad actualizada.';
            }
        }
    } elseif ($accion === 'comprar') {
        if (empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') {
            $_SESSION['despues_login'] = 'carrito.php';
            header('Location: Login.php');
            exit();
        }

        if (empty($_SESSION['carrito'])) {
            $error = 'Agrega un producto antes de finalizar la compra.';
        } else {
            $nombreCliente = trim(texto_post('nombre_cliente'));
            $direccion = trim(texto_post('direccion'));
            $ciudad = trim(texto_post('ciudad'));
            if ($nombreCliente === '' || strlen($nombreCliente) > 150 || $direccion === '' || strlen($direccion) > 255 || $ciudad === '' || strlen($ciudad) > 100) {
                $error = 'Completa tu nombre, dirección y ciudad para continuar con la compra.';
            }

            $total = 0;
            $items = [];
            if ($error === '') {
                $conexion->beginTransaction();
            }

            try {
                if ($error !== '') {
                    throw new RuntimeException($error);
                }
                foreach ($_SESSION['carrito'] as $id => $cantidad) {
                    $id = filter_var($id, FILTER_VALIDATE_INT);
                    $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT);
                    if ($id === false || $id < 1 || $cantidad === false || $cantidad < 1) {
                        $error = 'Hay un producto inválido en el carrito.';
                        break;
                    }

                    $stmt = $conexion->prepare('SELECT id, nombre, imagen, precio, stock FROM productos WHERE id = :id FOR UPDATE');
                    $stmt->execute(['id' => $id]);
                    $producto = $stmt->fetch();

                    if (!$producto || (int) $producto['stock'] < $cantidad) {
                        $error = 'Uno de los productos ya no tiene unidades suficientes. Revisa el carrito.';
                        break;
                    }

                    $items[] = ['producto' => $producto, 'cantidad' => $cantidad];
                    $total += (float) $producto['precio'] * $cantidad;
                }

                if ($error !== '') {
                    $conexion->rollBack();
                } elseif (!$items) {
                    $conexion->rollBack();
                    $error = 'No hay productos disponibles para comprar.';
                } else {
                    $stmt = $conexion->prepare(
                        'INSERT INTO pedidos (usuario_id, nombre_cliente, direccion, ciudad, total)
                         VALUES (:usuario_id, :nombre_cliente, :direccion, :ciudad, :total)'
                    );
                    $stmt->execute([
                        'usuario_id' => (int) $_SESSION['usuario_id'],
                        'nombre_cliente' => $nombreCliente,
                        'direccion' => $direccion,
                        'ciudad' => $ciudad,
                        'total' => round($total, 2),
                    ]);
                    $pedidoId = (int) $conexion->lastInsertId();

                    $stmt = $conexion->prepare('UPDATE productos SET stock = stock - :cantidad WHERE id = :id');
                    $detalleStmt = $conexion->prepare(
                        'INSERT INTO pedido_detalles (pedido_id, producto_id, nombre_producto, imagen_producto, cantidad, precio_unitario, subtotal)
                         VALUES (:pedido_id, :producto_id, :nombre_producto, :imagen_producto, :cantidad, :precio_unitario, :subtotal)'
                    );
                    foreach ($items as $item) {
                        $stmt->execute([
                            'cantidad' => $item['cantidad'],
                            'id' => $item['producto']['id'],
                        ]);
                        $detalleStmt->execute([
                            'pedido_id' => $pedidoId,
                            'producto_id' => $item['producto']['id'],
                            'nombre_producto' => $item['producto']['nombre'],
                            'imagen_producto' => $item['producto']['imagen'],
                            'cantidad' => $item['cantidad'],
                            'precio_unitario' => $item['producto']['precio'],
                            'subtotal' => round((float) $item['producto']['precio'] * $item['cantidad'], 2),
                        ]);
                    }
                    $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, direccion = :direccion, ciudad = :ciudad WHERE id = :id AND rol = 'cliente'");
                    $stmt->execute([
                        'nombre' => $nombreCliente,
                        'direccion' => $direccion,
                        'ciudad' => $ciudad,
                        'id' => (int) $_SESSION['usuario_id'],
                    ]);
                    $_SESSION['usuario'] = $nombreCliente;

                    $conexion->commit();
                    unset($_SESSION['carrito']);
                    $_SESSION['mensaje'] = 'Tu pedido quedó registrado. Gracias por comprar en RopaLive.';
                    header('Location: index.php');
                    exit();
                }
            } catch (RuntimeException $e) {
                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }
                $error = $e->getMessage();
            } catch (PDOException $e) {
                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }
                error_log($e->getMessage());
                $error = 'No se pudo completar el pedido. Inténtalo nuevamente.';
            }
        }
    } else {
        http_response_code(400);
        exit('La acción solicitada no es válida.');
    }
}

$lineas = [];
$datosCliente = ['nombre' => '', 'direccion' => '', 'ciudad' => ''];
if (!empty($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'cliente') {
    $stmt = $conexion->prepare("SELECT nombre, direccion, ciudad FROM usuarios WHERE id = :id AND rol = 'cliente'");
    $stmt->execute(['id' => (int) $_SESSION['usuario_id']]);
    $datosCliente = $stmt->fetch() ?: $datosCliente;
}
$gran_total = 0;
if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    $stmt = $conexion->prepare('SELECT id, nombre, imagen, precio, stock FROM productos WHERE id = :id');
    foreach ($_SESSION['carrito'] as $id => $cantidad) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || $cantidad === false || $cantidad < 1) {
            continue;
        }

        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();
        if ($producto) {
            $subtotal = (float) $producto['precio'] * $cantidad;
            $gran_total += $subtotal;
            $lineas[] = [
                'producto' => $producto,
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carrito | RopaLive</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/carrito.css">
</head>
<body>
    <div class="top-note">ENVÍOS A TODO EL PAÍS · NUEVA COLECCIÓN DISPONIBLE</div>
    <header class="site-header">
        <a class="brand" href="index.php">ROPALIVE</a>
        <nav class="site-nav"><a href="index.php#catalogo">Seguir comprando</a></nav>
        <div class="site-actions">
            <?php if (!empty($_SESSION['usuario'])) { ?>
                <a href="<?php echo es_admin() ? 'admin/index.php' : 'mi_cuenta.php'; ?>"><?php echo h($_SESSION['usuario']); ?></a>
            <?php } else { ?>
                <a href="Login.php">Iniciar sesión</a>
            <?php } ?>
        </div>
    </header>
    <main class="page-width section">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Tu selección</p>
                <h1 class="page-title">Carrito</h1>
            </div>
        </div>
        <?php if ($error !== '') { ?>
            <dialog open class="notice-modal notice-error" role="alert"><p><?php echo h($error); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog>
        <?php } ?>
        <?php if ($mensaje !== '') { ?>
            <dialog open class="notice-modal notice-success" role="status"><p><?php echo h($mensaje); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog>
        <?php } ?>

        <?php if ($lineas) { ?>
            <div class="table-scroll">
                <table class="cart-table">
                    <thead>
                        <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lineas as $linea) { ?>
                            <tr>
                                <td class="cart-product">
                                    <img src="<?php echo h(escapar_imagen($linea['producto']['imagen'])); ?>" alt="">
                                    <span><?php echo h($linea['producto']['nombre']); ?></span>
                                </td>
                                <td>$<?php echo number_format((float) $linea['producto']['precio'], 2); ?></td>
                                <td><form method="post" class="quantity-form">
                                    <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                                    <input type="hidden" name="accion" value="actualizar">
                                    <input type="hidden" name="producto_id" value="<?php echo (int) $linea['producto']['id']; ?>">
                                    <input type="number" name="cantidad" min="0" max="<?php echo (int) $linea['producto']['stock']; ?>" value="<?php echo (int) $linea['cantidad']; ?>" aria-label="Cantidad de <?php echo h($linea['producto']['nombre']); ?>">
                                    <button class="button-light button-small" type="submit">Actualizar</button>
                                </form></td>
                                <td>$<?php echo number_format($linea['subtotal'], 2); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="total-line">Total: $<?php echo number_format($gran_total, 2); ?></div>
            <div class="cart-actions">
                <a class="button button-light" href="index.php#catalogo">Seguir comprando</a>
                <div class="cart-buttons">
                    <form method="post">
                        <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                        <input type="hidden" name="accion" value="vaciar">
                        <button class="button-light" type="submit">Vaciar carrito</button>
                    </form>
                    <?php if (!empty($_SESSION['usuario_id']) && ($_SESSION['rol'] ?? '') === 'cliente') { ?>
                        <button type="submit" form="form-checkout">Finalizar compra</button>
                    <?php } ?>
                </div>
            </div>
            <?php if (empty($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'cliente') { ?>
                <p class="product-stock">Para finalizar la compra, primero inicia sesión con una cuenta de cliente.</p>
            <?php } else { ?>
                <form id="form-checkout" class="checkout-form" method="post">
                    <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                    <input type="hidden" name="accion" value="comprar">
                    <h2>Datos de entrega</h2>
                    <div class="checkout-fields">
                        <label>Nombre del cliente
                            <input name="nombre_cliente" maxlength="150" value="<?php echo h($_POST['nombre_cliente'] ?? $datosCliente['nombre']); ?>" required>
                        </label>
                        <label>Dirección
                            <input name="direccion" maxlength="255" autocomplete="street-address" value="<?php echo h($_POST['direccion'] ?? $datosCliente['direccion']); ?>" required>
                        </label>
                        <label>Ciudad
                            <input name="ciudad" maxlength="100" autocomplete="address-level2" value="<?php echo h($_POST['ciudad'] ?? $datosCliente['ciudad']); ?>" required>
                        </label>
                    </div>
                </form>
            <?php } ?>
        <?php } else { ?>
            <div class="empty-state">
                <p>Tu carrito está vacío.</p>
                <a class="button" href="index.php#catalogo">Ver colección</a>
            </div>
        <?php } ?>
    </main>
    <footer class="site-footer"><p>RopaLive · Moda para todos los días</p></footer>
</body>
</html>
