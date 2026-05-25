<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'maestro') { 
    header('Location: index.php'); 
    exit; 
}

$Stmt = $Pdo->prepare("
    SELECT A.Id AS AsignacionId, G.Grado, G.Grupo, G.Turno, A.MateriaNombre 
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
    <title>EST 101 - Mis Clases</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #F8F9FA; font-family: sans-serif; }
        .NavbarMaestro { background-color: #7A0818; }
        .CardClase { border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); background: white; transition: 0.2s; }
        .CardClase:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
        .BtnEntrar { background-color: #7A0818; color: white; border: none; }
        .BtnEntrar:hover { background-color: #56040E; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark NavbarMaestro mb-4 shadow-sm">
    <div class="container-fluid px-4">
        <span class="navbar-brand fw-bold"><i class="fa-solid fa-chalkboard-user"></i> EST 101 &nbsp;|&nbsp; <small class="fw-normal fs-6 text-white-50">Portal Docente</small></span>
        <a href="Logout.php" class="btn btn-sm btn-outline-light"><i class="fa-solid fa-power-off"></i> Cerrar Sesión</a>
    </div>
</nav>

<div class="container">
    <h5 class="mb-4 text-secondary"><i class="fa-solid fa-user"></i> Profesor(a): <strong><?= htmlspecialchars($UserSession['NombreCompleto']) ?></strong></h5>
    <div class="row">
        <?php if(empty($MisClases)): ?>
            <div class="col-12"><div class="alert alert-info border-0 shadow-sm"><i class="fa-solid fa-info"></i> No Tienes Materias Vinculadas Por El Momento.</div></div>
        <?php else: ?>
            <?php foreach($MisClases as $Clase): ?>
                <div class="col-md-4 mb-3">
                    <div class="card CardClase h-100 p-2">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($Clase['MateriaNombre']) ?></h4>
                                    <span class="badge <?= $Clase['Turno']=='Matutino'?'bg-primary':'bg-warning text-dark' ?>">
                                        <i class="fa-solid <?= $Clase['Turno']=='Matutino'?'fa-sun':'fa-moon' ?>"></i> <?= $Clase['Turno'] ?>
                                    </span>
                                </div>
                                <p class="text-muted fs-5">Grupo: <strong><?= $Clase['Grado'] ?> "<?= $Clase['Grupo'] ?>"</strong></p>
                            </div>
                            
                            <div class="mt-3">
                                <a href="Calificar.php?AsignacionId=<?= $Clase['AsignacionId'] ?>" class="btn btn-sm BtnEntrar w-100 py-2 fw-bold mb-2"><i class="fa-solid fa-pen-field"></i> Evaluar Alumnos</a>
                                
                                <a href="Asistencia.php?id=<?= $Clase['AsignacionId'] ?>" class="btn btn-sm btn-info text-white w-100 py-2 fw-bold mb-2"><i class="fa-solid fa-clipboard-user"></i> Pasar Asistencia</a>
                                
                                <div class="d-flex gap-1">
                                    <a href="Exportar.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel" class="btn btn-sm btn-outline-success w-50 py-1 small"><i class="fa-solid fa-file-excel"></i> Excel</a>
                                    <a href="Exportar.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf" target="_blank" class="btn btn-sm btn-outline-danger w-50 py-1 small"><i class="fa-solid fa-file-pdf"></i> PDF</a>
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