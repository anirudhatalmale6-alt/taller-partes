<?php
require 'config.php';
$id = (int)($_GET['id'] ?? 0);

$parte = db()->prepare("SELECT p.*, o.nombre as operador_nombre FROM partes p LEFT JOIN operadores o ON p.operador_id=o.id WHERE p.id=?");
$parte->execute([$id]);
$parte = $parte->fetch();
if (!$parte) exit('Parte no encontrado');

$tareas = db()->prepare("SELECT t.*, (SELECT SUM(r.minutos) FROM registros_tiempo r WHERE r.tarea_id=t.id) as tiempo_acumulado FROM tareas t WHERE t.parte_id=? ORDER BY t.id");
$tareas->execute([$id]);
$tareas = $tareas->fetchAll();

$articulos = db()->prepare("SELECT * FROM articulos WHERE parte_id=? ORDER BY id");
$articulos->execute([$id]);
$articulos = $articulos->fetchAll();

$total_estimado = array_sum(array_column($tareas, 'tiempo_estimado'));
$total_real = 0;
foreach ($tareas as $t) $total_real += ($t['tiempo_acumulado'] ?? $t['tiempo_real']);
$total_coste = 0; $total_venta = 0;
foreach ($articulos as $a) { $total_coste += $a['precio_coste']*$a['cantidad']; $total_venta += $a['precio_venta']*$a['cantidad']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Parte #<?= $id ?> - Imprimir</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; color: #333; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 13px; color: #666; margin-bottom: 15px; }
        .header-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; border: 1px solid #ccc; padding: 10px; }
        .header-grid .field { flex: 1 1 45%; }
        .header-grid .field label { font-weight: bold; display: block; font-size: 10px; text-transform: uppercase; color: #666; }
        .header-grid .field span { font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background: #f0f0f0; font-size: 11px; text-transform: uppercase; }
        tfoot th { background: #e8e8e8; }
        .section-title { font-size: 13px; font-weight: bold; margin: 10px 0 5px; padding: 3px 8px; background: #eee; }
        .estado { display: inline-block; padding: 2px 8px; font-size: 11px; font-weight: bold; border-radius: 3px; }
        .estado-abierto { background: #d4edda; color: #155724; }
        .estado-cerrado { background: #e2e3e5; color: #383d41; }
        .text-right { text-align: right; }
        .footer-info { margin-top: 20px; font-size: 10px; color: #999; text-align: center; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
    <h1>PARTE DE TRABAJO #<?= $id ?></h1>
    <div class="subtitle">
        Fecha: <?= date('d/m/Y', strtotime($parte['created_at'])) ?> |
        Estado: <span class="estado estado-<?= $parte['estado'] ?>"><?= ucfirst($parte['estado']) ?></span> |
        Operario: <?= sanitize($parte['operador_nombre'] ?? 'Sin asignar') ?>
    </div>

    <div class="header-grid">
        <div class="field"><label>Cliente</label><span><?= sanitize($parte['cliente_nombre'] . ' ' . $parte['cliente_apellidos']) ?></span></div>
        <div class="field"><label>Telefono</label><span><?= sanitize($parte['telefono'] ?: 'N/A') ?></span></div>
        <div class="field"><label>Vehiculo</label><span><?= sanitize($parte['vehiculo_marca'] . ' ' . $parte['vehiculo_modelo']) ?></span></div>
        <div class="field"><label>Matricula</label><span><?= sanitize($parte['matricula']) ?></span></div>
    </div>

    <div class="section-title">TAREAS</div>
    <table>
        <thead>
            <tr><th>Descripcion</th><th>T. Estimado</th><th>T. Real</th><th>Estado</th><th>Observaciones</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tareas as $t): ?>
            <?php $tacum = $t['tiempo_acumulado'] ?? $t['tiempo_real']; ?>
            <tr>
                <td><?= sanitize($t['descripcion']) ?></td>
                <td><?= format_tiempo($t['tiempo_estimado']) ?></td>
                <td><?= format_tiempo($tacum) ?></td>
                <td><?= $t['cerrada'] ? 'Cerrada' : 'Abierta' ?></td>
                <td><?= sanitize($t['observaciones'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><th>TOTALES</th><th><?= format_tiempo($total_estimado) ?></th><th><?= format_tiempo($total_real) ?></th><th colspan="2"></th></tr>
        </tfoot>
    </table>

    <div class="section-title">ARTICULOS</div>
    <table>
        <thead>
            <tr><th>Descripcion</th><th>Cantidad</th><th>P. Coste</th><th>P. Venta</th><th>Beneficio</th></tr>
        </thead>
        <tbody>
        <?php foreach ($articulos as $a): ?>
            <tr>
                <td><?= sanitize($a['descripcion']) ?></td>
                <td><?= (int)$a['cantidad'] ?></td>
                <td class="text-right"><?= format_euro($a['precio_coste']) ?></td>
                <td class="text-right"><?= format_euro($a['precio_venta']) ?></td>
                <td class="text-right"><?= format_euro(($a['precio_venta'] - $a['precio_coste']) * $a['cantidad']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($articulos)): ?>
            <tr><td colspan="5" style="text-align:center;color:#999">Sin articulos</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr><th colspan="2">TOTALES</th><th class="text-right"><?= format_euro($total_coste) ?></th><th class="text-right"><?= format_euro($total_venta) ?></th><th class="text-right"><?= format_euro($total_venta - $total_coste) ?></th></tr>
        </tfoot>
    </table>

    <div class="footer-info">Impreso el <?= date('d/m/Y H:i') ?></div>

    <script>window.onload = function(){ window.print(); }</script>
</body>
</html>
