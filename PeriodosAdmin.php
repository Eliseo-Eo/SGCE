<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !in_array($UserSession['Rol'], ['admin','director'], true)) {
    header('Location: index.php');
    exit;
}

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
            $Stmt = $Pdo->prepare("\n                INSERT INTO CiclosEscolares (Nombre, FechaInicio, FechaFin, Activo)\n                VALUES (?, ?, ?, ?)\n                ON DUPLICATE KEY UPDATE\n                    FechaInicio = VALUES(FechaInicio),\n                    FechaFin = VALUES(FechaFin),\n                    Activo = VALUES(Activo)\n            ");
            $Stmt->execute([$Nombre, $FechaInicio, $FechaFin, $Activo]);
            RegistrarBitacora($Pdo, $UserSession, 'GUARDAR_CICLO_ESCOLAR', 'CiclosEscolares', null, $Nombre);
            $_SESSION['MensajePeriodos'] = 'Ciclo escolar guardado correctamente.';
        } else {
            $_SESSION['MensajePeriodos'] = 'Datos de ciclo inválidos.';
        }

        header('Location: PeriodosAdmin.php');
        exit;
    }

    if (isset($_POST['AltaPeriodo'])) {
        $CicloId = (int)($_POST['CicloId'] ?? 0);
        $Nombre = trim((string)($_POST['NombrePeriodo'] ?? ''));
        $Orden = max(1, (int)($_POST['Orden'] ?? 1));
        $Activo = isset($_POST['ActivoPeriodo']) ? 1 : 0;

        if ($CicloId > 0 && $Nombre !== '') {
            $NombrePeriodo = function_exists('mb_strtoupper') ? mb_strtoupper($Nombre, 'UTF-8') : strtoupper($Nombre);
            $Stmt = $Pdo->prepare("\n                INSERT INTO PeriodosEvaluacion (CicloId, Nombre, Orden, Activo)\n                VALUES (?, ?, ?, ?)\n                ON DUPLICATE KEY UPDATE\n                    Orden = VALUES(Orden),\n                    Activo = VALUES(Activo)\n            ");
            $Stmt->execute([$CicloId, $NombrePeriodo, $Orden, $Activo]);
            RegistrarBitacora($Pdo, $UserSession, 'GUARDAR_PERIODO_EVALUACION', 'PeriodosEvaluacion', null, $NombrePeriodo);
            $_SESSION['MensajePeriodos'] = 'Periodo guardado correctamente.';
        } else {
            $_SESSION['MensajePeriodos'] = 'Datos de periodo inválidos.';
        }

        header('Location: PeriodosAdmin.php');
        exit;
    }
}

$CiclosTodos = $Pdo->query("SELECT * FROM CiclosEscolares ORDER BY FechaInicio DESC, Id DESC")->fetchAll();

$PaginaCiclos = SgcePaginaActual('PagCiclos', 1);
$PorPaginaCiclos = 4;
[$OffsetCiclos, $LimitCiclos] = SgceLimitOffset($PaginaCiclos, $PorPaginaCiclos);
$TotalCiclos = (int)$Pdo->query("SELECT COUNT(*) FROM CiclosEscolares")->fetchColumn();
$StmtCiclos = $Pdo->prepare("SELECT * FROM CiclosEscolares ORDER BY FechaInicio DESC, Id DESC LIMIT $LimitCiclos OFFSET $OffsetCiclos");
$StmtCiclos->execute();
$Ciclos = $StmtCiclos->fetchAll();

$PaginaPeriodos = SgcePaginaActual('PagPeriodos', 1);
$PorPaginaPeriodos = 4;
[$OffsetPeriodos, $LimitPeriodos] = SgceLimitOffset($PaginaPeriodos, $PorPaginaPeriodos);
$TotalPeriodos = (int)$Pdo->query("SELECT COUNT(*) FROM PeriodosEvaluacion")->fetchColumn();
$StmtPeriodos = $Pdo->prepare("\n    SELECT P.*, C.Nombre AS CicloNombre\n    FROM PeriodosEvaluacion P\n    JOIN CiclosEscolares C ON P.CicloId = C.Id\n    ORDER BY C.FechaInicio DESC, P.Orden ASC, P.Id ASC\n    LIMIT $LimitPeriodos OFFSET $OffsetPeriodos\n");
$StmtPeriodos->execute();
$Periodos = $StmtPeriodos->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGCE | Ciclos y Periodos</title>
    <link rel="icon" href="favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sgce-base.css?v=55">
