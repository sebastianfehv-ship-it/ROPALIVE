<?php
$adminBasePath = $adminBasePath ?? '../';
$adminSection = $adminSection ?? '';
$adminTitle = $adminTitle ?? 'Administración';
$adminExtraCss = $adminExtraCss ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo h($adminTitle); ?> | RopaLive</title>
    <link rel="stylesheet" href="<?php echo h($adminBasePath); ?>assets/css/base.css">
    <?php if ($adminExtraCss) { ?><link rel="stylesheet" href="<?php echo h($adminBasePath); ?>assets/css/formularios.css"><?php } ?>
    <link rel="stylesheet" href="<?php echo h($adminBasePath); ?>assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo h($adminBasePath); ?>assets/css/confirmaciones.css">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-header">
            <a class="brand" href="<?php echo h($adminBasePath); ?>admin/index.php"><img class="brand-logo" src="<?php echo h($adminBasePath); ?>assets/images/LOGO.png" width="120" height="50" alt="RopaLive"></a>
            <button class="sidebar-collapse-button" id="sidebar-collapse" type="button" aria-label="Plegar menú" aria-controls="admin-sidebar">
                <span aria-hidden="true">‹</span>
            </button>
        </div>
        <p>Panel de administración</p>
        <nav aria-label="Administración">
            <a class="<?php echo $adminSection === 'resumen' ? 'active' : ''; ?>" href="<?php echo h($adminBasePath); ?>admin/index.php">Resumen</a>
            <a class="<?php echo $adminSection === 'clientes' ? 'active' : ''; ?>" href="<?php echo h($adminBasePath); ?>admin/clientes/index.php">Clientes</a>
            <a class="<?php echo $adminSection === 'productos' ? 'active' : ''; ?>" href="<?php echo h($adminBasePath); ?>admin/productos/index.php">Productos</a>
            <a class="<?php echo $adminSection === 'colecciones' ? 'active' : ''; ?>" href="<?php echo h($adminBasePath); ?>admin/colecciones/index.php">Colecciones</a>
            <a class="<?php echo $adminSection === 'pedidos' ? 'active' : ''; ?>" href="<?php echo h($adminBasePath); ?>admin/pedidos/index.php">Pedidos</a>
            <a class="<?php echo $adminSection === 'cuenta' ? 'active' : ''; ?>" href="<?php echo h($adminBasePath); ?>admin/cuenta.php">Mi cuenta</a>
        </nav>
        <a class="sidebar-store" href="<?php echo h($adminBasePath); ?>index.php">Ver tienda ↗</a>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <div><p class="eyebrow">RopaLive / administración</p><h1><?php echo h($adminTitle); ?></h1></div>
            <div class="admin-topbar-actions">
                <button class="sidebar-open-button" id="sidebar-toggle" type="button" aria-label="Desplegar menú" aria-controls="admin-sidebar" aria-expanded="true">
                    <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
                </button>
                <form method="post" action="<?php echo h($adminBasePath); ?>logout.php" data-confirm-message="¿Quieres cerrar tu sesión?" data-confirm-title="Cerrar sesión"><input type="hidden" name="token_csrf" value="<?php echo h(token_csrf()); ?>"><button class="button button-light button-small" type="submit">Cerrar sesión</button></form>
            </div>
        </header>
