<?php
/*
    Archivo: AvisosAdmin.php
    Descripción: Módulo administrativo para administrar avisos y comunicados.
    Desde esta pantalla puedo crear, editar, activar y desactivar avisos para maestros, padres o todo el sistema.
    Todos los datos visibles se normalizan en mayúsculas para mantener uniforme el sistema SGCE.
*/

require 'Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);

if (!$UserSession || !in_array($UserSession['Rol'], ['admin', 'director'], true)) {
    header('Location: index.php');
    exit;
}

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

// Escapo texto para imprimirlo seguro en HTML.
function HAviso($Texto) {
    return htmlspecialchars((string)$Texto, ENT_QUOTES, 'UTF-8');
}

// Normalizo textos de avisos a MAYÚSCULAS, respetando acentos y Ñ cuando mbstring está disponible.
function MayusAviso($Valor) {
    $Valor = trim((string)$Valor);
    $Valor = preg_replace('/\s+/u', ' ', $Valor);

    if ($Valor === '') {
        return '';
    }

    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($Valor, 'UTF-8');
    }

    return strtoupper($Valor);
}

// Valido que el público del aviso sea uno de los permitidos.
function PublicoAvisoValido($Publico) {
    $Publico = MayusAviso($Publico);
    return in_array($Publico, ['TODOS', 'MAESTROS', 'PADRES'], true) ? $Publico : 'TODOS';
}

// Redirecciono a esta misma pantalla después de cualquier acción para evitar reenvío de formulario.
function RedirectAvisos() {
    header('Location: AvisosAdmin.php');
    exit;
}

// =====================================================
// PROCESAMIENTO DE FORMULARIOS
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    RequerirCsrfPost();

    // ----------------------------
    // CREAR AVISO
    // ----------------------------
    if (isset($_POST['CrearAviso'])) {

        $Titulo = MayusAviso($_POST['Titulo'] ?? '');
        $Mensaje = MayusAviso($_POST['Mensaje'] ?? '');
        $Publico = PublicoAvisoValido($_POST['Publico'] ?? 'TODOS');

        if ($Titulo === '' || $Mensaje === '') {
            $_SESSION['Mensaje'] = 'Completa título y mensaje para publicar el aviso.';
            $_SESSION['MensajeTipo'] = 'danger';
            RedirectAvisos();
        }

        try {
            $Stmt = $Pdo->prepare("\n                INSERT INTO Avisos (Titulo, Mensaje, Publico, Activo)\n                VALUES (?, ?, ?, 1)\n            ");
            $Stmt->execute([$Titulo, $Mensaje, $Publico]);

            RegistrarBitacora($Pdo, $UserSession, 'CREAR_AVISO', 'Avisos', $Pdo->lastInsertId(), 'AVISO PUBLICADO PARA ' . $Publico);

            $_SESSION['Mensaje'] = 'Aviso publicado correctamente.';
            $_SESSION['MensajeTipo'] = 'success';
        } catch (Exception $E) {
            $_SESSION['Mensaje'] = 'Error al publicar el aviso.';
            $_SESSION['MensajeTipo'] = 'danger';
        }

        RedirectAvisos();
    }

    // ----------------------------
    // EDITAR AVISO
    // ----------------------------
    if (isset($_POST['EditarAviso'])) {

        $Id = intval($_POST['AvisoId'] ?? 0);
        $Titulo = MayusAviso($_POST['Titulo'] ?? '');
        $Mensaje = MayusAviso($_POST['Mensaje'] ?? '');
        $Publico = PublicoAvisoValido($_POST['Publico'] ?? 'TODOS');

        if ($Id <= 0 || $Titulo === '' || $Mensaje === '') {
            $_SESSION['Mensaje'] = 'Datos inválidos para editar el aviso.';
            $_SESSION['MensajeTipo'] = 'danger';
            RedirectAvisos();
        }

        try {
            $Stmt = $Pdo->prepare("\n                UPDATE Avisos\n                SET Titulo = ?, Mensaje = ?, Publico = ?\n                WHERE Id = ?\n            ");
            $Stmt->execute([$Titulo, $Mensaje, $Publico, $Id]);

            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_AVISO', 'Avisos', $Id, 'AVISO ACTUALIZADO');

            $_SESSION['Mensaje'] = 'Aviso actualizado correctamente.';
            $_SESSION['MensajeTipo'] = 'success';
        } catch (Exception $E) {
            $_SESSION['Mensaje'] = 'Error al actualizar el aviso.';
            $_SESSION['MensajeTipo'] = 'danger';
        }

        RedirectAvisos();
    }

    // ----------------------------
    // ACTIVAR AVISO
    // ----------------------------
    if (isset($_POST['ActivarAviso'])) {

        $Id = intval($_POST['ActivarAviso']);

        if ($Id > 0) {
            try {
                $Pdo->prepare("UPDATE Avisos SET Activo = 1 WHERE Id = ?")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'ACTIVAR_AVISO', 'Avisos', $Id, 'AVISO ACTIVADO');

                $_SESSION['Mensaje'] = 'Aviso activado correctamente.';
                $_SESSION['MensajeTipo'] = 'success';
            } catch (Exception $E) {
                $_SESSION['Mensaje'] = 'Error al activar el aviso.';
                $_SESSION['MensajeTipo'] = 'danger';
            }
        }

        RedirectAvisos();
    }

    // ----------------------------
    // DESACTIVAR AVISO
    // ----------------------------
    if (isset($_POST['DesactivarAviso'])) {

        $Id = intval($_POST['DesactivarAviso']);

        if ($Id > 0) {
            try {
                $Pdo->prepare("UPDATE Avisos SET Activo = 0 WHERE Id = ?")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'DESACTIVAR_AVISO', 'Avisos', $Id, 'AVISO DESACTIVADO');

                $_SESSION['Mensaje'] = 'Aviso desactivado correctamente.';
                $_SESSION['MensajeTipo'] = 'success';
            } catch (Exception $E) {
                $_SESSION['Mensaje'] = 'Error al desactivar el aviso.';
                $_SESSION['MensajeTipo'] = 'danger';
            }
        }

        RedirectAvisos();
    }
}

