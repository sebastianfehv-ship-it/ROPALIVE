<?php
require_once __DIR__ . '/../../includes/verificar_admin.php';

$clientes = $conexion->query("SELECT id, nombre, email, ciudad FROM usuarios WHERE rol = 'cliente' ORDER BY id DESC")->fetchAll();
$adminBasePath = '../../';
$adminSection = 'clientes';
$adminTitle = 'Clientes';
require __DIR__ . '/../layout_inicio.php';
?>
        <section class="panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Cuentas</p>
                    <h1 class="page-title">Clientes</h1>
                </div>
                <a class="button" href="agregar.php">Agregar cliente</a>
            </div>
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr><th>Nombre</th><th>Email</th><th>Ciudad</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($clientes) { ?>
                            <?php foreach ($clientes as $cliente) { ?>
                                <tr>
                                    <td><?php echo h($cliente['nombre'] ?: 'Sin nombre'); ?></td>
                                    <td><?php echo h($cliente['email']); ?></td>
                                    <td><?php echo h($cliente['ciudad'] ?: '—'); ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <a class="button button-light button-small" href="editar.php?id=<?php echo (int) $cliente['id']; ?>">Editar</a>
                                            <form method="post" action="eliminar.php" data-confirm-message="¿Quieres eliminar esta cuenta de cliente?" data-confirm-title="Eliminar cliente" data-confirm-danger="true">
                                                <input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>">
                                                <input type="hidden" name="id" value="<?php echo (int) $cliente['id']; ?>">
                                                <button class="button-danger button-small" type="submit">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="4">Todavía no hay clientes registrados.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>
<?php require __DIR__ . '/../layout_fin.php'; ?>
