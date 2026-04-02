<?php
require 'config.php';
if (!is_operador()) redirect('operador_login.php');

$id = (int)($_GET['id'] ?? 0);
$parte = db()->prepare("SELECT p.*, o.nombre as operador_nombre FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id WHERE p.id=? AND p.operador_id=?");
$parte->execute([$id, operador_id()]);
$parte = $parte->fetch();
if (!$parte) { flash('error', 'Parte no encontrado o no asignado'); redirect('operador_partes.php'); }

$tareas = db()->prepare("SELECT t.*,
    (SELECT SUM(r.minutos) FROM registros_tiempo r WHERE r.tarea_id=t.id) as tiempo_acumulado,
    (SELECT GROUP_CONCAT(r.minutos || ' min' || CASE WHEN r.nota IS NOT NULL AND r.nota!='' THEN ' - ' || r.nota ELSE '' END, '||') FROM registros_tiempo r WHERE r.tarea_id=t.id ORDER BY r.created_at) as registros_detalle
FROM tareas t WHERE t.parte_id=? ORDER BY t.cerrada ASC, t.id ASC");
$tareas->execute([$id]);
$tareas = $tareas->fetchAll();

$articulos = db()->prepare("SELECT * FROM articulos WHERE parte_id=? ORDER BY id");
$articulos->execute([$id]);
$articulos = $articulos->fetchAll();

$pageTitle = 'Parte #' . $id;
require 'header.php';
?>

<div class="mb-3">
    <a href="operador_partes.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<!-- Header info -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <strong class="text-primary">#<?= $id ?></strong> -
        <?= sanitize($parte['cliente_nombre'] . ' ' . $parte['cliente_apellidos']) ?> |
        <i class="bi bi-car-front"></i> <?= sanitize($parte['vehiculo_marca'] . ' ' . $parte['vehiculo_modelo']) ?> |
        <strong><?= sanitize($parte['matricula']) ?></strong>
    </div>
</div>

<!-- Tasks -->
<h5 class="mb-2"><i class="bi bi-list-task"></i> Tareas</h5>

<?php foreach ($tareas as $t): ?>
    <?php $tacum = (float)($t['tiempo_acumulado'] ?? $t['tiempo_real']); ?>
    <div class="card shadow-sm task-card <?= $t['cerrada'] ? 'cerrada' : '' ?> mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="mb-1">
                        <?php if ($t['cerrada']): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        <?php else: ?>
                            <i class="bi bi-circle text-primary"></i>
                        <?php endif; ?>
                        <?= sanitize($t['descripcion']) ?>
                    </h6>
                    <small class="text-muted">
                        Estimado: <?= format_tiempo($t['tiempo_estimado']) ?> |
                        Real: <strong><?= format_tiempo($tacum) ?></strong>
                        <?php if ($t['tiempo_estimado'] > 0 && $tacum > $t['tiempo_estimado']): ?>
                            <span class="text-danger">(+<?= format_tiempo($tacum - $t['tiempo_estimado']) ?>)</span>
                        <?php endif; ?>
                    </small>
                </div>
                <?php if (!$t['cerrada']): ?>
                    <span class="badge bg-primary">Abierta</span>
                <?php else: ?>
                    <span class="badge bg-success">Cerrada</span>
                <?php endif; ?>
            </div>

            <!-- Time entries -->
            <?php if ($t['registros_detalle']): ?>
                <div class="mt-2">
                    <small class="text-muted">Registros de tiempo:</small>
                    <ul class="list-unstyled mb-0 small">
                    <?php foreach (explode('||', $t['registros_detalle']) as $reg): ?>
                        <li><i class="bi bi-clock"></i> <?= sanitize($reg) ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!$t['cerrada']): ?>
            <!-- Add time entry -->
            <form method="POST" action="operador_tarea_tiempo.php" class="mt-2 border-top pt-2">
                <input type="hidden" name="tarea_id" value="<?= $t['id'] ?>">
                <input type="hidden" name="parte_id" value="<?= $id ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-4">
                        <label class="form-label small">Minutos</label>
                        <input type="number" name="minutos" class="form-control form-control-sm" min="1" required placeholder="Min">
                    </div>
                    <div class="col-5">
                        <label class="form-label small">Nota (opcional)</label>
                        <input type="text" name="nota" class="form-control form-control-sm" placeholder="Nota">
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i> Tiempo</button>
                    </div>
                </div>
            </form>

            <!-- Observations -->
            <form method="POST" action="operador_tarea_obs.php" class="mt-2">
                <input type="hidden" name="tarea_id" value="<?= $t['id'] ?>">
                <input type="hidden" name="parte_id" value="<?= $id ?>">
                <div class="input-group input-group-sm">
                    <input type="text" name="observaciones" class="form-control" placeholder="Observaciones..." value="<?= sanitize($t['observaciones'] ?? '') ?>">
                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-chat-dots"></i></button>
                </div>
            </form>

            <!-- Close task -->
            <form method="POST" action="operador_tarea_cerrar.php" class="mt-2">
                <input type="hidden" name="tarea_id" value="<?= $t['id'] ?>">
                <input type="hidden" name="parte_id" value="<?= $id ?>">
                <button type="submit" class="btn btn-sm btn-success w-100" onclick="return confirm('Cerrar esta tarea? Se sumara todo el tiempo registrado.')">
                    <i class="bi bi-check-lg"></i> Cerrar Tarea
                </button>
            </form>
            <?php else: ?>
                <?php if ($t['observaciones']): ?>
                    <div class="mt-2 small"><i class="bi bi-chat-dots"></i> <?= sanitize($t['observaciones']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>

<!-- Add new task -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white"><i class="bi bi-plus-circle"></i> Nueva Tarea</div>
    <div class="card-body">
        <form method="POST" action="operador_tarea_add.php">
            <input type="hidden" name="parte_id" value="<?= $id ?>">
            <div class="row g-2">
                <div class="col-8">
                    <input type="text" name="descripcion" class="form-control" required placeholder="Descripcion de la tarea">
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-info text-white w-100"><i class="bi bi-plus"></i> Agregar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Articles -->
<h5 class="mb-2"><i class="bi bi-box-seam"></i> Articulos</h5>

<?php if (!empty($articulos)): ?>
    <div class="table-responsive mb-3">
    <table class="table table-sm">
        <thead class="table-light"><tr><th>Descripcion</th><th>Cant.</th><th>P. Venta</th></tr></thead>
        <tbody>
        <?php foreach ($articulos as $a): ?>
            <tr>
                <td><?= sanitize($a['descripcion']) ?></td>
                <td><?= (int)$a['cantidad'] ?></td>
                <td><?= format_euro($a['precio_venta']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<!-- Add article -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning"><i class="bi bi-plus-circle"></i> Nuevo Articulo</div>
    <div class="card-body">
        <form method="POST" action="operador_articulo_add.php">
            <input type="hidden" name="parte_id" value="<?= $id ?>">
            <div class="row g-2">
                <div class="col-12 col-md-4">
                    <input type="text" name="descripcion" class="form-control form-control-sm" required placeholder="Descripcion">
                </div>
                <div class="col-4 col-md-2">
                    <input type="number" name="cantidad" class="form-control form-control-sm" min="1" value="1" placeholder="Cant.">
                </div>
                <div class="col-4 col-md-2">
                    <input type="number" name="precio_coste" class="form-control form-control-sm" min="0" step="0.01" placeholder="Coste">
                </div>
                <div class="col-4 col-md-2">
                    <input type="number" name="precio_venta" class="form-control form-control-sm" min="0" step="0.01" placeholder="Venta">
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-warning btn-sm w-100"><i class="bi bi-plus"></i> Agregar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require 'footer.php'; ?>
