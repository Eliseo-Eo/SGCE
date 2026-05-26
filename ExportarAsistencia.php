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
$FechaInicio = trim((string)($_GET['FechaInicio'] ?? ''));
$FechaFin = trim((string)($_GET['FechaFin'] ?? ''));
$TieneRangoFechas = preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $FechaFin);

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
$ParametrosFecha = [];
if ($Rango === 'Hoy') {
    $FiltroFechaSql = " AND Asis.FechaDia = CURDATE() ";
} elseif ($TieneRangoFechas) {
    $FiltroFechaSql = " AND Asis.FechaDia BETWEEN ? AND ? ";
    $ParametrosFecha = [$FechaInicio, $FechaFin];
} else {
    $FiltroFechaSql = "";
}

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
        AND A.Activo = 1
        AND G.Activo = 1
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
        AND Al.Activo = 1
        $FiltroFechaSql
        ORDER BY Asis.FechaDia DESC, Al.NombreCompleto ASC
    ");
    $StmtAsistencia->execute(array_merge([$AsignacionId], $ParametrosFecha));

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
        <style>
/* ============================================================
   SGCE FIX7 - DISENO INTEGRADO POR ARCHIVO
   Se elimino sgce-fix.css. Este bloque queda dentro de cada PHP
   para evitar cache y conflictos entre archivos externos.
   ============================================================ */
