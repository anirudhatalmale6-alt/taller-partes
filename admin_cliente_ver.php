<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$cliente = db()->prepare("SELECT * FROM clientes WHERE id=?");
$cliente->execute([$id]);
$cliente = $cliente->fetch();
if (!$cliente) { flash('error', 'Cliente no encontrado'); redirect('admin_clientes.php'); }

$vehiculos = db()->prepare("SELECT * FROM vehiculos WHERE cliente_id=? ORDER BY matricula");
$vehiculos->execute([$id]);
$vehiculos = $vehiculos->fetchAll();

$partes = db()->prepare("SELECT p.*, o.nombre as operador_nombre,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id) as total_tareas
    FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id
    WHERE p.cliente_id=? ORDER BY p.created_at DESC");
$partes->execute([$id]);
$partes = $partes->fetchAll();

$pageTitle = 'Cliente: ' . $cliente['nombre'];
require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-person"></i> <?= sanitize($cliente['nombre'] . ' ' . $cliente['apellidos']) ?></h4>
    <a href="admin_clientes.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-person-lines-fill"></i> Datos del Cliente</div>
            <div class="card-body">
                <p><strong>Nombre:</strong> <?= sanitize($cliente['nombre'] . ' ' . $cliente['apellidos']) ?></p>
                <p><strong>Telefono:</strong> <?= sanitize($cliente['telefono'] ?: 'N/A') ?></p>
                <p><strong>Email:</strong> <?= sanitize($cliente['email'] ?: 'N/A') ?></p>
                <p><strong>Direccion:</strong> <?= sanitize($cliente['direccion'] ?: 'N/A') ?></p>
                <?php if ($cliente['notas']): ?>
                    <p><strong>Notas:</strong> <?= sanitize($cliente['notas']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-car-front"></i> Vehiculos</span>
                <a href="admin_vehiculos.php?nuevo=1&cliente_id=<?= $id ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Nuevo</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($vehiculos)): ?>
                    <p class="text-muted text-center py-3">Sin vehiculos registrados</p>
                <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Marca/Modelo</th><th>Matricula</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($vehiculos as $v): ?>
                        <tr>
                            <td><?= sanitize($v['marca'] . ' ' . $v['modelo']) ?></td>
                            <td><strong><?= sanitize($v['matricula']) ?></strong></td>
                            <td><a href="admin_vehiculo_ver.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Partes history -->
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-clipboard-data"></i> Historico de Partes</div>
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>#</th><th>Vehiculo</th><th>Operario</th><th>Tareas</th><th>Estado</th><th>Fecha</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($partes as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= sanitize($p['vehiculo_marca'] . ' ' . $p['vehiculo_modelo'] . ' - ' . $p['matricula']) ?></td>
                <td><?= sanitize($p['operador_nombre'] ?? 'Sin asignar') ?></td>
                <td><span class="badge bg-info"><?= (int)$p['total_tareas'] ?></span></td>
                <td><span class="badge badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                <td><a href="admin_parte_ver.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($partes)): ?>
            <tr><td colspan="7" class="text-muted text-center py-3">Sin partes</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require 'footer.php'; ?>
