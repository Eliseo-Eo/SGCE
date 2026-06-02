<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

$PageSizeAdmin = 20;
$PageSizeAlumnos = SgcePageSizeSeguro($_GET['PageSizeAlumnos'] ?? 50, 50, 10, 100);
$PageSizeAsignaciones = SgcePageSizeSeguro($_GET['PageSizeAsignaciones'] ?? 50, 50, 10, 100);
$PageSizeBitacora = SgcePageSizeSeguro($_GET['PageSizeBitacora'] ?? 50, 50, 10, 100);
$PagMaestros = SgcePaginaActual('PagMaestros');
$PagGrupos = SgcePaginaActual('PagGrupos');
$PagAlumnos = SgcePaginaActual('PagAlumnos');
$PagAsig = SgcePaginaActual('PagAsig');
$PagBitacora = SgcePaginaActual('PagBitacora');
[$OffsetMaestros, $LimitMaestros] = SgceLimitOffset($PagMaestros, $PageSizeAdmin);
[$OffsetGrupos, $LimitGrupos] = SgceLimitOffset($PagGrupos, $PageSizeAdmin);
[$OffsetAlumnos, $LimitAlumnos] = SgceLimitOffset($PagAlumnos, $PageSizeAlumnos);
[$OffsetAsig, $LimitAsig] = SgceLimitOffset($PagAsig, $PageSizeAsignaciones);
[$OffsetBitacora, $LimitBitacora] = SgceLimitOffset($PagBitacora, $PageSizeBitacora);
$FiltroAlumnos = SgceRepoAlumnoFiltros($_GET);
$FiltroAsignaciones = SgceRepoAsignacionFiltros($_GET);
$FiltroBitacora = SgceRepoBitacoraFiltros($_GET);

$Maestros = [];
$Grupos = [];
$MaestrosTabla = [];
$GruposTabla = [];
$Alumnos = [];
$Asignaciones = [];
$AlumnosExpedientes = [];
$GrupoExpedienteSeleccionado = null;
$TotalMaestrosTabla = 0;
$TotalGruposTabla = 0;
$TotalAlumnosTabla = 0;
$TotalAsignacionesTabla = 0;
$TotalAlumnosActivos = 0;
$TotalMaestrosActivos = 0;
$TotalGruposActivos = 0;
$AsistenciasHoy = 0;
$FaltasHoy = 0;
$PromedioGeneral = '0.0';
$AlumnosRiesgo = [];
$BitacoraReciente = [];
$TotalBitacoraTabla = 0;

$CicloActivo = SgceCicloActivo($Pdo);
$CicloActivoId = (int)($CicloActivo['Id'] ?? 0);
$QueryCicloActivoAsistencia = '';
if (!empty($CicloActivo['FechaInicio']) && !empty($CicloActivo['FechaFin'])) {
    $QueryCicloActivoAsistencia = '&FechaInicio=' . urlencode((string)$CicloActivo['FechaInicio']) . '&FechaFin=' . urlencode((string)$CicloActivo['FechaFin']) . '&CicloId=' . urlencode((string)$CicloActivoId);
}
$CicloFechaInicio = $CicloActivo['FechaInicio'] ?? date('Y-01-01');
$CicloFechaFin = $CicloActivo['FechaFin'] ?? date('Y-12-31');

$NecesitaGruposSelect = in_array($TabActual, ['expedientes', 'alumnos', 'asignaciones'], true);
$NecesitaMaestrosSelect = ($TabActual === 'asignaciones');

if ($NecesitaMaestrosSelect) {
    $Maestros = SgceMaestroListarActivos($Pdo);
}

if ($NecesitaGruposSelect) {
    $Grupos = SgceGrupoListarActivos($Pdo);
}

if ($TabActual === 'maestros') {
    $TotalMaestrosTabla = SgceMaestroContarActivos($Pdo);
    $MaestrosTabla = SgceMaestroListarPaginado($Pdo, $LimitMaestros, $OffsetMaestros);
}

if ($TabActual === 'grupos') {
    $TotalGruposTabla = SgceGrupoContarActivos($Pdo);
    $GruposTabla = SgceGrupoListarPaginado($Pdo, $LimitGrupos, $OffsetGrupos);
}

if ($TabActual === 'alumnos') {
    $TotalAlumnosTabla = SgceAlumnoContarFiltrado($Pdo, $FiltroAlumnos);
    $Alumnos = SgceAlumnoListarFiltrado($Pdo, $FiltroAlumnos, $LimitAlumnos, $OffsetAlumnos);
}

if ($TabActual === 'asignaciones') {
    $TotalAsignacionesTabla = SgceAsignacionContarFiltradas($Pdo, $FiltroAsignaciones);
    $Asignaciones = SgceAsignacionListarFiltradas($Pdo, $FiltroAsignaciones, $LimitAsig, $OffsetAsig);
}

if ($TabActual === 'expedientes') {
    
    $ExpedienteGrupoId = intval($_GET['ExpGrupoId'] ?? 0);
    if ($ExpedienteGrupoId > 0) {
        $GrupoExpedienteSeleccionado = SgceGrupoObtenerActivoPorId($Pdo, $ExpedienteGrupoId);

        if ($GrupoExpedienteSeleccionado) {
            $AlumnosExpedientes = SgceAlumnoListarActivosPorGrupo($Pdo, $ExpedienteGrupoId);
        } else {
            $ExpedienteGrupoId = 0;
        }
    }
} else {
    $ExpedienteGrupoId = 0;
}

if ($TabActual === 'inicio') {
    $TotalAlumnosActivos = SgceAlumnoContarActivos($Pdo);
    $TotalMaestrosActivos = SgceMaestroContarActivos($Pdo);
    $TotalGruposActivos = SgceGrupoContarActivos($Pdo);
    $ResumenAsistenciaHoy = SgceAsistenciaResumenHoy($Pdo);
    $AsistenciasHoy = $ResumenAsistenciaHoy['Total'];
    $FaltasHoy = $ResumenAsistenciaHoy['Faltas'];
    $PromedioGeneral = SgceCalificacionPromedioGeneralCiclo($Pdo, $CicloActivoId);

    $AlumnosRiesgo = SgceReporteAlumnosRiesgo($Pdo, $CicloActivoId, $CicloFechaInicio, $CicloFechaFin, 10);

}

if ($TabActual === 'bitacora' && $PuedeVerBitacora) {
    try {
        $TotalBitacoraTabla = SgceReporteBitacoraContar($Pdo, $FiltroBitacora);
        $BitacoraReciente = SgceReporteBitacoraPaginada($Pdo, $FiltroBitacora, $LimitBitacora, $OffsetBitacora);
    } catch (Exception $E) {
        $BitacoraReciente = [];
$TotalBitacoraTabla = 0;
    }
}

?>
