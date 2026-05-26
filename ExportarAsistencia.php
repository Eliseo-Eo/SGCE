<?php

/*
    Archivo: ExportarAsistencia.php
    Descripción: Genera reportes de asistencia por grupo o por asignación.
    Esta versión está optimizada para trabajar con muchos registros porque imprime
    las filas conforme las lee de la base de datos y no carga todo el historial en memoria.
    Además, agrupa visualmente las asistencias por fecha para que sea fácil imprimir varios días.
*/

require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) {
    die("Acceso Denegado.");
}

$AsignacionId = intval($_GET['AsignacionId'] ?? 0);
$GrupoId = intval($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';
$Rango = (($_GET['Rango'] ?? 'Todas') === 'Hoy') ? 'Hoy' : 'Todas';

if ($AsignacionId <= 0 && $GrupoId <= 0) {
    die("Parámetros inválidos. Debes enviar AsignacionId o GrupoId.");
}

function H($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

function NombreArchivoSeguroAsis($Texto) {
    $Texto = (string)$Texto;
    $Texto = str_replace(' ', '_', $Texto);
    $Texto = preg_replace('/[^A-Za-z0-9_\-]/u', '', $Texto);
    return $Texto !== '' ? $Texto : 'Reporte';
}

function TextoEstadoAsistencia($Estado) {
    switch ($Estado) {
        case 'A': return 'ASISTENCIA';
        case 'F': return 'FALTA';
        case 'R': return 'RETARDO';
        case 'J': return 'JUSTIFICANTE';
        default: return H($Estado);
    }
}

function ClaseEstadoAsistencia($Estado) {
    switch ($Estado) {
        case 'A': return 'EstadoA';
        case 'F': return 'EstadoF';
        case 'R': return 'EstadoR';
        case 'J': return 'EstadoJ';
        default: return '';
    }
}

function ImprimirFilaFecha($Fecha, $Columnas) {
    echo '<tr class="FilaFecha"><td colspan="' . (int)$Columnas . '"><i class="fa-solid fa-calendar-day"></i> FECHA: ' . H($Fecha) . '</td></tr>';
}

function ImprimirFilasAsistenciaStreaming($Stmt, $Columnas, $Modo) {
    $FechaActual = null;
    $Numero = 0;
    $Total = 0;

    while ($Row = $Stmt->fetch()) {
        $FechaLlave = $Row['FechaDia'] ?? 'SIN_FECHA';
        $FechaTexto = $Row['FechaTexto'] ?? $FechaLlave;

        if ($FechaActual !== $FechaLlave) {
            $FechaActual = $FechaLlave;
            $Numero = 1;
            ImprimirFilaFecha($FechaTexto, $Columnas);
        }

        $EstadoTexto = TextoEstadoAsistencia($Row['Estado'] ?? '');
        $EstadoClase = ClaseEstadoAsistencia($Row['Estado'] ?? '');

        if ($Modo === 'Asignacion') {
            echo '<tr>';
            echo '<td class="Centro">' . $Numero++ . '</td>';
            echo '<td>' . H($Row['NombreCompleto'] ?? '') . '</td>';
            echo '<td class="Centro"><span class="EstadoBadge ' . $EstadoClase . '">' . $EstadoTexto . '</span></td>';
            echo '<td class="Centro">' . H($FechaTexto) . '</td>';
            echo '</tr>';
        } else {
            echo '<tr>';
            echo '<td class="Centro">' . $Numero++ . '</td>';
            echo '<td>' . H($Row['MateriaNombre'] ?? '') . '</td>';
            echo '<td>' . H($Row['Maestro'] ?? '') . '</td>';
            echo '<td>' . H($Row['NombreCompleto'] ?? '') . '</td>';
            echo '<td class="Centro"><span class="EstadoBadge ' . $EstadoClase . '">' . $EstadoTexto . '</span></td>';
            echo '<td class="Centro">' . H($FechaTexto) . '</td>';
            echo '</tr>';
        }

        $Total++;

        if (($Total % 500) === 0) {
            flush();
        }
    }

    if ($Total === 0) {
        echo '<tr><td colspan="' . (int)$Columnas . '" class="Centro">SIN REGISTROS DE ASISTENCIA.</td></tr>';
    }
}

$Modo = $GrupoId > 0 ? 'Grupo' : 'Asignacion';
$FiltroFechaSql = ($Rango === 'Hoy') ? " AND Asis.FechaDia = CURDATE() " : "";

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
        LIMIT 1
    ");
    $Stmt->execute([$AsignacionId]);
    $Info = $Stmt->fetch();

    if (!$Info) {
        die("Reporte No Disponible.");
    }

    if ($UserSession['Rol'] === 'maestro' && (int)$UserSession['Id'] !== (int)$Info['MaestroId']) {
        die("No Tienes Permiso.");
    }

    $TituloArchivo = "Reporte_Asistencia_" . NombreArchivoSeguroAsis($Info['MateriaNombre']) . ($Rango === 'Hoy' ? "_HOY" : "_TODAS");
    $Columnas = 4;

    if ($Tipo === 'Excel') {
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("X-Content-Type-Options: nosniff");
        echo "\xEF\xBB\xBF";
    }

    $StmtAsistencia = $Pdo->prepare("
        SELECT
            Al.NombreCompleto,
            Asis.Estado,
            Asis.FechaDia,
            DATE_FORMAT(Asis.FechaDia, '%d/%m/%Y') AS FechaTexto
        FROM Asistencias Asis
        JOIN Alumnos Al ON Asis.AlumnoId = Al.Id
        WHERE Asis.AsignacionId = ?
        $FiltroFechaSql
        ORDER BY Asis.FechaDia DESC, Al.NombreCompleto ASC
    ");
    $StmtAsistencia->execute([$AsignacionId]);

    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title><?= H($TituloArchivo) ?></title>
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <?php if ($Tipo === 'Pdf'): ?>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <?php endif; ?>
        <style>
            @page{size:letter;margin:1.3cm;}
            body{font-family:Arial,sans-serif;color:#222;font-size:12px;}
            table{border-collapse:collapse;width:100%;margin-bottom:14px;}
            th{background:#7A0818;color:white;padding:10px;border:1px solid #CCC;text-transform:uppercase;font-size:11px;}
            td{border:1px solid #CCC;padding:8px;}
            .Titulo{background:#7A0818;color:white;font-size:18px;font-weight:bold;text-align:center;padding:18px;}
            .SubTitulo{background:#A10D26;color:white;text-align:center;padding:10px;}
            .Info{background:#F8F9FA;font-weight:bold;width:180px;}
            .FilaFecha td{background:#EEF2F7!important;color:#7A0818!important;font-size:15px;font-weight:bold;text-align:left;border:1px solid #7A0818;padding:12px;}
            .Centro{text-align:center;}
            .EstadoBadge{display:inline-block;border-radius:999px;padding:4px 10px;font-weight:bold;border:1px solid #CBD5E1;}
            .EstadoA{color:#15803D;border-color:#16A34A;background:#F0FDF4;}
            .EstadoF{color:#B91C1C;border-color:#DC2626;background:#FEF2F2;}
            .EstadoR{color:#B45309;border-color:#F59E0B;background:#FFFBEB;}
            .EstadoJ{color:#1D4ED8;border-color:#2563EB;background:#EFF6FF;}
            .Header{border-bottom:4px solid #7A0818;margin-bottom:22px;padding-bottom:12px;}
            .Header h2{color:#7A0818;font-weight:800;margin:0;}
            .Header h5{color:#555;text-transform:uppercase;margin-top:5px;}
            .InfoBox{background:#F8F9FA;border:1px solid #E5E7EB;border-radius:12px;padding:14px;margin-bottom:18px;}
            @media print{.NoPrint{display:none;} th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} .FilaFecha td{background:#EEF2F7!important;color:#7A0818!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}
        </style>
    </head>
    <body>
        <?php if ($Tipo === 'Pdf'): ?>
            <div class="NoPrint mb-4"><button onclick="window.print()" class="btn btn-danger rounded-pill fw-bold px-4"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button></div>
            <div class="Header">
                <h2>ESCUELA SECUNDARIA TÉCNICA 101</h2>
                <h5>Reporte Oficial De Asistencia Por Fecha <?= $Rango === 'Hoy' ? '(HOY)' : '(TODAS)' ?></h5>
            </div>
            <div class="InfoBox">
                <strong>Materia:</strong> <?= H($Info['MateriaNombre']) ?><br>
                <strong>Grupo:</strong> <?= H($Info['Grado']) ?> "<?= H($Info['Grupo']) ?>"<br>
                <strong>Turno:</strong> <?= H($Info['Turno']) ?><br>
                <strong>Docente:</strong> <?= H($Info['Maestro']) ?>
            </div>
        <?php else: ?>
            <table><tr><td colspan="4" class="Titulo">ESCUELA SECUNDARIA TÉCNICA 101</td></tr><tr><td colspan="4" class="SubTitulo">REPORTE DE ASISTENCIA POR FECHA <?= $Rango === 'Hoy' ? '(HOY)' : '(TODAS)' ?></td></tr></table>
            <table><tr><td class="Info">Materia</td><td><?= H($Info['MateriaNombre']) ?></td></tr><tr><td class="Info">Grupo</td><td><?= H($Info['Grado']) ?> "<?= H($Info['Grupo']) ?>"</td></tr><tr><td class="Info">Turno</td><td><?= H($Info['Turno']) ?></td></tr><tr><td class="Info">Docente</td><td><?= H($Info['Maestro']) ?></td></tr></table>
        <?php endif; ?>
        <table>
            <thead><tr><th>No.</th><th>Alumno</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody><?php ImprimirFilasAsistenciaStreaming($StmtAsistencia, $Columnas, 'Asignacion'); ?></tbody>
        </table>
        <?php if ($Tipo === 'Pdf'): ?><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script><?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

$StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ? LIMIT 1");
$StmtGrupo->execute([$GrupoId]);
$InfoGrupo = $StmtGrupo->fetch();

if (!$InfoGrupo) {
    die("Grupo No Disponible.");
}

if ($UserSession['Rol'] !== 'admin') {
    die("No Tienes Permiso.");
}

$TituloArchivo = "Reporte_Asistencia_Grupo_" . NombreArchivoSeguroAsis($InfoGrupo['Grado'] . $InfoGrupo['Grupo']) . ($Rango === 'Hoy' ? "_HOY" : "_TODAS");
$Columnas = 6;

if ($Tipo === 'Excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename={$TituloArchivo}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-Content-Type-Options: nosniff");
    echo "\xEF\xBB\xBF";
}

$StmtAsistencia = $Pdo->prepare("
    SELECT
        Al.NombreCompleto,
        Asn.MateriaNombre,
        U.NombreCompleto AS Maestro,
        Asis.Estado,
        Asis.FechaDia,
        DATE_FORMAT(Asis.FechaDia, '%d/%m/%Y') AS FechaTexto
    FROM Asignaciones Asn
    JOIN Asistencias Asis ON Asis.AsignacionId = Asn.Id
    JOIN Alumnos Al ON Asis.AlumnoId = Al.Id
    JOIN Usuarios U ON Asn.MaestroId = U.Id
    WHERE Asn.GrupoId = ?
    $FiltroFechaSql
    ORDER BY Asis.FechaDia DESC, Asn.MateriaNombre ASC, Al.NombreCompleto ASC
");
$StmtAsistencia->execute([$GrupoId]);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= H($TituloArchivo) ?></title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <?php if ($Tipo === 'Pdf'): ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <?php endif; ?>
    <style>
        @page{size:letter landscape;margin:1cm;}
        body{font-family:Arial,sans-serif;color:#222;font-size:11px;}
        table{border-collapse:collapse;width:100%;margin-bottom:14px;}
        th{background:#7A0818;color:white;padding:8px;border:1px solid #CCC;text-transform:uppercase;font-size:10px;}
        td{border:1px solid #CCC;padding:6px;}
        .Titulo{background:#7A0818;color:white;font-size:18px;font-weight:bold;text-align:center;padding:18px;}
        .SubTitulo{background:#A10D26;color:white;text-align:center;padding:10px;}
        .Info{background:#F8F9FA;font-weight:bold;width:180px;}
        .FilaFecha td{background:#EEF2F7!important;color:#7A0818!important;font-size:13px;font-weight:bold;text-align:left;border:1px solid #7A0818;padding:9px;}
        .Centro{text-align:center;}
        tbody tr:nth-child(even){background:#F8F9FA;}
        .EstadoBadge{display:inline-block;border-radius:999px;padding:3px 8px;font-weight:bold;border:1px solid #CBD5E1;}
        .EstadoA{color:#15803D;border-color:#16A34A;background:#F0FDF4;}
        .EstadoF{color:#B91C1C;border-color:#DC2626;background:#FEF2F2;}
        .EstadoR{color:#B45309;border-color:#F59E0B;background:#FFFBEB;}
        .EstadoJ{color:#1D4ED8;border-color:#2563EB;background:#EFF6FF;}
        .Header{border-bottom:4px solid #7A0818;margin-bottom:18px;padding-bottom:10px;}
        .Header h2{color:#7A0818;font-weight:800;margin:0;}
        .Header h5{color:#555;text-transform:uppercase;margin-top:5px;}
        @media print{.NoPrint{display:none;} th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} .FilaFecha td{background:#EEF2F7!important;color:#7A0818!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} tbody tr:nth-child(even){background:#F8F9FA!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}
    </style>
</head>
<body>
    <?php if ($Tipo === 'Pdf'): ?>
        <div class="NoPrint mb-4 p-3"><button onclick="window.print()" class="btn btn-danger rounded-pill fw-bold px-4"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button></div>
        <div class="Header d-flex justify-content-between align-items-end">
            <div><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte De Asistencia Por Grupo Y Fecha <?= $Rango === 'Hoy' ? '(HOY)' : '(TODAS)' ?></h5></div>
            <div class="text-end"><strong>Grupo:</strong> <?= H($InfoGrupo['Grado']) ?> "<?= H($InfoGrupo['Grupo']) ?>"<br><strong>Turno:</strong> <?= H($InfoGrupo['Turno']) ?></div>
        </div>
    <?php else: ?>
        <table><tr><td colspan="6" class="Titulo">ESCUELA SECUNDARIA TÉCNICA 101</td></tr><tr><td colspan="6" class="SubTitulo">REPORTE DE ASISTENCIA POR GRUPO Y FECHA <?= $Rango === 'Hoy' ? '(HOY)' : '(TODAS)' ?></td></tr></table>
        <table><tr><td class="Info">Grupo</td><td><?= H($InfoGrupo['Grado']) ?> "<?= H($InfoGrupo['Grupo']) ?>"</td></tr><tr><td class="Info">Turno</td><td><?= H($InfoGrupo['Turno']) ?></td></tr></table>
    <?php endif; ?>
    <table>
        <thead><tr><th>No.</th><th>Materia</th><th>Docente</th><th>Alumno</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody><?php ImprimirFilasAsistenciaStreaming($StmtAsistencia, $Columnas, 'Grupo'); ?></tbody>
    </table>
    <?php if ($Tipo === 'Pdf'): ?><script>window.onload=function(){setTimeout(function(){window.print();},300);};</script><?php endif; ?>
</body>
</html>
<?php
exit;
