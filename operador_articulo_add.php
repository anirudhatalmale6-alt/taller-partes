<?php
require 'config.php';
if (!is_operador() || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('operador_login.php');

$parte_id = (int)($_POST['parte_id'] ?? 0);
$desc = trim($_POST['descripcion'] ?? '');
$cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
$coste = max(0, (float)($_POST['precio_coste'] ?? 0));
$venta = max(0, (float)($_POST['precio_venta'] ?? 0));

if (!$desc) {
    flash('error', 'La descripcion del articulo es obligatoria');
    redirect("operador_parte_ver.php?id=$parte_id");
}

$check = db()->prepare("SELECT id FROM partes WHERE id=? AND operador_id=? AND estado='abierto'");
$check->execute([$parte_id, operador_id()]);
if (!$check->fetch()) {
    flash('error', 'Parte no encontrado');
    redirect('operador_partes.php');
}

db()->prepare("INSERT INTO articulos (parte_id, descripcion, cantidad, precio_coste, precio_venta) VALUES (?,?,?,?,?)")
    ->execute([$parte_id, $desc, $cantidad, $coste, $venta]);
flash('ok', 'Articulo agregado');
redirect("operador_parte_ver.php?id=$parte_id");
