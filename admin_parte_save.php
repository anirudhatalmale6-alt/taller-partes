<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_partes.php');

$id = (int)($_POST['id'] ?? 0);
$cliente_id = (int)($_POST['cliente_id'] ?? 0);
$vehiculo_id = (int)($_POST['vehiculo_id'] ?? 0);
$cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
$cliente_apellidos = trim($_POST['cliente_apellidos'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$vehiculo_marca = trim($_POST['vehiculo_marca'] ?? '');
$vehiculo_modelo = trim($_POST['vehiculo_modelo'] ?? '');
$matricula = trim($_POST['matricula'] ?? '');
$bastidor = trim($_POST['bastidor'] ?? '');
$operador_id = (int)($_POST['operador_id'] ?? 0) ?: null;
$prioridad = $_POST['prioridad'] ?? 'normal';
if (!in_array($prioridad, ['baja','normal','alta'])) $prioridad = 'normal';

if (!$cliente_nombre && !$cliente_id) {
    flash('error', 'El nombre del cliente es obligatorio');
    redirect($id ? "admin_parte_form.php?id=$id" : 'admin_parte_form.php');
}

$pdo = db();
$pdo->beginTransaction();

try {
    // Auto-create or use existing client
    if (!$cliente_id && $cliente_nombre) {
        // Check if client with same name+phone exists
        $existing = $pdo->prepare("SELECT id FROM clientes WHERE nombre=? AND apellidos=? AND telefono=?");
        $existing->execute([$cliente_nombre, $cliente_apellidos, $telefono]);
        $found = $existing->fetch();
        if ($found) {
            $cliente_id = (int)$found['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO clientes (nombre, apellidos, telefono) VALUES (?,?,?)");
            $stmt->execute([$cliente_nombre, $cliente_apellidos, $telefono]);
            $cliente_id = (int)$pdo->lastInsertId();
        }
    }

    // Auto-create or use existing vehicle (search by matricula or bastidor)
    $vehiculoIdentifier = $matricula ?: $bastidor;
    if (!$vehiculo_id && $vehiculoIdentifier && $cliente_id) {
        if ($bastidor) {
            $existing = $pdo->prepare("SELECT id FROM vehiculos WHERE bastidor=?");
            $existing->execute([$bastidor]);
        } else {
            $existing = $pdo->prepare("SELECT id FROM vehiculos WHERE matricula=?");
            $existing->execute([$matricula]);
        }
        $found = $existing->fetch();
        if ($found) {
            $vehiculo_id = (int)$found['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO vehiculos (cliente_id, marca, modelo, matricula, bastidor) VALUES (?,?,?,?,?)");
            $stmt->execute([$cliente_id, $vehiculo_marca, $vehiculo_modelo, $matricula, $bastidor]);
            $vehiculo_id = (int)$pdo->lastInsertId();
        }
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE partes SET cliente_nombre=?, cliente_apellidos=?, vehiculo_marca=?, vehiculo_modelo=?, matricula=?, bastidor=?, telefono=?, operador_id=?, cliente_id=?, vehiculo_id=?, prioridad=? WHERE id=?");
        $stmt->execute([
            $cliente_nombre, $cliente_apellidos,
            $vehiculo_marca, $vehiculo_modelo,
            $matricula, $bastidor, $telefono, $operador_id, $cliente_id, $vehiculo_id, $prioridad, $id
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO partes (cliente_nombre, cliente_apellidos, vehiculo_marca, vehiculo_modelo, matricula, bastidor, telefono, operador_id, cliente_id, vehiculo_id, prioridad) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $cliente_nombre, $cliente_apellidos,
            $vehiculo_marca, $vehiculo_modelo,
            $matricula, $bastidor, $telefono, $operador_id, $cliente_id, $vehiculo_id, $prioridad
        ]);
        $id = (int)$pdo->lastInsertId();
    }

    // Handle tareas
    $submitted_tareas = $_POST['tareas'] ?? [];
    $submitted_tarea_ids = [];

    foreach ($submitted_tareas as $t) {
        $desc = trim($t['descripcion'] ?? '');
        if (!$desc) continue;
        $tiempo = max(0, (int)($t['tiempo_estimado'] ?? 0));
        $tid = (int)($t['id'] ?? 0);

        if ($tid > 0) {
            $stmt = $pdo->prepare("UPDATE tareas SET descripcion=?, tiempo_estimado=? WHERE id=? AND parte_id=?");
            $stmt->execute([$desc, $tiempo, $tid, $id]);
            $submitted_tarea_ids[] = $tid;
        } else {
            $stmt = $pdo->prepare("INSERT INTO tareas (parte_id, descripcion, tiempo_estimado) VALUES (?,?,?)");
            $stmt->execute([$id, $desc, $tiempo]);
            $submitted_tarea_ids[] = (int)$pdo->lastInsertId();
        }
    }

    if (!empty($submitted_tarea_ids)) {
        $placeholders = implode(',', array_fill(0, count($submitted_tarea_ids), '?'));
        $pdo->prepare("DELETE FROM tareas WHERE parte_id=? AND id NOT IN ($placeholders) AND cerrada=0 AND tiempo_real=0")
            ->execute(array_merge([$id], $submitted_tarea_ids));
    }

    // Handle articulos
    $submitted_articulos = $_POST['articulos'] ?? [];
    $submitted_art_ids = [];

    foreach ($submitted_articulos as $a) {
        $desc = trim($a['descripcion'] ?? '');
        if (!$desc) continue;
        $cant = max(1, (int)($a['cantidad'] ?? 1));
        $coste = max(0, (float)($a['precio_coste'] ?? 0));
        $venta = max(0, (float)($a['precio_venta'] ?? 0));
        $aid = (int)($a['id'] ?? 0);

        if ($aid > 0) {
            $stmt = $pdo->prepare("UPDATE articulos SET descripcion=?, cantidad=?, precio_coste=?, precio_venta=? WHERE id=? AND parte_id=?");
            $stmt->execute([$desc, $cant, $coste, $venta, $aid, $id]);
            $submitted_art_ids[] = $aid;
        } else {
            $stmt = $pdo->prepare("INSERT INTO articulos (parte_id, descripcion, cantidad, precio_coste, precio_venta) VALUES (?,?,?,?,?)");
            $stmt->execute([$id, $desc, $cant, $coste, $venta]);
            $submitted_art_ids[] = (int)$pdo->lastInsertId();
        }
    }

    if (!empty($submitted_art_ids)) {
        $placeholders = implode(',', array_fill(0, count($submitted_art_ids), '?'));
        $pdo->prepare("DELETE FROM articulos WHERE parte_id=? AND id NOT IN ($placeholders)")
            ->execute(array_merge([$id], $submitted_art_ids));
    }

    $pdo->commit();
    flash('ok', 'Parte guardado correctamente');
    redirect("admin_parte_ver.php?id=$id");

} catch (Exception $e) {
    $pdo->rollBack();
    flash('error', 'Error al guardar: ' . $e->getMessage());
    redirect($id ? "admin_parte_form.php?id=$id" : 'admin_parte_form.php');
}
