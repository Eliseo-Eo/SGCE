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
        

/* ==========================================================
   FIX8 - BOTONES DE REGRESO / CERRAR SESION HOMOLOGADOS
   Estado normal: blanco con texto tinto.
   Hover: relleno tinto con texto blanco.
   ========================================================== */
.SgceBtnInicio,
a.SgceBtnInicio,
button.SgceBtnInicio,
.BtnBack.SgceBtnInicio,
.ActionBtn.SgceBtnInicio,
.btn.SgceBtnInicio,
.btn-outline-light.SgceBtnInicio,
.btn-light.SgceBtnInicio,
.BtnGuinda.SgceBtnInicio,
.Top .SgceBtnInicio,
.navbar .SgceBtnInicio,
.NavbarMaestro .SgceBtnInicio,
.navbar-custom .SgceBtnInicio{
    background:#FFFFFF !important;
    color:#7A0818 !important;
    border:2px solid #FFFFFF !important;
    border-radius:999px !important;
    box-shadow:0 8px 18px rgba(0,0,0,.12) !important;
    text-decoration:none !important;
}
.SgceBtnInicio i,
a.SgceBtnInicio i,
button.SgceBtnInicio i,
.btn.SgceBtnInicio i{
    color:#7A0818 !important;
}
.SgceBtnInicio:hover,
a.SgceBtnInicio:hover,
button.SgceBtnInicio:hover,
.BtnBack.SgceBtnInicio:hover,
.ActionBtn.SgceBtnInicio:hover,
.btn.SgceBtnInicio:hover,
.btn-outline-light.SgceBtnInicio:hover,
.btn-light.SgceBtnInicio:hover,
.BtnGuinda.SgceBtnInicio:hover,
.Top .SgceBtnInicio:hover,
.navbar .SgceBtnInicio:hover,
.NavbarMaestro .SgceBtnInicio:hover,
.navbar-custom .SgceBtnInicio:hover{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border-color:#7A0818 !important;
    transform:translateY(-1px) !important;
    box-shadow:0 12px 26px rgba(122,8,24,.28) !important;
}
.SgceBtnInicio:hover i,
a.SgceBtnInicio:hover i,
button.SgceBtnInicio:hover i,
.btn.SgceBtnInicio:hover i{
    color:#FFFFFF !important;
}

a[href*="Logout.php"],
.BtnLogout,
.NavbarMaestro a[href="Logout.php"],
.navbar-custom a[href="Logout.php"],
.navbar a[href="Logout.php"]{
    background:#FFFFFF !important;
    color:#7A0818 !important;
    border:2px solid #FFFFFF !important;
    border-radius:999px !important;
    box-shadow:0 8px 18px rgba(0,0,0,.12) !important;
    text-decoration:none !important;
}
a[href*="Logout.php"] i,
.BtnLogout i,
.NavbarMaestro a[href="Logout.php"] i,
.navbar-custom a[href="Logout.php"] i,
.navbar a[href="Logout.php"] i,
a[href*="Logout.php"] span{
    color:#7A0818 !important;
}
a[href*="Logout.php"]:hover,
.BtnLogout:hover,
.NavbarMaestro a[href="Logout.php"]:hover,
.navbar-custom a[href="Logout.php"]:hover,
.navbar a[href="Logout.php"]:hover{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border-color:#7A0818 !important;
    transform:translateY(-1px) !important;
    box-shadow:0 12px 26px rgba(122,8,24,.28) !important;
}
a[href*="Logout.php"]:hover i,
.BtnLogout:hover i,
.NavbarMaestro a[href="Logout.php"]:hover i,
.navbar-custom a[href="Logout.php"]:hover i,
.navbar a[href="Logout.php"]:hover i,
a[href*="Logout.php"]:hover span{
    color:#FFFFFF !important;
}

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


/* ==========================================================
   FIX8 - BOTONES DE REGRESO / CERRAR SESION HOMOLOGADOS
   Estado normal: blanco con texto tinto.
   Hover: relleno tinto con texto blanco.
   ========================================================== */
.SgceBtnInicio,
a.SgceBtnInicio,
button.SgceBtnInicio,
.BtnBack.SgceBtnInicio,
.ActionBtn.SgceBtnInicio,
.btn.SgceBtnInicio,
.btn-outline-light.SgceBtnInicio,
.btn-light.SgceBtnInicio,
.BtnGuinda.SgceBtnInicio,
.Top .SgceBtnInicio,
.navbar .SgceBtnInicio,
.NavbarMaestro .SgceBtnInicio,
.navbar-custom .SgceBtnInicio{
    background:#FFFFFF !important;
    color:#7A0818 !important;
    border:2px solid #FFFFFF !important;
    border-radius:999px !important;
    box-shadow:0 8px 18px rgba(0,0,0,.12) !important;
    text-decoration:none !important;
}
.SgceBtnInicio i,
a.SgceBtnInicio i,
button.SgceBtnInicio i,
.btn.SgceBtnInicio i{
    color:#7A0818 !important;
}
.SgceBtnInicio:hover,
a.SgceBtnInicio:hover,
button.SgceBtnInicio:hover,
.BtnBack.SgceBtnInicio:hover,
.ActionBtn.SgceBtnInicio:hover,
.btn.SgceBtnInicio:hover,
.btn-outline-light.SgceBtnInicio:hover,
.btn-light.SgceBtnInicio:hover,
.BtnGuinda.SgceBtnInicio:hover,
.Top .SgceBtnInicio:hover,
.navbar .SgceBtnInicio:hover,
.NavbarMaestro .SgceBtnInicio:hover,
.navbar-custom .SgceBtnInicio:hover{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border-color:#7A0818 !important;
    transform:translateY(-1px) !important;
    box-shadow:0 12px 26px rgba(122,8,24,.28) !important;
}
.SgceBtnInicio:hover i,
a.SgceBtnInicio:hover i,
button.SgceBtnInicio:hover i,
.btn.SgceBtnInicio:hover i{
    color:#FFFFFF !important;
}

