<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    
    <link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/media/img/favicon.png">
<title>SGCE - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<?= SgceCss('assets/css/sgce-base.min.css') ?>
<?= SgceCss('assets/css/sgce-soft-motion.css') ?>
<?= SgceEstilosTema($Pdo) ?>
<?= SgceCss('assets/css/maestros-botones-metalicos.css') ?>
<?= SgceCss('assets/css/grupos-alumnos-botones-metalicos.css') ?>
<?= SgceCss('assets/css/materias-botones-metalicos.css') ?>
<?= SgceCss('assets/css/admin-paginacion-busqueda.css') ?>
<?= SgceCss('assets/css/asignaciones-botones-metalicos.css') ?>
<?= SgceCss('assets/css/expedientes-botones-metalicos.css') ?>
<?= SgceCss('assets/css/dashboard-colores-suaves.css') ?>
<style id="SgceAdminDashboardAjusteSuave">
html body .SgceModuleWrap .DashboardRiskEmpty .DashboardRiskEmptyIcon{
    background:rgba(22,163,74,.10)!important;
    color:#16A34A!important;
    border:1px solid rgba(22,163,74,.18)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.88),0 8px 18px rgba(22,163,74,.06)!important;
}
html body .SgceModuleWrap .DashboardRiskEmpty .DashboardRiskEmptyIcon .SgceColorIcon{
    background:transparent!important;
    border:0!important;
    box-shadow:none!important;
    color:#16A34A!important;
}
html body .SgceModuleWrap .DashboardModuleGridPro .DashboardModuleCard.DashboardModuleAnuncios{
    --ModuleAccent:#2563EB;
    --ModuleSoft:rgba(37,99,235,.065);
    --ModuleTint:#F8FBFF;
    --ModuleBorder:rgba(37,99,235,.13);
    --ModuleGlow:rgba(37,99,235,.045);
    --ModuleTopAccent:rgba(37,99,235,.62);
    border-top-color:rgba(37,99,235,.62)!important;
    box-shadow:0 10px 22px rgba(15,23,42,.05),0 10px 20px rgba(37,99,235,.04)!important;
}
html body .SgceModuleWrap .DashboardModuleGridPro .DashboardModuleCard.DashboardModuleAnuncios>.SgceColorIcon{
    background:rgba(37,99,235,.055)!important;
    color:#2563EB!important;
    border:1px solid rgba(37,99,235,.12)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.90),0 6px 14px rgba(37,99,235,.04)!important;
}
html body .SgceModuleWrap .DashboardModuleGridPro .DashboardModuleCard.DashboardModuleAnuncios:hover{
    border-color:rgba(37,99,235,.15)!important;
    box-shadow:0 14px 28px rgba(15,23,42,.07),0 12px 24px rgba(37,99,235,.055)!important;
}
</style>

</head>
<body>

