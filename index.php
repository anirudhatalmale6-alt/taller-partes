<?php
require 'config.php';
if (is_operador()) redirect('operador_partes.php');
$pageTitle = 'Taller - Inicio';
require 'header.php';
?>
<div class="row justify-content-center mt-5">
    <div class="col-md-5 col-lg-4 mb-4">
        <a href="admin_dashboard.php" class="text-decoration-none">
            <div class="card text-center shadow-sm p-5">
                <i class="bi bi-gear-wide-connected display-1 text-primary"></i>
                <h3 class="mt-3 text-dark">Administrador</h3>
                <p class="text-muted">Gestionar partes, operarios y estadisticas</p>
            </div>
        </a>
    </div>
    <div class="col-md-5 col-lg-4 mb-4">
        <a href="operador_login.php" class="text-decoration-none">
            <div class="card text-center shadow-sm p-5">
                <i class="bi bi-person-workspace display-1 text-success"></i>
                <h3 class="mt-3 text-dark">Operario</h3>
                <p class="text-muted">Acceder con PIN para trabajar en partes</p>
            </div>
        </a>
    </div>
</div>
<?php require 'footer.php'; ?>
