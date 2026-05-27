<?php

/*
    Archivo: ExportarCalificaciones.php
    Descripción: Genera reportes de calificaciones por grupo o por asignación.
    Puede entregar el archivo como Excel o como vista imprimible para guardar en PDF.
*/

require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession) { die("Acceso Denegado."); }

$AsignacionId = intval($_GET['AsignacionId'] ?? 0);
$GrupoId = intval($_GET['GrupoId'] ?? 0);
$Tipo = (($_GET['Tipo'] ?? 'Excel') === 'Pdf') ? 'Pdf' : 'Excel';
$PeriodoId = SgcePeriodoActualId($Pdo, $_GET['PeriodoId'] ?? 0);
$PeriodosDisponibles = SgcePeriodosDisponibles($Pdo);

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
        WHERE A.Id = ? AND A.Activo = 1
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
            AND C.PeriodoId = ?
        WHERE Al.GrupoId = ?
        AND Al.Activo = 1
        ORDER BY Al.NombreCompleto ASC
    ");
    $StmtAlumnos->execute([$AsignacionId, $PeriodoId, $Info['GrupoId']]);
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
        <html><head><meta charset="utf-8">
    <!-- FAVICON DEL SISTEMA: ICONO QUE APARECE EN LA PESTAÑA DEL NAVEGADOR -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<style>
            body{font-family:Arial;} table{border-collapse:collapse;width:100%;}
            th{background:#7A0818;color:white;padding:10px;border:1px solid #ccc;}
            td{border:1px solid #ccc;padding:8px;} .Titulo{background:#7A0818;color:white;font-size:18px;font-weight:bold;text-align:center;padding:18px;}
            .SubTitulo{background:#A10D26;color:white;text-align:center;padding:10px;} .Info{background:#F8F9FA;font-weight:bold;width:180px;} .Centro{text-align:center;font-weight:bold;}
        

        /* =====================================================
           EFECTOS VISUALES Y RESPONSIVIDAD GLOBAL
           Estos estilos ayudan a que las pantallas se vean más modernas,
           fluidas y adaptables en computadora, tablet y celular.
        ===================================================== */
        :root{
            --AnimacionSuave:cubic-bezier(.22,.61,.36,1);
        }

        html{
            scroll-behavior:smooth;
        }

        body{
            overflow-x:hidden;
        }

        body::before{
            content:"";
            position:fixed;
            inset:-40%;
            pointer-events:none;
            z-index:-1;
            background:
                radial-gradient(circle at 15% 15%, rgba(161,13,38,.10), transparent 28%),
                radial-gradient(circle at 85% 25%, rgba(37,99,235,.08), transparent 30%),
                radial-gradient(circle at 50% 90%, rgba(245,158,11,.08), transparent 28%);
            animation:FondoSuave 18s ease-in-out infinite alternate;
        }

        @keyframes FondoSuave{
            from{ transform:translate3d(-1%, -1%, 0) scale(1); }
            to{ transform:translate3d(1%, 1%, 0) scale(1.04); }
        }

        @keyframes EntradaSuave{
            from{ opacity:0; transform:translateY(14px) scale(.985); }
            to{ opacity:1; transform:translateY(0) scale(1); }
        }

        @keyframes BrilloSutil{
            from{ transform:translateX(-140%) skewX(-18deg); }
            to{ transform:translateX(180%) skewX(-18deg); }
        }

        .card,
        .card-custom,
        .MainCard,
        .StatsCard,
        .CardClase,
        .TopBar,
        .TopHeader,
        .GridLogin,
        .alert,
        .modal-content{
            animation:EntradaSuave .45s var(--AnimacionSuave) both;
        }

        .card,
        .card-custom,
        .MainCard,
        .StatsCard,
        .CardClase{
            will-change:transform, box-shadow;
        }

        .card:hover,
        .card-custom:hover,
        .MainCard:hover,
        .StatsCard:hover,
        .CardClase:hover{
            transform:translateY(-4px);
            box-shadow:0 18px 45px rgba(15,23,42,.10) !important;
        }

        .navbar,
        .navbar-custom,
        .NavbarMaestro{
            position:sticky;
            top:0;
            z-index:1030;
            backdrop-filter:blur(14px);
        }

        .btn,
        .nav-link,
        .ExportIcon,
        .ActionBtn,
        .BotonAccion,
        .BtnExport,
        .InputEstadoLabel,
        .CardCaracteristica{
            position:relative;
            overflow:hidden;
            transition:transform .20s var(--AnimacionSuave), box-shadow .20s var(--AnimacionSuave), border-color .20s ease, background .20s ease, color .20s ease;
        }

        .btn::after,
        .ActionBtn::after,
        .BotonAccion::after,
        .BtnExport::after{
            content:"";
            position:absolute;
            top:-40%;
            left:0;
            width:42%;
            height:180%;
            background:linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent);
            transform:translateX(-140%) skewX(-18deg);
            pointer-events:none;
        }

        .btn:hover::after,
        .ActionBtn:hover::after,
        .BotonAccion:hover::after,
        .BtnExport:hover::after{
            animation:BrilloSutil .75s ease;
        }

        .btn:hover,
        .ActionBtn:hover,
        .ExportIcon:hover,
        .BotonAccion:hover,
        .BtnExport:hover{
            transform:translateY(-2px);
        }

        .btn:active,
        .ActionBtn:active,
        .ExportIcon:active,
        .BotonAccion:active,
        .BtnExport:active{
            transform:translateY(0) scale(.98);
        }

        .form-control,
        .form-select,
        textarea,
        input[type="file"]{
            transition:border-color .20s ease, box-shadow .20s ease, transform .20s ease, background .20s ease;
        }

        .form-control:hover,
        .form-select:hover,
        textarea:hover,
        input[type="file"]:hover{
            transform:translateY(-1px);
            border-color:rgba(122,8,24,.35) !important;
        }

        .form-control:focus,
        .form-select:focus,
        textarea:focus{
            transform:translateY(-1px);
        }

        .table tbody tr{
            transition:transform .20s var(--AnimacionSuave), box-shadow .20s var(--AnimacionSuave), background .20s ease;
        }

        .table tbody tr:hover{
            transform:translateY(-2px) scale(1.002);
        }

        .badge{
            transition:transform .20s ease, box-shadow .20s ease;
        }

        .badge:hover{
            transform:translateY(-1px);
            box-shadow:0 8px 18px rgba(15,23,42,.10);
        }

        .AutoHideAlert{
            animation:EntradaSuave .35s var(--AnimacionSuave) both;
        }

        .PageFadeIn{
            animation:EntradaSuave .40s var(--AnimacionSuave) both;
        }

        /* ================= RESPONSIVE GENERAL ================= */
        img,
        svg,
        video{
            max-width:100%;
            height:auto;
        }

        .table-responsive{
            -webkit-overflow-scrolling:touch;
        }

        @media (max-width:1200px){
            .container,
            .container-fluid{
                max-width:100%;
            }
        }

        @media (max-width:992px){
            .navbar-brand{
                font-size:1.05rem !important;
                white-space:normal;
            }

            .nav-tabs{
                display:flex;
                flex-wrap:wrap;
                gap:10px;
            }

            .nav-tabs .nav-item,
            .nav-tabs .nav-link{
                flex:1 1 180px;
                text-align:center;
            }

            .d-flex.justify-content-between.align-items-center,
            .d-flex.justify-content-between.align-items-end,
            .d-flex.align-items-center.justify-content-between{
                gap:14px;
                flex-wrap:wrap;
            }

            .search-container,
            .input-group.search-container,
            .w-25,
            .w-50{
                width:100% !important;
                max-width:100% !important;
            }

            .TopBar,
            .TopHeader{
                padding:22px !important;
            }

            .TopBar h2,
            .TopHeader h2,
            .TituloSistema{
                font-size:clamp(1.45rem, 6vw, 2.3rem) !important;
            }
        }

        @media (max-width:768px){
            body{
                font-size:.95rem;
            }

            .container,
            .container-fluid{
                padding-left:14px !important;
                padding-right:14px !important;
            }

            .navbar .container-fluid{
                gap:12px;
            }

            .navbar .btn,
            .navbar a.btn{
                width:100%;
                justify-content:center;
            }

            .card-body,
            .modal-body{
                padding:18px !important;
            }

            .card-header,
            .card-header-custom{
                padding:16px 18px !important;
            }

            .row{
                --bs-gutter-x:1rem;
                --bs-gutter-y:1rem;
            }

            form.row > [class*="col-"],
            .row > [class*="col-md-"],
            .row > [class*="col-lg-"],
            .row > [class*="col-xl-"]{
                flex:0 0 100%;
                max-width:100%;
            }

            .form-control,
            .form-select,
            .btn{
                min-height:46px;
            }

            .table{
                min-width:760px;
            }

            .table thead th,
            .table tbody td{
                white-space:nowrap;
                padding:12px 10px !important;
            }

            .ActionBtn{
                min-width:98px;
            }

            .ExportIcons,
            .AdminActions{
                gap:6px;
            }

            .modal-dialog{
                margin:12px;
            }
        }

        @media (max-width:576px){
            .nav-tabs .nav-item,
            .nav-tabs .nav-link{
                flex:1 1 100%;
            }

            .btn,
            .ActionBtn,
            .BotonAccion,
            .BtnExport{
                width:100%;
                justify-content:center;
            }

            .ExportIcon{
                width:44px;
                height:40px;
            }

            .TopBar,
            .TopHeader,
            .card,
            .card-custom,
            .MainCard,
            .GridLogin{
                border-radius:18px !important;
            }

            .HeaderIcon,
            .IconBox,
            .LoginIcon,
            .MateriaIcon{
                width:58px !important;
                height:58px !important;
                border-radius:16px !important;
                font-size:1.45rem !important;
            }
        }

        @media (prefers-reduced-motion:reduce){
            *,
            *::before,
            *::after{
                animation:none !important;
                transition:none !important;
                scroll-behavior:auto !important;
            }
        }

    

        /* ==========================================================
           BOTONES CON EFECTO DE RELLENO EN HOVER
           En todas las páginas los botones con borde conservan su color
           y al pasar el mouse se rellenan con ese mismo color.
           ========================================================== */
        .btn-outline-primary,
        .btn-outline-success,
        .btn-outline-danger,
        .btn-outline-secondary{
            background:#FFFFFF !important;
            border-width:2px !important;
            box-shadow:0 0 0 3px rgba(15,23,42,.04), 0 7px 18px rgba(15,23,42,.06) !important;
            transition:all .18s ease !important;
        }

        .btn-outline-primary:hover{
            background:#2563EB !important;
            border-color:#2563EB !important;
            color:#FFFFFF !important;
        }

        .btn-outline-success:hover{
            background:#16A34A !important;
            border-color:#16A34A !important;
            color:#FFFFFF !important;
        }

        .btn-outline-danger:hover{
            background:#DC2626 !important;
            border-color:#DC2626 !important;
            color:#FFFFFF !important;
        }

        .btn-outline-secondary:hover{
            background:#6B7280 !important;
            border-color:#6B7280 !important;
            color:#FFFFFF !important;
        }

    

        /* ==========================================================
           EFECTO HOMOLOGADO: BORDE + RELLENO AL PASAR EL MOUSE
           ----------------------------------------------------------
           Este bloque lo agregué para que todos los botones de acción
           y exportación tengan el mismo comportamiento visual:
           primero se ven blancos con borde de color y, al pasar el mouse,
           se rellenan con el color de su borde.
           ========================================================== */
        .ExportIcon,
        .BtnExport,
        .ActionBtn{
            background:#FFFFFF !important;
            border:2px solid currentColor !important;
            transition:all .22s ease !important;
            position:relative;
            overflow:hidden;
        }

        .ExportIcon i,
        .ExportIcon span,
        .BtnExport i,
        .BtnExport span,
        .ActionBtn i,
        .ActionBtn span{
            position:relative;
            z-index:2;
        }

        .ExportIcon:hover,
        .BtnExport:hover,
        .ActionBtn:hover{
            transform:translateY(-2px) !important;
            box-shadow:0 12px 26px rgba(15,23,42,.16) !important;
        }

        /* Colores para exportaciones en tablas de administración */
        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas){
            color:#16A34A !important;
            border-color:#16A34A !important;
            box-shadow:0 0 0 3px rgba(22,163,74,.08), 0 6px 16px rgba(22,163,74,.08) !important;
        }

        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas):hover{
            background:#16A34A !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas){
            color:#DC2626 !important;
            border-color:#DC2626 !important;
            box-shadow:0 0 0 3px rgba(220,38,38,.08), 0 6px 16px rgba(220,38,38,.08) !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas):hover{
            background:#DC2626 !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportExcel.ExportHoy{
            color:#F59E0B !important;
            border-color:#F59E0B !important;
            box-shadow:0 0 0 3px rgba(245,158,11,.11), 0 6px 16px rgba(245,158,11,.10) !important;
        }

        .ExportIcon.ExportExcel.ExportHoy:hover{
            background:#F59E0B !important;
            color:#111827 !important;
        }

        .ExportIcon.ExportPdf.ExportHoy{
            color:#D97706 !important;
            border-color:#D97706 !important;
            box-shadow:0 0 0 3px rgba(217,119,6,.11), 0 6px 16px rgba(217,119,6,.10) !important;
        }

        .ExportIcon.ExportPdf.ExportHoy:hover{
            background:#D97706 !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportExcel.ExportTodas{
            color:#2563EB !important;
            border-color:#2563EB !important;
            box-shadow:0 0 0 3px rgba(37,99,235,.10), 0 6px 16px rgba(37,99,235,.10) !important;
        }

        .ExportIcon.ExportExcel.ExportTodas:hover{
            background:#2563EB !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportPdf.ExportTodas{
            color:#7C3AED !important;
            border-color:#7C3AED !important;
            box-shadow:0 0 0 3px rgba(124,58,237,.10), 0 6px 16px rgba(124,58,237,.10) !important;
        }

        .ExportIcon.ExportPdf.ExportTodas:hover{
            background:#7C3AED !important;
            color:#FFFFFF !important;
        }

        /* Colores para botones de editar y eliminar dentro de tablas */
        .ActionBtn.ActionEdit{
            color:#2563EB !important;
            border-color:#2563EB !important;
            box-shadow:0 0 0 3px rgba(37,99,235,.08), 0 6px 16px rgba(37,99,235,.08) !important;
        }

        .ActionBtn.ActionEdit:hover{
            background:#2563EB !important;
            color:#FFFFFF !important;
        }

        .ActionBtn.ActionDelete{
            color:#DC2626 !important;
            border-color:#DC2626 !important;
            box-shadow:0 0 0 3px rgba(220,38,38,.08), 0 6px 16px rgba(220,38,38,.08) !important;
        }

        .ActionBtn.ActionDelete:hover{
            background:#DC2626 !important;
            color:#FFFFFF !important;
        }

        /* Colores para exportaciones del portal docente */
        .BtnExport.ExportCalifExcel{
            color:#16A34A !important;
            border-color:#16A34A !important;
            box-shadow:0 0 0 3px rgba(22,163,74,.08), 0 6px 16px rgba(22,163,74,.08) !important;
        }

        .BtnExport.ExportCalifExcel:hover{
            background:#16A34A !important;
            color:#FFFFFF !important;
        }

        .BtnExport.ExportCalifPdf{
            color:#DC2626 !important;
            border-color:#DC2626 !important;
            box-shadow:0 0 0 3px rgba(220,38,38,.08), 0 6px 16px rgba(220,38,38,.08) !important;
        }

        .BtnExport.ExportCalifPdf:hover{
            background:#DC2626 !important;
            color:#FFFFFF !important;
        }

        .BtnExport.ExportAsisExcel{
            color:#F59E0B !important;
            border-color:#F59E0B !important;
            box-shadow:0 0 0 3px rgba(245,158,11,.10), 0 6px 16px rgba(245,158,11,.10) !important;
        }

        .BtnExport.ExportAsisExcel:hover{
            background:#F59E0B !important;
            color:#111827 !important;
        }

        .BtnExport.ExportAsisPdf{
            color:#2563EB !important;
            border-color:#2563EB !important;
            box-shadow:0 0 0 3px rgba(37,99,235,.10), 0 6px 16px rgba(37,99,235,.10) !important;
        }

        .BtnExport.ExportAsisPdf:hover{
            background:#2563EB !important;
            color:#FFFFFF !important;
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

</style>    <style>
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

</head><body>
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
        

<!-- ============================================================
     NOTIFICACIONES AUTOMÁTICAS DEL SISTEMA
     ------------------------------------------------------------
     Este bloque lo uso para homologar todas las notificaciones.
     Cualquier alerta puede cerrarse manualmente con la tachita y,
     si el usuario no la cierra, desaparece sola después de unos segundos.
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    function OcultarNotificacion(Alerta) {
        if (!Alerta || Alerta.dataset.Ocultando === '1') {
            return;
        }

        Alerta.dataset.Ocultando = '1';
        Alerta.style.transition = 'opacity .45s ease, transform .45s ease, max-height .45s ease, margin .45s ease, padding .45s ease';
        Alerta.style.opacity = '0';
        Alerta.style.transform = 'translateY(-12px)';
        Alerta.style.maxHeight = '0';
        Alerta.style.marginTop = '0';
        Alerta.style.marginBottom = '0';
        Alerta.style.paddingTop = '0';
        Alerta.style.paddingBottom = '0';

        setTimeout(function() {
            Alerta.remove();
        }, 500);
    }

    function PrepararNotificacion(Alerta) {
        if (!Alerta || Alerta.dataset.NotificacionPreparada === '1') {
            return;
        }

        Alerta.dataset.NotificacionPreparada = '1';
        Alerta.classList.add('alert-dismissible', 'fade', 'show');
        Alerta.style.position = 'relative';

        let BotonCerrar = Alerta.querySelector('.btn-close');

        if (!BotonCerrar) {
            BotonCerrar = document.createElement('button');
            BotonCerrar.type = 'button';
            BotonCerrar.className = 'btn-close';
            BotonCerrar.setAttribute('aria-label', 'CERRAR');
            Alerta.appendChild(BotonCerrar);
        }

        BotonCerrar.addEventListener('click', function(Evento) {
            Evento.preventDefault();
            OcultarNotificacion(Alerta);
        });

        function ProgramarAutoCierre() {
            if (!Alerta.classList.contains('d-none') && Alerta.dataset.AutoCierreProgramado !== '1') {
                Alerta.dataset.AutoCierreProgramado = '1';

                setTimeout(function() {
                    OcultarNotificacion(Alerta);
                }, 4500);
            }
        }

        ProgramarAutoCierre();

        const Observador = new MutationObserver(function() {
            ProgramarAutoCierre();
        });

        Observador.observe(Alerta, {
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    }

    document.querySelectorAll('.alert').forEach(function(Alerta) {
        PrepararNotificacion(Alerta);
    });

    const ObservadorBody = new MutationObserver(function(Mutaciones) {
        Mutaciones.forEach(function(Mutacion) {
            Mutacion.addedNodes.forEach(function(Nodo) {
                if (Nodo.nodeType !== 1) {
                    return;
                }

                if (Nodo.classList && Nodo.classList.contains('alert')) {
                    PrepararNotificacion(Nodo);
                }

                if (Nodo.querySelectorAll) {
                    Nodo.querySelectorAll('.alert').forEach(function(Alerta) {
                        PrepararNotificacion(Alerta);
                    });
                }
            });
        });
    });

    ObservadorBody.observe(document.body, {
        childList: true,
        subtree: true
    });

});
</script>




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

</body></html>
        <?php exit;
    }

    if ($Tipo === 'Pdf') {
        ?>
        <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= htmlspecialchars($TituloArchivo, ENT_QUOTES, 'UTF-8') ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>@page{size:letter;margin:1.5cm;} body{font-family:'Segoe UI',sans-serif;color:#333;font-size:12px;} .NoPrint{background:#F8F9FA;border:1px solid #DDD;} .HeaderReporte{border-bottom:4px solid #7A0818;padding-bottom:12px;margin-bottom:20px;} .HeaderReporte h2{color:#7A0818;font-weight:800;margin:0;} .HeaderReporte h5{color:#666;margin-top:5px;text-transform:uppercase;} .TableReporte{width:100%;border-collapse:collapse;} .TableReporte th{background:#7A0818;color:white;padding:10px;border:1px solid #CCC;text-transform:uppercase;font-size:11px;} .TableReporte td{border:1px solid #DDD;padding:8px;} .TableReporte tbody tr:nth-child(even){background:#F8F9FA;} .Firma{margin-top:60px;text-align:center;} .FirmaLinea{width:260px;margin:auto;border-top:1px solid #333;padding-top:5px;} @media print{.NoPrint{display:none;} .TableReporte th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} .TableReporte tbody tr:nth-child(even){background:#F8F9FA!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}

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
        </head><body>
        <div class="NoPrint p-3 rounded mb-4 d-flex justify-content-between align-items-center"><div><i class="fa-solid fa-eye"></i> Vista Preliminar</div><button onclick="window.print()" class="btn btn-danger btn-sm rounded-pill fw-bold px-4"><i class="fa-solid fa-print"></i> Imprimir / Guardar PDF</button></div>
        <div class="HeaderReporte d-flex justify-content-between align-items-end"><div><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte Oficial De Calificaciones</h5></div><div class="text-end"><div><strong>Materia:</strong> <?= htmlspecialchars($Info['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?></div><div><strong>Grupo:</strong> <?= htmlspecialchars($Info['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Info['Grupo'], ENT_QUOTES, 'UTF-8') ?>"</div><div><strong>Turno:</strong> <?= htmlspecialchars($Info['Turno'], ENT_QUOTES, 'UTF-8') ?></div><div><strong>Docente:</strong> <?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></div></div></div>
        <table class="TableReporte"><thead><tr><th style="width:8%;">No.</th><th>Nombre Del Alumno</th><th style="width:18%;">Calificación</th></tr></thead><tbody>
        <?php $Numero = 1; foreach($ListaAlumnos as $Al): ?>
            <tr><td align="center"><?= $Numero++ ?></td><td><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><td align="center"><strong><?= FormatoCalificacion($Al['Calificacion']) ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <div class="Firma"><div class="FirmaLinea"><strong><?= htmlspecialchars($Info['Maestro'], ENT_QUOTES, 'UTF-8') ?></strong><br>Firma Del Docente</div></div>
        <script>window.onload=function(){setTimeout(function(){window.focus(); window.print();},300);}</script>
        </body></html>
        <?php exit;
    }
}

/*
|--------------------------------------------------------------------------
| MODO GRUPO: reporte de todas las materias/asignaciones del grupo
|--------------------------------------------------------------------------
*/
$StmtGrupo = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ? AND Activo = 1");
$StmtGrupo->execute([$GrupoId]);
$InfoGrupo = $StmtGrupo->fetch();
if (!$InfoGrupo) { die("Grupo No Disponible."); }

// Los reportes completos por grupo solo deben ser exportados por administración.
if (!SgcePuedeAdministrarReportes($UserSession)) {
    die("No Tienes Permiso.");
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

$StmtAlumnos = $Pdo->prepare("SELECT Id, NombreCompleto FROM Alumnos WHERE GrupoId = ? AND Activo = 1 ORDER BY NombreCompleto ASC");
$StmtAlumnos->execute([$GrupoId]);
$ListaAlumnos = $StmtAlumnos->fetchAll();

$StmtCal = $Pdo->prepare("
    SELECT C.AlumnoId, C.AsignacionId, C.Calificacion
    FROM Calificaciones C
    JOIN Asignaciones A ON C.AsignacionId = A.Id
    WHERE A.GrupoId = ?
    AND C.PeriodoId = ?
");
$StmtCal->execute([$GrupoId, $PeriodoId]);
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
    <!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title><?= htmlspecialchars($TituloArchivo, ENT_QUOTES, 'UTF-8') ?></title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>@page{size:letter landscape;margin:1cm;} body{font-family:'Segoe UI',sans-serif;color:#333;font-size:11px;} .NoPrint{background:#F8F9FA;border:1px solid #DDD;} .Header{border-bottom:4px solid #7A0818;padding-bottom:12px;margin-bottom:15px;} .Header h2{color:#7A0818;font-weight:800;margin:0;} table{width:100%;border-collapse:collapse;} th{background:#7A0818;color:white;padding:8px;border:1px solid #CCC;text-transform:uppercase;font-size:10px;} td{border:1px solid #DDD;padding:6px;} tbody tr:nth-child(even){background:#F8F9FA;} .Centro{text-align:center;font-weight:bold;} @media print{.NoPrint{display:none;} th{background:#7A0818!important;color:white!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;} tbody tr:nth-child(even){background:#F8F9FA!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}}

        /* ============================================================
           HOMOLOGACIÓN DE TEXTBOX Y SELECT EN MAYÚSCULAS
           ------------------------------------------------------------
           Esta sección solo afecta controles de captura: input, textarea
           y select. Los botones se dejan intactos porque no se pidieron
           cambios en botones.

           Nota importante: el text-transform convierte la vista a
           MAYÚSCULAS sin romper valores internos como contraseñas.
           Los campos password y file se excluyen para no alterar su uso.
        ============================================================ */
        input:not([type="password"]):not([type="file"]):not([type="hidden"]),
        textarea,
        select,
        select option{
            text-transform:uppercase !important;
        }

        input:not([type="password"]):not([type="file"]):not([type="hidden"])::placeholder,
        textarea::placeholder{
            text-transform:uppercase !important;
        }

        input[type="password"],
        input[type="file"]{
            text-transform:none !important;
        }

        .form-control,
        .form-select{
            letter-spacing:0.2px;
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
</style></head><body>
    <div class="NoPrint p-3 rounded mb-4 d-flex justify-content-between align-items-center"><div>Vista Preliminar</div><button onclick="window.print()" class="btn btn-danger btn-sm rounded-pill fw-bold px-4">Imprimir / Guardar PDF</button></div>
    <div class="Header d-flex justify-content-between"><div><h2>ESCUELA SECUNDARIA TÉCNICA 101</h2><h5>Reporte De Calificaciones Por Grupo</h5></div><div class="text-end"><strong>Grupo:</strong> <?= htmlspecialchars($InfoGrupo['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($InfoGrupo['Grupo'], ENT_QUOTES, 'UTF-8') ?>"<br><strong>Turno:</strong> <?= htmlspecialchars($InfoGrupo['Turno'], ENT_QUOTES, 'UTF-8') ?></div></div>
    <table><thead><tr><th style="width:40px;">No.</th><th>Alumno</th><?php foreach($ListaAsignaciones as $Asg): ?><th><?= htmlspecialchars($Asg['MateriaNombre'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($Asg['Maestro'], ENT_QUOTES, 'UTF-8') ?></small></th><?php endforeach; ?></tr></thead><tbody>
    <?php $N=1; foreach($ListaAlumnos as $Al): ?>
        <tr><td class="Centro"><?= $N++ ?></td><td><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td><?php foreach($ListaAsignaciones as $Asg): ?><td class="Centro"><?= FormatoCalificacion($MapaCalificaciones[(int)$Al['Id']][(int)$Asg['Id']] ?? null) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
    </tbody></table>
    <script>window.onload=function(){setTimeout(function(){window.focus(); window.print();},300);}

    // ============================================================
    // HOMOLOGAR TEXTOS POR DEFECTO EN MAYÚSCULAS
    // ------------------------------------------------------------
    // Aquí dejo en mayúsculas los placeholders y el texto visible de
    // las opciones de los select. No modifico los valores internos de
    // los option para no romper validaciones como Matutino/Vespertino.
    // Tampoco toco passwords ni archivos.
    // ============================================================
    document.querySelectorAll('input:not([type="password"]):not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }
    });

    document.querySelectorAll('select option').forEach(function(Opcion){
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });
</script>
    </body></html>
    <?php exit;
}

die("Tipo de exportación inválido.");