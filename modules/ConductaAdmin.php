<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/services/ConductaService.php';
require_once dirname(__DIR__) . '/services/GrupoService.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
if (!SgcePuedeGestionarConducta($UserSession)) { SgceDenegarAcceso('No tienes permiso para administrar conducta y disciplina.'); }

function HConducta($Texto): string { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function HConductaJson(array $Datos): string { return htmlspecialchars((string)json_encode($Datos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8'); }
function SgceConductaGrupoTexto(array $G): string { return trim(($G['Grado'] ?? '') . ' ' . ($G['Grupo'] ?? '') . ' - ' . ($G['Turno'] ?? '')); }

$CicloActivo = SgceCicloActivo($Pdo);
$CicloId = (int)($CicloActivo['Id'] ?? 0);
$Mensaje = $_SESSION['MensajeConducta'] ?? '';
$MensajeTipo = $_SESSION['MensajeConductaTipo'] ?? 'success';
unset($_SESSION['MensajeConducta'], $_SESSION['MensajeConductaTipo']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RequerirCsrfPost();
    $Accion = trim((string)($_POST['AccionConducta'] ?? ''));
    try {
        if ($Accion === 'GuardarManual') {
            $NuevoId = SgceConductaGuardarManual($Pdo, [
                'CicloId' => $CicloId,
                'AlumnoId' => (int)($_POST['AlumnoId'] ?? 0),
                'GrupoId' => (int)($_POST['GrupoId'] ?? 0),
                'FechaIncidente' => $_POST['FechaIncidente'] ?? date('Y-m-d'),
                'Tipo' => $_POST['Tipo'] ?? 'REPORTE',
                'Categoria' => $_POST['Categoria'] ?? '',
                'Severidad' => $_POST['Severidad'] ?? 'LEVE',
                'MotivoCorto' => $_POST['MotivoCorto'] ?? '',
                'Detalle' => $_POST['Detalle'] ?? '',
                'AccionTomada' => $_POST['AccionTomada'] ?? '',
                'Estado' => $_POST['Estado'] ?? 'VALIDADO',
                'VisiblePadre' => !empty($_POST['VisiblePadre']) ? 1 : 0,
            ], $UserSession);
            if ($NuevoId > 0) {
                RegistrarBitacora($Pdo, $UserSession, 'ALTA_CONDUCTA', 'ConductaRegistros', $NuevoId, 'REPORTE DE CONDUCTA REGISTRADO DESDE ADMINISTRACIÓN');
                $_SESSION['MensajeConducta'] = 'Reporte de conducta registrado correctamente.';
                $_SESSION['MensajeConductaTipo'] = 'success';
            } else {
                $_SESSION['MensajeConducta'] = 'No se pudo registrar el reporte. Revisa alumno y motivo corto.';
                $_SESSION['MensajeConductaTipo'] = 'danger';
            }
        }
        if ($Accion === 'ActualizarRevision') {
            $ConductaId = (int)($_POST['ConductaId'] ?? 0);
            $ConductaActual = SgceConductaObtener($Pdo, $ConductaId);
            if (!$ConductaActual || (int)($ConductaActual['CicloId'] ?? 0) !== $CicloId) {
                throw new RuntimeException('El reporte no pertenece al ciclo activo.');
            }
            $Ok = SgceConductaActualizarRevision($Pdo, $ConductaId, [
                'Estado' => $_POST['Estado'] ?? 'PENDIENTE',
                'VisiblePadre' => !empty($_POST['VisiblePadre']) ? 1 : 0,
                'Detalle' => $_POST['Detalle'] ?? '',
                'AccionTomada' => $_POST['AccionTomada'] ?? '',
            ], $UserSession);
            RegistrarBitacora($Pdo, $UserSession, 'REVISAR_CONDUCTA', 'ConductaRegistros', $ConductaId, 'REPORTE DE CONDUCTA REVISADO O ACTUALIZADO');
            $_SESSION['MensajeConducta'] = $Ok ? 'Reporte actualizado correctamente.' : 'No hubo cambios en el reporte.';
            $_SESSION['MensajeConductaTipo'] = 'success';
        }
    } catch (Throwable $E) {
        if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
        $_SESSION['MensajeConducta'] = 'Error al procesar conducta. Revisa los datos e intenta nuevamente.';
        $_SESSION['MensajeConductaTipo'] = 'danger';
    }
    header('Location: ConductaAdmin.php');
    exit;
}

$Grupos = $CicloId > 0 ? SgceGrupoListarActivos($Pdo) : [];
$AlumnosActivos = $CicloId > 0 ? SgceConductaAlumnosActivos($Pdo, $CicloId) : [];
$Maestros = $Pdo->query("SELECT Id, NombreCompleto FROM Usuarios WHERE Activo = 1 AND Rol IN ('maestro','administrativo','admin') ORDER BY NombreCompleto ASC")->fetchAll();

$Filtro = SgceConductaFiltros($_GET);
if (($Filtro['FechaInicio'] ?? '') > ($Filtro['FechaFin'] ?? '')) {
    [$Filtro['FechaInicio'], $Filtro['FechaFin']] = [$Filtro['FechaFin'], $Filtro['FechaInicio']];
}
$Pagina = SgcePaginaActual('PagConducta', 1);
$PorPagina = SgcePageSizeSeguro($_GET['PageSizeConducta'] ?? 5, 5, 5, 50);
[$Offset, $Limite] = SgceLimitOffset($Pagina, $PorPagina);
$RegistrosConducta = SgceConductaListar($Pdo, $CicloId, $Filtro, $Limite, $Offset);
$TotalConducta = SgceConductaContar($Pdo, $CicloId, $Filtro);
$TotalPaginas = max(1, (int)ceil($TotalConducta / max(1, $PorPagina)));
$ResumenHoy = SgceConductaResumenHoy($Pdo, $CicloId);

$QueryExportar = http_build_query(array_filter([
    'FechaInicio' => $Filtro['FechaInicio'] ?? '',
    'FechaFin' => $Filtro['FechaFin'] ?? '',
    'GrupoId' => (int)($Filtro['GrupoId'] ?? 0) ?: null,
    'AlumnoId' => (int)($Filtro['AlumnoId'] ?? 0) ?: null,
    'DocenteId' => (int)($Filtro['DocenteId'] ?? 0) ?: null,
    'Tipo' => $Filtro['Tipo'] ?? '',
    'Severidad' => $Filtro['Severidad'] ?? '',
    'Estado' => $Filtro['Estado'] ?? '',
    'VisiblePadre' => (int)($Filtro['VisiblePadre'] ?? -1) >= 0 ? (int)$Filtro['VisiblePadre'] : null,
], static fn($V) => $V !== null && $V !== ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutHeadBase('Conducta y disciplina | SGCE', $Pdo, ['assets/css/conducta-disciplina.css', 'assets/css/admin-paginacion-busqueda.css']) ?>
</head>
<body>
<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4 ConductaAdminPage">
    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">🧭</span></div>
            <div><h1>Conducta y disciplina</h1><p>Registra, valida y da seguimiento a reportes disciplinarios del ciclo activo.</p></div>
        </div>
        <div class="SgceHeroActions"><a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a></div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HConducta($MensajeTipo) ?> alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
            <i class="fa-solid <?= $MensajeTipo === 'danger' ? 'fa-circle-xmark' : 'fa-circle-check' ?> me-2"></i><?= HConducta($Mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if ($CicloId <= 0): ?>
        <div class="alert alert-warning rounded-4 border-0 fw-semibold">No hay ciclo activo para administrar conducta.</div>
    <?php else: ?>

    <section class="ConductaStatsGrid mb-4">
        <article class="ConductaStatCard"><span>📌</span><div><strong><?= (int)$ResumenHoy['Total'] ?></strong><small>Reportes hoy</small></div></article>
        <article class="ConductaStatCard"><span>⏳</span><div><strong><?= (int)$ResumenHoy['Pendientes'] ?></strong><small>Pendientes hoy</small></div></article>
        <article class="ConductaStatCard"><span>🚨</span><div><strong><?= (int)$ResumenHoy['Graves'] ?></strong><small>Graves hoy</small></div></article>
        <article class="ConductaStatCard"><span>📚</span><div><strong><?= HConducta($CicloActivo['Nombre'] ?? '') ?></strong><small>Ciclo activo</small></div></article>
    </section>

    <section class="SgceConfigCard ConductaFormCard p-4 mb-4">
        <div class="SgceConfigHead"><span><span class="SgceColorIcon" aria-hidden="true">➕</span></span><div><h2>Reporte manual</h2><p>Úsalo para prefectura, dirección o situaciones fuera de clase.</p></div></div>
        <form method="post" class="row g-3 align-items-end ConductaManualForm">
            <?= CampoCsrf() ?>
            <input type="hidden" name="AccionConducta" value="GuardarManual">
            <div class="col-lg-4 col-md-12">
                <label class="SgceFieldLabel">Alumno</label>
                <select name="AlumnoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar alumno por nombre, grupo o turno..." required>
                    <option value="">SELECCIONA ALUMNO...</option>
                    <?php foreach($AlumnosActivos as $Al): ?>
                        <option value="<?= (int)$Al['Id'] ?>" data-grupo="<?= (int)$Al['GrupoId'] ?>"><?= HConducta($Al['NombreCompleto'] . ' · ' . $Al['Grado'] . ' ' . $Al['Grupo'] . ' ' . $Al['Turno']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3"><label class="SgceFieldLabel">Fecha</label><input type="date" name="FechaIncidente" class="form-control" max="<?= HConducta(date('Y-m-d')) ?>" value="<?= HConducta(date('Y-m-d')) ?>" required></div>
            <div class="col-lg-2 col-md-3"><label class="SgceFieldLabel">Tipo</label><select name="Tipo" class="form-select"><?php foreach(SgceConductaTipos() as $T): ?><option value="<?= HConducta($T) ?>"><?= HConducta(SgceConductaTextoTipo($T)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-3"><label class="SgceFieldLabel">Severidad</label><select name="Severidad" class="form-select"><?php foreach(SgceConductaSeveridades() as $S): ?><option value="<?= HConducta($S) ?>"><?= HConducta(SgceConductaTextoSeveridad($S)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-3"><label class="SgceFieldLabel">Estado</label><select name="Estado" class="form-select"><option value="VALIDADO">Validado</option><option value="PENDIENTE">Pendiente</option><option value="EN_SEGUIMIENTO">En seguimiento</option><option value="CERRADO">Cerrado</option></select></div>
            <div class="col-lg-4 col-md-12"><label class="SgceFieldLabel">Categoría</label><input name="Categoria" class="form-control ConductaMayuscula" maxlength="80" placeholder="DISCIPLINA"></div>
            <div class="col-lg-8 col-md-12"><label class="SgceFieldLabel">Motivo corto</label><input name="MotivoCorto" class="form-control ConductaMayuscula" maxlength="180" placeholder="EJ. FALTA DE RESPETO, PELEA, USO INADECUADO DE CELULAR" required></div>
            <div class="col-md-6"><label class="SgceFieldLabel">Detalle</label><textarea name="Detalle" class="form-control ConductaMayuscula" rows="3" placeholder="DESCRIBE LOS HECHOS DE FORMA INSTITUCIONAL"></textarea></div>
            <div class="col-md-6"><label class="SgceFieldLabel">Acción tomada</label><textarea name="AccionTomada" class="form-control ConductaMayuscula" rows="3" placeholder="DIÁLOGO, CANALIZACIÓN, CITATORIO, SEGUIMIENTO"></textarea></div>
            <div class="col-md-8"><label class="ConductaVisiblePadre"><input type="checkbox" name="VisiblePadre" value="1"> Visible para padre/tutor cuando el estado lo permita</label></div>
            <div class="col-md-4 text-md-end"><button class="btn BtnGuardar BtnAsistenciaVerdeMetalico px-4" type="submit"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar reporte</button></div>
        </form>
    </section>

    <section class="SgceConfigCard ConductaFilterCard p-4 mb-4">
        <div class="SgceConfigHead"><span><span class="SgceColorIcon" aria-hidden="true">🔍</span></span><div><h2>Filtros</h2><p>Busca reportes por fecha, grupo, alumno, docente, severidad o estado.</p></div></div>
        <form method="get" class="row g-3 align-items-end ConductaFilterForm">
            <div class="col-md-2"><label class="SgceFieldLabel">Desde</label><input type="date" name="FechaInicio" class="form-control" value="<?= HConducta($Filtro['FechaInicio'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="SgceFieldLabel">Hasta</label><input type="date" name="FechaFin" class="form-control" value="<?= HConducta($Filtro['FechaFin'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="SgceFieldLabel">Grupo</label><select name="GrupoId" class="form-select"><option value="0">TODOS</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>" <?= (int)$Filtro['GrupoId']===(int)$G['Id']?'selected':'' ?>><?= HConducta(SgceConductaGrupoTexto($G)) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="SgceFieldLabel">Tipo</label><select name="Tipo" class="form-select"><option value="">TODOS</option><?php foreach(SgceConductaTipos() as $T): ?><option value="<?= HConducta($T) ?>" <?= ($Filtro['Tipo']??'')===$T?'selected':'' ?>><?= HConducta(SgceConductaTextoTipo($T)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-3"><label class="SgceFieldLabel">Severidad</label><select name="Severidad" class="form-select"><option value="">TODAS</option><?php foreach(SgceConductaSeveridades() as $S): ?><option value="<?= HConducta($S) ?>" <?= ($Filtro['Severidad']??'')===$S?'selected':'' ?>><?= HConducta(SgceConductaTextoSeveridad($S)) ?></option><?php endforeach; ?></select></div>
            <div class="col-lg-2 col-md-3"><label class="SgceFieldLabel">Estado</label><select name="Estado" class="form-select"><option value="">TODOS</option><?php foreach(SgceConductaEstados() as $E): ?><option value="<?= HConducta($E) ?>" <?= ($Filtro['Estado']??'')===$E?'selected':'' ?>><?= HConducta(SgceConductaTextoEstado($E)) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="SgceFieldLabel">Alumno</label><select name="AlumnoId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar alumno..."><option value="0">TODOS</option><?php foreach($AlumnosActivos as $Al): ?><option value="<?= (int)$Al['Id'] ?>" <?= (int)$Filtro['AlumnoId']===(int)$Al['Id']?'selected':'' ?>><?= HConducta($Al['NombreCompleto']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="SgceFieldLabel">Reportó</label><select name="DocenteId" class="form-select SgceSearchableSelect" data-sgce-searchable-select="1" data-sgce-search-placeholder="Buscar docente o usuario..."><option value="0">TODOS</option><?php foreach($Maestros as $M): ?><option value="<?= (int)$M['Id'] ?>" <?= (int)$Filtro['DocenteId']===(int)$M['Id']?'selected':'' ?>><?= HConducta($M['NombreCompleto']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><label class="SgceFieldLabel">Visible padre</label><select name="VisiblePadre" class="form-select"><option value="">TODOS</option><option value="1" <?= (int)($Filtro['VisiblePadre']??-1)===1?'selected':'' ?>>SÍ</option><option value="0" <?= (int)($Filtro['VisiblePadre']??-1)===0?'selected':'' ?>>NO</option></select></div>
            <div class="col-md-4 d-flex gap-2 justify-content-md-end"><button class="btn btn-dark rounded-pill px-4" type="submit"><i class="fa-solid fa-filter me-2"></i>Filtrar</button><a class="btn btn-outline-secondary rounded-pill px-4" href="ConductaAdmin.php">Limpiar</a><a class="btn btn-outline-danger rounded-pill px-4" target="_blank" rel="noopener noreferrer" href="ExportarConducta.php<?= $QueryExportar ? '?' . HConducta($QueryExportar) : '' ?>"><i class="fa-solid fa-file-pdf me-2"></i>PDF</a></div>
        </form>
    </section>

    <?php
    $InicioPagina = $TotalConducta > 0 ? ($Offset + 1) : 0;
    $FinPagina = min($Offset + $PorPagina, $TotalConducta);
    ?>
    <section class="card border-0 shadow-sm rounded-4 ConductaTableCard">
        <div class="ConductaListHead">
            <div class="ConductaListTitle">
                <span class="ConductaListIcon"><i class="fa-solid fa-clipboard-list"></i></span>
                <div>
                    <h4>Reportes registrados</h4>
                    <p>Consulta incidencias, revisa seguimiento y controla la visibilidad para padres.</p>
                </div>
            </div>
            <span class="ConductaListCounter"><i class="fa-solid fa-layer-group"></i><?= (int)$TotalConducta ?> reportes</span>
        </div>
        <div class="table-responsive ConductaTableWrap">
            <table class="table align-middle mb-0 SgceTable ConductaAdminTable">
                <thead class="table-light"><tr><th>Fecha</th><th>Alumno</th><th>Grupo</th><th>Materia/Origen</th><th>Reporte</th><th>Estado</th><th>Revisión</th></tr></thead>
                <tbody>
                <?php foreach($RegistrosConducta as $R): ?>
                    <tr>
                        <td><?= HConducta($R['FechaTexto'] ?? '') ?></td>
                        <td class="fw-bold"><?= HConducta($R['AlumnoNombre'] ?? '') ?><br><small class="text-muted">Reportó: <?= HConducta($R['ReportaNombre'] ?? 'Sistema') ?></small></td>
                        <td><?= HConducta(($R['Grado'] ?? '') . ' ' . ($R['Grupo'] ?? '') . ' ' . ($R['Turno'] ?? '')) ?></td>
                        <td><?= HConducta($R['MateriaNombre'] ?: SgceConductaTextoTipo((string)$R['Tipo'])) ?><br><small class="text-muted"><?= HConducta($R['Origen'] ?? '') ?></small></td>
                        <td><span class="badge bg-<?= HConducta(SgceConductaClaseSeveridad((string)$R['Severidad'])) ?> mb-2"><?= HConducta(SgceConductaTextoSeveridad((string)$R['Severidad'])) ?></span><br><strong><?= HConducta($R['MotivoCorto'] ?? '') ?></strong><div class="text-muted small mt-1"><?= HConducta($R['Detalle'] ?? '') ?></div></td>
                        <td><span class="badge bg-<?= HConducta(SgceConductaClaseEstado((string)$R['Estado'])) ?>"><?= HConducta(SgceConductaTextoEstado((string)$R['Estado'])) ?></span><br><small class="text-muted">Padre: <?= !empty($R['VisiblePadre']) ? 'Visible' : 'Interno' ?></small></td>
                        <td class="text-center">
                            <?php
                            $PayloadRevision = [
                                'Id' => (int)$R['Id'],
                                'Fecha' => (string)($R['FechaTexto'] ?? ''),
                                'Alumno' => (string)($R['AlumnoNombre'] ?? ''),
                                'Grupo' => trim((string)(($R['Grado'] ?? '') . ' ' . ($R['Grupo'] ?? '') . ' ' . ($R['Turno'] ?? ''))),
                                'Materia' => (string)($R['MateriaNombre'] ?: SgceConductaTextoTipo((string)$R['Tipo'])),
                                'Origen' => (string)($R['Origen'] ?? ''),
                                'Severidad' => (string)($R['Severidad'] ?? 'LEVE'),
                                'SeveridadTexto' => SgceConductaTextoSeveridad((string)($R['Severidad'] ?? 'LEVE')),
                                'Estado' => (string)($R['Estado'] ?? 'PENDIENTE'),
                                'MotivoCorto' => (string)($R['MotivoCorto'] ?? ''),
                                'Detalle' => (string)($R['Detalle'] ?? ''),
                                'AccionTomada' => (string)($R['AccionTomada'] ?? ''),
                                'VisiblePadre' => !empty($R['VisiblePadre']) ? 1 : 0,
                                'Reporta' => (string)($R['ReportaNombre'] ?? 'Sistema'),
                            ];
                            ?>
                            <button type="button" class="btn ConductaReviewBtn" data-conducta-revision="<?= HConductaJson($PayloadRevision) ?>">
                                <i class="fa-solid fa-clipboard-check me-2"></i>Revisar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($RegistrosConducta)): ?><tr><td colspan="7"><?= SgceComponenteTablaVacia('Sin reportes de conducta en el rango seleccionado') ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="ConductaTableFooter">
            <div class="ConductaPageInfo">
                <i class="fa-solid fa-list-check"></i>
                Mostrando <?= (int)$InicioPagina ?>-<?= (int)$FinPagina ?> de <?= (int)$TotalConducta ?> registro(s).
            </div>
            <?php if($TotalPaginas > 1): ?>
                <nav class="ConductaPagination" aria-label="Paginación de reportes de conducta">
                    <?php for($P=1; $P<=$TotalPaginas; $P++): $Q=$_GET; $Q['PagConducta']=$P; ?>
                        <a class="ConductaPageBtn <?= $P===$Pagina?'is-active':'' ?>" href="ConductaAdmin.php?<?= HConducta(http_build_query($Q)) ?>"><?= $P ?></a>
                    <?php endfor; ?>
                </nav>
            <?php else: ?>
                <span class="ConductaPageSingle"><i class="fa-solid fa-check"></i> Página única</span>
            <?php endif; ?>
        </div>
    </section>

    <div class="modal fade ConductaRevisionModal" id="ConductaRevisionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
            <form method="post" class="modal-content ConductaRevisionModalContent">
                <?= CampoCsrf() ?>
                <input type="hidden" name="AccionConducta" value="ActualizarRevision">
                <input type="hidden" name="ConductaId" id="ConductaRevisionId" value="0">
                <div class="ConductaRevisionHeader">
                    <div class="ConductaRevisionIcon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div class="text-center">
                        <h5>Revisar reporte de conducta</h5>
                        <p>Actualiza estado, seguimiento y visibilidad para padres.</p>
                        <span id="ConductaRevisionAlumno" class="ConductaRevisionAlumno">Alumno</span>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <div class="ConductaRevisionSummary mb-3">
                        <div><span>Fecha</span><strong id="ConductaRevisionFecha">-</strong></div>
                        <div><span>Grupo</span><strong id="ConductaRevisionGrupo">-</strong></div>
                        <div><span>Materia / origen</span><strong id="ConductaRevisionMateria">-</strong></div>
                        <div><span>Reportó</span><strong id="ConductaRevisionReporta">-</strong></div>
                    </div>
                    <div class="ConductaRevisionMotive mb-3">
                        <span id="ConductaRevisionSeveridad" class="badge rounded-pill mb-2">Severidad</span>
                        <strong id="ConductaRevisionMotivo">Motivo</strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="SgceFieldLabel">Estado</label>
                            <select name="Estado" id="ConductaRevisionEstado" class="form-select">
                                <?php foreach(SgceConductaEstados() as $E): ?><option value="<?= HConducta($E) ?>"><?= HConducta(SgceConductaTextoEstado($E)) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <label class="ConductaVisiblePadre ConductaRevisionVisible w-100 justify-content-center">
                                <input type="checkbox" name="VisiblePadre" id="ConductaRevisionVisiblePadre" value="1"> Visible para padre
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="SgceFieldLabel">Detalle</label>
                            <textarea name="Detalle" id="ConductaRevisionDetalle" class="form-control ConductaMayuscula" rows="4" placeholder="DETALLE DEL REPORTE"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="SgceFieldLabel">Acción tomada</label>
                            <textarea name="AccionTomada" id="ConductaRevisionAccion" class="form-control ConductaMayuscula" rows="4" placeholder="ACCIÓN TOMADA"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ConductaRevisionFooter">
                    <button type="button" class="btn ConductaRevisionCancel" data-bs-dismiss="modal"><i class="fa-solid fa-xmark me-2"></i>Cancelar</button>
                    <button type="submit" class="btn ConductaRevisionSave"><i class="fa-solid fa-check me-2"></i>Guardar revisión</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>
</div>
<?= SgceLayoutSharedJs(['assets/js/admin/AdminSearchableSelects.js', 'assets/js/ConductaAdmin.js'], true, true) ?>
</body>
</html>
