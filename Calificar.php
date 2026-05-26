<?php

/*
    Archivo: Calificar.php
    Descripción: Módulo para capturar y actualizar calificaciones por alumno.
    Valida que el maestro solo pueda acceder a sus asignaciones y guarda notas de 0 a 10.
*/

require 'Conexion.php';

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || $UserSession['Rol'] !== 'maestro') {
    header('Location: index.php');
    exit;
}

$AsignacionId = intval($_GET['AsignacionId'] ?? 0);

$Stmt = $Pdo->prepare("
    SELECT A.*, G.Grado, G.Grupo, G.Turno
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    WHERE A.Id = ? AND A.MaestroId = ?
");

$Stmt->execute([$AsignacionId, $UserSession['Id']]);
$InfoClase = $Stmt->fetch();

if (!$InfoClase) {
    die("Acceso Denegado O Grupo No Encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['GuardarNotes'])) {

    $Notas = $_POST['Notas'] ?? [];

    if (!is_array($Notas)) {
        header("Location: Calificar.php?AsignacionId=$AsignacionId&Error=1");
        exit;
    }

    $StmtExiste = $Pdo->prepare("
        SELECT COUNT(*)
        FROM Alumnos
        WHERE Id = ?
        AND GrupoId = ?
    ");

    $StmtGuardar = $Pdo->prepare("
        INSERT INTO Calificaciones
        (AlumnoId, AsignacionId, Calificacion)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        Calificacion = VALUES(Calificacion)
    ");

    $StmtEliminar = $Pdo->prepare("
        DELETE FROM Calificaciones
        WHERE AlumnoId = ?
        AND AsignacionId = ?
    ");

    try {
        $Pdo->beginTransaction();

        foreach ($Notas as $AlumnoId => $Calificacion) {

            $AlumnoId = intval($AlumnoId);
            $Calificacion = trim((string)$Calificacion);

            if ($AlumnoId <= 0) {
                continue;
            }

            $StmtExiste->execute([$AlumnoId, $InfoClase['GrupoId']]);

            if ((int)$StmtExiste->fetchColumn() <= 0) {
                continue;
            }

            // Si se deja vacío, se elimina la calificación existente.
            if ($Calificacion === '') {
                $StmtEliminar->execute([$AlumnoId, $AsignacionId]);
                continue;
            }

            if (!is_numeric($Calificacion)) {
                continue;
            }

            $CalificacionFloat = round((float)$Calificacion, 2);

            if ($CalificacionFloat < 0) { $CalificacionFloat = 0; }
            if ($CalificacionFloat > 10) { $CalificacionFloat = 10; }

            $StmtGuardar->execute([
                $AlumnoId,
                $AsignacionId,
                $CalificacionFloat
            ]);
        }

        $Pdo->commit();

    } catch (Exception $E) {

        if ($Pdo->inTransaction()) {
            $Pdo->rollBack();
        }

        header("Location: Calificar.php?AsignacionId=$AsignacionId&Error=1");
        exit;
    }

    header("Location: Calificar.php?AsignacionId=$AsignacionId&Success=1");
    exit;
}

$Stmt = $Pdo->prepare("
    SELECT
        Al.Id AS AlumnoId,
        Al.NombreCompleto,
        C.Calificacion
    FROM Alumnos Al
    LEFT JOIN Calificaciones C
        ON C.AlumnoId = Al.Id
        AND C.AsignacionId = ?
    WHERE Al.GrupoId = ?
    ORDER BY Al.NombreCompleto ASC
");

$Stmt->execute([
    $AsignacionId,
    $InfoClase['GrupoId']
]);

$Alumnos = $Stmt->fetchAll();

$TotalAlumnos = count($Alumnos);

$Calificados = 0;
$PromedioGrupo = 0;
$Suma = 0;

foreach ($Alumnos as $Al) {

    if ($Al['Calificacion'] !== null && $Al['Calificacion'] !== '') {

        $Calificados++;

        $Suma += floatval($Al['Calificacion']);
    }
}

if ($Calificados > 0) {

    $PromedioGrupo = round($Suma / $Calificados, 1);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    
    <!-- FAVICON DEL SISTEMA: ICONO QUE APARECE EN LA PESTAÑA DEL NAVEGADOR -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGCE | Evaluar Grupo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>

        *{
            font-family:'Poppins',sans-serif;
        }

        body{
            background:
                linear-gradient(rgba(248,249,252,.96),rgba(248,249,252,.96)),
                url('https://www.transparenttextures.com/patterns/cubes.png');
            min-height:100vh;
        }

        .TopBar{
            background:linear-gradient(135deg,#6a1b4d,#8e244d);
            border-radius:22px;
            padding:28px;
            color:white;
            box-shadow:0 10px 35px rgba(0,0,0,.12);
        }

        .TopBar h2{
            font-weight:700;
            margin:0;
        }

        .TopBar .IconBox{
            width:70px;
            height:70px;
            border-radius:20px;
            background:rgba(255,255,255,.15);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.8rem;
        }

        .InfoBadge{
            background:rgba(255,255,255,.15);
            border:1px solid rgba(255,255,255,.2);
            padding:8px 16px;
            border-radius:50px;
            font-size:.85rem;
            display:inline-flex;
            align-items:center;
            gap:8px;
            margin-top:10px;
        }

        .StatsCard{
            border:none;
            border-radius:22px;
            overflow:hidden;
            box-shadow:0 6px 22px rgba(0,0,0,.05);
            transition:.25s;
        }

        .StatsCard:hover{
            transform:translateY(-3px);
        }

        .StatsIcon{
            width:55px;
            height:55px;
            border-radius:16px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.2rem;
        }

        .MainCard{
            border:none;
            border-radius:24px;
            overflow:hidden;
            box-shadow:0 8px 30px rgba(0,0,0,.06);
        }

        .MainCard .card-header{
            background:white;
            border-bottom:1px solid #f1f1f1;
            padding:22px 28px;
        }

        .table thead th{
            background:#f8f9fc;
            border:none;
            padding:16px;
            font-size:.85rem;
            text-transform:uppercase;
            letter-spacing:.5px;
            color:#6c757d;
        }

        .table tbody td{
            padding:15px;
            vertical-align:middle;
            border-color:#f1f3f5;
        }

        .AlumnoRow{
            transition:.2s;
        }

        .AlumnoRow:hover{
            background:#fafbff;
        }

        .AlumnoAvatar{
            width:42px;
            height:42px;
            border-radius:14px;
            background:linear-gradient(135deg,#6a1b4d,#8e244d);
            color:white;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:.9rem;
        }

        .InputNota{
            border-radius:14px;
            border:2px solid #edf0f5;
            height:45px;
            font-weight:700;
            font-size:1rem;
            transition:.2s;
        }

        .InputNota:focus{
            border-color:#8e244d;
            box-shadow:0 0 0 .15rem rgba(142,36,77,.15);
        }

        .BtnGuardar{
            background:linear-gradient(135deg,#198754,#157347);
            border:none;
            border-radius:14px;
            padding:12px 26px;
            font-weight:600;
            box-shadow:0 5px 18px rgba(25,135,84,.25);
        }

        .BtnGuardar:hover{
            transform:translateY(-1px);
        }

        .BtnBack{
            border-radius:12px;
            padding:10px 18px;
            font-weight:500;
        }

        .alert{
            border-radius:16px;
        }

        @media(max-width:768px){

            .TopBar{
                padding:22px;
            }

            .TopBar h2{
                font-size:1.4rem;
            }

            .table thead{
                display:none;
            }

            .table tbody td{
                display:block;
                width:100%;
                text-align:left!important;
                padding:8px 14px;
            }

            .AlumnoRow{
                border-bottom:1px solid #eee;
            }
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

    </style>
</head>

<body>


<div class="container py-4" style="max-width:1200px;">

    <div class="TopBar mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

            <div class="d-flex align-items-center gap-3">

                <div class="IconBox">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>

                <div>
                    <h2>
                        <?= htmlspecialchars($InfoClase['MateriaNombre']) ?>
                    </h2>

                    <div class="text-light opacity-75 mt-1">
                        Grupo <?= $InfoClase['Grado'] ?> "<?= $InfoClase['Grupo'] ?>"
                    </div>

                    <div class="InfoBadge">
                        <i class="fa-solid <?= strtoupper((string)$InfoClase['Turno']) === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>
                        Turno <?= $InfoClase['Turno'] ?>
                    </div>
                </div>

            </div>

            <div>
                <a href="Maestro.php" class="btn btn-light BtnBack">
                    <i class="fa-solid fa-arrow-left me-2"></i>
                    Volver
                </a>
            </div>

        </div>
    </div>

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="card StatsCard">

                <div class="card-body d-flex align-items-center gap-3 p-4">

                    <div class="StatsIcon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div>
                        <div class="text-muted small">
                            Total De Alumnos
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= $TotalAlumnos ?>
                        </h3>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card StatsCard">

                <div class="card-body d-flex align-items-center gap-3 p-4">

                    <div class="StatsIcon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-check"></i>
                    </div>

                    <div>
                        <div class="text-muted small">
                            Calificados
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= $Calificados ?>
                        </h3>
                    </div>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card StatsCard">

                <div class="card-body d-flex align-items-center gap-3 p-4">

                    <div class="StatsIcon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>

                    <div>
                        <div class="text-muted small">
                            Promedio General
                        </div>

                        <h3 class="fw-bold mb-0">
                            <?= $PromedioGrupo ?>
                        </h3>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php if(isset($_GET['Success'])): ?>

        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fa-solid fa-circle-check me-2"></i>
            Calificaciones Guardadas Correctamente.
        </div>

    <?php endif; ?>

    <div id="JsAlert" class="alert alert-warning border-0 shadow-sm d-none mb-4"></div>

    <div class="card MainCard">

        <div class="card-header d-flex justify-content-between align-items-center">

            <div>
                <h5 class="fw-bold mb-1">
                    Lista De Evaluación
                </h5>

                <span class="text-muted small">
                    Captura Las Calificaciones Del Grupo
                </span>
            </div>

            <div class="badge bg-dark px-3 py-2 rounded-pill">
                <?= $TotalAlumnos ?> Alumnos
            </div>

        </div>

        <div class="card-body p-0">

            <form method="POST" id="FormCalificaciones">

                <input type="hidden" name="GuardarNotes">

                <div class="table-responsive">

                    <table class="table align-middle mb-0">

                        <thead>

                            <tr>
                                <th>
                                    Alumno
                                </th>

                                <th class="text-center" style="width:180px;">
                                    Calificación
                                </th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if(empty($Alumnos)): ?>

                                <tr>

                                    <td colspan="2" class="text-center py-5 text-muted">

                                        <i class="fa-solid fa-folder-open fa-2x mb-3"></i>

                                        <div>
                                            No Hay Alumnos Registrados
                                        </div>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach($Alumnos as $Al): ?>

                                    <tr class="AlumnoRow">

                                        <td>

                                            <div class="d-flex align-items-center gap-3">

                                                <div class="AlumnoAvatar">
                                                    <i class="fa-solid fa-user"></i>
                                                </div>

                                                <div>

                                                    <div class="fw-semibold">
                                                        <?= htmlspecialchars($Al['NombreCompleto']) ?>
                                                    </div>

                                                    <small class="text-muted">
                                                        Alumno Registrado
                                                    </small>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <input
                                                type="number"
                                                name="Notas[<?= $Al['AlumnoId'] ?>]"
                                                class="form-control text-center InputNota"
                                                step="0.1"
                                                min="0"
                                                max="10"
                                                placeholder="-"
                                                data-original="<?= $Al['Calificacion'] !== null ? $Al['Calificacion'] : '' ?>"
                                                value="<?= $Al['Calificacion'] !== null ? $Al['Calificacion'] : '' ?>"
                                            >

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <?php if(!empty($Alumnos)): ?>

                    <div class="p-4 border-top bg-light d-flex justify-content-end">

                        <button type="submit" class="btn BtnGuardar text-white">

                            <i class="fa-solid fa-floppy-disk me-2"></i>

                            <i class="fa-solid fa-floppy-disk"></i> Guardar Calificaciones

                        </button>

                    </div>

                <?php endif; ?>

            </form>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function() {

    const Alertas = document.querySelectorAll('.alert-success');

    Alertas.forEach(function(Alerta) {

        setTimeout(function() {

            Alerta.style.transition = "all .5s ease";
            Alerta.style.opacity = "0";
            Alerta.style.transform = "translateY(-10px)";

            setTimeout(function() {
                Alerta.remove();
            }, 500);

        }, 3000);

    });

    const Inputs = document.querySelectorAll('.InputNota');

    Inputs.forEach(function(Input) {

        Input.addEventListener('input', function() {

            let Valor = parseFloat(this.value);

            this.classList.remove(
                'border-success',
                'border-warning',
                'border-danger'
            );

            if (this.value === '') {
                return;
            }

            if (Valor >= 8) {

                this.classList.add('border-success');

            } else if (Valor >= 6) {

                this.classList.add('border-warning');

            } else {

                this.classList.add('border-danger');

            }

        });

    });

    document.getElementById('FormCalificaciones')
    .addEventListener('submit', function(E) {

        const Inputs = document.querySelectorAll('.InputNota');

        const Alerta = document.getElementById('JsAlert');

        let HuboCambios = false;

        let TieneCeros = false;

        Inputs.forEach(function(Input) {

            const ValorOriginal = Input.getAttribute('data-original');

            const ValorActual = Input.value;

            if (ValorActual !== ValorOriginal) {

                HuboCambios = true;
            }

            if (
                ValorActual !== '' &&
                parseFloat(ValorActual) === 0
            ) {

                TieneCeros = true;
            }

        });

        if (!HuboCambios) {

            E.preventDefault();

            Alerta.innerHTML = `
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <strong>Sin Cambios:</strong>
                No Has Modificado Ninguna Calificación.
            `;

            Alerta.classList.remove('d-none');

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            return;
        }

        if (TieneCeros) {

            if (!confirm('Hay alumnos con calificación 0. ¿Deseas continuar?')) {

                E.preventDefault();
            }
        }

    });

});



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