a[href*="Logout.php"],
.BtnLogout,
.NavbarMaestro a[href="Logout.php"],
.navbar-custom a[href="Logout.php"],
.navbar a[href="Logout.php"]{
    background:#FFFFFF !important;
    color:#7A0818 !important;
    border:2px solid #FFFFFF !important;
    border-radius:999px !important;
    box-shadow:0 8px 18px rgba(0,0,0,.12) !important;
    text-decoration:none !important;
}
a[href*="Logout.php"] i,
.BtnLogout i,
.NavbarMaestro a[href="Logout.php"] i,
.navbar-custom a[href="Logout.php"] i,
.navbar a[href="Logout.php"] i,
a[href*="Logout.php"] span{
    color:#7A0818 !important;
}
a[href*="Logout.php"]:hover,
.BtnLogout:hover,
.NavbarMaestro a[href="Logout.php"]:hover,
.navbar-custom a[href="Logout.php"]:hover,
.navbar a[href="Logout.php"]:hover{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border-color:#7A0818 !important;
    transform:translateY(-1px) !important;
    box-shadow:0 12px 26px rgba(122,8,24,.28) !important;
}
a[href*="Logout.php"]:hover i,
.BtnLogout:hover i,
.NavbarMaestro a[href="Logout.php"]:hover i,
.navbar-custom a[href="Logout.php"]:hover i,
.navbar a[href="Logout.php"]:hover i,
a[href*="Logout.php"]:hover span{
    color:#FFFFFF !important;
}

</style>



