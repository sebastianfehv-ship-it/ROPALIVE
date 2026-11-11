<?php
require_once __DIR__ . '/../includes/verificar_admin.php';
$stmt = $conexion->prepare('SELECT nombre, email FROM usuarios WHERE id = :id AND rol = :rol');
$stmt->execute(['id' => (int) $_SESSION['usuario_id'], 'rol' => 'admin']);
$cuenta = $stmt->fetch();
if (!$cuenta) { http_response_code(404); exit('No se encontró la cuenta de administración.'); }
$error = '';
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(texto_post('nombre'));
    $email = strtolower(trim(texto_post('email')));
    $password = texto_post('password');
    if (!csrf_valido()) { $error = 'La sesión del formulario venció. Actualiza la página.'; }
    elseif ($nombre === '' || strlen($nombre) > 150) { $error = 'El nombre es obligatorio y debe tener máximo 150 caracteres.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) { $error = 'Escribe un email válido.'; }
    elseif ($password !== '' && (strlen($password) < 8 || strlen($password) > 72)) { $error = 'La nueva contraseña debe tener entre 8 y 72 caracteres.'; }
    else {
        $stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = :email AND id <> :id');
        $stmt->execute(['email' => $email, 'id' => (int) $_SESSION['usuario_id']]);
        if ($stmt->fetch()) { $error = 'Ese email ya está asociado a otra cuenta.'; }
        else {
            if ($password !== '') {
                $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, password = :password WHERE id = :id AND rol = 'admin'");
                $stmt->execute(['nombre' => $nombre, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int) $_SESSION['usuario_id']]);
            } else {
                $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id AND rol = 'admin'");
                $stmt->execute(['nombre' => $nombre, 'email' => $email, 'id' => (int) $_SESSION['usuario_id']]);
            }
            $_SESSION['usuario'] = $nombre;
            $_SESSION['email'] = $email;
            $cuenta = ['nombre' => $nombre, 'email' => $email];
            $mensaje = 'Tu cuenta se actualizó correctamente.';
        }
    }
    if ($error !== '') { $cuenta = ['nombre' => $nombre, 'email' => $email]; }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi cuenta | RopaLive</title><link rel="stylesheet" href="../assets/css/base.css"><link rel="stylesheet" href="../assets/css/formularios.css"><link rel="stylesheet" href="../assets/css/admin.css"></head>
<body class="modal-page"><main class="modal-card"><a class="brand" href="index.php">ROPALIVE</a><p class="eyebrow">Administración</p><h1 class="page-title">Mi cuenta</h1>
<?php if ($error !== '') { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h($error); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?><?php if ($mensaje !== '') { ?><dialog open class="notice-modal notice-success" role="status"><p><?php echo h($mensaje); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
<form method="post"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>"><div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" value="<?php echo h($cuenta['nombre']); ?>" required></div><div class="form-group"><label for="email">Email</label><input id="email" type="email" name="email" maxlength="255" value="<?php echo h($cuenta['email']); ?>" required></div><div class="form-group"><label for="password">Nueva contraseña (opcional)</label><input id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password"></div><div class="modal-actions"><a class="button button-light" href="index.php">Volver</a><button type="submit">Guardar cambios</button></div></form></main></body></html>
