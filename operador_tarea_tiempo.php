<?php
require 'config.php';
if (!is_operador() || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('operador_login.php');

$tarea_id = (int)($_POST['tarea_id'] ?? 0);
$parte_id = (int)($_POST['parte_id'] ?? 0);
$minutos = max(1, (float)($_POST['minutos'] ?? 0));
$nota = trim($_POST['nota'] ?? '');

if (!$tarea_id || !$parte_id) {
    flash('error', 'Datos invalidos');
    redirect('operador_partes.php');
}

// Verify tarea belongs to this parte and is open
$check = db()->prepare("SELECT t.id FROM tareas t JOIN partes p ON t.parte_id=p.id WHERE t.id=? AND t.parte_id=? AND t.cerrada=0 AND p.operador_id=?");
$check->execute([$tarea_id, $parte_id, operador_id()]);
if (!$check->fetch()) {
    flash('error', 'Tarea no encontrada o cerrada');
    redirect("operador_parte_ver.php?id=$parte_id");
}

$stmt = db()->prepare("INSERT INTO registros_tiempo (tarea_id, operador_id, minutos, nota) VALUES (?,?,?,?)");
$stmt->execute([$tarea_id, operador_id(), $minutos, $nota ?: null]);

// Update tiempo_real
db()->prepare("UPDATE tareas SET tiempo_real = (SELECT COALESCE(SUM(minutos),0) FROM registros_tiempo WHERE tarea_id=?) WHERE id=?")
    ->execute([$tarea_id, $tarea_id]);

flash('ok', "Registrados $minutos minutos");
redirect("operador_parte_ver.php?id=$parte_id");