</head>
<body>
<div class="SgceModuleWrap SgcePeriodosWrap">
    <div class="Top SgcePeriodosHero">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><i class="fa-solid fa-calendar-days"></i></div>
            <div>
                <h1>Ciclos escolares y periodos</h1>
                <p>Administra ciclos activos y periodos para separar calificaciones por evaluación.</p>
            </div>
        </div>
        <a class="BtnBack" href="Admin.php?Tab=inicio"><i class="fa-solid fa-house"></i> Volver a inicio</a>
    </div>

    <?php if ($Mensaje): ?>
        <div class="SgceNoticeInfo"><i class="fa-solid fa-circle-info"></i><span><?= HPeriodo($Mensaje) ?></span></div>
    <?php endif; ?>

    <section class="SgcePeriodosGrid">
        <article class="SgcePeriodCard SgcePeriodFormCard">
            <div class="SgcePeriodCardTitle">
                <span><i class="fa-solid fa-calendar-plus"></i></span>
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

                <button class="BtnPrimary w-100" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar ciclo</button>
            </form>
        </article>

        <article class="SgcePeriodCard SgcePeriodFormCard">
            <div class="SgcePeriodCardTitle">
                <span><i class="fa-solid fa-layer-group"></i></span>
                <div>
                    <h2>Nuevo / editar periodo</h2>
                    <p>Define bimestres, trimestres, periodos parciales o final.</p>
                </div>
            </div>
            <form method="POST" class="SgcePeriodForm">
                <?= CampoCsrf() ?>
                <input type="hidden" name="AltaPeriodo">

                <div class="SgcePeriodFormGrid">
                    <div>
                        <label class="SgceFieldLabel">Ciclo</label>
                        <select name="CicloId" class="form-select" required>
                            <?php foreach ($CiclosTodos as $C): ?>
                                <option value="<?= (int)$C['Id'] ?>"><?= HPeriodo($C['Nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="SgceFieldLabel">Periodo</label>
                        <input name="NombrePeriodo" class="form-control" placeholder="PRIMER PERIODO" required>
                    </div>
                    <div>
                        <label class="SgceFieldLabel">Orden</label>
                        <input type="number" name="Orden" class="form-control" min="1" value="1" required>
                    </div>
                </div>

                <label class="SgceSwitch">
                    <input type="checkbox" name="ActivoPeriodo" checked>
                    <span></span>
                    <strong>Activo</strong>
                </label>

                <button class="BtnPrimary SgcePeriodSubmit" type="submit"><i class="fa-solid fa-plus"></i> Guardar periodo</button>
            </form>
        </article>

        <article class="SgcePeriodCard SgcePeriodTableCard">
            <div class="SgcePeriodCardTitle">
                <span><i class="fa-solid fa-list-check"></i></span>
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
                <span><i class="fa-solid fa-table-list"></i></span>
                <div>
                    <h2>Periodos registrados</h2>
                    <p>Controla el orden y estado de cada periodo.</p>
                </div>
            </div>
            <div class="table-responsive SgcePeriodTableBox">
                <table class="table align-middle SgcePeriodTable">
                    <thead>
                        <tr><th>Ciclo</th><th>Periodo</th><th>Orden</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($Periodos as $P): ?>
                            <tr>
                                <td><?= HPeriodo($P['CicloNombre']) ?></td>
                                <td class="fw-bold"><?= HPeriodo($P['Nombre']) ?></td>
                                <td><?= (int)$P['Orden'] ?></td>
                                <td><span class="SgceStatusBadge <?= $P['Activo'] ? 'IsActive' : 'IsInactive' ?>"><?= $P['Activo'] ? 'ACTIVO' : 'INACTIVO' ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$Periodos): ?><tr><td colspan="4" class="text-center text-muted py-3">No hay periodos registrados.</td></tr><?php endif; ?>
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
