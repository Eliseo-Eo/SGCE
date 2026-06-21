<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

$PageSizeAdmin = 20;
$PageSizeAlumnos = SgcePageSizeSeguro($_GET['PageSizeAlumnos'] ?? 6, 6, 6, 100);
$PageSizeMaterias = SgcePageSizeSeguro($_GET['PageSizeMaterias'] ?? 7, 7, 7, 100);
$PageSizeAsignaciones = SgcePageSizeSeguro($_GET['PageSizeAsignaciones'] ?? 7, 7, 7, 100);
$PageSizeBitacora = SgcePageSizeSeguro($_GET['PageSizeBitacora'] ?? 6, 6, 6, 100);
$PagMaestros = SgcePaginaActual('PagMaestros');
$PagGrupos = SgcePaginaActual('PagGrupos');
$PagAlumnos = SgcePaginaActual('PagAlumnos');
$PagMaterias = SgcePaginaActual('PagMaterias');
$PagAsig = SgcePaginaActual('PagAsig');
$PagBitacora = SgcePaginaActual('PagBitacora');
[$OffsetMaestros, $LimitMaestros] = SgceLimitOffset($PagMaestros, $PageSizeAdmin);
[$OffsetGrupos, $LimitGrupos] = SgceLimitOffset($PagGrupos, $PageSizeAdmin);
[$OffsetAlumnos, $LimitAlumnos] = SgceLimitOffset($PagAlumnos, $PageSizeAlumnos);
[$OffsetMaterias, $LimitMaterias] = SgceLimitOffset($PagMaterias, $PageSizeMaterias);
[$OffsetAsig, $LimitAsig] = SgceLimitOffset($PagAsig, $PageSizeAsignaciones);
[$OffsetBitacora, $LimitBitacora] = SgceLimitOffset($PagBitacora, $PageSizeBitacora);
$FiltroAlumnos = SgceRepoAlumnoFiltros($_GET);
$FiltroMaterias = function_exists('SgceRepoMateriaFiltros') ? SgceRepoMateriaFiltros($_GET) : [];
$FiltroAsignaciones = SgceRepoAsignacionFiltros($_GET);
$FiltroBitacora = SgceRepoBitacoraFiltros($_GET);

$Maestros = [];
$Grupos = [];
$MaestrosTabla = [];
$GruposTabla = [];
$Alumnos = [];
$MateriasGrupo = [];
$MateriasDisponiblesAsignacion = [];
$Asignaciones = [];
$AlumnosExpedientes = [];
$GrupoExpedienteSeleccionado = null;
$TotalMaestrosTabla = 0;
$TotalGruposTabla = 0;
$TotalAlumnosTabla = 0;
$TotalMateriasTabla = 0;
$TotalAsignacionesTabla = 0;
$TotalAlumnosActivos = 0;
$TotalMaestrosActivos = 0;
$TotalGruposActivos = 0;
$AsistenciasHoy = 0;
$FaltasHoy = 0;
$ConductaHoy = 0;
$ConductaPendientesHoy = 0;
$PromedioGeneral = '0.0';
$AlumnosRiesgo = [];
$BitacoraReciente = [];
$TotalBitacoraTabla = 0;
$BitacoraUsuariosFiltro = [];
$BitacoraAccionesFiltro = [];

$CicloActivo = SgceCicloActivo($Pdo);
$CicloActivoId = (int)($CicloActivo['Id'] ?? 0);
$QueryCicloActivoAsistencia = '';
$CicloFechaInicio = $CicloActivo['FechaInicio'] ?? date('Y-01-01');
$CicloFechaFin = $CicloActivo['FechaFin'] ?? date('Y-12-31');
$ReporteFechaFinDefault = min(date('Y-m-d'), (string)$CicloFechaFin);
$ReporteFechaInicioDefault = max((string)$CicloFechaInicio, date('Y-m-d', strtotime($ReporteFechaFinDefault . ' -30 days')));
if (!empty($CicloActivo['FechaInicio']) && !empty($CicloActivo['FechaFin'])) {
    $QueryCicloActivoAsistencia = '&FechaInicio=' . urlencode($ReporteFechaInicioDefault) . '&FechaFin=' . urlencode($ReporteFechaFinDefault) . '&CicloId=' . urlencode((string)$CicloActivoId);
}
$OfertaActiva = SgceOfertaActiva($Pdo);
$OfertaActivaId = (int)($OfertaActiva['Id'] ?? 0);
$EtapasAcademicas = $OfertaActivaId > 0 ? SgceEtapasAcademicasListar($Pdo, $OfertaActivaId, true) : [];
$ProgramasActivos = !empty($OfertaActiva['UsaProgramas']) ? SgceProgramasEducativosListar($Pdo, true) : [];

