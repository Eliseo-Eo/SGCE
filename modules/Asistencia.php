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
    SgceSalirConError('Asignación inválida.', 400);
}

$Mensaje = "";
$YaSeRegistro = false;
$EstadosPermitidos = SgceAsistenciaEstadosPermitidos();


$StmtInfo = $Pdo->prepare("
    SELECT
        A.Id,
        A.MaestroId,
        A.GrupoId,
        A.CicloId,
        A.MateriaNombre,
        G.Grado,
        G.Grupo,
        G.Turno,
        C.Nombre AS CicloNombre,
        C.FechaInicio,
        C.FechaFin
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id AND G.CicloId = A.CicloId
    JOIN CiclosEscolares C ON C.Id = A.CicloId
    WHERE A.Id = ?
    AND A.Activo = 1
    AND G.Activo = 1
    AND C.Activo = 1
    LIMIT 1
");

$StmtInfo->execute([$AsignacionId]);
$InfoClase = $StmtInfo->fetch();

if (!$InfoClase) {
    SgceSalirConError('Asignación no encontrada en el ciclo activo.', 404);
}

$CicloClaseId = (int)$InfoClase['CicloId'];
if (!empty($InfoClase['FechaInicio']) && !empty($InfoClase['FechaFin'])) {
    if ($FechaConsulta < $InfoClase['FechaInicio'] || $FechaConsulta > $InfoClase['FechaFin']) {
        SgceSalirConError('La fecha seleccionada no pertenece al ciclo escolar activo de esta asignación.', 400);
    }
}

if (SgceTieneRol($UserSession, ['maestro']) && (int)$UserSession['Id'] !== (int)$InfoClase['MaestroId']) {
    SgceSalirConError('Acceso denegado.', 403);
}


$StmtCheck = $Pdo->prepare("
    SELECT COUNT(*)
    FROM Asistencias
    WHERE CicloId = ?
    AND AsignacionId = ?
    AND FechaDia = ?
");

$StmtCheck->execute([$CicloClaseId, $AsignacionId, $FechaConsulta]);

if ((int)$StmtCheck->fetchColumn() > 0) {
    $YaSeRegistro = true;
}


$Stmt = $Pdo->prepare("
    SELECT
        a.Id,
        a.NombreCompleto
    FROM AlumnoInscripciones ai
    INNER JOIN Alumnos a ON a.Id = ai.AlumnoId
    WHERE ai.GrupoId = ?
    AND ai.CicloId = ?
    AND ai.Estado = 'INSCRITO'
    AND a.Activo = 1
    ORDER BY a.NombreCompleto ASC
");

$Stmt->execute([(int)$InfoClase['GrupoId'], $CicloClaseId]);
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
            WHERE CicloId = ?
            AND AsignacionId = ?
            AND AlumnoId = ?
            AND FechaDia = ?
            ORDER BY Id ASC
            LIMIT 1
        ");

        $StmtActualizar = $Pdo->prepare("
            UPDATE Asistencias
            SET Estado = ?, Fecha = ?
            WHERE CicloId = ?
            AND AsignacionId = ?
            AND AlumnoId = ?
            AND FechaDia = ?
        ");

        $StmtInsertar = $Pdo->prepare("
            INSERT INTO Asistencias (CicloId, AsignacionId, AlumnoId, Fecha, Estado)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($Alumnos as $Alumno) {
            $AlumnoId = (int)$Alumno['Id'];
            $Estado = $_POST['estado'][$AlumnoId] ?? 'A';

            $Estado = SgceAsistenciaEstadoSeguro($Estado);

            $StmtExiste->execute([
                $CicloClaseId,
                $AsignacionId,
                $AlumnoId,
                $FechaConsulta
            ]);

            $AsistenciaId = (int)$StmtExiste->fetchColumn();

            if ($AsistenciaId > 0) {
                $StmtActualizar->execute([
                    $Estado,
                    $Momento,
                    $CicloClaseId,
                    $AsignacionId,
                    $AlumnoId,
                    $FechaConsulta
                ]);
            } else {
                $StmtInsertar->execute([
                    $CicloClaseId,
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
                ON AsiBase.CicloId = AsiDuplicada.CicloId
                AND AsiBase.AsignacionId = AsiDuplicada.AsignacionId
                AND AsiBase.AlumnoId = AsiDuplicada.AlumnoId
                AND AsiBase.FechaDia = AsiDuplicada.FechaDia
                AND AsiBase.Id < AsiDuplicada.Id
            WHERE AsiDuplicada.CicloId = ?
            AND AsiDuplicada.AsignacionId = ?
            AND AsiDuplicada.FechaDia = ?
        ");
        $StmtLimpiarDuplicados->execute([$CicloClaseId, $AsignacionId, $FechaConsulta]);

        $Pdo->commit();

        $AccionBitacora = $YaSeRegistro ? 'EDITAR_ASISTENCIA' : 'REGISTRAR_ASISTENCIA';
        $DetalleBitacora = $YaSeRegistro ? 'PASE DE LISTA ACTUALIZADO: ' . $FechaConsulta : 'PASE DE LISTA REGISTRADO: ' . $FechaConsulta;
        RegistrarBitacora($Pdo, $UserSession, $AccionBitacora, 'Asistencias', $AsignacionId, $DetalleBitacora);

        header('Location: Asistencia.php?' . http_build_query([
            'id' => $AsignacionId,
            'Fecha' => $FechaConsulta,
            'Success' => 1,
            'Tipo' => $YaSeRegistro ? 'actualizada' : 'registrada'
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
    $TipoMensaje = strtolower(trim((string)($_GET['Tipo'] ?? '')));
    $TextoMensaje = SgceAsistenciaMensajeResultado($TipoMensaje);

    $Mensaje = '
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>
            ' . HGlobal($TextoMensaje) . '
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
    WHERE CicloId = ?
    AND AsignacionId = ?
    AND FechaDia = ?
    ORDER BY Id ASC
");
$StmtEstados->execute([$CicloClaseId, $AsignacionId, $FechaConsulta]);
foreach ($StmtEstados->fetchAll() as $RowEstado) {
    $EstadoGuardado = $RowEstado['Estado'];
    $EstadoGuardado = SgceAsistenciaEstadoSeguro($EstadoGuardado);
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


    
    <link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/media/img/favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SGCE | Pase De Lista</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<?= SgceCss('assets/css/sgce-base.min.css') ?>
<?= SgceCss('assets/css/sgce-soft-motion.css') ?>
<?= SgceEstilosTema($Pdo) ?>


<?= SgceCss('assets/css/asistencia-botones-metalicos.css') ?>

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

                            <?= HGlobal(date('h:i A')) ?>

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

    <?php if ($YaSeRegistro && !isset($_GET['Success']) && !isset($_GET['Error'])): ?>

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

                    <?= (int)count($Alumnos) ?> Alumnos

                </div>

            </div>

        </div>

        <div class="card-body p-0">

            <form method="POST">
                    <?php echo CampoCsrf(); ?>

                <input type="hidden" name="asignacion_id" value="<?= (int)$AsignacionId ?>"><input type="hidden" name="Fecha" value="<?= htmlspecialchars($FechaConsulta, ENT_QUOTES, 'UTF-8') ?>">

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

                                                        <?= HGlobal($a['NombreCompleto']) ?>

                                                    </div>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <select
                                                name="estado[<?= (int)$a['Id'] ?>]"
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
<?= SgceJs('assets/js/sgce-shared.js') ?>
<?= SgceJs('assets/js/Asistencia.js') ?>
</body>
</html>
