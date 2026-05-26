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


<style>
/* ==========================================================
   FIX14 - MODALES CENTRADAS Y ESTABLES
   Evita que Bootstrap + tablas + estilos viejos corten el cuerpo.
   ========================================================== */
body.modal-open{overflow:hidden !important;padding-right:0 !important;}
.modal.show{display:flex !important;align-items:center !important;justify-content:center !important;padding:18px !important;}
.modal-dialog{margin:0 auto !important;width:min(96vw,680px) !important;max-width:680px !important;max-height:calc(100vh - 36px) !important;display:flex !important;align-items:center !important;pointer-events:none !important;}
.modal-dialog.modal-lg{width:min(96vw,860px) !important;max-width:860px !important;}
.modal-dialog.modal-sm{width:min(96vw,620px) !important;max-width:620px !important;}
.modal-content{width:100% !important;background:#FFFFFF !important;border:0 !important;border-radius:28px !important;overflow:hidden !important;box-shadow:0 28px 90px rgba(15,23,42,.38) !important;max-height:calc(100vh - 36px) !important;display:flex !important;flex-direction:column !important;pointer-events:auto !important;}
.ModalHeaderEdit,.DeleteModalHeader{flex:0 0 auto !important;}
.modal-body,.DeleteModalBody{display:block !important;background:#FFFFFF !important;color:#1F2937 !important;overflow-y:auto !important;max-height:calc(100vh - 230px) !important;padding:28px !important;}
.modal-body label{color:#374151 !important;font-weight:900 !important;}
.modal-backdrop.show{opacity:.58 !important;}
@media(max-width:576px){.modal.show{padding:10px !important;}.modal-dialog,.modal-dialog.modal-lg,.modal-dialog.modal-sm{width:calc(100vw - 20px) !important;max-width:calc(100vw - 20px) !important;}.modal-body,.DeleteModalBody{max-height:calc(100vh - 210px) !important;padding:20px !important;}}

/* FIX15: evita que los padres transformados rompan el centrado de modales */
html body.modal-open .Card,
html body.modal-open .Card:hover,
html body.modal-open .CardPadding,
html body.modal-open .CardPadding:hover{
    transform:none !important;
}
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
                                                <button type="button" class="Btn BtnDanger" data-bs-toggle="modal" data-bs-target="#ModalDesactivarAviso<?= $AvisoId ?>">
                                                    <i class="fa-solid fa-ban"></i>
                                                    DESACTIVAR
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="Btn BtnActivate" data-bs-toggle="modal" data-bs-target="#ModalActivarAviso<?= $AvisoId ?>">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    ACTIVAR
                                                </button>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>

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

<!-- MODALES PARA EDITAR AVISOS - HIJAS DIRECTAS DEL BODY.
     IMPORTANTE: no deben ir dentro de .Card porque .Card:hover usa transform,
     y un ancestor con transform rompe position:fixed de Bootstrap. -->
<?php foreach ($Avisos as $A): ?>
                    <?php $AvisoId = (int)$A['Id']; ?>
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

                    <?php $EstaActivoModal = (int)$A['Activo'] === 1; ?>
                    <?php if ($EstaActivoModal): ?>
                    <!-- MODAL PARA DESACTIVAR AVISO -->
                    <div class="modal fade ModalAvisoEstado" id="ModalDesactivarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-sm ModalEliminarFijo">
                            <div class="modal-content DeleteModalContent AvisoConfirmContent">
                                <div class="DeleteModalHeader AvisoConfirmHeader HeaderDesactivar">
                                    <div class="DeleteIcon">
                                        <i class="fa-solid fa-ban"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">CONFIRMAR DESACTIVACIÓN</h4>
                                    <p class="mb-0 opacity-75">AVISO</p>
                                </div>
                                <div class="DeleteModalBody">
                                    <p class="fs-6 fw-bold mb-3">¿DESEAS DESACTIVAR ESTE AVISO?</p>
                                    <div class="AvisoTituloModal mb-3">
                                        <?= HAviso($A['Titulo']) ?>
                                    </div>
                                    <div class="DeleteWarningBox mb-4">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                        Revisa bien antes de confirmar. El aviso dejará de mostrarse, pero podrás activarlo después.
                                    </div>
                                    <form method="POST" class="m-0">
                                        <?php echo CampoCsrf(); ?>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal">
                                                <i class="fa-solid fa-xmark"></i> CANCELAR
                                            </button>
                                            <button name="DesactivarAviso" value="<?= $AvisoId ?>" type="submit" class="BtnConfirmDelete BtnConfirmDesactivar">
                                                <i class="fa-solid fa-ban"></i> SÍ, DESACTIVAR
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- MODAL PARA ACTIVAR AVISO -->
                    <div class="modal fade ModalAvisoEstado" id="ModalActivarAviso<?= $AvisoId ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-sm ModalEliminarFijo">
                            <div class="modal-content DeleteModalContent AvisoConfirmContent">
                                <div class="DeleteModalHeader AvisoConfirmHeader HeaderActivar">
                                    <div class="DeleteIcon">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </div>
                                    <h4 class="fw-bold mb-1">CONFIRMAR ACTIVACIÓN</h4>
                                    <p class="mb-0 opacity-75">AVISO</p>
                                </div>
                                <div class="DeleteModalBody">
                                    <p class="fs-6 fw-bold mb-3">¿DESEAS ACTIVAR ESTE AVISO?</p>
                                    <div class="AvisoTituloModal mb-3">
                                        <?= HAviso($A['Titulo']) ?>
                                    </div>
                                    <div class="DeleteWarningBox mb-4">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                        Revisa bien antes de confirmar. El aviso volverá a mostrarse al público seleccionado.
                                    </div>
                                    <form method="POST" class="m-0">
                                        <?php echo CampoCsrf(); ?>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal">
                                                <i class="fa-solid fa-xmark"></i> CANCELAR
                                            </button>
                                            <button name="ActivarAviso" value="<?= $AvisoId ?>" type="submit" class="BtnConfirmDelete BtnConfirmActivar">
                                                <i class="fa-solid fa-circle-check"></i> SÍ, ACTIVAR
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>


<!-- SGCE FIX16: modales profesionales para activar/desactivar avisos -->
<style id="SgceFix16AvisosEstadoModal">
html body .AvisoConfirmContent{border:0 !important;border-radius:30px !important;overflow:hidden !important;box-shadow:0 28px 90px rgba(15,23,42,.38) !important;background:#FFFFFF !important;}
html body .AvisoConfirmHeader{color:#FFFFFF !important;padding:28px !important;text-align:center !important;}
html body .HeaderDesactivar{background:linear-gradient(135deg,#7A0818,#DC2626) !important;}
html body .HeaderActivar{background:linear-gradient(135deg,#065F46,#16A34A) !important;}
html body .DeleteIcon{width:82px !important;height:82px !important;border-radius:26px !important;margin:0 auto 14px !important;background:rgba(255,255,255,.16) !important;display:flex !important;align-items:center !important;justify-content:center !important;font-size:2.2rem !important;box-shadow:inset 0 0 0 1px rgba(255,255,255,.20) !important;}
html body .DeleteModalBody{text-align:center !important;background:#FFFFFF !important;padding:28px !important;color:#1F2937 !important;}
html body .AvisoTituloModal{background:#F8FAFC !important;border:2px solid #E5E7EB !important;border-radius:18px !important;padding:14px 16px !important;color:#7A0818 !important;font-weight:900 !important;text-transform:uppercase !important;box-shadow:inset 0 1px 0 rgba(255,255,255,.75) !important;}
html body .DeleteWarningBox{background:#FFF7ED !important;border:1px solid #FDBA74 !important;color:#9A3412 !important;border-radius:18px !important;padding:14px 16px !important;font-weight:800 !important;font-size:.92rem !important;}
html body .AvisoInfoBox{background:#ECFDF5 !important;border:1px solid #86EFAC !important;color:#166534 !important;border-radius:18px !important;padding:14px 16px !important;font-weight:800 !important;font-size:.92rem !important;}
html body .BtnCancelDelete,
html body .BtnConfirmDelete{min-width:190px !important;min-height:48px !important;border-radius:999px !important;font-weight:900 !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;gap:8px !important;transition:.22s ease !important;padding:0 22px !important;text-transform:uppercase !important;text-decoration:none !important;}
html body .BtnCancelDelete{background:#FFFFFF !important;color:#64748B !important;border:3px solid #CBD5E1 !important;box-shadow:0 10px 24px rgba(100,116,139,.12) !important;}
html body .BtnCancelDelete:hover{background:#64748B !important;color:#FFFFFF !important;transform:translateY(-2px) !important;box-shadow:0 14px 30px rgba(100,116,139,.20) !important;}
html body .BtnConfirmDesactivar{background:#FFFFFF !important;color:#DC2626 !important;border:3px solid #DC2626 !important;box-shadow:0 10px 24px rgba(220,38,38,.14) !important;}
html body .BtnConfirmDesactivar:hover{background:#DC2626 !important;color:#FFFFFF !important;transform:translateY(-2px) !important;box-shadow:0 14px 32px rgba(220,38,38,.25) !important;}
html body .BtnConfirmActivar{background:#FFFFFF !important;color:#16A34A !important;border:3px solid #16A34A !important;box-shadow:0 10px 24px rgba(22,163,74,.14) !important;}
html body .BtnConfirmActivar:hover{background:#16A34A !important;color:#FFFFFF !important;transform:translateY(-2px) !important;box-shadow:0 14px 32px rgba(22,163,74,.25) !important;}
html body .BtnCancelDelete i,
html body .BtnConfirmDelete i{color:inherit !important;}
html body .ModalAvisoEstado .modal-dialog{width:min(96vw,560px) !important;max-width:560px !important;}
@media(max-width:576px){html body .BtnCancelDelete,html body .BtnConfirmDelete{width:100% !important;min-width:100% !important;}}
</style>
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

<script id="SgceFix15ModalBodyAppend">
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.modal').forEach(function(Modal){
        if (Modal.parentElement !== document.body) {
            document.body.appendChild(Modal);
        }
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



<!-- SGCE FIX14: centrado final de modales y restauración visual -->
<style id="SgceFix14ModalFinal">
html body.modal-open{overflow:hidden !important;padding-right:0 !important;}
html body .modal.show{display:flex !important;align-items:center !important;justify-content:center !important;padding:18px !important;}
html body .modal-dialog,
html body .modal-dialog.modal-sm,
html body .modal-dialog.modal-lg,
html body .ModalEliminarFijo,
html body .ModalEditarPro{margin:0 auto !important;width:min(96vw,680px) !important;max-width:680px !important;max-height:calc(100vh - 36px) !important;display:flex !important;align-items:center !important;justify-content:center !important;pointer-events:none !important;transform:none !important;}
html body .modal-dialog.modal-lg{width:min(96vw,860px) !important;max-width:860px !important;}
html body .modal-dialog.modal-sm{width:min(96vw,620px) !important;max-width:620px !important;}
html body #ModalConfirmarEliminar{padding:18px !important;}
html body #ModalConfirmarEliminar.show{display:flex !important;align-items:center !important;justify-content:center !important;}
html body #ModalConfirmarEliminar .ModalEliminarFijo{margin:0 auto !important;width:min(96vw,520px) !important;max-width:520px !important;min-height:auto !important;}
html body .modal-content,
html body .DeleteModalContent{width:100% !important;background:#FFFFFF !important;background-color:#FFFFFF !important;border:0 !important;border-radius:28px !important;overflow:hidden !important;box-shadow:0 28px 90px rgba(15,23,42,.38) !important;max-height:calc(100vh - 36px) !important;display:flex !important;flex-direction:column !important;pointer-events:auto !important;opacity:1 !important;}
html body .modal-content > form{display:block !important;background:#FFFFFF !important;overflow:visible !important;}
html body .ModalHeaderEdit,
html body .DeleteModalHeader{flex:0 0 auto !important;}
html body .modal-body,
html body .DeleteModalBody{display:block !important;background:#FFFFFF !important;background-color:#FFFFFF !important;color:#1F2937 !important;overflow-y:auto !important;max-height:calc(100vh - 230px) !important;padding:28px !important;opacity:1 !important;visibility:visible !important;}
html body .modal-body label{color:#374151 !important;font-weight:900 !important;}
html body .modal-backdrop.show{opacity:.58 !important;}
@media(max-width:576px){
  html body .modal.show,html body #ModalConfirmarEliminar{padding:10px !important;}
  html body .modal-dialog,html body .modal-dialog.modal-sm,html body .modal-dialog.modal-lg,html body .ModalEliminarFijo,html body .ModalEditarPro,html body #ModalConfirmarEliminar .ModalEliminarFijo{width:calc(100vw - 20px) !important;max-width:calc(100vw - 20px) !important;}
  html body .modal-body,html body .DeleteModalBody{max-height:calc(100vh - 210px) !important;padding:20px !important;}
}
</style>



<!-- SGCE FIX17: modales de activar/desactivar igual al diseño de eliminación y centrado blindado -->
<style id="SgceFix17AvisosModalFinal">
html body .ModalAvisoEstado{
    position:fixed !important;
    inset:0 !important;
    z-index:1060 !important;
    padding:18px !important;
    overflow:hidden !important;
}
html body .ModalAvisoEstado.show{
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
}
html body .ModalAvisoEstado .modal-dialog,
html body .ModalAvisoEstado .ModalEliminarFijo{
    margin:0 auto !important;
    width:min(96vw,520px) !important;
    max-width:520px !important;
    min-height:auto !important;
    max-height:calc(100vh - 36px) !important;
    transform:none !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
}
html body .ModalAvisoEstado .DeleteModalContent,
html body .ModalAvisoEstado .AvisoConfirmContent{
    width:100% !important;
    background:#FFFFFF !important;
    border:0 !important;
    border-radius:30px !important;
    overflow:hidden !important;
    box-shadow:0 28px 90px rgba(15,23,42,.38) !important;
    max-height:calc(100vh - 36px) !important;
    display:flex !important;
    flex-direction:column !important;
}
html body .ModalAvisoEstado .DeleteModalHeader,
html body .ModalAvisoEstado .AvisoConfirmHeader,
html body .ModalAvisoEstado .HeaderDesactivar,
html body .ModalAvisoEstado .HeaderActivar{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    padding:28px !important;
    text-align:center !important;
    flex:0 0 auto !important;
}
html body .ModalAvisoEstado .DeleteIcon{
    width:82px !important;
    height:82px !important;
    border-radius:26px !important;
    margin:0 auto 14px !important;
    background:rgba(255,255,255,.16) !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    font-size:2.2rem !important;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.20) !important;
}
html body .ModalAvisoEstado .DeleteIcon i,
html body .ModalAvisoEstado .DeleteModalHeader h4,
html body .ModalAvisoEstado .DeleteModalHeader p{
    color:#FFFFFF !important;
}
html body .ModalAvisoEstado .DeleteModalBody{
    padding:28px !important;
    text-align:center !important;
    background:#FFFFFF !important;
    color:#1F2937 !important;
    overflow-y:auto !important;
    max-height:calc(100vh - 230px) !important;
}
html body .ModalAvisoEstado .DeleteWarningBox{
    background:#FFF7ED !important;
    border:1px solid #FDBA74 !important;
    color:#9A3412 !important;
    border-radius:18px !important;
    padding:14px 16px !important;
    font-weight:700 !important;
    font-size:.92rem !important;
}
html body .ModalAvisoEstado .BtnCancelDelete,
html body .ModalAvisoEstado .BtnConfirmDelete,
html body .ModalAvisoEstado .BtnConfirmDesactivar,
html body .ModalAvisoEstado .BtnConfirmActivar{
    min-width:190px !important;
    min-height:46px !important;
    border-radius:999px !important;
    font-weight:900 !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:8px !important;
    transition:.2s ease !important;
    padding:0 22px !important;
    text-transform:uppercase !important;
}
html body .ModalAvisoEstado .BtnCancelDelete{
    background:#FFFFFF !important;
    color:#64748B !important;
    border:2px solid #CBD5E1 !important;
}
html body .ModalAvisoEstado .BtnCancelDelete:hover{
    background:#64748B !important;
    color:#FFFFFF !important;
    transform:translateY(-2px) !important;
    box-shadow:0 12px 24px rgba(100,116,139,.18) !important;
}
html body .ModalAvisoEstado .BtnConfirmDelete,
html body .ModalAvisoEstado .BtnConfirmDesactivar,
html body .ModalAvisoEstado .BtnConfirmActivar{
    background:#FFFFFF !important;
    color:#DC2626 !important;
    border:2px solid #DC2626 !important;
}
html body .ModalAvisoEstado .BtnConfirmDelete:hover,
html body .ModalAvisoEstado .BtnConfirmDesactivar:hover,
html body .ModalAvisoEstado .BtnConfirmActivar:hover{
    background:#DC2626 !important;
    color:#FFFFFF !important;
    transform:translateY(-2px) !important;
    box-shadow:0 12px 24px rgba(220,38,38,.22) !important;
}
html body .ModalAvisoEstado button i{color:inherit !important;}
@media(max-width:576px){
    html body .ModalAvisoEstado{padding:10px !important;}
    html body .ModalAvisoEstado .modal-dialog{width:calc(100vw - 20px) !important;max-width:calc(100vw - 20px) !important;}
    html body .ModalAvisoEstado .BtnCancelDelete,
    html body .ModalAvisoEstado .BtnConfirmDelete{width:100% !important;min-width:100% !important;}
}
</style>
<script id="SgceFix17AvisosMoverModales">
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.modal').forEach(function(Modal){
        if (Modal.parentElement !== document.body) {
            document.body.appendChild(Modal);
        }
    });
});
</script>



<!-- SGCE FIX18: botones de activar/desactivar avisos iguales al estilo tinto de eliminar -->
<style id="SgceFix18AvisosBotonesTintoFinal">
html body .ModalAvisoEstado .BtnCancelDelete,
html body .ModalAvisoEstado .BtnConfirmDelete,
html body .ModalAvisoEstado .BtnConfirmDesactivar,
html body .ModalAvisoEstado .BtnConfirmActivar{
    min-width:190px !important;
    min-height:46px !important;
    border-radius:999px !important;
    font-weight:900 !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:8px !important;
    transition:.22s ease !important;
    padding:0 22px !important;
    text-transform:uppercase !important;
    box-shadow:0 10px 22px rgba(122,8,24,.12) !important;
}
html body .ModalAvisoEstado .BtnCancelDelete{
    background:#FFFFFF !important;
    color:#7A0818 !important;
    border:3px solid #7A0818 !important;
}
html body .ModalAvisoEstado .BtnCancelDelete:hover{
    background:linear-gradient(135deg,#7A0818,#4F0610) !important;
    color:#FFFFFF !important;
    border-color:#4F0610 !important;
    transform:translateY(-2px) !important;
    box-shadow:0 14px 30px rgba(122,8,24,.28) !important;
}
html body .ModalAvisoEstado .BtnConfirmDelete,
html body .ModalAvisoEstado .BtnConfirmDesactivar,
html body .ModalAvisoEstado .BtnConfirmActivar{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border:3px solid #4F0610 !important;
}
html body .ModalAvisoEstado .BtnConfirmDelete:hover,
html body .ModalAvisoEstado .BtnConfirmDesactivar:hover,
html body .ModalAvisoEstado .BtnConfirmActivar:hover{
    background:linear-gradient(135deg,#4F0610,#2E0309) !important;
    color:#FFFFFF !important;
    border-color:#2E0309 !important;
    transform:translateY(-2px) !important;
    box-shadow:0 14px 30px rgba(79,6,16,.32) !important;
}
html body .ModalAvisoEstado .BtnCancelDelete i,
html body .ModalAvisoEstado .BtnConfirmDelete i,
html body .ModalAvisoEstado .BtnConfirmDesactivar i,
html body .ModalAvisoEstado .BtnConfirmActivar i{
    color:inherit !important;
}
html body .ModalAvisoEstado.show .modal-dialog{
    position:fixed !important;
    top:50% !important;
    left:50% !important;
    transform:translate(-50%, -50%) !important;
    margin:0 !important;
}
</style>
<script id="SgceFix18AvisosModalCenterHardJs">
(function(){
    function ForceImportant(Element, Property, Value){
        if(Element){ Element.style.setProperty(Property, Value, 'important'); }
    }
    function CenterAvisoModal(Modal){
        if(!Modal || !Modal.classList.contains('ModalAvisoEstado')){ return; }
        if(Modal.parentElement !== document.body){ document.body.appendChild(Modal); }
        var Dialog = Modal.querySelector('.modal-dialog');
        if(Dialog){
            ForceImportant(Dialog, 'position', 'fixed');
            ForceImportant(Dialog, 'top', '50%');
            ForceImportant(Dialog, 'left', '50%');
            ForceImportant(Dialog, 'transform', 'translate(-50%, -50%)');
            ForceImportant(Dialog, 'margin', '0');
            ForceImportant(Dialog, 'width', 'min(94vw, 520px)');
            ForceImportant(Dialog, 'max-width', 'min(94vw, 520px)');
        }
    }
    document.addEventListener('show.bs.modal', function(Event){ CenterAvisoModal(Event.target); setTimeout(function(){ CenterAvisoModal(Event.target); }, 20); }, true);
    document.addEventListener('shown.bs.modal', function(Event){ CenterAvisoModal(Event.target); setTimeout(function(){ CenterAvisoModal(Event.target); }, 90); }, true);
})();
</script>


<!-- SGCE FIX19 DEFINITIVO: botones de activar/desactivar avisos en tinto, sin gris -->
<style id="SgceFix19AvisosBotonesTintoHard">
html body .ModalAvisoEstado .BtnCancelDelete,
html body .ModalAvisoEstado button.BtnCancelDelete,
html body .ModalAvisoEstado .BtnConfirmDelete,
html body .ModalAvisoEstado .BtnConfirmDesactivar,
html body .ModalAvisoEstado .BtnConfirmActivar,
html body .ModalAvisoEstado button.BtnConfirmDelete,
html body .ModalAvisoEstado button.BtnConfirmDesactivar,
html body .ModalAvisoEstado button.BtnConfirmActivar{
    min-width:190px !important;
    min-height:48px !important;
    border-radius:999px !important;
    font-weight:900 !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:8px !important;
    padding:0 22px !important;
    text-transform:uppercase !important;
    text-decoration:none !important;
    opacity:1 !important;
    transition:.22s ease !important;
}
html body .ModalAvisoEstado .BtnCancelDelete,
html body .ModalAvisoEstado button.BtnCancelDelete{
    background:#FFFFFF !important;
    background-image:none !important;
    color:#7A0818 !important;
    border:3px solid #7A0818 !important;
    box-shadow:0 10px 24px rgba(122,8,24,.14) !important;
}
html body .ModalAvisoEstado .BtnCancelDelete:hover,
html body .ModalAvisoEstado button.BtnCancelDelete:hover,
html body .ModalAvisoEstado .BtnCancelDelete:focus,
html body .ModalAvisoEstado button.BtnCancelDelete:focus{
    background:linear-gradient(135deg,#7A0818,#4F0610) !important;
    color:#FFFFFF !important;
    border-color:#4F0610 !important;
    transform:translateY(-2px) !important;
    box-shadow:0 14px 30px rgba(122,8,24,.30) !important;
}
html body .ModalAvisoEstado .BtnConfirmDelete,
html body .ModalAvisoEstado .BtnConfirmDesactivar,
html body .ModalAvisoEstado .BtnConfirmActivar,
html body .ModalAvisoEstado button.BtnConfirmDelete,
html body .ModalAvisoEstado button.BtnConfirmDesactivar,
html body .ModalAvisoEstado button.BtnConfirmActivar{
    background:linear-gradient(135deg,#7A0818,#A10D26) !important;
    color:#FFFFFF !important;
    border:3px solid #4F0610 !important;
    box-shadow:0 12px 28px rgba(122,8,24,.24) !important;
}
html body .ModalAvisoEstado .BtnConfirmDelete:hover,
html body .ModalAvisoEstado .BtnConfirmDesactivar:hover,
html body .ModalAvisoEstado .BtnConfirmActivar:hover,
html body .ModalAvisoEstado button.BtnConfirmDelete:hover,
html body .ModalAvisoEstado button.BtnConfirmDesactivar:hover,
html body .ModalAvisoEstado button.BtnConfirmActivar:hover,
html body .ModalAvisoEstado .BtnConfirmDelete:focus,
html body .ModalAvisoEstado .BtnConfirmDesactivar:focus,
html body .ModalAvisoEstado .BtnConfirmActivar:focus,
html body .ModalAvisoEstado button.BtnConfirmDelete:focus,
html body .ModalAvisoEstado button.BtnConfirmDesactivar:focus,
html body .ModalAvisoEstado button.BtnConfirmActivar:focus{
    background:linear-gradient(135deg,#4F0610,#2E0309) !important;
    color:#FFFFFF !important;
    border-color:#2E0309 !important;
    transform:translateY(-2px) !important;
    box-shadow:0 16px 34px rgba(79,6,16,.34) !important;
}
html body .ModalAvisoEstado .BtnCancelDelete *,
html body .ModalAvisoEstado .BtnConfirmDelete *,
html body .ModalAvisoEstado .BtnConfirmDesactivar *,
html body .ModalAvisoEstado .BtnConfirmActivar *{
    color:inherit !important;
}
@media(max-width:576px){
    html body .ModalAvisoEstado .BtnCancelDelete,
    html body .ModalAvisoEstado .BtnConfirmDelete,
    html body .ModalAvisoEstado .BtnConfirmDesactivar,
    html body .ModalAvisoEstado .BtnConfirmActivar{
        width:100% !important;
        min-width:100% !important;
    }
}
</style>

</body>
</html>
