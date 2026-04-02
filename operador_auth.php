<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('operador_login.php');

$pin = trim($_POST['pin'] ?? '');
if (!preg_match('/^\d{4}$/', $pin)) {
    flash('error', 'PIN invalido');
    redirect('operador_login.php');
}

$stmt = db()->prepare("SELECT id, nombre FROM operadores WHERE pin=? AND activo=1");
$stmt->execute([$pin]);
$op = $stmt->fetch();

if (!$op) {
    flash('error', 'PIN incorrecto o operario inactivo');
    redirect('operador_login.php');
}

$_SESSION['operador_id'] = $op['id'];
$_SESSION['operador_nombre'] = $op['nombre'];
redirect('operador_partes.php');
