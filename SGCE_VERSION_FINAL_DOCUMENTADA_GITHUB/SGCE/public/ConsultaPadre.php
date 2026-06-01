<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_PublicConsultas.php';
SgcePublicoEnviarHeaders();

$ConfigSistema = SgceObtenerConfiguracion($Pdo);
$NombreEscuelaConsulta = trim((string)($ConfigSistema['NombreEscuela'] ?? 'SGCE'));
$UrlInicioProyecto = SgcePublicoUrlRaizProyecto();
$Hoy = date('Y-m-d');
$Resultado = null;
$Error = '';

$StmtAvisosPadres = $Pdo->query("SELECT Titulo, Mensaje, FechaCreacion FROM Avisos WHERE Activo = 1 AND Publico IN ('TODOS','PADRES') ORDER BY FechaCreacion DESC LIMIT 3");
$AvisosPadres = $StmtAvisosPadres ? $StmtAvisosPadres->fetchAll() : [];
[$GradosDisponibles, $GruposDisponibles] = SgcePublicoCatalogos($Pdo);

$NombreAlumno = '';
$Grado = '';
$Grupo = '';
$Turno = '';
$FechaInicio = $Hoy;
$FechaFin = $Hoy;

$ConsultaToken = SgcePublicoTokenDesdeGet();
if ($ConsultaToken !== '' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $ConsultaToken !== '') {
    $ConsultaGuardada = SgcePublicoLeerTokenConsulta($ConsultaToken, 'asistencia');
    if (!$ConsultaGuardada) {
        $Error = 'LA CONSULTA EXPIRÓ O YA NO ES VÁLIDA. REALIZA LA BÚSQUEDA NUEVAMENTE.';
    } else {
        $Datos = $ConsultaGuardada['Datos'] ?? [];
        $NombreAlumno = SgceNormalizarMayusculas($Datos['NombreAlumno'] ?? '');
        $Grado = SgceNormalizarMayusculas($Datos['Grado'] ?? '');
        $Grupo = SgcePublicoNormalizarGrupo($Datos['Grupo'] ?? '');
        $Turno = SgceNormalizarMayusculas($Datos['Turno'] ?? '');
        $FechaInicio = SgcePublicoNormalizarFecha($Datos['FechaInicio'] ?? $Hoy, $Hoy);
        $FechaFin = SgcePublicoNormalizarFecha($Datos['FechaFin'] ?? $Hoy, $Hoy);
        [$FechaInicio, $FechaFin] = SgcePublicoValidarRangoFechas($FechaInicio, $FechaFin, $Error, 60);

        if ($Error === '') {
            $DatosAlumno = SgcePublicoBuscarAlumno($Pdo, $NombreAlumno, $Grado, $Grupo, $Turno, $Error);
            if ($DatosAlumno) {
                $Alumno = $DatosAlumno['Alumno'];
                $InfoGrupo = $DatosAlumno['Grupo'];
                $Resumen = SgcePublicoResumenAsistencia($Pdo, (int)$Alumno['Id'], (int)$InfoGrupo['Id'], $FechaInicio, $FechaFin);

                $Resultado = [
                    'Alumno' => $Alumno['NombreCompleto'],
                    'AlumnoId' => (int)$Alumno['Id'],
                    'Grado' => $InfoGrupo['Grado'],
                    'Grupo' => $InfoGrupo['Grupo'],
                    'Turno' => $InfoGrupo['Turno'],
                    'FechaInicio' => $FechaInicio,
                    'FechaFin' => $FechaFin,
                ] + $Resumen;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RequerirCsrfPost();

    $NombreAlumno = SgceNormalizarMayusculas($_POST['NombreAlumno'] ?? '');
    $Grado = SgceNormalizarMayusculas($_POST['Grado'] ?? '');
    $Grupo = SgcePublicoNormalizarGrupo($_POST['Grupo'] ?? '');
    $Turno = SgceNormalizarMayusculas($_POST['Turno'] ?? '');
    $FechaInicio = SgcePublicoNormalizarFecha($_POST['FechaInicio'] ?? $Hoy, $Hoy);
    $FechaFin = SgcePublicoNormalizarFecha($_POST['FechaFin'] ?? $Hoy, $Hoy);

    $RateKey = SgcePublicoRateKey($NombreAlumno, $Grado, $Grupo, $Turno);
    if (SgcePublicoHoneypotActivado()) {
        SgcePublicoRegistrarFallo($Pdo, 'consulta_padre', $RateKey, 4, 12, 30);
        $Error = 'NO FUE POSIBLE PROCESAR LA CONSULTA. REVISA LOS DATOS E INTENTA NUEVAMENTE.';
    } elseif (!SgcePublicoRateDisponible($Pdo, 'consulta_padre', $RateKey)) {
        $Error = 'DEMASIADOS INTENTOS DE CONSULTA. ESPERA 15 MINUTOS E INTENTA NUEVAMENTE.';
    } else {
        [$FechaInicio, $FechaFin] = SgcePublicoValidarRangoFechas($FechaInicio, $FechaFin, $Error, 60);
        if ($Error === '') {
            $DatosAlumno = SgcePublicoBuscarAlumno($Pdo, $NombreAlumno, $Grado, $Grupo, $Turno, $Error);
            if (!$DatosAlumno) {
                SgcePublicoRegistrarFallo($Pdo, 'consulta_padre', $RateKey, 8, 24, 15);
            } else {
                $Alumno = $DatosAlumno['Alumno'];
                SgcePublicoLimpiarFalloExacto($Pdo, 'consulta_padre', $RateKey);
                RegistrarBitacora($Pdo, ['Id' => null, 'Rol' => 'publico'], 'CONSULTA_PADRE_ASISTENCIA', 'Alumnos', (int)$Alumno['Id'], 'CONSULTA PÚBLICA DE ASISTENCIA');

                $ConsultaToken = SgcePublicoCrearTokenConsulta('asistencia', [
                    'NombreAlumno' => $NombreAlumno,
                    'Grado' => $Grado,
                    'Grupo' => $Grupo,
                    'Turno' => $Turno,
                    'FechaInicio' => $FechaInicio,
                    'FechaFin' => $FechaFin,
                ]);
                header('Location: ConsultaPadre.php?ConsultaToken=' . urlencode($ConsultaToken));
                exit;
            }
        }
    }
}

function HCP($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function FechaHumanaCP($Fecha) { return date('d/m/Y', strtotime((string)$Fecha)); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= HCP($NombreEscuelaConsulta) ?> | Consulta De Asistencia</title>
    <link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/media/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026final">
    <?= SgceEstilosTema($Pdo) ?>
    <link rel="stylesheet" href="assets/css/consulta-publica-botones-metalicos.css?cache=sgce2026final">
</head>
<body class="ConsultaPublicaBody">

<main class="ConsultaPublicaWrap">

    <section class="ConsultaHero ConsultaHeroCompact">
        <div class="ConsultaHeroMain">
            <div class="ConsultaHeroIcon"><span class="SgceColorIcon" aria-hidden="true">📅</span></div>
            <div class="ConsultaHeroText">
                <div class="ConsultaSchoolName"><?= HCP($NombreEscuelaConsulta) ?></div>
                <h1>Consulta de asistencia</h1>
                <p>Consulta el estado de asistencia por día, semana, mes o rango personalizado. Solo se muestra el alumno que coincida exactamente con los datos capturados.</p>
            </div>
        </div>
        <div class="ConsultaHeroActions">
            <a href="<?= HCP($UrlInicioProyecto) ?>" class="SgceBtnVolverInicio" title="Regresar al inicio" aria-label="Regresar al inicio" id="SgceConsultaBack"><i class="fa-solid fa-house"></i><span>Regresar al inicio</span></a>
        </div>
    </section>

    <?php if(!empty($AvisosPadres)): ?>
        <section class="ConsultaAvisosCard ConsultaPadresAvisosPanel mb-4">
            <div class="ConsultaPadresAvisosHeader">
                <div class="ConsultaPadresAvisosTitleBlock">
                    <div class="ConsultaPadresAvisosIcon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div>
                        <span class="ConsultaPadresAvisosEyebrow">Comunicación escolar</span>
                        <h5>Avisos para padres</h5>
                    </div>
                </div>
                <span class="ConsultaPadresAvisosBadge"><?= count($AvisosPadres) === 1 ? '1 aviso' : count($AvisosPadres) . ' avisos' ?></span>
            </div>
            <div class="ConsultaPadresAvisosGrid">
                <?php foreach($AvisosPadres as $Aviso): ?>
                    <article class="ConsultaPadresAvisoItem">
                        <div class="ConsultaPadresAvisoItemIcon"><i class="fa-solid fa-bell"></i></div>
                        <div class="ConsultaPadresAvisoItemBody">
                            <h6><?= HCP($Aviso['Titulo']) ?></h6>
                            <div class="ConsultaPadresAvisoFecha"><i class="fa-regular fa-clock"></i><?= HCP(date('d/m/Y H:i', strtotime($Aviso['FechaCreacion']))) ?></div>
                            <p><?= nl2br(HCP($Aviso['Mensaje'])) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="ConsultaGrid">
        <div>
            <div class="ConsultaCard">
                <h4 class="fw-bold mb-1"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🔎</span>Buscar alumno</h4>
                <p class="text-muted mb-4">Escribe el nombre completo y selecciona grado, grupo, turno y rango de fechas.</p>

                <?php if($Error): ?>
                    <div class="alert alert-danger mb-4"><i class="fa-solid fa-circle-exclamation me-2"></i><?= HCP($Error) ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <?php echo CampoCsrf(); ?>
                    <?php echo SgcePublicoCampoHoneypot(); ?>
                    <div class="mb-3">
                        <label>Nombre completo del alumno</label>
                        <input type="text" name="NombreAlumno" class="form-control SoloLetrasMayus" placeholder="NOMBRE COMPLETO" value="<?= HCP($NombreAlumno) ?>" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Grado</label>
                            <select name="Grado" class="form-select" required>
                                <option value="">GRADO</option>
                                <?php foreach($GradosDisponibles as $G): ?>
                                    <option value="<?= HCP($G['Grado']) ?>" <?= $Grado === $G['Grado'] ? 'selected' : '' ?>><?= HCP($G['Grado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Grupo</label>
                            <select name="Grupo" class="form-select" required>
                                <option value="">GRUPO</option>
                                <?php foreach($GruposDisponibles as $G): ?>
                                    <option value="<?= HCP($G['Grupo']) ?>" <?= $Grupo === $G['Grupo'] ? 'selected' : '' ?>><?= HCP($G['Grupo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Turno</label>
                            <select name="Turno" class="form-select" required>
                                <option value="">TURNO</option>
                                <option value="MATUTINO" <?= $Turno === 'MATUTINO' ? 'selected' : '' ?>>MATUTINO</option>
                                <option value="VESPERTINO" <?= $Turno === 'VESPERTINO' ? 'selected' : '' ?>>VESPERTINO</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label>Desde</label>
                            <input type="date" name="FechaInicio" class="form-control" max="<?= HCP($Hoy) ?>" value="<?= HCP($FechaInicio) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label>Hasta</label>
                            <input type="date" name="FechaFin" class="form-control" max="<?= HCP($Hoy) ?>" value="<?= HCP($FechaFin) ?>" required>
                        </div>
                    </div>

                    <div class="ConsultaQuickRanges" aria-label="Rangos rápidos">
                        <button type="button" data-range="hoy">Hoy</button>
                        <button type="button" data-range="semana">Últimos 7 días</button>
                        <button type="button" data-range="mes">Mes actual</button>
                    </div>

                    <button type="submit" id="BtnConsultaPublicaAsistenciaVerdeMetalico" class="BtnPrincipal BtnConsultaPublicaVerdeMetalico mt-4"><span class="SgceColorIcon" aria-hidden="true">📅</span>CONSULTAR ASISTENCIA</button>
                </form>

                <div class="AvisoPrivacidad mt-4"><i class="fa-solid fa-shield-halved me-2"></i>Por seguridad, la consulta requiere coincidencia exacta y no muestra listas completas.</div>
            </div>
        </div>

        <div>
            <div class="ResultadoCard">
                <?php if($Resultado): ?>
                    <div class="EstadoBox <?= HCP($Resultado['ClaseEstado']) ?> mb-4">
                        <div class="EstadoIcon"><span class="SgceColorIcon" aria-hidden="true"><?= HCP($Resultado['IconoEmoji'] ?? '📋') ?></span></div>
                        <h3 class="fw-bold mb-2"><?= HCP($Resultado['EstatusGeneral']) ?></h3>
                        <p class="mb-0"><?= HCP($Resultado['Descripcion']) ?></p>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-user-graduate text-danger me-2"></i><?= HCP($Resultado['Alumno']) ?></h5>
                        <div class="text-muted fw-semibold"><?= HCP($Resultado['Grado']) ?> "<?= HCP($Resultado['Grupo']) ?>" · <?= HCP($Resultado['Turno']) ?> · <?= HCP(FechaHumanaCP($Resultado['FechaInicio']) . ' al ' . FechaHumanaCP($Resultado['FechaFin'])) ?></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['A'] ?></div><div class="Texto">ASISTENCIAS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['F'] ?></div><div class="Texto">FALTAS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['R'] ?></div><div class="Texto">RETARDOS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Conteos']['J'] ?></div><div class="Texto">JUSTIFICANTES</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['RegistrosCapturados'] ?></div><div class="Texto">REGISTROS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['TotalMaterias'] ?></div><div class="Texto">MATERIAS</div></div></div>
                    </div>

                    <?php if($Resultado['SinCapturarHoy'] !== null): ?>
                        <div class="AvisoPrivacidad mt-4"><strong>Pendientes de hoy:</strong> <?= (int)$Resultado['SinCapturarHoy'] ?> materia(s) sin pase de lista capturado.</div>
                    <?php endif; ?>

                    <div class="ConsultaPublicActions mt-4">
                        <form method="GET" action="ExportarConsultaAsistencia.php" target="_blank" rel="noopener noreferrer">
                            <input type="hidden" name="ConsultaToken" value="<?= HCP($ConsultaToken) ?>">
                            <button type="submit" class="BtnPrincipal BtnPdfPublico"><i class="fa-solid fa-file-pdf"></i>DESCARGAR ASISTENCIA PDF</button>
                        </form>
                    </div>

                    <div class="ConsultaDetalleTable mt-4">
                        <div class="ConsultaDetalleHeader"><h4><i class="fa-solid fa-list-check"></i>Detalle de asistencias</h4><span><?= count($Resultado['Detalle']) ?> registros</span></div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Fecha</th><th>Materia</th><th>Docente</th><th>Estado</th></tr></thead>
                                <tbody>
                                    <?php foreach($Resultado['Detalle'] as $D): ?>
                                        <tr>
                                            <td><?= HCP($D['FechaTexto']) ?></td>
                                            <td><?= HCP($D['MateriaNombre']) ?></td>
                                            <td><?= HCP($D['Maestro']) ?></td>
                                            <td><span class="ExpedienteEstadoBadge Estado<?= HCP($D['Estado']) ?>"><?= HCP(SgcePublicoTextoEstado($D['Estado'])) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($Resultado['Detalle'])): ?>
                                        <tr><td colspan="4"><div class="ExpedienteEmptyState"><i class="fa-solid fa-circle-info"></i><span>Sin registros capturados en el rango seleccionado.</span></div></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="EstadoIcon mb-3"><span class="SgceColorIcon" aria-hidden="true">📋</span></div>
                        <h3 class="fw-bold">Resultado de la consulta</h3>
                        <p class="text-muted mb-0">Aquí aparecerá la asistencia del alumno consultado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?cache=sgce2026final"></script>
<script src="assets/js/ConsultaPadre.js?cache=sgce2026final"></script>
</body>
</html>