// =====================================================
// CONSULTA DE AVISOS
// =====================================================

$Avisos = $Pdo->query("\n    SELECT *\n    FROM Avisos\n    ORDER BY Activo DESC, FechaCreacion DESC, Id DESC\n    LIMIT 200\n")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SGCE | Avisos</title>

    <!-- FAVICON DEL SISTEMA -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root{
            --Guinda:#7A0818;
            --Guinda2:#A10D26;
            --GuindaHover:#4F050F;
            --Fondo:#EEF2F7;
            --Texto:#1F2937;
            --Muted:#6B7280;
            --Borde:#E5E7EB;
            --Azul:#1D4ED8;
            --AzulHover:#172554;
            --Verde:#15803D;
            --VerdeHover:#14532D;
            --Rojo:#B91C1C;
            --RojoHover:#7F1D1D;
            --Gris:#64748B;
            --GrisHover:#334155;
        }

        *{
            box-sizing:border-box;
            font-family:'Poppins','Segoe UI',sans-serif;
        }

        body{
            min-height:100vh;
            background:
                radial-gradient(circle at top left,rgba(122,8,24,.10),transparent 34%),
                linear-gradient(to bottom,#F8FAFC,#EEF2F7);
            color:var(--Texto);
        }

        .Top{
            background:linear-gradient(135deg,var(--Guinda),var(--Guinda2));
            color:white;
            border-radius:28px;
            padding:28px;
            box-shadow:0 14px 35px rgba(122,8,24,.22);
        }

        .Card{
            border:0;
            border-radius:26px;
            background:white;
            box-shadow:0 12px 35px rgba(15,23,42,.08);
            overflow:hidden;
        }

        .CardPadding{
            padding:24px;
        }

        .Btn{
            min-height:44px;
            border-radius:999px;
            font-weight:900;
            display:inline-flex;
            gap:8px;
            align-items:center;
            justify-content:center;
            text-decoration:none;
            padding:10px 18px;
            border:2px solid transparent;
            transition:transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
            line-height:1;
            white-space:nowrap;
        }

        .Btn:hover{
            transform:translateY(-2px);
            box-shadow:0 12px 24px rgba(15,23,42,.16);
        }

        .BtnGuinda{
            background:#FFFFFF;
            color:var(--Guinda);
            border-color:#FFFFFF;
        }

        .BtnGuinda:hover{
            background:#F8FAFC;
            color:var(--GuindaHover);
            border-color:#F8FAFC;
        }

        .BtnSave,
        .BtnTinto{
            background:linear-gradient(135deg,var(--Guinda),var(--Guinda2));
            color:#FFFFFF;
            border-color:var(--Guinda);
        }

        .BtnSave:hover,
        .BtnTinto:hover{
            background:linear-gradient(135deg,var(--GuindaHover),var(--Guinda));
            color:#FFFFFF;
            border-color:var(--GuindaHover);
        }

        .BtnEdit{
            background:linear-gradient(135deg,var(--Azul),#3B82F6);
            color:#FFFFFF;
            border-color:var(--Azul);
        }

        .BtnEdit:hover{
            background:linear-gradient(135deg,var(--AzulHover),var(--Azul));
            color:#FFFFFF;
            border-color:var(--AzulHover);
        }

        .BtnActivate{
            background:linear-gradient(135deg,var(--Verde),#16A34A);
            color:#FFFFFF;
            border-color:var(--Verde);
        }

        .BtnActivate:hover{
            background:linear-gradient(135deg,var(--VerdeHover),var(--Verde));
            color:#FFFFFF;
            border-color:var(--VerdeHover);
        }

        .BtnDanger{
            background:linear-gradient(135deg,var(--Rojo),#DC2626);
            color:#FFFFFF;
            border-color:var(--Rojo);
        }

        .BtnDanger:hover{
            background:linear-gradient(135deg,var(--RojoHover),var(--Rojo));
            color:#FFFFFF;
            border-color:var(--RojoHover);
        }

        .BtnCancel{
            background:linear-gradient(135deg,var(--Gris),#94A3B8);
            color:#FFFFFF;
            border-color:var(--Gris);
        }

        .BtnCancel:hover{
            background:linear-gradient(135deg,var(--GrisHover),var(--Gris));
            color:#FFFFFF;
            border-color:var(--GrisHover);
        }

        .form-control,
        .form-select{
            border:2px solid var(--Borde);
            border-radius:16px;
            min-height:48px;
            padding:12px 14px;
            text-transform:uppercase;
            box-shadow:none !important;
        }

        .form-control:focus,
        .form-select:focus{
            border-color:var(--Guinda);
            box-shadow:0 0 0 4px rgba(122,8,24,.10) !important;
        }

        label{
            font-weight:900;
            color:var(--Muted);
            font-size:.82rem;
            margin-bottom:7px;
        }

        .table{
            border-collapse:separate;
            border-spacing:0 10px;
        }

        .table th{
            text-transform:uppercase;
            color:var(--Muted);
            font-size:.82rem;
            font-weight:900;
            border:0;
            background:#F8FAFC;
            text-align:center;
            vertical-align:middle;
        }

        .table td{
            text-align:center;
            vertical-align:middle;
            border:0;
            background:#FFFFFF;
            padding:15px 12px;
        }

        .table tbody tr{
            box-shadow:0 5px 16px rgba(15,23,42,.05);
        }

        .table tbody tr td:first-child{
            border-radius:16px 0 0 16px;
        }

        .table tbody tr td:last-child{
            border-radius:0 16px 16px 0;
        }

        .BadgeEstado,
        .BadgePublico{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:7px 12px;
            border-radius:999px;
            font-size:.75rem;
            font-weight:900;
            color:#FFFFFF;
            background:linear-gradient(135deg,var(--Guinda),var(--Guinda2));
        }

        .BadgeInactivo{
            background:linear-gradient(135deg,#64748B,#94A3B8);
        }

        .AccionesAviso{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            flex-wrap:wrap;
        }

        .ModalHeaderEdit{
            background:linear-gradient(135deg,var(--Azul),#0EA5E9);
            color:white;
            padding:24px;
            text-align:center;
        }

        .ModalIcon{
            width:70px;
            height:70px;
            border-radius:24px;
            margin:0 auto 12px;
            background:rgba(255,255,255,.16);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:1.9rem;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.18);
        }

        .modal-content{
            border:0;
            border-radius:28px;
            overflow:hidden;
            box-shadow:0 25px 60px rgba(15,23,42,.25);
        }

        .modal-body{
            padding:26px;
        }

        .AlertAuto{
            border:0;
            border-radius:18px;
            box-shadow:0 10px 25px rgba(15,23,42,.10);
        }

        @media(max-width:768px){
            .Top{
                border-radius:22px;
                padding:22px;
            }

            .CardPadding{
                padding:18px;
            }

            .Btn{
                width:100%;
            }

            .table{
                min-width:820px;
            }
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

    <div class="Top mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="mb-1" style="font-weight:900;">
                <i class="fa-solid fa-bullhorn me-2"></i>
                AVISOS Y COMUNICADOS
            </h2>
            <div>PUBLICA AVISOS PARA MAESTROS, PADRES O TODO EL SISTEMA.</div>
        </div>

        <a href="Admin.php?Tab=inicio" class="Btn BtnGuinda SgceBtnInicio">
            <i class="fa-solid fa-arrow-left"></i>
            VOLVER A INICIO
        </a>
    </div>

    <?php if (isset($_SESSION['Mensaje'])): ?>
        <div class="alert alert-<?= HAviso($_SESSION['MensajeTipo'] ?? 'success') ?> AlertAuto alert-dismissible fade show mb-4">
            <i class="fa-solid fa-circle-info me-2"></i>
            <?= HAviso($_SESSION['Mensaje']) ?>
            <?php unset($_SESSION['Mensaje'], $_SESSION['MensajeTipo']); ?>
            <button class="btn-close" data-bs-dismiss="alert" type="button" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-4">
            <div class="Card CardPadding">
                <h5 class="fw-bold mb-3" style="color:var(--Guinda);">
                    <i class="fa-solid fa-plus-circle me-2"></i>
                    NUEVO AVISO
                </h5>

                <form method="POST">
                    <?php echo CampoCsrf(); ?>
                    <input type="hidden" name="CrearAviso" value="1">

                    <label>TÍTULO</label>
                    <input name="Titulo" class="form-control mb-3" required placeholder="TÍTULO DEL AVISO" autocomplete="off">

                    <label>PÚBLICO</label>
                    <select name="Publico" class="form-select mb-3">
                        <option value="TODOS">TODOS</option>
                        <option value="MAESTROS">MAESTROS</option>
                        <option value="PADRES">PADRES</option>
                    </select>

                    <label>MENSAJE</label>
                    <textarea name="Mensaje" class="form-control mb-3" rows="5" required placeholder="ESCRIBE EL COMUNICADO"></textarea>

                    <button class="Btn BtnSave w-100" type="submit">
                        <i class="fa-solid fa-paper-plane"></i>
                        PUBLICAR AVISO
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="Card CardPadding">
                <h5 class="fw-bold mb-3" style="color:var(--Guinda);">
                    <i class="fa-solid fa-list me-2"></i>
                    AVISOS REGISTRADOS
                </h5>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Título</th>
                                <th>Público</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($Avisos as $A): ?>
                                <?php
                                    $AvisoId = (int)$A['Id'];
                                    $EstaActivo = (int)$A['Activo'] === 1;
                                ?>
                                <tr>
                                    <td><?= HAviso(date('d/m/Y H:i', strtotime($A['FechaCreacion']))) ?></td>
                                    <td class="fw-bold"><?= HAviso($A['Titulo']) ?></td>
                                    <td><span class="BadgePublico"><?= HAviso($A['Publico']) ?></span></td>
                                    <td>
                                        <span class="BadgeEstado <?= $EstaActivo ? '' : 'BadgeInactivo' ?>">
                                            <?= $EstaActivo ? 'ACTIVO' : 'INACTIVO' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="AccionesAviso">

                                            <button type="button" class="Btn BtnEdit" data-bs-toggle="modal" data-bs-target="#ModalEditarAviso<?= $AvisoId ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                EDITAR
                                            </button>

                                            <?php if ($EstaActivo): ?>
                                                <form method="POST" class="m-0" onsubmit="return confirm('¿DESACTIVAR ESTE AVISO?')">
                    <?php echo CampoCsrf(); ?>
                                                    <button name="DesactivarAviso" value="<?= $AvisoId ?>" class="Btn BtnDanger" type="submit">
                                                        <i class="fa-solid fa-ban"></i>
                                                        DESACTIVAR
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="m-0" onsubmit="return confirm('¿ACTIVAR ESTE AVISO?')">
                    <?php echo CampoCsrf(); ?>
                                                    <button name="ActivarAviso" value="<?= $AvisoId ?>" class="Btn BtnActivate" type="submit">
                                                        <i class="fa-solid fa-circle-check"></i>
                                                        ACTIVAR
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>

                                <!-- MODAL PARA EDITAR AVISO -->
                                <div class="modal fade" id="ModalEditarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content">

                                            <div class="ModalHeaderEdit">
                                                <div class="ModalIcon">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </div>
                                                <h4 class="fw-bold mb-1">EDITAR AVISO</h4>
                                                <div>ACTUALIZA EL COMUNICADO SELECCIONADO</div>
                                            </div>

                                            <form method="POST">
                    <?php echo CampoCsrf(); ?>
                                                <div class="modal-body">
                                                    <input type="hidden" name="EditarAviso" value="1">
                                                    <input type="hidden" name="AvisoId" value="<?= $AvisoId ?>">

                                                    <label>TÍTULO</label>
                                                    <input name="Titulo" class="form-control mb-3" required value="<?= HAviso($A['Titulo']) ?>">

                                                    <label>PÚBLICO</label>
                                                    <select name="Publico" class="form-select mb-3">
                                                        <option value="TODOS" <?= $A['Publico'] === 'TODOS' ? 'selected' : '' ?>>TODOS</option>
                                                        <option value="MAESTROS" <?= $A['Publico'] === 'MAESTROS' ? 'selected' : '' ?>>MAESTROS</option>
                                                        <option value="PADRES" <?= $A['Publico'] === 'PADRES' ? 'selected' : '' ?>>PADRES</option>
                                                    </select>

                                                    <label>MENSAJE</label>
                                                    <textarea name="Mensaje" class="form-control mb-3" rows="5" required><?= HAviso($A['Mensaje']) ?></textarea>

                                                    <div class="row g-2 mt-2">
                                                        <div class="col-md-6">
                                                            <button type="button" class="Btn BtnCancel w-100" data-bs-dismiss="modal">
                                                                <i class="fa-solid fa-xmark"></i>
                                                                CANCELAR
                                                            </button>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <button type="submit" class="Btn BtnEdit w-100">
                                                                <i class="fa-solid fa-floppy-disk"></i>
                                                                GUARDAR CAMBIOS
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($Avisos)): ?>
                                <tr>
                                    <td colspan="5" class="py-5 text-muted fw-bold">SIN AVISOS REGISTRADOS.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.AlertAuto').forEach(function(Alerta) {
        setTimeout(function() {
            if (!Alerta) { return; }
            Alerta.style.transition = 'opacity .4s ease, transform .4s ease';
            Alerta.style.opacity = '0';
            Alerta.style.transform = 'translateY(-10px)';
            setTimeout(function() { Alerta.remove(); }, 450);
        }, 4500);
    });
});
</script>
<?php ImprimirCsrfScript(); ?>
</body>
</html>
