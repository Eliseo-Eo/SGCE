<?php
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
        $Turno = trim($_POST['Turno'] ?? '');

        if (!ValidarGrado($Grado) || $Grupo === '' || ($Turno !== 'Matutino' && $Turno !== 'Vespertino')) {
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
        $Turno = trim($_POST['Turno'] ?? '');

        if ($Id <= 0 || !ValidarGrado($Grado) || $Grupo === '' || ($Turno !== 'Matutino' && $Turno !== 'Vespertino')) {
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
        $Materia = trim($_POST['Materia'] ?? '');

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
        $Materia = trim($_POST['Materia'] ?? '');

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
    <title>SGCE - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['Mensaje'], ENT_QUOTES, 'UTF-8') ?>
            <?php unset($_SESSION['Mensaje']); ?>
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
                                        <input type="text" name="User" class="form-control form-control-sm" placeholder="Usuario" required autocomplete="off">
                                    </div>

                                    <div class="col-6">
                                        <input type="password" name="Pass" class="form-control form-control-sm" placeholder="Contraseña" required autocomplete="off">
                                    </div>
                                </div>

                                <button class="btn btn-sm btn-guinda w-100 fw-bold">
                                    Guardar Maestro
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
                                    Formato CSV: <code>Nombre, Usuario, Contraseña</code>
                                </p>

                                <input type="file" name="CsvDocentes" class="form-control form-control-sm mb-3" accept=".csv" required>

                                <button type="submit" class="btn btn-sm btn-outline-success w-100 fw-bold">
                                    Cargar Archivo
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

                                        <td class="d-flex justify-content-center gap-2">
                                            <button class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#EM<?= $M['Id'] ?>">
                                                <i class="fa fa-pen"></i>
                                            </button>

                                            <form method="POST" class="m-0 p-0" onsubmit="return confirm('¿Eliminar Docente?')">
                                                <input type="hidden" name="Tab" value="maestros">
                                                <button type="submit" name="DelMaestro" value="<?= $M['Id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
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

                                <label class="small text-muted">Usuario</label>
                                <input type="text"
                                       name="User"
                                       value="<?= htmlspecialchars($M['Username']) ?>"
                                       class="form-control form-control-sm mb-2"
                                       required
                                       autocomplete="off">

                                <label class="small text-muted">Contraseña</label>
                                <input type="text"
                                       name="Pass"
                                       value="<?= htmlspecialchars($M['Password']) ?>"
                                       class="form-control form-control-sm mb-3"
                                       required
                                       autocomplete="off">

                                <button class="btn btn-sm btn-success w-100">Guardar Cambios</button>

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
                                    <option value="">Selecciona Turno...</option>
                                    <option value="Matutino">Matutino</option>
                                    <option value="Vespertino">Vespertino</option>
                                </select>

                                <button class="btn btn-sm btn-primary w-100 fw-bold">Guardar Grupo</button>
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
                                            <span class="badge bg-<?= $G['Turno']=='Matutino' ? 'primary' : 'warning text-dark' ?>">
                                                <?= htmlspecialchars($G['Turno']) ?>
                                            </span>
                                        </td>

                                        <!-- CALIFICACIONES DEL GRUPO -->
                                        <td class="text-center">
                                            <div class="ExportLabel">Calif.</div>
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel" target="_blank" title="Calificaciones del grupo en Excel" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel">
                                                    <i class="fa-solid fa-file-excel"></i>
                                                </a>
                                                <a class="ExportIcon ExportPdf" target="_blank" title="Calificaciones del grupo en PDF" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>
                                            </div>
                                        </td>

                                        <!-- ASISTENCIAS DE HOY DEL GRUPO -->
                                        <td class="text-center">
                                            <div class="ExportLabel">Hoy</div>
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportHoy" target="_blank" title="Asistencias de hoy del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                    <i class="fa-solid fa-file-excel"></i>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportHoy" target="_blank" title="Asistencias de hoy del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>
                                            </div>
                                        </td>

                                        <!-- TODAS LAS ASISTENCIAS DEL GRUPO -->
                                        <td class="text-center">
                                            <div class="ExportLabel">Todas</div>
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportTodas" target="_blank" title="Todas las asistencias del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Todas">
                                                    <i class="fa-solid fa-file-excel"></i>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportTodas" target="_blank" title="Todas las asistencias del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Todas">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center d-flex justify-content-center gap-2">

                                            <button class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#EG<?= $G['Id'] ?>">
                                                <i class="fa fa-pen"></i>
                                            </button>

                                            <form method="POST" class="m-0 p-0" onsubmit="return confirm('¿Eliminar Grupo?')">
                                                <input type="hidden" name="Tab" value="grupos">
                                                <button type="submit" name="DelGrupo" value="<?= $G['Id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>

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
                                        <option value="Matutino" <?= $G['Turno']=='Matutino' ? 'selected' : '' ?>>Matutino</option>
                                        <option value="Vespertino" <?= $G['Turno']=='Vespertino' ? 'selected' : '' ?>>Vespertino</option>
                                    </select>

                                    <button class="btn btn-sm btn-success w-100">Guardar Cambios</button>

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
                                    <i class="fa-solid fa-plus-circle me-1"></i> Registrar
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
                                    <i class="fa-solid fa-cloud-upload me-1"></i> Cargar CSV
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

                                            <td class="text-center d-flex justify-content-center gap-2">

                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#EAl<?= $Al['Id'] ?>">
                                                    <i class="fa fa-pen"></i>
                                                </button>

                                                <form method="POST" class="m-0 p-0" onsubmit="return confirm('¿Confirmar baja?')">
                                                    <input type="hidden" name="Tab" value="alumnos">
                                                    <button type="submit" name="DelAlumno" value="<?= $Al['Id'] ?>" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>

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

                            <button class="btn btn-primary w-100">Guardar Cambios</button>

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

                    <form method="POST" class="row g-3 align-items-end mb-4">
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
                            <input type="text" name="Materia" class="form-control" placeholder="Ej: Matemáticas I" required>
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-dark w-100 fw-bold">
                                <i class="fa-solid fa-plus me-1"></i> Vincular
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
                                        <span class="badge bg-light text-dark border">
                                            <?= $Asg['Grado'] ?> "<?= $Asg['Grupo'] ?>"
                                        </span>
                                        <small class="text-muted"><?= $Asg['Turno'] ?></small>
                                    </td>

                                    <!-- CALIFICACIONES -->
                                    <td class="text-center">
                                        <div class="ExportLabel">Calif.</div>
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel"
                                               target="_blank"
                                               title="Exportar calificaciones en Excel"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel">
                                                <i class="fa-solid fa-file-excel"></i>
                                            </a>

                                            <a class="ExportIcon ExportPdf"
                                               target="_blank"
                                               title="Exportar calificaciones en PDF"
                                               href="ExportarCalificaciones.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf">
                                                <i class="fa-solid fa-file-pdf"></i>
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
                                                <i class="fa-solid fa-file-excel"></i>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportHoy"
                                               target="_blank"
                                               title="Exportar asistencias de hoy en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                <i class="fa-solid fa-file-pdf"></i>
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
                                                <i class="fa-solid fa-file-excel"></i>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportTodas"
                                               target="_blank"
                                               title="Exportar todas las asistencias en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf&Rango=Todas">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                        </div>
                                    </td>

                                    <td class="text-center d-flex justify-content-center gap-2">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#EAsg<?= $Asg['Id'] ?>">
                                            <i class="fa fa-pen"></i>
                                        </button>

                                        <form method="POST" class="m-0 p-0" onsubmit="return confirm('¿Eliminar esta asignación?')">
                                            <input type="hidden" name="Tab" value="asignaciones">
                                            <button type="submit" name="DelAsignacion" value="<?= $Asg['Id'] ?>" class="btn btn-sm btn-outline-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
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

                            <button class="btn btn-primary w-100">Guardar Cambios</button>

                        </div>
                    </form>

                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {

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
    // BUSCADOR + PAGINACIÓN (30 POR PÁGINA)
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

});
</script>
</body>
</html>