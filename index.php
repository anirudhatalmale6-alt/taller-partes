<?php
require 'config.php';
if (is_operador()) redirect('operador_partes.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taller - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1a1d23; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .entry-card {
            background: #fff; border-radius: 16px; padding: 50px 40px;
            text-align: center; text-decoration: none; color: #333;
            transition: transform 0.2s, box-shadow 0.2s; display: block;
            min-width: 260px;
        }
        .entry-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.3); color: #333; }
        .entry-card .icon { font-size: 4rem; }
        .entry-card h3 { margin-top: 15px; }
        .entry-card p { color: #666; margin: 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="text-center mb-5">
        <h2 class="text-white"><i class="bi bi-wrench-adjustable"></i> Taller - Partes de Trabajo</h2>
    </div>
    <div class="row justify-content-center g-4">
        <div class="col-md-5 col-lg-4">
            <a href="admin_dashboard.php" class="entry-card">
                <div class="icon text-primary"><i class="bi bi-gear-wide-connected"></i></div>
                <h3>Administrador</h3>
                <p>Gestionar partes, clientes, vehiculos y operarios</p>
            </a>
        </div>
        <div class="col-md-5 col-lg-4">
            <a href="operador_login.php" class="entry-card">
                <div class="icon text-success"><i class="bi bi-person-workspace"></i></div>
                <h3>Operario</h3>
                <p>Acceder con PIN para trabajar en partes</p>
            </a>
        </div>
    </div>
</div>
</body>
</html>
