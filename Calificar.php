<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'maestro') {
    header('Location: index.php');
    exit;
}

$AsignacionId = intval($_GET['AsignacionId'] ?? 0);

$Stmt = $Pdo->prepare("
    SELECT A.*, G.Grado, G.Grupo, G.Turno
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    WHERE A.Id = ? AND A.MaestroId = ?
");

$Stmt->execute([$AsignacionId, $UserSession['Id']]);
$InfoClase = $Stmt->fetch();

if (!$InfoClase) {
    die("Acceso Denegado O Grupo No Encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['GuardarNotes'])) {

   foreach ($_POST['Notas'] as $AlumnoId => $Calificacion) {

    $AlumnoId = intval($AlumnoId);

        if ($Calificacion !== '') {

            $Stmt = $Pdo->prepare("
                INSERT INTO Calificaciones
                (AlumnoId, AsignacionId, Calificacion)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                Calificacion = VALUES(Calificacion)
            ");

            $Stmt->execute([
                $AlumnoId,
                $AsignacionId,
                $Calificacion
            ]);
        }
    }

    header("Location: Calificar.php?AsignacionId=$AsignacionId&Success=1");
    exit;
}

$Stmt = $Pdo->prepare("
    SELECT
        Al.Id AS AlumnoId,
        Al.NombreCompleto,
        C.Calificacion
    FROM Alumnos Al
    LEFT JOIN Calificaciones C
        ON C.AlumnoId = Al.Id
        AND C.AsignacionId = ?
    WHERE Al.GrupoId = ?
    ORDER BY Al.NombreCompleto ASC
");

$Stmt->execute([
    $AsignacionId,
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGCE | Evaluar Grupo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>

        *{
            font-family:'Poppins',sans-serif;
        }

        body{
            background:
                linear-gradient(rgba(248,249,252,.96),rgba(248,249,252,.96)),
                url('https://www.transparenttextures.com/patterns/cubes.png');
            min-height:100vh;
        }

        .TopBar{
            background:linear-gradient(135deg,#6a1b4d,#8e244d);
            border-radius:22px;
            padding:28px;
            color:white;
            box-shadow:0 10px 35px rgba(0,0,0,.12);
        }

        .TopBar h2{
            font-weight:700;
            margin:0;
        }

        .TopBar .IconBox{
            width:70px;
            height:70px;
            border-radius:20px;
            background:rgba(255,255,255,.15);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.8rem;
        }

        .InfoBadge{
            background:rgba(255,255,255,.15);
            border:1px solid rgba(255,255,255,.2);
            padding:8px 16px;
            border-radius:50px;
            font-size:.85rem;
            display:inline-flex;
            align-items:center;
            gap:8px;
            margin-top:10px;
        }

        .StatsCard{
            border:none;
            border-radius:22px;
            overflow:hidden;
            box-shadow:0 6px 22px rgba(0,0,0,.05);
            transition:.25s;
        }

        .StatsCard:hover{
            transform:translateY(-3px);
        }

        .StatsIcon{
            width:55px;
            height:55px;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.2rem;
        }

        .MainCard{
            border:none;
            border-radius:24px;
            overflow:hidden;
            box-shadow:0 8px 30px rgba(0,0,0,.06);
        }

        .MainCard .card-header{
            background:white;
            border-bottom:1px solid #f1f1f1;
            padding:22px 28px;
        }

        .table thead th{
            background:#f8f9fc;
            border:none;
            padding:16px;
            font-size:.85rem;
            text-transform:uppercase;
            letter-spacing:.5px;
            color:#6c757d;
        }

        .table tbody td{
            padding:15px;
            vertical-align:middle;
            border-color:#f1f3f5;
        }

        .AlumnoRow{
            transition:.2s;
        }

        .AlumnoRow:hover{
            background:#fafbff;
        }

        .AlumnoAvatar{
            width:42px;
            height:42px;
            border-radius:14px;
            background:linear-gradient(135deg,#6a1b4d,#8e244d);
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:.9rem;
        }

        .InputNota{
            border-radius:14px;
            border:2px solid #edf0f5;
            height:45px;
            font-weight:700;
            font-size:1rem;
            transition:.2s;
        }

        .InputNota:focus{
            border-color:#8e244d;
            box-shadow:0 0 0 .15rem rgba(142,36,77,.15);
        }

        .BtnGuardar{
            background:linear-gradient(135deg,#198754,#157347);
            border:none;
            border-radius:14px;
            padding:12px 26px;
            font-weight:600;
            box-shadow:0 5px 18px rgba(25,135,84,.25);
        }

        .BtnGuardar:hover{
            transform:translateY(-1px);
        }

        .BtnBack{
            border-radius:12px;
            padding:10px 18px;
            font-weight:500;
        }

        .alert{
            border-radius:16px;
        }

        @media(max-width:768px){

            .TopBar{
                padding:22px;
            }

            .TopBar h2{
                font-size:1.4rem;
            }

            .table thead{
                display:none;
            }

            .table tbody td{
                display:block;
                width:100%;
                text-align:left!important;
                padding:8px 14px;
            }

            .AlumnoRow{
                border-bottom:1px solid #eee;
            }
        }

    </style>
</head>

<body>


<div class="container py-4" style="max-width:1200px;">

    <div class="TopBar mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

            <div class="d-flex align-items-center gap-3">

                <div class="IconBox">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>

                <div>
                    <h2>
                        <?= htmlspecialchars($InfoClase['MateriaNombre']) ?>
                    </h2>

                    <div class="text-light opacity-75 mt-1">
                        Grupo <?= $InfoClase['Grado'] ?> "<?= $InfoClase['Grupo'] ?>"
                    </div>

                    <div class="InfoBadge">
                        <i class="fa-solid <?= $InfoClase['Turno']=='Matutino' ? 'fa-sun' : 'fa-moon' ?>"></i>
                        Turno <?= $InfoClase['Turno'] ?>
                    </div>
                </div>

            </div>

            <div>
                <a href="Maestro.php" class="btn btn-light BtnBack">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Volver
                </a>
            </div>

        </div>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="card StatsCard">

                <div class="card-body d-flex align-items-center gap-3 p-4">

                    <div class="StatsIcon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-users"></i>
                    </div>

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

                    <div class="StatsIcon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-check"></i>
                    </div>

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

                    <div class="StatsIcon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>

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

                                                <div class="AlumnoAvatar">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>

                                                <div>

                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars($Al['NombreCompleto']) ?>
                                                    </div>

                                                    <small class="text-muted">
                                                        Alumno Registrado
                                                    </small>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <input
                                                type="number"
                                                name="Notas[<?= $Al['AlumnoId'] ?>]"
                                                class="form-control text-center InputNota"
                                                step="0.1"
                                                min="0"
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

                    <div class="p-4 border-top bg-light d-flex justify-content-end">

                        <button type="submit" class="btn BtnGuardar text-white">

                            <i class="fa-solid fa-floppy-disk me-2"></i>

                            Guardar Calificaciones

                        </button>

                    </div>

                <?php endif; ?>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function() {

    const Alertas = document.querySelectorAll('.alert-success');

    Alertas.forEach(function(Alerta) {

        setTimeout(function() {

            Alerta.style.transition = "all .5s ease";
            Alerta.style.opacity = "0";
            Alerta.style.transform = "translateY(-10px)";

            setTimeout(function() {
                Alerta.remove();
            }, 500);

        }, 3000);

    });

    const Inputs = document.querySelectorAll('.InputNota');

    Inputs.forEach(function(Input) {

        Input.addEventListener('input', function() {

            let Valor = parseFloat(this.value);

            this.classList.remove(
                'border-success',
                'border-warning',
                'border-danger'
            );

            if (this.value === '') {
                return;
            }

            if (Valor >= 8) {

                this.classList.add('border-success');

            } else if (Valor >= 6) {

                this.classList.add('border-warning');

            } else {

                this.classList.add('border-danger');

            }

        });

    });

    document.getElementById('FormCalificaciones')
    .addEventListener('submit', function(E) {

        const Inputs = document.querySelectorAll('.InputNota');

        const Alerta = document.getElementById('JsAlert');

        let HuboCambios = false;

        let TieneCeros = false;

        Inputs.forEach(function(Input) {

            const ValorOriginal = Input.getAttribute('data-original');

            const ValorActual = Input.value;

            if (ValorActual !== ValorOriginal) {

                HuboCambios = true;
            }

            if (
                ValorActual !== '' &&
                parseFloat(ValorActual) === 0
            ) {

                TieneCeros = true;
            }

        });

        if (!HuboCambios) {

            E.preventDefault();

            Alerta.innerHTML = `
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <strong>Sin Cambios:</strong>
                No Has Modificado Ninguna Calificación.
            `;

            Alerta.classList.remove('d-none');

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            return;
        }

        if (TieneCeros) {

            if (!confirm('Hay alumnos con calificación 0. ¿Deseas continuar?')) {

                E.preventDefault();
            }
        }

    });

});

</script>

</body>
</html>