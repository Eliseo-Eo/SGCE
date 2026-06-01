<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }



require_once dirname(__DIR__) . '/config/Conexion.php';

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
SgceCrearTablaPlaneacionesSiNoExiste($Pdo);
$CantidadPlaneacionesDocente = SgceCantidadPlaneaciones($Pdo);
$CicloActivoDocente = SgceCicloActivo($Pdo);
$CicloDocenteId = (int)($CicloActivoDocente['Id'] ?? 0);
$MateriasPlaneacionDocente = SgceMateriasDocente($Pdo, (int)$UserSession['Id']);
$TotalPlaneacionesRequeridas = count($MateriasPlaneacionDocente) * $CantidadPlaneacionesDocente;
$TotalPlaneacionesSubidas = 0;
if ($CicloDocenteId > 0) {
    $StmtPlaneacionesDocente = $Pdo->prepare('SELECT COUNT(*) FROM Planeaciones WHERE CicloId = ? AND MaestroId = ?');
    $StmtPlaneacionesDocente->execute([$CicloDocenteId, (int)$UserSession['Id']]);
    $TotalPlaneacionesSubidas = (int)$StmtPlaneacionesDocente->fetchColumn();
}
$StmtStatsMaestro = $Pdo->prepare("SELECT COUNT(*) FROM Asistencias Asi JOIN Asignaciones A ON Asi.AsignacionId = A.Id WHERE A.MaestroId = ? AND Asi.FechaDia = CURDATE()");
$StmtStatsMaestro->execute([$UserSession['Id']]);
$AsistenciasHoyMaestro = (int)$StmtStatsMaestro->fetchColumn();


$StmtAvisosMaestro = $Pdo->query("SELECT Titulo, Mensaje, FechaCreacion FROM Avisos WHERE Activo = 1 AND Publico IN ('TODOS','MAESTROS') ORDER BY FechaCreacion DESC LIMIT 3");
$AvisosMaestro = $StmtAvisosMaestro ? $StmtAvisosMaestro->fetchAll() : [];
$ConfigSistema = SgceObtenerConfiguracion($Pdo);
$NombreEscuelaMaestro = trim((string)($ConfigSistema['NombreEscuela'] ?? 'SGCE'));
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    
    <link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/media/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/media/img/favicon.png">
<title><?= htmlspecialchars($NombreEscuelaMaestro, ENT_QUOTES, 'UTF-8') ?> - Portal Docente</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.min.css?cache=sgce2026final">
<?= SgceEstilosTema($Pdo) ?>
</head>

<body>

