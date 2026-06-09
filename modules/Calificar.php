<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'maestro') {
    header('Location: index.php');
    exit;
}

$AsignacionId = intval($_GET['AsignacionId'] ?? ($_POST['AsignacionId'] ?? 0));
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? ($_POST['PeriodoId'] ?? 0));
$PeriodosDisponibles = SgcePeriodosDisponibles($Pdo);
$ConfigCalificacion = SgceCalificacionConfig($Pdo);
$TextoRangoCalificacion = SgceCalificacionTextoRango($Pdo);

$Stmt = $Pdo->prepare("
    SELECT A.*, G.Grado, G.Grupo, G.Turno, C.Nombre AS CicloNombre
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id AND G.CicloId = A.CicloId
    JOIN CiclosEscolares C ON C.Id = A.CicloId AND C.Activo = 1
    WHERE A.Id = ? AND A.MaestroId = ? AND A.Activo = 1
");

$Stmt->execute([$AsignacionId, $UserSession['Id']]);
$InfoClase = $Stmt->fetch();

if (!$InfoClase) {
    SgceSalirConError('Acceso denegado o grupo no encontrado en el ciclo activo.', 404);
}
$CicloClaseId = (int)$InfoClase['CicloId'];
$PeriodoInfoCalificar = SgcePeriodoInfo($Pdo, $PeriodoId);
if (!$PeriodoInfoCalificar || (int)$PeriodoInfoCalificar['CicloId'] !== $CicloClaseId) {
    SgceSalirConError('El periodo seleccionado no pertenece al ciclo activo de esta asignación.', 400);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['GuardarNotes'])) {

    RequerirCsrfPost();

    $UrlError = 'Calificar.php?' . http_build_query([
        'AsignacionId' => $AsignacionId,
        'PeriodoId' => $PeriodoId,
        'Error' => 1
    ]);

    $Notas = $_POST['Notas'] ?? [];

    if (!is_array($Notas)) {
        header('Location: ' . $UrlError);
        exit;
    }

    $StmtAlumnosValidos = $Pdo->prepare("SELECT A.Id FROM AlumnoInscripciones AI INNER JOIN Alumnos A ON A.Id = AI.AlumnoId AND A.Activo = 1 WHERE AI.GrupoId = ? AND AI.CicloId = ? AND AI.Estado = 'INSCRITO'");
    $StmtAlumnosValidos->execute([$InfoClase['GrupoId'], $CicloClaseId]);
    $AlumnosValidos = array_flip(array_map('intval', $StmtAlumnosValidos->fetchAll(PDO::FETCH_COLUMN)));

    $StmtBuscarCalificacion = $Pdo->prepare("
        SELECT Id
        FROM Calificaciones
        WHERE AlumnoId = ?
        AND AsignacionId = ?
        AND PeriodoId = ?
        ORDER BY Id DESC
        LIMIT 1
    ");

    $StmtActualizarCalificacion = $Pdo->prepare("
        UPDATE Calificaciones
        SET Calificacion = ?
        WHERE Id = ?
    ");

    $StmtInsertarCalificacion = $Pdo->prepare("
        INSERT INTO Calificaciones (AlumnoId, AsignacionId, PeriodoId, Calificacion)
        VALUES (?, ?, ?, ?)
    ");

    $StmtEliminar = $Pdo->prepare("
        DELETE FROM Calificaciones
        WHERE AlumnoId = ?
        AND AsignacionId = ?
        AND PeriodoId = ?
    ");

    try {
        $Pdo->beginTransaction();

        foreach ($Notas as $AlumnoId => $Calificacion) {

            $AlumnoId = intval($AlumnoId);
            $Calificacion = trim((string)$Calificacion);

            if ($AlumnoId <= 0) {
                continue;
            }

            if (!isset($AlumnosValidos[$AlumnoId])) {
                continue;
            }

            if ($Calificacion === '') {
                $StmtEliminar->execute([$AlumnoId, $AsignacionId, $PeriodoId]);
                continue;
            }

            $CalificacionFloat = SgceCalificacionNormalizar($Pdo, $Calificacion);

            if ($CalificacionFloat === null) {
                continue;
            }

            $StmtBuscarCalificacion->execute([
                $AlumnoId,
                $AsignacionId,
                $PeriodoId
            ]);

            $CalificacionId = (int)$StmtBuscarCalificacion->fetchColumn();

            if ($CalificacionId > 0) {
                $StmtActualizarCalificacion->execute([
                    $CalificacionFloat,
                    $CalificacionId
                ]);
            } else {
                $StmtInsertarCalificacion->execute([
                    $AlumnoId,
                    $AsignacionId,
                    $PeriodoId,
                    $CalificacionFloat
                ]);
            }
        }

        $StmtLimpiarDuplicados = $Pdo->prepare("
            DELETE CalDuplicada
            FROM Calificaciones CalDuplicada
            INNER JOIN Calificaciones CalBase
                ON CalBase.AlumnoId = CalDuplicada.AlumnoId
                AND CalBase.AsignacionId = CalDuplicada.AsignacionId
                AND CalBase.PeriodoId = CalDuplicada.PeriodoId
                AND CalBase.Id > CalDuplicada.Id
            WHERE CalDuplicada.AsignacionId = ?
            AND CalDuplicada.PeriodoId = ?
        ");
        $StmtLimpiarDuplicados->execute([$AsignacionId, $PeriodoId]);

        $Pdo->commit();

        RegistrarBitacora($Pdo, $UserSession, 'GUARDAR_CALIFICACIONES', 'Calificaciones', $AsignacionId, 'CALIFICACIONES ACTUALIZADAS EN PERIODO ID ' . $PeriodoId);

    } catch (Exception $E) {

        if ($Pdo->inTransaction()) {
            $Pdo->rollBack();
        }

        header('Location: ' . $UrlError);
        exit;
    }

    header('Location: Calificar.php?' . http_build_query([
        'AsignacionId' => $AsignacionId,
        'PeriodoId' => $PeriodoId,
        'Success' => 1
    ]));
    exit;
}

$Stmt = $Pdo->prepare("
    SELECT
        Al.Id AS AlumnoId,
        Al.NombreCompleto,
        C.Calificacion
    FROM AlumnoInscripciones AI
    INNER JOIN Alumnos Al ON Al.Id = AI.AlumnoId
    LEFT JOIN (
        SELECT AlumnoId, MAX(Id) AS UltimaCalificacionId
        FROM Calificaciones
        WHERE AsignacionId = ?
        AND PeriodoId = ?
        GROUP BY AlumnoId
    ) CU ON CU.AlumnoId = Al.Id
    LEFT JOIN Calificaciones C
        ON C.Id = CU.UltimaCalificacionId
    WHERE AI.GrupoId = ?
    AND AI.CicloId = ?
    AND AI.Estado = 'INSCRITO'
    AND Al.Activo = 1
    ORDER BY Al.NombreCompleto ASC
");

$Stmt->execute([
    $AsignacionId,
    $PeriodoId,
    $InfoClase['GrupoId'],
    $CicloClaseId
]);

$Alumnos = $Stmt->fetchAll();

$TotalAlumnos = count($Alumnos);

$Calificados = 0;
$PromedioGrupo = 0;
$Suma = 0;

foreach ($Alumnos as $Al) {

    if ($Al['Calificacion'] !== null && $Al['Calificacion'] !== '') {

        $Calificados++;

        $Suma += floatval($Al['Calificacion']);
    }
}

if ($Calificados > 0) {

    $PromedioGrupo = round($Suma / $Calificados, 1);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    
    
    <link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/media/img/favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGCE | Evaluar Grupo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<?= SgceCss('assets/css/sgce-base.min.css') ?>
<?= SgceCss('assets/css/sgce-soft-motion.css') ?>
<?= SgceEstilosTema($Pdo) ?>

<?= SgceCss('assets/css/calificar-botones-metalicos.css') ?>

</head>

<body>


<div class="container py-3 SgcePage SgceModuleWrap">

    <div class="TopBar mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

            <div class="d-flex align-items-center gap-3">

                <div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">🎓</span></div>

                <div>
                    <h2>
                        <?= HGlobal($InfoClase['MateriaNombre']) ?>
                    </h2>

                    <div class="text-light opacity-75 mt-1">
                        Grupo <?= HGlobal($InfoClase['Grado']) ?> "<?= HGlobal($InfoClase['Grupo']) ?>"
                    </div>

                    <div class="InfoBadge">
                        <i class="fa-solid <?= strtoupper((string)$InfoClase['Turno']) === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>
                        Turno <?= HGlobal($InfoClase['Turno']) ?>
                    </div>
                </div>

            </div>

            <div>
                <a href="Maestro.php" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
            </div>

        </div>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="card StatsCard">

                <div class="card-body d-flex align-items-center gap-3 p-4">

                    <div class="StatsIcon SgceStatsIconSoft SgceStatsAlumnos"><span class="SgceColorIcon" aria-hidden="true">👥</span></div>

                    <div>
                        <div class="text-muted small">
                            Total De Alumnos
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= (int)$TotalAlumnos ?>
                        </h3>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card StatsCard">

                <div class="card-body d-flex align-items-center gap-3 p-4">

                    <div class="StatsIcon SgceStatsIconSoft SgceStatsCalificados"><span class="SgceColorIcon" aria-hidden="true">✅</span></div>

                    <div>
                        <div class="text-muted small">
                            Calificados
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= (int)$Calificados ?>
                        </h3>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card StatsCard">

                <div class="card-body d-flex align-items-center gap-3 p-4">

                    <div class="StatsIcon SgceStatsIconSoft SgceStatsPromedio"><span class="SgceColorIcon" aria-hidden="true">📈</span></div>

                    <div>
                        <div class="text-muted small">
                            Promedio General
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= HGlobal($PromedioGrupo) ?>
                        </h3>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php if(isset($_GET['Success'])): ?>

        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>
            Calificaciones Guardadas Correctamente.
        </div>

    <?php endif; ?>

    <div id="JsAlert" class="alert alert-warning border-0 shadow-sm d-none mb-4"></div>

    <div class="card MainCard">

        <div class="card-header d-flex justify-content-between align-items-center">

            <div>
                <h5 class="fw-bold mb-1">
                    Lista De Evaluación
                </h5>

                <span class="text-muted small">
                    Captura Las Calificaciones Del Grupo · Escala <?= HGlobal($TextoRangoCalificacion) ?>
                </span>
            </div>

            <div class="badge bg-dark px-3 py-2 rounded-pill">
                <?= (int)$TotalAlumnos ?> Alumnos
            </div>

        </div>

        <div class="card-body p-0">

            <form method="POST" id="FormCalificaciones" data-calificacion-min="<?= HGlobal((string)$ConfigCalificacion['Minima']) ?>" data-calificacion-max="<?= HGlobal((string)$ConfigCalificacion['Maxima']) ?>" data-calificacion-aprobatoria="<?= HGlobal((string)$ConfigCalificacion['Aprobatoria']) ?>">
                        <input type="hidden" name="AsignacionId" value="<?= (int)$AsignacionId ?>">
                        <input type="hidden" name="PeriodoId" value="<?= (int)$PeriodoId ?>">
                        <div class="SgcePeriodoBox mb-3">
                            <label for="PeriodoIdSelect"><i class="fa-solid fa-calendar-days"></i> Periodo de evaluación</label>
                            <select id="PeriodoIdSelect" class="form-select" data-asignacion-id="<?= (int)$AsignacionId ?>">
                                <?php foreach ($PeriodosDisponibles as $Periodo): ?>
                                    <option value="<?= (int)$Periodo['Id'] ?>" <?= (int)$Periodo['Id'] === (int)$PeriodoId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($Periodo['CicloNombre'] . ' - ' . $Periodo['Nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php echo CampoCsrf(); ?>

                <input type="hidden" name="GuardarNotes">

                <div class="table-responsive">

                    <table class="table align-middle mb-0">

                        <thead>

                            <tr>
                                <th>
                                    Alumno
                                </th>

                                <th class="text-center" style="width:180px;">
                                    Calificación
                                </th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if(empty($Alumnos)): ?>

                                <tr>

                                    <td colspan="2" class="text-center py-5 text-muted">

                                        <i class="fa-solid fa-folder-open fa-2x mb-3"></i>

                                        <div>
                                            No Hay Alumnos Registrados
                                        </div>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach($Alumnos as $Al): ?>

                                    <tr class="AlumnoRow">

                                        <td>

                                            <div class="d-flex align-items-center gap-3">

                                                <div class="AlumnoAvatar AlumnoAvatarEmoji">
                                                    <span class="SgceAlumnoEmoji" aria-hidden="true">🧑‍🎓</span>
                                                </div>

                                                <div>

                                                    <div class="AlumnoNombre">
                                                        <?= HGlobal($Al['NombreCompleto']) ?>
                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <input
                                                type="number"
                                                name="Notas[<?= (int)$Al['AlumnoId'] ?>]"
                                                class="form-control text-center InputNota"
                                                step="<?= !empty($ConfigCalificacion['Decimales']) ? '0.01' : '1' ?>"
                                                min="<?= HGlobal((string)$ConfigCalificacion['Minima']) ?>"
                                                max="<?= HGlobal((string)$ConfigCalificacion['Maxima']) ?>"
                                                placeholder="-"
                                                data-original="<?= HGlobal($Al['Calificacion'] !== null ? $Al['Calificacion'] : '') ?>"
                                                value="<?= HGlobal($Al['Calificacion'] !== null ? $Al['Calificacion'] : '') ?>"
                                            >

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <?php if(!empty($Alumnos)): ?>

                    <div class="SgceStickyActions border-top d-flex justify-content-end">

                        <button type="submit" id="BtnGuardarCalificacionesVerdeMetalico" class="btn BtnGuardar BtnGuardarCalificacionesVerdeMetalico text-white">

                            <span class="SgceColorIcon me-2" aria-hidden="true">💾</span>
                            <span>Guardar Calificaciones</span>

                        </button>

                    </div>

                <?php endif; ?>

            </form>

        </div>

    </div>

</div>











<?php ImprimirCsrfScript(); ?>
<?= SgceJs('assets/js/sgce-shared.js') ?>
<?= SgceJs('assets/js/Calificar.js') ?>
</body>
</html>
