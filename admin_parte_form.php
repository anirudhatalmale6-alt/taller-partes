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

// Load client/vehicle names for edit mode
$clienteNombre = '';
$vehiculoNombre = '';
if ($parte) {
    if ($parte['cliente_id']) {
        $c = db()->prepare("SELECT nombre, apellidos FROM clientes WHERE id=?")->execute([$parte['cliente_id']]);
        $c = db()->prepare("SELECT nombre, apellidos FROM clientes WHERE id=?");
        $c->execute([$parte['cliente_id']]);
        $c = $c->fetch();
        if ($c) $clienteNombre = $c['nombre'] . ' ' . $c['apellidos'];
    }
    if ($parte['vehiculo_id']) {
        $v = db()->prepare("SELECT marca, modelo, matricula FROM vehiculos WHERE id=?");
        $v->execute([$parte['vehiculo_id']]);
        $v = $v->fetch();
        if ($v) $vehiculoNombre = $v['matricula'] . ' - ' . $v['marca'] . ' ' . $v['modelo'];
    }
}

$pageTitle = $id ? 'Editar Parte #' . $id : 'Nuevo Parte';
$operadores = db()->query("SELECT id, nombre FROM operadores WHERE activo=1 ORDER BY nombre")->fetchAll();
require 'header.php';
?>

<h4 class="mb-3"><i class="bi bi-clipboard-plus"></i> <?= sanitize($pageTitle) ?></h4>

