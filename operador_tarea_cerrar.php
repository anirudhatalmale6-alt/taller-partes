<?php
require 'config.php';
if (!is_operador() || $_SERVER['REQUEST_METHOD'] !== 'POST') redirect('operador_login.php');

$tarea_id = (int)($_POST['tarea_id'] ?? 0);
$parte_id = (int)($_POST['parte_id'] ?? 0);

$check = db()->prepare("SELECT t.id FROM tareas t JOIN partes p ON t.parte_id=p.id WHERE t.id=? AND t.parte_id=? AND t.cerrada=0 AND p.operador_id=?");
$check->execute([$tarea_id, $parte_id, operador_id()]);
if (!$check->fetch()) {
    flash('error', 'Tarea no encontrada o ya cerrada');
    redirect("operador_parte_ver.php?id=$parte_id");
}

$pdo = db();
$pdo->beginTransaction();

// Sum all time entries
$pdo->prepare("UPDATE tareas SET tiempo_real = (SELECT COALESCE(SUM(minutos),0) FROM registros_tiempo WHERE tarea_id=?), cerrada=1 WHERE id=?")
    ->execute([$tarea_id, $tarea_id]);

$pdo->commit();

flash('ok', 'Tarea cerrada correctamente');
redirect("operador_parte_ver.php?id=$parte_id");
