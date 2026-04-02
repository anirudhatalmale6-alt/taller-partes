<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin_partes.php');

$id = (int)($_POST['id'] ?? 0);
$data = [
    'cliente_nombre'    => trim($_POST['cliente_nombre'] ?? ''),
    'cliente_apellidos' => trim($_POST['cliente_apellidos'] ?? ''),
    'vehiculo_marca'    => trim($_POST['vehiculo_marca'] ?? ''),
    'vehiculo_modelo'   => trim($_POST['vehiculo_modelo'] ?? ''),
    'matricula'         => trim($_POST['matricula'] ?? ''),
    'telefono'          => trim($_POST['telefono'] ?? ''),
    'operador_id'       => (int)($_POST['operador_id'] ?? 0) ?: null,
];

if (!$data['cliente_nombre']) {
    flash('error', 'El nombre del cliente es obligatorio');
    redirect($id ? "admin_parte_form.php?id=$id" : 'admin_parte_form.php');
}

$pdo = db();
$pdo->beginTransaction();

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE partes SET cliente_nombre=?, cliente_apellidos=?, vehiculo_marca=?, vehiculo_modelo=?, matricula=?, telefono=?, operador_id=? WHERE id=?");
        $stmt->execute([
            $data['cliente_nombre'], $data['cliente_apellidos'],
            $data['vehiculo_marca'], $data['vehiculo_modelo'],
            $data['matricula'], $data['telefono'], $data['operador_id'], $id
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO partes (cliente_nombre, cliente_apellidos, vehiculo_marca, vehiculo_modelo, matricula, telefono, operador_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            $data['cliente_nombre'], $data['cliente_apellidos'],
            $data['vehiculo_marca'], $data['vehiculo_modelo'],
            $data['matricula'], $data['telefono'], $data['operador_id']
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

    // Delete removed tareas (only those without time entries)
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
