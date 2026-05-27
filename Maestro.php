<?php

/*
    Archivo: Maestro.php
    Descripción: Portal del docente.
    Muestra las materias asignadas al profesor y ofrece accesos rápidos para calificar, pasar asistencia y exportar reportes.
*/

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
    AND A.Activo = 1
    AND G.Activo = 1
    ORDER BY G.Turno, G.Grado, G.Grupo ASC
");

$Stmt->execute([$UserSession['Id']]);
$MisClases = $Stmt->fetchAll();
$TotalClases = count($MisClases);
$StmtStatsMaestro = $Pdo->prepare("SELECT COUNT(*) FROM Asistencias Asi JOIN Asignaciones A ON Asi.AsignacionId = A.Id WHERE A.MaestroId = ? AND Asi.FechaDia = CURDATE()");
$StmtStatsMaestro->execute([$UserSession['Id']]);
$AsistenciasHoyMaestro = (int)$StmtStatsMaestro->fetchColumn();

// Cargo avisos activos dirigidos a maestros o a todo el sistema.
$StmtAvisosMaestro = $Pdo->query("SELECT Titulo, Mensaje, FechaCreacion FROM Avisos WHERE Activo = 1 AND Publico IN ('TODOS','MAESTROS') ORDER BY FechaCreacion DESC LIMIT 3");
$AvisosMaestro = $StmtAvisosMaestro ? $StmtAvisosMaestro->fetchAll() : [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    
    <!-- FAVICON DEL SISTEMA: ICONO QUE APARECE EN LA PESTAÑA DEL NAVEGADOR -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<title>EST 101 - Portal Docente</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    
    



<!-- SGCE FIX10: Botones de regreso/cerrar sesión con borde tinto fuerte y estilo homologado -->



    <link rel="stylesheet" href="assets/css/sgce-base.css?v=50">
    <link rel="stylesheet" href="assets/css/sgce-shared.css?v=44">
    <link rel="stylesheet" href="assets/css/Maestro.css?v=44">
</head>

<body>

<div class="SgcePageWrap container py-4">

    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div>
                <h1>Portal Docente</h1>
                <p>Bienvenido profesor <?= htmlspecialchars($UserSession['NombreCompleto']) ?></p>
            </div>
        </div>
    </section>




    <?php if(!empty($AvisosMaestro)): ?>
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-danger mb-3"><i class="fa-solid fa-bullhorn me-2"></i> AVISOS IMPORTANTES</h5>
            <div class="row g-3">
                <?php foreach($AvisosMaestro as $Aviso): ?>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 bg-light border h-100">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($Aviso['Titulo']) ?></div>
                            <div class="small text-muted mb-2"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($Aviso['FechaCreacion']))) ?></div>
                            <div class="small"><?= htmlspecialchars($Aviso['Mensaje']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

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

                                        <i class="fa-solid <?= strtoupper((string)$Clase['Turno']) === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>

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

                                        <?php $TurnoClase = strtoupper((string)$Clase['Turno']); ?>
                                        <h5 class="mb-0 fw-bold">
                                            <span class="GrupoTurnoBadge <?= $TurnoClase === 'MATUTINO' ? 'GrupoTurnoMatutino' : 'GrupoTurnoVespertino' ?>">
                                                <i class="fa-solid <?= $TurnoClase === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>
                                                <?= htmlspecialchars($Clase['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Clase['Grupo'], ENT_QUOTES, 'UTF-8') ?>" - <?= htmlspecialchars($TurnoClase, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
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
                                           class="btn BtnExport ExportCalifExcel w-100">

                                            <i class="fa-solid fa-file-excel"></i>
                                            Calif. Excel

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarCalificaciones.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf"
                                           target="_blank"
                                           class="btn BtnExport ExportCalifPdf w-100">

                                            <i class="fa-solid fa-file-pdf"></i>
                                            Calif. PDF

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel"
                                           class="btn BtnExport ExportAsisExcel w-100">

                                            <i class="fa-solid fa-table"></i>
                                            Asist. Excel

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf"
                                           target="_blank"
                                           class="btn BtnExport ExportAsisPdf w-100">

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



<!-- ============================================================
     NOTIFICACIONES AUTOMÁTICAS DEL SISTEMA
     ------------------------------------------------------------
     Este bloque lo uso para homologar todas las notificaciones.
     Cualquier alerta puede cerrarse manualmente con la tachita y,
     si el usuario no la cierra, desaparece sola después de unos segundos.
     ============================================================ -->








<!-- SGCE FIX12: Homologación final de botones superiores y reportes -->



<script src="assets/js/sgce-shared.js?v=44"></script>
</body>
</html>