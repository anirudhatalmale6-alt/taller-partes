<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$vehiculo = db()->prepare("SELECT v.*, c.nombre as cliente_nombre, c.apellidos as cliente_apellidos, c.telefono as cliente_telefono
    FROM vehiculos v JOIN clientes c ON v.cliente_id=c.id WHERE v.id=?");
$vehiculo->execute([$id]);
$vehiculo = $vehiculo->fetch();
if (!$vehiculo) { flash('error', 'Vehiculo no encontrado'); redirect('admin_vehiculos.php'); }

$partes = db()->prepare("SELECT p.*, o.nombre as operador_nombre,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id) as total_tareas,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id AND t.cerrada=1) as tareas_cerradas
    FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id
    WHERE p.vehiculo_id=? ORDER BY p.created_at DESC");
$partes->execute([$id]);
$partes = $partes->fetchAll();

$pageTitle = 'Vehiculo: ' . $vehiculo['matricula'];
require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-car-front"></i> <?= sanitize($vehiculo['marca'] . ' ' . $vehiculo['modelo']) ?> - <strong><?= sanitize($vehiculo['matricula']) ?></strong></h4>
    <a href="admin_vehiculos.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-car-front"></i> Datos del Vehiculo</div>
            <div class="card-body">
                <p><strong>Marca:</strong> <?= sanitize($vehiculo['marca']) ?></p>
                <p><strong>Modelo:</strong> <?= sanitize($vehiculo['modelo']) ?></p>
                <p><strong>Matricula:</strong> <?= sanitize($vehiculo['matricula']) ?></p>
                <?php if ($vehiculo['anio']): ?><p><strong>Ano:</strong> <?= sanitize($vehiculo['anio']) ?></p><?php endif; ?>
                <?php if ($vehiculo['color']): ?><p><strong>Color:</strong> <?= sanitize($vehiculo['color']) ?></p><?php endif; ?>
                <?php if ($vehiculo['vin']): ?><p><strong>VIN:</strong> <?= sanitize($vehiculo['vin']) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-person"></i> Propietario</div>
            <div class="card-body">
                <p><strong><?= sanitize($vehiculo['cliente_nombre'] . ' ' . $vehiculo['cliente_apellidos']) ?></strong></p>
                <p><i class="bi bi-telephone"></i> <?= sanitize($vehiculo['cliente_telefono'] ?: 'N/A') ?></p>
                <a href="admin_cliente_ver.php?id=<?= $vehiculo['cliente_id'] ?>" class="btn btn-sm btn-outline-primary">Ver ficha cliente</a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-clipboard-data"></i> Historico de Partes</div>
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>#</th><th>Operario</th><th>Tareas</th><th>Estado</th><th>Fecha</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($partes as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= sanitize($p['operador_nombre'] ?? 'Sin asignar') ?></td>
                <td><span class="badge bg-info"><?= $p['tareas_cerradas'] ?>/<?= $p['total_tareas'] ?></span></td>
                <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                <td><a href="admin_parte_ver.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($partes)): ?>
            <tr><td colspan="6" class="text-muted text-center py-3">Sin partes para este vehiculo</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require 'footer.php'; ?>
