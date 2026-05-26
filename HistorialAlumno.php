<?php

/*
    Archivo: HistorialAlumno.php
    Descripción: Expediente individual del alumno.
    Permite al administrador revisar calificaciones, asistencias y resumen general
    sin cargar listas completas innecesarias.
*/

require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || !in_array($UserSession['Rol'], ['admin','director','prefecto'], true)) {
    header('Location: index.php');
    exit;
}

$AlumnoId = intval($_GET['AlumnoId'] ?? 0);
if ($AlumnoId <= 0) {
    die('Alumno inválido.');
}

function H($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

$StmtAlumno = $Pdo->prepare("\n    SELECT Al.Id, Al.NombreCompleto, G.Grado, G.Grupo, G.Turno\n    FROM Alumnos Al\n    LEFT JOIN Grupos G ON Al.GrupoId = G.Id\n    WHERE Al.Id = ?\n    AND Al.Activo = 1\n    LIMIT 1\n");
$StmtAlumno->execute([$AlumnoId]);
$Alumno = $StmtAlumno->fetch();

if (!$Alumno) {
    die('Alumno no encontrado o dado de baja.');
}

$StmtResumenAsis = $Pdo->prepare("\n    SELECT Estado, COUNT(*) AS Total\n    FROM Asistencias\n    WHERE AlumnoId = ?\n    GROUP BY Estado\n");
$StmtResumenAsis->execute([$AlumnoId]);
$Conteos = ['A'=>0,'F'=>0,'R'=>0,'J'=>0];
foreach ($StmtResumenAsis->fetchAll() as $Fila) {
    if (isset($Conteos[$Fila['Estado']])) {
        $Conteos[$Fila['Estado']] = (int)$Fila['Total'];
    }
}

$StmtPromedio = $Pdo->prepare("SELECT ROUND(AVG(Calificacion), 1) FROM Calificaciones WHERE AlumnoId = ?");
$StmtPromedio->execute([$AlumnoId]);
$Promedio = $StmtPromedio->fetchColumn();
$Promedio = $Promedio !== null ? $Promedio : '0.0';

$StmtCalificaciones = $Pdo->prepare("\n    SELECT Asg.MateriaNombre, U.NombreCompleto AS Maestro, C.Calificacion, C.FechaActualizacion\n    FROM Calificaciones C\n    JOIN Asignaciones Asg ON C.AsignacionId = Asg.Id\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE C.AlumnoId = ?\n    ORDER BY Asg.MateriaNombre ASC\n");
$StmtCalificaciones->execute([$AlumnoId]);
$Calificaciones = $StmtCalificaciones->fetchAll();

$StmtAsistencias = $Pdo->prepare("\n    SELECT Asis.FechaDia, DATE_FORMAT(Asis.FechaDia, '%d/%m/%Y') AS FechaTexto, Asg.MateriaNombre, U.NombreCompleto AS Maestro, Asis.Estado\n    FROM Asistencias Asis\n    JOIN Asignaciones Asg ON Asis.AsignacionId = Asg.Id\n    JOIN Usuarios U ON Asg.MaestroId = U.Id\n    WHERE Asis.AlumnoId = ?\n    ORDER BY Asis.FechaDia DESC, Asg.MateriaNombre ASC\n    LIMIT 300\n");
$StmtAsistencias->execute([$AlumnoId]);
$Asistencias = $StmtAsistencias->fetchAll();

function TextoEstado($Estado) {
    switch ($Estado) {
        case 'A': return 'ASISTENCIA';
        case 'F': return 'FALTA';
        case 'R': return 'RETARDO';
        case 'J': return 'JUSTIFICANTE';
        default: return 'SIN REGISTRO';
    }
}

function ClaseEstado($Estado) {
    switch ($Estado) {
        case 'A': return 'success';
        case 'F': return 'danger';
        case 'R': return 'warning text-dark';
        case 'J': return 'primary';
        default: return 'secondary';
    }
}

RegistrarBitacora($Pdo, $UserSession, 'CONSULTAR_EXPEDIENTE', 'Alumnos', $AlumnoId, 'EXPEDIENTE INDIVIDUAL CONSULTADO');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGCE | Expediente Del Alumno</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root{--Guinda:#7A0818;--Guinda2:#A10D26;--Fondo:#EEF2F7;--Texto:#1F2937;--Borde:#E5E7EB;}
        *{font-family:'Poppins','Segoe UI',sans-serif;box-sizing:border-box;}
        body{min-height:100vh;background:radial-gradient(circle at top left,rgba(122,8,24,.10),transparent 34%),linear-gradient(to bottom,#F8FAFC,#EEF2F7);color:var(--Texto);}
        .Top{background:linear-gradient(135deg,var(--Guinda),var(--Guinda2));color:white;border-radius:28px;padding:28px;box-shadow:0 14px 35px rgba(122,8,24,.22);}
        .Card{border:0;border-radius:26px;background:white;box-shadow:0 12px 35px rgba(15,23,42,.08);overflow:hidden;}
        .IconBox{width:70px;height:70px;border-radius:22px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:2rem;}
        .Stat{border-radius:22px;background:white;padding:20px;box-shadow:0 12px 30px rgba(15,23,42,.08);}
        .table th{color:#6B7280;text-transform:uppercase;font-size:.8rem;letter-spacing:.4px;}
        .table td,.table th{vertical-align:middle;text-align:center;}
        .BtnBack{border:2px solid white;color:white;border-radius:999px;padding:10px 20px;text-decoration:none;font-weight:800;display:inline-flex;gap:8px;align-items:center;}
        .BtnBack:hover{background:white;color:var(--Guinda);}
        @media(max-width:768px){.Top{padding:20px}.table{font-size:.85rem}.IconBox{width:56px;height:56px}}
    

        /* ==========================================================
           AJUSTE FINAL INSTITUCIONAL EST 101
           Botones sobrios en guinda/tinto: blanco con borde y hover relleno.
        ========================================================== */
        :root{
            --Tinto101:#7A0818;
            --Tinto101Hover:#4F0610;
            --Tinto101Suave:rgba(122,8,24,.10);
        }

        .ActionBtn,
        .BtnExport,
        .ExportIcon,
        .BotonAccion,
        .BtnBack,
        .BtnGuardar,
        .btn:not(.btn-close):not(.navbar-toggler):not(.BtnLogin){
            background:#FFFFFF !important;
            border:2px solid var(--Tinto101) !important;
            color:var(--Tinto101) !important;
            box-shadow:0 6px 16px rgba(122,8,24,.08) !important;
        }

        .ActionBtn:hover,
        .BtnExport:hover,
        .ExportIcon:hover,
        .BotonAccion:hover,
        .BtnBack:hover,
        .BtnGuardar:hover,
        .btn:not(.btn-close):not(.navbar-toggler):not(.BtnLogin):hover{
            background:linear-gradient(135deg,var(--Tinto101),var(--Tinto101Hover)) !important;
            border-color:var(--Tinto101Hover) !important;
            color:#FFFFFF !important;
            transform:translateY(-2px) !important;
            box-shadow:0 12px 26px rgba(122,8,24,.20) !important;
        }

        .ActionBtn:hover i,
        .BtnExport:hover i,
        .ExportIcon:hover i,
        .BotonAccion:hover i,
        .BtnBack:hover i,
        .BtnGuardar:hover i,
        .btn:not(.btn-close):not(.navbar-toggler):not(.BtnLogin):hover i{
            color:#FFFFFF !important;
        }

        .ActionBtn span,
        .BtnExport span,
        .ExportIcon span{
            color:inherit !important;
        }

        .badge.bg-primary,
        .badge.bg-danger,
        .badge.bg-warning,
        .badge.bg-success,
        .badge.bg-dark{
            background:var(--Tinto101) !important;
            color:#FFFFFF !important;
        }

        .text-danger,
        .text-primary,
        .text-success,
        .text-warning{
            color:var(--Tinto101) !important;
        }

    

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
<div class="container-fluid px-4 py-4">
    <div class="Top mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="IconBox"><i class="fa-solid fa-folder-open"></i></div>
            <div>
                <h2 class="fw-black mb-1" style="font-weight:900;">EXPEDIENTE DEL ALUMNO</h2>
                <div><?= H($Alumno['NombreCompleto']) ?> · <?= H($Alumno['Grado'].' '.$Alumno['Grupo'].' '.$Alumno['Turno']) ?></div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2"><a href="ExportarAlumno.php?AlumnoId=<?= (int)$AlumnoId ?>" target="_blank" class="BtnBack"><i class="fa-solid fa-file-pdf"></i> BOLETA PDF</a><a href="Admin.php?Tab=alumnos" class="BtnBack SgceBtnInicio"><i class="fa-solid fa-arrow-left"></i> VOLVER A INICIO</a></div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">PROMEDIO</div><div class="fs-2 fw-bold"><?= H($Promedio) ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">ASISTENCIAS</div><div class="fs-2 fw-bold text-success"><?= $Conteos['A'] ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">FALTAS</div><div class="fs-2 fw-bold text-danger"><?= $Conteos['F'] ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">RETARDOS</div><div class="fs-2 fw-bold text-warning"><?= $Conteos['R'] ?></div></div></div>
        <div class="col-md-2 col-6"><div class="Stat"><div class="text-muted fw-bold small">JUSTIFICANTES</div><div class="fs-2 fw-bold text-primary"><?= $Conteos['J'] ?></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="Card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-star text-warning me-2"></i> CALIFICACIONES</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Materia</th><th>Docente</th><th>Calificación</th></tr></thead>
                        <tbody>
                            <?php foreach($Calificaciones as $C): ?>
                            <tr><td><?= H($C['MateriaNombre']) ?></td><td><?= H($C['Maestro']) ?></td><td><span class="badge bg-dark"><?= H(number_format((float)$C['Calificacion'],2)) ?></span></td></tr>
                            <?php endforeach; ?>
                            <?php if(empty($Calificaciones)): ?><tr><td colspan="3">SIN CALIFICACIONES CAPTURADAS.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="Card p-4 h-100">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-calendar-check text-success me-2"></i> ÚLTIMAS ASISTENCIAS</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Fecha</th><th>Materia</th><th>Docente</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach($Asistencias as $A): ?>
                            <tr><td><?= H($A['FechaTexto']) ?></td><td><?= H($A['MateriaNombre']) ?></td><td><?= H($A['Maestro']) ?></td><td><span class="badge bg-<?= ClaseEstado($A['Estado']) ?>"><?= TextoEstado($A['Estado']) ?></span></td></tr>
                            <?php endforeach; ?>
                            <?php if(empty($Asistencias)): ?><tr><td colspan="4">SIN ASISTENCIAS CAPTURADAS.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-muted small">SE MUESTRAN HASTA 300 REGISTROS RECIENTES PARA EVITAR CARGAS PESADAS.</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
