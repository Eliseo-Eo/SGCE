<?php

require_once 'Conexion.php';

date_default_timezone_set('America/Mexico_City');

$Hoy = date('Y-m-d');

$AsignacionId = $_GET['id'] ?? ($_POST['asignacion_id'] ?? null);

$Mensaje = "";

$YaSeRegistro = false;

// VERIFICAR SI YA SE REGISTRÓ ASISTENCIA HOY

if ($AsignacionId) {

    $StmtCheck = $Pdo->prepare("
        SELECT COUNT(*)
        FROM Asistencias
        WHERE AsignacionId = ?
        AND DATE(Fecha) = ?
    ");

    $StmtCheck->execute([
        $AsignacionId,
        $Hoy
    ]);

    if ($StmtCheck->fetchColumn() > 0) {

        $YaSeRegistro = true;
    }
}

// GUARDAR ASISTENCIA

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['guardar'])
    &&
    !$YaSeRegistro
) {

    $Momento = date('Y-m-d H:i:s');

    foreach ($_POST['estado'] as $AlumnoId => $Estado) {

        $AlumnoId = intval($AlumnoId);

        $Stmt = $Pdo->prepare("
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

        $Stmt->execute([
            $AsignacionId,
            $AlumnoId,
            $Momento,
            $Estado
        ]);
    }

    $YaSeRegistro = true;

    $Mensaje = '

        <div class="alert alert-success border-0 shadow-sm mb-4">

            <i class="fa-solid fa-circle-check me-2"></i>

            Asistencia Guardada Correctamente.

        </div>

    ';
}

// OBTENER ALUMNOS

$Alumnos = [];

if ($AsignacionId) {

    $Stmt = $Pdo->prepare("
        SELECT
            a.Id,
            a.NombreCompleto
        FROM Alumnos a
        JOIN Asignaciones asig
            ON a.GrupoId = asig.GrupoId
        WHERE asig.Id = ?
        ORDER BY a.NombreCompleto ASC
    ");

    $Stmt->execute([
        $AsignacionId
    ]);

    $Alumnos = $Stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

   <meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SGCE | Pase De Lista</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

    *{
        font-family:'Poppins',sans-serif;
    }

    body{
        background:
            linear-gradient(rgba(247,248,252,.96),rgba(247,248,252,.96)),
            url('https://www.transparenttextures.com/patterns/cubes.png');

        min-height:100vh;
    }

    .TopHeader{
        background:linear-gradient(135deg,#5f0f40,#9a031e);
        border-radius:28px;
        padding:30px;
        color:white;
        box-shadow:0 10px 35px rgba(0,0,0,.12);
    }

    .HeaderIcon{
        width:78px;
        height:78px;
        border-radius:24px;
        background:rgba(255,255,255,.15);

        display:flex;
        align-items:center;
        justify-content:center;

        font-size:2rem;
    }

    .BadgeGlass{
        background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.2);

        padding:8px 16px;

        border-radius:50px;

        font-size:.85rem;

        display:flex;
        align-items:center;
        gap:8px;
    }

    .BtnBack{
        background:white;
        border:none;

        border-radius:14px;

        padding:12px 22px;

        font-weight:600;

        color:#5f0f40;
    }

    .MainCard{
        border:none;

        border-radius:28px;

        overflow:hidden;

        box-shadow:0 10px 35px rgba(0,0,0,.06);
    }

    .AlumnoAvatar{
        width:48px;
        height:48px;

        border-radius:16px;

        background:linear-gradient(135deg,#5f0f40,#9a031e);

        color:white;

        display:flex;
        align-items:center;
        justify-content:center;
    }

    .EstadoSelect{
        border-radius:14px;

        border:2px solid #edf0f5;

        height:48px;

        font-weight:600;
    }

    .BtnGuardar{
        background:linear-gradient(135deg,#198754,#157347);

        border:none;

        border-radius:16px;

        padding:14px;

        color:white;

        font-weight:700;

        width:100%;
    }

</style>

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

                <a href="Maestro.php" class="btn BtnBack">

                    <i class="fa-solid fa-arrow-left me-2"></i>

                    Volver

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

    <script>

document.addEventListener("DOMContentLoaded", function() {

    // ALERTA SUCCESS AUTO HIDE

    const Alertas = document.querySelectorAll('.alert-success');

    Alertas.forEach(function(Alerta){

        setTimeout(function(){

            Alerta.style.transition = "all .5s ease";

            Alerta.style.opacity = "0";

            Alerta.style.transform = "translateY(-10px)";

            setTimeout(function(){

                Alerta.remove();

            },500);

        },3000);

    });

    // COLORES DINÁMICOS EN SELECT

    const Selects = document.querySelectorAll('.EstadoSelect');

    function AplicarColor(Select){

        Select.classList.remove(
            'border-success',
            'border-danger',
            'border-warning',
            'border-primary'
        );

        switch(Select.value){

            case 'A':

                Select.classList.add('border-success');

            break;

            case 'F':

                Select.classList.add('border-danger');

            break;

            case 'R':

                Select.classList.add('border-warning');

            break;

            case 'J':

                Select.classList.add('border-primary');

            break;

        }

    }

    Selects.forEach(function(Select){

        AplicarColor(Select);

        Select.addEventListener('change', function(){

            AplicarColor(this);

        });

    });

});

</script>

</body>
</html>