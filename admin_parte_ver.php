<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);

$parte = db()->prepare("SELECT p.*, o.nombre as operador_nombre FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id WHERE p.id=?");
$parte->execute([$id]);
$parte = $parte->fetch();
if (!$parte) { flash('error', 'Parte no encontrado'); redirect('admin_partes.php'); }

$tareas = db()->prepare("SELECT t.*,
    (SELECT SUM(r.minutos) FROM registros_tiempo r WHERE r.tarea_id=t.id) as tiempo_acumulado
    FROM tareas t WHERE t.parte_id=? ORDER BY t.id");
$tareas->execute([$id]);
$tareas = $tareas->fetchAll();

$articulos = db()->prepare("SELECT * FROM articulos WHERE parte_id=? ORDER BY id");
$articulos->execute([$id]);
$articulos = $articulos->fetchAll();

$pageTitle = 'Parte #' . $id;
require 'header.php';

$total_estimado = array_sum(array_column($tareas, 'tiempo_estimado'));
$total_real = 0;
foreach ($tareas as $t) $total_real += ($t['tiempo_acumulado'] ?? $t['tiempo_real']);
$total_coste = 0;
$total_venta = 0;
foreach ($articulos as $a) {
    $total_coste += $a['precio_coste'] * $a['cantidad'];
    $total_venta += $a['precio_venta'] * $a['cantidad'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>
        <i class="bi bi-clipboard-data"></i> Parte #<?= $id ?>
        <span class="badge badge-<?= $parte['estado'] ?>"><?= ucfirst($parte['estado']) ?></span>
    </h4>
    <div class="d-flex gap-2">
        <a href="admin_parte_form.php?id=<?= $id ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Editar</a>
        <a href="admin_parte_print.php?id=<?= $id ?>" class="btn btn-outline-dark" target="_blank"><i class="bi bi-printer"></i> Imprimir</a>
        <?php if ($parte['estado'] === 'abierto'): ?>
            <a href="admin_parte_cerrar.php?id=<?= $id ?>" class="btn btn-outline-success" onclick="return confirm('Cerrar este parte?')"><i class="bi bi-check-circle"></i> Cerrar Parte</a>
        <?php else: ?>
            <a href="admin_parte_cerrar.php?id=<?= $id ?>&reopen=1" class="btn btn-outline-warning"><i class="bi bi-arrow-counterclockwise"></i> Reabrir</a>
        <?php endif; ?>
    </div>
</div>

<!-- Client/Vehicle Info -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-1">Cliente</h6>
                <p class="mb-1"><strong><?= sanitize($parte['cliente_nombre'] . ' ' . $parte['cliente_apellidos']) ?></strong></p>
                <p class="mb-0"><i class="bi bi-telephone"></i> <?= sanitize($parte['telefono'] ?: 'N/A') ?></p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted mb-1">Vehiculo</h6>
                <p class="mb-1"><?= sanitize($parte['vehiculo_marca'] . ' ' . $parte['vehiculo_modelo']) ?></p>
                <p class="mb-0"><strong>Matricula:</strong> <?= sanitize($parte['matricula']) ?></p>
            </div>
            <div class="col-md-2">
                <h6 class="text-muted mb-1">Operario</h6>
                <p class="mb-0"><?= sanitize($parte['operador_nombre'] ?? 'Sin asignar') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Tasks -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-info text-white"><i class="bi bi-list-task"></i> Tareas</div>
    <div class="table-responsive">
    <table class="table mb-0">
        <thead class="table-light">
            <tr><th>Descripcion</th><th>T. Estimado</th><th>T. Real</th><th>Estado</th><th>Observaciones</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tareas as $t): ?>
            <?php $tacum = $t['tiempo_acumulado'] ?? $t['tiempo_real']; ?>
            <tr class="<?= $t['cerrada'] ? 'table-success' : '' ?>">
                <td><?= sanitize($t['descripcion']) ?></td>
                <td><?= format_tiempo($t['tiempo_estimado']) ?></td>
                <td>
                    <?= format_tiempo($tacum) ?>
                    <?php if ($t['tiempo_estimado'] > 0 && $tacum > $t['tiempo_estimado']): ?>
                        <span class="badge bg-danger">+<?= format_tiempo($tacum - $t['tiempo_estimado']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= $t['cerrada'] ? '<span class="badge bg-success">Cerrada</span>' : '<span class="badge bg-primary">Abierta</span>' ?></td>
                <td><?= sanitize($t['observaciones'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($tareas)): ?>
            <tr><td colspan="5" class="text-muted text-center">Sin tareas</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th>TOTALES</th>
                <th><?= format_tiempo($total_estimado) ?></th>
                <th><?= format_tiempo($total_real) ?></th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
    </div>
</div>

<!-- Articles -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-warning"><i class="bi bi-box-seam"></i> Articulos</div>
    <div class="table-responsive">
    <table class="table mb-0">
        <thead class="table-light">
            <tr><th>Descripcion</th><th>Cantidad</th><th>P. Coste</th><th>P. Venta</th><th>Beneficio</th></tr>
        </thead>
        <tbody>
        <?php foreach ($articulos as $a): ?>
            <tr>
                <td><?= sanitize($a['descripcion']) ?></td>
                <td><?= (int)$a['cantidad'] ?></td>
                <td><?= format_euro($a['precio_coste']) ?></td>
                <td><?= format_euro($a['precio_venta']) ?></td>
                <td class="<?= ($a['precio_venta'] - $a['precio_coste']) >= 0 ? 'text-success' : 'text-danger' ?>">
                    <?= format_euro(($a['precio_venta'] - $a['precio_coste']) * $a['cantidad']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($articulos)): ?>
            <tr><td colspan="5" class="text-muted text-center">Sin articulos</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot class="table-light">
            <tr>
                <th colspan="2">TOTALES</th>
                <th><?= format_euro($total_coste) ?></th>
                <th><?= format_euro($total_venta) ?></th>
                <th class="<?= ($total_venta - $total_coste) >= 0 ? 'text-success' : 'text-danger' ?>"><?= format_euro($total_venta - $total_coste) ?></th>
            </tr>
        </tfoot>
    </table>
    </div>
</div>

<a href="admin_partes.php" class="btn btn-outline-secondary mb-4"><i class="bi bi-arrow-left"></i> Volver</a>

<?php require 'footer.php'; ?>
