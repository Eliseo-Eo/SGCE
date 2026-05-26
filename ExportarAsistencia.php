<?php
require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { die("Acceso Denegado."); }

$AsignacionId = intval($_GET['AsignacionId'] ?? 0);
$GrupoId = intval($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';
$Rango = (($_GET['Rango'] ?? 'Todas') === 'Hoy') ? 'Hoy' : 'Todas';

if ($AsignacionId <= 0 && $GrupoId <= 0) {
    die("Parámetros inválidos. Debes enviar AsignacionId o GrupoId.");
}

function NombreArchivoSeguroAsis($Texto) {
    $Texto = (string)$Texto;
    $Texto = str_replace(' ', '_', $Texto);
    $Texto = preg_replace('/[^A-Za-z0-9_\-]/u', '', $Texto);
    return $Texto !== '' ? $Texto : 'Reporte';
}

function TextoEstadoAsistencia($Estado) {
    switch($Estado) {
        case 'A': return 'Asistencia';
        case 'F': return 'Falta';
        case 'R': return 'Retardo';
        case 'J': return 'Justificante';
        default: return htmlspecialchars((string)$Estado, ENT_QUOTES, 'UTF-8');
    }
}

$Modo = $GrupoId > 0 ? 'Grupo' : 'Asignacion';
$FiltroFechaSql = ($Rango === 'Hoy') ? " AND DATE(Asis.Fecha) = CURDATE() " : "";

if ($Modo === 'Asignacion') {
    $Stmt = $Pdo->prepare("
        SELECT A.Id AS AsignacionId, A.MateriaNombre, A.MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno, U.NombreCompleto AS Maestro
        FROM Asignaciones A
        JOIN Grupos G ON A.GrupoId = G.Id
        JOIN Usuarios U ON A.MaestroId = U.Id
        WHERE A.Id = ?
    ");
    $Stmt->execute([$AsignacionId]);
    $Info = $Stmt->fetch();
    if (!$Info) { die("Reporte No Disponible."); }

    if ($UserSession['Rol'] === 'maestro' && (int)$UserSession['Id'] !== (int)$Info['MaestroId']) { die("No Tienes Permiso."); }

    $StmtAsistencia = $Pdo->prepare("
        SELECT Al.NombreCompleto, Asis.Estado, DATE_FORMAT(Asis.Fecha, '%d/%m/%Y') AS Fecha
        FROM Asistencias Asis
        JOIN Alumnos Al ON Asis.AlumnoId = Al.Id
        WHERE Asis.AsignacionId = ?
        $FiltroFechaSql
        ORDER BY Asis.Fecha DESC, Al.NombreCompleto ASC
    ");
    $StmtAsistencia->execute([$AsignacionId]);
    $Lista = $StmtAsistencia->fetchAll();
    $TituloArchivo = "Reporte_Asistencia_" . NombreArchivoSeguroAsis($Info['MateriaNombre']) . ($Rango === 'Hoy' ? "_HOY" : "_TODAS");

    if ($Tipo === 'Excel') {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo "\xEF\xBB\xBF";
        ?>
        <html><head><meta charset="utf-8"><style>body{font-family:Arial;} table{border-collapse:collapse;width:100%;} th{background:#7A0818;color:white;padding:10px;border:1px solid #ccc;} td{border:1px solid #ccc;padding:8px;} .Info{background:#F8F9FA;font-weight:bold;width:180px;}</style></head><body>
        <h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h3>Reporte De Asistencia (<?= $Rango === 'Hoy' ? 'HOY' : 'TODAS' ?>)</h3>
        <table><tr><td class="Info">Materia</td><td><?= htmlspecialchars($Info['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></td></tr><tr><td class="Info">Grupo</td><td><?= htmlspecialchars($Info['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Info['Grupo'], ENT_QUOTES, 'UTF-8') ?>"</td></tr><tr><td class="Info">Turno</td><td><?= htmlspecialchars($Info['Turno'], ENT_QUOTES, 'UTF-8') ?></td></tr><tr><td class="Info">Docente</td><td><?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></td></tr></table><br>
        <table><tr><th>No.</th><th>Alumno</th><th>Estado</th><th>Fecha</th></tr><?php $i=1; foreach($Lista as $Row): ?><tr><td><?= $i++ ?></td><td><?= htmlspecialchars($Row['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><td><?= TextoEstadoAsistencia($Row['Estado']) ?></td><td><?= htmlspecialchars($Row['Fecha'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></table>
        </body></html>
        <?php exit;
    }

    if ($Tipo === 'Pdf') {
        ?>
        <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= htmlspecialchars($TituloArchivo, ENT_QUOTES, 'UTF-8') ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{font-family:Arial;padding:30px;color:#333;} .Header{border-bottom:3px solid #7A0818;margin-bottom:25px;padding-bottom:10px;} .Header h2{color:#7A0818;font-weight:800;} .TableReporte{width:100%;border-collapse:collapse;} .TableReporte th{background:#7A0818;color:white;padding:10px;border:1px solid #ddd;} .TableReporte td{border:1px solid #ddd;padding:8px;} @media print{.NoPrint{display:none;} th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}</style></head><body>
        <div class="NoPrint mb-4"><button onclick="window.print()" class="btn btn-danger">Imprimir / Guardar PDF</button></div>
        <div class="Header"><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte Oficial De Asistencia (<?= $Rango === 'Hoy' ? 'HOY' : 'TODAS' ?>)</h5></div>
        <div class="mb-4"><strong>Materia:</strong> <?= htmlspecialchars($Info['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?><br><strong>Grupo:</strong> <?= htmlspecialchars($Info['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Info['Grupo'], ENT_QUOTES, 'UTF-8') ?>"<br><strong>Turno:</strong> <?= htmlspecialchars($Info['Turno'], ENT_QUOTES, 'UTF-8') ?><br><strong>Docente:</strong> <?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></div>
        <table class="TableReporte"><thead><tr><th>No.</th><th>Alumno</th><th>Estado</th><th>Fecha</th></tr></thead><tbody><?php $i=1; foreach($Lista as $Row): ?><tr><td><?= $i++ ?></td><td><?= htmlspecialchars($Row['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><td><?= TextoEstadoAsistencia($Row['Estado']) ?></td><td><?= htmlspecialchars($Row['Fecha'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table>
        <script>window.onload=function(){setTimeout(function(){window.print();},300);}</script></body></html>
        <?php exit;
    }
}

// MODO GRUPO
$StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ?");
$StmtGrupo->execute([$GrupoId]);
$InfoGrupo = $StmtGrupo->fetch();
if (!$InfoGrupo) { die("Grupo No Disponible."); }

if ($UserSession['Rol'] === 'maestro') {
    $StmtPermiso = $Pdo->prepare("SELECT COUNT(*) FROM Asignaciones WHERE GrupoId = ? AND MaestroId = ?");
    $StmtPermiso->execute([$GrupoId, $UserSession['Id']]);
    if ((int)$StmtPermiso->fetchColumn() <= 0) { die("No Tienes Permiso."); }
}

$StmtAsistencia = $Pdo->prepare("
    SELECT Al.NombreCompleto, Asn.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado, DATE_FORMAT(Asis.Fecha, '%d/%m/%Y') AS Fecha
    FROM Asistencias Asis
    JOIN Alumnos Al ON Asis.AlumnoId = Al.Id
    JOIN Asignaciones Asn ON Asis.AsignacionId = Asn.Id
    JOIN Usuarios U ON Asn.MaestroId = U.Id
    WHERE Asn.GrupoId = ?
    $FiltroFechaSql
    ORDER BY Asis.Fecha DESC, Asn.MateriaNombre ASC, Al.NombreCompleto ASC
");
$StmtAsistencia->execute([$GrupoId]);
$Lista = $StmtAsistencia->fetchAll();

$TituloArchivo = "Reporte_Asistencia_Grupo_" . NombreArchivoSeguroAsis($InfoGrupo['Grado'] . $InfoGrupo['Grupo']) . ($Rango === 'Hoy' ? "_HOY" : "_TODAS");

if ($Tipo === 'Excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "\xEF\xBB\xBF";
    ?>
    <html><head><meta charset="utf-8"><style>body{font-family:Arial;} table{border-collapse:collapse;width:100%;} th{background:#7A0818;color:white;padding:10px;border:1px solid #ccc;} td{border:1px solid #ccc;padding:8px;} .Info{background:#F8F9FA;font-weight:bold;width:180px;}</style></head><body>
    <h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h3>Reporte De Asistencia Por Grupo (<?= $Rango === 'Hoy' ? 'HOY' : 'TODAS' ?>)</h3>
    <table><tr><td class="Info">Grupo</td><td><?= htmlspecialchars($InfoGrupo['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($InfoGrupo['Grupo'], ENT_QUOTES, 'UTF-8') ?>"</td></tr><tr><td class="Info">Turno</td><td><?= htmlspecialchars($InfoGrupo['Turno'], ENT_QUOTES, 'UTF-8') ?></td></tr></table><br>
    <table><tr><th>No.</th><th>Fecha</th><th>Materia</th><th>Docente</th><th>Alumno</th><th>Estado</th></tr><?php $i=1; foreach($Lista as $Row): ?><tr><td><?= $i++ ?></td><td><?= htmlspecialchars($Row['Fecha'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($Row['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($Row['Maestro'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($Row['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><td><?= TextoEstadoAsistencia($Row['Estado']) ?></td></tr><?php endforeach; ?></table>
    </body></html>
    <?php exit;
}

if ($Tipo === 'Pdf') {
    ?>
    <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= htmlspecialchars($TituloArchivo, ENT_QUOTES, 'UTF-8') ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>@page{size:letter landscape;margin:1cm;} body{font-family:Arial;color:#333;font-size:11px;} .Header{border-bottom:3px solid #7A0818;margin-bottom:20px;padding-bottom:10px;} .Header h2{color:#7A0818;font-weight:800;} table{width:100%;border-collapse:collapse;} th{background:#7A0818;color:white;padding:8px;border:1px solid #ddd;} td{border:1px solid #ddd;padding:6px;} tbody tr:nth-child(even){background:#F8F9FA;} @media print{.NoPrint{display:none;} th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} tbody tr:nth-child(even){background:#F8F9FA!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}</style></head><body>
    <div class="NoPrint mb-4 p-3"><button onclick="window.print()" class="btn btn-danger">Imprimir / Guardar PDF</button></div>
    <div class="Header d-flex justify-content-between"><div><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte De Asistencia Por Grupo (<?= $Rango === 'Hoy' ? 'HOY' : 'TODAS' ?>)</h5></div><div class="text-end"><strong>Grupo:</strong> <?= htmlspecialchars($InfoGrupo['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($InfoGrupo['Grupo'], ENT_QUOTES, 'UTF-8') ?>"<br><strong>Turno:</strong> <?= htmlspecialchars($InfoGrupo['Turno'], ENT_QUOTES, 'UTF-8') ?></div></div>
    <table><thead><tr><th>No.</th><th>Fecha</th><th>Materia</th><th>Docente</th><th>Alumno</th><th>Estado</th></tr></thead><tbody><?php $i=1; foreach($Lista as $Row): ?><tr><td><?= $i++ ?></td><td><?= htmlspecialchars($Row['Fecha'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($Row['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($Row['Maestro'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($Row['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><td><?= TextoEstadoAsistencia($Row['Estado']) ?></td></tr><?php endforeach; ?></tbody></table>
    <script>window.onload=function(){setTimeout(function(){window.print();},300);}</script></body></html>
    <?php exit;
}

die("Tipo de exportación inválido.");