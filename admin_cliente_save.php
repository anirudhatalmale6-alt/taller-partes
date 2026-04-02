<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_clientes.php');

$id = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$notas = trim($_POST['notas'] ?? '');

if (!$nombre) {
    flash('error', 'El nombre es obligatorio');
    redirect('admin_clientes.php');
}

if ($id > 0) {
    db()->prepare("UPDATE clientes SET nombre=?, apellidos=?, telefono=?, email=?, direccion=?, notas=? WHERE id=?")
        ->execute([$nombre, $apellidos, $telefono, $email, $direccion, $notas, $id]);
    flash('ok', 'Cliente actualizado');
} else {
    db()->prepare("INSERT INTO clientes (nombre, apellidos, telefono, email, direccion, notas) VALUES (?,?,?,?,?,?)")
        ->execute([$nombre, $apellidos, $telefono, $email, $direccion, $notas]);
    flash('ok', 'Cliente creado');
}

$returnTo = $_POST['return_to'] ?? 'admin_clientes.php';
redirect($returnTo);
