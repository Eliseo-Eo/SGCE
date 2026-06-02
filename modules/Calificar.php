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

$Stmt = $Pdo->prepare("
    SELECT A.*, G.Grado, G.Grupo, G.Turno
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    WHERE A.Id = ? AND A.MaestroId = ? AND A.Activo = 1
");

$Stmt->execute([$AsignacionId, $UserSession['Id']]);
$InfoClase = $Stmt->fetch();

if (!$InfoClase) {
    SgceSalirConError('Acceso denegado o grupo no encontrado.', 404);
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

    $StmtAlumnosValidos = $Pdo->prepare("SELECT Id FROM Alumnos WHERE GrupoId = ? AND Activo = 1");
    $StmtAlumnosValidos->execute([$InfoClase['GrupoId']]);
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

            if (!is_numeric($Calificacion)) {
                continue;
            }

            $CalificacionFloat = round((float)$Calificacion, 2);

            if ($CalificacionFloat < 5) { $CalificacionFloat = 5; }
            if ($CalificacionFloat > 10) { $CalificacionFloat = 10; }

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
    FROM Alumnos Al
    LEFT JOIN (
        SELECT AlumnoId, MAX(Id) AS UltimaCalificacionId
        FROM Calificaciones
        WHERE AsignacionId = ?
        AND PeriodoId = ?
        GROUP BY AlumnoId
    ) CU ON CU.AlumnoId = Al.Id
    LEFT JOIN Calificaciones C
        ON C.Id = CU.UltimaCalificacionId
    WHERE Al.GrupoId = ?
    AND Al.Activo = 1
    ORDER BY Al.NombreCompleto ASC
");

$Stmt->execute([
    $AsignacionId,
    $PeriodoId,
    $InfoClase['GrupoId']
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

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026consulta">
<link rel="stylesheet" href="assets/css/sgce-soft-motion.css?cache=sgce2026consulta">
<?= SgceEstilosTema($Pdo) ?>

<style>
    :root { --SgceVino:var(--SgceGuinda); --SgceVinoOscuro:var(--SgceGuindaOscuro); --SgceAzul:#2f6fec; --SgceVerde:#149447; --SgceBorde:#e6ebf2; --SgceTexto:#111827; }
    body { background: radial-gradient(circle at top left, rgba(155,0,27,.08), transparent 34%), #f4f7fb; font-family: 'Poppins', sans-serif; }
    .SgcePage { max-width: 1080px; }
    .TopBar { position: relative; overflow: hidden; border-radius: 24px; padding: 22px 26px; background: linear-gradient(135deg, var(--SgceVino), var(--SgceVinoOscuro)); box-shadow: 0 18px 45px rgba(80,0,14,.18); color:#fff; display:block !important; }
    .TopBar > .d-flex { width:100%; }
    .TopBar h2 { margin:0; font-weight:800; letter-spacing:.3px; font-size: clamp(1.45rem, 2.6vw, 2.2rem); max-width: 620px; line-height: 1.05; }
    .IconBox { width:52px; height:52px; border-radius:16px; display:grid; place-items:center; background:rgba(255,255,255,.16); font-size:1.55rem; flex:0 0 auto; }
    .InfoBadge { display:inline-flex; align-items:center; gap:7px; margin-top:8px; padding:5px 12px; border-radius:999px; background:rgba(255,255,255,.16); color:#fff; font-weight:800; font-size:.76rem; text-transform:uppercase; }
    .StatsCard, .MainCard { border:0; border-radius:18px; box-shadow: 0 12px 32px rgba(15,23,42,.08); overflow:hidden; }
    .StatsCard .card-body { padding:16px 18px !important; min-height:78px; }
    .StatsIcon { width:36px; height:36px; border-radius:12px; display:grid; place-items:center; flex:0 0 auto; }
    html body .SgceModuleWrap .StatsIcon.SgceStatsIconSoft {
        width:42px !important;
        height:42px !important;
        min-width:42px !important;
        margin-right:0 !important;
        border-radius:15px !important;
        display:grid !important;
        place-items:center !important;
        box-shadow:inset 0 1px 0 rgba(255,255,255,.90), 0 8px 18px rgba(15,23,42,.055) !important;
        border:1px solid rgba(15,23,42,.045) !important;
    }
    html body .SgceModuleWrap .StatsIcon.SgceStatsAlumnos {
        background:linear-gradient(135deg, rgba(47,111,236,.095) 0%, rgba(14,165,233,.16) 100%) !important;
        color:#2563EB !important;
    }
    html body .SgceModuleWrap .StatsIcon.SgceStatsCalificados {
        background:linear-gradient(135deg, rgba(5,150,105,.095) 0%, rgba(16,185,129,.17) 100%) !important;
        color:#047857 !important;
    }
    html body .SgceModuleWrap .StatsIcon.SgceStatsPromedio {
        background:linear-gradient(135deg, rgba(245,158,11,.105) 0%, rgba(251,191,36,.18) 100%) !important;
        color:#B45309 !important;
    }
    html body .SgceModuleWrap .StatsIcon.SgceStatsIconSoft .SgceColorIcon {
        font-size:1.22rem !important;
        line-height:1 !important;
        filter:drop-shadow(0 1px 0 rgba(255,255,255,.85)) !important;
    }
    .StatsCard h3 { font-size:1.45rem; }
    .MainCard .card-header { background:#fff; border-bottom:1px solid var(--SgceBorde); padding:16px 18px; }
    .SgcePeriodoBox { padding:14px 16px 8px; margin:0 !important; background:#fff; border-bottom:1px solid var(--SgceBorde); }
    .SgcePeriodoBox label { display:block; margin-bottom:6px; font-size:.78rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.04em; }
    .SgcePeriodoBox .form-select { height:40px; border-radius:12px; font-weight:600; }
    table.table { table-layout:fixed; }
    table.table thead th { background:#f7f9fc; color:#64748b; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; padding:9px 14px; border-bottom:1px solid var(--SgceBorde); }
    table.table tbody td { padding:7px 14px; border-color:#eef2f7; vertical-align:middle; }
    .AlumnoAvatar { width:30px; height:30px; border-radius:11px; display:grid; place-items:center; background:#eef2ff; color:#3158df; font-size:.95rem; flex:0 0 auto; }
    html body .SgceModuleWrap .AlumnoAvatar.AlumnoAvatarEmoji {
        background:linear-gradient(135deg, rgba(47,111,236,.12) 0%, rgba(14,165,233,.20) 100%) !important;
        color:#1D4ED8 !important;
        box-shadow:inset 0 1px 0 rgba(255,255,255,.85), 0 8px 18px rgba(47,111,236,.10) !important;
        border:1px solid rgba(47,111,236,.14) !important;
    }
    html body .SgceModuleWrap .AlumnoAvatar.AlumnoAvatarEmoji .SgceAlumnoEmoji {
        color:inherit !important;
        line-height:1 !important;
        font-size:1rem !important;
        filter:drop-shadow(0 1px 0 rgba(255,255,255,.75));
    }
    .AlumnoNombre { font-size:.88rem; font-weight:800; line-height:1.15; color:var(--SgceTexto); }
    .InputNota { width:112px; height:36px; margin-left:auto; border-radius:12px; font-weight:800; }
    .SgceStickyActions { position:sticky; bottom:0; z-index:5; padding:12px 16px !important; background:rgba(248,250,252,.96) !important; backdrop-filter: blur(8px); }
    .BtnGuardar { border:0 !important; border-radius:14px; padding:11px 22px; background:linear-gradient(135deg, var(--SgceVerde), #20bf63) !important; color:#fff !important; font-weight:900; box-shadow:0 12px 24px rgba(20,148,71,.22) !important; min-width:250px; }
    html body .SgceModuleWrap #BtnGuardarCalificacionesVerdeMetalico.BtnGuardarCalificacionesVerdeMetalico {
        background:#047857 !important;
        background-image:linear-gradient(135deg,#064E3B 0%,#047857 48%,#059669 74%,#10B981 100%) !important;
        color:#FFFFFF !important;
        border:0 !important;
        box-shadow:0 18px 36px rgba(4,120,87,.30), inset 0 1px 0 rgba(255,255,255,.26) !important;
        text-shadow:0 1px 2px rgba(0,0,0,.30) !important;
    }
    html body .SgceModuleWrap #BtnGuardarCalificacionesVerdeMetalico.BtnGuardarCalificacionesVerdeMetalico:hover {
        background-image:linear-gradient(135deg,#065F46 0%,#059669 48%,#10B981 78%,#34D399 100%) !important;
        box-shadow:0 22px 42px rgba(4,120,87,.36), inset 0 1px 0 rgba(255,255,255,.30) !important;
        transform:translateY(-1px) !important;
        filter:saturate(1.10) brightness(1.02) !important;
    }
    html body .SgceModuleWrap #BtnGuardarCalificacionesVerdeMetalico.BtnGuardarCalificacionesVerdeMetalico span {
        color:#FFFFFF !important;
        position:relative !important;
        z-index:1 !important;
    }
    @media (max-width: 991px) { .TopBar { padding:20px; } .SgceBtnVolverInicio { width:100%; text-align:center; } }
    @media (max-width: 576px) { .SgcePage { padding-left:12px !important; padding-right:12px !important; } table.table { table-layout:auto; } .InputNota { width:95px; } .BtnGuardar { width:100%; min-width:0; } }
</style>

</head>

<body>


<div class="container py-3 SgcePage SgceModuleWrap">

    <div class="TopBar mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

            <div class="d-flex align-items-center gap-3">

                <div class="IconBox"><span class="SgceColorIcon" aria-hidden="true">🎓</span></div>

                <div>
                    <h2>
                        <?= htmlspecialchars($InfoClase['MateriaNombre']) ?>
                    </h2>

                    <div class="text-light opacity-75 mt-1">
                        Grupo <?= $InfoClase['Grado'] ?> "<?= $InfoClase['Grupo'] ?>"
                    </div>

                    <div class="InfoBadge">
                        <i class="fa-solid <?= strtoupper((string)$InfoClase['Turno']) === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>
                        Turno <?= $InfoClase['Turno'] ?>
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
                            <?= $TotalAlumnos ?>
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
                            <?= $Calificados ?>
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
                            <?= $PromedioGrupo ?>
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
                    Captura Las Calificaciones Del Grupo
                </span>
            </div>

            <div class="badge bg-dark px-3 py-2 rounded-pill">
                <?= $TotalAlumnos ?> Alumnos
            </div>

        </div>

        <div class="card-body p-0">

            <form method="POST" id="FormCalificaciones">
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
                                                        <?= htmlspecialchars($Al['NombreCompleto']) ?>
                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <input
                                                type="number"
                                                name="Notas[<?= $Al['AlumnoId'] ?>]"
                                                class="form-control text-center InputNota"
                                                step="0.1"
                                                min="5"
                                                max="10"
                                                placeholder="-"
                                                data-original="<?= $Al['Calificacion'] !== null ? $Al['Calificacion'] : '' ?>"
                                                value="<?= $Al['Calificacion'] !== null ? $Al['Calificacion'] : '' ?>"
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
<script src="assets/js/sgce-shared.js?cache=sgce2026consulta"></script>
<script src="assets/js/Calificar.js?cache=sgce2026consulta"></script>
</body>
</html>