<!-- SGCE FIX10: Botones de regreso/cerrar sesión con borde tinto fuerte y estilo homologado -->
<style>
:root{
    --SgceFixTinto:#7A0818;
    --SgceFixTinto2:#A10D26;
    --SgceFixTintoOscuro:#3B030A;
    --SgceFixAnim:cubic-bezier(.22,.61,.36,1);
}
a.SgceBtnInicio,
button.SgceBtnInicio,
.btn.SgceBtnInicio,
.BtnBack.SgceBtnInicio,
.ActionBtn.SgceBtnInicio,
.btn-light.SgceBtnInicio,
.btn-outline-light.SgceBtnInicio,
.Top .SgceBtnInicio,
.TopHeader .SgceBtnInicio,
.navbar .SgceBtnInicio,
.navbar-custom .SgceBtnInicio,
.NavbarMaestro .SgceBtnInicio,
a[href="Logout.php"],
.navbar a[href="Logout.php"],
.navbar-custom a[href="Logout.php"],
.NavbarMaestro a[href="Logout.php"],
.BtnLogout{
    background:#FFFFFF !important;
    color:var(--SgceFixTinto) !important;
    border:3px solid var(--SgceFixTinto) !important;
    border-radius:999px !important;
    min-height:42px !important;
    padding:10px 22px !important;
    font-weight:900 !important;
    letter-spacing:.02em !important;
    text-decoration:none !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:.45rem !important;
    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.75),
        0 8px 18px rgba(122,8,24,.20) !important;
    transition:transform .22s var(--SgceFixAnim), box-shadow .22s var(--SgceFixAnim), background .22s var(--SgceFixAnim), color .22s var(--SgceFixAnim), border-color .22s var(--SgceFixAnim) !important;
}
a.SgceBtnInicio i,
button.SgceBtnInicio i,
.btn.SgceBtnInicio i,
a[href="Logout.php"] i,
a[href="Logout.php"] span,
.BtnLogout i,
.BtnLogout span{
    color:inherit !important;
}
a.SgceBtnInicio:hover,
button.SgceBtnInicio:hover,
.btn.SgceBtnInicio:hover,
.BtnBack.SgceBtnInicio:hover,
.ActionBtn.SgceBtnInicio:hover,
.btn-light.SgceBtnInicio:hover,
.btn-outline-light.SgceBtnInicio:hover,
.Top .SgceBtnInicio:hover,
.TopHeader .SgceBtnInicio:hover,
.navbar .SgceBtnInicio:hover,
.navbar-custom .SgceBtnInicio:hover,
.NavbarMaestro .SgceBtnInicio:hover,
a[href="Logout.php"]:hover,
.navbar a[href="Logout.php"]:hover,
.navbar-custom a[href="Logout.php"]:hover,
.NavbarMaestro a[href="Logout.php"]:hover,
.BtnLogout:hover{
    background:linear-gradient(135deg,var(--SgceFixTinto),var(--SgceFixTintoOscuro)) !important;
    color:#FFFFFF !important;
    border:3px solid var(--SgceFixTintoOscuro) !important;
    transform:translateY(-2px) !important;
    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.16),
        0 14px 30px rgba(122,8,24,.36),
        0 0 0 4px rgba(122,8,24,.10) !important;
}
a.SgceBtnInicio:hover i,
button.SgceBtnInicio:hover i,
.btn.SgceBtnInicio:hover i,
a[href="Logout.php"]:hover i,
a[href="Logout.php"]:hover span,
.BtnLogout:hover i,
.BtnLogout:hover span{
    color:#FFFFFF !important;
}
/* Reportes: botones principales siempre rellenos y con borde tinto fuerte */
.ReporteBtn,
button.ReporteBtn,
.btn.ReporteBtn,
.Btn.ReporteBtn,
form .ReporteBtn,
.card .ReporteBtn,
.Card .ReporteBtn,
body .ReporteBtn,
body button.ReporteBtn,
body .btn.ReporteBtn{
    background:linear-gradient(135deg,var(--SgceFixTinto),var(--SgceFixTinto2)) !important;
    color:#FFFFFF !important;
    border:3px solid var(--SgceFixTintoOscuro) !important;
    border-radius:999px !important;
    min-height:48px !important;
    font-weight:900 !important;
    letter-spacing:.03em !important;
    box-shadow:0 12px 28px rgba(122,8,24,.28) !important;
    text-decoration:none !important;
    transition:transform .22s var(--SgceFixAnim), box-shadow .22s var(--SgceFixAnim), filter .22s var(--SgceFixAnim) !important;
}
.ReporteBtn:hover,
button.ReporteBtn:hover,
.btn.ReporteBtn:hover,
.Btn.ReporteBtn:hover,
form .ReporteBtn:hover,
.card .ReporteBtn:hover,
.Card .ReporteBtn:hover,
body .ReporteBtn:hover,
body button.ReporteBtn:hover,
body .btn.ReporteBtn:hover{
    background:linear-gradient(135deg,var(--SgceFixTintoOscuro),var(--SgceFixTinto)) !important;
    color:#FFFFFF !important;
    border-color:var(--SgceFixTintoOscuro) !important;
    transform:translateY(-2px) scale(1.01) !important;
    box-shadow:0 16px 34px rgba(122,8,24,.36),0 0 0 4px rgba(122,8,24,.10) !important;
    filter:saturate(1.06) !important;
}
.ReporteBtn i,
.ReporteBtn:hover i,
button.ReporteBtn i,
button.ReporteBtn:hover i,
.btn.ReporteBtn i,
.btn.ReporteBtn:hover i{
    color:#FFFFFF !important;
}
@media (max-width:768px){
    a.SgceBtnInicio,button.SgceBtnInicio,.btn.SgceBtnInicio,a[href="Logout.php"],.BtnLogout{width:100%;}
}
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
    


<!-- SGCE FIX12: Homologación final de botones superiores y reportes -->
<style id="SgceFix12BotonesFinales">
:root{
    --SgceFix12Tinto:#7A0818;
    --SgceFix12Tinto2:#A10D26;
    --SgceFix12TintoDark:#3B030A;
    --SgceFix12TintoBorder:#4F050F;
    --SgceFix12Anim:cubic-bezier(.22,.61,.36,1);
}

/* Botones superiores: Cerrar sesión / Volver a inicio. Mismo tamaño y misma reacción. */
html body a.SgceBtnInicio,
html body button.SgceBtnInicio,
html body .btn.SgceBtnInicio,
html body .BtnBack.SgceBtnInicio,
html body .ActionBtn.SgceBtnInicio,
html body .btn-light.SgceBtnInicio,
html body .btn-outline-light.SgceBtnInicio,
html body .BtnGuinda.SgceBtnInicio,
html body .SgceTopAction,
html body a.SgceTopAction,
html body .Top .SgceBtnInicio,
html body .TopHeader .SgceBtnInicio,
html body .navbar .SgceBtnInicio,
html body .navbar-custom .SgceBtnInicio,
html body .NavbarMaestro .SgceBtnInicio,
html body a[href="Logout.php"],
html body a[href*="Logout.php"],
html body .navbar a[href="Logout.php"],
html body .navbar-custom a[href="Logout.php"],
html body .NavbarMaestro a[href="Logout.php"],
html body #BtnCerrarSesionAdmin,
html body .BotonCerrarSesionBlanco,
html body .BtnLogout{
    width:210px !important;
    min-width:210px !important;
    max-width:210px !important;
    height:48px !important;
    min-height:48px !important;
    padding:0 18px !important;
    margin:0 !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:9px !important;
    border-radius:999px !important;
    background:#FFFFFF !important;
    background-image:none !important;
    color:var(--SgceFix12Tinto) !important;
    border:3px solid var(--SgceFix12TintoBorder) !important;
    outline:0 !important;
    font-weight:900 !important;
    font-size:14px !important;
    line-height:1 !important;
    letter-spacing:.02em !important;
    text-transform:none !important;
    text-decoration:none !important;
    white-space:nowrap !important;
    opacity:1 !important;
    filter:none !important;
    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.78),
        0 8px 18px rgba(122,8,24,.22),
        0 0 0 3px rgba(122,8,24,.07) !important;
    transform:none !important;
    transition:
        transform .22s var(--SgceFix12Anim),
        box-shadow .22s var(--SgceFix12Anim),
        background .22s var(--SgceFix12Anim),
        background-image .22s var(--SgceFix12Anim),
        color .22s var(--SgceFix12Anim),
        border-color .22s var(--SgceFix12Anim) !important;
}

