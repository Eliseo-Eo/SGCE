<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'maestro') {
    header('Location: index.php');
    exit;
}

$Stmt = $Pdo->prepare("
    SELECT A.Id AS AsignacionId, 
           G.Grado, 
           G.Grupo, 
           G.Turno, 
           A.MateriaNombre 
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    WHERE A.MaestroId = ?
    ORDER BY G.Turno, G.Grado, G.Grupo ASC
");

$Stmt->execute([$UserSession['Id']]);
$MisClases = $Stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>EST 101 - Portal Docente</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <style>

        body{
            background:#EEF2F7;
            font-family:'Segoe UI', sans-serif;
        }

        .NavbarMaestro{
            background:linear-gradient(90deg,#7A0818,#A10D26);
        }

        .TituloPagina{
            color:#7A0818;
            font-weight:700;
        }

        .CardClase{
            border:none;
            border-radius:20px;
            overflow:hidden;
            transition:0.25s;
            box-shadow:0 5px 18px rgba(0,0,0,0.08);
        }

        .CardClase:hover{
            transform:translateY(-5px);
            box-shadow:0 10px 25px rgba(0,0,0,0.12);
        }

        .CardHeader{
            background:linear-gradient(135deg,#7A0818,#A10D26);
            color:white;
            padding:20px;
        }

        .MateriaIcon{
            width:65px;
            height:65px;
            border-radius:50%;
            background:rgba(255,255,255,0.15);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:28px;
        }

        .InfoGrupo{
            background:#F8F9FA;
            border-radius:12px;
            padding:12px;
        }

        .BotonAccion{
            border-radius:12px;
            padding:12px;
            font-weight:600;
            transition:0.2s;
        }

        .BotonAccion:hover{
            transform:scale(1.02);
        }

        .BtnCalificaciones{
            background:#7A0818;
            color:white;
        }

        .BtnCalificaciones:hover{
            background:#5B0612;
            color:white;
        }

        .BtnAsistencia{
            background:#0EA5E9;
            color:white;
        }

        .BtnAsistencia:hover{
            background:#0284C7;
            color:white;
        }

        .SeccionExportar{
            background:#F8FAFC;
            border-radius:14px;
            padding:12px;
        }

        .BtnExport{
            border-radius:10px;
            font-size:14px;
            font-weight:600;
        }

        .BadgeTurno{
            font-size:12px;
            padding:8px 12px;
            border-radius:30px;
        }

    </style>
</head>

<body>

<nav class="navbar navbar-dark NavbarMaestro shadow-sm mb-4">
    <div class="container-fluid px-4">

        <span class="navbar-brand fw-bold fs-4">
            <i class="fa-solid fa-school"></i>
            EST 101
            <span class="fw-light fs-6 ms-2">
                Portal Docente
            </span>
        </span>

        <a href="Logout.php" class="btn btn-outline-light rounded-pill px-4">
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar Sesión
        </a>

    </div>
</nav>

<div class="container">

    <div class="mb-4">
        <h2 class="TituloPagina">
            <i class="fa-solid fa-chalkboard-user"></i>
            Bienvenido Profesor
        </h2>

        <p class="text-secondary fs-5">
            <?= htmlspecialchars($UserSession['NombreCompleto']) ?>
        </p>
    </div>

    <div class="row">

        <?php if(empty($MisClases)): ?>

            <div class="col-12">
                <div class="alert alert-info shadow-sm border-0 rounded-4 p-4">

                    <h5>
                        <i class="fa-solid fa-circle-info"></i>
                        Sin materias asignadas
                    </h5>

                    <p class="mb-0">
                        Actualmente no tiene materias vinculadas.
                    </p>

                </div>
            </div>

        <?php else: ?>

            <?php foreach($MisClases as $Clase): ?>

                <div class="col-lg-4 col-md-6 mb-4">

                    <div class="card CardClase h-100">

                        <div class="CardHeader">

                            <div class="d-flex justify-content-between align-items-start">

                                <div>

                                    <h4 class="fw-bold mb-2">
                                        <?= htmlspecialchars($Clase['MateriaNombre']) ?>
                                    </h4>

                                    <span class="BadgeTurno bg-light text-dark">

                                        <i class="fa-solid <?= $Clase['Turno']=='Matutino' ? 'fa-sun' : 'fa-moon' ?>"></i>

                                        <?= $Clase['Turno'] ?>

                                    </span>

                                </div>

                                <div class="MateriaIcon">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>

                            </div>

                        </div>

                        <div class="card-body">

                            <div class="InfoGrupo mb-3">

                                <div class="d-flex justify-content-between">

                                    <div>
                                        <small class="text-muted">
                                            Grupo
                                        </small>

                                        <h5 class="mb-0 fw-bold">
                                            <?= $Clase['Grado'] ?>
                                            "<?= $Clase['Grupo'] ?>"
                                        </h5>
                                    </div>

                                    <div class="text-end">

                                        <small class="text-muted">
                                            Acciones
                                        </small>

                                        <div>
                                            <i class="fa-solid fa-clipboard-check text-success"></i>
                                            <i class="fa-solid fa-file-lines text-primary"></i>
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="d-grid gap-2">

                                <a href="Calificar.php?AsignacionId=<?= $Clase['AsignacionId'] ?>"
                                   class="btn BotonAccion BtnCalificaciones">

                                    <i class="fa-solid fa-file-pen"></i>
                                    Administrar Calificaciones

                                </a>

                                <a href="Asistencia.php?id=<?= $Clase['AsignacionId'] ?>"
                                   class="btn BotonAccion BtnAsistencia">

                                    <i class="fa-solid fa-user-check"></i>
                                    Control de Asistencia

                                </a>

                            </div>

                            <hr>

                            <div class="SeccionExportar">

                                <h6 class="fw-bold mb-3">
                                    <i class="fa-solid fa-download"></i>
                                    Exportaciones
                                </h6>

                                <div class="row g-2">

                                    <div class="col-6">

                                        <a href="ExportarCalificaciones.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel"
                                           class="btn btn-outline-success BtnExport w-100">

                                            <i class="fa-solid fa-file-excel"></i>
                                            Calif. Excel

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarCalificaciones.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf"
                                           target="_blank"
                                           class="btn btn-outline-danger BtnExport w-100">

                                            <i class="fa-solid fa-file-pdf"></i>
                                            Calif. PDF

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel"
                                           class="btn btn-outline-success BtnExport w-100">

                                            <i class="fa-solid fa-table"></i>
                                            Asist. Excel

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf"
                                           target="_blank"
                                           class="btn btn-outline-danger BtnExport w-100">

                                            <i class="fa-solid fa-file-export"></i>
                                            Asist. PDF

                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>