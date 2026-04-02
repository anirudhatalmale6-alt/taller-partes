<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    db()->prepare("DELETE FROM vehiculos WHERE id=?")->execute([$id]);
    flash('ok', 'Vehiculo eliminado');
}
redirect('admin_vehiculos.php');
