<?php
require_once __DIR__ . '/includes/Funciones.php';

$error = '';
$mensaje = isset($_GET['registro']) && $_GET['registro'] === 'ok'
    ? 'Tu cuenta quedó creada. Ya puedes iniciar sesión.'
    : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim(texto_post('email')));
    $password = texto_post('password');

    if (!csrf_valido()) {
        $error = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || $password === '') {
        $error = 'Escribe un email y una contraseña válidos.';
    } else {
        $stmt = $conexion->prepare('SELECT id, nombre, email, password, rol, foto FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_valido($password, (string) $usuario['password'])) {
            if (password_en_texto_plano((string) $usuario['password']) || password_needs_rehash((string) $usuario['password'], PASSWORD_DEFAULT)) {
                $stmt = $conexion->prepare('UPDATE usuarios SET password = :password WHERE id = :id');
                $stmt->execute([
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $usuario['id'],
                ]);
            }

            session_regenerate_id(true);
            $_SESSION['usuario'] = $usuario['nombre'];
            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['rol'] = $usuario['rol'];
            $_SESSION['foto'] = $usuario['foto'];
            $_SESSION['es_admin'] = $usuario['rol'] === 'admin';
            if ($usuario['rol'] === 'admin') {
                header('Location: admin/index.php');
                exit();
            }

            $destino = ($_SESSION['despues_login'] ?? '') === 'carrito.php' ? 'carrito.php' : 'index.php';
            unset($_SESSION['despues_login']);
            header('Location: ' . $destino);
            exit();
        }

        $error = 'Email o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | RopaLive</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/formularios.css">
</head>
<body>
    <div class="top-note">ROPA URBANA · NUEVA TEMPORADA</div>
    <main class="form-page">
        <section class="form-card">
            <a class="brand" href="index.php">ROPALIVE</a>
            <h1 class="page-title">Iniciar sesión</h1>
            <p>Entra a tu cuenta para continuar con tu compra.</p>
            <?php if ($mensaje !== '') { ?>
                <div class="notice notice-success" role="status"><?php echo h($mensaje); ?></div>
            <?php } ?>
            <?php if ($error !== '') { ?>
                <dialog open class="notice-modal notice-error" role="alert"><p><?php echo h($error); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog>
            <?php } ?>
            <form method="post">
                <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" maxlength="255" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input id="password" type="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit">Entrar</button>
            </form>
            <div class="form-links">
                <a href="registro.php">Crear una cuenta</a>
                <a href="index.php">Volver a la tienda</a>
            </div>
        </section>
    </main>
</body>
</html>