:root{
    --SgceGuinda:#7A0818;
    --SgceGuinda2:#A10D26;
    --SgceGuindaHover:#4F050F;
    --SgceTexto:#1F2937;
    --SgceMuted:#6B7280;
    --SgceFondo:#EEF2F7;
    --SgceBorde:#E5E7EB;
    --SgceCard:#FFFFFF;
    --SgceAzul:#2563EB;
    --SgceAzulHover:#1D4ED8;
    --SgceRojo:#DC2626;
    --SgceRojoHover:#991B1B;
    --SgceVerde:#15803D;
    --SgceVerdeHover:#166534;
    --SgceNaranja:#C2410C;
    --SgceMorado:#6D28D9;
}
html{scroll-behavior:smooth;}
body{overflow-x:hidden;}
.card,.Card,.Panel,.TablaCard,.DashboardCard,.Contenedor,.ContainerCard{border-radius:22px !important;}
.form-control,.form-select{border-radius:14px !important;border:2px solid var(--SgceBorde) !important;min-height:44px;}
.form-control:focus,.form-select:focus{border-color:var(--SgceGuinda) !important;box-shadow:0 0 0 .18rem rgba(122,8,24,.14) !important;}
.btn:not(.btn-close):not(.navbar-toggler),.ActionBtn,.BotonAccion,.BtnExport,.BtnGuardar,.BtnBack,.ExportIcon,.BtnLogin,.MenuButton,.ModuleButton,.NavButton{min-height:42px;border-radius:999px !important;font-weight:800 !important;letter-spacing:.02em;display:inline-flex;align-items:center;justify-content:center;gap:.45rem;text-decoration:none !important;transition:transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease, border-color .18s ease !important;}
.btn:not(.btn-close):not(.navbar-toggler):hover,.ActionBtn:hover,.BotonAccion:hover,.BtnExport:hover,.BtnGuardar:hover,.BtnBack:hover,.ExportIcon:hover,.BtnLogin:hover,.MenuButton:hover,.ModuleButton:hover,.NavButton:hover{transform:translateY(-1px);box-shadow:0 12px 26px rgba(122,8,24,.20) !important;}
.btn-primary,.ActionPrimary,.BtnGuardar,.BtnCalificaciones,.BtnLogin,.ModuleButton,.MenuButton,.NavButton,.BotonAccion{background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;border:2px solid var(--SgceGuinda) !important;color:#FFFFFF !important;}
.btn-primary:hover,.ActionPrimary:hover,.BtnGuardar:hover,.BtnCalificaciones:hover,.BtnLogin:hover,.ModuleButton:hover,.MenuButton:hover,.NavButton:hover,.BotonAccion:hover{background:linear-gradient(135deg,var(--SgceGuindaHover),var(--SgceGuinda)) !important;border-color:var(--SgceGuindaHover) !important;color:#FFFFFF !important;}
.ActionBtn.ActionEdit,.btn-outline-primary.ActionBtn,.btn-outline-primary:not(.SgceBtnInicio):not(.ReporteBtn){background:linear-gradient(135deg,var(--SgceAzul),#3B82F6) !important;border-color:var(--SgceAzul) !important;color:#FFFFFF !important;}
.ActionBtn.ActionEdit:hover,.btn-outline-primary.ActionBtn:hover,.btn-outline-primary:not(.SgceBtnInicio):not(.ReporteBtn):hover{background:linear-gradient(135deg,var(--SgceAzulHover),var(--SgceAzul)) !important;border-color:var(--SgceAzulHover) !important;color:#FFFFFF !important;}
.ActionBtn.ActionDelete,.btn-outline-danger.ActionBtn,.btn-outline-danger:not(.SgceBtnInicio):not(.ReporteBtn),.btn-danger{background:linear-gradient(135deg,var(--SgceRojo),#EF4444) !important;border-color:var(--SgceRojo) !important;color:#FFFFFF !important;}
.ActionBtn.ActionDelete:hover,.btn-outline-danger.ActionBtn:hover,.btn-outline-danger:not(.SgceBtnInicio):not(.ReporteBtn):hover,.btn-danger:hover{background:linear-gradient(135deg,var(--SgceRojoHover),var(--SgceRojo)) !important;border-color:var(--SgceRojoHover) !important;color:#FFFFFF !important;}
.ExportIcon.ExportExcel,.BtnExport.ExportCalifExcel,.BtnExport.ExportAsisExcel,.btn-success{background:linear-gradient(135deg,var(--SgceVerde),#22C55E) !important;border-color:var(--SgceVerde) !important;color:#FFFFFF !important;}
.ExportIcon.ExportExcel:hover,.BtnExport.ExportCalifExcel:hover,.BtnExport.ExportAsisExcel:hover,.btn-success:hover{background:linear-gradient(135deg,var(--SgceVerdeHover),var(--SgceVerde)) !important;border-color:var(--SgceVerdeHover) !important;color:#FFFFFF !important;}
.ExportIcon.ExportPdf,.BtnExport.ExportCalifPdf,.BtnExport.ExportAsisPdf{background:linear-gradient(135deg,#B91C1C,#EF4444) !important;border-color:#B91C1C !important;color:#FFFFFF !important;}
.ExportIcon.ExportHoy{background:linear-gradient(135deg,var(--SgceNaranja),#F97316) !important;border-color:var(--SgceNaranja) !important;color:#FFFFFF !important;}
.ExportIcon.ExportTodas{background:linear-gradient(135deg,var(--SgceMorado),#8B5CF6) !important;border-color:var(--SgceMorado) !important;color:#FFFFFF !important;}
.SgceBtnInicio,a.SgceBtnInicio,button.SgceBtnInicio,.Top .SgceBtnInicio,.navbar .SgceBtnInicio,.BtnBack.SgceBtnInicio,.ActionBtn.SgceBtnInicio,.btn-outline-light.SgceBtnInicio,.btn-light.SgceBtnInicio,.BtnGuinda.SgceBtnInicio{background:#FFFFFF !important;color:var(--SgceGuinda) !important;border:2px solid rgba(255,255,255,.92) !important;border-radius:999px !important;box-shadow:0 8px 18px rgba(0,0,0,.10) !important;text-decoration:none !important;}
.SgceBtnInicio:hover,a.SgceBtnInicio:hover,button.SgceBtnInicio:hover,.Top .SgceBtnInicio:hover,.navbar .SgceBtnInicio:hover,.BtnBack.SgceBtnInicio:hover,.ActionBtn.SgceBtnInicio:hover,.btn-outline-light.SgceBtnInicio:hover,.btn-light.SgceBtnInicio:hover,.BtnGuinda.SgceBtnInicio:hover{background:#FFFFFF !important;color:var(--SgceGuinda) !important;border-color:#FFFFFF !important;transform:translateY(-1px) !important;box-shadow:0 10px 22px rgba(0,0,0,.14) !important;}
.SgceBtnInicio i,.SgceBtnInicio:hover i{color:var(--SgceGuinda) !important;}
a[href*="Logout.php"],.BtnLogout,a[href*="Logout.php"]:hover,.BtnLogout:hover{background:#FFFFFF !important;color:var(--SgceGuinda) !important;border:2px solid rgba(255,255,255,.92) !important;box-shadow:0 8px 18px rgba(0,0,0,.12) !important;}
a[href*="Logout.php"] i,a[href*="Logout.php"]:hover i,.BtnLogout i,.BtnLogout:hover i{color:var(--SgceGuinda) !important;}
.ReporteBtn,button.ReporteBtn,.card .ReporteBtn,.Card .ReporteBtn,form .ReporteBtn,.btn.ReporteBtn{background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;color:#FFFFFF !important;border:2px solid var(--SgceGuinda) !important;border-radius:999px !important;min-height:46px !important;font-weight:800 !important;letter-spacing:.3px !important;box-shadow:0 10px 22px rgba(122,8,24,.18) !important;text-decoration:none !important;}
.ReporteBtn:hover,button.ReporteBtn:hover,.card .ReporteBtn:hover,.Card .ReporteBtn:hover,form .ReporteBtn:hover,.btn.ReporteBtn:hover{background:linear-gradient(135deg,var(--SgceGuindaHover),var(--SgceGuinda)) !important;color:#FFFFFF !important;border-color:var(--SgceGuindaHover) !important;transform:translateY(-2px) !important;box-shadow:0 14px 30px rgba(122,8,24,.28) !important;}
.ReporteBtn i,.ReporteBtn:hover i,button.ReporteBtn i,button.ReporteBtn:hover i{color:#FFFFFF !important;}
.table td,.table th{vertical-align:middle !important;}
.modal-dialog{display:flex;align-items:center;min-height:calc(100vh - 1rem);}
.modal-content{border-radius:24px !important;border:0 !important;box-shadow:0 25px 70px rgba(0,0,0,.25) !important;}
@media (max-width:768px){.btn:not(.btn-close):not(.navbar-toggler),.ActionBtn,.BotonAccion,.BtnExport,.ReporteBtn{width:100%;}.table-responsive{border-radius:18px;}}
</style>

</head>
    <body>
        <?php if ($Tipo === 'Pdf'): ?>
            <div class="NoPrint mb-4"><button onclick="window.print()" class="btn btn-danger rounded-pill fw-bold px-4"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button></div>
            <div class="Header">
                <h2>ESCUELA SECUNDARIA TÉCNICA 101</h2>
                <h5>Reporte Oficial De Asistencia Por Fecha <?= $Rango === 'Hoy' ? '(HOY)' : ($TieneRangoFechas ? '(' . H($FechaInicio) . ' A ' . H($FechaFin) . ')' : '(TODAS)') ?></h5>
            </div>
            <div class="InfoBox">
                <strong>Materia:</strong> <?= H($Info['MateriaNombre']) ?><br>
                <strong>Grupo:</strong> <?= H($Info['Grado']) ?> "<?= H($Info['Grupo']) ?>"<br>
                <strong>Turno:</strong> <?= H($Info['Turno']) ?><br>
                <strong>Docente:</strong> <?= H($Info['Maestro']) ?>
            </div>
        <?php else: ?>
            <table><tr><td colspan="4" class="Titulo">ESCUELA SECUNDARIA TÉCNICA 101</td></tr><tr><td colspan="4" class="SubTitulo">REPORTE DE ASISTENCIA POR FECHA <?= $Rango === 'Hoy' ? '(HOY)' : ($TieneRangoFechas ? '(' . H($FechaInicio) . ' A ' . H($FechaFin) . ')' : '(TODAS)') ?></td></tr></table>
            <table><tr><td class="Info">Materia</td><td><?= H($Info['MateriaNombre']) ?></td></tr><tr><td class="Info">Grupo</td><td><?= H($Info['Grado']) ?> "<?= H($Info['Grupo']) ?>"</td></tr><tr><td class="Info">Turno</td><td><?= H($Info['Turno']) ?></td></tr><tr><td class="Info">Docente</td><td><?= H($Info['Maestro']) ?></td></tr></table>
        <?php endif; ?>
        <table>
            <thead><tr><th>No.</th><th>Alumno</th><th>Estado</th><th>Fecha</th></tr></thead>
            <tbody><?php ImprimirFilasAsistenciaStreaming($StmtAsistencia, $Columnas, 'Asignacion'); ?></tbody>
        </table>
        <?php if ($Tipo === 'Pdf'): ?><script>window.onload=function(){setTimeout(function(){window.focus(); window.print();},300);};</script><?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

$StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ? AND Activo = 1 LIMIT 1");
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
    AND Asn.Activo = 1
    AND Al.Activo = 1
    $FiltroFechaSql
    ORDER BY Asis.FechaDia DESC, Asn.MateriaNombre ASC, Al.NombreCompleto ASC
");
$StmtAsistencia->execute(array_merge([$GrupoId], $ParametrosFecha));

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
    

        /* ==========================================================
           AJUSTE FINAL: BOTONES RELLENOS Y ESTILO INSTITUCIONAL
           ----------------------------------------------------------
           En esta versión dejé los botones principales rellenos, sin
           fondo blanco. La intención es que el sistema se vea más firme,
           más escolar e institucional usando el color guinda/tinto de la
           Secundaria Técnica 101. En hover solo oscurecen un poco para
           que se note claramente el paso del mouse.
           ========================================================== */
        :root{
            --Tinto101:#7A0818;
            --Tinto101Claro:#A10D26;
            --Tinto101Hover:#4F050F;
            --AzulAccion:#2563EB;
            --AzulAccionHover:#1D4ED8;
            --RojoAccion:#DC2626;
            --RojoAccionHover:#991B1B;
            --VerdeExcel:#15803D;
            --VerdeExcelHover:#166534;
            --RojoPdf:#B91C1C;
            --RojoPdfHover:#7F1D1D;
            --NaranjaHoy:#C2410C;
            --NaranjaHoyHover:#9A3412;
            --MoradoTodas:#6D28D9;
            --MoradoTodasHover:#4C1D95;
        }

        .nav-tabs .nav-link,
        .NavButton,
        .MenuButton,
        .ModuleButton,
        .btn:not(.btn-close),
        .BotonAccion,
        .BtnExport,
        .BtnGuardar,
        .BtnBack,
        .BtnLogin,
        .ActionBtn{
            background:linear-gradient(135deg,var(--Tinto101),var(--Tinto101Claro)) !important;
            color:#FFFFFF !important;
            border:2px solid var(--Tinto101) !important;
            box-shadow:0 10px 22px rgba(122,8,24,.18) !important;
            text-decoration:none !important;
        }

        .nav-tabs .nav-link i,
        .NavButton i,
        .MenuButton i,
        .ModuleButton i,
        .btn:not(.btn-close) i,
        .BotonAccion i,
        .BtnExport i,
        .BtnGuardar i,
        .BtnBack i,
        .BtnLogin i,
        .ActionBtn i,
        .ActionBtn span,
        .BtnExport span,
        .btn:not(.btn-close) span{
            color:inherit !important;
        }

        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link.active,
        .NavButton:hover,
        .MenuButton:hover,
        .ModuleButton:hover,
        .btn:not(.btn-close):hover,
        .BotonAccion:hover,
        .BtnExport:hover,
        .BtnGuardar:hover,
        .BtnBack:hover,
        .BtnLogin:hover,
        .ActionBtn:hover{
            background:linear-gradient(135deg,var(--Tinto101Hover),var(--Tinto101)) !important;
            color:#FFFFFF !important;
            border-color:var(--Tinto101Hover) !important;
            transform:translateY(-2px) !important;
            box-shadow:0 14px 30px rgba(122,8,24,.28) !important;
        }

        .ModulosRecomendados .ActionBtn{
            background:linear-gradient(135deg,var(--Tinto101),var(--Tinto101Claro)) !important;
            color:#FFFFFF !important;
            border-color:var(--Tinto101) !important;
        }

        .ModulosRecomendados .ActionBtn:hover{
            background:linear-gradient(135deg,var(--Tinto101Hover),var(--Tinto101)) !important;
            color:#FFFFFF !important;
            border-color:var(--Tinto101Hover) !important;
        }

        /* Acciones de tablas: quedan rellenas desde el inicio. */
        .ActionBtn.ActionEdit,
        .btn-outline-primary.ActionBtn,
        .btn-outline-primary{
            background:linear-gradient(135deg,var(--AzulAccion),#3B82F6) !important;
            border-color:var(--AzulAccion) !important;
            color:#FFFFFF !important;
        }

        .ActionBtn.ActionEdit:hover,
        .btn-outline-primary.ActionBtn:hover,
        .btn-outline-primary:hover{
            background:linear-gradient(135deg,var(--AzulAccionHover),var(--AzulAccion)) !important;
            border-color:var(--AzulAccionHover) !important;
            color:#FFFFFF !important;
        }

        .ActionBtn.ActionDelete,
        .btn-outline-danger.ActionBtn,
        .btn-outline-danger{
            background:linear-gradient(135deg,var(--RojoAccion),#EF4444) !important;
            border-color:var(--RojoAccion) !important;
            color:#FFFFFF !important;
        }

        .ActionBtn.ActionDelete:hover,
        .btn-outline-danger.ActionBtn:hover,
        .btn-outline-danger:hover{
            background:linear-gradient(135deg,var(--RojoAccionHover),var(--RojoAccion)) !important;
            border-color:var(--RojoAccionHover) !important;
            color:#FFFFFF !important;
        }

        /* Exportaciones: se conservan colores, pero ahora rellenos. */
        .ExportIcon{
            color:#FFFFFF !important;
            border-width:2px !important;
            box-shadow:0 8px 20px rgba(15,23,42,.12) !important;
        }

        .ExportIcon i,
        .ExportIcon span{
            color:inherit !important;
        }

        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas){
            background:linear-gradient(135deg,var(--VerdeExcel),#22C55E) !important;
            border-color:var(--VerdeExcel) !important;
        }

        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas):hover{
            background:linear-gradient(135deg,var(--VerdeExcelHover),var(--VerdeExcel)) !important;
            border-color:var(--VerdeExcelHover) !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas){
            background:linear-gradient(135deg,var(--RojoPdf),#EF4444) !important;
            border-color:var(--RojoPdf) !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas):hover{
            background:linear-gradient(135deg,var(--RojoPdfHover),var(--RojoPdf)) !important;
            border-color:var(--RojoPdfHover) !important;
        }

        .ExportIcon.ExportHoy{
            background:linear-gradient(135deg,var(--NaranjaHoy),#F97316) !important;
            border-color:var(--NaranjaHoy) !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportHoy:hover{
            background:linear-gradient(135deg,var(--NaranjaHoyHover),var(--NaranjaHoy)) !important;
            border-color:var(--NaranjaHoyHover) !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportTodas{
            background:linear-gradient(135deg,var(--MoradoTodas),#8B5CF6) !important;
            border-color:var(--MoradoTodas) !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportTodas:hover{
            background:linear-gradient(135deg,var(--MoradoTodasHover),var(--MoradoTodas)) !important;
            border-color:var(--MoradoTodasHover) !important;
            color:#FFFFFF !important;
        }

        /* Botones de exportación del portal docente, también rellenos. */
        .BtnExport.ExportCalifExcel,
        .BtnExport.ExportAsisExcel{
            background:linear-gradient(135deg,var(--VerdeExcel),#22C55E) !important;
            border-color:var(--VerdeExcel) !important;
            color:#FFFFFF !important;
        }

        .BtnExport.ExportCalifExcel:hover,
        .BtnExport.ExportAsisExcel:hover{
            background:linear-gradient(135deg,var(--VerdeExcelHover),var(--VerdeExcel)) !important;
            border-color:var(--VerdeExcelHover) !important;
        }

        .BtnExport.ExportCalifPdf,
        .BtnExport.ExportAsisPdf{
            background:linear-gradient(135deg,var(--RojoPdf),#EF4444) !important;
            border-color:var(--RojoPdf) !important;
            color:#FFFFFF !important;
        }

        .BtnExport.ExportCalifPdf:hover,
        .BtnExport.ExportAsisPdf:hover{
            background:linear-gradient(135deg,var(--RojoPdfHover),var(--RojoPdf)) !important;
            border-color:var(--RojoPdfHover) !important;
        }

        /* En asignaciones el grupo se muestra como texto normal, sin rectángulo. */
        .GrupoTextoSimple{
            display:inline-flex !important;
            align-items:center !important;
            justify-content:center !important;
            gap:6px !important;
            background:transparent !important;
            border:0 !important;
            box-shadow:none !important;
            padding:0 !important;
            margin:0 !important;
            border-radius:0 !important;
            color:#111827 !important;
            font-weight:800 !important;
            white-space:nowrap !important;
        }

        .GrupoTextoSimple i{
            color:#111827 !important;
        }

    </style>
</head>
<body>
    <?php if ($Tipo === 'Pdf'): ?>
        <div class="NoPrint mb-4 p-3"><button onclick="window.print()" class="btn btn-danger rounded-pill fw-bold px-4"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button></div>
        <div class="Header d-flex justify-content-between align-items-end">
            <div><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte De Asistencia Por Grupo Y Fecha <?= $Rango === 'Hoy' ? '(HOY)' : ($TieneRangoFechas ? '(' . H($FechaInicio) . ' A ' . H($FechaFin) . ')' : '(TODAS)') ?></h5></div>
            <div class="text-end"><strong>Grupo:</strong> <?= H($InfoGrupo['Grado']) ?> "<?= H($InfoGrupo['Grupo']) ?>"<br><strong>Turno:</strong> <?= H($InfoGrupo['Turno']) ?></div>
        </div>
    <?php else: ?>
        <table><tr><td colspan="6" class="Titulo">ESCUELA SECUNDARIA TÉCNICA 101</td></tr><tr><td colspan="6" class="SubTitulo">REPORTE DE ASISTENCIA POR GRUPO Y FECHA <?= $Rango === 'Hoy' ? '(HOY)' : ($TieneRangoFechas ? '(' . H($FechaInicio) . ' A ' . H($FechaFin) . ')' : '(TODAS)') ?></td></tr></table>
        <table><tr><td class="Info">Grupo</td><td><?= H($InfoGrupo['Grado']) ?> "<?= H($InfoGrupo['Grupo']) ?>"</td></tr><tr><td class="Info">Turno</td><td><?= H($InfoGrupo['Turno']) ?></td></tr></table>
    <?php endif; ?>
    <table>
        <thead><tr><th>No.</th><th>Materia</th><th>Docente</th><th>Alumno</th><th>Estado</th><th>Fecha</th></tr></thead>
        <tbody><?php ImprimirFilasAsistenciaStreaming($StmtAsistencia, $Columnas, 'Grupo'); ?></tbody>
    </table>
    <?php if ($Tipo === 'Pdf'): ?><script>window.onload=function(){setTimeout(function(){window.focus(); window.print();},300);};</script><?php endif; ?>
</body>
</html>
<?php
exit;
