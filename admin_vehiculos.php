<?php
require 'config.php';
$pageTitle = 'Vehiculos';

$buscar = trim($_GET['buscar'] ?? '');
$sql = "SELECT v.*, c.nombre as cliente_nombre, c.apellidos as cliente_apellidos, c.telefono as cliente_telefono,
        (SELECT COUNT(*) FROM partes p WHERE p.vehiculo_id=v.id) as total_partes
        FROM vehiculos v JOIN clientes c ON v.cliente_id=c.id WHERE 1=1";
$params = [];
if ($buscar) {
    $sql .= " AND (v.matricula LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ? OR c.nombre LIKE ? OR c.apellidos LIKE ?)";
    $params = ["%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%", "%$buscar%"];
}
$sql .= " ORDER BY v.matricula";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$vehiculos = $stmt->fetchAll();

$clientes = db()->query("SELECT id, nombre, apellidos FROM clientes ORDER BY nombre")->fetchAll();

// Check if opening modal for new vehicle with pre-selected client
$preClienteId = (int)($_GET['cliente_id'] ?? 0);
$openModal = isset($_GET['nuevo']);

require 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="bi bi-car-front"></i> Vehiculos</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVehiculo" onclick="resetVehiculoForm()">
        <i class="bi bi-plus-lg"></i> Nuevo Vehiculo
    </button>
</div>

<div class="card shadow-sm mb-3">
<div class="card-body py-2">
<form class="row g-2 align-items-center" method="GET">
    <div class="col-auto flex-grow-1">
        <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar por matricula, marca, modelo o cliente..." value="<?= sanitize($buscar) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-outline-primary">Buscar</button>
        <?php if ($buscar): ?><a href="admin_vehiculos.php" class="btn btn-sm btn-outline-secondary">Limpiar</a><?php endif; ?>
    </div>
</form>
</div>
</div>

<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-hover mb-0">
    <thead class="table-light">
        <tr><th>Matricula</th><th>Marca/Modelo</th><th>Cliente</th><th>Partes</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($vehiculos as $v): ?>
        <tr>
            <td><strong><?= sanitize($v['matricula']) ?></strong></td>
            <td><?= sanitize($v['marca'] . ' ' . $v['modelo']) ?></td>
            <td>
                <a href="admin_cliente_ver.php?id=<?= $v['cliente_id'] ?>"><?= sanitize($v['cliente_nombre'] . ' ' . $v['cliente_apellidos']) ?></a>
            </td>
            <td><span class="badge bg-primary"><?= (int)$v['total_partes'] ?></span></td>
            <td>
                <a href="admin_vehiculo_ver.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                <button class="btn btn-sm btn-outline-secondary" onclick="editVehiculo(<?= htmlspecialchars(json_encode($v), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                <a href="admin_vehiculo_delete.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar este vehiculo?')"><i class="bi bi-trash"></i></a>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($vehiculos)): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No hay vehiculos registrados</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>

<!-- Modal Vehiculo -->
<div class="modal fade" id="modalVehiculo" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
    <form method="POST" action="admin_vehiculo_save.php">
        <div class="modal-header">
            <h5 class="modal-title" id="modalVehiculoTitle">Nuevo Vehiculo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" id="vId">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Cliente *</label>
                    <select name="cliente_id" id="vClienteId" class="form-select" required>
                        <option value="">-- Seleccionar cliente --</option>
                        <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $preClienteId==$c['id']?'selected':'' ?>>
                                <?= sanitize($c['nombre'] . ' ' . $c['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Marca</label>
                    <input type="text" name="marca" id="vMarca" class="form-control" maxlength="80">
                </div>
                <div class="col-6">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="modelo" id="vModelo" class="form-control" maxlength="80">
                </div>
                <div class="col-4">
                    <label class="form-label">Matricula *</label>
                    <input type="text" name="matricula" id="vMatricula" class="form-control" required maxlength="20">
                </div>
                <div class="col-4">
                    <label class="form-label">Ano</label>
                    <input type="text" name="anio" id="vAnio" class="form-control" maxlength="4">
                </div>
                <div class="col-4">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" id="vColor" class="form-control" maxlength="40">
                </div>
                <div class="col-12">
                    <label class="form-label">VIN</label>
                    <input type="text" name="vin" id="vVin" class="form-control" maxlength="50">
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
function resetVehiculoForm() {
    document.getElementById('modalVehiculoTitle').textContent = 'Nuevo Vehiculo';
    ['vId','vMarca','vModelo','vMatricula','vAnio','vColor','vVin'].forEach(function(id){
        document.getElementById(id).value = '';
    });
    document.getElementById('vClienteId').value = '<?= $preClienteId ?>';
}
function editVehiculo(v) {
    document.getElementById('modalVehiculoTitle').textContent = 'Editar Vehiculo';
    document.getElementById('vId').value = v.id;
    document.getElementById('vClienteId').value = v.cliente_id;
    document.getElementById('vMarca').value = v.marca;
    document.getElementById('vModelo').value = v.modelo;
    document.getElementById('vMatricula').value = v.matricula;
    document.getElementById('vAnio').value = v.anio || '';
    document.getElementById('vColor').value = v.color || '';
    document.getElementById('vVin').value = v.vin || '';
    new bootstrap.Modal(document.getElementById('modalVehiculo')).show();
}
<?php if ($openModal): ?>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalVehiculo')).show();
});
<?php endif; ?>
</script>

<?php require 'footer.php'; ?>
