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

// Format time as Xh Ym
function ft($min) { $min=(float)$min; $h=floor($min/60); $m=round($min%60); return $h>0?"{$h}h {$m}m":"{$m}m"; }
function fe($amt) { return number_format((float)$amt, 2, ',', '.') . ' €'; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Parte #<?= $id ?></title>
    <style>
        @page { margin: 15mm 12mm; size: A4; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #222; padding: 10px; }

        /* Header */
        .print-header {
            text-align: center; padding: 12px 0 8px;
            border-bottom: 3px solid #222; margin-bottom: 10px;
        }
        .print-header h1 { font-size: 22px; letter-spacing: 2px; margin-bottom: 2px; }
        .print-header .subtitle { font-size: 11px; color: #555; }

        /* Vehicle line */
        .vehicle-line {
            font-size: 14px; font-weight: bold; padding: 8px 0 6px;
            border-bottom: 1px solid #ccc; margin-bottom: 8px;
        }

        /* Info row */
        .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px; }
        .info-row .field { }
        .info-row .field label { font-weight: bold; color: #555; font-size: 9px; text-transform: uppercase; display: block; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { background: #333; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        th, td { border: 1px solid #bbb; padding: 5px 8px; text-align: left; }
        td { font-size: 11px; }
        tbody tr:nth-child(even) { background: #f9f9f9; }
        tbody tr.empty-row td { border-left: 1px solid #bbb; border-right: 1px solid #bbb; height: 22px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Section titles */
        .section-title {
            font-size: 11px; font-weight: bold; text-transform: uppercase;
            padding: 4px 8px; background: #eee; border: 1px solid #bbb;
            border-bottom: none; letter-spacing: 0.5px;
        }

        /* Totals */
        .totals-row { font-weight: bold; background: #f0f0f0; }

        /* Footer */
        .print-footer {
            margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc;
            font-size: 9px; color: #999; display: flex; justify-content: space-between;
        }

        /* Priority indicator */
        .priority-badge {
            display: inline-block; padding: 1px 8px; font-size: 9px;
            border-radius: 3px; font-weight: bold; text-transform: uppercase;
        }
        .priority-baja { background: #d4edda; color: #155724; }
        .priority-normal { background: #d6e9f8; color: #084298; }
        .priority-alta { background: #f8d7da; color: #842029; }

        @media print { body { padding: 0; } }
    </style>
</head>
<body>

    <!-- Header with business name -->
    <div class="print-header">
        <h1>PARTE DE TRABAJO</h1>
        <div class="subtitle">N&ordm; <?= $id ?> | <?= date('d/m/Y', strtotime($parte['created_at'])) ?></div>
    </div>

    <!-- Vehicle line (prominent, like the sample) -->
    <div class="vehicle-line">
        <?= sanitize($parte['vehiculo_marca'] . ' ' . $parte['vehiculo_modelo']) ?> - <?= sanitize($parte['matricula']) ?>
        <span class="priority-badge priority-<?= $parte['prioridad'] ?? 'normal' ?>" style="float:right; margin-top:2px">
            <?= ucfirst($parte['prioridad'] ?? 'normal') ?>
        </span>
    </div>

    <!-- Client info -->
    <div class="info-row">
        <div class="field"><label>Cliente</label><?= sanitize($parte['cliente_nombre'] . ' ' . $parte['cliente_apellidos']) ?></div>
        <div class="field"><label>Telefono</label><?= sanitize($parte['telefono'] ?: 'N/A') ?></div>
        <div class="field"><label>Operario</label><?= sanitize($parte['operador_nombre'] ?? 'Sin asignar') ?></div>
        <div class="field"><label>Estado</label><?= ucfirst($parte['estado']) ?></div>
    </div>

    <!-- Trabajos a realizar -->
    <table>
        <thead>
            <tr>
                <th style="width:6%">Id</th>
                <th>Trabajos a realizar</th>
                <th style="width:12%" class="text-center">Tiempo</th>
                <th style="width:12%" class="text-center">Tiempo</th>
                <th style="width:6%" class="text-center">Op</th>
            </tr>
        </thead>
        <tbody>
        <?php $idx = 1; foreach ($tareas as $t): ?>
            <?php $tacum = $t['tiempo_acumulado'] ?? $t['tiempo_real']; ?>
            <tr>
                <td class="text-center">T<?= $idx++ ?></td>
                <td>
                    <?= sanitize($t['descripcion']) ?>
                    <?php if ($t['observaciones']): ?>
                        <br><small style="color:#666"><em><?= sanitize($t['observaciones']) ?></em></small>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= (int)$t['tiempo_estimado'] ?>'</td>
                <td class="text-center"><?= $tacum > 0 ? round($tacum) . "'" : '' ?></td>
                <td class="text-center"><?= $t['cerrada'] ? '&#10003;' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php for ($i = count($tareas); $i < 10; $i++): ?>
            <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
        </tbody>
    </table>

    <!-- Material -->
    <table>
        <thead>
            <tr>
                <th>Material</th>
                <th style="width:15%" class="text-right">Coste</th>
                <th style="width:15%" class="text-right">Pvp</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($articulos as $a): ?>
            <tr>
                <td><?= sanitize($a['descripcion']) ?><?= $a['cantidad'] > 1 ? ' (x'.$a['cantidad'].')' : '' ?></td>
                <td class="text-right"><?= fe($a['precio_coste'] * $a['cantidad']) ?></td>
                <td class="text-right"><?= fe($a['precio_venta'] * $a['cantidad']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php for ($i = count($articulos); $i < 6; $i++): ?>
            <tr class="empty-row"><td></td><td></td><td></td></tr>
        <?php endfor; ?>
        <tr class="totals-row">
            <td class="text-right"><strong>TOTALES</strong></td>
            <td class="text-right"><?= fe($total_coste) ?></td>
            <td class="text-right"><?= fe($total_venta) ?></td>
        </tr>
        <tr class="totals-row">
            <td class="text-right"><strong>TIEMPO</strong></td>
            <td class="text-center" colspan="2"><?= ft($total_estimado) ?> est. / <?= ft($total_real) ?> real</td>
        </tr>
        </tbody>
    </table>

    <div class="print-footer">
        <span>Parte #<?= $id ?> | Impreso: <?= date('d/m/Y H:i') ?></span>
        <span>Firma: _______________________________</span>
    </div>

    <script>window.onload = function(){ window.print(); }</script>
</body>
</html>
