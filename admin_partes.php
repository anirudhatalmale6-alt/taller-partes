<?php
require 'config.php';
$pageTitle = 'Partes de Trabajo';

$filtro_estado = $_GET['estado'] ?? '';
$filtro_operador = (int)($_GET['operador'] ?? 0);

$sql = "SELECT p.*, o.nombre as operador_nombre,
        (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id) as total_tareas,
        (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id AND t.cerrada=1) as tareas_cerradas
        FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id WHERE 1=1";
$params = [];

if ($filtro_estado) {
    $sql .= " AND p.estado=?";
    $params[] = $filtro_estado;
}
if ($filtro_operador) {
    $sql .= " AND p.operador_id=?";
    $params[] = $filtro_operador;
}
$sql .= " ORDER BY p.updated_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$partes = $stmt->fetchAll();

$operadores = db()->query("SELECT id, nombre FROM operadores WHERE activo=1 ORDER BY nombre")->fetchAll();

require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-clipboard-data"></i> Partes de Trabajo</h4>
    <a href="admin_parte_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo Parte</a>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-3">
<div class="card-body py-2">
<form class="row g-2 align-items-center" method="GET">
    <div class="col-auto">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            <option value="abierto" <?= $filtro_estado==='abierto'?'selected':'' ?>>Abierto</option>
            <option value="cerrado" <?= $filtro_estado==='cerrado'?'selected':'' ?>>Cerrado</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="operador" class="form-select form-select-sm">
            <option value="">Todos los operarios</option>
            <?php foreach ($operadores as $op): ?>
                <option value="<?= $op['id'] ?>" <?= $filtro_operador==$op['id']?'selected':'' ?>><?= sanitize($op['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary">Filtrar</button>
        <a href="admin_partes.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>
</div>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Vehiculo</th>
            <th>Matricula</th>
            <th>Operario</th>
            <th>Tareas</th>
            <th>Prioridad</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($partes as $p): ?>
        <tr class="prioridad-<?= $p['prioridad'] ?? 'normal' ?>">
            <td><?= $p['id'] ?></td>
            <td><?= sanitize($p['cliente_nombre'] . ' ' . $p['cliente_apellidos']) ?></td>
            <td><?= sanitize($p['vehiculo_marca'] . ' ' . $p['vehiculo_modelo']) ?></td>
            <td><strong><?= sanitize($p['matricula']) ?></strong></td>
            <td><?= sanitize($p['operador_nombre'] ?? 'Sin asignar') ?></td>
            <td>
                <span class="badge bg-info"><?= $p['tareas_cerradas'] ?>/<?= $p['total_tareas'] ?></span>
            </td>
            <td>
                <span class="badge badge-prioridad-<?= $p['prioridad'] ?? 'normal' ?>"><?= ucfirst($p['prioridad'] ?? 'normal') ?></span>
            </td>
            <td>
                <span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span>
            </td>
            <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
            <td>
                <a href="admin_parte_ver.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                <a href="admin_parte_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                <a href="admin_parte_print.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-dark" title="Imprimir" target="_blank"><i class="bi bi-printer"></i></a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($partes)): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">No hay partes de trabajo</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<?php require 'footer.php'; ?>
