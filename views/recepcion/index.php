<?php
require_once __DIR__ . '/../../controllers/recepcion/RecepcionController.php';
require_once __DIR__ . '/../../services/AuthorizationService.php';
require_once __DIR__ . '/../layouts/session.php';

$module_styles = ['recepciones/recepcion'];
$module_scripts = ['recepciones/index-recepciones'];

requireLogin();
$idusuario = $_SESSION['usuario_id'];
$authService = new AuthorizationService();

if (!$authService->esAdministrador($idusuario) && !$authService->puedeAccederModulo($idusuario, 'recepcion')) {
    $_SESSION['mensaje'] = 'No tiene permisos para acceder a esta sección.';
    $_SESSION['icono'] = 'error';
    header('Location: ' . $URL);
    exit;
}

// Redirección legacy: la antigua lista-recepciones.php ahora es el tab Historial
if (isset($_GET['redirect']) && $_GET['redirect'] === 'historial') {
    header('Location: ' . $URL . 'views/recepcion/index.php#historial');
    exit;
}

// Select2 para el buscador global; sin Chart.js
$skip_select2 = false;
$skip_chartjs = true;
$skip_datatables = false;
include_once '../layouts/header.php';

$controller = new RecepcionController();
$panel = $controller->panel();
$hoy = $panel['hoy'];
$mapa = $panel['mapa'];
$kpis = $panel['kpis'];
$historial = $panel['historial'];
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-5">
                <h1>Recepción</h1>
            </div>
            <div class="col-sm-7 d-flex flex-column flex-sm-row justify-content-sm-end align-items-sm-center">
                <?php include __DIR__ . '/partials/buscador-global.php'; ?>
                <a href="<?= $URL; ?>views/recepcion/create.php" class="btn btn-primary btn-sm ml-sm-2 mt-2 mt-sm-0">
                    <i class="fas fa-plus mr-1"></i> Nueva reserva
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <?php include __DIR__ . '/partials/kpi-bar.php'; ?>

        <ul class="nav nav-tabs rec-tabs" id="recTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-hoy-link" data-toggle="tab" href="#hoy" role="tab" aria-controls="hoy" aria-selected="true">
                    <i class="fas fa-calendar-day mr-1"></i> Hoy
                    <span class="badge badge-info"><?= (int) ($hoy['contadores']['llegadas'] + $hoy['contadores']['salidas']); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-mapa-link" data-toggle="tab" href="#mapa" role="tab" aria-controls="mapa" aria-selected="false">
                    <i class="fas fa-th mr-1"></i> Mapa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-historial-link" data-toggle="tab" href="#historial" role="tab" aria-controls="historial" aria-selected="false">
                    <i class="fas fa-list mr-1"></i> Historial
                </a>
            </li>
        </ul>

        <div class="tab-content pt-3" id="recTabsContent">
            <div class="tab-pane fade show active" id="hoy" role="tabpanel" aria-labelledby="tab-hoy-link">
                <?php include __DIR__ . '/partials/tab-hoy.php'; ?>
            </div>
            <div class="tab-pane fade" id="mapa" role="tabpanel" aria-labelledby="tab-mapa-link">
                <?php include __DIR__ . '/partials/tab-mapa.php'; ?>
            </div>
            <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="tab-historial-link">
                <div class="card">
                    <div class="card-body">
                        <?php include __DIR__ . '/partials/tab-historial.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
include_once '../layouts/mensajes.php';
include_once '../layouts/footer.php';
?>
