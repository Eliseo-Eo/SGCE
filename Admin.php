<?php

/*
    Archivo: Admin.php
    Descripción: Panel principal del administrador.
    Aquí controlo maestros, grupos, alumnos, asignaciones, exportaciones, importaciones, búsquedas y paginación.
    También mantengo activa la pestaña donde estaba trabajando después de guardar, editar o eliminar.
*/

require 'Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);
if (!$UserSession || $UserSession['Rol'] !== 'admin') { header('Location: index.php'); exit; }

// ================================
// HELPERS
// ================================

function ValidarGrado($Valor) {
    $Valor = trim((string)$Valor);
    return ($Valor !== '' && ctype_digit($Valor));
}

function NormalizarGrupo($Valor) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return ''; }

    $Valor = strtoupper($Valor);

    // Solo letras A-Z (una o más)
    if (!preg_match('/^[A-Z]+$/', $Valor)) { return ''; }

    return $Valor;
}

// NOMBRE: SOLO LETRAS + ESPACIOS (incluye acentos/Ñ) y MAYÚSCULAS
function NormalizarNombre($Valor) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return ''; }

    $Valor = preg_replace('/\s+/u', ' ', $Valor);

    if (!preg_match('/^[\p{L}\s]+$/u', $Valor)) { return ''; }

    if (function_exists('mb_strtoupper')) {
        $Valor = mb_strtoupper($Valor, 'UTF-8');
    } else {
        $Valor = strtoupper($Valor);
    }

    return $Valor;
}

// Con esta función guardo en MAYÚSCULAS todos los textos normales del sistema.
// No la uso en usuario ni contraseña porque esos campos deben respetar mayúsculas/minúsculas.
function NormalizarMayusculas($Valor) {
    $Valor = trim((string)$Valor);
    if ($Valor === '') { return ''; }

    $Valor = preg_replace('/\s+/u', ' ', $Valor);

    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($Valor, 'UTF-8');
    }

    return strtoupper($Valor);
}

// El turno también se guarda en MAYÚSCULAS para mantener uniforme la base de datos.
function NormalizarTurno($Valor) {
    $Valor = NormalizarMayusculas($Valor);
    return in_array($Valor, ['MATUTINO', 'VESPERTINO'], true) ? $Valor : '';
}

// ================================
// HELPERS (TAB + REDIRECT)
// ================================

function TabPermitida($Tab) {
    $Permitidas = ['maestros','grupos','alumnos','asignaciones'];
    return in_array($Tab, $Permitidas, true) ? $Tab : 'maestros';
}

function RedirectTab($Tab) {
    $Tab = TabPermitida($Tab);
    header("Location: Admin.php?Tab=" . urlencode($Tab));
    exit;
}

// Tab actual (para pintar activo en UI)
$TabActual = TabPermitida($_GET['Tab'] ?? $_POST['Tab'] ?? ($_SESSION['Tab'] ?? 'maestros'));
$_SESSION['Tab'] = $TabActual;

// ================================
// --- LÓGICA DE PROCESAMIENTO ---
// ================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $TabPost = TabPermitida($_POST['Tab'] ?? 'maestros');
    $_SESSION['Tab'] = $TabPost;

    // ----------------------------
    // ELIMINAR MAESTRO
    // ----------------------------
    if (isset($_POST['DelMaestro'])) {

        $Id = intval($_POST['DelMaestro']);

        if ($Id > 0) {

            try {
                $Pdo->prepare("DELETE FROM Usuarios WHERE Id = ?")->execute([$Id]);
                $_SESSION['Mensaje'] = "Docente Eliminado";
            } catch (PDOException $Ex) {
                $_SESSION['Mensaje'] = "Error al eliminar docente.";
            }

            RedirectTab($TabPost);
        }
    }

    // ----------------------------
    // ELIMINAR GRUPO
    // ----------------------------
    if (isset($_POST['DelGrupo'])) {

        $Id = intval($_POST['DelGrupo']);

        if ($Id > 0) {

            try {
                $Pdo->prepare("DELETE FROM Grupos WHERE Id = ?")->execute([$Id]);
                $_SESSION['Mensaje'] = "Grupo Eliminado";
            } catch (PDOException $Ex) {

                if ($Ex->getCode() === '23000') {
                    $_SESSION['Mensaje'] = "No se puede eliminar el grupo porque tiene datos relacionados (alumnos/asignaciones).";
                } else {
                    $_SESSION['Mensaje'] = "Error al eliminar grupo.";
                }
            }

            RedirectTab($TabPost);
        }
    }

    // ----------------------------
    // ELIMINAR ALUMNO
    // ----------------------------
    if (isset($_POST['DelAlumno'])) {

        $Id = intval($_POST['DelAlumno']);

        if ($Id > 0) {

            try {
                $Pdo->prepare("DELETE FROM Alumnos WHERE Id = ?")->execute([$Id]);
                $_SESSION['Mensaje'] = "Alumno Eliminado";
            } catch (PDOException $Ex) {
                $_SESSION['Mensaje'] = "Error al eliminar alumno.";
            }

            RedirectTab($TabPost);
        }
    }

    // ----------------------------
    // ELIMINAR ASIGNACION
    // ----------------------------
    if (isset($_POST['DelAsignacion'])) {

        $Id = intval($_POST['DelAsignacion']);

        if ($Id > 0) {

            try {
                $Pdo->prepare("DELETE FROM Asignaciones WHERE Id = ?")->execute([$Id]);
                $_SESSION['Mensaje'] = "Materia Desasignada";
            } catch (PDOException $Ex) {
                $_SESSION['Mensaje'] = "Error al desasignar materia.";
            }

            RedirectTab($TabPost);
        }
    }

    // ============================
    // ALTAS Y EDICIONES MANUALES
    // ============================

    // ----------------------------
    // MAESTROS
    // ----------------------------
    if (isset($_POST['AltaMaestro'])) {

        $User = trim($_POST['User'] ?? '');
        $Pass = trim($_POST['Pass'] ?? '');
        $Nombre = NormalizarNombre($_POST['Nombre'] ?? '');

        if ($User === '' || $Pass === '' || $Nombre === '') {
            $_SESSION['Mensaje'] = "Completa Todos Los Campos Del Docente. (Nombre solo letras)";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol)
                VALUES (?, ?, ?, 'maestro')
            ")->execute([$User, $Pass, $Nombre]);

            $_SESSION['Mensaje'] = "Docente Registrado";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "Ese usuario ya existe. Usa otro username.";
            } else {
                $_SESSION['Mensaje'] = "Error al registrar docente.";
            }
        }

        RedirectTab($TabPost);
    }

    if (isset($_POST['EditMaestro'])) {

        $Id = intval($_POST['Id'] ?? 0);

        $User = trim($_POST['User'] ?? '');
        $Pass = trim($_POST['Pass'] ?? '');
        $Nombre = NormalizarNombre($_POST['Nombre'] ?? '');

        if ($Id <= 0 || $User === '' || $Pass === '' || $Nombre === '') {
            $_SESSION['Mensaje'] = "Datos Del Docente Inválidos. (Nombre solo letras)";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                UPDATE Usuarios
                SET NombreCompleto = ?, Username = ?, Password = ?
                WHERE Id = ?
            ")->execute([$Nombre, $User, $Pass, $Id]);

            $_SESSION['Mensaje'] = "Docente Actualizado";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "Ese usuario ya existe. Usa otro username.";
            } else {
                $_SESSION['Mensaje'] = "Error al actualizar docente.";
            }
        }

        RedirectTab($TabPost);
    }

    // ----------------------------
    // GRUPOS
    // ----------------------------
    if (isset($_POST['AltaGrupo'])) {

        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = NormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = NormalizarTurno($_POST['Turno'] ?? '');

        if (!ValidarGrado($Grado) || $Grupo === '' || $Turno === '') {
            $_SESSION['Mensaje'] = "Grupo Inválido: Grado Solo Números y Grupo Solo Letras Mayúsculas.";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                INSERT INTO Grupos (Grado, Grupo, Turno)
                VALUES (?, ?, ?)
            ")->execute([$Grado, $Grupo, $Turno]);

            $_SESSION['Mensaje'] = "Grupo Creado";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "Ese grupo ya existe o hay una restricción en BD.";
            } else {
                $_SESSION['Mensaje'] = "Error al crear grupo.";
            }
        }

        RedirectTab($TabPost);
    }

    if (isset($_POST['EditGrupo'])) {

        $Id = intval($_POST['Id'] ?? 0);

        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = NormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = NormalizarTurno($_POST['Turno'] ?? '');

        if ($Id <= 0 || !ValidarGrado($Grado) || $Grupo === '' || $Turno === '') {
            $_SESSION['Mensaje'] = "Grupo Inválido: Grado Solo Números y Grupo Solo Letras Mayúsculas.";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                UPDATE Grupos
                SET Grado = ?, Grupo = ?, Turno = ?
                WHERE Id = ?
            ")->execute([$Grado, $Grupo, $Turno, $Id]);

            $_SESSION['Mensaje'] = "Grupo Actualizado";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "Ese grupo ya existe o hay una restricción en BD.";
            } else {
                $_SESSION['Mensaje'] = "Error al actualizar grupo.";
            }
        }

        RedirectTab($TabPost);
    }

    // ----------------------------
    // ALUMNOS
    // ----------------------------
    if (isset($_POST['AltaAlumno'])) {

        $Nombre = NormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        if ($Nombre === '' || $GrupoId <= 0) {
            $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. (Nombre solo letras)";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                INSERT INTO Alumnos (NombreCompleto, GrupoId)
                VALUES (?, ?)
            ")->execute([$Nombre, $GrupoId]);

            $_SESSION['Mensaje'] = "Alumno Inscrito";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "No se pudo inscribir (restricción en BD).";
            } else {
                $_SESSION['Mensaje'] = "Error al inscribir alumno.";
            }
        }

        RedirectTab($TabPost);
    }

    if (isset($_POST['EditAlumno'])) {

        $Id = intval($_POST['Id'] ?? 0);
        $Nombre = NormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        if ($Id <= 0 || $Nombre === '' || $GrupoId <= 0) {
            $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. (Nombre solo letras)";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                UPDATE Alumnos
                SET NombreCompleto = ?, GrupoId = ?
                WHERE Id = ?
            ")->execute([$Nombre, $GrupoId, $Id]);

            $_SESSION['Mensaje'] = "Alumno Actualizado";

        } catch (PDOException $Ex) {

            $_SESSION['Mensaje'] = "Error al actualizar alumno.";

        }

        RedirectTab($TabPost);
    }

    // ----------------------------
    // ASIGNACIONES
    // ----------------------------
    if (isset($_POST['AltaAsignacion'])) {

        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = NormalizarMayusculas($_POST['Materia'] ?? '');

        if ($MaestroId <= 0 || $GrupoId <= 0 || $Materia === '') {
            $_SESSION['Mensaje'] = "Datos De Asignación Inválidos.";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                INSERT INTO Asignaciones (MaestroId, GrupoId, MateriaNombre)
                VALUES (?, ?, ?)
            ")->execute([$MaestroId, $GrupoId, $Materia]);

            $_SESSION['Mensaje'] = "Materia Asignada";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "No se pudo asignar (restricción en BD).";
            } else {
                $_SESSION['Mensaje'] = "Error al asignar materia.";
            }
        }

        RedirectTab($TabPost);
    }

    if (isset($_POST['EditAsignacion'])) {

        $Id = intval($_POST['Id'] ?? 0);
        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = NormalizarMayusculas($_POST['Materia'] ?? '');

        if ($Id <= 0 || $MaestroId <= 0 || $GrupoId <= 0 || $Materia === '') {
            $_SESSION['Mensaje'] = "Datos De Asignación Inválidos.";
            RedirectTab($TabPost);
        }

        try {

            $Pdo->prepare("
                UPDATE Asignaciones
                SET MaestroId = ?, GrupoId = ?, MateriaNombre = ?
                WHERE Id = ?
            ")->execute([$MaestroId, $GrupoId, $Materia, $Id]);

            $_SESSION['Mensaje'] = "Asignación Modificada";

        } catch (PDOException $Ex) {

            $_SESSION['Mensaje'] = "Error al modificar asignación.";

        }

        RedirectTab($TabPost);
    }
}

