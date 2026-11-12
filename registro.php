<?php
require_once __DIR__ . '/includes/Funciones.php';

$error = '';
$nombre = '';
$email = '';
$direccion = '';
$ciudad = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(texto_post('nombre'));
    $email = strtolower(trim(texto_post('email')));
    $direccion = trim(texto_post('direccion'));
    $ciudad = trim(texto_post('ciudad'));
    $password = texto_post('password');

    if (!csrf_valido()) {
        $error = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.';
    } elseif ($nombre === '' || strlen($nombre) > 150) {
        $error = 'Escribe tu nombre (máximo 150 caracteres).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = 'Escribe un email válido.';
    } elseif (strlen($direccion) > 255 || strlen($ciudad) > 100) {
        $error = 'La dirección o la ciudad supera la longitud permitida.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $error = 'La contraseña debe tener entre 8 y 72 caracteres.';
    } else {
        $stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $error = 'Ese email ya tiene una cuenta. Prueba iniciar sesión.';
        } else {
            try {
                $fotos = guardar_imagenes_subidas('foto', 'users', 1);
                $stmt = $conexion->prepare(
                    "INSERT INTO usuarios (nombre, email, password, rol, direccion, ciudad, foto)
                     VALUES (:nombre, :email, :password, 'cliente', :direccion, :ciudad, :foto)"
                );
                $stmt->execute([
                    'nombre' => $nombre,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'direccion' => $direccion !== '' ? $direccion : null,
                    'ciudad' => $ciudad !== '' ? $ciudad : null,
                    'foto' => $fotos[0] ?? null,
                ]);
                header('Location: Login.php?registro=ok');
                exit();
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'No se pudo crear la cuenta. Verifica los datos e inténtalo nuevamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta | RopaLive</title>
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/formularios.css">
</head>
<body>
    <div class="top-note">ROPA URBANA · NUEVA TEMPORADA</div>
    <main class="form-page">
        <section class="form-card">
            <a class="brand" href="index.php"><img class="brand-logo" src="assets/images/LOGO.png" width="120" height="50" alt="RopaLive"></a>
            <h1 class="page-title" style="margin-top: 28px;">Crear cuenta</h1>
            <p>Regístrate para guardar tus datos y hacer pedidos.</p>
            <?php if ($error !== '') { ?>
                <dialog open class="notice-modal notice-error" role="alert"><p><?php echo h($error); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog>
            <?php } ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input id="nombre" type="text" name="nombre" maxlength="150" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" maxlength="255" autocomplete="email" value="<?php echo h($email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="direccion">Dirección (opcional)</label>
                    <input id="direccion" name="direccion" maxlength="255" autocomplete="street-address" value="<?php echo h($direccion); ?>">
                </div>
                <div class="form-group">
                    <label for="ciudad">Ciudad (opcional)</label>
                    <input id="ciudad" name="ciudad" maxlength="100" autocomplete="address-level2" value="<?php echo h($ciudad); ?>">
                </div>
                <div class="form-group">
                    <label for="foto">Foto de perfil (opcional)</label>
                    <input id="foto" type="file" name="foto[]" accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    <small>Usa entre 8 y 72 caracteres.</small>
                </div>
                <button type="submit">Crear cuenta</button>
            </form>
            <div class="form-links">
                <a href="Login.php">Ya tengo una cuenta</a>
                <a href="index.php">Volver a la tienda</a>
            </div>
        </section>
    </main>
</body>
</html>