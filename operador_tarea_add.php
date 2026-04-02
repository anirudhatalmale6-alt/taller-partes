<?php
require 'config.php';
if (!is_operador() || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('operador_login.php');

$parte_id = (int)($_POST['parte_id'] ?? 0);
$desc = trim($_POST['descripcion'] ?? '');

if (!$desc) {
    flash('error', 'La descripcion es obligatoria');
    redirect("operador_parte_ver.php?id=$parte_id");
}

// Verify parte belongs to this operator
$check = db()->prepare("SELECT id FROM partes WHERE id=? AND operador_id=? AND estado='abierto'");
$check->execute([$parte_id, operador_id()]);
if (!$check->fetch()) {
    flash('error', 'Parte no encontrado');
    redirect('operador_partes.php');
}

db()->prepare("INSERT INTO tareas (parte_id, descripcion) VALUES (?,?)")->execute([$parte_id, $desc]);
flash('ok', 'Tarea agregada');
redirect("operador_parte_ver.php?id=$parte_id");
