<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    http_response_code(404);
    exit('No se encontró el cliente solicitado.');
}

$stmt = $conexion->prepare("SELECT id, nombre, email FROM usuarios WHERE id = :id AND rol = 'cliente'");
$stmt->execute(['id' => $id]);
$cliente = $stmt->fetch();
if (!$cliente) {
    http_response_code(404);
    exit('No se encontró el cliente solicitado.');
}

$error = '';
$nombre = $cliente['nombre'] ?? '';
$email = $cliente['email'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!csrf_valido()) {
        $error = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.';
    } elseif (!is_string($nombre) || trim($nombre) === '' || strlen(trim($nombre)) > 150) {
        $error = 'Escribe un nombre de máximo 150 caracteres.';
    } elseif (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = 'Escribe un email válido.';
    } elseif (!is_string($password) || ($password !== '' && (strlen($password) < 8 || strlen($password) > 72))) {
        $error = 'La contraseña nueva debe tener entre 8 y 72 caracteres.';
    } else {
        $nombre = trim($nombre);
        $email = strtolower(trim($email));
        $stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = :email AND id <> :id LIMIT 1');
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ($stmt->fetch()) {
            $error = 'Ese email ya está asociado a otro cliente.';
        } elseif ($password !== '') {
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, password = :password WHERE id = :id AND rol = 'cliente'");
            $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $id,
            ]);
            header('Location: index.php');
            exit();
        } else {
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id AND rol = 'cliente'");
            $stmt->execute(['nombre' => $nombre, 'email' => $email, 'id' => $id]);
            header('Location: index.php');
            exit();
        }
    }

    if (!is_string($nombre)) {
        $nombre = '';
    }
    if (!is_string($email)) {
        $email = '';
    }
}
$adminBasePath = '../../';
$adminSection = 'clientes';
$adminTitle = 'Editar cliente';
$adminExtraCss = true;
require __DIR__ . '/../layout_inicio.php';
?>
<section class="panel admin-form-panel">
    <?php if ($error !== '') { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h($error); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
    <form method="post">
        <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
        <div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" value="<?php echo h($nombre); ?>" required></div>
        <div class="form-group"><label for="email">Email</label><input id="email" type="email" name="email" maxlength="255" value="<?php echo h($email); ?>" required></div>
        <div class="form-group"><label for="password">Cambiar contraseña (opcional)</label><input id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password"></div>
        <div class="modal-actions"><a class="button button-light" href="index.php">Cancelar</a><button type="submit">Guardar cambios</button></div>
    </form>
</section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
