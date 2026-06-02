<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';
require_once dirname(__DIR__) . '/includes/SGCE_PublicConsultas.php';
SgcePublicoEnviarHeaders();

$ConfigSistema = SgceObtenerConfiguracion($Pdo);
$NombreEscuelaConsulta = trim((string)($ConfigSistema['NombreEscuela'] ?? 'SGCE'));
$UrlInicioProyecto = SgcePublicoUrlRaizProyecto();
$Resultado = null;
$Error = '';

$StmtAvisosPadres = $Pdo->query("SELECT Titulo, Mensaje, FechaCreacion FROM Avisos WHERE Activo = 1 AND Publico IN ('TODOS','PADRES') ORDER BY FechaCreacion DESC LIMIT 3");
$AvisosPadres = $StmtAvisosPadres ? $StmtAvisosPadres->fetchAll() : [];
[$GradosDisponibles, $GruposDisponibles] = SgcePublicoCatalogos($Pdo);

$NombreAlumno = '';
$Grado = '';
$Grupo = '';
$Turno = '';

$ConsultaToken = SgcePublicoTokenDesdeGet();
if ($ConsultaToken !== '' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $ConsultaToken !== '') {
    $ConsultaGuardada = SgcePublicoLeerTokenConsulta($ConsultaToken, 'calificaciones');
    if (!$ConsultaGuardada) {
        $Error = 'LA CONSULTA EXPIRÓ O YA NO ES VÁLIDA. REALIZA LA BÚSQUEDA NUEVAMENTE.';
    } else {
        $Datos = $ConsultaGuardada['Datos'] ?? [];
        $NombreAlumno = SgceNormalizarMayusculas($Datos['NombreAlumno'] ?? '');
        $Grado = SgceNormalizarMayusculas($Datos['Grado'] ?? '');
        $Grupo = SgcePublicoNormalizarGrupo($Datos['Grupo'] ?? '');
        $Turno = SgceNormalizarMayusculas($Datos['Turno'] ?? '');

        $DatosAlumno = SgcePublicoBuscarAlumno($Pdo, $NombreAlumno, $Grado, $Grupo, $Turno, $Error);
        if ($DatosAlumno) {
            $Alumno = $DatosAlumno['Alumno'];
            $InfoGrupo = $DatosAlumno['Grupo'];
            $Calificaciones = SgcePublicoCalificacionesCiclo($Pdo, (int)$Alumno['Id'], (int)$InfoGrupo['Id']);
            $Resultado = [
                'Alumno' => $Alumno['NombreCompleto'],
                'AlumnoId' => (int)$Alumno['Id'],
                'Grado' => $InfoGrupo['Grado'],
                'Grupo' => $InfoGrupo['Grupo'],
                'Turno' => $InfoGrupo['Turno'],
            ] + $Calificaciones;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RequerirCsrfPost();

    $NombreAlumno = SgceNormalizarMayusculas($_POST['NombreAlumno'] ?? '');
    $Grado = SgceNormalizarMayusculas($_POST['Grado'] ?? '');
    $Grupo = SgcePublicoNormalizarGrupo($_POST['Grupo'] ?? '');
    $Turno = SgceNormalizarMayusculas($_POST['Turno'] ?? '');

    $RateKey = SgcePublicoRateKey($NombreAlumno, $Grado, $Grupo, $Turno);
    if (SgcePublicoHoneypotActivado()) {
        SgcePublicoRegistrarFallo($Pdo, 'consulta_calificaciones', $RateKey, 4, 12, 30);
        $Error = 'NO FUE POSIBLE PROCESAR LA CONSULTA. REVISA LOS DATOS E INTENTA NUEVAMENTE.';
    } elseif (!SgcePublicoRateDisponible($Pdo, 'consulta_calificaciones', $RateKey)) {
        $Error = 'DEMASIADOS INTENTOS DE CONSULTA. ESPERA 15 MINUTOS E INTENTA NUEVAMENTE.';
    } else {
        $DatosAlumno = SgcePublicoBuscarAlumno($Pdo, $NombreAlumno, $Grado, $Grupo, $Turno, $Error);
        if (!$DatosAlumno) {
            SgcePublicoRegistrarFallo($Pdo, 'consulta_calificaciones', $RateKey, 8, 24, 15);
        } else {
            $Alumno = $DatosAlumno['Alumno'];
            SgcePublicoLimpiarFalloExacto($Pdo, 'consulta_calificaciones', $RateKey);
            RegistrarBitacora($Pdo, ['Id' => null, 'Rol' => 'publico'], 'CONSULTA_PADRE_CALIFICACIONES', 'Alumnos', (int)$Alumno['Id'], 'CONSULTA PÚBLICA DE CALIFICACIONES');

            $ConsultaToken = SgcePublicoCrearTokenConsulta('calificaciones', [
                'NombreAlumno' => $NombreAlumno,
                'Grado' => $Grado,
                'Grupo' => $Grupo,
                'Turno' => $Turno,
            ]);
            header('Location: ConsultaCalificaciones.php?ConsultaToken=' . urlencode($ConsultaToken));
            exit;
        }
    }
}

function HCC($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= HCC($NombreEscuelaConsulta) ?> | Consulta De Calificaciones</title>
    <link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/media/img/favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sgce-base.min.css?v=sgce">
<link rel="stylesheet" href="assets/css/sgce-soft-motion.css?v=sgce">
    <?= SgceEstilosTema($Pdo) ?>
    <link rel="stylesheet" href="assets/css/consulta-publica-botones-metalicos.css?v=sgce">
</head>
<body class="ConsultaPublicaBody ConsultaCalificacionesBody">

<main class="ConsultaPublicaWrap">

    <section class="ConsultaHero ConsultaHeroCompact ConsultaHeroCalificaciones">
        <div class="ConsultaHeroMain">
            <div class="ConsultaHeroIcon"><span class="SgceColorIcon" aria-hidden="true">📈</span></div>
            <div class="ConsultaHeroText">
                <div class="ConsultaSchoolName"><?= HCC($NombreEscuelaConsulta) ?></div>
                <h1>Consulta de calificaciones</h1>
                <p>Consulta calificaciones por materia y parcial del ciclo activo. También puedes descargar la boleta del alumno consultado.</p>
            </div>
        </div>
        <div class="ConsultaHeroActions">
            <a href="<?= HCC($UrlInicioProyecto) ?>" class="SgceBtnVolverInicio" title="Regresar al inicio" aria-label="Regresar al inicio" id="SgceConsultaBack"><i class="fa-solid fa-house"></i><span>Regresar al inicio</span></a>
        </div>
    </section>

    <?php if(!empty($AvisosPadres)): ?>
        <section class="ConsultaAvisosCard ConsultaPadresAvisosPanel mb-4">
            <div class="ConsultaPadresAvisosHeader">
                <div class="ConsultaPadresAvisosTitleBlock">
                    <div class="ConsultaPadresAvisosIcon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div><span class="ConsultaPadresAvisosEyebrow">Comunicación escolar</span><h5>Avisos para padres</h5></div>
                </div>
                <span class="ConsultaPadresAvisosBadge"><?= count($AvisosPadres) === 1 ? '1 aviso' : count($AvisosPadres) . ' avisos' ?></span>
            </div>
            <div class="ConsultaPadresAvisosGrid">
                <?php foreach($AvisosPadres as $Aviso): ?>
                    <article class="ConsultaPadresAvisoItem">
                        <div class="ConsultaPadresAvisoItemIcon"><i class="fa-solid fa-bell"></i></div>
                        <div class="ConsultaPadresAvisoItemBody">
                            <h6><?= HCC($Aviso['Titulo']) ?></h6>
                            <div class="ConsultaPadresAvisoFecha"><i class="fa-regular fa-clock"></i><?= HCC(date('d/m/Y H:i', strtotime($Aviso['FechaCreacion']))) ?></div>
                            <p><?= nl2br(HCC($Aviso['Mensaje'])) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="ConsultaGrid ConsultaGridCalificaciones">
        <div>
            <div class="ConsultaCard">
                <h4 class="fw-bold mb-1"><span class="SgceColorIcon SgceTitleIcon" aria-hidden="true">🔎</span>Buscar alumno</h4>
                <p class="text-muted mb-4">Escribe el nombre completo y selecciona grado, grupo y turno. No se muestran listas completas por privacidad.</p>

                <?php if($Error): ?>
                    <div class="alert alert-danger mb-4"><i class="fa-solid fa-circle-exclamation me-2"></i><?= HCC($Error) ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <?php echo CampoCsrf(); ?>
                    <?php echo SgcePublicoCampoHoneypot(); ?>
                    <div class="mb-3">
                        <label>Nombre completo del alumno</label>
                        <input type="text" name="NombreAlumno" class="form-control SoloLetrasMayus" placeholder="NOMBRE COMPLETO" value="<?= HCC($NombreAlumno) ?>" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label>Grado</label>
                            <select name="Grado" class="form-select" required>
                                <option value="">GRADO</option>
                                <?php foreach($GradosDisponibles as $G): ?>
                                    <option value="<?= HCC($G['Grado']) ?>" <?= $Grado === $G['Grado'] ? 'selected' : '' ?>><?= HCC($G['Grado']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Grupo</label>
                            <select name="Grupo" class="form-select" required>
                                <option value="">GRUPO</option>
                                <?php foreach($GruposDisponibles as $G): ?>
                                    <option value="<?= HCC($G['Grupo']) ?>" <?= $Grupo === $G['Grupo'] ? 'selected' : '' ?>><?= HCC($G['Grupo']) ?></option>
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
                    <button type="submit" id="BtnConsultaPublicaCalificacionesVerdeMetalico" class="BtnPrincipal BtnConsultaPublicaVerdeMetalico mt-4"><span class="SgceColorIcon" aria-hidden="true">⭐</span>CONSULTAR CALIFICACIONES</button>
                </form>

                <div class="AvisoPrivacidad mt-4"><i class="fa-solid fa-shield-halved me-2"></i>Por seguridad, la consulta requiere coincidencia exacta; no muestra listas completas ni permite navegar alumnos.</div>
            </div>
        </div>

        <div>
            <div class="ResultadoCard ResultadoCalificacionesCard">
                <?php if($Resultado): ?>
                    <?php $PromedioTexto = $Resultado['PromedioGeneral'] !== null ? number_format((float)$Resultado['PromedioGeneral'], 2) : '-'; ?>
                    <div class="EstadoBox <?= $Resultado['PromedioGeneral'] !== null ? 'success' : 'secondary' ?> mb-4">
                        <div class="EstadoIcon"><span class="SgceColorIcon" aria-hidden="true">📊</span></div>
                        <h3 class="fw-bold mb-2">PROMEDIO GENERAL: <?= HCC($PromedioTexto) ?></h3>
                        <p class="mb-0">Calificaciones capturadas del ciclo activo <?= HCC($Resultado['Ciclo']['Nombre'] ?? 'sin ciclo activo') ?>.</p>
                    </div>

                    <div class="mb-4">
                        <h5 class="fw-bold mb-2"><i class="fa-solid fa-user-graduate text-danger me-2"></i><?= HCC($Resultado['Alumno']) ?></h5>
                        <div class="text-muted fw-semibold"><?= HCC($Resultado['Grado']) ?> "<?= HCC($Resultado['Grupo']) ?>" · <?= HCC($Resultado['Turno']) ?> · <?= HCC($Resultado['Ciclo']['Nombre'] ?? 'SIN CICLO ACTIVO') ?></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= HCC($PromedioTexto) ?></div><div class="Texto">PROMEDIO</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Materias'] ?></div><div class="Texto">MATERIAS</div></div></div>
                        <div class="col-6 col-md-4"><div class="MetricCard"><div class="Numero"><?= (int)$Resultado['Capturadas'] ?></div><div class="Texto">CALIFICACIONES</div></div></div>
                    </div>

                    <div class="ConsultaPublicActions mt-4">
                        <form method="GET" action="ExportarBoletaPublica.php" target="_blank" rel="noopener noreferrer">
                            <input type="hidden" name="ConsultaToken" value="<?= HCC($ConsultaToken) ?>">
                            <button type="submit" class="BtnPrincipal BtnPdfPublico"><i class="fa-solid fa-file-pdf"></i>DESCARGAR BOLETA PDF</button>
                        </form>
                    </div>

                    <div class="ConsultaDetalleTable mt-4">
                        <div class="ConsultaDetalleHeader"><h4><i class="fa-solid fa-table-list"></i>Calificaciones por materia</h4><span><?= count($Resultado['Filas']) ?> materias</span></div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 ConsultaCalificacionesTable">
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <?php foreach($Resultado['Periodos'] as $P): ?><th class="text-center"><?= HCC($P['Nombre']) ?></th><?php endforeach; ?>
                                        <th class="text-center">Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($Resultado['Filas'] as $Fila): ?>
                                        <tr>
                                            <td><strong><?= HCC($Fila['MateriaNombre']) ?></strong><br><small class="text-muted"><?= HCC($Fila['Maestro']) ?></small></td>
                                            <?php foreach($Resultado['Periodos'] as $P): ?>
                                                <td class="text-center"><span class="ExpedienteGradeBadge"><?= HCC(SgcePublicoFormatoCalificacion($Fila['Valores'][(int)$P['Id']] ?? null)) ?></span></td>
                                            <?php endforeach; ?>
                                            <td class="text-center"><span class="ExpedienteGradeBadge PromedioBadge"><?= HCC(SgcePublicoFormatoCalificacion($Fila['PromedioMateria'])) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($Resultado['Filas'])): ?>
                                        <tr><td colspan="<?= 2 + count($Resultado['Periodos']) ?>"><div class="ExpedienteEmptyState"><i class="fa-solid fa-circle-info"></i><span>Sin materias o calificaciones capturadas en el ciclo activo.</span></div></td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="EstadoIcon mb-3"><span class="SgceColorIcon" aria-hidden="true">📈</span></div>
                        <h3 class="fw-bold">Resultado de la consulta</h3>
                        <p class="text-muted mb-0">Aquí aparecerán las calificaciones y la opción para descargar la boleta.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?v=sgce"></script>
<script src="assets/js/ConsultaPadre.js?v=sgce"></script>
</body>
</html>
