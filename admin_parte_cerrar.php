<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('admin_partes.php');

if (isset($_GET['reopen'])) {
    db()->prepare("UPDATE partes SET estado='abierto' WHERE id=?")->execute([$id]);
    flash('ok', 'Parte reabierto');
} else {
    db()->prepare("UPDATE partes SET estado='cerrado' WHERE id=?")->execute([$id]);
    flash('ok', 'Parte cerrado correctamente');
}
redirect("admin_parte_ver.php?id=$id");
