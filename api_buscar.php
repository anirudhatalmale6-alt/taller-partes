<?php
require 'config.php';
header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? '';
$q = trim($_GET['q'] ?? '');

if (strlen($q) < 1) { echo '[]'; exit; }

$results = [];

if ($tipo === 'clientes') {
    $stmt = db()->prepare("SELECT id, nombre, apellidos, telefono FROM clientes
        WHERE nombre LIKE ? OR apellidos LIKE ? OR telefono LIKE ? ORDER BY nombre LIMIT 10");
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);
    foreach ($stmt->fetchAll() as $c) {
        $results[] = [
            'id' => $c['id'],
            'label' => '<strong>' . sanitize($c['nombre'] . ' ' . $c['apellidos']) . '</strong>'
                . '<br><small><i class="bi bi-telephone"></i> ' . sanitize($c['telefono'] ?: 'Sin telefono') . '</small>',
            'nombre' => $c['nombre'],
            'apellidos' => $c['apellidos'],
            'telefono' => $c['telefono'],
        ];
    }
}

if ($tipo === 'vehiculos') {
    $cliente_id = (int)($_GET['cliente_id'] ?? 0);
    $sql = "SELECT v.id, v.marca, v.modelo, v.matricula, v.bastidor, v.cliente_id, c.nombre as cliente_nombre
        FROM vehiculos v JOIN clientes c ON v.cliente_id=c.id
        WHERE (v.matricula LIKE ? OR v.bastidor LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?)";
    $params = ["%$q%", "%$q%", "%$q%", "%$q%"];
    if ($cliente_id) {
        $sql .= " AND v.cliente_id=?";
        $params[] = $cliente_id;
    }
    $sql .= " ORDER BY v.matricula LIMIT 10";
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll() as $v) {
        $identifier = $v['matricula'] ?: $v['bastidor'];
        $identifierLabel = $v['matricula'] ? sanitize($v['matricula']) : sanitize($v['bastidor']) . ' <small>(Bastidor)</small>';
        $results[] = [
            'id' => $v['id'],
            'label' => '<strong>' . $identifierLabel . '</strong> - ' . sanitize($v['marca'] . ' ' . $v['modelo']) . '<br><small>' . sanitize($v['cliente_nombre']) . '</small>',
            'marca' => $v['marca'],
            'modelo' => $v['modelo'],
            'matricula' => $v['matricula'],
            'bastidor' => $v['bastidor'],
            'cliente_id' => $v['cliente_id'],
        ];
    }
}

echo json_encode($results);
