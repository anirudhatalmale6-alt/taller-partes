<?php
require 'config.php';
if (is_operador()) redirect('operador_partes.php');
$pageTitle = 'Acceso Operario';
$error = get_flash('error');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-dark">
<div class="pin-container text-center">
    <div class="mb-4">
        <i class="bi bi-person-workspace display-1 text-success"></i>
        <h4 class="text-white mt-2">Acceso Operario</h4>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="operador_auth.php" id="pinForm">
        <input type="hidden" name="pin" id="pinValue">
        <div class="d-flex justify-content-center mb-3">
            <div class="d-flex gap-2">
                <div class="pin-dot" id="d0"></div>
                <div class="pin-dot" id="d1"></div>
                <div class="pin-dot" id="d2"></div>
                <div class="pin-dot" id="d3"></div>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-center">
            <?php for ($i = 1; $i <= 9; $i++): ?>
                <button type="button" class="btn btn-outline-light pin-btn" onclick="addDigit(<?= $i ?>)"><?= $i ?></button>
            <?php endfor; ?>
            <button type="button" class="btn btn-outline-danger pin-btn" onclick="clearPin()"><i class="bi bi-x-lg"></i></button>
            <button type="button" class="btn btn-outline-light pin-btn" onclick="addDigit(0)">0</button>
            <button type="button" class="btn btn-outline-warning pin-btn" onclick="delDigit()"><i class="bi bi-backspace"></i></button>
        </div>
    </form>

    <a href="index.php" class="btn btn-link text-muted mt-3">Volver al inicio</a>
</div>

<style>
.pin-dot {
    width: 20px; height: 20px;
    border: 2px solid #fff;
    border-radius: 50%;
    transition: background 0.15s;
}
.pin-dot.filled { background: #198754; border-color: #198754; }
</style>

<script>
let pin = '';
function addDigit(d) {
    if (pin.length >= 4) return;
    pin += d;
    updateDots();
    if (pin.length === 4) {
        document.getElementById('pinValue').value = pin;
        document.getElementById('pinForm').submit();
    }
}
function delDigit() {
    pin = pin.slice(0, -1);
    updateDots();
}
function clearPin() {
    pin = '';
    updateDots();
}
function updateDots() {
    for (let i = 0; i < 4; i++) {
        document.getElementById('d'+i).classList.toggle('filled', i < pin.length);
    }
}
</script>
</body>
</html>