$NecesitaGruposSelect = in_array($TabActual, ['expedientes', 'alumnos', 'materias', 'asignaciones'], true);
$NecesitaMaestrosSelect = ($TabActual === 'asignaciones');

if ($NecesitaMaestrosSelect) { $Maestros = SgceMaestroListarActivos($Pdo); }
if ($NecesitaGruposSelect || in_array($TabActual, ['grupos','alumnos','materias','asignaciones'], true)) { $Grupos = SgceGrupoListarActivos($Pdo); }

if ($TabActual === 'maestros') {
    $MaestrosTabla = SgceMaestroListarActivos($Pdo);
    $TotalMaestrosTabla = count($MaestrosTabla);
}

if ($TabActual === 'grupos') {
    $GruposTabla = SgceGrupoListarActivos($Pdo);
    $TotalGruposTabla = count($GruposTabla);
}

if ($TabActual === 'alumnos') {
    $Alumnos = SgceAlumnoListarFiltrado($Pdo, $FiltroAlumnos, $LimitAlumnos, $OffsetAlumnos);
    $TotalAlumnosTabla = SgceAlumnoContarFiltrado($Pdo, $FiltroAlumnos);
}

if ($TabActual === 'materias') {
    if (function_exists('SgceMateriaGrupoListarFiltradas')) {
        $MateriasGrupo = SgceMateriaGrupoListarFiltradas($Pdo, $FiltroMaterias, $LimitMaterias, $OffsetMaterias);
        $TotalMateriasTabla = SgceMateriaGrupoContarFiltradas($Pdo, $FiltroMaterias);
    } else {
        $MateriasGrupo = SgceMateriaGrupoListar($Pdo, $CicloActivoId, true);
        $TotalMateriasTabla = count($MateriasGrupo);
    }
}

if ($TabActual === 'asignaciones') {
    $MateriasDisponiblesAsignacion = SgceMateriaGrupoListarDisponiblesAsignacion($Pdo, $CicloActivoId);
    $Asignaciones = SgceAsignacionListarFiltradas($Pdo, $FiltroAsignaciones, $LimitAsig, $OffsetAsig);
    $TotalAsignacionesTabla = SgceAsignacionContarFiltradas($Pdo, $FiltroAsignaciones);
}

if ($TabActual === 'expedientes') {
    $ExpedienteGrupoId = intval($_GET['ExpGrupoId'] ?? 0);
    if ($ExpedienteGrupoId > 0) {
        $GrupoExpedienteSeleccionado = SgceGrupoObtenerActivoPorId($Pdo, $ExpedienteGrupoId);
        if ($GrupoExpedienteSeleccionado) { $AlumnosExpedientes = SgceAlumnoListarActivosPorGrupo($Pdo, $ExpedienteGrupoId); }
        else { $ExpedienteGrupoId = 0; }
    }
} else { $ExpedienteGrupoId = 0; }

if ($TabActual === 'inicio') {
    $TotalAlumnosActivos = SgceAlumnoContarActivos($Pdo);
    $TotalMaestrosActivos = SgceMaestroContarActivos($Pdo);
    $TotalGruposActivos = SgceGrupoContarActivos($Pdo);
    $ResumenAsistenciaHoy = SgceAsistenciaResumenHoy($Pdo);
    $AsistenciasHoy = $ResumenAsistenciaHoy['Total'];
    $FaltasHoy = $ResumenAsistenciaHoy['Faltas'];
    $ResumenConductaHoy = SgceConductaResumenHoy($Pdo, $CicloActivoId);
    $ConductaHoy = $ResumenConductaHoy['Total'];
    $ConductaPendientesHoy = $ResumenConductaHoy['Pendientes'];
    $PromedioGeneral = SgceCalificacionPromedioGeneralCiclo($Pdo, $CicloActivoId);
    $AlumnosRiesgo = SgceReporteAlumnosRiesgo($Pdo, $CicloActivoId, $CicloFechaInicio, $CicloFechaFin, 10);
}

if ($TabActual === 'bitacora' && $PuedeVerBitacora) {
    try {
        $BitacoraReciente = SgceReporteBitacoraPaginada($Pdo, $FiltroBitacora, $LimitBitacora, $OffsetBitacora);
        $TotalBitacoraTabla = SgceReporteBitacoraContar($Pdo, $FiltroBitacora);
        $BitacoraUsuariosFiltro = SgceMaestroListarTodosUsuariosParaFiltro($Pdo);
        $BitacoraAccionesFiltro = SgceBitacoraAccionesDisponibles($Pdo);
    } catch (Exception $E) {
        $BitacoraReciente = [];
        $TotalBitacoraTabla = 0;
    }
}

?>
