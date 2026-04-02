<?php
require 'config.php';
$pageTitle = 'Clientes';

$buscar = trim($_GET['buscar'] ?? '');
$sql = "SELECT c.*, (SELECT COUNT(*) FROM vehiculos v WHERE v.cliente_id=c.id) as total_vehiculos,
        (SELECT COUNT(*) FROM partes p WHERE p.cliente_id=c.id) as total_partes
        FROM clientes c WHERE 1=1";
$params = [];
if ($buscar) {
    $sql .= " AND (c.nombre LIKE ? OR c.apellidos LIKE ? OR c.telefono LIKE ?)";
    $params = ["%$buscar%", "%$buscar%", "%$buscar%"];
}
$sql .= " ORDER BY c.nombre, c.apellidos";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-people"></i> Clientes</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="resetClienteForm()">
        <i class="bi bi-plus-lg"></i> Nuevo Cliente
    </button>
</div>

<div class="card shadow-sm mb-3">
<div class="card-body py-2">
<form class="row g-2 align-items-center" method="GET">
    <div class="col-auto flex-grow-1">
        <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar por nombre, apellidos o telefono..." value="<?= sanitize($buscar) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary">Buscar</button>
        <?php if ($buscar): ?><a href="admin_clientes.php" class="btn btn-sm btn-outline-secondary">Limpiar</a><?php endif; ?>
    </div>
</form>
</div>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr><th>Nombre</th><th>Telefono</th><th>Email</th><th>Vehiculos</th><th>Partes</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($clientes as $c): ?>
        <tr>
            <td><strong><?= sanitize($c['nombre'] . ' ' . $c['apellidos']) ?></strong></td>
            <td><?= sanitize($c['telefono'] ?: '-') ?></td>
            <td><?= sanitize($c['email'] ?: '-') ?></td>
            <td><span class="badge bg-info"><?= (int)$c['total_vehiculos'] ?></span></td>
            <td><span class="badge bg-primary"><?= (int)$c['total_partes'] ?></span></td>
            <td>
                <a href="admin_cliente_ver.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                <button class="btn btn-sm btn-outline-secondary" onclick="editCliente(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
                <a href="admin_cliente_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar este cliente y sus vehiculos?')" title="Eliminar"><i class="bi bi-trash"></i></a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($clientes)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No hay clientes registrados</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<!-- Modal Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
    <form method="POST" action="admin_cliente_save.php">
        <div class="modal-header">
            <h5 class="modal-title" id="modalClienteTitle">Nuevo Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" id="cId">
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="cNombre" class="form-control" required maxlength="150">
                </div>
                <div class="col-6">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellidos" id="cApellidos" class="form-control" maxlength="150">
                </div>
                <div class="col-6">
                    <label class="form-label">Telefono</label>
                    <input type="text" name="telefono" id="cTelefono" class="form-control" maxlength="20">
                </div>
                <div class="col-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" id="cEmail" class="form-control" maxlength="150">
                </div>
                <div class="col-12">
                    <label class="form-label">Direccion</label>
                    <input type="text" name="direccion" id="cDireccion" class="form-control" maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" id="cNotas" class="form-control" rows="2"></textarea>
                </div>
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
function resetClienteForm() {
    document.getElementById('modalClienteTitle').textContent = 'Nuevo Cliente';
    ['cId','cNombre','cApellidos','cTelefono','cEmail','cDireccion','cNotas'].forEach(function(id){
        document.getElementById(id).value = '';
    });
}
function editCliente(c) {
    document.getElementById('modalClienteTitle').textContent = 'Editar Cliente';
    document.getElementById('cId').value = c.id;
    document.getElementById('cNombre').value = c.nombre;
    document.getElementById('cApellidos').value = c.apellidos;
    document.getElementById('cTelefono').value = c.telefono;
    document.getElementById('cEmail').value = c.email || '';
    document.getElementById('cDireccion').value = c.direccion || '';
    document.getElementById('cNotas').value = c.notas || '';
    new bootstrap.Modal(document.getElementById('modalCliente')).show();
}
</script>

<?php require 'footer.php'; ?>
