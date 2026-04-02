<?php
require 'config.php';
$pageTitle = 'Operarios';

// Handle delete
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    db()->prepare("UPDATE operadores SET activo=0 WHERE id=?")->execute([$id]);
    flash('ok', 'Operario desactivado');
    redirect('admin_operadores.php');
}

// Handle reactivate
if (isset($_GET['act'])) {
    $id = (int)$_GET['act'];
    db()->prepare("UPDATE operadores SET activo=1 WHERE id=?")->execute([$id]);
    flash('ok', 'Operario reactivado');
    redirect('admin_operadores.php');
}

$operadores = db()->query("SELECT * FROM operadores ORDER BY activo DESC, nombre")->fetchAll();
require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-people"></i> Operarios</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOp" onclick="resetForm()">
        <i class="bi bi-plus-lg"></i> Nuevo Operario
    </button>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr><th>Nombre</th><th>PIN</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($operadores as $op): ?>
        <tr class="<?= $op['activo'] ? '' : 'table-secondary' ?>">
            <td><?= sanitize($op['nombre']) ?></td>
            <td><code><?= sanitize($op['pin']) ?></code></td>
            <td>
                <?php if ($op['activo']): ?>
                    <span class="badge bg-success">Activo</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Inactivo</span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editOp(<?= $op['id'] ?>, '<?= sanitize($op['nombre']) ?>', '<?= sanitize($op['pin']) ?>')">
                    <i class="bi bi-pencil"></i>
                </button>
                <?php if ($op['activo']): ?>
                    <a href="?del=<?= $op['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Desactivar operario?')"><i class="bi bi-x-circle"></i></a>
                <?php else: ?>
                    <a href="?act=<?= $op['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i></a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($operadores)): ?>
        <tr><td colspan="4" class="text-center text-muted py-4">No hay operarios registrados</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalOp" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
    <form method="POST" action="admin_operador_save.php">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">Nuevo Operario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" id="opId">
            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="opNombre" class="form-control" required maxlength="100">
            </div>
            <div class="mb-3">
                <label class="form-label">PIN (4 digitos)</label>
                <input type="text" name="pin" id="opPin" class="form-control" required pattern="[0-9]{4}" maxlength="4" inputmode="numeric">
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
</div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').textContent = 'Nuevo Operario';
    document.getElementById('opId').value = '';
    document.getElementById('opNombre').value = '';
    document.getElementById('opPin').value = '';
}
function editOp(id, nombre, pin) {
    document.getElementById('modalTitle').textContent = 'Editar Operario';
    document.getElementById('opId').value = id;
    document.getElementById('opNombre').value = nombre;
    document.getElementById('opPin').value = pin;
    new bootstrap.Modal(document.getElementById('modalOp')).show();
}
</script>

<?php require 'footer.php'; ?>
