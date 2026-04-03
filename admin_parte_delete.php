<?php
require 'config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    flash('error', 'Parte no especificado');
    redirect('admin_partes.php');
}

// CASCADE will delete tareas, articulos, registros_tiempo
$stmt = db()->prepare("DELETE FROM partes WHERE id=?");
$stmt->execute([$id]);

if ($stmt->rowCount() > 0) {
    flash('ok', 'Parte #' . $id . ' borrado correctamente');
} else {
    flash('error', 'Parte no encontrado');
}

redirect('admin_partes.php');
