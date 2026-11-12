<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$error = '';
$nombre = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(texto_post('nombre'));
    $email = strtolower(trim(texto_post('email')));
    $password = texto_post('password');

    if (!csrf_valido()) {
        $error = 'La sesión del formulario venció. Actualiza la página e inténtalo otra vez.';
    } elseif ($nombre === '' || strlen($nombre) > 150) {
        $error = 'Escribe un nombre de máximo 150 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = 'Escribe un email válido.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $error = 'La contraseña debe tener entre 8 y 72 caracteres.';
    } else {
        $stmt = $conexion->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Ya existe una cuenta con ese email.';
        } else {
            try {
                $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (:nombre, :email, :password, 'cliente')");
                $stmt->execute([
                    'nombre' => $nombre,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'No se pudo guardar el cliente. Verifica los datos e inténtalo nuevamente.';
            }
        }
    }
}
$adminBasePath = '../../';
$adminSection = 'clientes';
$adminTitle = 'Agregar cliente';
$adminExtraCss = true;
require __DIR__ . '/../layout_inicio.php';
?>
<section class="panel admin-form-panel">
    <?php if ($error !== '') { ?><dialog open class="notice-modal notice-error" role="alert"><p><?php echo h($error); ?></p><form method="dialog"><button class="button-small">Entendido</button></form></dialog><?php } ?>
    <form method="post">
        <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
        <div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="150" value="<?php echo h($nombre); ?>" required></div>
        <div class="form-group"><label for="email">Email</label><input id="email" type="email" name="email" maxlength="255" value="<?php echo h($email); ?>" required></div>
        <div class="form-group"><label for="password">Contraseña inicial</label><input id="password" type="password" name="password" minlength="8" maxlength="72" required></div>
        <div class="modal-actions"><a class="button button-light" href="index.php">Cancelar</a><button type="submit">Guardar cliente</button></div>
    </form>
</section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
