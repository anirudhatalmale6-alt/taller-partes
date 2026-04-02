<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);
$parte = null;
$tareas = [];
$articulos = [];

if ($id > 0) {
    $parte = db()->prepare("SELECT * FROM partes WHERE id=?");
    $parte->execute([$id]);
    $parte = $parte->fetch();
    if (!$parte) { flash('error', 'Parte no encontrado'); redirect('admin_partes.php'); }

    $stmt = db()->prepare("SELECT * FROM tareas WHERE parte_id=? ORDER BY id");
    $stmt->execute([$id]);
    $tareas = $stmt->fetchAll();

    $stmt = db()->prepare("SELECT * FROM articulos WHERE parte_id=? ORDER BY id");
    $stmt->execute([$id]);
    $articulos = $stmt->fetchAll();
}

$pageTitle = $id ? 'Editar Parte #' . $id : 'Nuevo Parte';
$operadores = db()->query("SELECT id, nombre FROM operadores WHERE activo=1 ORDER BY nombre")->fetchAll();
require 'header.php';
?>

<h4 class="mb-3"><i class="bi bi-clipboard-plus"></i> <?= sanitize($pageTitle) ?></h4>

<form method="POST" action="admin_parte_save.php" id="parteForm">
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- Header -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white"><i class="bi bi-person"></i> Datos del Cliente y Vehiculo</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="cliente_nombre" class="form-control" required value="<?= sanitize($parte['cliente_nombre'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="cliente_apellidos" class="form-control" value="<?= sanitize($parte['cliente_apellidos'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= sanitize($parte['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Marca</label>
                    <input type="text" name="vehiculo_marca" class="form-control" value="<?= sanitize($parte['vehiculo_marca'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="vehiculo_modelo" class="form-control" value="<?= sanitize($parte['vehiculo_modelo'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Matricula</label>
                    <input type="text" name="matricula" class="form-control" value="<?= sanitize($parte['matricula'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Operario asignado</label>
                    <select name="operador_id" class="form-select">
                        <option value="">-- Sin asignar --</option>
                        <?php foreach ($operadores as $op): ?>
                            <option value="<?= $op['id'] ?>" <?= ($parte['operador_id'] ?? 0)==$op['id']?'selected':'' ?>>
                                <?= sanitize($op['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-task"></i> Tareas</span>
            <button type="button" class="btn btn-sm btn-light" onclick="addTarea()"><i class="bi bi-plus"></i> Agregar Tarea</button>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0" id="tablaTareas">
                <thead class="table-light">
                    <tr>
                        <th style="width:60%">Descripcion</th>
                        <th style="width:25%">Tiempo estimado (min)</th>
                        <th style="width:15%"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($tareas)): ?>
                    <?php foreach ($tareas as $i => $t): ?>
                    <tr>
                        <td>
                            <input type="hidden" name="tareas[<?= $i ?>][id]" value="<?= $t['id'] ?>">
                            <input type="text" name="tareas[<?= $i ?>][descripcion]" class="form-control form-control-sm" required value="<?= sanitize($t['descripcion']) ?>">
                        </td>
                        <td>
                            <input type="number" name="tareas[<?= $i ?>][tiempo_estimado]" class="form-control form-control-sm" min="0" step="1" value="<?= (int)$t['tiempo_estimado'] ?>">
                        </td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td><input type="text" name="tareas[0][descripcion]" class="form-control form-control-sm" required placeholder="Descripcion de la tarea"></td>
                        <td><input type="number" name="tareas[0][tiempo_estimado]" class="form-control form-control-sm" min="0" step="1" value="0"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Articles -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-warning d-flex justify-content-between align-items-center">
            <span><i class="bi bi-box-seam"></i> Articulos</span>
            <button type="button" class="btn btn-sm btn-light" onclick="addArticulo()"><i class="bi bi-plus"></i> Agregar Articulo</button>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0" id="tablaArticulos">
                <thead class="table-light">
                    <tr>
                        <th style="width:40%">Descripcion</th>
                        <th>Cantidad</th>
                        <th>Precio Coste</th>
                        <th>Precio Venta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($articulos)): ?>
                    <?php foreach ($articulos as $i => $a): ?>
                    <tr>
                        <td>
                            <input type="hidden" name="articulos[<?= $i ?>][id]" value="<?= $a['id'] ?>">
                            <input type="text" name="articulos[<?= $i ?>][descripcion]" class="form-control form-control-sm" required value="<?= sanitize($a['descripcion']) ?>">
                        </td>
                        <td><input type="number" name="articulos[<?= $i ?>][cantidad]" class="form-control form-control-sm" min="1" value="<?= (int)$a['cantidad'] ?>"></td>
                        <td><input type="number" name="articulos[<?= $i ?>][precio_coste]" class="form-control form-control-sm" min="0" step="0.01" value="<?= $a['precio_coste'] ?>"></td>
                        <td><input type="number" name="articulos[<?= $i ?>][precio_venta]" class="form-control form-control-sm" min="0" step="0.01" value="<?= $a['precio_venta'] ?>"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar Parte</button>
        <a href="admin_partes.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<script>
let tareaIdx = <?= max(count($tareas), 1) ?>;
let artIdx = <?= max(count($articulos), 0) ?>;

function addTarea() {
    const tbody = document.querySelector('#tablaTareas tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="tareas[${tareaIdx}][descripcion]" class="form-control form-control-sm" required placeholder="Descripcion de la tarea"></td>
        <td><input type="number" name="tareas[${tareaIdx}][tiempo_estimado]" class="form-control form-control-sm" min="0" step="1" value="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    tareaIdx++;
}

function addArticulo() {
    const tbody = document.querySelector('#tablaArticulos tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="articulos[${artIdx}][descripcion]" class="form-control form-control-sm" required placeholder="Descripcion"></td>
        <td><input type="number" name="articulos[${artIdx}][cantidad]" class="form-control form-control-sm" min="1" value="1"></td>
        <td><input type="number" name="articulos[${artIdx}][precio_coste]" class="form-control form-control-sm" min="0" step="0.01" value="0"></td>
        <td><input type="number" name="articulos[${artIdx}][precio_venta]" class="form-control form-control-sm" min="0" step="0.01" value="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    artIdx++;
}
</script>

<?php require 'footer.php'; ?>
