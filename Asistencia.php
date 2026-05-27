<?php

/*
    Archivo: Asistencia.php
    Descripción: Módulo para registrar asistencia diaria de un grupo.
    Permite marcar asistencia, falta, retardo o justificante, evitando duplicar el pase de lista del día.
*/

require_once 'Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Mexico_City');

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || !in_array($UserSession['Rol'], ['maestro', 'admin'], true)) {
    header('Location: index.php');
    exit;
}

$Hoy = date('Y-m-d');

$AsignacionId = intval($_GET['id'] ?? ($_GET['AsignacionId'] ?? ($_POST['asignacion_id'] ?? 0)));

if ($AsignacionId <= 0) {
    die("Asignación inválida.");
}

$Mensaje = "";
$YaSeRegistro = false;
$EstadosPermitidos = ['A', 'F', 'R', 'J'];

// VERIFICAR QUE LA ASIGNACIÓN EXISTA Y QUE EL MAESTRO TENGA PERMISO
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

if ($UserSession['Rol'] === 'maestro' && (int)$UserSession['Id'] !== (int)$InfoClase['MaestroId']) {
    die("Acceso denegado.");
}

// VERIFICAR SI YA SE REGISTRÓ ASISTENCIA HOY
$StmtCheck = $Pdo->prepare("
    SELECT COUNT(*)
    FROM Asistencias
    WHERE AsignacionId = ?
    AND FechaDia = ?
");

$StmtCheck->execute([$AsignacionId, $Hoy]);

if ((int)$StmtCheck->fetchColumn() > 0) {
    $YaSeRegistro = true;
}

// OBTENER ALUMNOS
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

// GUARDAR ASISTENCIA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar']) && !$YaSeRegistro) {
    RequerirCsrfPost();

    if (!isset($_POST['estado']) || !is_array($_POST['estado'])) {
        $Mensaje = '
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <i class="fa-solid fa-circle-xmark me-2"></i>
                No se recibieron datos de asistencia.
            </div>
        ';
    } else {

        $Momento = date('Y-m-d H:i:s');

        try {
            $Pdo->beginTransaction();

            $StmtInsert = $Pdo->prepare("
                INSERT INTO Asistencias
                (
                    AsignacionId,
                    AlumnoId,
                    Fecha,
                    Estado
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            foreach ($Alumnos as $Alumno) {

                $AlumnoId = (int)$Alumno['Id'];
                $Estado = $_POST['estado'][$AlumnoId] ?? 'A';

                if (!in_array($Estado, $EstadosPermitidos, true)) {
                    $Estado = 'A';
                }

                $StmtInsert->execute([
                    $AsignacionId,
                    $AlumnoId,
                    $Momento,
                    $Estado
                ]);
            }

            $Pdo->commit();

            RegistrarBitacora($Pdo, $UserSession, 'REGISTRAR_ASISTENCIA', 'Asistencias', $AsignacionId, 'PASE DE LISTA REGISTRADO');

            $YaSeRegistro = true;

            $Mensaje = '
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    Asistencia Guardada Correctamente.
                </div>
            ';

        } catch (Exception $E) {

            if ($Pdo->inTransaction()) {
                $Pdo->rollBack();
            }

            $Mensaje = '
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-circle-xmark me-2"></i>
                    Error al guardar la asistencia. Revisa que no se haya registrado ya el día de hoy.
                </div>
            ';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

   <meta charset="UTF-8">


    <!-- FAVICON DEL SISTEMA: ICONO QUE APARECE EN LA PESTAÑA DEL NAVEGADOR -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SGCE | Pase De Lista</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=final">

</head>

<body>

   <div class="container py-4" style="max-width:1200px;">

    <!-- HEADER -->

    <div class="TopHeader mb-4">

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

            <div class="d-flex align-items-center gap-3">

                <div class="HeaderIcon">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>

                <div>

                    <h2 class="fw-bold mb-1">
                        Pase De Lista
                    </h2>

                    <div class="text-light opacity-75">
                        Control De Asistencia Escolar
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">

                        <span class="BadgeGlass">

                            <i class="fa-solid fa-calendar-days"></i>

                            <?= date('d/m/Y') ?>

                        </span>

                        <span class="BadgeGlass">

                            <i class="fa-solid fa-clock"></i>

                            <?= date('h:i A') ?>

                        </span>

                    </div>

                </div>

            </div>

            <div>

                <a href="Maestro.php" class="btn BtnBack SgceBtnInicio">

                    <i class="fa-solid fa-arrow-left me-2"></i>

                    VOLVER A INICIO

                </a>

            </div>

        </div>

    </div>

    <!-- ALERTAS -->

    <?= $Mensaje ?>

    <?php if ($YaSeRegistro): ?>

        <div class="alert alert-warning border-0 shadow-sm mb-4">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            Ya Se Registró La Asistencia De Este Grupo El Día De Hoy.

        </div>

    <?php endif; ?>

    <!-- CARD -->

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

                <input type="hidden" name="asignacion_id" value="<?= $AsignacionId ?>">

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

                                                <div class="AlumnoAvatar">

                                                    <i class="fa-solid fa-user"></i>

                                                </div>

                                                <div>

                                                    <div class="fw-semibold">

                                                        <?= htmlspecialchars($a['NombreCompleto']) ?>

                                                    </div>

                                                    <small class="text-muted">

                                                        Alumno Registrado

                                                    </small>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <select
                                                name="estado[<?= $a['Id'] ?>]"
                                                class="form-select EstadoSelect"
                                                <?= $YaSeRegistro ? 'disabled' : '' ?>
                                            >

                                                <option value="A">
                                                    ✅ Asistencia
                                                </option>

                                                <option value="F">
                                                    ❌ Falta
                                                </option>

                                                <option value="R">
                                                    ⏰ Retardo
                                                </option>

                                                <option value="J">
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

                <?php if (!$YaSeRegistro && !empty($Alumnos)): ?>

                    <div class="p-4 bg-light border-top">

                        <button type="submit" name="guardar" class="btn BtnGuardar">

                            <i class="fa-solid fa-floppy-disk me-2"></i>

                            Guardar Pase De Lista

                        </button>

                    </div>

                <?php endif; ?>

            </form>

        </div>

    </div>

</div>

    



<!-- ============================================================
     NOTIFICACIONES AUTOMÁTICAS DEL SISTEMA
     ------------------------------------------------------------
     Este bloque lo uso para homologar todas las notificaciones.
     Cualquier alerta puede cerrarse manualmente con la tachita y,
     si el usuario no la cierra, desaparece sola después de unos segundos.
     ============================================================ -->





<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?v=44"></script>
<script src="assets/js/Asistencia.js?v=44"></script>
</body>
</html>