<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <?php
        require_once dirname(__DIR__, 2) . '/includes/SGCE_AdminViewContext.php';
        $AdminTabMeta = [
            'inicio' => ['SGCE | Administrador', 'Panel principal, accesos rápidos, contadores y alumnos con riesgo.', '🧭'],
            'maestros' => ['Maestros', 'Alta, edición y control de docentes.', '🧑‍🏫'],
            'grupos' => ['Grupos', 'Control de etapa académica, grupo y turno.', '🏫'],
            'materias' => ['Materias', 'Catálogo por grupo, horas semanales y disponibilidad.', '📚'],
            'alumnos' => ['Alumnos', 'Inscripciones y administración de estudiantes.', '🎒'],
            'expedientes' => ['Expedientes', 'Historial y consulta individual de alumnos.', '🗃️'],
            'asignaciones' => ['Asignaciones', 'Materias vinculadas con docentes y grupos.', '📖'],
            'bitacora' => ['Bitácora', 'Movimientos importantes realizados en el sistema.', '📝']
        ];
        $AdminMeta = $AdminTabMeta[$TabActual] ?? $AdminTabMeta['inicio'];
    ?>

    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true"><?= htmlspecialchars($AdminMeta[2], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div>
                <h1><?= htmlspecialchars($AdminMeta[0], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($AdminMeta[1], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <?php if ($TabActual === 'inicio'): ?>
                <a href="Logout.php" id="BtnCerrarSesionAdmin" class="SgceHeroBtn SgceHeroLogout" title="Cerrar sesión" aria-label="Cerrar sesión" data-sgce-confirm="logout" data-sgce-confirm-title="CERRAR SESIÓN" data-sgce-confirm-subtitle="SALIDA DEL SISTEMA" data-sgce-confirm-message="¿REALMENTE DESEAS CERRAR SESIÓN?" data-sgce-confirm-detail="Se cerrará tu sesión actual y tendrás que iniciar sesión nuevamente para entrar al sistema." data-sgce-confirm-button="SÍ, CERRAR SESIÓN" data-sgce-confirm-loading="CERRANDO SESIÓN..." data-sgce-confirm-icon="fa-right-from-bracket">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Cerrar sesión</span>
                </a>
            <?php else: ?>
                <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
            <?php endif; ?>
        </div>
    </section>

    <?php if (isset($_SESSION['Mensaje'])): ?>
        <?php
            $MensajeTipo = $_SESSION['MensajeTipo'] ?? 'success';
            $MensajeIcono = ($MensajeTipo === 'danger') ? 'fa-circle-xmark' : 'fa-check-circle';
        ?>
        <div class="alert alert-<?= htmlspecialchars($MensajeTipo, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fa-solid <?= htmlspecialchars($MensajeIcono, ENT_QUOTES, 'UTF-8') ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['Mensaje'], ENT_QUOTES, 'UTF-8') ?>
            <?= function_exists('SgceImportacionReporteBoton') ? SgceImportacionReporteBoton() : '' ?>
            <?php unset($_SESSION['Mensaje'], $_SESSION['MensajeTipo']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<div class="tab-content">



        

        <?php
            $SgceAdminVistaMapa = [
                'inicio' => 'inicio.php',
                'maestros' => 'maestros.php',
                'grupos' => 'grupos.php',
                'expedientes' => 'expedientes.php',
                'alumnos' => 'alumnos.php',
                'materias' => 'materias.php',
                'asignaciones' => 'asignaciones.php',
                'bitacora' => 'bitacora.php',
            ];
            $SgceAdminVistaArchivo = $SgceAdminVistaMapa[$TabActual] ?? $SgceAdminVistaMapa['inicio'];
            $SgceAdminVistaRuta = dirname(__DIR__, 2) . '/views/admin/' . $SgceAdminVistaArchivo;
            if (is_file($SgceAdminVistaRuta)) { require $SgceAdminVistaRuta; }
        ?>
        
    </div>
</div>



<div class="modal fade" id="ModalConfirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ModalEliminarFijo">
        <div class="modal-content DeleteModalContent">
            <div class="DeleteModalHeader">
                <div class="DeleteIcon">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h4 class="fw-bold mb-1">CONFIRMAR ELIMINACIÓN</h4>
                <p class="mb-0 opacity-75" id="DeleteModalTipo">REGISTRO</p>
            </div>
            <div class="DeleteModalBody">
                <p class="fs-6 fw-bold mb-3" id="DeleteModalMensaje">¿DESEAS ELIMINAR ESTE REGISTRO?</p>
                <div class="DeleteWarningBox mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Revisa bien antes de confirmar. Esta acción puede afectar información relacionada.
                </div>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> CANCELAR
                    </button>
                    <button type="button" class="BtnConfirmDelete" id="BtnConfirmarEliminar">
                        <i class="fa-solid fa-trash"></i> SÍ, ELIMINAR
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<?php ImprimirCsrfScript(); ?>
<?= SgceJs('assets/js/sgce-shared.js') ?>
<?= SgceJs('assets/js/Admin.js') ?>
</body>
</html>
