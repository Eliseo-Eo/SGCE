<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutAdminCss($Pdo) ?>
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



<?= SgceLayoutAdminAppJs([], true, true) ?>
</body>
</html>