<form method="POST" action="admin_parte_save.php" id="parteForm">
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- Client selection -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-primary text-white"><i class="bi bi-person"></i> Cliente</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 position-relative">
                    <label class="form-label">Buscar cliente existente (nombre, apellidos o telefono)</label>
                    <input type="text" id="buscarCliente" class="form-control" autocomplete="off"
                        placeholder="Escribe nombre o telefono (min. 3 caracteres)..." value="<?= sanitize($clienteNombre) ?>">
                    <div id="listaClientes" class="autocomplete-list" style="display:none"></div>
                    <input type="hidden" name="cliente_id" id="clienteId" value="<?= $parte['cliente_id'] ?? '' ?>">
                </div>
                <div class="col-md-6">
                    <div class="card bg-light p-2 mt-4" id="clienteInfo" style="<?= $clienteNombre ? '' : 'display:none' ?>">
                        <small class="text-muted">Cliente seleccionado:</small>
                        <strong id="clienteInfoNombre"><?= sanitize($clienteNombre) ?></strong>
                        <a href="#" id="clienteClear" class="text-danger small">Cambiar</a>
                    </div>
                </div>
            </div>
            <div class="mt-2" id="nuevoClienteBox" style="display:none">
                <div class="alert alert-info py-2">
                    <i class="bi bi-info-circle"></i> Cliente no encontrado. Se creara automaticamente con los datos del parte.
                </div>
            </div>
            <!-- New client fields (shown when no existing client selected) -->
            <div id="camposNuevoCliente" style="<?= ($parte && $parte['cliente_id']) ? 'display:none' : '' ?>">
                <div class="row g-3 mt-1">
                    <div class="col-md-4">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="cliente_nombre" id="fNombre" class="form-control" value="<?= sanitize($parte['cliente_nombre'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Apellidos</label>
                        <input type="text" name="cliente_apellidos" id="fApellidos" class="form-control" value="<?= sanitize($parte['cliente_apellidos'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="telefono" id="fTelefono" class="form-control" value="<?= sanitize($parte['telefono'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle selection -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-secondary text-white"><i class="bi bi-car-front"></i> Vehiculo</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6 position-relative">
                    <label class="form-label">Buscar vehiculo existente (matricula, marca o modelo)</label>
                    <input type="text" id="buscarVehiculo" class="form-control" autocomplete="off"
                        placeholder="Escribe matricula o marca (min. 3 caracteres)..." value="<?= sanitize($vehiculoNombre) ?>">
                    <div id="listaVehiculos" class="autocomplete-list" style="display:none"></div>
                    <input type="hidden" name="vehiculo_id" id="vehiculoId" value="<?= $parte['vehiculo_id'] ?? '' ?>">
                </div>
                <div class="col-md-6">
                    <div class="card bg-light p-2 mt-4" id="vehiculoInfo" style="<?= $vehiculoNombre ? '' : 'display:none' ?>">
                        <small class="text-muted">Vehiculo seleccionado:</small>
                        <strong id="vehiculoInfoNombre"><?= sanitize($vehiculoNombre) ?></strong>
                        <a href="#" id="vehiculoClear" class="text-danger small">Cambiar</a>
                    </div>
                </div>
            </div>
            <!-- New vehicle fields -->
            <div id="camposNuevoVehiculo" style="<?= ($parte && $parte['vehiculo_id']) ? 'display:none' : '' ?>">
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Marca</label>
                        <input type="text" name="vehiculo_marca" id="fMarca" class="form-control" value="<?= sanitize($parte['vehiculo_marca'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="vehiculo_modelo" id="fModelo" class="form-control" value="<?= sanitize($parte['vehiculo_modelo'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Matricula / Bastidor</label>
                        <input type="text" name="matricula_bastidor" id="fMatriculaBastidor" class="form-control"
                            placeholder="Matricula o n. bastidor (>8 dig.)"
                            value="<?= sanitize(!empty($parte['bastidor']) ? $parte['bastidor'] : ($parte['matricula'] ?? '')) ?>">
                        <small class="text-muted" id="matriculaBastidorHint">Si tiene mas de 8 caracteres se guardara como bastidor</small>
                        <input type="hidden" name="matricula" id="fMatricula" value="<?= sanitize($parte['matricula'] ?? '') ?>">
                        <input type="hidden" name="bastidor" id="fBastidor" value="<?= sanitize($parte['bastidor'] ?? '') ?>">
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
            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <label class="form-label">Prioridad</label>
                    <select name="prioridad" class="form-select">
                        <option value="normal" <?= ($parte['prioridad'] ?? 'normal')==='normal'?'selected':'' ?>>Normal</option>
                        <option value="baja" <?= ($parte['prioridad'] ?? '')==='baja'?'selected':'' ?>>Baja</option>
                        <option value="alta" <?= ($parte['prioridad'] ?? '')==='alta'?'selected':'' ?>>Alta</option>
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
                        <td><input type="number" name="tareas[<?= $i ?>][tiempo_estimado]" class="form-control form-control-sm" min="0" step="1" value="<?= (int)$t['tiempo_estimado'] ?>"></td>
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
                    <tr><th style="width:40%">Descripcion</th><th>Cantidad</th><th>Precio Coste</th><th>Precio Venta</th><th></th></tr>
                </thead>
                <tbody>
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

// Wait for app.js to load (it's in the footer)
document.addEventListener('DOMContentLoaded', function() {

// Client autocomplete (searches by name, apellidos and telefono)
setupAutocomplete('buscarCliente', 'listaClientes', 'api_buscar.php?tipo=clientes', function(item) {
    document.getElementById('clienteId').value = item.id;
    document.getElementById('buscarCliente').value = item.nombre + ' ' + item.apellidos;
    document.getElementById('clienteInfoNombre').textContent = item.nombre + ' ' + item.apellidos + (item.telefono ? ' - Tel: ' + item.telefono : '');
    document.getElementById('clienteInfo').style.display = '';
    document.getElementById('camposNuevoCliente').style.display = 'none';
    document.getElementById('nuevoClienteBox').style.display = 'none';
    // Fill hidden fields
    document.getElementById('fNombre').value = item.nombre;
    document.getElementById('fApellidos').value = item.apellidos;
    document.getElementById('fTelefono').value = item.telefono;
    // Store for vehicle filtering
    window.selectedClienteId = item.id;
}, {minChars: 3});

document.getElementById('clienteClear').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('clienteId').value = '';
    document.getElementById('buscarCliente').value = '';
    document.getElementById('clienteInfo').style.display = 'none';
    document.getElementById('camposNuevoCliente').style.display = '';
    document.getElementById('fNombre').value = '';
    document.getElementById('fApellidos').value = '';
    document.getElementById('fTelefono').value = '';
    window.selectedClienteId = 0;
    document.getElementById('buscarCliente').focus();
});

// Vehicle autocomplete (searches by matricula, marca, modelo)
setupAutocomplete('buscarVehiculo', 'listaVehiculos', 'api_buscar.php?tipo=vehiculos', function(item) {
    var identifier = item.matricula || item.bastidor || '';
    document.getElementById('vehiculoId').value = item.id;
    document.getElementById('buscarVehiculo').value = identifier + ' - ' + item.marca + ' ' + item.modelo;
    document.getElementById('vehiculoInfoNombre').textContent = identifier + ' - ' + item.marca + ' ' + item.modelo;
    document.getElementById('vehiculoInfo').style.display = '';
    document.getElementById('camposNuevoVehiculo').style.display = 'none';
    document.getElementById('fMarca').value = item.marca;
    document.getElementById('fModelo').value = item.modelo;
    document.getElementById('fMatricula').value = item.matricula || '';
    document.getElementById('fBastidor').value = item.bastidor || '';
    document.getElementById('fMatriculaBastidor').value = identifier;
    // If vehicle has a client, auto-select it
    if (item.cliente_id && !document.getElementById('clienteId').value) {
        document.getElementById('clienteId').value = item.cliente_id;
    }
}, {
    minChars: 3,
    getClienteId: function() { return document.getElementById('clienteId').value || window.selectedClienteId || ''; }
});

document.getElementById('vehiculoClear').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('vehiculoId').value = '';
    document.getElementById('buscarVehiculo').value = '';
    document.getElementById('vehiculoInfo').style.display = 'none';
    document.getElementById('camposNuevoVehiculo').style.display = '';
    document.getElementById('fMarca').value = '';
    document.getElementById('fModelo').value = '';
    document.getElementById('fMatricula').value = '';
    document.getElementById('fBastidor').value = '';
    document.getElementById('fMatriculaBastidor').value = '';
    document.getElementById('buscarVehiculo').focus();
});

// Matricula / Bastidor auto-detect
var mbInput = document.getElementById('fMatriculaBastidor');
var mbHint = document.getElementById('matriculaBastidorHint');
function updateMatriculaBastidor() {
    var val = mbInput.value.replace(/\s/g, '');
    if (val.length > 8) {
        document.getElementById('fMatricula').value = '';
        document.getElementById('fBastidor').value = val;
        mbHint.textContent = 'Se guardara como BASTIDOR';
        mbHint.className = 'text-info';
    } else {
        document.getElementById('fMatricula').value = val;
        document.getElementById('fBastidor').value = '';
        mbHint.textContent = 'Se guardara como MATRICULA';
        mbHint.className = 'text-muted';
    }
}
mbInput.addEventListener('input', updateMatriculaBastidor);
updateMatriculaBastidor();

}); // end DOMContentLoaded
</script>

<?php require 'footer.php'; ?>
