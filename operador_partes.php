<?php
require 'config.php';
if (!is_operador()) redirect('operador_login.php');

$pageTitle = 'Mis Partes';
$stmt = db()->prepare("SELECT p.*,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id) as total_tareas,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id AND t.cerrada=1) as tareas_cerradas
FROM partes p WHERE p.operador_id=? AND p.estado='abierto' ORDER BY p.updated_at DESC");
$stmt->execute([operador_id()]);
$partes = $stmt->fetchAll();

require 'header.php';
?>

<h4 class="mb-3"><i class="bi bi-clipboard-check"></i> Mis Partes Asignados</h4>

<?php if (empty($partes)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox display-1 text-muted"></i>
        <p class="text-muted mt-3">No tienes partes asignados en este momento</p>
    </div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($partes as $p): ?>
        <div class="col-12">
            <a href="operador_parte_ver.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                <div class="card shadow-sm task-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="text-dark mb-1">#<?= $p['id'] ?> - <?= sanitize($p['cliente_nombre'] . ' ' . $p['cliente_apellidos']) ?></h5>
                                <p class="text-muted mb-1">
                                    <i class="bi bi-car-front"></i> <?= sanitize($p['vehiculo_marca'] . ' ' . $p['vehiculo_modelo']) ?>
                                    | <strong><?= sanitize($p['matricula']) ?></strong>
                                </p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-info fs-6"><?= $p['tareas_cerradas'] ?>/<?= $p['total_tareas'] ?></span>
                                <br><small class="text-muted">tareas</small>
                            </div>
                        </div>
                        <?php $pct = $p['total_tareas'] > 0 ? round(($p['tareas_cerradas']/$p['total_tareas'])*100) : 0; ?>
                        <div class="progress mt-2" style="height:8px">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require 'footer.php'; ?>
