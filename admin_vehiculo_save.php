<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_vehiculos.php');

$id = (int)($_POST['id'] ?? 0);
$cliente_id = (int)($_POST['cliente_id'] ?? 0);
$marca = trim($_POST['marca'] ?? '');
$modelo = trim($_POST['modelo'] ?? '');
$matricula = trim($_POST['matricula'] ?? '');
$anio = trim($_POST['anio'] ?? '');
$color = trim($_POST['color'] ?? '');
$vin = trim($_POST['vin'] ?? '');

if (!$cliente_id || !$matricula) {
    flash('error', 'Cliente y matricula son obligatorios');
    redirect('admin_vehiculos.php');
}

if ($id > 0) {
    db()->prepare("UPDATE vehiculos SET cliente_id=?, marca=?, modelo=?, matricula=?, anio=?, color=?, vin=? WHERE id=?")
        ->execute([$cliente_id, $marca, $modelo, $matricula, $anio, $color, $vin, $id]);
    flash('ok', 'Vehiculo actualizado');
} else {
    db()->prepare("INSERT INTO vehiculos (cliente_id, marca, modelo, matricula, anio, color, vin) VALUES (?,?,?,?,?,?,?)")
        ->execute([$cliente_id, $marca, $modelo, $matricula, $anio, $color, $vin]);
    flash('ok', 'Vehiculo creado');
}

redirect('admin_vehiculos.php');
