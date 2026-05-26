<?php

/*
    Archivo: Maestro.php
    Descripción: Portal del docente.
    Muestra las materias asignadas al profesor y ofrece accesos rápidos para calificar, pasar asistencia y exportar reportes.
*/

require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'maestro') {
    header('Location: index.php');
    exit;
}

$Stmt = $Pdo->prepare("
    SELECT A.Id AS AsignacionId, 
           G.Grado, 
           G.Grupo, 
           G.Turno, 
           A.MateriaNombre 
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    WHERE A.MaestroId = ?
    AND A.Activo = 1
    AND G.Activo = 1
    ORDER BY G.Turno, G.Grado, G.Grupo ASC
");

$Stmt->execute([$UserSession['Id']]);
$MisClases = $Stmt->fetchAll();
$TotalClases = count($MisClases);
$StmtStatsMaestro = $Pdo->prepare("SELECT COUNT(*) FROM Asistencias Asi JOIN Asignaciones A ON Asi.AsignacionId = A.Id WHERE A.MaestroId = ? AND Asi.FechaDia = CURDATE()");
$StmtStatsMaestro->execute([$UserSession['Id']]);
$AsistenciasHoyMaestro = (int)$StmtStatsMaestro->fetchColumn();

// Cargo avisos activos dirigidos a maestros o a todo el sistema.
$StmtAvisosMaestro = $Pdo->query("SELECT Titulo, Mensaje, FechaCreacion FROM Avisos WHERE Activo = 1 AND Publico IN ('TODOS','MAESTROS') ORDER BY FechaCreacion DESC LIMIT 3");
$AvisosMaestro = $StmtAvisosMaestro ? $StmtAvisosMaestro->fetchAll() : [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    
    <!-- FAVICON DEL SISTEMA: ICONO QUE APARECE EN LA PESTAÑA DEL NAVEGADOR -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<title>EST 101 - Portal Docente</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>

        body{
            background:#EEF2F7;
            font-family:'Segoe UI', sans-serif;
        }

        .NavbarMaestro{
            background:linear-gradient(90deg,#7A0818,#A10D26);
        }

        .TituloPagina{
            color:#7A0818;
            font-weight:700;
        }

        .CardClase{
            border:none;
            border-radius:20px;
            overflow:hidden;
            transition:0.25s;
            box-shadow:0 5px 18px rgba(0,0,0,0.08);
        }

        .CardClase:hover{
            transform:translateY(-5px);
            box-shadow:0 10px 25px rgba(0,0,0,0.12);
        }

        .CardHeader{
            background:linear-gradient(135deg,#7A0818,#A10D26);
            color:white;
            padding:20px;
        }

        .MateriaIcon{
            width:65px;
            height:65px;
            border-radius:50%;
            background:rgba(255,255,255,0.15);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:28px;
        }

        .InfoGrupo{
            background:#F8F9FA;
            border-radius:12px;
            padding:12px;
        }

        .BotonAccion{
            border-radius:12px;
            padding:12px;
            font-weight:600;
            transition:0.2s;
        }

        .BotonAccion:hover{
            transform:scale(1.02);
        }

        .BtnCalificaciones{
            background:#7A0818;
            color:white;
        }

        .BtnCalificaciones:hover{
            background:#5B0612;
            color:white;
        }

        .BtnAsistencia{
            background:#0EA5E9;
            color:white;
        }

        .BtnAsistencia:hover{
            background:#0284C7;
            color:white;
        }

        .SeccionExportar{
            background:#F8FAFC;
            border-radius:14px;
            padding:12px;
        }

        .BtnExport{
            border-radius:10px;
            font-size:14px;
            font-weight:600;
        }

        .BadgeTurno{
            font-size:12px;
            padding:8px 12px;
            border-radius:30px;
        }

    

        /* ==========================================================
           SGCE DESIGN SYSTEM HOMOLOGADO
           ========================================================== */
        :root{
            --SgceGuinda:#7A0818;
            --SgceGuinda2:#A10D26;
            --SgceAzul:#2563EB;
            --SgceVerde:#16A34A;
            --SgceRojo:#DC2626;
            --SgceAmarillo:#F59E0B;
            --SgceFondo:#EEF2F7;
            --SgceCard:#FFFFFF;
            --SgceTexto:#1F2937;
            --SgceMuted:#6B7280;
            --SgceBorde:#E5E7EB;
        }

        body{
            background:
                radial-gradient(circle at top left, rgba(122,8,24,.08), transparent 34%),
                linear-gradient(to bottom,#F8FAFC,#EEF2F7) !important;
            color:var(--SgceTexto) !important;
            font-family:'Poppins','Segoe UI',sans-serif !important;
            min-height:100vh;
        }

        .navbar,
        .navbar-custom,
        .NavbarMaestro,
        .TopBar,
        .TopHeader{
            background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;
            box-shadow:0 12px 32px rgba(122,8,24,.22) !important;
        }

        .card,
        .card-custom,
        .MainCard,
        .StatsCard,
        .CardClase,
        .LoginCard,
        .PanelDerecho{
            border:0 !important;
            border-radius:26px !important;
            box-shadow:0 12px 35px rgba(15,23,42,.07) !important;
        }

        .card-header,
        .card-header-custom,
        .CardHeader{
            border-bottom:1px solid #F1F5F9 !important;
        }

        .form-control,
        .form-select,
        input[type="file"]{
            border:2px solid var(--SgceBorde) !important;
            border-radius:16px !important;
            min-height:48px !important;
            padding:12px 14px !important;
            box-shadow:none !important;
            transition:.18s ease !important;
            background-color:#FFFFFF !important;
        }

        .form-control:focus,
        .form-select:focus{
            border-color:var(--SgceGuinda) !important;
            box-shadow:0 0 0 4px rgba(122,8,24,.10) !important;
        }

        label,
        .form-label{
            font-weight:800 !important;
            color:var(--SgceMuted) !important;
            margin-bottom:7px !important;
        }

        .btn,
        .ActionBtn,
        .BotonAccion,
        .BtnExport,
        .BtnGuardar,
        .BtnBack,
        .BtnLogin{
            min-height:42px;
            border-radius:999px !important;
            font-weight:800 !important;
            display:inline-flex !important;
            align-items:center !important;
            justify-content:center !important;
            gap:8px !important;
            letter-spacing:.1px;
            transition:.18s ease !important;
            text-decoration:none !important;
        }

        .btn:hover,
        .ActionBtn:hover,
        .BotonAccion:hover,
        .BtnExport:hover,
        .BtnGuardar:hover,
        .BtnBack:hover,
        .BtnLogin:hover{
            transform:translateY(-2px) !important;
            box-shadow:0 12px 24px rgba(15,23,42,.13) !important;
        }

        .btn-guinda,
        .BtnGuardar,
        .BtnLogin,
        .BtnCalificaciones{
            background:linear-gradient(135deg,var(--SgceGuinda),var(--SgceGuinda2)) !important;
            color:white !important;
            border:none !important;
        }

        .btn-primary,
        .BtnAsistencia{
            background:linear-gradient(135deg,#2563EB,#0EA5E9) !important;
            border:none !important;
            color:white !important;
        }

        .btn-success{
            background:linear-gradient(135deg,#16A34A,#22C55E) !important;
            border:none !important;
            color:white !important;
        }

        .btn-warning{
            background:linear-gradient(135deg,#F59E0B,#FBBF24) !important;
            border:none !important;
            color:#111827 !important;
        }

        .btn-dark{
            background:linear-gradient(135deg,#111827,#374151) !important;
            border:none !important;
            color:white !important;
        }

        .btn-outline-success,
        .BtnExport.btn-outline-success{
            border:2px solid var(--SgceVerde) !important;
            color:var(--SgceVerde) !important;
            background:white !important;
        }

        .btn-outline-success:hover,
        .BtnExport.btn-outline-success:hover{
            background:var(--SgceVerde) !important;
            color:white !important;
        }

        .btn-outline-danger,
        .BtnExport.btn-outline-danger{
            border:2px solid var(--SgceRojo) !important;
            color:var(--SgceRojo) !important;
            background:white !important;
        }

        .btn-outline-danger:hover,
        .BtnExport.btn-outline-danger:hover{
            background:var(--SgceRojo) !important;
            color:white !important;
        }

        .btn-outline-primary{
            border:2px solid var(--SgceAzul) !important;
            color:var(--SgceAzul) !important;
            background:white !important;
        }

        .btn-outline-primary:hover{
            background:var(--SgceAzul) !important;
            color:white !important;
        }

        .table{
            border-collapse:separate !important;
            border-spacing:0 10px !important;
        }

        .table thead th{
            background:#F8FAFC !important;
            color:var(--SgceMuted) !important;
            border:0 !important;
            text-transform:uppercase;
            letter-spacing:.5px;
            font-weight:900 !important;
            font-size:.78rem;
            text-align:center !important;
            vertical-align:middle !important;
        }

        .table tbody tr{
            background:white !important;
            box-shadow:0 7px 18px rgba(15,23,42,.05) !important;
            border-radius:18px !important;
            transition:.18s ease !important;
        }

        .table tbody tr:hover{
            transform:translateY(-2px) !important;
            box-shadow:0 12px 26px rgba(15,23,42,.09) !important;
        }

        .table td{
            border:0 !important;
            padding:16px 14px !important;
            vertical-align:middle !important;
            text-align:center;
        }

        .badge{
            border-radius:999px !important;
            padding:9px 14px !important;
            font-weight:900 !important;
        }

        .alert{
            border:0 !important;
            border-radius:20px !important;
            box-shadow:0 10px 25px rgba(15,23,42,.08) !important;
        }

        .search-container{
            border:2px solid var(--SgceBorde) !important;
            border-radius:18px !important;
            background:white !important;
            overflow:hidden !important;
        }

        .search-container .input-group-text,
        .input-group-text{
            background:white !important;
            border:0 !important;
            color:#9CA3AF !important;
        }

        .search-container .form-control{
            border:0 !important;
            min-height:44px !important;
        }

        .modal-content{
            border:0 !important;
            border-radius:28px !important;
            box-shadow:0 25px 60px rgba(15,23,42,.25) !important;
        }

        .modal-body{
            padding:28px !important;
        }

        .IconCircle,
        .HeaderIcon,
        .IconBox,
        .LoginIcon,
        .MateriaIcon,
        .StatsIcon,
        .AlumnoAvatar{
            box-shadow:inset 0 1px 0 rgba(255,255,255,.18), 0 10px 24px rgba(15,23,42,.10);
        }

        @media(max-width:768px){
            .d-flex.justify-content-between.align-items-center{
                gap:14px;
                flex-direction:column;
                align-items:stretch !important;
            }
            .w-50,.w-25{width:100% !important;}
            .table-responsive{border-radius:20px;}
        }



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
           BOTONES DE EXPORTACIÓN DEL PORTAL DOCENTE
           Restauro los colores y bordes de cada botón para que no
           se vean planos o negros. Cada acción tiene su propio color.
           ========================================================== */
        .SeccionExportar .BtnExport{
            background:#FFFFFF !important;
            border:2px solid currentColor !important;
            box-shadow:0 5px 14px rgba(15,23,42,.06) !important;
            color:#111827 !important;
        }

        .SeccionExportar .BtnExport i{
            font-size:1rem;
        }

        .SeccionExportar .ExportCalifExcel{
            color:#16A34A !important;
            border-color:#16A34A !important;
            box-shadow:0 0 0 3px rgba(22,163,74,.08), 0 7px 18px rgba(22,163,74,.10) !important;
        }

        .SeccionExportar .ExportCalifExcel:hover{
            background:linear-gradient(135deg,#16A34A,#22C55E) !important;
            color:#FFFFFF !important;
            border-color:#16A34A !important;
        }

        .SeccionExportar .ExportCalifPdf{
            color:#DC2626 !important;
            border-color:#DC2626 !important;
            box-shadow:0 0 0 3px rgba(220,38,38,.08), 0 7px 18px rgba(220,38,38,.10) !important;
        }

        .SeccionExportar .ExportCalifPdf:hover{
            background:linear-gradient(135deg,#DC2626,#EF4444) !important;
            color:#FFFFFF !important;
            border-color:#DC2626 !important;
        }

        .SeccionExportar .ExportAsisExcel{
            color:#F59E0B !important;
            border-color:#F59E0B !important;
            box-shadow:0 0 0 3px rgba(245,158,11,.10), 0 7px 18px rgba(245,158,11,.10) !important;
        }

        .SeccionExportar .ExportAsisExcel:hover{
            background:linear-gradient(135deg,#F59E0B,#FBBF24) !important;
            color:#111827 !important;
            border-color:#F59E0B !important;
        }

        .SeccionExportar .ExportAsisPdf{
            color:#2563EB !important;
            border-color:#2563EB !important;
            box-shadow:0 0 0 3px rgba(37,99,235,.10), 0 7px 18px rgba(37,99,235,.10) !important;
        }

        .SeccionExportar .ExportAsisPdf:hover{
            background:linear-gradient(135deg,#2563EB,#0EA5E9) !important;
            color:#FFFFFF !important;
            border-color:#2563EB !important;
        }

        .SeccionExportar .BtnExport:focus{
            outline:none !important;
            box-shadow:0 0 0 4px rgba(122,8,24,.12), 0 10px 24px rgba(15,23,42,.12) !important;
        }

        @media (max-width:575.98px){
            .SeccionExportar .BtnExport{
                min-height:46px;
                font-size:.82rem !important;
                padding-left:8px !important;
                padding-right:8px !important;
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

    

        /* ==========================================================
           CORRECCIÓN FINAL DEL PORTAL DOCENTE
           ----------------------------------------------------------
           Los botones del maestro quedan rellenos desde el inicio.
           Calificaciones usa guinda institucional; asistencia usa azul
           para distinguir la acción. Los botones de exportación quedan
           con colores sólidos y texto blanco para que sean legibles.
           ========================================================== */
        :root{
            --DocTinto:#7A0818;
            --DocTinto2:#A10D26;
            --DocTintoHover:#4A0410;
            --DocAzul:#1D4ED8;
            --DocAzulHover:#123A9C;
            --DocVerde:#15803D;
            --DocVerdeHover:#0F5F2A;
            --DocRojo:#B91C1C;
            --DocRojoHover:#7F1D1D;
            --DocNaranja:#C2410C;
            --DocNaranjaHover:#8F2E08;
            --DocMorado:#5B21B6;
            --DocMoradoHover:#3B0F82;
        }

        .BtnCalificaciones,
        .BotonAccion.BtnCalificaciones{
            background:linear-gradient(135deg,var(--DocTinto),var(--DocTinto2)) !important;
            border:2px solid var(--DocTinto) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(122,8,24,.22) !important;
        }

        .BtnCalificaciones:hover,
        .BotonAccion.BtnCalificaciones:hover{
            background:linear-gradient(135deg,var(--DocTintoHover),var(--DocTinto)) !important;
            border-color:var(--DocTintoHover) !important;
            color:#FFFFFF !important;
        }

        .BtnAsistencia,
        .BotonAccion.BtnAsistencia{
            background:linear-gradient(135deg,var(--DocAzul),#0EA5E9) !important;
            border:2px solid var(--DocAzul) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(29,78,216,.22) !important;
        }

        .BtnAsistencia:hover,
        .BotonAccion.BtnAsistencia:hover{
            background:linear-gradient(135deg,var(--DocAzulHover),var(--DocAzul)) !important;
            border-color:var(--DocAzulHover) !important;
            color:#FFFFFF !important;
        }

        .SeccionExportar .BtnExport,
        .BtnExport{
            color:#FFFFFF !important;
            border-width:2px !important;
            box-shadow:0 9px 20px rgba(15,23,42,.12) !important;
        }

        .SeccionExportar .BtnExport i,
        .SeccionExportar .BtnExport span,
        .BtnExport i,
        .BtnExport span{
            color:#FFFFFF !important;
        }

        .SeccionExportar .ExportCalifExcel,
        .BtnExport.ExportCalifExcel{
            background:linear-gradient(135deg,var(--DocVerde),#16A34A) !important;
            border-color:var(--DocVerde) !important;
        }

        .SeccionExportar .ExportCalifExcel:hover,
        .BtnExport.ExportCalifExcel:hover{
            background:linear-gradient(135deg,var(--DocVerdeHover),var(--DocVerde)) !important;
            border-color:var(--DocVerdeHover) !important;
        }

        .SeccionExportar .ExportCalifPdf,
        .BtnExport.ExportCalifPdf{
            background:linear-gradient(135deg,var(--DocRojo),#DC2626) !important;
            border-color:var(--DocRojo) !important;
        }

        .SeccionExportar .ExportCalifPdf:hover,
        .BtnExport.ExportCalifPdf:hover{
            background:linear-gradient(135deg,var(--DocRojoHover),var(--DocRojo)) !important;
            border-color:var(--DocRojoHover) !important;
        }

        .SeccionExportar .ExportAsisExcel,
        .BtnExport.ExportAsisExcel{
            background:linear-gradient(135deg,var(--DocNaranja),#EA580C) !important;
            border-color:var(--DocNaranja) !important;
        }

        .SeccionExportar .ExportAsisExcel:hover,
        .BtnExport.ExportAsisExcel:hover{
            background:linear-gradient(135deg,var(--DocNaranjaHover),var(--DocNaranja)) !important;
            border-color:var(--DocNaranjaHover) !important;
        }

        .SeccionExportar .ExportAsisPdf,
        .BtnExport.ExportAsisPdf{
            background:linear-gradient(135deg,var(--DocMorado),#7C3AED) !important;
            border-color:var(--DocMorado) !important;
        }

        .SeccionExportar .ExportAsisPdf:hover,
        .BtnExport.ExportAsisPdf:hover{
            background:linear-gradient(135deg,var(--DocMoradoHover),var(--DocMorado)) !important;
            border-color:var(--DocMoradoHover) !important;
        }

        .navbar .btn,
        .BtnBack,
        .btn:not(.btn-close):not(.navbar-toggler){
            color:#FFFFFF !important;
        }

    

        /* ==========================================================
           AJUSTE FINAL PORTAL DOCENTE - BOTONES RELLENOS
           Aquí dejo los accesos principales con colores claros y sólidos,
           evitando botones blancos para que se distingan mejor.
        ========================================================== */
        :root{
            --DocFinalTinto:#7A0818;
            --DocFinalTinto2:#A10D26;
            --DocFinalTintoHover:#4F050F;
            --DocFinalAzul:#1E40AF;
            --DocFinalAzul2:#0EA5E9;
            --DocFinalAzulHover:#172554;
            --DocFinalVerde:#166534;
            --DocFinalVerde2:#16A34A;
            --DocFinalRojo:#991B1B;
            --DocFinalRojo2:#DC2626;
            --DocFinalNaranja:#9A3412;
            --DocFinalNaranja2:#EA580C;
            --DocFinalMorado:#4C1D95;
            --DocFinalMorado2:#7C3AED;
        }

        .BtnCalificaciones,
        .BotonAccion.BtnCalificaciones{
            background:linear-gradient(135deg,var(--DocFinalTinto),var(--DocFinalTinto2)) !important;
            border:2px solid var(--DocFinalTinto) !important;
            color:#FFFFFF !important;
            box-shadow:0 11px 24px rgba(122,8,24,.25) !important;
        }

        .BtnCalificaciones:hover,
        .BotonAccion.BtnCalificaciones:hover{
            background:linear-gradient(135deg,var(--DocFinalTintoHover),var(--DocFinalTinto)) !important;
            border-color:var(--DocFinalTintoHover) !important;
            color:#FFFFFF !important;
        }

        .BtnAsistencia,
        .BotonAccion.BtnAsistencia{
            background:linear-gradient(135deg,var(--DocFinalAzul),var(--DocFinalAzul2)) !important;
            border:2px solid var(--DocFinalAzul) !important;
            color:#FFFFFF !important;
            box-shadow:0 11px 24px rgba(30,64,175,.25) !important;
        }

        .BtnAsistencia:hover,
        .BotonAccion.BtnAsistencia:hover{
            background:linear-gradient(135deg,var(--DocFinalAzulHover),var(--DocFinalAzul)) !important;
            border-color:var(--DocFinalAzulHover) !important;
            color:#FFFFFF !important;
        }

        .BotonAccion i,
        .BotonAccion span,
        .BtnExport i,
        .BtnExport span{
            color:#FFFFFF !important;
        }

        .SeccionExportar .BtnExport,
        .BtnExport{
            color:#FFFFFF !important;
            border-width:2px !important;
            font-weight:800 !important;
            box-shadow:0 10px 22px rgba(15,23,42,.16) !important;
        }

        .BtnExport.ExportCalifExcel{
            background:linear-gradient(135deg,var(--DocFinalVerde),var(--DocFinalVerde2)) !important;
            border-color:var(--DocFinalVerde) !important;
        }

        .BtnExport.ExportCalifPdf{
            background:linear-gradient(135deg,var(--DocFinalRojo),var(--DocFinalRojo2)) !important;
            border-color:var(--DocFinalRojo) !important;
        }

        .BtnExport.ExportAsisExcel{
            background:linear-gradient(135deg,var(--DocFinalNaranja),var(--DocFinalNaranja2)) !important;
            border-color:var(--DocFinalNaranja) !important;
        }

        .BtnExport.ExportAsisPdf{
            background:linear-gradient(135deg,var(--DocFinalMorado),var(--DocFinalMorado2)) !important;
            border-color:var(--DocFinalMorado) !important;
        }

        .BtnExport:hover{
            filter:brightness(.88) !important;
            color:#FFFFFF !important;
            transform:translateY(-2px) !important;
        }

        /* Mantengo cerrar sesión en blanco dentro del portal docente para contraste. */
        .NavbarMaestro a[href="Logout.php"],
        .NavbarMaestro .btn-outline-light[href="Logout.php"]{
            background:#FFFFFF !important;
            color:var(--DocFinalTinto) !important;
            border:2px solid #FFFFFF !important;
        }

        .NavbarMaestro a[href="Logout.php"] i,
        .NavbarMaestro .btn-outline-light[href="Logout.php"] i{
            color:var(--DocFinalTinto) !important;
        }

        .NavbarMaestro a[href="Logout.php"]:hover,
        .NavbarMaestro .btn-outline-light[href="Logout.php"]:hover{
            background:#F8FAFC !important;
            color:var(--DocFinalTintoHover) !important;
        }


        /* ==========================================================
           CORRECCIÓN DEFINITIVA MAESTRO.PHP - BOTONES RELLENOS
           ----------------------------------------------------------
           Este bloque va al final del CSS para ganar prioridad sobre
           reglas anteriores. Así evito que Bootstrap o estilos previos
           vuelvan a dejar los botones blancos con solo borde.
           ========================================================== */

        .CardClase .card-body a.btn.BotonAccion.BtnCalificaciones,
        .CardClase .card-body a.BotonAccion.BtnCalificaciones,
        a.BtnCalificaciones,
        .BtnCalificaciones{
            background:linear-gradient(135deg,#7A0818,#A10D26) !important;
            background-color:#7A0818 !important;
            border:2px solid #7A0818 !important;
            color:#FFFFFF !important;
            box-shadow:0 12px 26px rgba(122,8,24,.28) !important;
        }

        .CardClase .card-body a.btn.BotonAccion.BtnCalificaciones:hover,
        .CardClase .card-body a.BotonAccion.BtnCalificaciones:hover,
        a.BtnCalificaciones:hover,
        .BtnCalificaciones:hover{
            background:linear-gradient(135deg,#4F050F,#7A0818) !important;
            background-color:#4F050F !important;
            border-color:#4F050F !important;
            color:#FFFFFF !important;
            transform:translateY(-2px) !important;
            box-shadow:0 16px 34px rgba(122,8,24,.36) !important;
        }

        .CardClase .card-body a.btn.BotonAccion.BtnAsistencia,
        .CardClase .card-body a.BotonAccion.BtnAsistencia,
        a.BtnAsistencia,
        .BtnAsistencia{
            background:linear-gradient(135deg,#1E40AF,#0EA5E9) !important;
            background-color:#1E40AF !important;
            border:2px solid #1E40AF !important;
            color:#FFFFFF !important;
            box-shadow:0 12px 26px rgba(30,64,175,.28) !important;
        }

        .CardClase .card-body a.btn.BotonAccion.BtnAsistencia:hover,
        .CardClase .card-body a.BotonAccion.BtnAsistencia:hover,
        a.BtnAsistencia:hover,
        .BtnAsistencia:hover{
            background:linear-gradient(135deg,#172554,#1E40AF) !important;
            background-color:#172554 !important;
            border-color:#172554 !important;
            color:#FFFFFF !important;
            transform:translateY(-2px) !important;
            box-shadow:0 16px 34px rgba(30,64,175,.36) !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportCalifExcel,
        .SeccionExportar .BtnExport.ExportCalifExcel{
            background:linear-gradient(135deg,#166534,#16A34A) !important;
            background-color:#166534 !important;
            border:2px solid #166534 !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(22,101,52,.25) !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportCalifExcel:hover,
        .SeccionExportar .BtnExport.ExportCalifExcel:hover{
            background:linear-gradient(135deg,#0F3D22,#166534) !important;
            background-color:#0F3D22 !important;
            border-color:#0F3D22 !important;
            color:#FFFFFF !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportCalifPdf,
        .SeccionExportar .BtnExport.ExportCalifPdf{
            background:linear-gradient(135deg,#991B1B,#DC2626) !important;
            background-color:#991B1B !important;
            border:2px solid #991B1B !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(153,27,27,.25) !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportCalifPdf:hover,
        .SeccionExportar .BtnExport.ExportCalifPdf:hover{
            background:linear-gradient(135deg,#641111,#991B1B) !important;
            background-color:#641111 !important;
            border-color:#641111 !important;
            color:#FFFFFF !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportAsisExcel,
        .SeccionExportar .BtnExport.ExportAsisExcel{
            background:linear-gradient(135deg,#9A3412,#EA580C) !important;
            background-color:#9A3412 !important;
            border:2px solid #9A3412 !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(154,52,18,.25) !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportAsisExcel:hover,
        .SeccionExportar .BtnExport.ExportAsisExcel:hover{
            background:linear-gradient(135deg,#67230C,#9A3412) !important;
            background-color:#67230C !important;
            border-color:#67230C !important;
            color:#FFFFFF !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportAsisPdf,
        .SeccionExportar .BtnExport.ExportAsisPdf{
            background:linear-gradient(135deg,#4C1D95,#7C3AED) !important;
            background-color:#4C1D95 !important;
            border:2px solid #4C1D95 !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(76,29,149,.25) !important;
        }

        .SeccionExportar a.btn.BtnExport.ExportAsisPdf:hover,
        .SeccionExportar .BtnExport.ExportAsisPdf:hover{
            background:linear-gradient(135deg,#2E1065,#4C1D95) !important;
            background-color:#2E1065 !important;
            border-color:#2E1065 !important;
            color:#FFFFFF !important;
        }

        .CardClase .card-body a.btn.BotonAccion i,
        .CardClase .card-body a.btn.BotonAccion span,
        .SeccionExportar a.btn.BtnExport i,
        .SeccionExportar a.btn.BtnExport span{
            color:#FFFFFF !important;
        }

        .CardClase .card-body a.btn.BotonAccion,
        .SeccionExportar a.btn.BtnExport{
            min-height:46px !important;
            border-radius:999px !important;
            font-weight:900 !important;
            display:inline-flex !important;
            align-items:center !important;
            justify-content:center !important;
            gap:8px !important;
            text-decoration:none !important;
        }

        /* El único botón blanco en esta vista sigue siendo Cerrar Sesión. */
        .NavbarMaestro a[href="Logout.php"],
        .NavbarMaestro .btn-outline-light[href="Logout.php"]{
            background:#FFFFFF !important;
            background-color:#FFFFFF !important;
            color:#7A0818 !important;
            border:2px solid #FFFFFF !important;
            box-shadow:0 10px 22px rgba(15,23,42,.12) !important;
        }

        .NavbarMaestro a[href="Logout.php"] i,
        .NavbarMaestro .btn-outline-light[href="Logout.php"] i{
            color:#7A0818 !important;
        }

        .NavbarMaestro a[href="Logout.php"]:hover,
        .NavbarMaestro .btn-outline-light[href="Logout.php"]:hover{
            background:#F8FAFC !important;
            background-color:#F8FAFC !important;
            color:#4F050F !important;
            border-color:#FFFFFF !important;
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

<nav class="navbar navbar-dark NavbarMaestro shadow-sm mb-4">
    <div class="container-fluid px-4">

        <span class="navbar-brand fw-bold fs-4">
            <i class="fa-solid fa-school"></i>
            EST 101
            <span class="fw-light fs-6 ms-2">
                Portal Docente
            </span>
        </span>

        <a href="Logout.php" class="btn btn-outline-light rounded-pill px-4">
            <i class="fa-solid fa-power-off"></i>
            Cerrar Sesión
        </a>

    </div>
</nav>

<div class="container">

    <div class="mb-4">
        <h2 class="TituloPagina">
            <i class="fa-solid fa-chalkboard-user"></i>
            Bienvenido Profesor
        </h2>

        <p class="text-secondary fs-5">
            <?= htmlspecialchars($UserSession['NombreCompleto']) ?>
        </p>
    </div>

    <?php if(!empty($AvisosMaestro)): ?>
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold text-danger mb-3"><i class="fa-solid fa-bullhorn me-2"></i> AVISOS IMPORTANTES</h5>
            <div class="row g-3">
                <?php foreach($AvisosMaestro as $Aviso): ?>
                    <div class="col-md-4">
                        <div class="p-3 rounded-4 bg-light border h-100">
                            <div class="fw-bold text-dark"><?= htmlspecialchars($Aviso['Titulo']) ?></div>
                            <div class="small text-muted mb-2"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($Aviso['FechaCreacion']))) ?></div>
                            <div class="small"><?= htmlspecialchars($Aviso['Mensaje']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">

        <?php if(empty($MisClases)): ?>

            <div class="col-12">
                <div class="alert alert-info shadow-sm border-0 rounded-4 p-4">

                    <h5>
                        <i class="fa-solid fa-circle-info"></i>
                        Sin materias asignadas
                    </h5>

                    <p class="mb-0">
                        Actualmente no tiene materias vinculadas.
                    </p>

                </div>
            </div>

        <?php else: ?>

            <?php foreach($MisClases as $Clase): ?>

                <div class="col-lg-4 col-md-6 mb-4">

                    <div class="card CardClase h-100">

                        <div class="CardHeader">

                            <div class="d-flex justify-content-between align-items-start">

                                <div>

                                    <h4 class="fw-bold mb-2">
                                        <?= htmlspecialchars($Clase['MateriaNombre']) ?>
                                    </h4>

                                    <span class="BadgeTurno bg-light text-dark">

                                        <i class="fa-solid <?= strtoupper((string)$Clase['Turno']) === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>

                                        <?= $Clase['Turno'] ?>

                                    </span>

                                </div>

                                <div class="MateriaIcon">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>

                            </div>

                        </div>

                        <div class="card-body">

                            <div class="InfoGrupo mb-3">

                                <div class="d-flex justify-content-between">

                                    <div>
                                        <small class="text-muted">
                                            Grupo
                                        </small>

                                        <?php $TurnoClase = strtoupper((string)$Clase['Turno']); ?>
                                        <h5 class="mb-0 fw-bold">
                                            <span class="GrupoTurnoBadge <?= $TurnoClase === 'MATUTINO' ? 'GrupoTurnoMatutino' : 'GrupoTurnoVespertino' ?>">
                                                <i class="fa-solid <?= $TurnoClase === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>
                                                <?= htmlspecialchars($Clase['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Clase['Grupo'], ENT_QUOTES, 'UTF-8') ?>" - <?= htmlspecialchars($TurnoClase, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </h5>
                                    </div>

                                    <div class="text-end">

                                        <small class="text-muted">
                                            Acciones
                                        </small>

                                        <div>
                                            <i class="fa-solid fa-clipboard-check text-success"></i>
                                            <i class="fa-solid fa-file-lines text-primary"></i>
                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="d-grid gap-2">

                                <a href="Calificar.php?AsignacionId=<?= $Clase['AsignacionId'] ?>"
                                   class="btn BotonAccion BtnCalificaciones">

                                    <i class="fa-solid fa-file-pen"></i>
                                    Administrar Calificaciones

                                </a>

                                <a href="Asistencia.php?id=<?= $Clase['AsignacionId'] ?>"
                                   class="btn BotonAccion BtnAsistencia">

                                    <i class="fa-solid fa-user-check"></i>
                                    Control de Asistencia

                                </a>

                            </div>

                            <hr>

                            <div class="SeccionExportar">

                                <h6 class="fw-bold mb-3">
                                    <i class="fa-solid fa-download"></i>
                                    Exportaciones
                                </h6>

                                <div class="row g-2">

                                    <div class="col-6">

                                        <a href="ExportarCalificaciones.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel"
                                           class="btn BtnExport ExportCalifExcel w-100">

                                            <i class="fa-solid fa-file-excel"></i>
                                            Calif. Excel

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarCalificaciones.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf"
                                           target="_blank"
                                           class="btn BtnExport ExportCalifPdf w-100">

                                            <i class="fa-solid fa-file-pdf"></i>
                                            Calif. PDF

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Excel"
                                           class="btn BtnExport ExportAsisExcel w-100">

                                            <i class="fa-solid fa-table"></i>
                                            Asist. Excel

                                        </a>

                                    </div>

                                    <div class="col-6">

                                        <a href="ExportarAsistencia.php?AsignacionId=<?= $Clase['AsignacionId'] ?>&Tipo=Pdf"
                                           target="_blank"
                                           class="btn BtnExport ExportAsisPdf w-100">

                                            <i class="fa-solid fa-file-export"></i>
                                            Asist. PDF

                                        </a>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>



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


<script>
// =====================================================
// EFECTOS VISUALES LIGEROS
// Agrego una clase al cargar la página y preparo animaciones suaves.
// No afecta la lógica del sistema, solo mejora la experiencia visual.
// =====================================================
document.addEventListener('DOMContentLoaded', function(){
    document.body.classList.add('PageFadeIn');

    const Elementos = document.querySelectorAll('.card, .card-custom, .MainCard, .StatsCard, .CardClase, .alert, .TopBar, .TopHeader');

    if ('IntersectionObserver' in window) {
        const Observador = new IntersectionObserver(function(Entradas){
            Entradas.forEach(function(Entrada){
                if (Entrada.isIntersecting) {
                    Entrada.target.style.animationPlayState = 'running';
                    Observador.unobserve(Entrada.target);
                }
            });
        }, { threshold:0.08 });

        Elementos.forEach(function(Elemento, Indice){
            Elemento.style.animationDelay = Math.min(Indice * 0.035, 0.35) + 's';
            Observador.observe(Elemento);
        });
    }
});
</script>

</body>
</html>