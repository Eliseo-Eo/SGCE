<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }




$PageSizeAdmin = 7;
$PageSizeAsignaciones = 6;
$PagMaestros = SgcePaginaActual('PagMaestros');
$PagGrupos = SgcePaginaActual('PagGrupos');
$PagAlumnos = SgcePaginaActual('PagAlumnos');
$PagAsig = SgcePaginaActual('PagAsig');
[$OffsetMaestros, $LimitMaestros] = SgceLimitOffset($PagMaestros, $PageSizeAdmin);
[$OffsetGrupos, $LimitGrupos] = SgceLimitOffset($PagGrupos, $PageSizeAdmin);
[$OffsetAlumnos, $LimitAlumnos] = SgceLimitOffset($PagAlumnos, $PageSizeAdmin);
[$OffsetAsig, $LimitAsig] = SgceLimitOffset($PagAsig, $PageSizeAsignaciones);

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
    $TotalAlumnosTabla = SgceAlumnoContarActivos($Pdo);
    $Alumnos = SgceAlumnoListarPaginado($Pdo, $LimitAlumnos, $OffsetAlumnos);
}

if ($TabActual === 'asignaciones') {
    $TotalAsignacionesTabla = SgceAsignacionContarActivas($Pdo);
    $Asignaciones = SgceAsignacionListarPaginadas($Pdo, $LimitAsig, $OffsetAsig);
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
        $BitacoraReciente = SgceReporteBitacoraReciente($Pdo, 100);
    } catch (Exception $E) {
        $BitacoraReciente = [];
    }
}

?>
