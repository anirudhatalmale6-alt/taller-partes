<?php
require 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$parte_id = (int)($_POST['parte_id'] ?? 0);
$operador_id = (int)($_POST['operador_id'] ?? 0);

if (!$parte_id) {
    echo json_encode(['ok' => false, 'error' => 'parte_id required']);
    exit;
}

$op_value = $operador_id > 0 ? $operador_id : null;

db()->prepare("UPDATE partes SET operador_id=? WHERE id=?")->execute([$op_value, $parte_id]);

echo json_encode(['ok' => true]);
