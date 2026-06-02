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
<link rel="stylesheet" href="assets/css/sgce-base.min.css?v=sgce">
<link rel="stylesheet" href="assets/css/sgce-soft-motion.css?v=sgce">
<?= SgceEstilosTema($Pdo) ?>
<link rel="stylesheet" href="assets/css/maestros-botones-metalicos.css?v=sgce">
<link rel="stylesheet" href="assets/css/grupos-alumnos-botones-metalicos.css?v=sgce">
<link rel="stylesheet" href="assets/css/asignaciones-botones-metalicos.css?v=sgce">
<link rel="stylesheet" href="assets/css/expedientes-botones-metalicos.css?v=sgce">
<link rel="stylesheet" href="assets/css/dashboard-colores-suaves.css?v=sgce">
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
        $AdminTabMeta = [
            'inicio' => ['SGCE | Administrador', 'Panel principal, accesos rápidos, contadores y alumnos con riesgo.', '🧭'],
            'maestros' => ['Maestros', 'Alta, edición y control de docentes.', '👨‍🏫'],
            'grupos' => ['Grupos', 'Control de grado, grupo y turno.', '👥'],
            'alumnos' => ['Alumnos', 'Inscripciones y administración de estudiantes.', '📚'],
            'expedientes' => ['Expedientes', 'Historial y consulta individual de alumnos.', '📁'],
            'asignaciones' => ['Asignaciones', 'Materias vinculadas con docentes y grupos.', '📖'],
            'bitacora' => ['Bitácora', 'Movimientos importantes realizados en el sistema.', '🛡️']
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
            <?php unset($_SESSION['Mensaje'], $_SESSION['MensajeTipo']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<div class="tab-content">



        
        <?php if ($TabActual === 'inicio'): ?>
<div class="tab-pane fade show active SgceActivePane" id="inicio">
            <?php
                $TarjetasInicio = [
                    ['ALUMNOS ACTIVOS', $TotalAlumnosActivos, 'fa-children', 'var(--SgceAzul)'],
                    ['MAESTROS ACTIVOS', $TotalMaestrosActivos, 'fa-chalkboard-user', 'var(--SgceVerde)'],
                    ['GRUPOS ACTIVOS', $TotalGruposActivos, 'fa-users-rectangle', 'var(--SgceAmarillo)'],
                    ['ASISTENCIAS HOY', $AsistenciasHoy, 'fa-calendar-check', 'var(--SgceGuinda)'],
                    ['FALTAS HOY', $FaltasHoy, 'fa-circle-xmark', 'var(--SgceRojo)'],
                    ['PROMEDIO GENERAL', $PromedioGeneral, 'fa-star', '#7C3AED']
                ];
            ?>

            <?php
                $DashboardFechaHoy = date('d/m/Y');
                $DashboardCicloNombre = trim((string)($CicloActivo['Nombre'] ?? 'CICLO NO CONFIGURADO'));
                $DashboardEstadoTexto = 'Sin pases de lista hoy';
                $DashboardEstadoClase = 'StatusNeutral';
                if ((int)$AsistenciasHoy > 0 && (int)$FaltasHoy > 0) {
                    $DashboardEstadoTexto = 'Actividad con incidencias';
                    $DashboardEstadoClase = 'StatusWarning';
                } elseif ((int)$AsistenciasHoy > 0) {
                    $DashboardEstadoTexto = 'Asistencia registrada';
                    $DashboardEstadoClase = 'StatusOk';
                }

                $DashboardKpis = [
                    ['Alumnos activos', $TotalAlumnosActivos, '📚', 'KpiBlue', 'Inscritos vigentes'],
                    ['Maestros activos', $TotalMaestrosActivos, '👨‍🏫', 'KpiGreen', 'Docentes disponibles'],
                    ['Grupos activos', $TotalGruposActivos, '👥', 'KpiCyan', 'Grado, grupo y turno'],
                    ['Asistencias hoy', $AsistenciasHoy, '✅', 'KpiAttendance', 'Registros del día'],
                    ['Faltas hoy', $FaltasHoy, '❌', 'KpiSoftRed', 'Incidencias actuales'],
                    ['Promedio general', $PromedioGeneral, '⭐', 'KpiGold', 'Ciclo activo']
                ];
            ?>

            <div class="DashboardTopPro">
                <section class="DashboardAccessPanel">
                    <div class="DashboardSectionHeader">
                        <div>
                            <span class="DashboardSectionKicker"><i class="fa-solid fa-grip"></i> Accesos rápidos</span>
                            <h2>Panel principal</h2>
                        </div>
                        <span class="DashboardMiniBadge"><?= $EsAdmin ? '14 módulos' : '9 módulos' ?></span>
                    </div>

                    <div class="DashboardModuleGridPro ModulosRecomendados">
                        <a href="AvisosAdmin.php" class="DashboardModuleCard DashboardModuleAnuncios">
                            <span class="SgceColorIcon" aria-hidden="true">📣</span>
                            <span>Anuncios</span>
                            <small>Comunicados</small>
                        </a>
                        <?php if (SgcePuedeAdministrarPeriodos($UserSession)): ?>
                        <a href="PeriodosAdmin.php" class="DashboardModuleCard DashboardModulePeriodos">
                            <span class="SgceColorIcon" aria-hidden="true">📅</span>
                            <span>Periodos</span>
                            <small>Ciclos</small>
                        </a>
                        <?php endif; ?>
                        <a href="Admin.php?Tab=maestros" class="DashboardModuleCard DashboardModuleMaestros">
                            <span class="SgceColorIcon" aria-hidden="true">👨‍🏫</span>
                            <span>Maestros</span>
                            <small>Docentes</small>
                        </a>
                        <a href="Admin.php?Tab=grupos" class="DashboardModuleCard DashboardModuleGrupos">
                            <span class="SgceColorIcon" aria-hidden="true">👥</span>
                            <span>Grupos</span>
                            <small>Grado y turno</small>
                        </a>
                        <a href="Admin.php?Tab=alumnos" class="DashboardModuleCard DashboardModuleAlumnos">
                            <span class="SgceColorIcon" aria-hidden="true">📚</span>
                            <span>Alumnos</span>
                            <small>Inscripciones</small>
                        </a>
                        <a href="Admin.php?Tab=asignaciones" class="DashboardModuleCard DashboardModuleAsignaciones">
                            <span class="SgceColorIcon" aria-hidden="true">📖</span>
                            <span>Asignaciones</span>
                            <small>Materias</small>
                        </a>
                        <a href="Admin.php?Tab=expedientes" class="DashboardModuleCard DashboardModuleExpedientes">
                            <span class="SgceColorIcon" aria-hidden="true">📁</span>
                            <span>Expedientes</span>
                            <small>Alumnos</small>
                        </a>
                        <a href="ReportesAdmin.php" class="DashboardModuleCard DashboardModuleReportes">
                            <span class="SgceColorIcon" aria-hidden="true">📈</span>
                            <span>Reportes</span>
                            <small>Centro</small>
                        </a>
                        <a href="PlaneacionesAdmin.php" class="DashboardModuleCard DashboardModulePlaneaciones">
                            <span class="SgceColorIcon" aria-hidden="true">☁️</span>
                            <span>Planeaciones</span>
                            <small>Docentes</small>
                        </a>
                        <a href="ConsultaPadre.php" class="DashboardModuleCard DashboardModuleAsistencias">
                            <span class="SgceColorIcon" aria-hidden="true">📅</span>
                            <span>Asistencias</span>
                            <small>Consulta individual</small>
                        </a>
                        <?php if (SgcePuedeGestionarUsuarios($UserSession)): ?>
                        <a href="UsuariosAdmin.php" class="DashboardModuleCard DashboardModuleUsuarios">
                            <span class="SgceColorIcon" aria-hidden="true">⚙️</span>
                            <span>Usuarios</span>
                            <small>Roles</small>
                        </a>
                        <?php endif; ?>
                        <?php if (SgcePuedeConfigurarSistema($UserSession)): ?>
                        <a href="ConfiguracionAdmin.php" class="DashboardModuleCard DashboardModuleConfiguracion">
                            <span class="SgceColorIcon" aria-hidden="true">🏫</span>
                            <span>Configuración</span>
                            <small>Escuela</small>
                        </a>
                        <?php endif; ?>
                        <?php if (SgcePuedeRespaldos($UserSession)): ?>
                        <a href="RestaurarBD.php" class="DashboardModuleCard DashboardModuleRespaldos">
                            <span class="SgceColorIcon" aria-hidden="true">💾</span>
                            <span>Respaldos</span>
                            <small>Datos</small>
                        </a>
                        <?php endif; ?>
                        <?php if (SgcePuedeBitacora($UserSession)): ?>
                        <a href="Admin.php?Tab=bitacora" class="DashboardModuleCard DashboardModuleBitacora">
                            <span class="SgceColorIcon" aria-hidden="true">🛡️</span>
                            <span>Bitácora</span>
                            <small>Movimientos</small>
                        </a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="DashboardSummaryPanel">
                    <div class="DashboardSectionHeader">
                        <div>
                            <span class="DashboardSectionKicker"><i class="fa-solid fa-chart-simple"></i> Monitoreo escolar</span>
                            <h2>Resumen general</h2>
                        </div>
                        <span class="DashboardMiniBadge DashboardCycleBadge"><?= htmlspecialchars($DashboardCicloNombre, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="DashboardTodayStrip">
                        <div class="DashboardTodayIcon"><span class="SgceColorIcon" aria-hidden="true">📅</span></div>
                        <div class="DashboardTodayText">
                            <span>Estado del día</span>
                            <strong><?= htmlspecialchars($DashboardFechaHoy, ENT_QUOTES, 'UTF-8') ?></strong>
                            <small>Resumen operativo del ciclo activo</small>
                        </div>
                        <div class="DashboardStatusPill <?= htmlspecialchars($DashboardEstadoClase, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($DashboardEstadoTexto, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="DashboardKpiGrid">
                        <?php foreach($DashboardKpis as $Kpi): ?>
                            <article class="DashboardKpiCard <?= htmlspecialchars($Kpi[3], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="DashboardKpiIcon"><span class="SgceColorIcon" aria-hidden="true"><?= htmlspecialchars($Kpi[2], ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="DashboardKpiValue"><?= htmlspecialchars((string)$Kpi[1], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="DashboardKpiLabel"><?= htmlspecialchars($Kpi[0], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="DashboardKpiHint"><?= htmlspecialchars($Kpi[4], ENT_QUOTES, 'UTF-8') ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="row g-3 DashboardRiskRow">

                <div class="col-12">
                    <section class="card card-custom DashboardRiskPro">
                        <div class="DashboardRiskHeader">
                            <div>
                                <span class="DashboardSectionKicker DashboardRiskKicker"><i class="fa-solid fa-triangle-exclamation"></i> Seguimiento preventivo</span>
                                <h2>Alumnos con mayor riesgo académico y de asistencia</h2>
                                <p>
                                    El riesgo se calcula con faltas, retardos y promedio menor a 7 dentro del ciclo activo.
                                </p>
                            </div>
                            <span class="DashboardRiskCount"><?= count($AlumnosRiesgo) ?> registros</span>
                        </div>

                        <?php if (count($AlumnosRiesgo) === 0): ?>
                            <div class="DashboardRiskEmpty">
                                <div class="DashboardRiskEmptyIcon"><span class="SgceColorIcon" aria-hidden="true">✅</span></div>
                                <div>
                                    <strong>Sin alumnos en riesgo por ahora</strong>
                                    <span>Cuando existan faltas, retardos o promedios bajos, aparecerán aquí automáticamente.</span>
                                </div>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive DashboardRiskTableWrap">
                            <table class="table table-hover align-middle text-center DashboardRiskTable">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <th>Prom.</th>
                                        <th>Faltas</th>
                                        <th>Retardos</th>
                                        <th>Nivel</th>
                                        <th>Motivo</th>
                                        <th>Puntos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($AlumnosRiesgo as $R): ?>
                                    <?php
                                        $ClaseNivelRiesgo = 'bg-warning text-dark';
                                        if ($R['NivelRiesgo'] === 'ALTO') { $ClaseNivelRiesgo = 'bg-danger'; }
                                        if ($R['NivelRiesgo'] === 'MEDIO') { $ClaseNivelRiesgo = 'bg-warning text-dark'; }
                                        if ($R['NivelRiesgo'] === 'BAJO') { $ClaseNivelRiesgo = 'bg-info text-dark'; }
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($R['NombreCompleto']) ?></td>
                                        <td><?= htmlspecialchars($R['Grado'].' '.$R['Grupo']) ?></td>
                                        <td><span class="badge <?= $R['Turno']==='MATUTINO'?'bg-primary':'bg-warning text-dark' ?>"><?= htmlspecialchars($R['Turno']) ?></span></td>
                                        <td><span class="badge <?= ($R['Promedio'] !== null && (float)$R['Promedio'] < 7) ? 'bg-danger' : 'bg-success' ?>"><?= $R['Promedio'] !== null ? htmlspecialchars($R['Promedio']) : 'S/C' ?></span></td>
                                        <td><span class="badge bg-danger"><?= (int)$R['Faltas'] ?></span></td>
                                        <td><span class="badge bg-warning text-dark"><?= (int)$R['Retardos'] ?></span></td>
                                        <td><span class="badge <?= $ClaseNivelRiesgo ?>"><?= htmlspecialchars($R['NivelRiesgo']) ?></span></td>
                                        <td><span class="badge bg-dark"><?= htmlspecialchars($R['MotivoRiesgo'] !== '' ? $R['MotivoRiesgo'] : 'SIN MOTIVO') ?></span></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($R['PuntajeRiesgo']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>

        

        
<?php endif; ?>
<?php if ($TabActual === 'maestros'): ?>
<div class="tab-pane fade show active SgceActivePane" id="maestros">
            <div class="row MaestrosLayoutRow GruposLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol GruposSideCol">

                    <div class="card card-custom MaestrosSideCard GruposSideCard MaestrosRegisterCard GruposRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle GruposCardTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">👨‍🏫</span> Registrar Maestro
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack GruposFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaMaestro">
                                <input type="hidden" name="Tab" value="maestros">

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Nombre completo</label>
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control form-control-sm SoloLetrasMayus MaestrosInput GruposInput"
                                           placeholder="NOMBRE COMPLETO"
                                           maxlength="140"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Usuario</label>
                                    <input type="text" name="User" class="form-control form-control-sm TextoLibre MaestrosInput GruposInput" placeholder="USUARIO" maxlength="80" required autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Contraseña</label>
                                    <input type="password" name="Pass" class="form-control form-control-sm TextoLibre MaestrosInput GruposInput" placeholder="CONTRASEÑA" required autocomplete="off">
                                </div>

                                <button type="submit" id="BtnGuardarMaestroVerdeMetalico" class="BtnMaestroGuardarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">💾</span> Guardar Maestro
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard GruposSideCard MaestrosImportCard GruposImportCard">
                        <div class="card-header-custom MaestrosCardTitle MaestrosImportTitle GruposCardTitle GruposImportTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📊</span> Importar CSV / Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack GruposFormStack" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR DOCENTES" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE ARCHIVO DE DOCENTES?" data-sgce-confirm-detail="Se procesará el archivo seleccionado para registrar docentes. Revisa que el formato sea NOMBRE, USUARIO, CONTRASEÑA antes de continuar." data-sgce-confirm-button="SÍ, IMPORTAR DOCENTES" data-sgce-confirm-loading="IMPORTANDO DOCENTES..." data-sgce-confirm-icon="fa-file-excel">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarDocentes" value="1">
                                <input type="hidden" name="Tab" value="maestros">

                                <p class="MaestrosHelpText GruposHelpText">
                                    FORMATO CSV O EXCEL: <code>NOMBRE, USUARIO, CONTRASEÑA</code>
                                </p>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvDocentes" class="form-control form-control-sm MaestrosInput MaestrosFileInput GruposInput" accept=".csv,.xlsx" required>
                                </div>

                                <button type="submit" id="BtnImportarMaestroAzulMetalico" class="BtnMaestroImportarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">☁️</span> Cargar Archivo
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol GruposTableCol">

                    <div class="card card-custom p-3 MaestrosTableCard GruposTableCard">

                        <div class="d-flex justify-content-between align-items-center mb-3 MaestrosTableTop GruposTableTop">
                            <h6 class="mb-0 text-muted SgceInlineTitle"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">👨‍🏫</span><span>Docentes Registrados</span></h6>

                            <div class="input-group input-group-sm search-container w-50">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>

                                <input type="text" id="SearchMaestros" class="form-control" placeholder="Buscar docente o usuario...">
                            </div>
                        </div>

                        <div class="table-responsive">

                            <table class="table table-hover text-center align-middle" id="TableMaestros">

                                <thead>
                                    <tr>
                                        <th class="text-start">Nombre</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach($MaestrosTabla as $M): ?>
                                    <tr>
                                        <td class="text-start searchable"><?= htmlspecialchars($M['NombreCompleto']) ?></td>
                                        <td class="searchable"><?= htmlspecialchars($M['Username']) ?></td>

                                        <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit BtnTeacherEdit" data-bs-toggle="modal" data-bs-target="#EM<?= $M['Id'] ?>">
                                                <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                            </button>

                                            <form method="POST" class="m-0 p-0" data-confirm-delete="DOCENTE" data-confirm-message="¿DESEAS ELIMINAR ESTE DOCENTE? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                    <?php echo CampoCsrf(); ?>
                                                <input type="hidden" name="Tab" value="maestros">
                                                <button type="submit" name="DelMaestro" value="<?= $M['Id'] ?>" class="ActionBtn ActionDelete BtnTeacherDelete">
                                                    <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                        
                        <?= SgceRenderPager('PagMaestros', $PagMaestros, $TotalMaestrosTabla, $PageSizeAdmin, ['Tab'=>'maestros']) ?>

                    </div>

                </div>

            </div>

            <?php foreach($MaestrosTabla as $M): ?>
            <div class="modal fade" id="EM<?= $M['Id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">

                        <form method="POST">
                    <?php echo CampoCsrf(); ?>
                            <div class="modal-body">

                                <h6 class="mb-3 border-bottom pb-2">Modificar Docente</h6>

                                <input type="hidden" name="EditMaestro">
                                <input type="hidden" name="Tab" value="maestros">
                                <input type="hidden" name="Id" value="<?= $M['Id'] ?>">

                                <label class="small text-muted">Nombre</label>
                                <input type="text"
                                       name="Nombre"
                                       value="<?= htmlspecialchars($M['NombreCompleto']) ?>"
                                       class="form-control form-control-sm mb-2 SoloLetrasMayus"
                                       required
                                       pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                       title="Solo letras y espacios"
                                       autocomplete="off">

                                <label class="small text-muted">USUARIO</label>
                                <input type="text"
                                       name="User"
                                       value="<?= htmlspecialchars($M['Username']) ?>"
                                       class="form-control form-control-sm mb-2 TextoLibre"
                                       required
                                       autocomplete="off">

                                <label class="small text-muted">CONTRASEÑA</label>
                                <input type="password"
                                       name="Pass"
                                       value="" placeholder="NUEVA CONTRASEÑA OPCIONAL"
                                       class="form-control form-control-sm mb-3 TextoLibre"
                                       autocomplete="off">

                                <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        

        
<?php endif; ?>
<?php if ($TabActual === 'grupos'): ?>
<div class="tab-pane fade show active SgceActivePane" id="grupos">
            <div class="row MaestrosLayoutRow GruposLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol GruposSideCol">

                    <div class="card card-custom MaestrosSideCard GruposSideCard GruposRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle GruposCardTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">👥</span> Crear Grupo
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack GruposFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaGrupo">
                                <input type="hidden" name="Tab" value="grupos">

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Grado</label>
                                    <input type="text"
                                           name="Grado"
                                           maxlength="20"
                                           class="form-control form-control-sm MaestrosInput GruposInput InputDigits"
                                           placeholder="GRADO (EJ: 1)"
                                           required
                                           inputmode="numeric"
                                           pattern="^\d+$"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Grupo</label>
                                    <input type="text"
                                           name="Grupo"
                                           class="form-control form-control-sm MaestrosInput GruposInput InputUpperAscii"
                                           placeholder="GRUPO (EJ: A)"
                                           required
                                           pattern="^[A-Z]+$"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Turno</label>
                                    <select name="Turno" class="form-select form-select-sm MaestrosInput GruposInput" required>
                                        <option value="">SELECCIONA TURNO...</option>
                                        <option value="MATUTINO">MATUTINO</option>
                                        <option value="VESPERTINO">VESPERTINO</option>
                                    </select>
                                </div>

                                <button type="submit" id="BtnGuardarGrupoVerdeMetalico" class="BtnGrupoGuardarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">👥</span> Guardar Grupo
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard GruposSideCard GruposImportCard">
                        <div class="card-header-custom MaestrosCardTitle MaestrosImportTitle GruposCardTitle GruposImportTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📊</span> Importar CSV / Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack GruposFormStack" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR GRUPOS" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE ARCHIVO DE GRUPOS?" data-sgce-confirm-detail="Se procesará el archivo seleccionado para registrar grupos. Revisa grado, grupo y turno antes de continuar." data-sgce-confirm-button="SÍ, IMPORTAR GRUPOS" data-sgce-confirm-loading="IMPORTANDO GRUPOS..." data-sgce-confirm-icon="fa-file-excel">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarGrupos" value="1">
                                <input type="hidden" name="Tab" value="grupos">

                                <p class="MaestrosHelpText GruposHelpText">
                                    FORMATO CSV O EXCEL: <code>GRADO, GRUPO, TURNO</code><br>
                                    EJEMPLO: <code>1, C, VESPERTINO</code>
                                </p>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvGrupos" class="form-control form-control-sm MaestrosInput MaestrosFileInput GruposInput" accept=".csv,.xlsx" required>
                                </div>

                                <button type="submit" id="BtnImportarGrupoAzulMetalico" class="BtnGrupoImportarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">☁️</span> Cargar Archivo
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol GruposTableCol">
                    <div class="card card-custom p-3 MaestrosTableCard GruposTableCard">

                        <div class="d-flex justify-content-between align-items-center mb-3 MaestrosTableTop GruposTableTop">
                            <h6 class="mb-0 text-muted SgceInlineTitle"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">👥</span><span>Grupos Existentes</span></h6>

                            <div class="input-group input-group-sm search-container w-50">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="SearchGrupos" class="form-control" placeholder="Buscar grupo o turno...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover text-center align-middle" id="TableGrupos">
                                <thead>
                                    <tr>
                                        <th>Grado</th>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <th class="text-center">Calif.</th>
                                        <th class="text-center">Asis. Hoy</th>
                                        <th class="text-center">Asis. Todas</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach($GruposTabla as $G): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($G['Grado']) ?></td>
                                        <td class="searchable"><span class="GruposGrupoBadge"><?= htmlspecialchars($G['Grupo']) ?></span></td>

                                        <td class="searchable">
                                            <span class="GruposTurnoBadge">
                                                <?= htmlspecialchars($G['Turno']) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel" target="_blank" rel="noopener noreferrer" title="Calificaciones del grupo en Excel" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf" target="_blank" rel="noopener noreferrer" title="Calificaciones del grupo en PDF" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportHoy" target="_blank" rel="noopener noreferrer" title="Asistencias de hoy del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportHoy" target="_blank" rel="noopener noreferrer" title="Asistencias de hoy del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportTodas" target="_blank" rel="noopener noreferrer" title="Todas las asistencias del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportTodas" target="_blank" rel="noopener noreferrer" title="Todas las asistencias del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="AdminActions">
                                                <button class="ActionBtn ActionEdit BtnGroupEdit" data-bs-toggle="modal" data-bs-target="#EG<?= $G['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="GRUPO" data-confirm-message="¿DESEAS ELIMINAR ESTE GRUPO? SI TIENE DATOS RELACIONADOS, EL SISTEMA PUEDE IMPEDIRLO.">
                    <?php echo CampoCsrf(); ?>
                                                    <input type="hidden" name="Tab" value="grupos">
                                                    <button type="submit" name="DelGrupo" value="<?= $G['Id'] ?>" class="ActionBtn ActionDelete BtnGroupDelete">
                                                        <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?= SgceRenderPager('PagGrupos', $PagGrupos, $TotalGruposTabla, $PageSizeAdmin, ['Tab'=>'grupos']) ?>

                    </div>
                </div>

                <?php foreach($GruposTabla as $G): ?>
                <div class="modal fade" id="EG<?= $G['Id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content">

                            <form method="POST">
                    <?php echo CampoCsrf(); ?>
                                <div class="modal-body">

                                    <h6 class="mb-3 border-bottom pb-2">Modificar Grupo</h6>

                                    <input type="hidden" name="EditGrupo">
                                    <input type="hidden" name="Tab" value="grupos">
                                    <input type="hidden" name="Id" value="<?= $G['Id'] ?>">

                                    <label class="small text-muted">Grado</label>
                                    <input type="text"
                                           name="Grado"
                                           value="<?= htmlspecialchars($G['Grado']) ?>"
                                           class="form-control form-control-sm mb-2 InputDigits"
                                           required
                                           inputmode="numeric"
                                           pattern="^\d+$">

                                    <label class="small text-muted">Grupo</label>
                                    <input type="text"
                                           name="Grupo"
                                           value="<?= htmlspecialchars($G['Grupo']) ?>"
                                           class="form-control form-control-sm mb-2 InputUpperAscii"
                                           required
                                           pattern="^[A-Z]+$">

                                    <label class="small text-muted">Turno</label>
                                    <select name="Turno" class="form-select form-select-sm mb-3" required>
                                        <option value="MATUTINO" <?= strtoupper((string)$G['Turno']) === 'MATUTINO' ? 'selected' : '' ?>>MATUTINO</option>
                                        <option value="VESPERTINO" <?= strtoupper((string)$G['Turno']) === 'VESPERTINO' ? 'selected' : '' ?>>VESPERTINO</option>
                                    </select>

                                    <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        

        
<?php endif; ?>
<?php if ($TabActual === 'expedientes'): ?>
<div class="tab-pane fade show active SgceActivePane" id="expedientes">
            <div class="card card-custom ExpedientesCard">
                <div class="card-body ExpedientesCardBody">

                    <div class="ExpedientesTop">
                        <div class="ExpedientesTitleBlock">
                            <span class="ExpedientesTitleIcon"><span class="SgceColorIcon" aria-hidden="true">📁</span></span>
                            <div>
                                <h4>Expedientes de Alumnos</h4>
                                <p>Selecciona un grupo para consultar solo el padrón correspondiente y abrir el historial individual.</p>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="Admin.php" class="ExpedientesFilterForm">
                        <input type="hidden" name="Tab" value="expedientes">
                        <div class="ExpedientesFilterGrid <?= $ExpedienteGrupoId > 0 ? 'HasCleanButton' : '' ?>">
                            <div class="ExpedientesGroupField">
                                <label>Grado / Grupo / Turno</label>
                                <select name="ExpGrupoId" class="form-select" required>
                                    <option value="">SELECCIONA GRUPO...</option>
                                    <?php foreach($Grupos as $GExp): ?>
                                        <option value="<?= (int)$GExp['Id'] ?>" <?= ((int)$ExpedienteGrupoId === (int)$GExp['Id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($GExp['Grado'].' '.$GExp['Grupo'].' - '.$GExp['Turno'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="ExpedientesActionField">
                                <label>Acción</label>
                                <button type="submit" id="BtnCargarExpedientesVerdeMetalico" class="BtnExpedienteLoadVerdeMetalico">
                                    <span aria-hidden="true">📂</span><span>Cargar Expedientes</span>
                                </button>
                            </div>

                            <?php if($ExpedienteGrupoId > 0): ?>
                            <div class="ExpedientesActionField">
                                <label>Restablecer</label>
                                <a href="Admin.php?Tab=expedientes" class="ActionBtn BtnExpedienteClean">
                                    <i class="fa-solid fa-eraser"></i><span>Limpiar</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if($ExpedienteGrupoId <= 0): ?>
                        <div class="ExpedientesEmptyState">
                            <span><i class="fa-solid fa-circle-info"></i></span>
                            <div>
                                <strong>Selecciona un grupo para cargar expedientes.</strong>
                                <p>Así el sistema evita consultar todos los alumnos y mantiene la pantalla rápida y ordenada.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="ExpedientesTools">
                            <div class="ExpedientesSelectedGroup">
                                <i class="fa-solid fa-users"></i>
                                <span>Grupo seleccionado:</span>
                                <strong>
                                    <?= $GrupoExpedienteSeleccionado
                                        ? htmlspecialchars($GrupoExpedienteSeleccionado['Grado'].' '.$GrupoExpedienteSeleccionado['Grupo'].' - '.$GrupoExpedienteSeleccionado['Turno'], ENT_QUOTES, 'UTF-8')
                                        : 'NO DISPONIBLE' ?>
                                </strong>
                            </div>

                            <div class="input-group search-container ExpedientesSearch">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="SearchExpedientes" class="form-control" placeholder="Buscar expediente...">
                            </div>
                        </div>

                        <div class="table-responsive ExpedientesTableWrap">
                            <table class="table table-hover align-middle" id="TableExpedientes">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th class="text-center">Grupo</th>
                                        <th class="text-center">Turno</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($AlumnosExpedientes as $Al): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="searchable text-center">
                                            <span class="ExpedientesGroupBadge"><?= htmlspecialchars($Al['Grado'].' '.$Al['Grupo'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="searchable text-center">
                                            <span class="ExpedientesTurnBadge"><?= htmlspecialchars($Al['Turno'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a class="ActionBtn BtnExpedienteOpen" href="HistorialAlumno.php?AlumnoId=<?= $Al['Id'] ?>">
                                                <span class="SgceColorIcon" aria-hidden="true">📁</span><span>Abrir Expediente</span>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if(count($AlumnosExpedientes) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="ExpedientesNoData">NO HAY ALUMNOS ACTIVOS EN ESTE GRUPO.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="PagerExpedientes" class="SgcePagerServer ExpedientesPager"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        

        
<?php endif; ?>
<?php if ($TabActual === 'alumnos'): ?>
<div class="tab-pane fade show active SgceActivePane" id="alumnos">
            <div class="row MaestrosLayoutRow AlumnosLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol AlumnosSideCol">

                    <div class="card card-custom MaestrosSideCard AlumnosSideCard AlumnosRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle AlumnosCardTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📚</span> Inscribir Alumno
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack AlumnosFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaAlumno">
                                <input type="hidden" name="Tab" value="alumnos">

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Nombre completo</label>
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control form-control-sm MaestrosInput AlumnosInput SoloLetrasMayus"
                                           placeholder="NOMBRE COMPLETO"
                                           maxlength="160"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Grupo</label>
                                    <select name="GrupoId" class="form-select form-select-sm MaestrosInput AlumnosInput" required>
                                        <option value="">SELECCIONAR...</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= $G['Id'] ?>">
                                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" id="BtnGuardarAlumnoVerdeMetalico" class="BtnAlumnoGuardarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">🎓</span> Registrar Alumno
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard AlumnosSideCard AlumnosImportCard">
                        <div class="card-header-custom MaestrosCardTitle AlumnosCardTitle AlumnosImportTitle">
                            <span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">📊</span> Importar Datos
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack AlumnosFormStack" data-sgce-confirm="import" data-sgce-confirm-title="CONFIRMAR IMPORTACIÓN" data-sgce-confirm-subtitle="IMPORTAR ALUMNOS" data-sgce-confirm-message="¿REALMENTE DESEAS IMPORTAR ESTE ARCHIVO DE ALUMNOS?" data-sgce-confirm-detail="Se registrarán los alumnos en el grupo seleccionado. Confirma que elegiste el grupo correcto y que el archivo corresponde a ese grupo." data-sgce-confirm-button="SÍ, IMPORTAR ALUMNOS" data-sgce-confirm-loading="IMPORTANDO ALUMNOS..." data-sgce-confirm-icon="fa-users">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarAlumnos" value="1">
                                <input type="hidden" name="Tab" value="alumnos">

                                <p class="MaestrosHelpText AlumnosHelpText">
                                    Selecciona el grupo destino y carga un archivo CSV o Excel con nombres de alumnos.
                                </p>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Grupo destino</label>
                                    <select name="GrupoId" class="form-select form-select-sm MaestrosInput AlumnosInput" required>
                                        <option value="">¿A DÓNDE VAN?</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= $G['Id'] ?>">
                                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvAlumnos" class="form-control form-control-sm MaestrosInput MaestrosFileInput AlumnosInput AlumnosFileInput" accept=".csv,.xlsx" required>
                                </div>

                                <button type="submit" id="BtnImportarAlumnoAzulMetalico" class="BtnAlumnoImportarMetalico w-100">
                                    <span class="SgceColorIcon" aria-hidden="true">☁️</span> Cargar Archivo
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol AlumnosTableCol">

                    <div class="card card-custom p-3 AlumnosTableCard">

                        <div class="d-flex justify-content-between align-items-center mb-3 AlumnosTableTop">
                            <div>
                                <h6 class="mb-0 text-muted SgceInlineTitle"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🎓</span><span>Padrón de Alumnos</span></h6>
                                <small class="text-muted fw-semibold">Consulta, expediente y administración de estudiantes.</small>
                            </div>

                            <div class="input-group input-group-sm search-container w-50">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                                <input type="text" id="SearchAlumnos" class="form-control" placeholder="Buscar alumno...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="TableAlumnos">

                                <thead>
                                    <tr>
                                        <th>Nombre del Alumno</th>
                                        <th class="text-center">Grupo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach($Alumnos as $Al): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($Al['NombreCompleto']) ?></td>

                                        <td class="searchable text-center">
                                            <?= $Al['Grado']
                                                ? "<span class='badge AlumnosGroupBadge'>".$Al['Grado']." ".$Al['Grupo']."</span>"
                                                : '<span class="text-danger small fw-bold">Sin Grupo</span>' ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="AdminActions AlumnosActions">
                                                <a class="ActionBtn BtnStudentFile" href="HistorialAlumno.php?AlumnoId=<?= $Al['Id'] ?>">
                                                    <span class="SgceColorIcon" aria-hidden="true">📁</span><span>Expediente</span>
                                                </a>

                                                <button class="ActionBtn BtnStudentEdit" data-bs-toggle="modal" data-bs-target="#EAl<?= $Al['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="ALUMNO" data-confirm-message="¿DESEAS DAR DE BAJA A ESTE ALUMNO? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                    <?php echo CampoCsrf(); ?>
                                                    <input type="hidden" name="Tab" value="alumnos">
                                                    <button type="submit" name="DelAlumno" value="<?= $Al['Id'] ?>" class="ActionBtn BtnStudentDelete">
                                                        <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                        <?= SgceRenderPager('PagAlumnos', $PagAlumnos, $TotalAlumnosTabla, $PageSizeAlumnos, ['Tab'=>'alumnos']) ?>

                    </div>

                </div>

            </div>
        </div>

        <?php foreach($Alumnos as $Al): ?>
        <div class="modal fade" id="EAl<?= $Al['Id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <form method="POST">
                    <?php echo CampoCsrf(); ?>
                        <div class="modal-body">

                            <h5 class="mb-4">Editar Alumno</h5>

                            <input type="hidden" name="EditAlumno">
                            <input type="hidden" name="Tab" value="alumnos">
                            <input type="hidden" name="Id" value="<?= $Al['Id'] ?>">

                            <div class="mb-3">
                                <label class="small">Nombre</label>
                                <input type="text"
                                       name="Nombre"
                                       value="<?= htmlspecialchars($Al['NombreCompleto']) ?>"
                                       class="form-control SoloLetrasMayus"
                                       maxlength="160"
                                       required
                                       pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                       title="Solo letras y espacios"
                                       autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label class="small">Grupo</label>
                                <select name="GrupoId" class="form-select" required>
                                    <?php foreach($Grupos as $G): ?>
                                        <option value="<?= $G['Id'] ?>" <?= $G['Id'] == $Al['GrupoId'] ? 'selected' : '' ?>>
                                            <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                        </div>
                    </form>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

        

        
<?php endif; ?>
<?php if ($TabActual === 'asignaciones'): ?>
<div class="tab-pane fade show active SgceActivePane" id="asignaciones">
            <div class="card card-custom shadow-sm border-0 AsignacionesTableCard">

                <div class="card-header bg-white py-3 border-bottom AsignacionesHeaderCard">
                    <h6 class="mb-0 fw-bold text-dark">
                        <span class="SgceColorIcon SgceTitleIcon me-2" aria-hidden="true">🔗</span>
                        Nueva Asignación Académica
                    </h6>
                </div>

                <div class="card-body p-4 AsignacionesCardBody">

                    <form method="POST" class="row g-3 align-items-end mb-4 AsignacionForm">
                    <?php echo CampoCsrf(); ?>
                        <input type="hidden" name="AltaAsignacion">
                        <input type="hidden" name="Tab" value="asignaciones">

                        <div class="col-md-4">
                            <label class="small fw-bold text-muted">Seleccionar Docente</label>
                            <select name="MaestroId" class="form-select" required>
                                <option value="">Elegir profesor...</option>
                                <?php foreach($Maestros as $M): ?>
                                    <option value="<?= $M['Id'] ?>"><?= htmlspecialchars($M['NombreCompleto']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Asignar Grupo</label>
                            <select name="GrupoId" class="form-select" required>
                                <option value="">Elegir grupo...</option>
                                <?php foreach($Grupos as $G): ?>
                                    <option value="<?= $G['Id'] ?>">
                                        <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Nombre de la Materia</label>
                            <input type="text" name="Materia" class="form-control" placeholder="EJ: MATEMÁTICAS I" maxlength="140" required>
                        </div>

                        <div class="col-md-2 AsignacionButtonCol">
                            <label class="small fw-bold text-muted d-block">Acción</label>
                            <button type="submit" id="BtnVincularAsignacionVerdeMetalico" class="w-100 fw-bold BtnAsignacionVincularMetalico" aria-label="Vincular asignación académica">
                                <i class="fa-solid fa-link"></i><span>Vincular</span>
                            </button>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-3 border-top pt-4">
                        <h6 class="mb-0 fw-bold text-secondary">Cargas Académicas Activas</h6>

                        <div class="input-group search-container w-25">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="SearchAsig" class="form-control border-start-0" placeholder="Buscar carga...">
                        </div>
                    </div>

                    <div class="table-responsive AsignacionesTableWrap">
                        <table class="table table-hover align-middle" id="TableAsig">

                            <thead class="table-light">
                                <tr>
                                    <th>Docente</th>
                                    <th>Materia</th>
                                    <th>Grupo</th>
                                    <th class="text-center">Calif.</th>
                                    <th class="text-center">Asis. Hoy</th>
                                    <th class="text-center">Asis. Todas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
        <?php foreach($Asignaciones as $Asg): ?>
                                <tr>
                                    <td class="searchable fw-medium"><?= htmlspecialchars($Asg['Maestro']) ?></td>

                                    <td class="searchable">
                                        <span class="AsignacionMateriaTexto" title="<?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>

                                    <td class="searchable AsignacionGrupoTd">
                                        <?php
                                            $TurnoAsignacion = strtoupper((string)$Asg['Turno']);
                                            $GrupoAsignacionEtiqueta = trim((string)$Asg['Grado'].'°'.(string)$Asg['Grupo'].' '.$TurnoAsignacion);
                                        ?>
                                        <span class="AsignacionGrupoBadgeFull" title="<?= htmlspecialchars($GrupoAsignacionEtiqueta, ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($GrupoAsignacionEtiqueta, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>

                                    
                                    <td class="text-center">
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar calificaciones en Excel"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar calificaciones en PDF"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    
                                    <td class="text-center">
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel ExportHoy"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar asistencias de hoy en Excel"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportHoy"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar asistencias de hoy en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    
                                    <td class="text-center">
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel ExportTodas"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar todas las asistencias en Excel"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportTodas"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Exportar todas las asistencias en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit BtnAsignacionEdit" data-bs-toggle="modal" data-bs-target="#EAsg<?= $Asg['Id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                        </button>

                                        <form method="POST" class="m-0 p-0" data-confirm-delete="ASIGNACIÓN" data-confirm-message="¿DESEAS ELIMINAR ESTA ASIGNACIÓN ACADÉMICA?">
                    <?php echo CampoCsrf(); ?>
                                            <input type="hidden" name="Tab" value="asignaciones">
                                            <button type="submit" name="DelAsignacion" value="<?= $Asg['Id'] ?>" class="ActionBtn ActionDelete BtnAsignacionDelete">
                                                <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                            </button>
                                        </form>
                                            </div>
                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                    
                    <?= SgceRenderPager('PagAsig', $PagAsig, $TotalAsignacionesTabla, $PageSizeAsignaciones, ['Tab'=>'asignaciones']) ?>

                </div>
            </div>
        </div>

        <?php foreach($Asignaciones as $Asg): ?>
        <div class="modal fade" id="EAsg<?= $Asg['Id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered ModalEditarPro">
                <div class="modal-content">

                    <form method="POST">
                    <?php echo CampoCsrf(); ?>
                        <div class="modal-body">

                            <h6 class="mb-3 border-bottom pb-2">Editar Asignación</h6>

                            <input type="hidden" name="EditAsignacion">
                            <input type="hidden" name="Tab" value="asignaciones">
                            <input type="hidden" name="Id" value="<?= $Asg['Id'] ?>">

                            <label class="small text-muted">Docente</label>
                            <select name="MaestroId" class="form-select mb-2" required>
                                <?php foreach($Maestros as $M): ?>
                                    <option value="<?= $M['Id'] ?>" <?= $M['Id'] == $Asg['MaestroId'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($M['NombreCompleto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="small text-muted">Grupo</label>
                            <select name="GrupoId" class="form-select mb-2" required>
                                <?php foreach($Grupos as $G): ?>
                                    <option value="<?= $G['Id'] ?>" <?= $G['Id'] == $Asg['GrupoId'] ? 'selected' : '' ?>>
                                        <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="small text-muted">Materia</label>
                            <input type="text" name="Materia" value="<?= htmlspecialchars($Asg['MateriaNombre']) ?>" class="form-control mb-3" maxlength="140" required>

                            <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                        </div>
                    </form>

                </div>
            </div>
        </div>
        <?php endforeach; ?>


        
        
<?php endif; ?>
<?php if ($PuedeVerBitacora && $TabActual === 'bitacora'): ?>
                <div class="tab-pane fade show active SgceActivePane" id="bitacora">
                    <div class="card card-custom p-4 SgceBitacoraCard">
                        <div class="SgceBitacoraHead">
                            <div class="SgceBitacoraTitle">
                                <span class="SgceBitacoraIcon"><span class="SgceColorIcon" aria-hidden="true">🛡️</span></span>
                                <div>
                                    <h4>BITÁCORA DE MOVIMIENTOS</h4>
                                    <p>Aquí se muestran los últimos movimientos importantes del sistema: altas, modificaciones, bajas, importaciones, asistencia y calificaciones.</p>
                                </div>
                            </div>

                            <div class="SgceBitacoraTools">
                                <div class="SgceSearchBox SgceSearchBoxSmall">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="SearchBitacora" placeholder="Buscar movimiento...">
                                </div>

                                <div class="SgceCountPill">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span><?= (int)$TotalBitacoraTabla ?> registros</span>
                                </div>
                            </div>
                        </div>

                        <div class="SgceInfoBanner mb-4">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Esta pantalla registra movimientos importantes del sistema y ayuda a revisar altas, bajas, modificaciones, sesiones, asistencias y calificaciones.</span>
                        </div>

                        <div class="table-responsive SgceTableWrap">
                            <table class="table table-hover align-middle text-center SgceBitacoraTable" id="TableBitacora">
                                <colgroup>
                                    <col class="SgceBitColFecha">
                                    <col class="SgceBitColUsuario">
                                    <col class="SgceBitColRol">
                                    <col class="SgceBitColAccion">
                                    <col class="SgceBitColTabla">
                                    <col class="SgceBitColRegistro">
                                    <col class="SgceBitColDetalle">
                                    <col class="SgceBitColIp">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Rol</th>
                                        <th>Acción</th>
                                        <th>Tabla</th>
                                        <th>Registro</th>
                                        <th>Detalle</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($BitacoraReciente)): ?>
                                        <tr>
                                            <td colspan="8" class="py-5 text-muted fw-bold">
                                                <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>
                                                TODAVÍA NO HAY MOVIMIENTOS REGISTRADOS.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($BitacoraReciente as $Mov): ?>
                                            <tr>
                                                <td class="fw-bold searchable SgceBitCellFecha" title="<?= htmlspecialchars(date('d/m/Y H:i', strtotime($Mov['FechaRegistro'])), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($Mov['FechaRegistro'])), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="searchable SgceBitCellUsuario" title="<?= htmlspecialchars($Mov['NombreCompleto'] ?: 'SISTEMA', ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($Mov['NombreCompleto'] ?: 'SISTEMA', ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="searchable">
                                                    <span class="badge bg-dark SgceBitBadgeRol" title="<?= htmlspecialchars(strtoupper((string)($Mov['Rol'] ?? '-')), ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars(strtoupper((string)($Mov['Rol'] ?? '-')), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="searchable">
                                                    <span class="badge bg-primary SgceBitBadgeAccion" title="<?= htmlspecialchars((string)$Mov['Accion'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars((string)$Mov['Accion'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="searchable SgceBitCellTabla" title="<?= htmlspecialchars((string)($Mov['TablaAfectada'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($Mov['TablaAfectada'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="searchable SgceBitCellRegistro"><?= htmlspecialchars((string)($Mov['RegistroId'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-start searchable SgceBitCellDetalle" title="<?= htmlspecialchars((string)($Mov['Detalle'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string)($Mov['Detalle'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="searchable SgceBitCellIp"><?= htmlspecialchars((string)($Mov['Ip'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?= SgceRenderPager('PagBitacora', $PagBitacora, $TotalBitacoraTabla, $PageSizeBitacora, ['Tab'=>'bitacora']) ?>
                    </div>
                </div>
        <?php endif; ?>
        
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
<script src="assets/js/sgce-shared.js?v=sgce"></script>
<script src="assets/js/Admin.js?v=sgce"></script>
</body>
</html>