<div class="SgcePageWrap SgceModuleWrap container py-4">

    <section class="SgceHero MaestroHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">👨‍🏫</span></div>
            <div>
                <h1>Portal Docente</h1>
                <p>Bienvenido profesor <?= htmlspecialchars($UserSession['NombreCompleto']) ?></p>
            </div>
        </div>

        <div class="SgceHeroActions">
            <span class="MaestroHeroStat">
                <span class="SgceColorIcon" aria-hidden="true">👥</span>
                <?= $TotalClases ?> <?= $TotalClases === 1 ? 'clase' : 'clases' ?>
            </span>

            <span class="MaestroHeroStat">
                <span class="SgceColorIcon" aria-hidden="true">✅</span>
                <?= $AsistenciasHoyMaestro ?> asistencias hoy
            </span>

            <a href="Planeaciones.php" class="MaestroHeroStat MaestroHeroLink">
                <span class="SgceColorIcon" aria-hidden="true">☁️</span>
                <?= $TotalPlaneacionesSubidas ?>/<?= $TotalPlaneacionesRequeridas ?> planeaciones
            </a>

            <a href="Logout.php" class="SgceHeroBtn SgceHeroLogout" title="Cerrar sesión" aria-label="Cerrar sesión" data-sgce-confirm="logout" data-sgce-confirm-title="CERRAR SESIÓN" data-sgce-confirm-subtitle="SALIDA DEL SISTEMA" data-sgce-confirm-message="¿REALMENTE DESEAS CERRAR SESIÓN?" data-sgce-confirm-detail="Se cerrará tu sesión actual y tendrás que iniciar sesión nuevamente para entrar al sistema." data-sgce-confirm-button="SÍ, CERRAR SESIÓN" data-sgce-confirm-loading="CERRANDO SESIÓN..." data-sgce-confirm-icon="fa-right-from-bracket">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </section>




    <?php if(!empty($AvisosMaestro)): ?>
        <section class="card card-custom MaestroAvisosPanel p-4 mb-4" aria-label="Avisos importantes">
            <div class="MaestroAvisosHeader">
                <div class="MaestroAvisosTitleBlock">
                    <span class="MaestroAvisosIcon"><span class="SgceColorIcon" aria-hidden="true">📣</span></span>
                    <div>
                        <span class="MaestroAvisosEyebrow">Comunicación escolar</span>
                        <h5>Avisos importantes</h5>
                    </div>
                </div>
                <span class="MaestroAvisosBadge"><?= count($AvisosMaestro) ?> <?= count($AvisosMaestro) === 1 ? 'aviso' : 'avisos' ?></span>
            </div>

            <div class="MaestroAvisosGrid">
                <?php foreach($AvisosMaestro as $Aviso): ?>
                    <article class="MaestroAvisoItem">
                        <div class="MaestroAvisoItemIcon"><i class="fa-solid fa-bell"></i></div>
                        <div class="MaestroAvisoItemBody">
                            <h6><?= htmlspecialchars($Aviso['Titulo'], ENT_QUOTES, 'UTF-8') ?></h6>
                            <div class="MaestroAvisoFecha">
                                <i class="fa-regular fa-clock"></i>
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($Aviso['FechaCreacion'])), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <p><?= nl2br(htmlspecialchars($Aviso['Mensaje'], ENT_QUOTES, 'UTF-8')) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="MaestroClasesGrid">

        <?php if(empty($MisClases)): ?>

            <div class="MaestroEmptyState">
                <div class="MaestroEmptyNotice" role="status" aria-live="polite">

                    <div class="MaestroEmptyIcon">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>

                    <div class="MaestroEmptyContent">
                        <span class="MaestroEmptyLabel">Aviso del sistema</span>
                        <h5>Sin materias asignadas</h5>
                        <p>Actualmente no tienes materias vinculadas. Cuando administración te asigne un grupo, aparecerá aquí automáticamente.</p>
                    </div>

                    <button type="button" class="MaestroEmptyClose" aria-label="Cerrar aviso" data-maestro-empty-close="true">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>
            </div>

        <?php else: ?>

            <?php foreach($MisClases as $Clase): ?>

                <div class="MaestroClaseItem">

                    <div class="card CardClase h-100">

                        <div class="CardHeader">

                            <div class="MaestroCardTop">

                                <div class="MaestroMateriaInfo">

                                    <h4>
                                        <?= htmlspecialchars($Clase['MateriaNombre']) ?>
                                    </h4>

                                    <span class="BadgeTurno">

                                        <i class="fa-solid <?= strtoupper((string)$Clase['Turno']) === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>

                                        <?= htmlspecialchars($Clase['Turno']) ?>

                                    </span>

                                </div>

                                <div class="MateriaIcon">
                                    <span class="SgceEmojiIcon" aria-hidden="true">📚</span>
                                </div>

                            </div>

                        </div>

                        <div class="card-body">

                            <div class="InfoGrupo mb-3">

                                <?php $TurnoClase = strtoupper((string)$Clase['Turno']); ?>
                                <div class="InfoGrupoLabel">Grupo asignado</div>

                                <div class="GrupoTurnoBadge <?= $TurnoClase === 'MATUTINO' ? 'GrupoTurnoMatutino' : 'GrupoTurnoVespertino' ?>">
                                    <i class="fa-solid <?= $TurnoClase === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>
                                    <?= htmlspecialchars($Clase['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Clase['Grupo'], ENT_QUOTES, 'UTF-8') ?>" - <?= htmlspecialchars($TurnoClase, ENT_QUOTES, 'UTF-8') ?>
                                </div>

                            </div>

                            <div class="MaestroAccionesGrid">

                                <a href="Calificar.php?AsignacionId=<?= $Clase['AsignacionId'] ?>"
                                   class="btn BotonAccion BtnCalificaciones">

                                    <span class="SgceEmojiIcon" aria-hidden="true">📊</span>
                                    Calificaciones

                                </a>

                                <a href="Asistencia.php?id=<?= $Clase['AsignacionId'] ?>"
                                   class="btn BotonAccion BtnAsistencia">

                                    <span class="SgceEmojiIcon" aria-hidden="true">✅</span>
                                    Asistencia

                                </a>

                                <a href="Planeaciones.php?Materia=<?= urlencode($Clase['MateriaNombre']) ?>"
                                   class="btn BotonAccion BtnPlaneacionesDocente">

                                    <span class="SgceEmojiIcon" aria-hidden="true">🗂️</span>
                                    Planeación

                                </a>

                            </div>

                            <hr class="MaestroSeparador">

                            <div class="SeccionExportar">

                                <h6 class="fw-bold mb-3">
                                    <span class="SgceEmojiIcon" aria-hidden="true">📤</span>
                                    Exportaciones
                                </h6>

                                <div class="MaestroExportGrid">

                                    <div>

                                        <a href="ExportarCalificaciones.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel"
                                           class="btn BtnExport ExportCalifExcel w-100">

                                            <span class="SgceEmojiIcon" aria-hidden="true">📗</span>
                                            Calif. Excel

                                        </a>

                                    </div>

                                    <div>

                                        <a href="ExportarCalificaciones.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf"
                                           target="_blank" rel="noopener noreferrer"
                                           class="btn BtnExport ExportCalifPdf w-100">

                                            <span class="SgceEmojiIcon" aria-hidden="true">📕</span>
                                            Calif. PDF

                                        </a>

                                    </div>

                                    <div>

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel&Rango=Hoy"
                                           class="btn BtnExport ExportAsisExcel w-100">

                                            <span class="SgceEmojiIcon" aria-hidden="true">📗</span>
                                            Asist. Excel

                                        </a>

                                    </div>

                                    <div>

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf&Rango=Hoy"
                                           target="_blank" rel="noopener noreferrer"
                                           class="btn BtnExport ExportAsisPdf w-100">

                                            <span class="SgceEmojiIcon" aria-hidden="true">📕</span>
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




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sgce-shared.js?cache=sgce2026final"></script>
</body>
</html>
