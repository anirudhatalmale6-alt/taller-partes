<?php
require 'config.php';
if (!is_operador() || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('operador_login.php');

$tarea_id = (int)($_POST['tarea_id'] ?? 0);
$parte_id = (int)($_POST['parte_id'] ?? 0);
$obs = trim($_POST['observaciones'] ?? '');

$check = db()->prepare("SELECT t.id FROM tareas t JOIN partes p ON t.parte_id=p.id WHERE t.id=? AND t.parte_id=? AND p.operador_id=?");
$check->execute([$tarea_id, $parte_id, operador_id()]);
if (!$check->fetch()) {
    flash('error', 'Tarea no encontrada');
    redirect("operador_parte_ver.php?id=$parte_id");
}

db()->prepare("UPDATE tareas SET observaciones=? WHERE id=?")->execute([$obs, $tarea_id]);
flash('ok', 'Observacion guardada');
redirect("operador_parte_ver.php?id=$parte_id");
