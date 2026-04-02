<?php
require 'config.php';
require __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

function ft($min) { $min=(float)$min; $h=floor($min/60); $m=round($min%60); return $h>0?"{$h}h {$m}m":"{$m}m"; }
function fe($amt) { return number_format((float)$amt, 2, ',', '.') . ' &euro;'; }

// Determine base URL for font loading
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim($baseUrl, '/') . '/';

// Embed logo as base64
$logoPath = __DIR__ . '/logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// Embed diagnostico image as base64
$diagPath = __DIR__ . '/diagnostico.png';
$diagBase64 = '';
if (file_exists($diagPath)) {
    $diagBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($diagPath));
}

// Vehicle identifier
$vehiculoId = $parte['matricula'];
if (!empty($parte['bastidor'])) {
    $vehiculoId = $parte['bastidor'];
}

ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18mm 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Dosis", Helvetica, Arial, sans-serif; font-size: 11px; color: #222; }

        .print-header {
            text-align: center; padding: 4px 0 4px;
            border-bottom: 3px solid #222; margin-bottom: 6px;
        }
        .print-header img { height: 100px; margin-bottom: 2px; }
        .print-header h1 { font-size: 20px; letter-spacing: 2px; margin-bottom: 2px; font-weight: 700; }
        .print-header .subtitle { font-size: 11px; color: #555; }

        .vehicle-line {
            font-size: 14px; font-weight: 700; padding: 8px 0 6px;
            border-bottom: 1px solid #ccc; margin-bottom: 8px;
        }

        .info-table { width: 100%; margin-bottom: 4px; }
        .info-table td { padding: 2px 0; vertical-align: top; }
        .info-table .lbl { font-weight: 700; color: #555; font-size: 9px; text-transform: uppercase; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.data th { background: #333; color: #fff; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
        table.data th, table.data td { border: 1px solid #bbb; padding: 3px 6px; text-align: left; }
        table.data td { font-size: 10px; }
        table.data .text-right { text-align: right; }
        table.data .text-center { text-align: center; }
        table.data .totals-row { font-weight: 700; background: #f0f0f0; }
        table.data .empty-row td { height: 18px; }

        .priority-badge {
            display: inline-block; padding: 1px 8px; font-size: 9px;
            font-weight: 700; text-transform: uppercase;
        }
        .priority-baja { background: #d4edda; color: #155724; }
        .priority-normal { background: #d6e9f8; color: #084298; }
        .priority-alta { background: #f8d7da; color: #842029; }

        .print-footer {
            margin-top: 20px; padding-top: 8px; border-top: 1px solid #ccc;
            font-size: 9px; color: #999;
        }
        .print-footer table { width: 100%; }
        .print-footer td { border: none; padding: 0; }

        /* Page 2: diagnostico as full-page image */
        .diag-page { text-align: center; }
        .diag-page img { width: 100%; }
    </style>
</head>
<body>

    <div class="print-header">
        <?php if ($logoBase64): ?>
            <img src="<?= $logoBase64 ?>" alt="Logo">
            <br>
        <?php endif; ?>
        <h1>PARTE DE TRABAJO</h1>
        <div class="subtitle">N&ordm; <?= $id ?> | <?= date('d/m/Y', strtotime($parte['created_at'])) ?></div>
    </div>

    <div class="vehicle-line">
        <?= htmlspecialchars($parte['vehiculo_marca'] . ' ' . $parte['vehiculo_modelo']) ?> - <?= htmlspecialchars($vehiculoId) ?>
        <span class="priority-badge priority-<?= $parte['prioridad'] ?? 'normal' ?>" style="float:right; margin-top:2px">
            <?= ucfirst($parte['prioridad'] ?? 'normal') ?>
        </span>
    </div>

    <table class="info-table">
        <tr>
            <td style="width:25%"><span class="lbl">Cliente</span><br><?= htmlspecialchars($parte['cliente_nombre'] . ' ' . $parte['cliente_apellidos']) ?></td>
            <td style="width:25%"><span class="lbl">Telefono</span><br><?= htmlspecialchars($parte['telefono'] ?: 'N/A') ?></td>
            <td style="width:25%"><span class="lbl">Operario</span><br><?= htmlspecialchars($parte['operador_nombre'] ?? 'Sin asignar') ?></td>
            <td style="width:25%"><span class="lbl">Estado</span><br><?= ucfirst($parte['estado']) ?></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:6%">Id</th>
                <th>Trabajos a realizar</th>
                <th style="width:12%" class="text-center">Tiempo Est.</th>
                <th style="width:12%" class="text-center">Tiempo Real</th>
                <th style="width:6%" class="text-center">Op</th>
            </tr>
        </thead>
        <tbody>
        <?php $idx = 1; foreach ($tareas as $t): ?>
            <?php $tacum = $t['tiempo_acumulado'] ?? $t['tiempo_real']; ?>
            <tr>
                <td class="text-center">T<?= $idx++ ?></td>
                <td>
                    <?= htmlspecialchars($t['descripcion']) ?>
                    <?php if ($t['observaciones']): ?>
                        <br><small style="color:#666"><em><?= htmlspecialchars($t['observaciones']) ?></em></small>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= (int)$t['tiempo_estimado'] ?>'</td>
                <td class="text-center"><?= $tacum > 0 ? round($tacum) . "'" : '' ?></td>
                <td class="text-center"><?= $t['cerrada'] ? '&#10003;' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php for ($i = count($tareas); $i < 12; $i++): ?>
            <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
        </tbody>
    </table>

    <table class="data">
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
                <td><?= htmlspecialchars($a['descripcion']) ?><?= $a['cantidad'] > 1 ? ' (x'.$a['cantidad'].')' : '' ?></td>
                <td class="text-right"><?= fe($a['precio_coste'] * $a['cantidad']) ?></td>
                <td class="text-right"><?= fe($a['precio_venta'] * $a['cantidad']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php for ($i = count($articulos); $i < 10; $i++): ?>
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
        <table><tr>
            <td>Parte #<?= $id ?> | Generado: <?= date('d/m/Y H:i') ?></td>
            <td style="text-align:right">Firma: _______________________________</td>
        </tr></table>
    </div>

    <!-- PAGE 2: Diagnostico as image -->
    <?php if ($diagBase64): ?>
    <div style="page-break-before: always;"></div>
    <div class="diag-page">
        <img src="<?= $diagBase64 ?>" alt="Diagnostico">
    </div>
    <?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultFont', 'Dosis');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Parte_' . $id . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
