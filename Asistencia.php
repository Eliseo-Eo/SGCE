<?php

/*
    Archivo: Asistencia.php
    Descripción: Módulo para registrar asistencia diaria de un grupo.
    Permite marcar asistencia, falta, retardo o justificante, evitando duplicar el pase de lista del día.
*/

require_once 'Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Mexico_City');

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || !in_array($UserSession['Rol'], ['maestro', 'admin'], true)) {
    header('Location: index.php');
    exit;
}

$Hoy = date('Y-m-d');

$AsignacionId = intval($_GET['id'] ?? ($_GET['AsignacionId'] ?? ($_POST['asignacion_id'] ?? 0)));

if ($AsignacionId <= 0) {
    die("Asignación inválida.");
}

$Mensaje = "";
$YaSeRegistro = false;
$EstadosPermitidos = ['A', 'F', 'R', 'J'];

// VERIFICAR QUE LA ASIGNACIÓN EXISTA Y QUE EL MAESTRO TENGA PERMISO
$StmtInfo = $Pdo->prepare("
    SELECT
        A.Id,
        A.MaestroId,
        A.GrupoId,
        A.MateriaNombre,
        G.Grado,
        G.Grupo,
        G.Turno
    FROM Asignaciones A
    JOIN Grupos G ON A.GrupoId = G.Id
    WHERE A.Id = ?
    AND A.Activo = 1
    AND G.Activo = 1
    LIMIT 1
");

$StmtInfo->execute([$AsignacionId]);
$InfoClase = $StmtInfo->fetch();

if (!$InfoClase) {
    die("Asignación no encontrada.");
}

if ($UserSession['Rol'] === 'maestro' && (int)$UserSession['Id'] !== (int)$InfoClase['MaestroId']) {
    die("Acceso denegado.");
}

// VERIFICAR SI YA SE REGISTRÓ ASISTENCIA HOY
$StmtCheck = $Pdo->prepare("
    SELECT COUNT(*)
    FROM Asistencias
    WHERE AsignacionId = ?
    AND FechaDia = ?
");

$StmtCheck->execute([$AsignacionId, $Hoy]);

if ((int)$StmtCheck->fetchColumn() > 0) {
    $YaSeRegistro = true;
}

// OBTENER ALUMNOS
$Stmt = $Pdo->prepare("
    SELECT
        a.Id,
        a.NombreCompleto
    FROM Alumnos a
    WHERE a.GrupoId = ?
    AND a.Activo = 1
    ORDER BY a.NombreCompleto ASC
");

$Stmt->execute([(int)$InfoClase['GrupoId']]);
$Alumnos = $Stmt->fetchAll();

// GUARDAR ASISTENCIA
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    (RequerirCsrfPost() || true)
    &&
    isset($_POST['guardar'])
    &&
    !$YaSeRegistro
) {

    if (!isset($_POST['estado']) || !is_array($_POST['estado'])) {
        $Mensaje = '
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <i class="fa-solid fa-circle-xmark me-2"></i>
                No se recibieron datos de asistencia.
            </div>
        ';
    } else {

        $Momento = date('Y-m-d H:i:s');

        try {
            $Pdo->beginTransaction();

            $StmtInsert = $Pdo->prepare("
                INSERT INTO Asistencias
                (
                    AsignacionId,
                    AlumnoId,
                    Fecha,
                    Estado
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            foreach ($Alumnos as $Alumno) {

                $AlumnoId = (int)$Alumno['Id'];
                $Estado = $_POST['estado'][$AlumnoId] ?? 'A';

                if (!in_array($Estado, $EstadosPermitidos, true)) {
                    $Estado = 'A';
                }

                $StmtInsert->execute([
                    $AsignacionId,
                    $AlumnoId,
                    $Momento,
                    $Estado
                ]);
            }

            $Pdo->commit();

            RegistrarBitacora($Pdo, $UserSession, 'REGISTRAR_ASISTENCIA', 'Asistencias', $AsignacionId, 'PASE DE LISTA REGISTRADO');

            $YaSeRegistro = true;

            $Mensaje = '
                <div class="alert alert-success border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    Asistencia Guardada Correctamente.
                </div>
            ';

        } catch (Exception $E) {

            if ($Pdo->inTransaction()) {
                $Pdo->rollBack();
            }

            $Mensaje = '
                <div class="alert alert-danger border-0 shadow-sm mb-4">
                    <i class="fa-solid fa-circle-xmark me-2"></i>
                    Error al guardar la asistencia. Revisa que no se haya registrado ya el día de hoy.
                </div>
            ';
        }
    }
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

<title>SGCE | Pase De Lista</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

    *{
        font-family:'Poppins',sans-serif;
    }

    body{
        background:
            linear-gradient(rgba(247,248,252,.96),rgba(247,248,252,.96)),
            url('https://www.transparenttextures.com/patterns/cubes.png');

        min-height:100vh;
    }

    .TopHeader{
        background:linear-gradient(135deg,#5f0f40,#9a031e);
        border-radius:28px;
        padding:30px;
        color:white;
        box-shadow:0 10px 35px rgba(0,0,0,.12);
    }

    .HeaderIcon{
        width:78px;
        height:78px;
        border-radius:24px;
        background:rgba(255,255,255,.15);

        display:flex;
        align-items:center;
        justify-content:center;

        font-size:2rem;
    }

    .BadgeGlass{
        background:rgba(255,255,255,.15);
        border:1px solid rgba(255,255,255,.2);

        padding:8px 16px;

        border-radius:50px;

        font-size:.85rem;

        display:flex;
        align-items:center;
        gap:8px;
    }

    .BtnBack{
        background:white;
        border:none;

        border-radius:14px;

        padding:12px 22px;

        font-weight:600;

        color:#5f0f40;
    }

    .MainCard{
        border:none;

        border-radius:28px;

        overflow:hidden;

        box-shadow:0 10px 35px rgba(0,0,0,.06);
    }

    .AlumnoAvatar{
        width:48px;
        height:48px;

        border-radius:16px;

        background:linear-gradient(135deg,#5f0f40,#9a031e);

        color:white;

        display:flex;
        align-items:center;
        justify-content:center;
    }

    .EstadoSelect{
        border-radius:14px;

        border:2px solid #edf0f5;

        height:48px;

        font-weight:600;
    }

    .BtnGuardar{
        background:linear-gradient(135deg,#198754,#157347);

        border:none;

        border-radius:16px;

        padding:14px;

        color:white;

        font-weight:700;

        width:100%;
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

   <div class="container py-4" style="max-width:1200px;">

    <!-- HEADER -->

    <div class="TopHeader mb-4">

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">

            <div class="d-flex align-items-center gap-3">

                <div class="HeaderIcon">
                    <i class="fa-solid fa-clipboard-user"></i>
                </div>

                <div>

                    <h2 class="fw-bold mb-1">
                        Pase De Lista
                    </h2>

                    <div class="text-light opacity-75">
                        Control De Asistencia Escolar
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">

                        <span class="BadgeGlass">

                            <i class="fa-solid fa-calendar-days"></i>

                            <?= date('d/m/Y') ?>

                        </span>

                        <span class="BadgeGlass">

                            <i class="fa-solid fa-clock"></i>

                            <?= date('h:i A') ?>

                        </span>

                    </div>

                </div>

            </div>

            <div>

                <a href="Maestro.php" class="btn BtnBack SgceBtnInicio">

                    <i class="fa-solid fa-arrow-left me-2"></i>

                    VOLVER A INICIO

                </a>

            </div>

        </div>

    </div>

    <!-- ALERTAS -->

    <?= $Mensaje ?>

    <?php if ($YaSeRegistro): ?>

        <div class="alert alert-warning border-0 shadow-sm mb-4">

            <i class="fa-solid fa-circle-exclamation me-2"></i>

            Ya Se Registró La Asistencia De Este Grupo El Día De Hoy.

        </div>

    <?php endif; ?>

    <!-- CARD -->

    <div class="card MainCard">

        <div class="card-header bg-white border-0 p-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h4 class="fw-bold mb-1">

                        Lista De Alumnos

                    </h4>

                    <div class="text-muted">

                        Selecciona El Estado De Asistencia

                    </div>

                </div>

                <div class="badge bg-dark rounded-pill px-4 py-3">

                    <?= count($Alumnos) ?> Alumnos

                </div>

            </div>

        </div>

        <div class="card-body p-0">

            <form method="POST">
                    <?php echo CampoCsrf(); ?>

                <input type="hidden" name="asignacion_id" value="<?= $AsignacionId ?>">

                <div class="table-responsive">

                    <table class="table align-middle mb-0">

                        <thead class="table-light">

                            <tr>

                                <th class="ps-4">
                                    Alumno
                                </th>

                                <th class="text-center" style="width:300px;">
                                    Estado
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if(empty($Alumnos)): ?>

                                <tr>

                                    <td colspan="2" class="text-center py-5 text-muted">

                                        <i class="fa-solid fa-folder-open fa-3x mb-3"></i>

                                        <div class="fw-semibold">

                                            No Hay Alumnos Registrados

                                        </div>

                                    </td>

                                </tr>

                            <?php else: ?>

                                <?php foreach($Alumnos as $a): ?>

                                    <tr>

                                        <td class="ps-4">

                                            <div class="d-flex align-items-center gap-3">

                                                <div class="AlumnoAvatar">

                                                    <i class="fa-solid fa-user"></i>

                                                </div>

                                                <div>

                                                    <div class="fw-semibold">

                                                        <?= htmlspecialchars($a['NombreCompleto']) ?>

                                                    </div>

                                                    <small class="text-muted">

                                                        Alumno Registrado

                                                    </small>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <select
                                                name="estado[<?= $a['Id'] ?>]"
                                                class="form-select EstadoSelect"
                                                <?= $YaSeRegistro ? 'disabled' : '' ?>
                                            >

                                                <option value="A">
                                                    ✅ Asistencia
                                                </option>

                                                <option value="F">
                                                    ❌ Falta
                                                </option>

                                                <option value="R">
                                                    ⏰ Retardo
                                                </option>

                                                <option value="J">
                                                    📄 Justificante
                                                </option>

                                            </select>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <?php if (!$YaSeRegistro && !empty($Alumnos)): ?>

                    <div class="p-4 bg-light border-top">

                        <button type="submit" name="guardar" class="btn BtnGuardar">

                            <i class="fa-solid fa-floppy-disk me-2"></i>

                            Guardar Pase De Lista

                        </button>

                    </div>

                <?php endif; ?>

            </form>

        </div>

    </div>

</div>

    <script>

document.addEventListener("DOMContentLoaded", function() {

    // ALERTA SUCCESS AUTO HIDE

    const Alertas = document.querySelectorAll('.alert-success');

    Alertas.forEach(function(Alerta){

        setTimeout(function(){

            Alerta.style.transition = "all .5s ease";

            Alerta.style.opacity = "0";

            Alerta.style.transform = "translateY(-10px)";

            setTimeout(function(){

                Alerta.remove();

            },500);

        },3000);

    });

    // COLORES DINÁMICOS EN SELECT

    const Selects = document.querySelectorAll('.EstadoSelect');

    function AplicarColor(Select){

        Select.classList.remove(
            'border-success',
            'border-danger',
            'border-warning',
            'border-primary'
        );

        switch(Select.value){

            case 'A':

                Select.classList.add('border-success');

            break;

            case 'F':

                Select.classList.add('border-danger');

            break;

            case 'R':

                Select.classList.add('border-warning');

            break;

            case 'J':

                Select.classList.add('border-primary');

            break;

        }

    }

    Selects.forEach(function(Select){

        AplicarColor(Select);

        Select.addEventListener('change', function(){

            AplicarColor(this);

        });

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

<?php ImprimirCsrfScript(); ?>
</body>
</html>