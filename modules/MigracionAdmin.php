<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'migracion', 'Solo el administrador principal puede ejecutar migraciones de ciclo escolar.');
RequerirCsrfPost();
SgceMigracionAsegurarTabla($Pdo);

function HMigracion($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }

function SgceMigracionRedirigir(string $Mensaje, string $Tipo = 'success', int $CicloOrigenId = 0): void {
    $_SESSION['MensajeMigracion'] = $Mensaje;
    $_SESSION['MensajeMigracionTipo'] = $Tipo;
    $Query = $CicloOrigenId > 0 ? '?CicloOrigenId=' . $CicloOrigenId : '';
    header('Location: MigracionAdmin.php' . $Query);
    exit;
}

function SgceMigracionMensajeResumen(array $R): string {
    $Movidos = (int)($R['Promovidos'] ?? 0) + (int)($R['Egresados'] ?? 0);
    if ($Movidos <= 0) {
        return 'Migración detenida/revisada: No se movieron alumnos porque no había alumnos INSCRITOS elegibles.';
    }
    $Omitidas = (int)($R['AsignacionesOmitidasDocente'] ?? 0) + (int)($R['AsignacionesOmitidasMateria'] ?? 0) + (int)($R['AsignacionesOmitidasDuplicado'] ?? 0) + (int)($R['AsignacionesOmitidas'] ?? 0);
    return 'Migración completada: ' . (int)($R['GruposProcesados'] ?? 1) . ' grupo(s) procesados, ' .
        (int)($R['Promovidos'] ?? 0) . ' alumno(s) promovidos, ' .
        (int)($R['Egresados'] ?? 0) . ' egresado(s), ' .
        (int)($R['KardexCongelados'] ?? 0) . ' kardex congelado(s), ' .
        (int)($R['Conflictos'] ?? 0) . ' conflicto(s), ' .
        (int)($R['GruposDestinoPreparados'] ?? $R['GruposCreados'] ?? (!empty($R['GrupoCreado']) ? 1 : 0)) . ' grupo(s) preparados en el ciclo nuevo, ' .
        (int)($R['MateriasCopiadas'] ?? 0) . ' materia(s) copiadas, ' .
        (int)($R['MateriasExistentes'] ?? 0) . ' materia(s) ya existentes, ' .
        (int)($R['AsignacionesCopiadas'] ?? 0) . ' asignación(es) copiadas. Omitidas por seguridad: ' . $Omitidas . '.';
}

