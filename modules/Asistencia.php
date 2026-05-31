<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || !SgceTienePermiso($UserSession, 'asistencia')) {
    header('Location: index.php');
    exit;
}

$Hoy = date('Y-m-d');
$FechaConsulta = trim((string)($_GET['Fecha'] ?? ($_POST['Fecha'] ?? $Hoy)));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaConsulta)) { $FechaConsulta = $Hoy; }
$PuedeHistorico = SgcePuedeCorregirAsistenciaHistorica($UserSession);
if (!$PuedeHistorico && $FechaConsulta !== $Hoy) { $FechaConsulta = $Hoy; }

$AsignacionId = intval($_GET['id'] ?? ($_GET['AsignacionId'] ?? ($_POST['asignacion_id'] ?? 0)));

if ($AsignacionId <= 0) {
    die("Asignación inválida.");
}

$Mensaje = "";
$YaSeRegistro = false;
$EstadosPermitidos = ['A', 'F', 'R', 'J'];


$StmtInfo = $Pdo->prepare("
    SELECT
        A.Id,
        A.MaestroId,
        A.GrupoId,
        A.MateriaNombre,
        G.Grado,
        G.Grupo,
        G.Turno
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    WHERE A.Id = ?
    AND A.Activo = 1
    AND G.Activo = 1
    LIMIT 1
");

$StmtInfo->execute([$AsignacionId]);
$InfoClase = $StmtInfo->fetch();

if (!$InfoClase) {
    die("Asignación no encontrada.");
}

if (SgceTieneRol($UserSession, ['maestro']) && (int)$UserSession['Id'] !== (int)$InfoClase['MaestroId']) {
    die("Acceso denegado.");
}


$StmtCheck = $Pdo->prepare("
    SELECT COUNT(*)
    FROM Asistencias
    WHERE AsignacionId = ?
    AND FechaDia = ?
");

$StmtCheck->execute([$AsignacionId, $FechaConsulta]);

if ((int)$StmtCheck->fetchColumn() > 0) {
    $YaSeRegistro = true;
}


$Stmt = $Pdo->prepare("
    SELECT
        a.Id,
        a.NombreCompleto
    FROM Alumnos a
    WHERE a.GrupoId = ?
    AND a.Activo = 1
    ORDER BY a.NombreCompleto ASC
");

$Stmt->execute([(int)$InfoClase['GrupoId']]);
$Alumnos = $Stmt->fetchAll();



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    RequerirCsrfPost();

    $UrlError = 'Asistencia.php?' . http_build_query([
        'id' => $AsignacionId,
        'Fecha' => $FechaConsulta,
        'Error' => 1
    ]);

    if (!isset($_POST['estado']) || !is_array($_POST['estado'])) {
        header('Location: ' . $UrlError);
        exit;
    }

    $Momento = $FechaConsulta . ' ' . date('H:i:s');

    try {
        $Pdo->beginTransaction();

        $StmtExiste = $Pdo->prepare("
            SELECT Id
            FROM Asistencias
            WHERE AsignacionId = ?
            AND AlumnoId = ?
            AND FechaDia = ?
            ORDER BY Id ASC
            LIMIT 1
        ");

        $StmtActualizar = $Pdo->prepare("
            UPDATE Asistencias
            SET Estado = ?, Fecha = ?
            WHERE AsignacionId = ?
            AND AlumnoId = ?
            AND FechaDia = ?
        ");

        $StmtInsertar = $Pdo->prepare("
            INSERT INTO Asistencias (AsignacionId, AlumnoId, Fecha, Estado)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($Alumnos as $Alumno) {
            $AlumnoId = (int)$Alumno['Id'];
            $Estado = $_POST['estado'][$AlumnoId] ?? 'A';

            if (!in_array($Estado, $EstadosPermitidos, true)) {
                $Estado = 'A';
            }

            $StmtExiste->execute([
                $AsignacionId,
                $AlumnoId,
                $FechaConsulta
            ]);

            $AsistenciaId = (int)$StmtExiste->fetchColumn();

            if ($AsistenciaId > 0) {
                $StmtActualizar->execute([
                    $Estado,
                    $Momento,
                    $AsignacionId,
                    $AlumnoId,
                    $FechaConsulta
                ]);
            } else {
                $StmtInsertar->execute([
                    $AsignacionId,
                    $AlumnoId,
                    $Momento,
                    $Estado
                ]);
            }
        }

        $StmtLimpiarDuplicados = $Pdo->prepare("
            DELETE AsiDuplicada
            FROM Asistencias AsiDuplicada
            INNER JOIN Asistencias AsiBase
                ON AsiBase.AsignacionId = AsiDuplicada.AsignacionId
                AND AsiBase.AlumnoId = AsiDuplicada.AlumnoId
                AND AsiBase.FechaDia = AsiDuplicada.FechaDia
                AND AsiBase.Id < AsiDuplicada.Id
            WHERE AsiDuplicada.AsignacionId = ?
            AND AsiDuplicada.FechaDia = ?
        ");
        $StmtLimpiarDuplicados->execute([$AsignacionId, $FechaConsulta]);

        $Pdo->commit();

        $AccionBitacora = $YaSeRegistro ? 'EDITAR_ASISTENCIA' : 'REGISTRAR_ASISTENCIA';
        $DetalleBitacora = $YaSeRegistro ? 'PASE DE LISTA ACTUALIZADO: ' . $FechaConsulta : 'PASE DE LISTA REGISTRADO: ' . $FechaConsulta;
        RegistrarBitacora($Pdo, $UserSession, $AccionBitacora, 'Asistencias', $AsignacionId, $DetalleBitacora);

        header('Location: Asistencia.php?' . http_build_query([
            'id' => $AsignacionId,
            'Fecha' => $FechaConsulta,
            'Success' => 1
        ]));
        exit;

    } catch (Exception $E) {
        if ($Pdo->inTransaction()) {
            $Pdo->rollBack();
        }

        header('Location: ' . $UrlError);
        exit;
    }
}

if (isset($_GET['Success'])) {
    $Mensaje = '
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>
            Asistencia guardada/actualizada correctamente.
        </div>
    ';
}

if (isset($_GET['Error'])) {
    $Mensaje = '
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <i class="fa-solid fa-circle-xmark me-2"></i>
            Error al guardar la asistencia. Recarga la página e intenta nuevamente.
        </div>
    ';
}

$EstadosRegistrados = [];
$StmtEstados = $Pdo->prepare("
    SELECT AlumnoId, Estado
    FROM Asistencias
    WHERE AsignacionId = ?
    AND FechaDia = ?
    ORDER BY Id ASC
");
$StmtEstados->execute([$AsignacionId, $FechaConsulta]);
foreach ($StmtEstados->fetchAll() as $RowEstado) {
    $EstadoGuardado = $RowEstado['Estado'];
    if (!in_array($EstadoGuardado, $EstadosPermitidos, true)) {
        $EstadoGuardado = 'A';
    }
    $EstadosRegistrados[(int)$RowEstado['AlumnoId']] = $EstadoGuardado;
}

$ResumenAsistencia = [
    'A' => 0,
    'F' => 0,
    'R' => 0,
    'J' => 0
];

foreach ($Alumnos as $AlumnoResumen) {
    $EstadoResumen = $EstadosRegistrados[(int)$AlumnoResumen['Id']] ?? 'A';
    if (!array_key_exists($EstadoResumen, $ResumenAsistencia)) {
        $EstadoResumen = 'A';
    }
    $ResumenAsistencia[$EstadoResumen]++;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

   <meta charset="UTF-8">


    
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SGCE | Pase De Lista</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026final">
<?= SgceEstilosTema($Pdo) ?>


<style>
    :root { --SgceVino:var(--SgceGuinda); --SgceVinoOscuro:var(--SgceGuindaOscuro); --SgceVerde:#149447; --SgceBorde:#e6ebf2; --SgceTexto:#111827; }
    body { background: radial-gradient(circle at top left, rgba(155,0,27,.08), transparent 34%), #f4f7fb; font-family:'Poppins', sans-serif; }
    .SgcePage { max-width:1080px; }
    .TopHeader { position:relative; overflow:hidden; border-radius:24px; padding:22px 26px; background:linear-gradient(135deg, var(--SgceVino), var(--SgceVinoOscuro)); box-shadow:0 18px 45px rgba(80,0,14,.18); color:#fff; display:block !important; }
    .TopHeader > .d-flex { width:100%; }
    .TopHeader h2 { margin:0; font-weight:800; letter-spacing:.3px; font-size:clamp(1.45rem,2.6vw,2.1rem); line-height:1.05; }
    .HeaderIcon { width:52px; height:52px; border-radius:16px; display:grid; place-items:center; background:rgba(255,255,255,.16); font-size:1.55rem; flex:0 0 auto; }
    .BadgeGlass { display:inline-flex; align-items:center; gap:7px; padding:5px 12px; border-radius:999px; background:rgba(255,255,255,.16); color:#fff; font-weight:800; font-size:.76rem; text-transform:uppercase; }
    .Card, .StatsCard, .MainCard { border:0; border-radius:18px; box-shadow:0 12px 32px rgba(15,23,42,.08); overflow:hidden; background:#fff; }
    .StatsCard .card-body { padding:16px 18px !important; min-height:78px; }
    .StatsIcon { width:42px; height:42px; min-width:42px; border-radius:15px; display:grid; place-items:center; flex:0 0 auto; }
    html body .SgceModuleWrap .StatsIcon.SgceAsistenciaStatIcon {
        width:42px !important;
        height:42px !important;
        min-width:42px !important;
        margin-right:0 !important;
        border-radius:15px !important;
        display:grid !important;
        place-items:center !important;
        box-shadow:inset 0 1px 0 rgba(255,255,255,.90), 0 8px 18px rgba(15,23,42,.055) !important;
        border:1px solid rgba(15,23,42,.045) !important;
        opacity:1 !important;
        pointer-events:auto !important;
    }
    html body .SgceModuleWrap .StatsIcon.StatsAsistencia {
        background:linear-gradient(135deg, rgba(5,150,105,.095) 0%, rgba(16,185,129,.17) 100%) !important;
        color:#047857 !important;
    }
    html body .SgceModuleWrap .StatsIcon.StatsRetardo {
        background:linear-gradient(135deg, rgba(245,158,11,.105) 0%, rgba(251,191,36,.18) 100%) !important;
        color:#B45309 !important;
    }
    html body .SgceModuleWrap .StatsIcon.StatsFalta {
        background:linear-gradient(135deg, rgba(220,38,38,.075) 0%, rgba(248,113,113,.145) 100%) !important;
        color:#B91C1C !important;
    }
    html body .SgceModuleWrap .StatsIcon.StatsJustificante {
        background:linear-gradient(135deg, rgba(47,111,236,.095) 0%, rgba(14,165,233,.16) 100%) !important;
        color:#2563EB !important;
    }
    html body .SgceModuleWrap .StatsIcon.SgceAsistenciaStatIcon .SgceColorIcon {
        width:100% !important;
        height:100% !important;
        margin:0 !important;
        font-size:1.22rem !important;
        line-height:1 !important;
        color:inherit !important;
        filter:drop-shadow(0 1px 0 rgba(255,255,255,.85)) !important;
    }
    .StatsCard h3 { font-size:1.45rem; }
    .MainCard .card-header { padding:16px 18px !important; }
    .MainCard h4 { font-size:1.05rem; }
    table.table { table-layout:fixed; }
    table.table thead th { background:#f7f9fc; color:#64748b; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; padding:9px 14px; border-bottom:1px solid var(--SgceBorde); }
    table.table tbody td { padding:7px 14px; border-color:#eef2f7; vertical-align:middle; }
    .AlumnoAvatar { width:30px; height:30px; border-radius:11px; display:grid; place-items:center; background:#eef2ff; color:#3158df; font-size:.92rem; flex:0 0 auto; }
    html body .SgceModuleWrap .AlumnoAvatar.AlumnoAvatarEmoji {
        width:30px !important;
        height:30px !important;
        min-width:30px !important;
        border-radius:11px !important;
        display:grid !important;
        place-items:center !important;
        background:linear-gradient(135deg, rgba(47,111,236,.10) 0%, rgba(14,165,233,.18) 100%) !important;
        color:#1D4ED8 !important;
        box-shadow:inset 0 1px 0 rgba(255,255,255,.90), 0 8px 18px rgba(47,111,236,.08) !important;
        border:1px solid rgba(47,111,236,.13) !important;
        opacity:1 !important;
        pointer-events:auto !important;
    }
    html body .SgceModuleWrap .AlumnoAvatar.AlumnoAvatarEmoji .SgceAlumnoEmoji {
        color:inherit !important;
        line-height:1 !important;
        font-size:1rem !important;
        filter:drop-shadow(0 1px 0 rgba(255,255,255,.75)) !important;
    }
    .AlumnoNombre { font-size:.88rem; font-weight:800; line-height:1.15; color:var(--SgceTexto); }
    .EstadoSelect { width:190px; height:36px; margin-left:auto; border-radius:12px; font-weight:800; font-size:.85rem; }
    .EstadoSelect.SgceEstadoA { background-color:rgba(16,185,129,.08) !important; border-color:rgba(5,150,105,.36) !important; color:#065F46 !important; }
    .EstadoSelect.SgceEstadoF { background-color:rgba(248,113,113,.08) !important; border-color:rgba(220,38,38,.32) !important; color:#991B1B !important; }
    .EstadoSelect.SgceEstadoR { background-color:rgba(251,191,36,.13) !important; border-color:rgba(217,119,6,.34) !important; color:#92400E !important; }
    .EstadoSelect.SgceEstadoJ { background-color:rgba(59,130,246,.08) !important; border-color:rgba(37,99,235,.30) !important; color:#1E40AF !important; }
    .SgceStickyActions { position:sticky; bottom:0; z-index:5; padding:12px 16px !important; background:rgba(248,250,252,.96) !important; backdrop-filter:blur(8px); display:flex; justify-content:flex-end; }
    .BtnGuardar { border:0 !important; border-radius:14px; padding:11px 22px; background:linear-gradient(135deg, var(--SgceVerde), #20bf63) !important; color:#fff !important; font-weight:900; box-shadow:0 12px 24px rgba(20,148,71,.22) !important; min-width:250px; }
    .BtnPrimary { border:0; border-radius:14px; padding:10px 16px; background:linear-gradient(135deg,var(--SgceVino),var(--SgceVinoOscuro)); color:#fff; font-weight:800; }
    @media (max-width:991px) { .TopHeader { padding:20px; } .SgceBtnVolverInicio { width:100%; text-align:center; } }
    @media (max-width:576px) { .SgcePage { padding-left:12px !important; padding-right:12px !important; } table.table { table-layout:auto; } .EstadoSelect { width:160px; } .BtnGuardar { width:100%; min-width:0; } }
</style>
<link rel="stylesheet" href="assets/css/asistencia-botones-metalicos.css?cache=sgce2026final">

</head>

<body>

   <div class="container py-3 SgcePage SgceModuleWrap">

    

    <div class="TopHeader mb-4">

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

            <div class="d-flex align-items-center gap-3">

                <div class="HeaderIcon">
                    <span class="SgceColorIcon" aria-hidden="true">📝</span>
                </div>

                <div>

                    <h2 class="fw-bold mb-1">
                        Pase De Lista
                    </h2>

                    <div class="text-light opacity-75">
                        Control De Asistencia Escolar · Fecha <?= htmlspecialchars(date('d/m/Y', strtotime($FechaConsulta)), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">

                        <span class="BadgeGlass">

                            <span aria-hidden="true">📅</span>

                            <?= htmlspecialchars(date('d/m/Y', strtotime($FechaConsulta)), ENT_QUOTES, 'UTF-8') ?>

                        </span>

                        <span class="BadgeGlass">

                            <span aria-hidden="true">🕒</span>

                            <?= date('h:i A') ?>

                        </span>

                    </div>

                </div>

            </div>

            <div>

                <a href="<?= HGlobal(SgceUrlInicioPorRol($UserSession)) ?>" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>

            </div>

        </div>

    </div>

    <?php if ($PuedeHistorico): ?>
        <div class="Card p-3 mb-4">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="id" value="<?= (int)$AsignacionId ?>">
                <div class="col-md-8">
                    <label class="fw-bold small text-muted">FECHA DE ASISTENCIA</label>
                    <input type="date" name="Fecha" class="form-control" value="<?= htmlspecialchars($FechaConsulta, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-4">
                    <button id="BtnCargarFechaAsistenciaVerdeMetalico" class="BtnPrimary BtnAsistenciaVerdeMetalico w-100"><i class="fa-solid fa-calendar-check me-2"></i>Cargar fecha</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    

    <?= $Mensaje ?>

    <?php if ($YaSeRegistro): ?>

        <div class="alert alert-warning border-0 shadow-sm mb-4">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            Ya existe asistencia registrada para esta fecha. Puedes actualizarla si necesitas corregirla.

        </div>

    <?php endif; ?>

    <div class="row g-3 mb-4">

        <div class="col-6 col-xl-3">
            <div class="card StatsCard">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="StatsIcon SgceAsistenciaStatIcon StatsAsistencia"><span class="SgceColorIcon" aria-hidden="true">✅</span></div>
                    <div>
                        <div class="text-muted small">Asistencias</div>
                        <h3 id="ContadorAsistencia" class="fw-bold mb-0"><?= (int)$ResumenAsistencia['A'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card StatsCard">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="StatsIcon SgceAsistenciaStatIcon StatsRetardo"><span class="SgceColorIcon" aria-hidden="true">⏰</span></div>
                    <div>
                        <div class="text-muted small">Retardos</div>
                        <h3 id="ContadorRetardo" class="fw-bold mb-0"><?= (int)$ResumenAsistencia['R'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card StatsCard">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="StatsIcon SgceAsistenciaStatIcon StatsFalta"><span class="SgceColorIcon" aria-hidden="true">❌</span></div>
                    <div>
                        <div class="text-muted small">Faltas</div>
                        <h3 id="ContadorFalta" class="fw-bold mb-0"><?= (int)$ResumenAsistencia['F'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card StatsCard">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="StatsIcon SgceAsistenciaStatIcon StatsJustificante"><span class="SgceColorIcon" aria-hidden="true">📄</span></div>
                    <div>
                        <div class="text-muted small">Justificantes</div>
                        <h3 id="ContadorJustificante" class="fw-bold mb-0"><?= (int)$ResumenAsistencia['J'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

    </div>

    

    <div class="card MainCard">

        <div class="card-header bg-white border-0 p-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h4 class="fw-bold mb-1">

                        Lista De Alumnos

                    </h4>

                    <div class="text-muted">

                        Selecciona El Estado De Asistencia

                    </div>

                </div>

                <div class="badge bg-dark rounded-pill px-4 py-3">

                    <?= count($Alumnos) ?> Alumnos

                </div>

            </div>

        </div>

        <div class="card-body p-0">

            <form method="POST">
                    <?php echo CampoCsrf(); ?>

                <input type="hidden" name="asignacion_id" value="<?= $AsignacionId ?>"><input type="hidden" name="Fecha" value="<?= htmlspecialchars($FechaConsulta, ENT_QUOTES, 'UTF-8') ?>">

                <div class="table-responsive">

                    <table class="table align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="ps-4">
                                    Alumno
                                </th>

                                <th class="text-center" style="width:300px;">
                                    Estado
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if(empty($Alumnos)): ?>

                                <tr>

                                    <td colspan="2" class="text-center py-5 text-muted">

                                        <i class="fa-solid fa-folder-open fa-3x mb-3"></i>

                                        <div class="fw-semibold">

                                            No Hay Alumnos Registrados

                                        </div>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach($Alumnos as $a): ?>

                                    <tr>

                                        <td class="ps-4">

                                            <div class="d-flex align-items-center gap-3">

                                                <div class="AlumnoAvatar AlumnoAvatarEmoji">

                                                    <span class="SgceAlumnoEmoji" aria-hidden="true">🧑‍🎓</span>

                                                </div>

                                                <div>

                                                    <div class="AlumnoNombre">

                                                        <?= htmlspecialchars($a['NombreCompleto']) ?>

                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <select
                                                name="estado[<?= $a['Id'] ?>]"
                                                class="form-select EstadoSelect"
                                                
                                            >

                                                <option value="A" <?= (($EstadosRegistrados[(int)$a['Id']] ?? 'A') === 'A') ? 'selected' : '' ?>>
                                                    ✅ Asistencia
                                                </option>

                                                <option value="F" <?= (($EstadosRegistrados[(int)$a['Id']] ?? 'A') === 'F') ? 'selected' : '' ?>>
                                                    ❌ Falta
                                                </option>

                                                <option value="R" <?= (($EstadosRegistrados[(int)$a['Id']] ?? 'A') === 'R') ? 'selected' : '' ?>>
                                                    ⏰ Retardo
                                                </option>

                                                <option value="J" <?= (($EstadosRegistrados[(int)$a['Id']] ?? 'A') === 'J') ? 'selected' : '' ?>>
                                                    📄 Justificante
                                                </option>

                                            </select>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <?php if (!empty($Alumnos)): ?>

                    <div class="SgceStickyActions border-top">

                        <button type="submit" name="guardar" id="BtnGuardarAsistenciaVerdeMetalico" class="btn BtnGuardar BtnAsistenciaVerdeMetalico">

                            <span class="me-2" aria-hidden="true">💾</span>

                            <?= $YaSeRegistro ? 'Actualizar Pase De Lista' : 'Guardar Pase De Lista' ?>

                        </button>

                    </div>

                <?php endif; ?>

            </form>

        </div>

    </div>

</div>

    









<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?cache=sgce2026final"></script>
<script src="assets/js/Asistencia.js?cache=sgce2026final"></script>
</body>
</html>
