<?php
require 'config.php';
$pageTitle = 'Dashboard';

// Stats
$stats = db()->query("SELECT
    COUNT(*) as total,
    SUM(estado='abierto') as abiertos,
    SUM(estado='cerrado') as cerrados
FROM partes")->fetch();

$tareas_stats = db()->query("SELECT
    SUM(tiempo_estimado) as total_estimado,
    SUM(tiempo_real) as total_real,
    SUM(cerrada=1) as cerradas,
    SUM(cerrada=0) as pendientes
FROM tareas")->fetch();

$art_stats = db()->query("SELECT
    SUM(precio_coste * cantidad) as total_coste,
    SUM(precio_venta * cantidad) as total_venta
FROM articulos")->fetch();

// Per-operator stats
$op_stats = db()->query("SELECT o.id, o.nombre,
    (SELECT COUNT(*) FROM partes p WHERE p.operador_id=o.id AND p.estado='abierto') as partes_abiertos,
    (SELECT COUNT(*) FROM partes p WHERE p.operador_id=o.id) as partes_total,
    (SELECT COUNT(*) FROM tareas t JOIN partes p ON t.parte_id=p.id WHERE p.operador_id=o.id AND t.cerrada=0) as tareas_pendientes,
    (SELECT COUNT(*) FROM tareas t JOIN partes p ON t.parte_id=p.id WHERE p.operador_id=o.id AND t.cerrada=1) as tareas_cerradas,
    (SELECT COALESCE(SUM(t.tiempo_estimado),0) FROM tareas t JOIN partes p ON t.parte_id=p.id WHERE p.operador_id=o.id) as tiempo_estimado,
    (SELECT COALESCE(SUM(t.tiempo_real),0) FROM tareas t JOIN partes p ON t.parte_id=p.id WHERE p.operador_id=o.id) as tiempo_real
FROM operadores o WHERE o.activo=1 ORDER BY o.nombre")->fetchAll();

// Recent open partes
$recientes = db()->query("SELECT p.*, o.nombre as operador_nombre,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id AND t.cerrada=0) as tareas_pendientes
FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id
WHERE p.estado='abierto' ORDER BY p.updated_at DESC LIMIT 10")->fetchAll();

require 'header.php';
?>

<h4 class="mb-3"><i class="bi bi-speedometer2"></i> Dashboard</h4>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-stat blue shadow-sm p-3">
            <div class="text-muted small">Partes Totales</div>
            <div class="fs-3 fw-bold"><?= (int)$stats['total'] ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat green shadow-sm p-3">
            <div class="text-muted small">Abiertos</div>
            <div class="fs-3 fw-bold text-success"><?= (int)$stats['abiertos'] ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat orange shadow-sm p-3">
            <div class="text-muted small">Tareas Pendientes</div>
            <div class="fs-3 fw-bold text-warning"><?= (int)($tareas_stats['pendientes'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat red shadow-sm p-3">
            <div class="text-muted small">Beneficio Articulos</div>
            <div class="fs-4 fw-bold"><?= format_euro(($art_stats['total_venta'] ?? 0) - ($art_stats['total_coste'] ?? 0)) ?></div>
        </div>
    </div>
</div>

<!-- Time comparison -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-clock-history"></i> Tiempo Global</div>
            <div class="card-body">
                <?php
                $est = (float)($tareas_stats['total_estimado'] ?? 0);
                $real = (float)($tareas_stats['total_real'] ?? 0);
                $pct = $est > 0 ? round(($real / $est) * 100) : 0;
                $barColor = $pct <= 100 ? 'bg-success' : 'bg-danger';
                ?>
                <div class="d-flex justify-content-between mb-1">
                    <span>Estimado: <strong><?= format_tiempo($est) ?></strong></span>
                    <span>Real: <strong><?= format_tiempo($real) ?></strong></span>
                </div>
                <div class="progress" style="height:25px">
                    <div class="progress-bar <?= $barColor ?>" style="width:<?= min($pct, 100) ?>%"><?= $pct ?>%</div>
                </div>
                <small class="text-muted mt-1 d-block">
                    <?php if ($est > 0): ?>
                        <?= $pct <= 100 ? 'Dentro del tiempo estimado' : 'Excedido en ' . format_tiempo($real - $est) ?>
                    <?php else: ?>
                        Sin estimaciones registradas
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-currency-euro"></i> Rentabilidad Articulos</div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="text-muted small">Coste</div>
                        <div class="fw-bold"><?= format_euro($art_stats['total_coste'] ?? 0) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Venta</div>
                        <div class="fw-bold"><?= format_euro($art_stats['total_venta'] ?? 0) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Margen</div>
                        <?php $margen = ($art_stats['total_venta'] ?? 0) - ($art_stats['total_coste'] ?? 0); ?>
                        <div class="fw-bold <?= $margen >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_euro($margen) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Per-operator panel -->
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-people"></i> Estado por Operario</div>
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Operario</th><th>Partes Abiertos</th><th>Tareas Pendientes</th><th>Tareas Cerradas</th><th>T. Estimado</th><th>T. Real</th><th>Rendimiento</th></tr>
        </thead>
        <tbody>
        <?php foreach ($op_stats as $op): ?>
            <?php
            $opEst = (float)$op['tiempo_estimado'];
            $opReal = (float)$op['tiempo_real'];
            $opPct = $opEst > 0 ? round(($opReal / $opEst) * 100) : 0;
            ?>
            <tr>
                <td><strong><?= sanitize($op['nombre']) ?></strong></td>
                <td><span class="badge bg-primary"><?= (int)$op['partes_abiertos'] ?></span></td>
                <td><span class="badge bg-warning text-dark"><?= (int)$op['tareas_pendientes'] ?></span></td>
                <td><span class="badge bg-success"><?= (int)$op['tareas_cerradas'] ?></span></td>
                <td><?= format_tiempo($opEst) ?></td>
                <td><?= format_tiempo($opReal) ?></td>
                <td>
                    <?php if ($opEst > 0): ?>
                        <span class="badge <?= $opPct <= 100 ? 'bg-success' : 'bg-danger' ?>"><?= $opPct ?>%</span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($op_stats)): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">No hay operarios activos</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Recent open orders -->
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-clock"></i> Partes Abiertos Recientes</div>
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>#</th><th>Cliente</th><th>Vehiculo</th><th>Matricula</th><th>Operario</th><th>Tareas Pend.</th><th>Fecha</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($recientes as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= sanitize($r['cliente_nombre']) ?></td>
                <td><?= sanitize($r['vehiculo_marca'] . ' ' . $r['vehiculo_modelo']) ?></td>
                <td><strong><?= sanitize($r['matricula']) ?></strong></td>
                <td><?= sanitize($r['operador_nombre'] ?? 'Sin asignar') ?></td>
                <td><span class="badge bg-warning text-dark"><?= (int)$r['tareas_pendientes'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                <td><a href="admin_parte_ver.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recientes)): ?>
            <tr><td colspan="8" class="text-center text-muted py-3">No hay partes abiertos</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require 'footer.php'; ?>
