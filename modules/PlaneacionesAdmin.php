<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'planeaciones', 'No tienes permiso para revisar planeaciones.');
if (SgceTieneRol($UserSession, ['maestro'])) { SgceDenegarAcceso('Los maestros solo pueden consultar sus propias planeaciones desde el portal docente.'); }
RequerirCsrfPost();

function HPlanAdmin($Texto) { return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8'); }
function PlanAdminEstadoClase($Estado) {
    return match ($Estado) {
        'APROBADA' => 'EstadoAprobada',
        'DEVUELTA' => 'EstadoDevuelta',
        'SUBIDA' => 'EstadoSubida',
        default => 'EstadoPendiente',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['RevisarPlaneacion'])) {
    try {
        $Id = (int)($_POST['PlaneacionId'] ?? 0);
        $Estado = strtoupper(trim((string)($_POST['EstadoRevision'] ?? 'SUBIDA')));
        $Nota = trim((string)($_POST['NotaRevision'] ?? ''));
        if ($Id <= 0) { throw new Exception('Planeación inválida.'); }
        if (!in_array($Estado, ['SUBIDA', 'APROBADA', 'DEVUELTA'], true)) { throw new Exception('Estado de revisión inválido.'); }
        if ($Estado === 'DEVUELTA' && $Nota === '') { throw new Exception('Escribe una nota para devolver la planeación.'); }
        if ((function_exists('mb_strlen') ? mb_strlen($Nota, 'UTF-8') : strlen($Nota)) > 1200) { throw new Exception('La nota no debe superar 1200 caracteres.'); }
        $Stmt = $Pdo->prepare('UPDATE Planeaciones SET Estado = ?, NotaRevision = ?, RevisadoPor = ?, FechaRevision = NOW() WHERE Id = ?');
        $Stmt->execute([$Estado, $Nota !== '' ? $Nota : null, (int)$UserSession['Id'], $Id]);
        RegistrarBitacora($Pdo, $UserSession, 'REVISAR_PLANEACION', 'Planeaciones', $Id, 'ESTADO: ' . $Estado);
        $_SESSION['MensajePlaneacionesAdmin'] = 'Revisión guardada correctamente.';
        $_SESSION['MensajePlaneacionesAdminTipo'] = 'success';
    } catch (Exception $E) {
        $_SESSION['MensajePlaneacionesAdmin'] = $E->getMessage();
        $_SESSION['MensajePlaneacionesAdminTipo'] = 'danger';
    }
    header('Location: PlaneacionesAdmin.php');
    exit;
}

$CicloActivo = SgceCicloActivo($Pdo);
$CicloId = (int)($CicloActivo['Id'] ?? 0);
$CantidadPlaneaciones = SgceCantidadPlaneaciones($Pdo);
$OfertaActivaPlaneacionesAdmin = SgceOfertaActiva($Pdo);
$OfertaIdPlaneacionesAdmin = (int)($OfertaActivaPlaneacionesAdmin['Id'] ?? 0);
$FiltroMaestro = (int)($_GET['MaestroId'] ?? 0);
$FiltroGrupo = (int)($_GET['GrupoId'] ?? 0);
$FiltroMateria = SgceNormalizarMateriaPlaneacion($_GET['Materia'] ?? '');
$FiltroEstado = strtoupper(trim((string)($_GET['Estado'] ?? '')));
$FiltroNumero = (int)($_GET['Numero'] ?? 0);
if (!in_array($FiltroEstado, ['', 'PENDIENTE', 'SUBIDA', 'APROBADA', 'DEVUELTA'], true)) { $FiltroEstado = ''; }
if ($FiltroNumero < 1 || $FiltroNumero > $CantidadPlaneaciones) { $FiltroNumero = 0; }

$Maestros = $Pdo->query("SELECT Id, NombreCompleto FROM Usuarios WHERE Rol = 'maestro' AND Activo = 1 ORDER BY NombreCompleto")->fetchAll();
$Grupos = [];
$Materias = [];
if ($CicloId > 0) {
    $StmtGruposPlan = $Pdo->prepare("SELECT Id, CONCAT(Grado, ' ', Grupo, ' - ', Turno) AS Nombre FROM Grupos WHERE CicloId = ? AND Activo = 1 ORDER BY Turno, Grado, Grupo");
    $StmtGruposPlan->execute([$CicloId]);
    $Grupos = $StmtGruposPlan->fetchAll();

    $StmtMateriasPlan = $Pdo->prepare("SELECT DISTINCT MateriaNombre FROM Asignaciones WHERE CicloId = ? AND Activo = 1 ORDER BY MateriaNombre");
    $StmtMateriasPlan->execute([$CicloId]);
    $Materias = $StmtMateriasPlan->fetchAll(PDO::FETCH_COLUMN);
}

$Params = [$CicloId, $OfertaIdPlaneacionesAdmin];
$Where = ["A.CicloId = ?", "G.OfertaId = ?", "A.Activo = 1", "G.Activo = 1", "G.CicloId = A.CicloId", "U.Rol = 'maestro'", "U.Activo = 1"];
if ($FiltroMaestro > 0) { $Where[] = 'U.Id = ?'; $Params[] = $FiltroMaestro; }
if ($FiltroGrupo > 0) { $Where[] = 'G.Id = ?'; $Params[] = $FiltroGrupo; }
if ($FiltroMateria !== '') { $Where[] = 'A.MateriaNombre = ?'; $Params[] = $FiltroMateria; }

$SqlCombosBase = "SELECT U.Id AS MaestroId, U.NombreCompleto, A.MateriaNombre, G.ProgramaId, PE.Nombre AS ProgramaNombre,
    GROUP_CONCAT(DISTINCT CONCAT(G.Grado, ' ', G.Grupo, ' - ', G.Turno) ORDER BY G.Turno, G.Grado, G.Grupo SEPARATOR ', ') AS Grupos
    FROM Asignaciones A
    INNER JOIN Usuarios U ON U.Id = A.MaestroId
    INNER JOIN Grupos G ON G.Id = A.GrupoId AND G.CicloId = A.CicloId
    INNER JOIN ProgramasEducativos PE ON PE.Id = G.ProgramaId
    WHERE " . implode(' AND ', $Where) . "
    GROUP BY U.Id, U.NombreCompleto, A.MateriaNombre, G.ProgramaId, PE.Nombre";

$StmtTotalCombos = $Pdo->prepare('SELECT COUNT(*) FROM (' . $SqlCombosBase . ') C');
$StmtTotalCombos->execute($Params);
$TotalCombos = (int)$StmtTotalCombos->fetchColumn();
$TotalRequeridas = $TotalCombos * max(1, $CantidadPlaneaciones);

$NumerosSqlPartes = [];
for ($I = 1; $I <= max(1, $CantidadPlaneaciones); $I++) { $NumerosSqlPartes[] = 'SELECT ' . $I . ' AS Numero'; }
$SqlNumeros = implode(' UNION ALL ', $NumerosSqlPartes);

$OuterWhere = [];
$OuterParams = [];
if ($FiltroNumero > 0) { $OuterWhere[] = 'N.Numero = ?'; $OuterParams[] = $FiltroNumero; }
if ($FiltroEstado !== '') { $OuterWhere[] = "COALESCE(P.Estado, 'PENDIENTE') = ?"; $OuterParams[] = $FiltroEstado; }
$SqlOuterWhere = $OuterWhere ? (' WHERE ' . implode(' AND ', $OuterWhere)) : '';

$SqlPlaneacionesBase = ' FROM (' . $SqlCombosBase . ') C
    CROSS JOIN (' . $SqlNumeros . ') N
    LEFT JOIN Planeaciones P ON P.CicloId = ? AND P.OfertaId = ? AND P.ProgramaId = C.ProgramaId AND P.MaestroId = C.MaestroId AND P.MateriaNombre = C.MateriaNombre AND P.Numero = N.Numero
    LEFT JOIN Usuarios R ON R.Id = P.RevisadoPor' . $SqlOuterWhere;
$ParamsPlaneaciones = array_merge($Params, [$CicloId, $OfertaIdPlaneacionesAdmin], $OuterParams);

$StmtConteo = $Pdo->prepare('SELECT COUNT(*)' . $SqlPlaneacionesBase);
$StmtConteo->execute($ParamsPlaneaciones);
$TotalFilas = (int)$StmtConteo->fetchColumn();

$StmtStats = $Pdo->prepare("SELECT
        SUM(CASE WHEN P.Id IS NOT NULL THEN 1 ELSE 0 END) AS TotalSubidas,
        SUM(CASE WHEN COALESCE(P.Estado, 'PENDIENTE') = 'PENDIENTE' THEN 1 ELSE 0 END) AS TotalPendientes
    " . $SqlPlaneacionesBase);
$StmtStats->execute($ParamsPlaneaciones);
$StatsPlaneaciones = $StmtStats->fetch() ?: [];
$TotalSubidas = (int)($StatsPlaneaciones['TotalSubidas'] ?? 0);
$TotalPendientes = (int)($StatsPlaneaciones['TotalPendientes'] ?? 0);

$PaginaPlaneaciones = SgcePaginaActual('PagPlan', 1);
$PorPaginaPlaneaciones = 6;
[$OffsetPlaneaciones, $LimitPlaneaciones] = SgceLimitOffset($PaginaPlaneaciones, $PorPaginaPlaneaciones);

$SqlPlaneaciones = "SELECT
        C.MaestroId,
        C.NombreCompleto,
        C.MateriaNombre,
        C.ProgramaId,
        C.ProgramaNombre,
        C.Grupos,
        N.Numero,
        COALESCE(P.Estado, 'PENDIENTE') AS EstadoCalculado,
        P.Id,
        P.ArchivoGuardado,
        P.ArchivoOriginal,
        P.VersionArchivo,
        P.FechaSubida,
        P.FechaActualizacion,
        P.NotaRevision,
        P.RevisadoPor,
        P.FechaRevision,
        R.NombreCompleto AS RevisorNombre
    " . $SqlPlaneacionesBase . "
    ORDER BY C.NombreCompleto, C.MateriaNombre, N.Numero
    LIMIT ? OFFSET ?";
$StmtFilas = $Pdo->prepare($SqlPlaneaciones);
$IndiceParametro = 1;
foreach ($ParamsPlaneaciones as $Valor) { $StmtFilas->bindValue($IndiceParametro++, $Valor); }
$StmtFilas->bindValue($IndiceParametro++, $LimitPlaneaciones, PDO::PARAM_INT);
$StmtFilas->bindValue($IndiceParametro, $OffsetPlaneaciones, PDO::PARAM_INT);
$StmtFilas->execute();

$Filas = [];
foreach ($StmtFilas->fetchAll() as $FilaDb) {
    $Registro = null;
    if (!empty($FilaDb['Id'])) {
        $Registro = [
            'Id' => $FilaDb['Id'],
            'ArchivoGuardado' => $FilaDb['ArchivoGuardado'],
            'ArchivoOriginal' => $FilaDb['ArchivoOriginal'],
            'VersionArchivo' => $FilaDb['VersionArchivo'],
            'FechaSubida' => $FilaDb['FechaSubida'],
            'FechaActualizacion' => $FilaDb['FechaActualizacion'],
            'Estado' => $FilaDb['EstadoCalculado'],
            'NotaRevision' => $FilaDb['NotaRevision'],
            'RevisadoPor' => $FilaDb['RevisadoPor'],
            'FechaRevision' => $FilaDb['FechaRevision'],
            'RevisorNombre' => $FilaDb['RevisorNombre'],
        ];
    }
    $Filas[] = [
        'combo' => [
            'MaestroId' => $FilaDb['MaestroId'],
            'NombreCompleto' => $FilaDb['NombreCompleto'],
            'MateriaNombre' => $FilaDb['MateriaNombre'],
            'ProgramaId' => $FilaDb['ProgramaId'],
            'ProgramaNombre' => $FilaDb['ProgramaNombre'],
            'Grupos' => $FilaDb['Grupos'],
        ],
        'numero' => (int)$FilaDb['Numero'],
        'estado' => $FilaDb['EstadoCalculado'],
        'registro' => $Registro,
    ];
}
$Mensaje = $_SESSION['MensajePlaneacionesAdmin'] ?? '';
$MensajeTipo = $_SESSION['MensajePlaneacionesAdminTipo'] ?? 'success';
unset($_SESSION['MensajePlaneacionesAdmin'], $_SESSION['MensajePlaneacionesAdminTipo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= SgceLayoutHeadBase('Planeaciones | SGCE', $Pdo, ['assets/css/planeaciones-botones-metalicos.css', 'assets/css/modules/planeaciones-admin-review-modal.css', 'assets/css/admin-paginacion-busqueda.css']) ?>
</head>
<body>
<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">☁️</span></div>
            <div><h1>Planeaciones</h1><p>Revisa, descarga y da seguimiento a las planeaciones docentes.</p></div>
        </div>
        <div class="SgceHeroActions"><a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a></div>
    </section>

    <?php if ($Mensaje !== ''): ?>
        <div class="alert alert-<?= HPlanAdmin($MensajeTipo) ?> alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
            <i class="fa-solid <?= $MensajeTipo === 'success' ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-2"></i><?= HPlanAdmin($Mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <section class="PlaneacionesStatsGrid mb-4">
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">👨‍🏫</span></span><div><strong><?= $TotalCombos ?></strong><small>Docente-programa-materia</small></div></div>
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">📝</span></span><div><strong><?= $CantidadPlaneaciones ?></strong><small>Entregas por materia</small></div></div>
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">☁️</span></span><div><strong><?= $TotalSubidas ?>/<?= $TotalRequeridas ?></strong><small>Subidas</small></div></div>
        <div class="PlaneacionStatCard"><span><span class="SgceColorIcon" aria-hidden="true">⏰</span></span><div><strong><?= $TotalPendientes ?></strong><small>Pendientes filtradas</small></div></div>
    </section>

    <section class="SgceConfigCard p-4 mb-4 PlaneacionesFiltroCard">
        <div class="SgceConfigHead"><span><span class="SgceColorIcon" aria-hidden="true">🔍</span></span><div><h2>Filtros de búsqueda</h2><p>Consulta por docente, materia, grupo, entrega o estado.</p></div></div>
        <form method="get" class="row g-3 align-items-end PlaneacionesFiltroForm">
            <div class="col-md-3"><label class="SgceFieldLabel">Docente</label><select class="form-select FormControl" name="MaestroId"><option value="0">TODOS</option><?php foreach($Maestros as $M): ?><option value="<?= (int)$M['Id'] ?>" <?= $FiltroMaestro === (int)$M['Id'] ? 'selected' : '' ?>><?= HPlanAdmin($M['NombreCompleto']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="SgceFieldLabel">Materia</label><select class="form-select FormControl" name="Materia"><option value="">TODAS</option><?php foreach($Materias as $Mat): ?><option value="<?= HPlanAdmin($Mat) ?>" <?= $FiltroMateria === $Mat ? 'selected' : '' ?>><?= HPlanAdmin($Mat) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2 PlaneacionesGrupoCol"><label class="SgceFieldLabel">Grupo</label><select class="form-select FormControl" name="GrupoId"><option value="0">TODOS</option><?php foreach($Grupos as $G): ?><option value="<?= (int)$G['Id'] ?>" <?= $FiltroGrupo === (int)$G['Id'] ? 'selected' : '' ?>><?= HPlanAdmin($G['Nombre']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2 PlaneacionesEstadoCol"><label class="SgceFieldLabel">Estado</label><select class="form-select FormControl" name="Estado"><option value="">TODOS</option><?php foreach(SgceEstadosPlaneacion() as $E): ?><option value="<?= HPlanAdmin($E) ?>" <?= $FiltroEstado === $E ? 'selected' : '' ?>><?= HPlanAdmin($E) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-1 PlaneacionesNumeroCol"><label class="SgceFieldLabel">No.</label><select class="form-select FormControl PlaneacionesNumeroSelect" name="Numero"><option value="0">TODAS</option><?php for($I=1;$I<=$CantidadPlaneaciones;$I++): ?><option value="<?= $I ?>" <?= $FiltroNumero === $I ? 'selected' : '' ?>><?= $I ?></option><?php endfor; ?></select></div>
            <div class="col-md-1 PlaneacionesBuscarCol"><button id="BtnBuscarPlaneacionesVerdeMetalico" class="BtnPlaneacionesBuscarVerdeMetalico w-100" type="submit" title="Buscar" aria-label="Buscar"><span class="SgceColorIcon" aria-hidden="true">🔍</span></button></div>
        </form>
    </section>

    <section class="SgceConfigCard p-4 PlaneacionesSeguimientoCard">
        <div class="SgceConfigHead"><span><span class="SgceColorIcon" aria-hidden="true">📋</span></span><div><h2>Seguimiento de planeaciones</h2><p>Descarga archivos y registra observaciones de revisión.</p></div></div>
        <div class="table-responsive PlaneacionesSeguimientoTableWrap">
            <table class="table align-middle SgceTablePro">
                <thead><tr><th>Docente</th><th>Programa</th><th>Materia</th><th>Grupos</th><th>Planeación</th><th>Estado</th><th>Archivo</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach($Filas as $Index => $Fila): $C=$Fila['combo']; $R=$Fila['registro']; $Estado=$Fila['estado']; ?>
                    <tr>
                        <td><?= HPlanAdmin($C['NombreCompleto']) ?></td>
                        <td class="small"><?= HPlanAdmin($C['ProgramaNombre'] ?? 'GENERAL') ?></td>
                        <td><span class="SgceChipInstitucional"><?= HPlanAdmin($C['MateriaNombre']) ?></span></td>
                        <td class="small text-muted fw-semibold"><?= HPlanAdmin($C['Grupos']) ?></td>
                        <td><span class="SgceChipInstitucional">No. <?= (int)$Fila['numero'] ?></span><?php if($R): ?><span class="SgceChipInstitucional PlaneacionVersionChip">V<?= (int)($R['VersionArchivo'] ?? 1) ?></span><?php endif; ?></td>
                        <td><span class="PlaneacionEstadoBadge <?= PlanAdminEstadoClase($Estado) ?>"><?= HPlanAdmin($Estado) ?></span></td>
                        <td><?php if($R): ?><a class="BtnPlaneacionDownload" href="DescargarPlaneacion.php?Id=<?= (int)$R['Id'] ?>"><i class="fa-solid fa-download"></i> Descargar</a><div class="PlaneacionFecha mt-2">Actualizado: <?= HPlanAdmin(date('d/m/Y H:i', strtotime($R['FechaActualizacion']))) ?></div><?php else: ?><span class="text-muted fw-bold">Sin archivo</span><?php endif; ?></td>
                        <td>
                            <?php if($R): ?>
                                <button type="button" class="ActionBtn BtnTeacherEdit" data-bs-toggle="modal" data-bs-target="#ModalRevision<?= (int)$R['Id'] ?>"><i class="fa-solid fa-pen-to-square"></i><span>Revisar</span></button>
                                <div class="modal fade" id="ModalRevision<?= (int)$R['Id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg"><form method="post" class="modal-content PlaneacionReviewModal <?= PlanAdminEstadoClase($Estado) ?>">
                                        <?= CampoCsrf() ?>
                                        <input type="hidden" name="PlaneacionId" value="<?= (int)$R['Id'] ?>">
                                        <div class="modal-header PlaneacionReviewHeader">
                                            <div class="PlaneacionReviewIcon"><i class="fa-solid fa-clipboard-check"></i></div>
                                            <div class="PlaneacionReviewTitle">
                                                <span class="PlaneacionReviewKicker">Revisión académica</span>
                                                <h5 class="modal-title">Analizar planeación</h5>
                                                <small><?= HPlanAdmin($C['NombreCompleto']) ?> · <?= HPlanAdmin($C['MateriaNombre']) ?> · Planeación No. <?= (int)$Fila['numero'] ?></small>
                                            </div>
                                            <button type="button" class="btn-close PlaneacionReviewClose" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                        </div>
                                        <div class="modal-body PlaneacionReviewBody">
                                            <div class="PlaneacionReviewPreview">
                                                <div class="PlaneacionReviewPreviewIcon"><i class="fa-solid fa-clock"></i></div>
                                                <div>
                                                    <strong class="PlaneacionReviewPreviewTitle">Planeación en revisión</strong>
                                                    <p class="PlaneacionReviewPreviewText">Selecciona el resultado de la revisión y agrega una nota clara para el docente.</p>
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="PlaneacionReviewMetaGrid">
                                                        <div class="PlaneacionReviewFieldBlock">
                                                            <label class="SgceFieldLabel">Resultado de la revisión</label>
                                                            <select class="form-select FormControl PlaneacionReviewSelect" name="EstadoRevision" required>
                                                                <option value="SUBIDA" <?= $Estado==='SUBIDA'?'selected':'' ?>>EN REVISIÓN / PENDIENTE</option>
                                                                <option value="APROBADA" <?= $Estado==='APROBADA'?'selected':'' ?>>APROBADA</option>
                                                                <option value="DEVUELTA" <?= $Estado==='DEVUELTA'?'selected':'' ?>>DEVUELTA PARA CORRECCIÓN</option>
                                                            </select>
                                                        </div>
                                                        <div class="PlaneacionReviewFileBlock">
                                                            <label class="SgceFieldLabel">Archivo cargado</label>
                                                            <div class="PlaneacionReviewInfoBox">
                                                                <i class="fa-solid fa-file-lines"></i>
                                                                <div><b>Archivo:</b> <span class="PlaneacionReviewFileName"><?= HPlanAdmin($R['ArchivoOriginal'] ?? 'Planeación cargada') ?></span><span>Última actualización: <?= HPlanAdmin(date('d/m/Y H:i', strtotime($R['FechaActualizacion']))) ?></span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="SgceFieldLabel">Nota para el docente</label>
                                                    <textarea class="form-control FormControl PlaneacionReviewNote" name="NotaRevision" rows="5" maxlength="1200" placeholder="Escribe una observación clara. Si devuelves la planeación, indica exactamente qué debe corregirse."><?= HPlanAdmin($R['NotaRevision'] ?? '') ?></textarea>
                                                    <div class="PlaneacionReviewHint"><i class="fa-solid fa-circle-info"></i><span>Cuando el estado sea devuelto, la nota será obligatoria para que el docente sepa qué corregir.</span></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer PlaneacionReviewFooter"><button type="button" class="PlaneacionReviewCancel" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cancelar</button><button type="submit" name="RevisarPlaneacion" value="1" class="PlaneacionReviewSave"><i class="fa-solid fa-floppy-disk"></i> Guardar revisión</button></div>
                                    </form></div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted fw-bold">Pendiente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($Filas)): ?><tr class="PlaneacionesEmptyRow"><td colspan="8" class="text-center fw-bold text-muted py-4">No hay registros con los filtros seleccionados.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="PlaneacionesPagerWrap mt-3">
            <?= SgceRenderPager('PagPlan', $PaginaPlaneaciones, $TotalFilas, $PorPaginaPlaneaciones, ['MaestroId' => $FiltroMaestro, 'GrupoId' => $FiltroGrupo, 'Materia' => $FiltroMateria, 'Estado' => $FiltroEstado, 'Numero' => $FiltroNumero], false) ?>
        </div>
        <div class="SgcePagerInfo mt-2"><?= HPlanAdmin(SgcePagerResumenTexto($PaginaPlaneaciones, $TotalFilas, $PorPaginaPlaneaciones, count($Filas))) ?></div>
    </section>
</div>
<?= SgceLayoutSharedJs(['assets/js/PlaneacionesAdmin.js']) ?>
</body>
</html>