html body a.SgceBtnInicio i,
html body a.SgceBtnInicio span,
html body button.SgceBtnInicio i,
html body button.SgceBtnInicio span,
html body .btn.SgceBtnInicio i,
html body .btn.SgceBtnInicio span,
html body .SgceTopAction i,
html body .SgceTopAction span,
html body a[href="Logout.php"] i,
html body a[href="Logout.php"] span,
html body a[href*="Logout.php"] i,
html body a[href*="Logout.php"] span,
html body #BtnCerrarSesionAdmin i,
html body #BtnCerrarSesionAdmin span,
html body .BotonCerrarSesionBlanco i,
html body .BotonCerrarSesionBlanco span,
html body .BtnLogout i,
html body .BtnLogout span{
    color:var(--SgceFix12Tinto) !important;
    opacity:1 !important;
    filter:none !important;
}

html body a.SgceBtnInicio:hover,
html body button.SgceBtnInicio:hover,
html body .btn.SgceBtnInicio:hover,
html body .BtnBack.SgceBtnInicio:hover,
html body .ActionBtn.SgceBtnInicio:hover,
html body .btn-light.SgceBtnInicio:hover,
html body .btn-outline-light.SgceBtnInicio:hover,
html body .BtnGuinda.SgceBtnInicio:hover,
html body .SgceTopAction:hover,
html body a.SgceTopAction:hover,
html body .Top .SgceBtnInicio:hover,
html body .TopHeader .SgceBtnInicio:hover,
html body .navbar .SgceBtnInicio:hover,
html body .navbar-custom .SgceBtnInicio:hover,
html body .NavbarMaestro .SgceBtnInicio:hover,
html body a[href="Logout.php"]:hover,
html body a[href*="Logout.php"]:hover,
html body .navbar a[href="Logout.php"]:hover,
html body .navbar-custom a[href="Logout.php"]:hover,
html body .NavbarMaestro a[href="Logout.php"]:hover,
html body #BtnCerrarSesionAdmin:hover,
html body .BotonCerrarSesionBlanco:hover,
html body .BtnLogout:hover,
html body a.SgceBtnInicio:focus-visible,
html body .SgceTopAction:focus-visible,
html body a[href*="Logout.php"]:focus-visible,
html body #BtnCerrarSesionAdmin:focus-visible{
    background:linear-gradient(135deg,var(--SgceFix12Tinto),var(--SgceFix12TintoDark)) !important;
    background-image:linear-gradient(135deg,var(--SgceFix12Tinto),var(--SgceFix12TintoDark)) !important;
    color:#FFFFFF !important;
    border-color:var(--SgceFix12TintoDark) !important;
    transform:translateY(-2px) !important;
    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.18),
        0 15px 32px rgba(122,8,24,.36),
        0 0 0 4px rgba(122,8,24,.12) !important;
    opacity:1 !important;
    filter:none !important;
}

html body a.SgceBtnInicio:hover i,
html body a.SgceBtnInicio:hover span,
html body button.SgceBtnInicio:hover i,
html body button.SgceBtnInicio:hover span,
html body .btn.SgceBtnInicio:hover i,
html body .btn.SgceBtnInicio:hover span,
html body .SgceTopAction:hover i,
html body .SgceTopAction:hover span,
html body a[href="Logout.php"]:hover i,
html body a[href="Logout.php"]:hover span,
html body a[href*="Logout.php"]:hover i,
html body a[href*="Logout.php"]:hover span,
html body #BtnCerrarSesionAdmin:hover i,
html body #BtnCerrarSesionAdmin:hover span,
html body .BotonCerrarSesionBlanco:hover i,
html body .BotonCerrarSesionBlanco:hover span,
html body .BtnLogout:hover i,
html body .BtnLogout:hover span,
html body a.SgceBtnInicio:focus-visible i,
html body a.SgceBtnInicio:focus-visible span,
html body #BtnCerrarSesionAdmin:focus-visible i,
html body #BtnCerrarSesionAdmin:focus-visible span{
    color:#FFFFFF !important;
}