// ================================
// --- CONSULTAS A LA BASE DE DATOS ---
// ================================

$Maestros = $Pdo->query("SELECT * FROM Usuarios WHERE Rol='maestro' ORDER BY NombreCompleto ASC")->fetchAll();
$Grupos   = $Pdo->query("SELECT * FROM Grupos ORDER BY Turno, Grado, Grupo ASC")->fetchAll();
$Alumnos  = $Pdo->query("SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos A LEFT JOIN Grupos G ON A.GrupoId = G.Id ORDER BY G.Turno, G.Grado, G.Grupo, A.NombreCompleto ASC")->fetchAll();
$Asignaciones = $Pdo->query("SELECT Asn.Id, Asn.MateriaNombre, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id ORDER BY U.NombreCompleto ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    
    <!-- FAVICON DEL SISTEMA: ICONO QUE APARECE EN LA PESTAÑA DEL NAVEGADOR -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
    <link rel="apple-touch-icon" href="favicon.png">
<title>SGCE - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
       :root{
            --Guinda:#7A0818;
            --GuindaHover:#5E0612;
            --Fondo:#EEF2F7;
            --Card:#FFFFFF;
            --Texto:#1F2937;
            --TextoClaro:#6B7280;
            --Borde:#E5E7EB;
            --Success:#16A34A;
            --Warning:#F59E0B;
            --Danger:#DC2626;
            --Primary:#2563EB;
        }

        body{
            background: linear-gradient(to bottom,#F8FAFC,#EEF2F7);
            font-family:'Segoe UI',sans-serif;
            color:var(--Texto);
            min-height:100vh;
        }

        .navbar-custom{
            background: linear-gradient(135deg,var(--Guinda),#A10D26);
            padding:14px 0;
            box-shadow: 0 8px 24px rgba(122,8,24,0.18);
        }

        .navbar-brand{
            font-size:1.35rem;
            font-weight:800;
            letter-spacing:0.5px;
        }

        .nav-tabs{ border:none; gap:10px; margin-bottom:28px; }

        .nav-tabs .nav-link{
            border:none;
            background:white;
            border-radius:16px;
            padding:14px 22px;
            font-weight:700;
            color:#6B7280;
            transition:0.2s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.04);
        }

        .nav-tabs .nav-link:hover{ transform:translateY(-2px); color:var(--Guinda); }

        .nav-tabs .nav-link.active{
            background: linear-gradient(135deg,var(--Guinda),#A10D26);
            color:white;
            box-shadow: 0 10px 24px rgba(122,8,24,0.25);
        }

        .card-custom{
            border:none;
            border-radius:26px;
            overflow:hidden;
            background:white;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
            transition:0.25s;
        }

        .card-custom:hover{ transform:translateY(-3px); }

        .card-header-custom{
            padding:20px 24px;
            font-size:1rem;
            font-weight:800;
            border-bottom:1px solid #F1F5F9;
            background:#FCFCFD;
        }

        .form-control,
        .form-select{
            border:2px solid #E5E7EB;
            border-radius:16px;
            padding:12px 14px;
            min-height:48px;
            box-shadow:none !important;
            transition:0.2s;
        }

        .form-control:focus,
        .form-select:focus{
            border-color:var(--Guinda);
            box-shadow: 0 0 0 4px rgba(122,8,24,0.08) !important;
        }

        .search-container{
            background:white;
            border-radius:18px;
            overflow:hidden;
            border:2px solid #E5E7EB;
        }

        .search-container .input-group-text{
            background:white;
            border:none;
            color:#9CA3AF;
            padding-left:16px;
        }

        .search-container .form-control{ border:none !important; }

        .btn{ border-radius:14px; font-weight:700; transition:0.2s; }
        .btn:hover{ transform:translateY(-2px); }

        .btn-guinda{
            background: linear-gradient(135deg,var(--Guinda),#A10D26);
            color:white;
            border:none;
        }

        .btn-guinda:hover{ color:white; box-shadow: 0 10px 22px rgba(122,8,24,0.25); }

        .table{ border-collapse:separate; border-spacing:0 10px; }

        .table thead th{
            border:none;
            color:#6B7280;
            font-size:0.82rem;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }

        .table tbody tr{
            background:white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            transition:0.2s;
        }

        .table tbody tr:hover{
            transform:scale(1.01);
            box-shadow: 0 8px 18px rgba(0,0,0,0.06);
        }

        .table td{
            vertical-align:middle;
            border:none;
            padding:16px 14px;
        }

        .table tbody tr td:first-child{ border-radius:16px 0 0 16px; }
        .table tbody tr td:last-child{ border-radius:0 16px 16px 0; }

        .badge{
            padding:9px 14px;
            border-radius:999px;
            font-weight:700;
        }

        .alert{
            border:none;
            border-radius:18px;
            padding:18px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.05);
        }

        .modal-content{
            border:none;
            border-radius:26px;
            overflow:hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.18);
        }

        .modal-body{ padding:28px; }

        ::-webkit-scrollbar{ width:10px; }
        ::-webkit-scrollbar-thumb{ background:#C7CBD1; border-radius:20px; }

        /* ============================
           ICONOS DE EXPORTACIÓN
           ============================ */
        .ExportIcons{
            display:flex;
            justify-content:center;
            align-items:center;
            gap:8px;
            flex-wrap:wrap;
        }

        .ExportIcon{
            width:36px;
            height:36px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border-radius:12px;
            border:1px solid #E5E7EB;
            background:white;
            text-decoration:none;
            transition:0.18s ease;
            font-size:17px;
        }

        .ExportIcon:hover{
            transform:translateY(-2px);
            box-shadow:0 8px 20px rgba(0,0,0,0.10);
            background:#F8FAFC;
        }

        .ExportExcel{ color:#16A34A; }
        .ExportPdf{ color:#DC2626; }

        .ExportHoy{
            border-color:#F59E0B;
            box-shadow:0 0 0 3px rgba(245,158,11,0.10);
        }

        .ExportTodas{
            border-color:#2563EB;
            box-shadow:0 0 0 3px rgba(37,99,235,0.08);
        }

        .ExportLabel{
            font-size:0.72rem;
            color:#6B7280;
            font-weight:700;
            margin-bottom:4px;
            text-transform:uppercase;
            letter-spacing:0.4px;
        }


        /* ============================
           ALINEACIÓN GENERAL Y BOTONES HOMOLOGADOS
           ============================ */
        .table th,
        .table td{
            text-align:center;
            vertical-align:middle !important;
        }

        .table td.text-start,
        .table th.text-start{
            text-align:center !important;
        }

        .table td.d-flex{
            display:table-cell !important;
        }

        .table td form{
            display:inline-flex;
            margin:0 4px !important;
            vertical-align:middle;
        }

        .AdminActions{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            flex-wrap:wrap;
            width:100%;
            min-height:44px;
        }

        .ActionBtn{
            min-width:92px;
            height:38px;
            padding:0 12px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:7px;
            border-radius:999px;
            font-weight:800;
            font-size:0.78rem;
            line-height:1;
            text-decoration:none;
            background:white;
            transition:0.18s ease;
            border:2px solid transparent;
        }

        .ActionBtn i{
            font-size:1rem;
        }

        .ActionBtn:hover{
            transform:translateY(-2px);
            box-shadow:0 10px 20px rgba(15,23,42,0.12);
        }

        .ActionEdit{
            color:#2563EB;
            border-color:#2563EB;
            box-shadow:0 0 0 3px rgba(37,99,235,0.08);
        }

        .ActionEdit:hover{
            color:white;
            background:#2563EB;
        }

        .ActionDelete{
            color:#DC2626;
            border-color:#DC2626;
            box-shadow:0 0 0 3px rgba(220,38,38,0.08);
        }

        .ActionDelete:hover{
            color:white;
            background:#DC2626;
        }

        .ExportIcon{
            width:auto;
            min-width:78px;
            height:40px;
            padding:0 10px;
            gap:7px;
            font-size:16px;
            font-weight:800;
            border-width:2px;
        }

        .ExportText{
            font-size:0.72rem;
            letter-spacing:0.2px;
        }

        .ExportLabel{
            text-align:center;
        }

        .AsignacionForm > div{
            display:flex;
            flex-direction:column;
            justify-content:flex-end;
        }

        .AsignacionForm label{
            min-height:18px;
            margin-bottom:6px;
        }

        .AsignacionForm .form-control,
        .AsignacionForm .form-select,
        .AsignacionForm .btn{
            min-height:52px;
        }

        .AsignacionForm .btn{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            border-radius:16px;
            white-space:nowrap;
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
        input:not([type="password"]):not([type="file"]):not([type="hidden"]):not(.TextoLibre),
        textarea:not(.TextoLibre),
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

        /* Campos libres: usuario y contraseña conservan mayúsculas/minúsculas reales. */
        .TextoLibre{
            text-transform:none !important;
        }

        .TextoLibre::placeholder{
            text-transform:uppercase !important;
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
           HOVER HOMOLOGADO PARA BOTONES DE TABLAS
           Mantengo todos los botones con fondo blanco, borde visible
           y al pasar el mouse se rellenan con el color de su borde.
           ========================================================== */
        .ExportIcon{
            border-width:2px !important;
            background:#FFFFFF !important;
            box-shadow:0 0 0 3px rgba(15,23,42,.04), 0 7px 18px rgba(15,23,42,.06) !important;
            transition:all .18s ease !important;
        }

        .ExportIcon i,
        .ExportIcon span{
            transition:color .18s ease !important;
        }

        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas){
            color:#16A34A !important;
            border-color:#16A34A !important;
        }

        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas):hover{
            background:#16A34A !important;
            border-color:#16A34A !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas){
            color:#DC2626 !important;
            border-color:#DC2626 !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas):hover{
            background:#DC2626 !important;
            border-color:#DC2626 !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportHoy.ExportExcel{
            color:#F59E0B !important;
            border-color:#F59E0B !important;
            box-shadow:0 0 0 3px rgba(245,158,11,.10), 0 7px 18px rgba(245,158,11,.10) !important;
        }

        .ExportIcon.ExportHoy.ExportExcel:hover{
            background:#F59E0B !important;
            border-color:#F59E0B !important;
            color:#111827 !important;
        }

        .ExportIcon.ExportHoy.ExportPdf{
            color:#EA580C !important;
            border-color:#EA580C !important;
            box-shadow:0 0 0 3px rgba(234,88,12,.10), 0 7px 18px rgba(234,88,12,.10) !important;
        }

        .ExportIcon.ExportHoy.ExportPdf:hover{
            background:#EA580C !important;
            border-color:#EA580C !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportTodas.ExportExcel{
            color:#2563EB !important;
            border-color:#2563EB !important;
            box-shadow:0 0 0 3px rgba(37,99,235,.10), 0 7px 18px rgba(37,99,235,.10) !important;
        }

        .ExportIcon.ExportTodas.ExportExcel:hover{
            background:#2563EB !important;
            border-color:#2563EB !important;
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportTodas.ExportPdf{
            color:#7C3AED !important;
            border-color:#7C3AED !important;
            box-shadow:0 0 0 3px rgba(124,58,237,.10), 0 7px 18px rgba(124,58,237,.10) !important;
        }

        .ExportIcon.ExportTodas.ExportPdf:hover{
            background:#7C3AED !important;
            border-color:#7C3AED !important;
            color:#FFFFFF !important;
        }

        .ActionBtn:hover,
        .btn-outline-primary:hover,
        .btn-outline-success:hover,
        .btn-outline-danger:hover,
        .btn-outline-secondary:hover{
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


        /* MODAL PROFESIONAL PARA CONFIRMAR ELIMINACIONES */
        .DeleteModalContent{
            border:0;
            border-radius:30px;
            overflow:hidden;
            box-shadow:0 26px 70px rgba(15,23,42,.25);
        }

        .DeleteModalHeader{
            background:linear-gradient(135deg,#7A0818,#A10D26);
            color:white;
            padding:28px;
            text-align:center;
        }

        .DeleteIcon{
            width:82px;
            height:82px;
            border-radius:26px;
            margin:0 auto 14px;
            background:rgba(255,255,255,.16);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:2.2rem;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.20);
        }

        .DeleteModalBody{
            padding:28px;
            text-align:center;
            background:white;
        }

        .DeleteWarningBox{
            background:#FFF7ED;
            border:1px solid #FDBA74;
            color:#9A3412;
            border-radius:18px;
            padding:14px 16px;
            font-weight:700;
            font-size:.92rem;
        }

        .BtnCancelDelete,
        .BtnConfirmDelete{
            min-height:46px;
            border-radius:999px;
            font-weight:900;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            transition:.2s ease;
            padding:0 22px;
        }

        .BtnCancelDelete{
            background:white;
            color:#64748B;
            border:2px solid #CBD5E1;
        }

        .BtnCancelDelete:hover{
            background:#64748B;
            color:white;
            transform:translateY(-2px);
            box-shadow:0 12px 24px rgba(100,116,139,.18);
        }

        .BtnConfirmDelete{
            background:white;
            color:#DC2626;
            border:2px solid #DC2626;
        }

        .BtnConfirmDelete:hover{
            background:#DC2626;
            color:white;
            transform:translateY(-2px);
            box-shadow:0 12px 24px rgba(220,38,38,.22);
        }


        /* MODAL PROFESIONAL PARA MODIFICAR REGISTROS */
        .EditModalContent{
            border:0 !important;
            border-radius:30px !important;
            overflow:hidden !important;
            box-shadow:0 26px 70px rgba(15,23,42,.25) !important;
        }

        .EditModalHeader{
            background:linear-gradient(135deg,#1D4ED8,#0EA5E9);
            color:white;
            padding:26px;
            text-align:center;
        }

        .EditIcon{
            width:78px;
            height:78px;
            border-radius:26px;
            margin:0 auto 14px;
            background:rgba(255,255,255,.16);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:2.05rem;
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.20);
        }

        .EditModalBody{
            padding:28px !important;
            background:white;
        }

        .EditInfoBox{
            background:#EFF6FF;
            border:1px solid #93C5FD;
            color:#1D4ED8;
            border-radius:18px;
            padding:12px 14px;
            font-weight:800;
            font-size:.9rem;
            margin-bottom:18px;
            text-align:center;
        }

        .EditModalContent label{
            text-transform:uppercase;
            font-weight:900 !important;
            color:#64748B !important;
        }

        .BtnCancelEdit,
        .BtnSaveEdit{
            min-height:46px;
            border-radius:999px;
            font-weight:900;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            transition:.2s ease;
            padding:0 20px;
            border:2px solid transparent;
            width:100%;
        }

        .BtnCancelEdit{
            background:white;
            color:#64748B;
            border-color:#CBD5E1;
        }

        .BtnCancelEdit:hover{
            background:#64748B;
            color:white;
            transform:translateY(-2px);
            box-shadow:0 12px 24px rgba(100,116,139,.18);
        }

        .BtnSaveEdit{
            background:white;
            color:#2563EB;
            border-color:#2563EB;
        }

        .BtnSaveEdit:hover{
            background:#2563EB;
            color:white;
            transform:translateY(-2px);
            box-shadow:0 12px 24px rgba(37,99,235,.22);
        }

        @media(max-width:576px){
            .EditModalHeader,
            .DeleteModalHeader{
                padding:22px 18px;
            }
            .EditIcon,
            .DeleteIcon{
                width:68px;
                height:68px;
                font-size:1.8rem;
            }
        }

    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-custom py-2">
    <div class="container-fluid px-4">
        <span class="navbar-brand"><i class="fa-solid fa-sliders text-light"></i> SGCE | <span class="fw-light fs-6">Administrador</span></span>
        <a href="Logout.php" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fa-solid fa-power-off"></i> Cerrar Sesión</a>
    </div>
</nav>

<div class="container-fluid px-4 mt-4">

    <?php if (isset($_SESSION['Mensaje'])): ?>
        <?php
            $MensajeTipo = $_SESSION['MensajeTipo'] ?? 'success';
            $MensajeIcono = ($MensajeTipo === 'danger') ? 'fa-circle-xmark' : 'fa-check-circle';
        ?>
        <div class="alert alert-<?= htmlspecialchars($MensajeTipo, ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fa-solid <?= htmlspecialchars($MensajeIcono, ENT_QUOTES, 'UTF-8') ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['Mensaje'], ENT_QUOTES, 'UTF-8') ?>
            <?php unset($_SESSION['Mensaje'], $_SESSION['MensajeTipo']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link <?= $TabActual==='maestros'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#maestros">
                <i class="fa-solid fa-user-tie"></i> Maestros
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $TabActual==='grupos'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#grupos">
                <i class="fa-solid fa-users-rectangle"></i> Grupos
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $TabActual==='alumnos'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#alumnos">
                <i class="fa-solid fa-children"></i> Alumnos
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $TabActual==='asignaciones'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#asignaciones">
                <i class="fa-solid fa-book-open"></i> Asignaciones
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ===================== MAESTROS ===================== -->

        <div class="tab-pane fade <?= $TabActual==='maestros'?'show active':'' ?>" id="maestros">
            <div class="row">

                <div class="col-xl-3 col-lg-4">

                    <div class="card card-custom border-start border-3 border-danger mb-3">
                        <div class="card-header-custom text-danger">
                            <i class="fa-solid fa-user-plus"></i> Registrar Maestro
                        </div>

                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="AltaMaestro">
                                <input type="hidden" name="Tab" value="maestros">

                                <div class="mb-2">
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control form-control-sm SoloLetrasMayus"
                                           placeholder="NOMBRE COMPLETO"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <input type="text" name="User" class="form-control form-control-sm TextoLibre" placeholder="USUARIO" required autocomplete="off">
                                    </div>

                                    <div class="col-6">
                                        <input type="password" name="Pass" class="form-control form-control-sm TextoLibre" placeholder="CONTRASEÑA" required autocomplete="off">
                                    </div>
                                </div>

                                <button class="btn btn-sm btn-guinda w-100 fw-bold">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar Maestro
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom border-start border-3 border-success">
                        <div class="card-header-custom text-success">
                            <i class="fa-solid fa-file-excel"></i> Importar Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="ImportarDocentes">
                                <input type="hidden" name="Tab" value="maestros">

                                <p class="text-muted small mb-2">
                                    FORMATO CSV: <code>NOMBRE, USUARIO, CONTRASEÑA</code>
                                </p>

                                <input type="file" name="CsvDocentes" class="form-control form-control-sm mb-3" accept=".csv" required>

                                <button type="submit" class="btn btn-sm btn-outline-success w-100 fw-bold">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Cargar Archivo
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8">

                    <div class="card card-custom p-3">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-muted">Docentes Registrados</h6>

                            <div class="input-group input-group-sm search-container w-50">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>

                                <input type="text" id="SearchMaestros" class="form-control" placeholder="Buscar docente o usuario...">
                            </div>
                        </div>

                        <div class="table-responsive">

                            <table class="table table-hover text-center align-middle" id="TableMaestros">

                                <thead>
                                    <tr>
                                        <th class="text-start">Nombre</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach($Maestros as $M): ?>
                                    <tr>
                                        <td class="text-start searchable"><?= htmlspecialchars($M['NombreCompleto']) ?></td>
                                        <td class="searchable"><?= htmlspecialchars($M['Username']) ?></td>

                                        <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit" data-bs-toggle="modal" data-bs-target="#EM<?= $M['Id'] ?>">
                                                <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                            </button>

                                            <form method="POST" class="m-0 p-0" data-confirm-delete="DOCENTE" data-confirm-message="¿DESEAS ELIMINAR ESTE DOCENTE? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                                                <input type="hidden" name="Tab" value="maestros">
                                                <button type="submit" name="DelMaestro" value="<?= $M['Id'] ?>" class="ActionBtn ActionDelete">
                                                    <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                        <!-- PAGINACIÓN -->
                        <div id="PagerMaestros" class="d-flex justify-content-center mt-3"></div>

                    </div>

                </div>

            </div>

            <?php foreach($Maestros as $M): ?>
            <div class="modal fade" id="EM<?= $M['Id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">

                        <form method="POST">
                            <div class="modal-body">

                                <h6 class="mb-3 border-bottom pb-2">Modificar Docente</h6>

                                <input type="hidden" name="EditMaestro">
                                <input type="hidden" name="Tab" value="maestros">
                                <input type="hidden" name="Id" value="<?= $M['Id'] ?>">

                                <label class="small text-muted">Nombre</label>
                                <input type="text"
                                       name="Nombre"
                                       value="<?= htmlspecialchars($M['NombreCompleto']) ?>"
                                       class="form-control form-control-sm mb-2 SoloLetrasMayus"
                                       required
                                       pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                       title="Solo letras y espacios"
                                       autocomplete="off">

                                <label class="small text-muted">USUARIO</label>
                                <input type="text"
                                       name="User"
                                       value="<?= htmlspecialchars($M['Username']) ?>"
                                       class="form-control form-control-sm mb-2 TextoLibre"
                                       required
                                       autocomplete="off">

                                <label class="small text-muted">CONTRASEÑA</label>
                                <input type="text"
                                       name="Pass"
                                       value="<?= htmlspecialchars($M['Password']) ?>"
                                       class="form-control form-control-sm mb-3 TextoLibre"
                                       required
                                       autocomplete="off">

                                <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        <!-- ===================== GRUPOS ===================== -->

        <div class="tab-pane fade <?= $TabActual==='grupos'?'show active':'' ?>" id="grupos">
            <div class="row">
                <div class="col-xl-3 col-lg-4">
                    <div class="card card-custom border-start border-3 border-primary">
                        <div class="card-header-custom text-primary"><i class="fa-solid fa-plus-square"></i> Crear Grupo</div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="AltaGrupo">
                                <input type="hidden" name="Tab" value="grupos">

                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <input type="text"
                                            name="Grado"
                                            class="form-control form-control-sm"
                                            placeholder="Grado (Ej: 1)"
                                            required
                                            inputmode="numeric"
                                            pattern="^\d+$"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>

                                    <div class="col-6">
                                        <input type="text"
                                            name="Grupo"
                                            class="form-control form-control-sm"
                                            placeholder="Grupo (Ej: A)"
                                            required
                                            pattern="^[A-Z]+$"
                                            oninput="this.value = this.value.toUpperCase().replace(/[^A-Z]/g,'')">
                                    </div>
                                </div>

                                <select name="Turno" class="form-select form-select-sm mb-3" required>
                                    <option value="">SELECCIONA TURNO...</option>
                                    <option value="MATUTINO">MATUTINO</option>
                                    <option value="VESPERTINO">VESPERTINO</option>
                                </select>

                                <button class="btn btn-sm btn-primary w-100 fw-bold"><i class="fa-solid fa-layer-group"></i> Guardar Grupo</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9 col-lg-8">
                    <div class="card card-custom p-3">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-muted">Grupos Existentes</h6>

                            <div class="input-group input-group-sm search-container w-50">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="SearchGrupos" class="form-control" placeholder="Buscar grupo o turno...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover text-center align-middle" id="TableGrupos">
                                <thead>
                                    <tr>
                                        <th>Grado</th>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <th class="text-center">Calif.</th>
                                        <th class="text-center">Asis. Hoy</th>
                                        <th class="text-center">Asis. Todas</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach($Grupos as $G): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($G['Grado']) ?></td>
                                        <td class="searchable"><?= htmlspecialchars($G['Grupo']) ?></td>

                                        <td class="searchable">
                                            <span class="badge bg-<?= strtoupper((string)$G['Turno']) === 'MATUTINO' ? 'primary' : 'warning text-dark' ?>">
                                                <?= htmlspecialchars($G['Turno']) ?>
                                            </span>
                                        </td>

                                        <!-- CALIFICACIONES DEL GRUPO -->
                                        <td class="text-center">
                                            <div class="ExportLabel">Calif.</div>
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel" target="_blank" title="Calificaciones del grupo en Excel" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf" target="_blank" title="Calificaciones del grupo en PDF" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <!-- ASISTENCIAS DE HOY DEL GRUPO -->
                                        <td class="text-center">
                                            <div class="ExportLabel">Hoy</div>
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportHoy" target="_blank" title="Asistencias de hoy del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportHoy" target="_blank" title="Asistencias de hoy del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <!-- TODAS LAS ASISTENCIAS DEL GRUPO -->
                                        <td class="text-center">
                                            <div class="ExportLabel">Todas</div>
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportTodas" target="_blank" title="Todas las asistencias del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Todas">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportTodas" target="_blank" title="Todas las asistencias del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Todas">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit" data-bs-toggle="modal" data-bs-target="#EG<?= $G['Id'] ?>">
                                                <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                            </button>

                                            <form method="POST" class="m-0 p-0" data-confirm-delete="GRUPO" data-confirm-message="¿DESEAS ELIMINAR ESTE GRUPO? SI TIENE DATOS RELACIONADOS, EL SISTEMA PUEDE IMPEDIRLO.">
                                                <input type="hidden" name="Tab" value="grupos">
                                                <button type="submit" name="DelGrupo" value="<?= $G['Id'] ?>" class="ActionBtn ActionDelete">
                                                    <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                                </button>
                                            </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>

                            </table>
                        </div>

                        <!-- PAGINACIÓN -->
                        <div id="PagerGrupos" class="d-flex justify-content-center mt-3"></div>

                    </div>
                </div>

                <?php foreach($Grupos as $G): ?>
                <div class="modal fade" id="EG<?= $G['Id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">

                            <form method="POST">
                                <div class="modal-body">

                                    <h6 class="mb-3 border-bottom pb-2">Modificar Grupo</h6>

                                    <input type="hidden" name="EditGrupo">
                                    <input type="hidden" name="Tab" value="grupos">
                                    <input type="hidden" name="Id" value="<?= $G['Id'] ?>">

                                    <label class="small text-muted">Grado</label>
                                    <input type="text"
                                           name="Grado"
                                           value="<?= htmlspecialchars($G['Grado']) ?>"
                                           class="form-control form-control-sm mb-2"
                                           required
                                           inputmode="numeric"
                                           pattern="^\d+$"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">

                                    <label class="small text-muted">Grupo</label>
                                    <input type="text"
                                           name="Grupo"
                                           value="<?= htmlspecialchars($G['Grupo']) ?>"
                                           class="form-control form-control-sm mb-2"
                                           required
                                           pattern="^[A-Z]+$"
                                           oninput="this.value = this.value.toUpperCase().replace(/[^A-Z]/g,'')">

                                    <label class="small text-muted">Turno</label>
                                    <select name="Turno" class="form-select form-select-sm mb-3" required>
                                        <option value="MATUTINO" <?= strtoupper((string)$G['Turno']) === 'MATUTINO' ? 'selected' : '' ?>>MATUTINO</option>
                                        <option value="VESPERTINO" <?= strtoupper((string)$G['Turno']) === 'VESPERTINO' ? 'selected' : '' ?>>VESPERTINO</option>
                                    </select>

                                    <button class="btn btn-sm btn-success w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                                </div>
                            </form>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        <!-- ===================== ALUMNOS ===================== -->

        <div class="tab-pane fade <?= $TabActual==='alumnos'?'show active':'' ?>" id="alumnos">
            <div class="row g-4">

                <div class="col-xl-3 col-lg-4">

                    <div class="card card-custom border-0 shadow-sm mb-4">

                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fa-solid fa-user-plus me-2 text-warning"></i>
                                Inscribir Alumno
                            </h6>
                        </div>

                        <div class="card-body">

                            <form method="POST">

                                <input type="hidden" name="AltaAlumno">
                                <input type="hidden" name="Tab" value="alumnos">

                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Nombre Completo</label>
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control SoloLetrasMayus"
                                           placeholder="NOMBRE COMPLETO"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Grupo</label>

                                    <select name="GrupoId" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= $G['Id'] ?>">
                                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-warning w-100 fw-bold text-dark">
                                    <i class="fa-solid fa-user-plus"></i> Registrar Alumno
                                </button>

                            </form>

                        </div>

                    </div>

                    <div class="card card-custom border-0 shadow-sm">

                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fa-solid fa-file-excel me-2 text-success"></i>
                                Importar Datos
                            </h6>
                        </div>

                        <div class="card-body">

                            <form action="Importar.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="ImportarAlumnos">
                                <input type="hidden" name="Tab" value="alumnos">

                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Grupo Destino</label>

                                    <select name="GrupoId" class="form-select form-select-sm" required>
                                        <option value="">¿A dónde van?</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= $G['Id'] ?>">
                                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <input type="file" name="CsvAlumnos" class="form-control form-control-sm" accept=".csv" required>
                                </div>

                                <button type="submit" class="btn btn-outline-success w-100 fw-bold">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Cargar CSV
                                </button>

                            </form>

                        </div>

                    </div>

                </div>

                <div class="col-xl-9 col-lg-8">

                    <div class="card card-custom shadow-sm border-0 h-100">
                        <div class="card-body p-4">

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="mb-0 fw-bold text-secondary">Padrón de Alumnos</h5>

                                <div class="input-group search-container" style="max-width: 300px;">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="SearchAlumnos" class="form-control border-start-0" placeholder="Buscar alumno...">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="TableAlumnos">

                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre del Alumno</th>
                                            <th>Grupo</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach($Alumnos as $Al): ?>
                                        <tr>
                                            <td class="searchable fw-medium"><?= htmlspecialchars($Al['NombreCompleto']) ?></td>

                                            <td class="searchable">
                                                <?= $Al['Grado']
                                                    ? "<span class='badge bg-light text-dark border'>".$Al['Grado']." ".$Al['Grupo']."</span>"
                                                    : '<span class="text-danger small">Sin Grupo</span>' ?>
                                            </td>

                                            <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit" data-bs-toggle="modal" data-bs-target="#EAl<?= $Al['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="ALUMNO" data-confirm-message="¿DESEAS DAR DE BAJA A ESTE ALUMNO? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                                                    <input type="hidden" name="Tab" value="alumnos">
                                                    <button type="submit" name="DelAlumno" value="<?= $Al['Id'] ?>" class="ActionBtn ActionDelete">
                                                        <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>

                                </table>
                            </div>

                            <!-- PAGINACIÓN -->
                            <div id="PagerAlumnos" class="d-flex justify-content-center mt-3"></div>

                        </div>
                    </div>

                </div>

            </div>
        </div>

        <?php foreach($Alumnos as $Al): ?>
        <div class="modal fade" id="EAl<?= $Al['Id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">

                    <form method="POST">
                        <div class="modal-body">

                            <h5 class="mb-4">Editar Alumno</h5>

                            <input type="hidden" name="EditAlumno">
                            <input type="hidden" name="Tab" value="alumnos">
                            <input type="hidden" name="Id" value="<?= $Al['Id'] ?>">

                            <div class="mb-3">
                                <label class="small">Nombre</label>
                                <input type="text"
                                       name="Nombre"
                                       value="<?= htmlspecialchars($Al['NombreCompleto']) ?>"
                                       class="form-control SoloLetrasMayus"
                                       required
                                       pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                       title="Solo letras y espacios"
                                       autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label class="small">Grupo</label>
                                <select name="GrupoId" class="form-select" required>
                                    <?php foreach($Grupos as $G): ?>
                                        <option value="<?= $G['Id'] ?>" <?= $G['Id'] == $Al['GrupoId'] ? 'selected' : '' ?>>
                                            <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                        </div>
                    </form>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- ===================== ASIGNACIONES ===================== -->

        <div class="tab-pane fade <?= $TabActual==='asignaciones'?'show active':'' ?>" id="asignaciones">
            <div class="card card-custom shadow-sm border-0">

                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-link text-dark me-2"></i>
                        Nueva Asignación Académica
                    </h6>
                </div>

                <div class="card-body p-4">

                    <form method="POST" class="row g-3 align-items-stretch mb-4 AsignacionForm">
                        <input type="hidden" name="AltaAsignacion">
                        <input type="hidden" name="Tab" value="asignaciones">

                        <div class="col-md-4">
                            <label class="small fw-bold text-muted">Seleccionar Docente</label>
                            <select name="MaestroId" class="form-select" required>
                                <option value="">Elegir profesor...</option>
                                <?php foreach($Maestros as $M): ?>
                                    <option value="<?= $M['Id'] ?>"><?= htmlspecialchars($M['NombreCompleto']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Asignar Grupo</label>
                            <select name="GrupoId" class="form-select" required>
                                <option value="">Elegir grupo...</option>
                                <?php foreach($Grupos as $G): ?>
                                    <option value="<?= $G['Id'] ?>">
                                        <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="small fw-bold text-muted">Nombre de la Materia</label>
                            <input type="text" name="Materia" class="form-control" placeholder="EJ: MATEMÁTICAS I" required>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-dark w-100 fw-bold">
                                <i class="fa-solid fa-link"></i> Vincular
                            </button>
                        </div>
                    </form>

                    <div class="d-flex justify-content-between align-items-center mb-3 border-top pt-4">
                        <h6 class="mb-0 fw-bold text-secondary">Cargas Académicas Activas</h6>

                        <div class="input-group search-container w-25">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" id="SearchAsig" class="form-control border-start-0" placeholder="Buscar carga...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="TableAsig">

                            <thead class="table-light">
                                <tr>
                                    <th>Docente</th>
                                    <th>Materia</th>
                                    <th>Grupo</th>
                                    <th class="text-center">Calif.</th>
                                    <th class="text-center">Asis. Hoy</th>
                                    <th class="text-center">Asis. Todas</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach($Asignaciones as $Asg): ?>
                                <tr>
                                    <td class="searchable fw-medium"><?= htmlspecialchars($Asg['Maestro']) ?></td>

                                    <td class="searchable text-danger fw-bold"><?= htmlspecialchars($Asg['MateriaNombre']) ?></td>

                                    <td class="searchable">
                                        <?php $TurnoAsignacion = strtoupper((string)$Asg['Turno']); ?>
                                        <span class="GrupoTurnoBadge <?= $TurnoAsignacion === 'MATUTINO' ? 'GrupoTurnoMatutino' : 'GrupoTurnoVespertino' ?>">
                                            <i class="fa-solid <?= $TurnoAsignacion === 'MATUTINO' ? 'fa-sun' : 'fa-moon' ?>"></i>
                                            <?= htmlspecialchars($Asg['Grado'], ENT_QUOTES, 'UTF-8') ?> "<?= htmlspecialchars($Asg['Grupo'], ENT_QUOTES, 'UTF-8') ?>" - <?= htmlspecialchars($TurnoAsignacion, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>

                                    <!-- CALIFICACIONES -->
                                    <td class="text-center">
                                        <div class="ExportLabel">Calif.</div>
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel"
                                               target="_blank"
                                               title="Exportar calificaciones en Excel"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf"
                                               target="_blank"
                                               title="Exportar calificaciones en PDF"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    <!-- ASISTENCIAS HOY -->
                                    <td class="text-center">
                                        <div class="ExportLabel">Hoy</div>
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel ExportHoy"
                                               target="_blank"
                                               title="Exportar asistencias de hoy en Excel"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportHoy"
                                               target="_blank"
                                               title="Exportar asistencias de hoy en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    <!-- ASISTENCIAS TODAS -->
                                    <td class="text-center">
                                        <div class="ExportLabel">Todas</div>
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel ExportTodas"
                                               target="_blank"
                                               title="Exportar todas las asistencias en Excel"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel&Rango=Todas">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportTodas"
                                               target="_blank"
                                               title="Exportar todas las asistencias en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf&Rango=Todas">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit" data-bs-toggle="modal" data-bs-target="#EAsg<?= $Asg['Id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                        </button>

                                        <form method="POST" class="m-0 p-0" data-confirm-delete="ASIGNACIÓN" data-confirm-message="¿DESEAS ELIMINAR ESTA ASIGNACIÓN ACADÉMICA?">
                                            <input type="hidden" name="Tab" value="asignaciones">
                                            <button type="submit" name="DelAsignacion" value="<?= $Asg['Id'] ?>" class="ActionBtn ActionDelete">
                                                <i class="fa-solid fa-trash-can"></i><span>Eliminar</span>
                                            </button>
                                        </form>
                                            </div>
                                        </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                    <!-- PAGINACIÓN -->
                    <div id="PagerAsig" class="d-flex justify-content-center mt-3"></div>

                </div>
            </div>
        </div>

        <?php foreach($Asignaciones as $Asg): ?>
        <div class="modal fade" id="EAsg<?= $Asg['Id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">

                    <form method="POST">
                        <div class="modal-body">

                            <h6 class="mb-3 border-bottom pb-2">Editar Asignación</h6>

                            <input type="hidden" name="EditAsignacion">
                            <input type="hidden" name="Tab" value="asignaciones">
                            <input type="hidden" name="Id" value="<?= $Asg['Id'] ?>">

                            <label class="small text-muted">Docente</label>
                            <select name="MaestroId" class="form-select mb-2" required>
                                <?php foreach($Maestros as $M): ?>
                                    <option value="<?= $M['Id'] ?>" <?= $M['Id'] == $Asg['MaestroId'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($M['NombreCompleto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="small text-muted">Grupo</label>
                            <select name="GrupoId" class="form-select mb-2" required>
                                <?php foreach($Grupos as $G): ?>
                                    <option value="<?= $G['Id'] ?>" <?= $G['Id'] == $Asg['GrupoId'] ? 'selected' : '' ?>>
                                        <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>"
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label class="small text-muted">Materia</label>
                            <input type="text" name="Materia" value="<?= htmlspecialchars($Asg['MateriaNombre']) ?>" class="form-control mb-3" required>

                            <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>

                        </div>
                    </form>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>


<!-- MODAL GLOBAL PARA CONFIRMAR ELIMINACIONES -->
<div class="modal fade" id="ModalConfirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content DeleteModalContent">
            <div class="DeleteModalHeader">
                <div class="DeleteIcon">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h4 class="fw-bold mb-1">CONFIRMAR ELIMINACIÓN</h4>
                <p class="mb-0 opacity-75" id="DeleteModalTipo">REGISTRO</p>
            </div>
            <div class="DeleteModalBody">
                <p class="fs-6 fw-bold mb-3" id="DeleteModalMensaje">¿DESEAS ELIMINAR ESTE REGISTRO?</p>
                <div class="DeleteWarningBox mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    Revisa bien antes de confirmar. Esta acción puede afectar información relacionada.
                </div>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button type="button" class="BtnCancelDelete" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> CANCELAR
                    </button>
                    <button type="button" class="BtnConfirmDelete" id="BtnConfirmarEliminar">
                        <i class="fa-solid fa-trash"></i> SÍ, ELIMINAR
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {



    // ============================
    // MODALES PROFESIONALES DE EDICIÓN
    // ============================
    document.querySelectorAll('.modal[id^="EM"], .modal[id^="EG"], .modal[id^="EAl"], .modal[id^="EAsg"]').forEach(function(Modal){
        const Content = Modal.querySelector('.modal-content');
        const Body = Modal.querySelector('.modal-body');
        const Form = Modal.querySelector('form');
        const Title = Body ? Body.querySelector('h5, h6') : null;

        if(!Content || !Body || !Title || Body.dataset.editDecorated === '1') return;

        let Titulo = (Title.textContent || 'MODIFICAR REGISTRO').trim().toUpperCase();
        let Subtitulo = 'REVISA LOS DATOS ANTES DE GUARDAR';

        if(Modal.id.indexOf('EM') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL DOCENTE';
        if(Modal.id.indexOf('EG') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL GRUPO';
        if(Modal.id.indexOf('EAl') === 0) Subtitulo = 'ACTUALIZAR INFORMACIÓN DEL ALUMNO';
        if(Modal.id.indexOf('EAsg') === 0) Subtitulo = 'ACTUALIZAR ASIGNACIÓN ACADÉMICA';

        Content.classList.add('EditModalContent');
        Body.classList.add('EditModalBody');
        Body.dataset.editDecorated = '1';

        const Header = document.createElement('div');
        Header.className = 'EditModalHeader';
        Header.innerHTML = '<div class="EditIcon"><i class="fa-solid fa-pen-to-square"></i></div>' +
                           '<h4 class="fw-bold mb-1">' + Titulo + '</h4>' +
                           '<p class="mb-0 opacity-75">' + Subtitulo + '</p>';

        const Info = document.createElement('div');
        Info.className = 'EditInfoBox';
        Info.innerHTML = '<i class="fa-solid fa-circle-info me-1"></i> LOS CAMBIOS SE GUARDARÁN AL CONFIRMAR.';

        Title.remove();
        Content.insertBefore(Header, Content.firstChild);
        Body.insertBefore(Info, Body.firstChild);

        const SubmitBtn = Body.querySelector('button[type="submit"], button:not([type])');
        if(SubmitBtn){
            SubmitBtn.className = 'BtnSaveEdit';
            SubmitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> GUARDAR CAMBIOS';

            const BtnRow = document.createElement('div');
            BtnRow.className = 'row g-2 mt-2';

            const ColCancel = document.createElement('div');
            ColCancel.className = 'col-12 col-sm-5';
            ColCancel.innerHTML = '<button type="button" class="BtnCancelEdit" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> CANCELAR</button>';

            const ColSave = document.createElement('div');
            ColSave.className = 'col-12 col-sm-7';

            SubmitBtn.parentNode.insertBefore(BtnRow, SubmitBtn);
            BtnRow.appendChild(ColCancel);
            BtnRow.appendChild(ColSave);
            ColSave.appendChild(SubmitBtn);
        }
    });

    // ============================
    // NOMBRES MAYÚSCULAS SOLO LETRAS
    // ============================
    function NormalizarInputNombre(El) {
        let Val = El.value || '';
        Val = Val.toUpperCase();
        Val = Val.replace(/[^A-ZÁÉÍÓÚÜÑ\s]/g, '');
        Val = Val.replace(/\s+/g, ' ');
        El.value = Val;
    }

    document.querySelectorAll('.SoloLetrasMayus').forEach(function(El){
        El.addEventListener('input', function(){ NormalizarInputNombre(El); });
        El.addEventListener('blur', function(){ NormalizarInputNombre(El); });
    });

    // ============================
    // BUSCADOR + PAGINACIÓN (10 POR PÁGINA)
    // ============================
    function SetupSearchPagination(InputId, TableId, PagerId, RowsPerPage) {

        const Input = document.getElementById(InputId);
        const Table = document.getElementById(TableId);
        const Pager = document.getElementById(PagerId);

        if(!Table || !Table.tBodies.length) return;

        let CurrentPage = 1;

        function GetRows() {
            return Array.from(Table.tBodies[0].rows);
        }

        function RenderPager(TotalPages) {
            if(!Pager) return;
            Pager.innerHTML = '';
            if (TotalPages <= 1) return;

            const CreateBtn = function(Label, Page, Disabled, Active) {
                const Btn = document.createElement('button');
                Btn.type = 'button';
                Btn.className = 'btn btn-sm mx-1 ' + (Active ? 'btn-guinda' : 'btn-outline-secondary');
                Btn.textContent = Label;
                Btn.disabled = !!Disabled;
                Btn.addEventListener('click', function(){
                    CurrentPage = Page;
                    Apply();
                });
                return Btn;
            };

            Pager.appendChild(CreateBtn('«', 1, CurrentPage === 1, false));
            Pager.appendChild(CreateBtn('‹', Math.max(1, CurrentPage - 1), CurrentPage === 1, false));

            let Start = Math.max(1, CurrentPage - 2);
            let End = Math.min(TotalPages, Start + 4);
            Start = Math.max(1, End - 4);

            for (let P = Start; P <= End; P++) {
                Pager.appendChild(CreateBtn(String(P), P, false, P === CurrentPage));
            }

            Pager.appendChild(CreateBtn('›', Math.min(TotalPages, CurrentPage + 1), CurrentPage === TotalPages, false));
            Pager.appendChild(CreateBtn('»', TotalPages, CurrentPage === TotalPages, false));
        }

        function Apply() {
            const Filter = (Input ? (Input.value || '').toLowerCase() : '');
            const Rows = GetRows();

            let Matched = [];

            Rows.forEach(function(Row){
                const Cells = Array.from(Row.getElementsByClassName('searchable'));
                const Text = Cells.map(C => (C.innerText || '').toLowerCase()).join(' ');
                const Match = (Filter === '') ? true : (Text.indexOf(Filter) > -1);
                Row.dataset.match = Match ? '1' : '0';
                if (Match) Matched.push(Row);
            });

            const TotalPages = Math.max(1, Math.ceil(Matched.length / RowsPerPage));
            if (CurrentPage > TotalPages) CurrentPage = TotalPages;

            Rows.forEach(function(Row){ Row.style.display = 'none'; });

            const StartIndex = (CurrentPage - 1) * RowsPerPage;
            const EndIndex = StartIndex + RowsPerPage;

            Matched.slice(StartIndex, EndIndex).forEach(function(Row){
                Row.style.display = '';
            });

            RenderPager(TotalPages);
        }

        if (Input) {
            Input.addEventListener('keyup', function(){
                CurrentPage = 1;
                Apply();
            });
        }

        Apply();
    }

    // 10 por página
    SetupSearchPagination('SearchMaestros', 'TableMaestros', 'PagerMaestros', 10);
    SetupSearchPagination('SearchGrupos',   'TableGrupos',   'PagerGrupos',   10);
    SetupSearchPagination('SearchAlumnos',  'TableAlumnos',  'PagerAlumnos',  10);
    SetupSearchPagination('SearchAsig',     'TableAsig',     'PagerAsig',     10);



    // ============================
    // CONFIRMACIÓN BONITA PARA ELIMINAR
    // Reemplaza el confirm simple del navegador por un modal profesional.
    // ============================
    let FormularioEliminarPendiente = null;
    let BotonEliminarPendiente = null;
    const ModalEliminarElemento = document.getElementById('ModalConfirmarEliminar');
    const TextoTipoEliminar = document.getElementById('DeleteModalTipo');
    const TextoMensajeEliminar = document.getElementById('DeleteModalMensaje');
    const BtnConfirmarEliminar = document.getElementById('BtnConfirmarEliminar');

    if (ModalEliminarElemento && BtnConfirmarEliminar) {
        const ModalEliminar = new bootstrap.Modal(ModalEliminarElemento);

        document.querySelectorAll('form[data-confirm-delete]').forEach(function(Formulario){
            Formulario.addEventListener('submit', function(Evento){
                if (Formulario.dataset.confirmado === '1') {
                    return true;
                }

                Evento.preventDefault();
                FormularioEliminarPendiente = Formulario;
                BotonEliminarPendiente = Evento.submitter || null;

                if (TextoTipoEliminar) {
                    TextoTipoEliminar.textContent = Formulario.dataset.confirmDelete || 'REGISTRO';
                }

                if (TextoMensajeEliminar) {
                    TextoMensajeEliminar.textContent = Formulario.dataset.confirmMessage || '¿DESEAS ELIMINAR ESTE REGISTRO?';
                }

                BtnConfirmarEliminar.innerHTML = '<i class="fa-solid fa-trash"></i> SÍ, ELIMINAR';
                BtnConfirmarEliminar.disabled = false;
                ModalEliminar.show();
            });
        });

        BtnConfirmarEliminar.addEventListener('click', function(){
            if (!FormularioEliminarPendiente) {
                return;
            }

            BtnConfirmarEliminar.disabled = true;
            BtnConfirmarEliminar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ELIMINANDO...';
            FormularioEliminarPendiente.dataset.confirmado = '1';

            if (BotonEliminarPendiente && BotonEliminarPendiente.name) {
                const CampoAccion = document.createElement('input');
                CampoAccion.type = 'hidden';
                CampoAccion.name = BotonEliminarPendiente.name;
                CampoAccion.value = BotonEliminarPendiente.value;
                FormularioEliminarPendiente.appendChild(CampoAccion);
            }

            FormularioEliminarPendiente.submit();
        });
    }


    // ============================
    // PERSISTIR TAB EN URL
    // ============================
    const TabButtons = document.querySelectorAll('button[data-bs-toggle="tab"]');
    TabButtons.forEach(function(Btn){
        Btn.addEventListener('shown.bs.tab', function (Event) {
            const Target = Event.target.getAttribute('data-bs-target');
            if(!Target) return;

            const Tab = Target.replace('#','');
            const Url = new URL(window.location.href);
            Url.searchParams.set('Tab', Tab);
            history.replaceState({}, '', Url.toString());
        });
    });


    // ============================================================
    // HOMOLOGAR TEXTBOX, TEXTAREA Y SELECT EN MAYÚSCULAS
    // ------------------------------------------------------------
    // Aquí hago que los textos visibles se vean en mayúsculas.
    // Usuario y contraseña NO se convierten porque deben respetar
    // exactamente lo que se escribe para iniciar sesión correctamente.
    // ============================================================
    function DebeRespetarMinusculas(Control) {
        const Nombre = (Control.getAttribute('name') || '').toLowerCase();
        const Id = (Control.getAttribute('id') || '').toLowerCase();
        const Tipo = (Control.getAttribute('type') || '').toLowerCase();

        return Tipo === 'password'
            || Nombre === 'user'
            || Nombre === 'username'
            || Nombre === 'pass'
            || Nombre === 'password'
            || Id.includes('search');
    }

    document.querySelectorAll('input:not([type="file"]):not([type="hidden"]), textarea').forEach(function(Control){
        if (Control.placeholder) {
            Control.placeholder = Control.placeholder.toUpperCase();
        }

        if (!DebeRespetarMinusculas(Control)) {
            Control.addEventListener('input', function(){
                Control.value = (Control.value || '').toUpperCase();
            });
        }
    });

    document.querySelectorAll('select option').forEach(function(Opcion){
        Opcion.textContent = (Opcion.textContent || '').toUpperCase();
    });

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