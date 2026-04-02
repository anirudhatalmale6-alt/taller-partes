<?php
require 'config.php';
$pageTitle = 'Administracion - Panel de Taller';

$operadores = db()->query("SELECT id, nombre FROM operadores WHERE activo=1 ORDER BY nombre")->fetchAll();

// Get open partes grouped by operator
$partes = db()->query("SELECT p.*, o.nombre as operador_nombre,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id) as total_tareas,
    (SELECT COUNT(*) FROM tareas t WHERE t.parte_id=p.id AND t.cerrada=1) as tareas_cerradas
    FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id
    WHERE p.estado='abierto'
    ORDER BY FIELD(p.prioridad, 'alta','normal','baja'), p.created_at DESC")->fetchAll();

// Group by operator
$byOperador = ['sin_asignar' => []];
foreach ($operadores as $op) $byOperador[$op['id']] = [];
foreach ($partes as $p) {
    $key = $p['operador_id'] ?: 'sin_asignar';
    if (!isset($byOperador[$key])) $byOperador[$key] = [];
    $byOperador[$key][] = $p;
}

require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-kanban"></i> Administracion del Taller</h4>
    <a href="admin_parte_form.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuevo Parte</a>
</div>

<p class="text-muted small mb-3">Arrastra las tarjetas entre columnas para reasignar operarios. Los colores indican la prioridad.</p>

<div class="kanban-board" id="kanbanBoard">

    <!-- Sin asignar column -->
    <div class="kanban-column">
        <div class="kanban-column-header">
            <i class="bi bi-inbox"></i> Sin Asignar
            <span class="badge bg-light text-dark ms-1"><?= count($byOperador['sin_asignar']) ?></span>
        </div>
        <div class="kanban-cards" data-operador="0">
            <?php foreach ($byOperador['sin_asignar'] as $p): ?>
                <?= renderCard($p) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Operator columns -->
    <?php foreach ($operadores as $op): ?>
    <div class="kanban-column">
        <div class="kanban-column-header">
            <i class="bi bi-person-fill"></i> <?= sanitize($op['nombre']) ?>
            <span class="badge bg-light text-dark ms-1"><?= count($byOperador[$op['id']] ?? []) ?></span>
        </div>
        <div class="kanban-cards" data-operador="<?= $op['id'] ?>">
            <?php foreach ($byOperador[$op['id']] ?? [] as $p): ?>
                <?= renderCard($p) ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<?php
function renderCard($p) {
    $pct = $p['total_tareas'] > 0 ? round(($p['tareas_cerradas']/$p['total_tareas'])*100) : 0;
    $prioClass = 'prioridad-' . ($p['prioridad'] ?? 'normal');
    $barColor = $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-info' : 'bg-primary');
    ob_start();
    ?>
    <div class="kanban-card <?= $prioClass ?>" draggable="true" data-parte-id="<?= $p['id'] ?>">
        <div class="d-flex justify-content-between align-items-start">
            <div class="card-title">
                #<?= $p['id'] ?> - <?= sanitize($p['cliente_nombre']) ?>
            </div>
            <span class="badge badge-prioridad-<?= $p['prioridad'] ?? 'normal' ?>" style="font-size:0.65rem"><?= ucfirst($p['prioridad'] ?? 'normal') ?></span>
        </div>
        <div class="card-sub">
            <i class="bi bi-car-front"></i> <?= sanitize($p['vehiculo_marca'] . ' ' . $p['vehiculo_modelo']) ?> - <strong><?= sanitize($p['matricula'] ?: $p['bastidor']) ?></strong>
        </div>
        <div class="card-sub mt-1">
            <i class="bi bi-list-check"></i> <?= $p['tareas_cerradas'] ?>/<?= $p['total_tareas'] ?> tareas
        </div>
        <div class="progress">
            <div class="progress-bar <?= $barColor ?>" style="width:<?= $pct ?>%"></div>
        </div>
        <div class="d-flex justify-content-end mt-1">
            <a href="admin_parte_ver.php?id=<?= $p['id'] ?>" class="text-primary" style="font-size:0.75rem" onclick="event.stopPropagation()"><i class="bi bi-eye"></i> Ver</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<!-- AJAX endpoint for moving cards -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var board = document.getElementById('kanbanBoard');
    var draggedCard = null;

    // Drag start
    board.addEventListener('dragstart', function(e) {
        if (!e.target.classList.contains('kanban-card')) return;
        draggedCard = e.target;
        draggedCard.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', e.target.dataset.parteId);
    });

    // Drag end
    board.addEventListener('dragend', function(e) {
        if (draggedCard) draggedCard.classList.remove('dragging');
        draggedCard = null;
        document.querySelectorAll('.drag-over').forEach(function(el) { el.classList.remove('drag-over'); });
    });

    // Allow drop on kanban-cards containers
    board.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var target = e.target.closest('.kanban-cards');
        if (target) {
            document.querySelectorAll('.drag-over').forEach(function(el) { el.classList.remove('drag-over'); });
            // Find closest card to insert before
            var afterElement = getDragAfterElement(target, e.clientY);
            if (afterElement) {
                afterElement.classList.add('drag-over');
            }
        }
    });

    // Drop
    board.addEventListener('drop', function(e) {
        e.preventDefault();
        var target = e.target.closest('.kanban-cards');
        if (!target || !draggedCard) return;

        var afterElement = getDragAfterElement(target, e.clientY);
        if (afterElement) {
            target.insertBefore(draggedCard, afterElement);
        } else {
            target.appendChild(draggedCard);
        }

        var parteId = draggedCard.dataset.parteId;
        var newOperadorId = target.dataset.operador;

        // Update via AJAX
        fetch('api_kanban_move.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'parte_id=' + parteId + '&operador_id=' + newOperadorId
        }).then(function(r) { return r.json(); })
          .then(function(data) {
            if (!data.ok) alert('Error al mover: ' + data.error);
            // Update column counts
            updateColumnCounts();
          });
    });

    function getDragAfterElement(container, y) {
        var elements = Array.from(container.querySelectorAll('.kanban-card:not(.dragging)'));
        var closest = null;
        var closestOffset = Number.POSITIVE_INFINITY;
        elements.forEach(function(child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > -closestOffset) {
                closestOffset = -offset;
                closest = child;
            }
        });
        return closest;
    }

    function updateColumnCounts() {
        document.querySelectorAll('.kanban-column').forEach(function(col) {
            var cards = col.querySelectorAll('.kanban-card').length;
            var badge = col.querySelector('.kanban-column-header .badge');
            if (badge) badge.textContent = cards;
        });
    }
});
</script>

<?php require 'footer.php'; ?>
