<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || $UserSession['Rol'] !== 'maestro') { header('Location: index.php'); exit; }
RequerirCsrfPost();

function HPlan($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function PlaneacionEstadoClase($Estado) {
    return match ($Estado) {
        'APROBADA' => 'EstadoAprobada',
        'DEVUELTA' => 'EstadoDevuelta',
        'SUBIDA' => 'EstadoSubida',
        default => 'EstadoPendiente',
    };
}

$MaestroId = (int)$UserSession['Id'];
$CicloActivo = SgceCicloActivo($Pdo);
$CicloId = (int)($CicloActivo['Id'] ?? 0);
$CantidadPlaneaciones = SgceCantidadPlaneaciones($Pdo);
$OfertaActivaPlaneacion = SgceOfertaActiva($Pdo);
$OfertaIdPlaneacion = (int)($OfertaActivaPlaneacion['Id'] ?? 0);
$ConfigAcademicaPlaneacion = SgceConfiguracionAcademicaPorOferta($Pdo, $OfertaIdPlaneacion);
$TipoPlaneacionActiva = SgceTipoPlaneacionValido((string)($ConfigAcademicaPlaneacion['TipoPlaneacion'] ?? 'PERIODO'));
$PeriodosPlaneacion = [];
if ($CicloId > 0 && $OfertaIdPlaneacion > 0) {
    $StmtPeriodosPlaneacion = $Pdo->prepare('SELECT Id, Orden FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ? AND Activo = 1 ORDER BY Orden ASC');
    $StmtPeriodosPlaneacion->execute([$CicloId, $OfertaIdPlaneacion]);
    foreach ($StmtPeriodosPlaneacion->fetchAll() as $PeriodoPlaneacion) { $PeriodosPlaneacion[(int)$PeriodoPlaneacion['Orden']] = (int)$PeriodoPlaneacion['Id']; }
}
$MateriasDocente = SgceMateriasDocente($Pdo, $MaestroId);
$MateriasPermitidas = [];
foreach ($MateriasDocente as $Mdoc) {
    $MateriasPermitidas[(int)($Mdoc['ProgramaId'] ?? 0) . '|' . (string)$Mdoc['MateriaNombre']] = $Mdoc;
}
$MateriaSeleccionada = SgceNormalizarMateriaPlaneacion($_GET['Materia'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['SubirPlaneacion'])) {
    try {
        if ($CicloId <= 0) { throw new Exception('No hay ciclo escolar activo.'); }
        $Materia = SgceNormalizarMateriaPlaneacion($_POST['MateriaNombre'] ?? '');
        $ProgramaIdPlaneacion = (int)($_POST['ProgramaId'] ?? 0);
        $Numero = (int)($_POST['Numero'] ?? 0);
        $Titulo = trim(preg_replace('/\s+/u', ' ', (string)($_POST['Titulo'] ?? '')));
        $LlaveMateriaPrograma = $ProgramaIdPlaneacion . '|' . $Materia;
        if (!isset($MateriasPermitidas[$LlaveMateriaPrograma])) { throw new Exception('La materia y programa seleccionados no están asignados a tu usuario.'); }
        if ($Numero < 1 || $Numero > $CantidadPlaneaciones) { throw new Exception('Número de planeación inválido.'); }
        $ValidacionArchivo = SgceValidarArchivoPlaneacion($_FILES['ArchivoPlaneacion'] ?? []);
        if ($ValidacionArchivo !== true) { throw new Exception($ValidacionArchivo); }

        $Archivo = $_FILES['ArchivoPlaneacion'];
        $NombreOriginal = (string)$Archivo['name'];
        $Ext = strtolower(pathinfo($NombreOriginal, PATHINFO_EXTENSION));
        $BaseDir = SgceCarpetaPlaneaciones();
        $SubDir = $BaseDir . '/M' . $MaestroId . '_' . SgceNombreArchivoSeguro($UserSession['Username']) . '/C' . $CicloId . '/P' . $ProgramaIdPlaneacion . '/' . SgceNombreArchivoSeguro($Materia);
        if (!is_dir($SubDir) && !@mkdir($SubDir, 0775, true) && !is_dir($SubDir)) { throw new Exception('No se pudo crear la carpeta de planeaciones.'); }
        if (!is_writable($SubDir)) { throw new Exception('La carpeta de planeaciones no tiene permisos de escritura.'); }

        $PeriodoIdPlaneacion = ($TipoPlaneacionActiva === 'PERIODO' && isset($PeriodosPlaneacion[$Numero])) ? (int)$PeriodosPlaneacion[$Numero] : 0;

        $StmtAnterior = $Pdo->prepare('SELECT Id, ArchivoGuardado, VersionArchivo FROM Planeaciones WHERE CicloId = ? AND OfertaId = ? AND ProgramaId = ? AND MaestroId = ? AND MateriaNombre = ? AND Numero = ? LIMIT 1');
        $StmtAnterior->execute([$CicloId, $OfertaIdPlaneacion, $ProgramaIdPlaneacion, $MaestroId, $Materia, $Numero]);
        $RegistroAnterior = $StmtAnterior->fetch(PDO::FETCH_ASSOC) ?: null;
        $ArchivoAnterior = $RegistroAnterior['ArchivoGuardado'] ?? null;
        $VersionArchivo = $RegistroAnterior ? ((int)($RegistroAnterior['VersionArchivo'] ?? 1) + 1) : 1;

        $NombreGuardado = SgceNombrePlaneacionInterno($CicloActivo['Nombre'] ?? 'CICLO', $UserSession['NombreCompleto'] ?? $UserSession['Username'], $Materia, $Numero, $Ext, $VersionArchivo);
        $RutaDestino = $SubDir . '/' . $NombreGuardado;
        if (!move_uploaded_file($Archivo['tmp_name'], $RutaDestino)) { throw new Exception('No se pudo guardar el archivo de planeación.'); }
        @chmod($RutaDestino, 0640);

        $Mime = '';
        if (function_exists('finfo_open')) {
            $Finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($Finfo) { $Mime = (string)finfo_file($Finfo, $RutaDestino); }
        }
        $Stmt = $Pdo->prepare("INSERT INTO Planeaciones
            (CicloId, OfertaId, ProgramaId, PeriodoId, TipoPlaneacion, MaestroId, MateriaNombre, Numero, VersionArchivo, Titulo, ArchivoOriginal, ArchivoGuardado, MimeType, TamanoBytes, Estado, NotaRevision, RevisadoPor, FechaRevision)
            VALUES (?, ?, ?, NULLIF(?,0), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'SUBIDA', NULL, NULL, NULL)
            ON DUPLICATE KEY UPDATE
                OfertaId = VALUES(OfertaId),
                ProgramaId = VALUES(ProgramaId),
                PeriodoId = VALUES(PeriodoId),
                TipoPlaneacion = VALUES(TipoPlaneacion),
                VersionArchivo = VALUES(VersionArchivo),
                Titulo = VALUES(Titulo),
                ArchivoOriginal = VALUES(ArchivoOriginal),
                ArchivoGuardado = VALUES(ArchivoGuardado),
                MimeType = VALUES(MimeType),
                TamanoBytes = VALUES(TamanoBytes),
                Estado = 'SUBIDA',
                NotaRevision = NULL,
                RevisadoPor = NULL,
                FechaRevision = NULL,
                FechaActualizacion = CURRENT_TIMESTAMP");
        $Stmt->execute([$CicloId, $OfertaIdPlaneacion, $ProgramaIdPlaneacion, $PeriodoIdPlaneacion, $TipoPlaneacionActiva, $MaestroId, $Materia, $Numero, $VersionArchivo, $Titulo, $NombreOriginal, $RutaDestino, $Mime, (int)filesize($RutaDestino)]);
        $PlaneacionId = $RegistroAnterior ? (int)$RegistroAnterior['Id'] : (int)$Pdo->lastInsertId();
        if ($ArchivoAnterior && is_string($ArchivoAnterior) && $ArchivoAnterior !== $RutaDestino && is_file($ArchivoAnterior)) { @unlink($ArchivoAnterior); }
        RegistrarBitacora($Pdo, $UserSession, $RegistroAnterior ? 'REEMPLAZAR_PLANEACION' : 'SUBIR_PLANEACION', 'Planeaciones', $PlaneacionId, 'PLANEACIÓN ' . $Numero . ' - ' . $Materia . ' - VERSIÓN ' . $VersionArchivo);
        $_SESSION['MensajePlaneacion'] = $RegistroAnterior ? 'Planeación actualizada correctamente. El archivo anterior fue reemplazado.' : 'Planeación enviada correctamente.';
        $_SESSION['MensajePlaneacionTipo'] = 'success';
        header('Location: Planeaciones.php?Materia=' . urlencode($Materia));
        exit;
    } catch (Exception $E) {
        $_SESSION['MensajePlaneacion'] = $E->getMessage();
        $_SESSION['MensajePlaneacionTipo'] = 'danger';
        header('Location: Planeaciones.php');
        exit;
    }
}

$Planeaciones = [];
if ($CicloId > 0) {
    $Stmt = $Pdo->prepare('SELECT Id, CicloId, OfertaId, PeriodoId, ProgramaId, MaestroId, MateriaNombre, Numero, TipoPlaneacion, VersionArchivo, Titulo, ArchivoOriginal, ArchivoGuardado, MimeType, TamanoBytes, Estado, NotaRevision, RevisadoPor, FechaRevision, FechaSubida, FechaActualizacion FROM Planeaciones WHERE CicloId = ? AND OfertaId = ? AND MaestroId = ? ORDER BY ProgramaId, MateriaNombre, Numero');
    $Stmt->execute([$CicloId, $OfertaIdPlaneacion, $MaestroId]);
    foreach ($Stmt->fetchAll() as $Row) {
        $Llave = (int)($Row['ProgramaId'] ?? 0) . '|' . (string)$Row['MateriaNombre'];
        $Planeaciones[$Llave][(int)$Row['Numero']] = $Row;
    }
}
$TotalRequeridas = count($MateriasDocente) * $CantidadPlaneaciones;
$TotalSubidas = 0;
$TotalAprobadas = 0;
$TotalDevueltas = 0;
foreach ($Planeaciones as $PorMateria) {
    foreach ($PorMateria as $P) {
        $TotalSubidas++;
        if ($P['Estado'] === 'APROBADA') { $TotalAprobadas++; }
        if ($P['Estado'] === 'DEVUELTA') { $TotalDevueltas++; }
    }
}
$Mensaje = $_SESSION['MensajePlaneacion'] ?? '';
$MensajeTipo = $_SESSION['MensajePlaneacionTipo'] ?? 'success';
unset($_SESSION['MensajePlaneacion'], $_SESSION['MensajePlaneacionTipo']);
$ConfigSistema = SgceObtenerConfiguracion($Pdo);
$NombreEscuela = trim((string)($ConfigSistema['NombreEscuela'] ?? 'SGCE'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= HPlan($NombreEscuela) ?> - Planeaciones</title>
<link rel="icon" type="image/x-icon" href="assets/media/img/favicon.ico">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<?= SgceCss('assets/css/sgce-base.min.css') ?>
<?= SgceCss('assets/css/sgce-soft-motion.css') ?>
<?= SgceEstilosTema($Pdo) ?>
</head>
<body>
<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">☁️</span></div>
            <div>
                <h1>Planeaciones docentes</h1>
                <p>Sube y consulta tus planeaciones por materia durante el ciclo activo.</p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <a href="Maestro.php" class="SgceBtnVolverInicio" title="Volver al portal docente"><i class="fa-solid fa-house"></i><span>Volver al portal</span></a>
            <a href="Logout.php" class="SgceHeroBtn SgceHeroLogout" title="Cerrar sesión" aria-label="Cerrar sesión" data-sgce-confirm="logout" data-sgce-confirm-title="CERRAR SESIÓN" data-sgce-confirm-subtitle="SALIDA DEL SISTEMA" data-sgce-confirm-message="¿REALMENTE DESEAS CERRAR SESIÓN?" data-sgce-confirm-detail="Se cerrará tu sesión actual y tendrás que iniciar sesión nuevamente para entrar al sistema." data-sgce-confirm-button="SÍ, CERRAR SESIÓN" data-sgce-confirm-loading="CERRANDO SESIÓN..." data-sgce-confirm-icon="fa-right-from-bracket"><i class="fa-solid fa-right-from-bracket"></i><span>Cerrar sesión</span></a>
        </div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HPlan($MensajeTipo) ?> alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
            <i class="fa-solid <?= $MensajeTipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-2"></i><?= HPlan($Mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <section class="PlaneacionesStatsGrid mb-4">
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">📝</span></span><div><strong><?= (int)$CantidadPlaneaciones ?></strong><small>Entregas por materia</small></div></div>
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">📚</span></span><div><strong><?= count($MateriasDocente) ?></strong><small>Materias activas</small></div></div>
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">☁️</span></span><div><strong><?= $TotalSubidas ?>/<?= $TotalRequeridas ?></strong><small>Planeaciones subidas</small></div></div>
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">✅</span></span><div><strong><?= $TotalAprobadas ?></strong><small>Aprobadas</small></div></div>
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">↩️</span></span><div><strong><?= $TotalDevueltas ?></strong><small>Devueltas</small></div></div>
    </section>

    <?php if (empty($MateriasDocente)): ?>
        <section class="SgceConfigCard p-4"><div class="SgceConfigHead"><span><span class="SgceColorIcon" aria-hidden="true">ℹ️</span></span><div><h2>Sin materias asignadas</h2><p>Cuando administración te asigne materias, aquí podrás subir tus planeaciones.</p></div></div></section>
    <?php else: ?>
        <div class="PlaneacionesMateriaGrid">
            <?php foreach ($MateriasDocente as $MateriaInfo): ?>
                <?php $Materia = (string)$MateriaInfo['MateriaNombre']; $ProgramaIdMateria = (int)($MateriaInfo['ProgramaId'] ?? 0); $LlavePlaneacion = $ProgramaIdMateria . '|' . $Materia; ?>
                <section class="PlaneacionMateriaCard" id="Materia<?= md5($LlavePlaneacion) ?>">
                    <div class="PlaneacionMateriaHeader">
                        <span class="PlaneacionMateriaIcon"><span class="SgceColorIcon" aria-hidden="true">📘</span></span>
                        <div>
                            <h2><?= HPlan($Materia) ?></h2>
                            <p><?= HPlan((($MateriaInfo['ProgramaNombre'] ?? '') !== 'GENERAL' ? (($MateriaInfo['ProgramaNombre'] ?? '') . ' · ') : '') . ($MateriaInfo['Grupos'] ?? '')) ?></p>
                        </div>
                    </div>
                    <div class="PlaneacionesEntregaGrid">
                        <?php for ($Numero = 1; $Numero <= $CantidadPlaneaciones; $Numero++): ?>
                            <?php
                                $Registro = $Planeaciones[$LlavePlaneacion][$Numero] ?? null;
                                $Estado = $Registro['Estado'] ?? 'PENDIENTE';
                                $IconoEstado = match ($Estado) {
                                    'APROBADA' => '✅',
                                    'DEVUELTA' => '↩️',
                                    'SUBIDA' => '📤',
                                    default => '⏳',
                                };
                            ?>
                            <article class="PlaneacionEntregaItem <?= PlaneacionEstadoClase($Estado) ?>">
                                <div class="PlaneacionEntregaTop">
                                    <div><strong>Planeación <?= $Numero ?></strong><small><?= HPlan($Estado) ?></small></div>
                                    <span class="PlaneacionEstadoIcono" aria-hidden="true"><?= HPlan($IconoEstado) ?></span>
                                </div>
                                <?php if ($Registro): ?>
                                    <div class="PlaneacionArchivoInfo">
                                        <i class="fa-solid fa-paperclip"></i>
                                        <span><?= HPlan($Registro['ArchivoOriginal']) ?></span>
                                    </div>
                                    <div class="PlaneacionFecha">Subida: <?= HPlan(date('d/m/Y H:i', strtotime($Registro['FechaActualizacion']))) ?> · Versión <?= (int)($Registro['VersionArchivo'] ?? 1) ?></div>
                                    <?php if (!empty($Registro['NotaRevision'])): ?>
                                        <div class="PlaneacionNota"><strong>Nota:</strong> <?= nl2br(HPlan($Registro['NotaRevision'])) ?></div>
                                    <?php endif; ?>
                                    <a class="BtnPlaneacionDownload" href="DescargarPlaneacion.php?Id=<?= (int)$Registro['Id'] ?>"><i class="fa-solid fa-download"></i> Descargar</a>
                                <?php else: ?>
                                    <p class="PlaneacionPendienteTexto">Todavía no se ha subido esta planeación.</p>
                                <?php endif; ?>
                                <form method="post" enctype="multipart/form-data" class="PlaneacionUploadForm">
                                    <?= CampoCsrf() ?>
                                    <input type="hidden" name="MateriaNombre" value="<?= HPlan($Materia) ?>">
                                    <input type="hidden" name="ProgramaId" value="<?= (int)$ProgramaIdMateria ?>">
                                    <input type="hidden" name="Numero" value="<?= $Numero ?>">
                                    <input type="text" name="Titulo" class="form-control FormControl" maxlength="180" placeholder="Título opcional" value="<?= HPlan($Registro['Titulo'] ?? '') ?>">
                                    <input type="file" name="ArchivoPlaneacion" class="form-control FormControl" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" required>
                                    <button type="submit" name="SubirPlaneacion" value="1" class="BtnPrimary PlaneacionUploadBtn"><span class="SgceColorIcon" aria-hidden="true">☁️</span> <?= $Registro ? 'Reemplazar archivo' : 'Subir planeación' ?></button>
                                </form>
                            </article>
                        <?php endfor; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= SgceJs('assets/js/sgce-shared.js') ?>
</body>
</html>
