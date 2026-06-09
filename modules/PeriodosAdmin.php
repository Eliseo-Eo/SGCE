<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once dirname(__DIR__) . '/config/Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { header('Location: index.php'); exit; }
SgceExigirPermiso($UserSession, 'periodos', 'Solo el administrador puede modificar ciclos y periodos.');

function HPeriodo($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

$Mensaje = $_SESSION['MensajePeriodos'] ?? '';
unset($_SESSION['MensajePeriodos']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RequerirCsrfPost();

    if (isset($_POST['AltaCiclo'])) {
        $Nombre = trim((string)($_POST['Nombre'] ?? ''));
        $FechaInicio = trim((string)($_POST['FechaInicio'] ?? ''));
        $FechaFin = trim((string)($_POST['FechaFin'] ?? ''));
        $Activo = isset($_POST['Activo']) ? 1 : 0;

        if ($Nombre !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaFin)) {
            try {
                $CicloActivoAnterior = SgceCicloActivo($Pdo);
                $CicloActivoAnteriorId = (int)($CicloActivoAnterior['Id'] ?? 0);
                $Pdo->beginTransaction();
                $Stmt = $Pdo->prepare("
                INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo)
                VALUES (?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE
                    FechaInicio = VALUES(FechaInicio),
                    FechaFin = VALUES(FechaFin)
            ");
            $Stmt->execute([$Nombre, $FechaInicio, $FechaFin]);
                $NuevoCicloId = (int)$Pdo->lastInsertId();
                if ($NuevoCicloId <= 0) {
                    $StmtCicloBuscar = $Pdo->prepare('SELECT Id FROM CiclosEscolares WHERE Nombre = ? LIMIT 1');
                    $StmtCicloBuscar->execute([$Nombre]);
                    $NuevoCicloId = (int)$StmtCicloBuscar->fetchColumn();
                }
                $PeriodosCopiadosTexto = '';
                if ($Activo === 1 && $NuevoCicloId > 0) {
                    SgceActivarCicloUnico($Pdo, $NuevoCicloId);
                    if ($CicloActivoAnteriorId > 0 && $CicloActivoAnteriorId !== $NuevoCicloId) {
                        try {
                            $CopiaPeriodos = SgceMigracionCopiarPeriodosDesdeOrigen($Pdo, $CicloActivoAnteriorId, $NuevoCicloId);
                            $TotalCopiados = (int)$CopiaPeriodos['Creados'] + (int)$CopiaPeriodos['Actualizados'];
                            if ($TotalCopiados > 0) { $PeriodosCopiadosTexto = ' Periodos copiados automáticamente: ' . $TotalCopiados . '.'; }
                        } catch (Throwable $EPeriodos) {
                            $PeriodosCopiadosTexto = ' No se pudieron copiar periodos automáticamente: ' . $EPeriodos->getMessage();
                        }
                    }
                }
                RegistrarBitacora($Pdo, $UserSession, 'GUARDAR_CICLO_ESCOLAR', 'CiclosEscolares', $NuevoCicloId, $Nombre . $PeriodosCopiadosTexto);
                $Pdo->commit();
                $_SESSION['MensajePeriodos'] = 'Ciclo escolar guardado correctamente.' . $PeriodosCopiadosTexto;
            } catch (Throwable $E) {
                if ($Pdo->inTransaction()) { $Pdo->rollBack(); }
                $_SESSION['MensajePeriodos'] = 'No se pudo guardar el ciclo escolar: ' . $E->getMessage();
            }
        } else {
            $_SESSION['MensajePeriodos'] = 'Datos de ciclo inválidos.';
        }

        header('Location: PeriodosAdmin.php');
        exit;
    }

    if (isset($_POST['AltaPeriodo'])) {
        $CicloId = (int)($_POST['CicloId'] ?? 0);
        $OfertaId = (int)($_POST['OfertaId'] ?? 0);
        if ($OfertaId <= 0) { $Oferta = SgceOfertaActiva($Pdo); $OfertaId = (int)($Oferta['Id'] ?? 0); }
        $NombrePeriodo = function_exists('mb_strtoupper') ? mb_strtoupper(trim((string)($_POST['NombrePeriodo'] ?? '')), 'UTF-8') : strtoupper(trim((string)($_POST['NombrePeriodo'] ?? '')));
        $OrdenRaw = trim((string)($_POST['OrdenPeriodo'] ?? ''));
        if ($OrdenRaw === '') {
            $StmtOrdenAutomatico = $Pdo->prepare('SELECT COALESCE(MAX(Orden), 0) + 1 FROM PeriodosEvaluacion WHERE CicloId = ? AND OfertaId = ?');
            $StmtOrdenAutomatico->execute([$CicloId, $OfertaId]);
            $Orden = (int)$StmtOrdenAutomatico->fetchColumn();
        } else {
            $Orden = (int)$OrdenRaw;
        }
        $Orden = max(1, min(12, $Orden));
        $Activo = isset($_POST['ActivoPeriodo']) ? 1 : 0;

        if ($CicloId > 0 && $OfertaId > 0 && $NombrePeriodo !== '' && mb_strlen($NombrePeriodo, 'UTF-8') <= 80 && SgceValidarParcial($Orden)) {
            if (SgceCicloOfertaTieneCalificaciones($Pdo, $CicloId, $OfertaId)) {
                $_SESSION['MensajePeriodos'] = 'No se puede modificar la estructura de periodos porque ya existen calificaciones en ese ciclo y oferta educativa.';
                header('Location: PeriodosAdmin.php');
                exit;
            }
            $Stmt = $Pdo->prepare("INSERT INTO PeriodosEvaluacion (CicloId, OfertaId, Nombre, Orden, Activo)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE Nombre = VALUES(Nombre), Activo = VALUES(Activo)");
            $Stmt->execute([$CicloId, $OfertaId, $NombrePeriodo, $Orden, $Activo]);
            RegistrarBitacora($Pdo, $UserSession, 'GUARDAR_PERIODO_EVALUACION', 'PeriodosEvaluacion', null, $NombrePeriodo);
            $_SESSION['MensajePeriodos'] = 'Periodo guardado correctamente.';
        } else {
            $_SESSION['MensajePeriodos'] = 'Datos de periodo inválidos.';
        }

        header('Location: PeriodosAdmin.php');
        exit;
    }
}

$CiclosTodos = $Pdo->query("SELECT Id, Nombre, FechaInicio, FechaFin, Activo, FechaCreacion FROM CiclosEscolares ORDER BY FechaInicio DESC, Id DESC")->fetchAll();
$OfertaActivaPeriodos = SgceOfertaActiva($Pdo);
$OfertasPeriodos = $Pdo->query("SELECT Id, Nombre FROM OfertasEducativas WHERE Activo = 1 ORDER BY Nombre ASC")->fetchAll();

$PaginaCiclos = SgcePaginaActual('PagCiclos', 1);
$PorPaginaCiclos = 4;
[$OffsetCiclos, $LimitCiclos] = SgceLimitOffset($PaginaCiclos, $PorPaginaCiclos);
$TotalCiclos = (int)$Pdo->query("SELECT COUNT(*) FROM CiclosEscolares")->fetchColumn();
$StmtCiclos = $Pdo->prepare("SELECT Id, Nombre, FechaInicio, FechaFin, Activo, FechaCreacion FROM CiclosEscolares ORDER BY FechaInicio DESC, Id DESC LIMIT $LimitCiclos OFFSET $OffsetCiclos");
$StmtCiclos->execute();
$Ciclos = $StmtCiclos->fetchAll();

$PaginaPeriodos = SgcePaginaActual('PagPeriodos', 1);
$PorPaginaPeriodos = 4;
[$OffsetPeriodos, $LimitPeriodos] = SgceLimitOffset($PaginaPeriodos, $PorPaginaPeriodos);
$TotalPeriodos = (int)$Pdo->query("SELECT COUNT(*) FROM PeriodosEvaluacion")->fetchColumn();
$StmtPeriodos = $Pdo->prepare("\n    SELECT P.*, C.Nombre AS CicloNombre, OE.Nombre AS OfertaNombre\n    FROM PeriodosEvaluacion P\n    JOIN CiclosEscolares C ON P.CicloId = C.Id\n    LEFT JOIN OfertasEducativas OE ON OE.Id = P.OfertaId\n    ORDER BY C.FechaInicio DESC, OE.Nombre ASC, P.Orden ASC, P.Id ASC\n    LIMIT $LimitPeriodos OFFSET $OffsetPeriodos\n");
$StmtPeriodos->execute();
$Periodos = $StmtPeriodos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGCE | Ciclos y Periodos</title>
    <link rel="icon" href="assets/media/img/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <?= SgceCss('assets/css/sgce-base.min.css') ?>
<?= SgceCss('assets/css/sgce-soft-motion.css') ?>
<?= SgceEstilosTema($Pdo) ?>
    <?= SgceCss('assets/css/periodos-verde-metalico.css') ?>
<?= SgceCss('assets/css/admin-paginacion-busqueda.css') ?>
</head>
<body>
<div class="SgceModuleWrap SgcePeriodosWrap">
    <div class="Top SgcePeriodosHero">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><span class="SgceColorIcon" aria-hidden="true">📅</span></div>
            <div>
                <h1>Ciclos escolares y periodos</h1>
                <p>Administra ciclos activos y periodos para separar calificaciones por evaluación.</p>
            </div>
        </div>
        <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
    </div>

    <?php if ($Mensaje): ?>
        <div class="SgceNoticeInfo"><i class="fa-solid fa-circle-info"></i><span><?= HPeriodo($Mensaje) ?></span></div>
    <?php endif; ?>

    <section class="SgcePeriodosGrid">
        <article class="SgcePeriodCard SgcePeriodFormCard">
            <div class="SgcePeriodCardTitle">
                <span><span class="SgceColorIcon" aria-hidden="true">🗓️</span></span>
                <div>
                    <h2>Nuevo / editar ciclo</h2>
                    <p>Registra el rango de fechas del ciclo escolar.</p>
                </div>
            </div>
            <form method="POST" class="SgcePeriodForm">
                <?= CampoCsrf() ?>
                <input type="hidden" name="AltaCiclo">

                <label class="SgceFieldLabel">Nombre del ciclo</label>
                <input name="Nombre" class="form-control" placeholder="2026-2027" required>

                <div class="SgceTwoCols">
                    <div>
                        <label class="SgceFieldLabel">Inicio</label>
                        <input type="date" name="FechaInicio" class="form-control" required>
                    </div>
                    <div>
                        <label class="SgceFieldLabel">Fin</label>
                        <input type="date" name="FechaFin" class="form-control" required>
                    </div>
                </div>

                <label class="SgceSwitch">
                    <input type="checkbox" name="Activo" checked>
                    <span></span>
                    <strong>Activo</strong>
                </label>

                <button id="BtnGuardarCicloVerdeMetalico" class="BtnPeriodoVerdeMetalico BtnVerdeMetalicoForzado w-100" type="submit" style="background:#047857 !important;background-image:linear-gradient(135deg,#064E3B 0%,#047857 52%,#059669 100%) !important;color:#FFFFFF !important;border:0 !important;box-shadow:0 18px 36px rgba(4,120,87,.30),inset 0 1px 0 rgba(255,255,255,.20) !important;text-shadow:0 1px 2px rgba(0,0,0,.34) !important;"><span class="SgceColorIcon" aria-hidden="true">💾</span> Guardar ciclo</button>
            </form>
        </article>

        <article class="SgcePeriodCard SgcePeriodFormCard">
            <div class="SgcePeriodCardTitle">
                <span><span class="SgceColorIcon" aria-hidden="true">📚</span></span>
                <div>
                    <h2>Nuevo / editar periodo</h2>
                    <p>Define los periodos oficiales del ciclo escolar. Pueden ser 1 a 12 según la oferta educativa.</p>
                </div>
            </div>
            <form method="POST" class="SgcePeriodForm">
                <?= CampoCsrf() ?>
                <input type="hidden" name="AltaPeriodo">

                <div class="SgcePeriodTopGrid">
                    <div>
                        <label class="SgceFieldLabel">Ciclo</label>
                        <select name="CicloId" class="form-select" required>
                            <?php foreach ($CiclosTodos as $C): ?>
                                <option value="<?= (int)$C['Id'] ?>"><?= HPeriodo($C['Nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="SgceFieldLabel">Oferta</label>
                        <select name="OfertaId" class="form-select" required>
                            <?php foreach ($OfertasPeriodos as $O): ?>
                                <option value="<?= (int)$O['Id'] ?>"><?= HPeriodo($O['Nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="SgcePeriodNameOrderGrid">
                    <div>
                        <label class="SgceFieldLabel">Nombre del periodo</label>
                        <input name="NombrePeriodo" class="form-control InputUpper" placeholder="PARCIAL 1 / ORDINARIO" required maxlength="80">
                    </div>
                    <div>
                        <label class="SgceFieldLabel">Orden</label>
                        <input name="OrdenPeriodo" class="form-control InputDigits" placeholder="Automático" min="1" max="12" maxlength="2" inputmode="numeric">
                        <small class="SgcePeriodAutoHint">Automático si lo dejas vacío.</small>
                    </div>
                </div>

                <label class="SgceSwitch">
                    <input type="checkbox" name="ActivoPeriodo" checked>
                    <span></span>
                    <strong>Activo</strong>
                </label>

                <button id="BtnGuardarPeriodoVerdeMetalico" class="BtnPeriodoVerdeMetalico BtnVerdeMetalicoForzado SgcePeriodSubmit" type="submit" style="background:#047857 !important;background-image:linear-gradient(135deg,#064E3B 0%,#047857 52%,#059669 100%) !important;color:#FFFFFF !important;border:0 !important;box-shadow:0 18px 36px rgba(4,120,87,.30),inset 0 1px 0 rgba(255,255,255,.20) !important;text-shadow:0 1px 2px rgba(0,0,0,.34) !important;"><span class="SgceColorIcon" aria-hidden="true">➕</span> Guardar periodo</button>
            </form>
        </article>

        <article class="SgcePeriodCard SgcePeriodTableCard">
            <div class="SgcePeriodCardTitle">
                <span><span class="SgceColorIcon" aria-hidden="true">📋</span></span>
                <div>
                    <h2>Ciclos registrados</h2>
                    <p>Consulta los ciclos disponibles en el sistema.</p>
                </div>
            </div>
            <div class="table-responsive SgcePeriodTableBox">
                <table class="table align-middle SgcePeriodTable">
                    <thead>
                        <tr><th>Ciclo</th><th>Fechas</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($Ciclos as $C): ?>
                            <tr>
                                <td class="fw-bold"><?= HPeriodo($C['Nombre']) ?></td>
                                <td><?= HPeriodo($C['FechaInicio']) ?> a <?= HPeriodo($C['FechaFin']) ?></td>
                                <td><span class="SgceStatusBadge <?= $C['Activo'] ? 'IsActive' : 'IsInactive' ?>"><?= $C['Activo'] ? 'ACTIVO' : 'INACTIVO' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$Ciclos): ?><tr><td colspan="3" class="text-center text-muted py-3">No hay ciclos registrados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= SgceRenderPager('PagCiclos', $PaginaCiclos, $TotalCiclos, $PorPaginaCiclos) ?>
        </article>

        <article class="SgcePeriodCard SgcePeriodTableCard">
            <div class="SgcePeriodCardTitle">
                <span><span class="SgceColorIcon" aria-hidden="true">📑</span></span>
                <div>
                    <h2>Periodos registrados</h2>
                    <p>Controla el orden y estado de cada periodo.</p>
                </div>
            </div>
            <div class="table-responsive SgcePeriodTableBox">
                <table class="table align-middle SgcePeriodTable">
                    <thead>
                        <tr><th>Ciclo</th><th>Oferta</th><th>Periodo</th><th>Orden</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($Periodos as $P): ?>
                            <tr>
                                <td><?= HPeriodo($P['CicloNombre']) ?></td>
                                <td><?= HPeriodo($P['OfertaNombre'] ?? 'GENERAL') ?></td>
                                <td class="fw-bold"><?= HPeriodo($P['Nombre']) ?></td>
                                <td><?= (int)$P['Orden'] ?></td>
                                <td><span class="SgceStatusBadge <?= $P['Activo'] ? 'IsActive' : 'IsInactive' ?>"><?= $P['Activo'] ? 'ACTIVO' : 'INACTIVO' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$Periodos): ?><tr><td colspan="5" class="text-center text-muted py-3">No hay periodos registrados.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= SgceRenderPager('PagPeriodos', $PaginaPeriodos, $TotalPeriodos, $PorPaginaPeriodos) ?>
        </article>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