$CicloActivoPost = SgceCicloActivo($Pdo);
$CicloDestinoPostId = (int)($CicloActivoPost['Id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['CopiarPeriodosDestino'])) {
    $CicloOrigenId = max(0, (int)($_POST['CicloOrigenId'] ?? 0));
    try {
        $Pdo->beginTransaction();
        $R = SgceMigracionCopiarPeriodosDesdeOrigen($Pdo, $CicloOrigenId, $CicloDestinoPostId);
        RegistrarBitacora($Pdo, $UserSession, 'COPIAR_PERIODOS_MIGRACION', 'PeriodosEvaluacion', $CicloOrigenId, 'CREADOS: ' . $R['Creados'] . ' | ACTUALIZADOS: ' . $R['Actualizados']);
        $Pdo->commit();
        SgceMigracionRedirigir('Periodos copiados al ciclo activo: ' . $R['Creados'] . ' creados y ' . $R['Actualizados'] . ' actualizados.', 'success', $CicloOrigenId);
    } catch (Throwable $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        SgceMigracionRedirigir($E->getMessage(), 'danger', $CicloOrigenId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['SimularMigracion'])) {
    $CicloOrigenId = max(0, (int)($_POST['CicloOrigenId'] ?? 0));
    $GrupoOrigenId = max(0, (int)($_POST['GrupoOrigenId'] ?? 0));
    $CopiarAsignaciones = !empty($_POST['CopiarAsignaciones']);
    try {
        $Diagnostico = SgceMigracionDiagnosticar($Pdo, $CicloOrigenId, $CicloDestinoPostId, $GrupoOrigenId, $CopiarAsignaciones);
        $_SESSION['DiagnosticoMigracion'] = $Diagnostico;
        $TipoMsg = !empty($Diagnostico['PuedeMigrar']) ? 'info' : 'warning';
        SgceMigracionRedirigir('Simulación lista: ' . (int)$Diagnostico['AlumnosAPromover'] . ' se promoverían, ' . (int)$Diagnostico['AlumnosAEgresar'] . ' egresarían y ' . (int)$Diagnostico['GruposOrigen'] . ' grupo(s) serían revisados.', $TipoMsg, $CicloOrigenId);
    } catch (Throwable $E) {
        SgceMigracionRedirigir($E->getMessage(), 'danger', $CicloOrigenId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MigrarGrupoAcademico'])) {
    $GrupoOrigenId = max(0, (int)($_POST['GrupoOrigenId'] ?? 0));
    $CicloOrigenId = max(0, (int)($_POST['CicloOrigenId'] ?? 0));
    $CopiarAsignaciones = !empty($_POST['CopiarAsignaciones']);
    $Confirmacion = (string)($_POST['ConfirmacionMigracion'] ?? '');
    try {
        if ($GrupoOrigenId <= 0 || $CicloDestinoPostId <= 0) { throw new Exception('Selecciona un grupo origen y asegúrate de tener un ciclo activo destino.'); }
        $R = SgceMigracionEjecutarGrupoBlindado($Pdo, $UserSession, $GrupoOrigenId, $CicloDestinoPostId, $CopiarAsignaciones, $Confirmacion);
        RegistrarBitacora($Pdo, $UserSession, 'MIGRAR_GRUPO_CICLO', 'Grupos', $GrupoOrigenId, 'PROMOVIDOS: '.$R['Promovidos'].' | EGRESADOS: '.$R['Egresados'].' | KARDEX: '.($R['KardexCongelados'] ?? 0).' | CONFLICTOS: '.$R['Conflictos'].' | MIGRACION_ID: '.($R['RegistroMigracionId'] ?? 0));
        SgceMigracionRedirigir(SgceMigracionMensajeResumen($R), 'success', $CicloOrigenId);
    } catch (Throwable $E) {
        SgceMigracionRedirigir($E->getMessage(), 'danger', $CicloOrigenId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['MigrarCicloAcademico'])) {
    $CicloOrigenId = max(0, (int)($_POST['CicloOrigenId'] ?? 0));
    $CopiarAsignaciones = !empty($_POST['CopiarAsignaciones']);
    $Confirmacion = (string)($_POST['ConfirmacionMigracion'] ?? '');
    try {
        if ($CicloOrigenId <= 0 || $CicloDestinoPostId <= 0) { throw new Exception('Selecciona un ciclo origen cerrado/inactivo y asegúrate de tener un ciclo activo destino.'); }
        $R = SgceMigracionEjecutarCicloBlindado($Pdo, $UserSession, $CicloOrigenId, $CicloDestinoPostId, $CopiarAsignaciones, $Confirmacion);
        RegistrarBitacora($Pdo, $UserSession, 'MIGRAR_CICLO_COMPLETO', 'CiclosEscolares', $CicloOrigenId, 'GRUPOS: '.$R['GruposProcesados'].' | PROMOVIDOS: '.$R['Promovidos'].' | EGRESADOS: '.$R['Egresados'].' | MATERIAS: '.($R['MateriasCopiadas'] ?? 0).' | KARDEX: '.($R['KardexCongelados'] ?? 0).' | CONFLICTOS: '.$R['Conflictos'].' | MIGRACION_ID: '.($R['RegistroMigracionId'] ?? 0));
        SgceMigracionRedirigir(SgceMigracionMensajeResumen($R), 'success', $CicloOrigenId);
    } catch (Throwable $E) {
        SgceMigracionRedirigir($E->getMessage(), 'danger', $CicloOrigenId);
    }
}

$Config = SgceObtenerConfiguracion($Pdo);
$CicloActivo = SgceCicloActivo($Pdo);
$CicloDestinoId = (int)($CicloActivo['Id'] ?? 0);
$CiclosInactivosMigracion = SgceCiclosInactivosConGrupos($Pdo);
$CicloOrigenMigracionId = max(0, (int)($_GET['CicloOrigenId'] ?? ($CiclosInactivosMigracion[0]['Id'] ?? 0)));
$GruposMigracion = $CicloOrigenMigracionId > 0 ? SgceGruposListarPorCiclo($Pdo, $CicloOrigenMigracionId, true) : [];
$GrupoDiagnosticoId = (int)($GruposMigracion[0]['Id'] ?? 0);
$DiagnosticoCiclo = $CicloOrigenMigracionId > 0 && $CicloDestinoId > 0 ? SgceMigracionDiagnosticar($Pdo, $CicloOrigenMigracionId, $CicloDestinoId, 0, false) : null;
$DiagnosticoGrupo = $GrupoDiagnosticoId > 0 && $CicloDestinoId > 0 ? SgceMigracionDiagnosticar($Pdo, $CicloOrigenMigracionId, $CicloDestinoId, $GrupoDiagnosticoId, false) : null;
$Mensaje = $_SESSION['MensajeMigracion'] ?? '';
$MensajeTipo = $_SESSION['MensajeMigracionTipo'] ?? 'success';
$DiagnosticoSesion = $_SESSION['DiagnosticoMigracion'] ?? null;
unset($_SESSION['MensajeMigracion'], $_SESSION['MensajeMigracionTipo'], $_SESSION['DiagnosticoMigracion']);
$DiagnosticoVista = is_array($DiagnosticoSesion) ? $DiagnosticoSesion : $DiagnosticoCiclo;
$ConfirmacionEsperada = (string)($DiagnosticoCiclo['ConfirmacionEsperada'] ?? 'MIGRAR');
$CicloPuedeMigrar = is_array($DiagnosticoCiclo) && !empty($DiagnosticoCiclo['PuedeMigrar']);
$GrupoPuedeMigrar = is_array($DiagnosticoGrupo) && !empty($DiagnosticoGrupo['PuedeMigrar']);
$DestinoSinPeriodos = is_array($DiagnosticoCiclo) ? ($DiagnosticoCiclo['DestinoSinPeriodos'] ?? []) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutHeadBase('Migración de ciclo escolar | SGCE', $Pdo, ['assets/css/configuracion-botones-metalicos.css', 'assets/css/migracion-botones-metalicos.css']) ?>
</head>
<body>
<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <section class="SgceHero mb-4 SgceMigrationLayout">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">🔁</span></div>
            <div>
                <h1>MIGRACIÓN DE CICLO ESCOLAR</h1>
                <p>Módulo exclusivo de administrador para promover grupos, congelar kardex y conservar boletas históricas.</p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
        </div>
    </section>

    <main class="SgceMigrationLayout">
        <?php if ($Mensaje !== ''): ?>
            <div class="alert alert-<?= HMigracion($MensajeTipo) ?> alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
                <i class="fa-solid <?= $MensajeTipo === 'success' ? 'fa-circle-check' : ($MensajeTipo === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark') ?> me-2"></i><?= HMigracion($Mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <?php if (SgcePuedeRespaldos($UserSession)): ?><div class="col-md-4"><a class="SgceQuickLink" href="RestaurarBD.php"><i class="fa-solid fa-database"></i><span>Crear respaldo / restaurar</span></a></div><?php endif; ?>
            <div class="col-md-4"><a class="SgceQuickLink" href="PeriodosAdmin.php"><i class="fa-solid fa-calendar-days"></i><span>Revisar ciclos y periodos</span></a></div>
            <div class="col-md-4"><a class="SgceQuickLink" href="ConfiguracionAdmin.php"><i class="fa-solid fa-sliders"></i><span>Revisar configuración académica</span></a></div>
        </div>

        <section class="SgceMigrationCard mb-4">
            <div class="SgceMigrationHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🧪</span></span>
                <div>
                    <h2>Diagnóstico previo obligatorio</h2>
                    <p>Antes de migrar se revisan ciclos, periodos, alumnos inscritos, grupos, materias por grupo, kardex, respaldo automático, doble migración y conflictos.</p>
                </div>
            </div>
            <?php if (is_array($DiagnosticoVista)): ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Ciclo origen</small><div class="fw-bold"><?= HMigracion($DiagnosticoVista['Origen']['Nombre'] ?? 'SIN ORIGEN') ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Destino activo</small><div class="fw-bold text-danger"><?= HMigracion($DiagnosticoVista['Destino']['Nombre'] ?? 'SIN DESTINO') ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Alumnos inscritos</small><div class="fw-bold"><?= (int)$DiagnosticoVista['AlumnosInscritos'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Periodos destino</small><div class="fw-bold <?= (int)$DiagnosticoVista['PeriodosDestino'] > 0 ? 'text-success' : 'text-danger' ?>"><?= (int)$DiagnosticoVista['PeriodosDestino'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Grupos origen</small><div class="fw-bold"><?= (int)$DiagnosticoVista['GruposOrigen'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Grupos a preparar</small><div class="fw-bold"><?= (int)($DiagnosticoVista['GruposDestinoPreparar'] ?? $DiagnosticoVista['GruposOrigen']) ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Materias a copiar</small><div class="fw-bold <?= (int)($DiagnosticoVista['MateriasDestinoCopiables'] ?? 0) > 0 ? 'text-success' : 'text-warning' ?>"><?= (int)($DiagnosticoVista['MateriasDestinoCopiables'] ?? 0) ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Se promoverían</small><div class="fw-bold"><?= (int)$DiagnosticoVista['AlumnosAPromover'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Egresarían</small><div class="fw-bold"><?= (int)$DiagnosticoVista['AlumnosAEgresar'] ?></div></div></div>
                    <div class="col-md-3"><div class="p-3 rounded-4 border bg-light h-100"><small class="fw-bold text-muted">Estado</small><div class="fw-bold <?= !empty($DiagnosticoVista['PuedeMigrar']) ? 'text-success' : 'text-danger' ?>"><?= !empty($DiagnosticoVista['PuedeMigrar']) ? 'LISTO' : 'BLOQUEADO' ?></div></div></div>
                </div>
                <?php if (!empty($DiagnosticoVista['Errores'])): ?>
                    <div class="alert alert-danger rounded-4"><strong>No se puede migrar todavía:</strong><ul class="mb-0 mt-2"><?php foreach($DiagnosticoVista['Errores'] as $Error): ?><li><?= HMigracion($Error) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <?php if (!empty($DiagnosticoVista['Advertencias'])): ?>
                    <div class="alert alert-warning rounded-4"><strong>Advertencias:</strong><ul class="mb-0 mt-2"><?php foreach($DiagnosticoVista['Advertencias'] as $Aviso): ?><li><?= HMigracion($Aviso) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <?php if (!empty($DestinoSinPeriodos)): ?>
                    <form method="post" class="mb-3">
                        <?= CampoCsrf() ?>
                        <input type="hidden" name="CicloOrigenId" value="<?= (int)$CicloOrigenMigracionId ?>">
                        <button class="btn btn-success rounded-pill fw-bold px-4" type="submit" name="CopiarPeriodosDestino" value="1"><i class="fa-solid fa-copy me-2"></i>Copiar periodos del ciclo origen al destino</button>
                    </form>
                <?php endif; ?>
                <p class="small text-muted fw-semibold mb-0">Confirmación requerida para ejecutar: <code><?= HMigracion($ConfirmacionEsperada) ?></code>. El sistema generará respaldo automático obligatorio antes de escribir cambios.</p>
            <?php else: ?>
                <div class="alert alert-info rounded-4 mb-0">Selecciona un ciclo origen inactivo para generar el diagnóstico.</div>
            <?php endif; ?>
        </section>

        <section class="SgceMigrationCard">
            <div class="SgceMigrationHead">
                <span><span class="SgceColorIcon" aria-hidden="true">🔐</span></span>
                <div>
                    <h2>Cierre y promoción académica</h2>
                    <p>Solo permite migrar desde ciclos inactivos hacia el ciclo activo. El sistema prepara el ciclo nuevo completo: grupos vacíos de nuevo ingreso, grupos promovidos, materias por grupo y, opcionalmente, asignaciones/docentes.</p>
                </div>
            </div>

            <form method="get" class="row g-3 align-items-end mb-4">
                <div class="col-md-8">
                    <label class="SgceFieldLabel">Ciclo origen inactivo / cerrado</label>
                    <select name="CicloOrigenId" class="form-select FormControl" onchange="this.form.submit()">
                        <?php if(empty($CiclosInactivosMigracion)): ?>
                            <option value="">No hay ciclos inactivos con grupos para migrar</option>
                        <?php else: ?>
                            <?php foreach($CiclosInactivosMigracion as $Ci): ?>
                                <option value="<?= (int)$Ci['Id'] ?>" <?= (int)$CicloOrigenMigracionId === (int)$Ci['Id'] ? 'selected' : '' ?>><?= HMigracion($Ci['Nombre']) ?> · <?= (int)$Ci['TotalGrupos'] ?> grupo(s)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4"><button class="btn btn-outline-secondary w-100 FormControl" type="submit"><i class="fa-solid fa-filter me-2"></i>Ver grupos</button></div>
            </form>

            <form method="post" class="mb-4">
                <?= CampoCsrf() ?>
                <input type="hidden" name="CicloOrigenId" value="<?= (int)$CicloOrigenMigracionId ?>">
                <input type="hidden" name="GrupoOrigenId" value="0">
                <button class="btn btn-outline-primary rounded-pill fw-bold px-4" type="submit" name="SimularMigracion" value="1" <?= empty($CicloOrigenMigracionId) ? 'disabled' : '' ?>><i class="fa-solid fa-vial me-2"></i>Simular migración del ciclo</button>
            </form>

            <div class="row g-3">
                <div class="col-lg-6">
                    <form method="post" class="SgceMigrationOption">
                        <?= CampoCsrf() ?>
                        <input type="hidden" name="MigrarGrupoAcademico" value="1">
                        <input type="hidden" name="CicloOrigenId" value="<?= (int)$CicloOrigenMigracionId ?>">
                        <h3><i class="fa-solid fa-users-viewfinder me-2"></i>Migrar un grupo</h3>
                        <p class="text-muted small fw-semibold">Ejemplo: 1A pasa a 2A, 2A pasa a 3A o la última etapa queda egresada según la estructura configurada.</p>
                        <div class="SgceMigrationControlBlock">
                            <label class="SgceFieldLabel">Grupo origen</label>
                            <select name="GrupoOrigenId" class="form-select FormControl" required <?= empty($GruposMigracion) ? 'disabled' : '' ?>>
                                <?php if(empty($GruposMigracion)): ?>
                                    <option value="">Sin grupos disponibles</option>
                                <?php else: ?>
                                    <?php foreach($GruposMigracion as $Gm): ?>
                                        <option value="<?= (int)$Gm['Id'] ?>"><?= HMigracion($Gm['Grado'].' '.$Gm['Grupo'].' '.$Gm['Turno']) ?> · <?= (int)$Gm['TotalAlumnos'] ?> alumno(s)</option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <label class="SgceMigrationToggle mb-3" for="CopiarAsignacionesGrupo">
                            <input type="checkbox" name="CopiarAsignaciones" value="1" id="CopiarAsignacionesGrupo">
                            <span class="SgceMigrationToggleMark" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                            <span class="SgceMigrationToggleText">
                                <strong>Copiar asignaciones con docente activo</strong>
                                <small>Solo se copiarán si ya existe materia equivalente segura en el grupo destino.</small>
                            </span>
                        </label>
                        <div class="mb-3">
                            <label class="SgceFieldLabel">Confirmación fuerte</label>
                            <input class="form-control FormControl" name="ConfirmacionMigracion" placeholder="<?= HMigracion($ConfirmacionEsperada) ?>" autocomplete="off">
                        </div>
                        <button class="SgceBtnDangerAction w-100" type="submit" <?= empty($GruposMigracion) || empty($CicloActivo['Id']) || !$CicloPuedeMigrar ? 'disabled' : '' ?> data-sgce-confirm="delete" data-sgce-confirm-title="CONFIRMAR MIGRACIÓN" data-sgce-confirm-subtitle="MIGRAR GRUPO" data-sgce-confirm-message="¿DESEAS MIGRAR ESTE GRUPO AL CICLO ACTIVO?" data-sgce-confirm-detail="Se generará respaldo automático, se congelará kardex y se validará que no existan duplicados." data-sgce-confirm-button="SÍ, MIGRAR GRUPO" data-sgce-confirm-loading="MIGRANDO..." data-sgce-confirm-icon="fa-rotate">
                            <i class="fa-solid fa-arrow-up-a-z me-2"></i>Migrar grupo a la siguiente etapa
                        </button>
                    </form>
                </div>

                <div class="col-lg-6">
                    <form method="post" class="SgceMigrationOption">
                        <?= CampoCsrf() ?>
                        <input type="hidden" name="MigrarCicloAcademico" value="1">
                        <input type="hidden" name="CicloOrigenId" value="<?= (int)$CicloOrigenMigracionId ?>">
                        <h3><i class="fa-solid fa-school-circle-check me-2"></i>Migrar ciclo completo</h3>
                        <p class="text-muted small fw-semibold">Procesa todos los grupos del ciclo origen seleccionado. Crea en el ciclo nuevo todos los grupos equivalentes; los de nuevo ingreso quedan vacíos, se promueven las etapas intermedias y la etapa terminal egresa.</p>
                        <div class="SgceMigrationControlBlock SgceMigrationDestinationBlock"><span class="SgceBadgeDestino">Destino activo: <?= HMigracion($CicloActivo['Nombre'] ?? 'SIN CICLO ACTIVO') ?></span></div>
                        <label class="SgceMigrationToggle mb-3" for="CopiarAsignacionesCiclo">
                            <input type="checkbox" name="CopiarAsignaciones" value="1" id="CopiarAsignacionesCiclo">
                            <span class="SgceMigrationToggleMark" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                            <span class="SgceMigrationToggleText">
                                <strong>Copiar asignaciones con docente activo</strong>
                                <small>Copia docentes/asignaciones como plantilla al mismo grado/semestre/módulo del ciclo nuevo; lo dudoso se omite y se reporta.</small>
                            </span>
                        </label>
                        <div class="mb-3">
                            <label class="SgceFieldLabel">Confirmación fuerte</label>
                            <input class="form-control FormControl" name="ConfirmacionMigracion" placeholder="<?= HMigracion($ConfirmacionEsperada) ?>" autocomplete="off">
                        </div>
                        <button class="SgceBtnDangerAction w-100" type="submit" <?= empty($GruposMigracion) || empty($CicloActivo['Id']) || !$CicloPuedeMigrar ? 'disabled' : '' ?> data-sgce-confirm="delete" data-sgce-confirm-title="CONFIRMAR MIGRACIÓN MASIVA" data-sgce-confirm-subtitle="MIGRAR CICLO COMPLETO" data-sgce-confirm-message="¿DESEAS MIGRAR TODOS LOS GRUPOS DEL CICLO CERRADO?" data-sgce-confirm-detail="Esta operación crea respaldo obligatorio, usa transacción completa y bloquea doble migración." data-sgce-confirm-button="SÍ, MIGRAR CICLO" data-sgce-confirm-loading="MIGRANDO CICLO..." data-sgce-confirm-icon="fa-school">
                            <i class="fa-solid fa-forward me-2"></i>Migrar todos los grupos
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</div>
<?= SgceLayoutSharedJs() ?>
</body>
</html>
