<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="app.js" defer></script>
</head>
<body>
<?php $isOp = is_operador(); ?>

<?php if ($isOp): ?>
<!-- OPERATOR: top navbar only -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-3 no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="operador_partes.php">
            <i class="bi bi-wrench-adjustable"></i> Taller - Operario
        </a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3"><i class="bi bi-person-badge"></i> <?= sanitize(operador_nombre()) ?></span>
            <a href="operador_logout.php" class="btn btn-outline-light btn-sm">Salir</a>
        </div>
    </div>
</nav>
<div class="container-fluid px-3">
<?php else: ?>
<!-- ADMIN: sidebar layout -->
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div class="sidebar bg-dark text-white no-print" id="sidebar">
        <div class="sidebar-header p-3 border-bottom border-secondary text-center">
            <img src="logo-white.jpg" alt="Garaje 86" class="img-fluid" style="max-height:50px">
            <div class="mt-1" style="font-size:0.75rem; color:#adb5bd;">Panel de Administracion</div>
        </div>
        <nav class="sidebar-nav p-2">
            <?php
            $currentPage = basename($_SERVER['PHP_SELF']);
            function navActive($page, $current) { return strpos($current, $page) !== false ? 'active' : ''; }
            ?>
            <a href="admin_dashboard.php" class="sidebar-link <?= navActive('dashboard', $currentPage) ?>" title="Dashboard">
                <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
            </a>
            <a href="admin_kanban.php" class="sidebar-link <?= navActive('kanban', $currentPage) ?>" title="Administracion">
                <i class="bi bi-kanban"></i> <span>Administracion</span>
            </a>
            <a href="admin_partes.php" class="sidebar-link <?= navActive('parte', $currentPage) ?>" title="Partes de Trabajo">
                <i class="bi bi-clipboard-data"></i> <span>Partes de Trabajo</span>
            </a>
            <a href="admin_clientes.php" class="sidebar-link <?= navActive('cliente', $currentPage) ?>" title="Clientes">
                <i class="bi bi-people"></i> <span>Clientes</span>
            </a>
            <a href="admin_vehiculos.php" class="sidebar-link <?= navActive('vehiculo', $currentPage) ?>" title="Vehiculos">
                <i class="bi bi-car-front"></i> <span>Vehiculos</span>
            </a>
            <a href="admin_operadores.php" class="sidebar-link <?= navActive('operador', $currentPage) ?>" title="Operarios">
                <i class="bi bi-person-gear"></i> <span>Operarios</span>
            </a>
            <hr class="border-secondary my-2">
            <a href="index.php" class="sidebar-link text-secondary" title="Salir">
                <i class="bi bi-box-arrow-left"></i> <span>Salir</span>
            </a>
        </nav>
    </div>
    <!-- Sidebar collapse toggle (desktop) -->
    <button class="sidebar-collapse-btn no-print" id="sidebarCollapseBtn" title="Replegar menu">
        <i class="bi bi-chevron-left" id="collapseIcon"></i>
    </button>
    <!-- Page content -->
    <div class="flex-grow-1" id="page-content">
        <!-- Top bar mobile toggle -->
        <nav class="navbar navbar-light bg-white border-bottom d-lg-none no-print mb-3">
            <div class="container-fluid">
                <button class="btn btn-outline-dark" id="sidebarToggle"><i class="bi bi-list"></i></button>
                <span class="navbar-text fw-bold"><?= sanitize($pageTitle ?? '') ?></span>
            </div>
        </nav>
        <div class="content-area px-3 px-lg-4">
<?php endif; ?>

<?php
$flash_ok = get_flash('ok');
$flash_err = get_flash('error');
if ($flash_ok): ?>
<div class="alert alert-success alert-dismissible fade show"><?= $flash_ok ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash_err): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= $flash_err ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