/* Reportes: botones principales rellenos, con efecto igual al dashboard. */
html body .ReporteBtn,
html body button.ReporteBtn,
html body .btn.ReporteBtn,
html body .Btn.ReporteBtn,
html body form .ReporteBtn,
html body .card .ReporteBtn,
html body .Card .ReporteBtn{
    width:100% !important;
    min-height:50px !important;
    border-radius:999px !important;
    background:linear-gradient(135deg,var(--SgceFix12Tinto),var(--SgceFix12Tinto2)) !important;
    background-image:linear-gradient(135deg,var(--SgceFix12Tinto),var(--SgceFix12Tinto2)) !important;
    color:#FFFFFF !important;
    border:3px solid var(--SgceFix12TintoDark) !important;
    font-weight:900 !important;
    letter-spacing:.03em !important;
    box-shadow:0 12px 28px rgba(122,8,24,.28) !important;
    text-decoration:none !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:.5rem !important;
    transition:transform .22s var(--SgceFix12Anim), box-shadow .22s var(--SgceFix12Anim), filter .22s var(--SgceFix12Anim) !important;
}
html body .ReporteBtn:hover,
html body button.ReporteBtn:hover,
html body .btn.ReporteBtn:hover,
html body .Btn.ReporteBtn:hover,
html body form .ReporteBtn:hover,
html body .card .ReporteBtn:hover,
html body .Card .ReporteBtn:hover{
    background:linear-gradient(135deg,var(--SgceFix12TintoDark),var(--SgceFix12Tinto)) !important;
    background-image:linear-gradient(135deg,var(--SgceFix12TintoDark),var(--SgceFix12Tinto)) !important;
    color:#FFFFFF !important;
    border-color:var(--SgceFix12TintoDark) !important;
    transform:translateY(-2px) scale(1.01) !important;
    box-shadow:0 16px 34px rgba(122,8,24,.36),0 0 0 4px rgba(122,8,24,.10) !important;
    filter:saturate(1.06) !important;
}
html body .ReporteBtn i,
html body .ReporteBtn span,
html body .ReporteBtn:hover i,
html body .ReporteBtn:hover span{
    color:#FFFFFF !important;
}

@media (max-width:768px){
    html body a.SgceBtnInicio,
    html body button.SgceBtnInicio,
    html body .btn.SgceBtnInicio,
    html body .SgceTopAction,
    html body a[href="Logout.php"],
    html body a[href*="Logout.php"],
    html body #BtnCerrarSesionAdmin,
    html body .BotonCerrarSesionBlanco,
    html body .BtnLogout{
        width:100% !important;
        min-width:0 !important;
        max-width:100% !important;
    }
}
</style>

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

