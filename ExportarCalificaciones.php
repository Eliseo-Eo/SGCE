<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { die("Acceso Denegado."); }

$AsignacionId = intval($_GET['AsignacionId'] ?? 0);
$GrupoId = intval($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';

if ($AsignacionId <= 0 && $GrupoId <= 0) {
    die("Parámetros inválidos. Debes enviar AsignacionId o GrupoId.");
}

function NombreArchivoSeguro($Texto) {
    $Texto = (string)$Texto;
    $Texto = str_replace(' ', '_', $Texto);
    $Texto = preg_replace('/[^A-Za-z0-9_\-]/u', '', $Texto);
    return $Texto !== '' ? $Texto : 'Reporte';
}

function FormatoCalificacion($Valor) {
    return $Valor !== null ? number_format((float)$Valor, 2) : '-';
}

$Modo = $GrupoId > 0 ? 'Grupo' : 'Asignacion';

/*
|--------------------------------------------------------------------------
| MODO ASIGNACIÓN: reporte de una sola materia/asignación
|--------------------------------------------------------------------------
*/
if ($Modo === 'Asignacion') {

    $Stmt = $Pdo->prepare("
        SELECT
            A.Id AS AsignacionId,
            A.MateriaNombre,
            A.MaestroId,
            G.Id AS GrupoId,
            G.Grado,
            G.Grupo,
            G.Turno,
            U.NombreCompleto AS Maestro
        FROM Asignaciones A
        JOIN Grupos G ON A.GrupoId = G.Id
        JOIN Usuarios U ON A.MaestroId = U.Id
        WHERE A.Id = ?
    ");
    $Stmt->execute([$AsignacionId]);
    $Info = $Stmt->fetch();

    if (!$Info) { die("Reporte No Disponible."); }

    if ($UserSession['Rol'] === 'maestro' && (int)$UserSession['Id'] !== (int)$Info['MaestroId']) {
        die("No Tienes Permiso.");
    }

    $StmtAlumnos = $Pdo->prepare("
        SELECT
            Al.NombreCompleto,
            C.Calificacion
        FROM Alumnos Al
        LEFT JOIN Calificaciones C
            ON C.AlumnoId = Al.Id
            AND C.AsignacionId = ?
        WHERE Al.GrupoId = ?
        ORDER BY Al.NombreCompleto ASC
    ");
    $StmtAlumnos->execute([$AsignacionId, $Info['GrupoId']]);
    $ListaAlumnos = $StmtAlumnos->fetchAll();

    $TituloArchivo = "Reporte_Calificaciones_" . NombreArchivoSeguro($Info['MateriaNombre']) . "_" . NombreArchivoSeguro($Info['Grado'] . $Info['Grupo']);

    if ($Tipo === 'Excel') {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("X-Content-Type-Options: nosniff");
        echo "\xEF\xBB\xBF";
        ?>
        <html><head><meta charset="utf-8"><style>
            body{font-family:Arial;} table{border-collapse:collapse;width:100%;}
            th{background:#7A0818;color:white;padding:10px;border:1px solid #ccc;}
            td{border:1px solid #ccc;padding:8px;} .Titulo{background:#7A0818;color:white;font-size:18px;font-weight:bold;text-align:center;padding:18px;}
            .SubTitulo{background:#A10D26;color:white;text-align:center;padding:10px;} .Info{background:#F8F9FA;font-weight:bold;width:180px;} .Centro{text-align:center;font-weight:bold;}
        </style></head><body>
        <table><tr><td colspan="3" class="Titulo">ESCUELA SECUNDARIA TÉCNICA 101</td></tr><tr><td colspan="3" class="SubTitulo">REPORTE OFICIAL DE CALIFICACIONES</td></tr></table><br>
        <table>
            <tr><td class="Info">Materia</td><td colspan="2"><?= htmlspecialchars($Info['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><td class="Info">Grupo</td><td colspan="2"><?= htmlspecialchars($Info['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Info['Grupo'], ENT_QUOTES, 'UTF-8') ?>"</td></tr>
            <tr><td class="Info">Turno</td><td colspan="2"><?= htmlspecialchars($Info['Turno'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><td class="Info">Docente</td><td colspan="2"><?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        </table><br>
        <table>
            <tr><th style="width:70px;">No.</th><th>Nombre Del Alumno</th><th style="width:150px;">Calificación</th></tr>
            <?php $Numero = 1; foreach($ListaAlumnos as $Al): ?>
                <tr><td align="center"><?= $Numero++ ?></td><td><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><td class="Centro"><?= FormatoCalificacion($Al['Calificacion']) ?></td></tr>
            <?php endforeach; ?>
        </table><br><br><br>
        <table style="width:100%;border:none;"><tr><td style="border:none;"></td><td style="border:none;text-align:center;width:300px;">___________________________<br><strong><?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></strong><br>Firma Del Docente</td><td style="border:none;"></td></tr></table>
        </body></html>
        <?php exit;
    }

    if ($Tipo === 'Pdf') {
        ?>
        <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= htmlspecialchars($TituloArchivo, ENT_QUOTES, 'UTF-8') ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>@page{size:letter;margin:1.5cm;} body{font-family:'Segoe UI',sans-serif;color:#333;font-size:12px;} .NoPrint{background:#F8F9FA;border:1px solid #DDD;} .HeaderReporte{border-bottom:4px solid #7A0818;padding-bottom:12px;margin-bottom:20px;} .HeaderReporte h2{color:#7A0818;font-weight:800;margin:0;} .HeaderReporte h5{color:#666;margin-top:5px;text-transform:uppercase;} .TableReporte{width:100%;border-collapse:collapse;} .TableReporte th{background:#7A0818;color:white;padding:10px;border:1px solid #CCC;text-transform:uppercase;font-size:11px;} .TableReporte td{border:1px solid #DDD;padding:8px;} .TableReporte tbody tr:nth-child(even){background:#F8F9FA;} .Firma{margin-top:60px;text-align:center;} .FirmaLinea{width:260px;margin:auto;border-top:1px solid #333;padding-top:5px;} @media print{.NoPrint{display:none;} .TableReporte th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} .TableReporte tbody tr:nth-child(even){background:#F8F9FA!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}</style>
        </head><body>
        <div class="NoPrint p-3 rounded mb-4 d-flex justify-content-between align-items-center"><div><i class="fa-solid fa-eye"></i> Vista Preliminar</div><button onclick="window.print()" class="btn btn-danger btn-sm"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button></div>
        <div class="HeaderReporte d-flex justify-content-between align-items-end"><div><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte Oficial De Calificaciones</h5></div><div class="text-end"><div><strong>Materia:</strong> <?= htmlspecialchars($Info['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></div><div><strong>Grupo:</strong> <?= htmlspecialchars($Info['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Info['Grupo'], ENT_QUOTES, 'UTF-8') ?>"</div><div><strong>Turno:</strong> <?= htmlspecialchars($Info['Turno'], ENT_QUOTES, 'UTF-8') ?></div><div><strong>Docente:</strong> <?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></div></div></div>
        <table class="TableReporte"><thead><tr><th style="width:8%;">No.</th><th>Nombre Del Alumno</th><th style="width:18%;">Calificación</th></tr></thead><tbody>
        <?php $Numero = 1; foreach($ListaAlumnos as $Al): ?>
            <tr><td align="center"><?= $Numero++ ?></td><td><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><td align="center"><strong><?= FormatoCalificacion($Al['Calificacion']) ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <div class="Firma"><div class="FirmaLinea"><strong><?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></strong><br>Firma Del Docente</div></div>
        <script>window.onload=function(){setTimeout(function(){window.print();},300);}</script>
        </body></html>
        <?php exit;
    }
}

/*
|--------------------------------------------------------------------------
| MODO GRUPO: reporte de todas las materias/asignaciones del grupo
|--------------------------------------------------------------------------
*/
$StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ?");
$StmtGrupo->execute([$GrupoId]);
$InfoGrupo = $StmtGrupo->fetch();
if (!$InfoGrupo) { die("Grupo No Disponible."); }

if ($UserSession['Rol'] === 'maestro') {
    $StmtPermiso = $Pdo->prepare("SELECT COUNT(*) FROM Asignaciones WHERE GrupoId = ? AND MaestroId = ?");
    $StmtPermiso->execute([$GrupoId, $UserSession['Id']]);
    if ((int)$StmtPermiso->fetchColumn() <= 0) { die("No Tienes Permiso."); }
}

$StmtAsignaciones = $Pdo->prepare("
    SELECT A.Id, A.MateriaNombre, U.NombreCompleto AS Maestro
    FROM Asignaciones A
    JOIN Usuarios U ON A.MaestroId = U.Id
    WHERE A.GrupoId = ?
    ORDER BY A.MateriaNombre ASC, U.NombreCompleto ASC
");
$StmtAsignaciones->execute([$GrupoId]);
$ListaAsignaciones = $StmtAsignaciones->fetchAll();

$StmtAlumnos = $Pdo->prepare("SELECT Id, NombreCompleto FROM Alumnos WHERE GrupoId = ? ORDER BY NombreCompleto ASC");
$StmtAlumnos->execute([$GrupoId]);
$ListaAlumnos = $StmtAlumnos->fetchAll();

$StmtCal = $Pdo->prepare("
    SELECT C.AlumnoId, C.AsignacionId, C.Calificacion
    FROM Calificaciones C
    JOIN Asignaciones A ON C.AsignacionId = A.Id
    WHERE A.GrupoId = ?
");
$StmtCal->execute([$GrupoId]);
$MapaCalificaciones = [];
foreach ($StmtCal->fetchAll() as $Cal) {
    $MapaCalificaciones[(int)$Cal['AlumnoId']][(int)$Cal['AsignacionId']] = $Cal['Calificacion'];
}

$TituloArchivo = "Reporte_Calificaciones_Grupo_" . NombreArchivoSeguro($InfoGrupo['Grado'] . $InfoGrupo['Grupo']);
$Colspan = 2 + max(1, count($ListaAsignaciones));

if ($Tipo === 'Excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-Content-Type-Options: nosniff");
    echo "\xEF\xBB\xBF";
    ?>
    <html><head><meta charset="utf-8"><style>
        body{font-family:Arial;} table{border-collapse:collapse;width:100%;} th{background:#7A0818;color:white;padding:10px;border:1px solid #ccc;} td{border:1px solid #ccc;padding:8px;} .Titulo{background:#7A0818;color:white;font-size:18px;font-weight:bold;text-align:center;padding:18px;} .SubTitulo{background:#A10D26;color:white;text-align:center;padding:10px;} .Info{background:#F8F9FA;font-weight:bold;width:180px;} .Centro{text-align:center;font-weight:bold;} .Materia{font-size:11px;}
    </style></head><body>
    <table><tr><td colspan="<?= $Colspan ?>" class="Titulo">ESCUELA SECUNDARIA TÉCNICA 101</td></tr><tr><td colspan="<?= $Colspan ?>" class="SubTitulo">REPORTE DE CALIFICACIONES POR GRUPO</td></tr></table><br>
    <table>
        <tr><td class="Info">Grupo</td><td colspan="<?= $Colspan - 1 ?>"><?= htmlspecialchars($InfoGrupo['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($InfoGrupo['Grupo'], ENT_QUOTES, 'UTF-8') ?>"</td></tr>
        <tr><td class="Info">Turno</td><td colspan="<?= $Colspan - 1 ?>"><?= htmlspecialchars($InfoGrupo['Turno'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    </table><br>
    <table>
        <tr><th style="width:60px;">No.</th><th>Alumno</th><?php foreach($ListaAsignaciones as $Asg): ?><th class="Materia"><?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($Asg['Maestro'], ENT_QUOTES, 'UTF-8') ?></small></th><?php endforeach; ?></tr>
        <?php $N=1; foreach($ListaAlumnos as $Al): ?>
            <tr><td class="Centro"><?= $N++ ?></td><td><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><?php foreach($ListaAsignaciones as $Asg): ?><td class="Centro"><?= FormatoCalificacion($MapaCalificaciones[(int)$Al['Id']][(int)$Asg['Id']] ?? null) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
    </table>
    </body></html>
    <?php exit;
}

if ($Tipo === 'Pdf') {
    ?>
    <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= htmlspecialchars($TituloArchivo, ENT_QUOTES, 'UTF-8') ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>@page{size:letter landscape;margin:1cm;} body{font-family:'Segoe UI',sans-serif;color:#333;font-size:11px;} .NoPrint{background:#F8F9FA;border:1px solid #DDD;} .Header{border-bottom:4px solid #7A0818;padding-bottom:12px;margin-bottom:15px;} .Header h2{color:#7A0818;font-weight:800;margin:0;} table{width:100%;border-collapse:collapse;} th{background:#7A0818;color:white;padding:8px;border:1px solid #CCC;text-transform:uppercase;font-size:10px;} td{border:1px solid #DDD;padding:6px;} tbody tr:nth-child(even){background:#F8F9FA;} .Centro{text-align:center;font-weight:bold;} @media print{.NoPrint{display:none;} th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} tbody tr:nth-child(even){background:#F8F9FA!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}</style></head><body>
    <div class="NoPrint p-3 rounded mb-4 d-flex justify-content-between align-items-center"><div>Vista Preliminar</div><button onclick="window.print()" class="btn btn-danger btn-sm">Imprimir / Guardar PDF</button></div>
    <div class="Header d-flex justify-content-between"><div><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte De Calificaciones Por Grupo</h5></div><div class="text-end"><strong>Grupo:</strong> <?= htmlspecialchars($InfoGrupo['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($InfoGrupo['Grupo'], ENT_QUOTES, 'UTF-8') ?>"<br><strong>Turno:</strong> <?= htmlspecialchars($InfoGrupo['Turno'], ENT_QUOTES, 'UTF-8') ?></div></div>
    <table><thead><tr><th style="width:40px;">No.</th><th>Alumno</th><?php foreach($ListaAsignaciones as $Asg): ?><th><?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($Asg['Maestro'], ENT_QUOTES, 'UTF-8') ?></small></th><?php endforeach; ?></tr></thead><tbody>
    <?php $N=1; foreach($ListaAlumnos as $Al): ?>
        <tr><td class="Centro"><?= $N++ ?></td><td><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><?php foreach($ListaAsignaciones as $Asg): ?><td class="Centro"><?= FormatoCalificacion($MapaCalificaciones[(int)$Al['Id']][(int)$Asg['Id']] ?? null) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody></table>
    <script>window.onload=function(){setTimeout(function(){window.print();},300);}</script>
    </body></html>
    <?php exit;
}

die("Tipo de exportación inválido.");