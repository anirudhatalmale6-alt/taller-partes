<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_operadores.php');

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$pin = trim($_POST['pin'] ?? '');

if (!$nombre || !preg_match('/^\d{4}$/', $pin)) {
    flash('error', 'Nombre y PIN (4 digitos) son obligatorios');
    redirect('admin_operadores.php');
}

// Check PIN uniqueness
$check = db()->prepare("SELECT id FROM operadores WHERE pin=? AND id!=?");
$check->execute([$pin, $id]);
if ($check->fetch()) {
    flash('error', 'Ese PIN ya esta en uso por otro operario');
    redirect('admin_operadores.php');
}

if ($id > 0) {
    $stmt = db()->prepare("UPDATE operadores SET nombre=?, pin=? WHERE id=?");
    $stmt->execute([$nombre, $pin, $id]);
    flash('ok', 'Operario actualizado');
} else {
    $stmt = db()->prepare("INSERT INTO operadores (nombre, pin) VALUES (?, ?)");
    $stmt->execute([$nombre, $pin]);
    flash('ok', 'Operario creado');
}

redirect('admin_operadores.php');