if (!SgcePuedeAdministrarReportes($UserSession)) {
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

    

/* ==========================================================
   FIX8 - BOTONES DE REGRESO / CERRAR SESION HOMOLOGADOS
   Estado normal: blanco con texto tinto.
   Hover: relleno tinto con texto blanco.
   ========================================================== */
.SgceBtnInicio,
a.SgceBtnInicio,
button.SgceBtnInicio,
.BtnBack.SgceBtnInicio,
.ActionBtn.SgceBtnInicio,
.btn.SgceBtnInicio,
.btn-outline-light.SgceBtnInicio,
.btn-light.SgceBtnInicio,
.BtnGuinda.SgceBtnInicio,
.Top .SgceBtnInicio,
.navbar .SgceBtnInicio,
.NavbarMaestro .SgceBtnInicio,
.navbar-custom .SgceBtnInicio{
    background:#FFFFFF !important;
    color:#7A0818 !important;
    border:2px solid #FFFFFF !important;
    border-radius:999px !important;
    box-shadow:0 8px 18px rgba(0,0,0,.12) !important;
    text-decoration:none !important;
}
.SgceBtnInicio i,
a.SgceBtnInicio i,
button.SgceBtnInicio i,
.btn.SgceBtnInicio i{
    color:#7A0818 !important;
}
.SgceBtnInicio:hover,
a.SgceBtnInicio:hover,
button.SgceBtnInicio:hover,
.BtnBack.SgceBtnInicio:hover,
.ActionBtn.SgceBtnInicio:hover,
.btn.SgceBtnInicio:hover,
.btn-outline-light.SgceBtnInicio:hover,
.btn-light.SgceBtnInicio:hover,
.BtnGuinda.SgceBtnInicio:hover,
.Top .SgceBtnInicio:hover,
.navbar .SgceBtnInicio:hover,
.NavbarMaestro .SgceBtnInicio:hover,
.navbar-custom .SgceBtnInicio:hover{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border-color:#7A0818 !important;
    transform:translateY(-1px) !important;
    box-shadow:0 12px 26px rgba(122,8,24,.28) !important;
}
.SgceBtnInicio:hover i,
a.SgceBtnInicio:hover i,
button.SgceBtnInicio:hover i,
.btn.SgceBtnInicio:hover i{
    color:#FFFFFF !important;
}

a[href*="Logout.php"],
.BtnLogout,
.NavbarMaestro a[href="Logout.php"],
.navbar-custom a[href="Logout.php"],
.navbar a[href="Logout.php"]{
    background:#FFFFFF !important;
    color:#7A0818 !important;
    border:2px solid #FFFFFF !important;
    border-radius:999px !important;
    box-shadow:0 8px 18px rgba(0,0,0,.12) !important;
    text-decoration:none !important;
}
a[href*="Logout.php"] i,
.BtnLogout i,
.NavbarMaestro a[href="Logout.php"] i,
.navbar-custom a[href="Logout.php"] i,
.navbar a[href="Logout.php"] i,
a[href*="Logout.php"] span{
    color:#7A0818 !important;
}
a[href*="Logout.php"]:hover,
.BtnLogout:hover,
.NavbarMaestro a[href="Logout.php"]:hover,
.navbar-custom a[href="Logout.php"]:hover,
.navbar a[href="Logout.php"]:hover{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border-color:#7A0818 !important;
    transform:translateY(-1px) !important;
    box-shadow:0 12px 26px rgba(122,8,24,.28) !important;
}
a[href*="Logout.php"]:hover i,
.BtnLogout:hover i,
.NavbarMaestro a[href="Logout.php"]:hover i,
.navbar-custom a[href="Logout.php"]:hover i,
.navbar a[href="Logout.php"]:hover i,
a[href*="Logout.php"]:hover span{
    color:#FFFFFF !important;
}



/* ==========================================================
   SGCE FIX9 - HOMOLOGACIÓN VISUAL FINAL
   Botones con borde visible, hover institucional y efectos.
   ========================================================== */
:root{
    --SgceGuinda:#7A0818;
    --SgceGuinda2:#A10D26;
    --SgceGuindaHover:#5E0612;
    --SgceTintoOscuro:#4F0610;
    --SgceBordeSuave:rgba(122,8,24,.22);
    --SgceSombra:0 12px 28px rgba(122,8,24,.20);
    --SgceSombraHover:0 18px 38px rgba(122,8,24,.32);
    --SgceAnim:cubic-bezier(.22,.61,.36,1);
}

/* Botones superiores: cerrar sesión y volver a inicio */
a.SgceBtnInicio,
button.SgceBtnInicio,
.btn.SgceBtnInicio,
.BtnBack.SgceBtnInicio,
.ActionBtn.SgceBtnInicio,
.btn-light.SgceBtnInicio,
.btn-outline-light.SgceBtnInicio,
.Top .SgceBtnInicio,
.navbar .SgceBtnInicio,
.NavbarMaestro .SgceBtnInicio,
.navbar-custom .SgceBtnInicio,
a[href="Logout.php"],
.navbar a[href="Logout.php"],
.NavbarMaestro a[href="Logout.php"]{
    background:#FFFFFF !important;
    color:var(--SgceGuinda) !important;
    border:2px solid rgba(122,8,24,.35) !important;
    border-radius:999px !important;
    box-shadow:0 8px 20px rgba(122,8,24,.12), inset 0 0 0 1px rgba(255,255,255,.75) !important;
    font-weight:800 !important;
    letter-spacing:.2px !important;
    text-decoration:none !important;
    transition:transform .22s var(--SgceAnim), box-shadow .22s var(--SgceAnim), background .22s var(--SgceAnim), color .22s var(--SgceAnim), border-color .22s var(--SgceAnim) !important;
}
a.SgceBtnInicio i,
button.SgceBtnInicio i,
.btn.SgceBtnInicio i,
a[href="Logout.php"] i{color:var(--SgceGuinda) !important; transition:color .22s var(--SgceAnim) !important;}

a.SgceBtnInicio:hover,
button.SgceBtnInicio:hover,
.btn.SgceBtnInicio:hover,
.BtnBack.SgceBtnInicio:hover,
.ActionBtn.SgceBtnInicio:hover,
.btn-light.SgceBtnInicio:hover,
.btn-outline-light.SgceBtnInicio:hover,
.Top .SgceBtnInicio:hover,
.navbar .SgceBtnInicio:hover,
.NavbarMaestro .SgceBtnInicio:hover,
.navbar-custom .SgceBtnInicio:hover,
a[href="Logout.php"]:hover,
.navbar a[href="Logout.php"]:hover,
.NavbarMaestro a[href="Logout.php"]:hover{
    background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;
    color:#FFFFFF !important;
    border-color:rgba(122,8,24,.75) !important;
    transform:translateY(-2px) !important;
    box-shadow:0 14px 32px rgba(122,8,24,.28), inset 0 0 0 1px rgba(255,255,255,.18) !important;
}
a.SgceBtnInicio:hover i,
button.SgceBtnInicio:hover i,
.btn.SgceBtnInicio:hover i,
a[href="Logout.php"]:hover i{color:#FFFFFF !important;}

/* Botones de reportes: rellenos, tinto y con movimiento como dashboard */
.ReporteBtn,
button.ReporteBtn,
.btn.ReporteBtn,
.Btn.ReporteBtn,
form .ReporteBtn,
.card .ReporteBtn,
.Card .ReporteBtn,
body .ReporteBtn,
body button.ReporteBtn,
body .btn.ReporteBtn,
body .Btn.ReporteBtn{
    position:relative !important;
    overflow:hidden !important;
    isolation:isolate !important;
    background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;
    color:#FFFFFF !important;
    border:2px solid var(--SgceTintoOscuro) !important;
    border-radius:999px !important;
    min-height:48px !important;
    font-weight:900 !important;
    letter-spacing:.35px !important;
    text-transform:uppercase !important;
    box-shadow:0 10px 22px rgba(122,8,24,.22), inset 0 1px 0 rgba(255,255,255,.18) !important;
    transition:transform .22s var(--SgceAnim), box-shadow .22s var(--SgceAnim), filter .22s var(--SgceAnim), border-color .22s var(--SgceAnim) !important;
}
.ReporteBtn::before,
button.ReporteBtn::before,
.btn.ReporteBtn::before,
.Btn.ReporteBtn::before{
    content:"";
    position:absolute;
    inset:0;
    z-index:-1;
    background:linear-gradient(120deg,transparent 0%,rgba(255,255,255,.22) 40%,transparent 72%);
    transform:translateX(-120%);
    transition:transform .55s var(--SgceAnim);
}
.ReporteBtn:hover,
button.ReporteBtn:hover,
.btn.ReporteBtn:hover,
.Btn.ReporteBtn:hover,
form .ReporteBtn:hover,
.card .ReporteBtn:hover,
.Card .ReporteBtn:hover,
body .ReporteBtn:hover,
body button.ReporteBtn:hover,
body .btn.ReporteBtn:hover,
body .Btn.ReporteBtn:hover{
    background:linear-gradient(135deg,var(--SgceGuindaHover),var(--SgceGuinda)) !important;
    color:#FFFFFF !important;
    border-color:var(--SgceTintoOscuro) !important;
    transform:translateY(-3px) scale(1.01) !important;
    box-shadow:0 18px 38px rgba(122,8,24,.34), inset 0 1px 0 rgba(255,255,255,.25) !important;
    filter:saturate(1.05) !important;
}
.ReporteBtn:hover::before,
button.ReporteBtn:hover::before,
.btn.ReporteBtn:hover::before,
.Btn.ReporteBtn:hover::before{transform:translateX(120%);}
.ReporteBtn i,
.ReporteBtn:hover i,
button.ReporteBtn i,
button.ReporteBtn:hover i,
.btn.ReporteBtn i,
.btn.ReporteBtn:hover i{color:#FFFFFF !important;}
.ReporteBtn:active,
button.ReporteBtn:active,
.btn.ReporteBtn:active{transform:translateY(0) scale(.99) !important;}

/* Efectos generales para botones de acciones sin romper colores existentes */
.ActionBtn,
.BotonAccion,
.BtnExport,
.BtnGuinda,
.ExportIcon,
button.btn:not(.btn-close):not(.navbar-toggler),
a.btn:not(.btn-close):not(.navbar-toggler){
    transition:transform .22s var(--SgceAnim), box-shadow .22s var(--SgceAnim), filter .22s var(--SgceAnim), background .22s var(--SgceAnim), color .22s var(--SgceAnim) !important;
}
.ActionBtn:hover,
.BotonAccion:hover,
.BtnExport:hover,
.BtnGuinda:hover,
.ExportIcon:hover,
button.btn:not(.btn-close):not(.navbar-toggler):hover,
a.btn:not(.btn-close):not(.navbar-toggler):hover{
    transform:translateY(-2px) !important;
    box-shadow:0 14px 30px rgba(15,23,42,.18) !important;
}

/* Tarjetas y cajas con respiración visual */
.card,
.Card,
.CardClase,
.ModuloCard,
.StatCard,
.PanelCard,
.DashboardCard{
    transition:transform .22s var(--SgceAnim), box-shadow .22s var(--SgceAnim), border-color .22s var(--SgceAnim) !important;
}
.card:hover,
.Card:hover,
.CardClase:hover,
.ModuloCard:hover,
.PanelCard:hover,
.DashboardCard:hover{
    transform:translateY(-2px) !important;
    box-shadow:0 18px 45px rgba(15,23,42,.11) !important;
}

/* Inputs más limpios y consistentes */
.form-control:focus,
.form-select:focus{
    border-color:var(--SgceGuinda) !important;
    box-shadow:0 0 0 .22rem rgba(122,8,24,.12) !important;
}

/* Respeto a usuarios con reducción de movimiento */
@media (prefers-reduced-motion:reduce){
    *,*::before,*::after{transition:none !important; animation:none !important; transform:none !important;}
}


/* ==========================================================
   SGCE FIX11 - BOTONES SUPERIORES HOMOLOGADOS DEFINITIVOS
   Aplicado directo en cada archivo para evitar conflictos.
   ========================================================== */
:root{
    --SgceTopTinto:#7A0818;
    --SgceTopTinto2:#A10D26;
    --SgceTopTintoDark:#3B030A;
    --SgceTopAnim:cubic-bezier(.22,.61,.36,1);
}

/* Volver a inicio / Cerrar sesión: mismo tamaño exacto */
a.SgceBtnInicio,
button.SgceBtnInicio,
.btn.SgceBtnInicio,
.BtnBack.SgceBtnInicio,
.ActionBtn.SgceBtnInicio,
.btn-light.SgceBtnInicio,
.btn-outline-light.SgceBtnInicio,
.BtnGuinda.SgceBtnInicio,
.Top .SgceBtnInicio,
.TopHeader .SgceBtnInicio,
.navbar .SgceBtnInicio,
.navbar-custom .SgceBtnInicio,
.NavbarMaestro .SgceBtnInicio,
a[href="Logout.php"],
a[href*="Logout.php"],
.navbar a[href="Logout.php"],
.navbar-custom a[href="Logout.php"],
.NavbarMaestro a[href="Logout.php"],
#BtnCerrarSesionAdmin,
.BotonCerrarSesionBlanco,
.BtnLogout{
    width:210px !important;
    min-width:210px !important;
    max-width:210px !important;
    height:48px !important;
    min-height:48px !important;
    padding:0 18px !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:9px !important;
    border-radius:999px !important;
    background:#FFFFFF !important;
    background-image:none !important;
    color:var(--SgceTopTinto) !important;
    border:3px solid var(--SgceTopTinto) !important;
    font-weight:900 !important;
    font-size:14px !important;
    line-height:1 !important;
    letter-spacing:.02em !important;
    text-transform:none !important;
    text-decoration:none !important;
    white-space:nowrap !important;
    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.85),
        0 8px 18px rgba(122,8,24,.18),
        0 0 0 3px rgba(122,8,24,.06) !important;
    opacity:1 !important;
    filter:none !important;
    transition:
        transform .22s var(--SgceTopAnim),
        box-shadow .22s var(--SgceTopAnim),
        background .22s var(--SgceTopAnim),
        color .22s var(--SgceTopAnim),
        border-color .22s var(--SgceTopAnim) !important;
}

a.SgceBtnInicio i,
button.SgceBtnInicio i,
.btn.SgceBtnInicio i,
.BtnBack.SgceBtnInicio i,
.ActionBtn.SgceBtnInicio i,
a[href="Logout.php"] i,
a[href*="Logout.php"] i,
a[href="Logout.php"] span,
a[href*="Logout.php"] span,
#BtnCerrarSesionAdmin i,
#BtnCerrarSesionAdmin span,
.BotonCerrarSesionBlanco i,
.BotonCerrarSesionBlanco span,
.BtnLogout i,
.BtnLogout span{
    color:inherit !important;
    transition:color .22s var(--SgceTopAnim) !important;
}

a.SgceBtnInicio:hover,
button.SgceBtnInicio:hover,
.btn.SgceBtnInicio:hover,
.BtnBack.SgceBtnInicio:hover,
.ActionBtn.SgceBtnInicio:hover,
.btn-light.SgceBtnInicio:hover,
.btn-outline-light.SgceBtnInicio:hover,
.BtnGuinda.SgceBtnInicio:hover,
.Top .SgceBtnInicio:hover,
.TopHeader .SgceBtnInicio:hover,
.navbar .SgceBtnInicio:hover,
.navbar-custom .SgceBtnInicio:hover,
.NavbarMaestro .SgceBtnInicio:hover,
a[href="Logout.php"]:hover,
a[href*="Logout.php"]:hover,
.navbar a[href="Logout.php"]:hover,
.navbar-custom a[href="Logout.php"]:hover,
.NavbarMaestro a[href="Logout.php"]:hover,
#BtnCerrarSesionAdmin:hover,
.BotonCerrarSesionBlanco:hover,
.BtnLogout:hover{
    background:linear-gradient(135deg,var(--SgceTopTinto),var(--SgceTopTintoDark)) !important;
    background-image:linear-gradient(135deg,var(--SgceTopTinto),var(--SgceTopTintoDark)) !important;
    color:#FFFFFF !important;
    border-color:var(--SgceTopTintoDark) !important;
    transform:translateY(-2px) !important;
    box-shadow:
        inset 0 0 0 1px rgba(255,255,255,.20),
        0 15px 32px rgba(122,8,24,.34),
        0 0 0 4px rgba(122,8,24,.10) !important;
}

a.SgceBtnInicio:hover i,
button.SgceBtnInicio:hover i,
.btn.SgceBtnInicio:hover i,
.BtnBack.SgceBtnInicio:hover i,
.ActionBtn.SgceBtnInicio:hover i,
a[href="Logout.php"]:hover i,
a[href*="Logout.php"]:hover i,
a[href="Logout.php"]:hover span,
a[href*="Logout.php"]:hover span,
#BtnCerrarSesionAdmin:hover i,
#BtnCerrarSesionAdmin:hover span,
.BotonCerrarSesionBlanco:hover i,
.BotonCerrarSesionBlanco:hover span,
.BtnLogout:hover i,
.BtnLogout:hover span{
    color:#FFFFFF !important;
}

/* Alineación del botón dentro de barras superiores */
.navbar .container-fluid,
.navbar-custom .container-fluid,
.NavbarMaestro .container-fluid{
    gap:16px !important;
}

/* En móviles, ocupa todo el ancho disponible sin romper diseño */
@media (max-width:768px){
    a.SgceBtnInicio,
    button.SgceBtnInicio,
    .btn.SgceBtnInicio,
    .BtnBack.SgceBtnInicio,
    .ActionBtn.SgceBtnInicio,
    a[href="Logout.php"],
    a[href*="Logout.php"],
    #BtnCerrarSesionAdmin,
    .BotonCerrarSesionBlanco,
    .BtnLogout{
        width:100% !important;
        min-width:0 !important;
        max-width:100% !important;
    }
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
