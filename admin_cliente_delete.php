<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    db()->prepare("DELETE FROM clientes WHERE id=?")->execute([$id]);
    flash('ok', 'Cliente eliminado');
}
redirect('admin_clientes.php');
