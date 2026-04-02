<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<?php $isOp = is_operador(); ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-wrench-adjustable"></i> <?= sanitize(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <?php if ($isOp): ?>
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="operador_partes.php">Mis Partes</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="nav-link text-info"><i class="bi bi-person-badge"></i> <?= sanitize(operador_nombre()) ?></span>
                </li>
                <li class="nav-item"><a class="nav-link" href="operador_logout.php">Salir</a></li>
            </ul>
            <?php else: ?>
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_partes.php">Partes</a></li>
                <li class="nav-item"><a class="nav-link" href="admin_operadores.php">Operarios</a></li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container-fluid px-3 px-md-4">
<?php
$flash_ok = get_flash('ok');
$flash_err = get_flash('error');
if ($flash_ok): ?>
<div class="alert alert-success alert-dismissible fade show"><?= $flash_ok ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flash_err): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= $flash_err ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
