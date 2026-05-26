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
    $Permitidas = ['inicio','maestros','grupos','alumnos','expedientes','asignaciones','bitacora'];
    return in_array($Tab, $Permitidas, true) ? $Tab : 'inicio';
}

function RedirectTab($Tab) {
    $Tab = TabPermitida($Tab);
    header("Location: Admin.php?Tab=" . urlencode($Tab));
    exit;
}

// Tab actual (para pintar activo en UI)
// Al entrar al panel sin indicar pestaña, siempre inicio en el DASHBOARD.
// Ya no uso la última pestaña guardada en sesión para evitar que al iniciar sesión abra Asignaciones u otra sección anterior.
$TabActual = TabPermitida($_GET['Tab'] ?? $_POST['Tab'] ?? 'inicio');
$_SESSION['Tab'] = $TabActual;

// ================================
// --- LÓGICA DE PROCESAMIENTO ---
// ================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    RequerirCsrfPost();

    $TabPost = TabPermitida($_POST['Tab'] ?? 'maestros');
    $_SESSION['Tab'] = $TabPost;

    // ----------------------------
    // ELIMINAR MAESTRO
    // ----------------------------
    if (isset($_POST['DelMaestro'])) {

        $Id = intval($_POST['DelMaestro']);

        if ($Id > 0) {

            try {
                $Pdo->prepare("UPDATE Usuarios SET Activo = 0, SessionToken = NULL WHERE Id = ? AND Rol = 'maestro'")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_DOCENTE', 'Usuarios', $Id, 'DOCENTE DESACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Docente Desactivado";
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
                $Pdo->prepare("UPDATE Grupos SET Activo = 0 WHERE Id = ?")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_GRUPO', 'Grupos', $Id, 'GRUPO DESACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Grupo Desactivado";
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
                $Pdo->prepare("UPDATE Alumnos SET Activo = 0 WHERE Id = ?")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_ALUMNO', 'Alumnos', $Id, 'ALUMNO DESACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Alumno Desactivado";
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
                $Pdo->prepare("UPDATE Asignaciones SET Activo = 0 WHERE Id = ?")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_ASIGNACION', 'Asignaciones', $Id, 'ASIGNACIÓN DESACTIVADA DESDE ADMIN');
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
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_DOCENTE', 'Usuarios', $Pdo->lastInsertId(), 'DOCENTE REGISTRADO');

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
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_DOCENTE', 'Usuarios', $Id, 'DOCENTE ACTUALIZADO');

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
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_GRUPO', 'Grupos', $Pdo->lastInsertId(), 'GRUPO CREADO');

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
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_GRUPO', 'Grupos', $Id, 'GRUPO ACTUALIZADO');

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
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_ALUMNO', 'Alumnos', $Pdo->lastInsertId(), 'ALUMNO INSCRITO');

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
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_ALUMNO', 'Alumnos', $Id, 'ALUMNO ACTUALIZADO');

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
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_ASIGNACION', 'Asignaciones', $Pdo->lastInsertId(), 'MATERIA ASIGNADA');

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
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_ASIGNACION', 'Asignaciones', $Id, 'ASIGNACIÓN MODIFICADA');

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

$Maestros = $Pdo->query("SELECT * FROM Usuarios WHERE Rol='maestro' AND Activo = 1 ORDER BY NombreCompleto ASC")->fetchAll();
$Grupos   = $Pdo->query("SELECT * FROM Grupos WHERE Activo = 1 ORDER BY Turno, Grado, Grupo ASC")->fetchAll();
$Alumnos  = $Pdo->query("SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos A LEFT JOIN Grupos G ON A.GrupoId = G.Id WHERE A.Activo = 1 ORDER BY G.Turno, G.Grado, G.Grupo, A.NombreCompleto ASC")->fetchAll();
$Asignaciones = $Pdo->query("SELECT Asn.Id, Asn.MateriaNombre, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id WHERE Asn.Activo = 1 AND U.Activo = 1 AND G.Activo = 1 ORDER BY U.NombreCompleto ASC")->fetchAll();

// Expedientes: no cargo todos los alumnos de golpe.
// Primero se selecciona un grupo y solo entonces se consulta ese padrón.
$ExpedienteGrupoId = intval($_GET['ExpGrupoId'] ?? 0);
$AlumnosExpedientes = [];
$GrupoExpedienteSeleccionado = null;

if ($ExpedienteGrupoId > 0) {
    $StmtGrupoExp = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Id = ? AND Activo = 1 LIMIT 1");
    $StmtGrupoExp->execute([$ExpedienteGrupoId]);
    $GrupoExpedienteSeleccionado = $StmtGrupoExp->fetch();

    if ($GrupoExpedienteSeleccionado) {
        $StmtExp = $Pdo->prepare("
            SELECT
                A.Id,
                A.NombreCompleto,
                A.GrupoId,
                G.Grado,
                G.Grupo,
                G.Turno
            FROM Alumnos A
            JOIN Grupos G ON A.GrupoId = G.Id
            WHERE A.Activo = 1
            AND G.Activo = 1
            AND A.GrupoId = ?
            ORDER BY A.NombreCompleto ASC
        ");
        $StmtExp->execute([$ExpedienteGrupoId]);
        $AlumnosExpedientes = $StmtExp->fetchAll();
    } else {
        $ExpedienteGrupoId = 0;
    }
}

$TotalAlumnosActivos = (int)$Pdo->query("SELECT COUNT(*) FROM Alumnos WHERE Activo = 1")->fetchColumn();
$TotalMaestrosActivos = (int)$Pdo->query("SELECT COUNT(*) FROM Usuarios WHERE Rol='maestro' AND Activo = 1")->fetchColumn();
$TotalGruposActivos = (int)$Pdo->query("SELECT COUNT(*) FROM Grupos WHERE Activo = 1")->fetchColumn();
$AsistenciasHoy = (int)$Pdo->query("SELECT COUNT(*) FROM Asistencias WHERE FechaDia = CURDATE()")->fetchColumn();
$FaltasHoy = (int)$Pdo->query("SELECT COUNT(*) FROM Asistencias WHERE FechaDia = CURDATE() AND Estado = 'F'")->fetchColumn();
$PromedioGeneral = $Pdo->query("SELECT ROUND(AVG(Calificacion), 1) FROM Calificaciones")->fetchColumn();
$PromedioGeneral = $PromedioGeneral !== null ? $PromedioGeneral : '0.0';
// En esta consulta calculo los alumnos con riesgo.
// Tomo faltas y retardos de los últimos 30 días, además del promedio menor a 7.
// También genero el MOTIVO para que el administrador sepa si el riesgo es por FALTAS, RETARDOS, PROMEDIO BAJO o una combinación.
$AlumnosRiesgo = $Pdo->query("
    SELECT
        Al.Id,
        Al.NombreCompleto,
        G.Grado,
        G.Grupo,
        G.Turno,
        COALESCE(AsisAgg.Faltas, 0) AS Faltas,
        COALESCE(AsisAgg.Retardos, 0) AS Retardos,
        CalAgg.Promedio,
        (
            COALESCE(AsisAgg.Faltas, 0) * 3
            + COALESCE(AsisAgg.Retardos, 0)
            + CASE
                WHEN CalAgg.Promedio IS NULL THEN 0
                WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)
                ELSE 0
              END
        ) AS PuntajeRiesgo,
        TRIM(BOTH ' + ' FROM CONCAT(
            CASE WHEN COALESCE(AsisAgg.Faltas, 0) > 0 THEN 'FALTAS + ' ELSE '' END,
            CASE WHEN COALESCE(AsisAgg.Retardos, 0) > 0 THEN 'RETARDOS + ' ELSE '' END,
            CASE WHEN CalAgg.Promedio IS NOT NULL AND CalAgg.Promedio < 7 THEN 'PROMEDIO BAJO + ' ELSE '' END
        )) AS MotivoRiesgo,
        CASE
            WHEN (
                COALESCE(AsisAgg.Faltas, 0) * 3
                + COALESCE(AsisAgg.Retardos, 0)
                + CASE
                    WHEN CalAgg.Promedio IS NULL THEN 0
                    WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)
                    ELSE 0
                  END
            ) >= 10 THEN 'ALTO'
            WHEN (
                COALESCE(AsisAgg.Faltas, 0) * 3
                + COALESCE(AsisAgg.Retardos, 0)
                + CASE
                    WHEN CalAgg.Promedio IS NULL THEN 0
                    WHEN CalAgg.Promedio < 7 THEN ROUND((7 - CalAgg.Promedio) * 2, 1)
                    ELSE 0
                  END
            ) >= 5 THEN 'MEDIO'
            ELSE 'BAJO'
        END AS NivelRiesgo
    FROM Alumnos Al
    JOIN Grupos G ON Al.GrupoId = G.Id
    LEFT JOIN (
        SELECT
            AlumnoId,
            SUM(CASE WHEN Estado='F' THEN 1 ELSE 0 END) AS Faltas,
            SUM(CASE WHEN Estado='R' THEN 1 ELSE 0 END) AS Retardos
        FROM Asistencias
        WHERE FechaDia >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY AlumnoId
    ) AsisAgg ON AsisAgg.AlumnoId = Al.Id
    LEFT JOIN (
        SELECT
            AlumnoId,
            ROUND(AVG(Calificacion), 1) AS Promedio
        FROM Calificaciones
        GROUP BY AlumnoId
    ) CalAgg ON CalAgg.AlumnoId = Al.Id
    WHERE Al.Activo = 1
    HAVING Faltas > 0 OR Retardos > 0 OR (Promedio IS NOT NULL AND Promedio < 7)
    ORDER BY PuntajeRiesgo DESC, Faltas DESC, Retardos DESC, Promedio ASC, Al.NombreCompleto ASC
    LIMIT 10
")->fetchAll();
// Cargo los últimos movimientos de la bitácora.
// Si la tabla todavía no existe porque se actualizó sobre una base vieja, la creo automáticamente desde Conexion.php.
$BitacoraReciente = [];
try {
    if (function_exists('CrearTablaBitacoraSiNoExiste')) {
        CrearTablaBitacoraSiNoExiste($Pdo);
    }

    $BitacoraReciente = $Pdo->query("
        SELECT
            B.*,
            U.NombreCompleto
        FROM BitacoraMovimientos B
        LEFT JOIN Usuarios U ON B.UsuarioId = U.Id
        ORDER BY B.FechaRegistro DESC
        LIMIT 100
    ")->fetchAll();
} catch (Exception $E) {
    $BitacoraReciente = [];
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

        /* MODALES DE EDICIÓN MÁS AMPLIOS: evito que los botones se encimen o se corten. */
        .modal-dialog.modal-sm{
            max-width:620px !important;
        }

        .ModalEditarPro{
            max-width:680px !important;
            width:calc(100% - 28px) !important;
        }

        @media (max-width:576px){
            .modal-dialog.modal-sm,
            .ModalEditarPro{
                max-width:calc(100% - 20px) !important;
                margin:10px auto !important;
            }
        }


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

        .ActionInfo{
            color:#0EA5E9;
            border-color:#0EA5E9;
            box-shadow:0 0 0 3px rgba(14,165,233,0.08);
        }

        .ActionInfo:hover{
            color:white;
            background:#0EA5E9;
        }

        .ActionWarning{
            color:#F59E0B;
            border-color:#F59E0B;
            box-shadow:0 0 0 3px rgba(245,158,11,0.10);
        }

        .ActionWarning:hover{
            color:#111827;
            background:#F59E0B;
        }



        .ActionSuccess{
            color:#16A34A;
            border-color:#16A34A;
            box-shadow:0 0 0 3px rgba(22,163,74,0.08);
        }

        .ActionSuccess:hover{
            color:white;
            background:#16A34A;
        }

        .ActionDanger{
            color:#DC2626;
            border-color:#DC2626;
            box-shadow:0 0 0 3px rgba(220,38,38,0.08);
        }

        .ActionDanger:hover{
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
            min-height:44px !important;
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

    

        /* ==========================================================
           AJUSTE FINAL INSTITUCIONAL EST 101
           Normalizo botones y menús al color guinda/tinto de la escuela.
           La idea es evitar demasiados colores y mantener una imagen formal.
        ========================================================== */
        :root{
            --Tinto101:#7A0818;
            --Tinto101Hover:#4F0610;
            --Tinto101Suave:rgba(122,8,24,.10);
            --Gris101:#6B7280;
            --Borde101:#E5E7EB;
        }

        .nav-tabs .nav-link,
        .ActionBtn,
        .BtnExport,
        .ExportIcon,
        .BotonAccion,
        .BtnBack,
        .BtnCancelEdit,
        .BtnSaveEdit,
        .BtnCancelDelete,
        .BtnConfirmDelete,
        .btn:not(.btn-close):not(.navbar-toggler){
            background:#FFFFFF !important;
            border:2px solid var(--Tinto101) !important;
            color:var(--Tinto101) !important;
            box-shadow:0 6px 16px rgba(122,8,24,.08) !important;
        }

        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link.active,
        .ActionBtn:hover,
        .BtnExport:hover,
        .ExportIcon:hover,
        .BotonAccion:hover,
        .BtnBack:hover,
        .BtnCancelEdit:hover,
        .BtnSaveEdit:hover,
        .BtnCancelDelete:hover,
        .BtnConfirmDelete:hover,
        .btn:not(.btn-close):not(.navbar-toggler):hover{
            background:linear-gradient(135deg,var(--Tinto101),var(--Tinto101Hover)) !important;
            border-color:var(--Tinto101Hover) !important;
            color:#FFFFFF !important;
            transform:translateY(-2px) !important;
            box-shadow:0 12px 26px rgba(122,8,24,.20) !important;
        }

        .nav-tabs .nav-link:hover i,
        .nav-tabs .nav-link.active i,
        .ActionBtn:hover i,
        .BtnExport:hover i,
        .ExportIcon:hover i,
        .BotonAccion:hover i,
        .btn:not(.btn-close):not(.navbar-toggler):hover i{
            color:#FFFFFF !important;
        }

        .ActionDelete,
        .BtnConfirmDelete{
            border-color:var(--Tinto101Hover) !important;
            color:var(--Tinto101Hover) !important;
        }

        .ActionDelete:hover,
        .BtnConfirmDelete:hover{
            background:linear-gradient(135deg,var(--Tinto101Hover),#2E0309) !important;
            border-color:#2E0309 !important;
            color:#FFFFFF !important;
        }

        .ModuleButtonBlankFix,
        .ActionBtn span,
        .ExportIcon span,
        .BtnExport span{
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

        .GrupoTurnoMatutino,
        .GrupoTurnoVespertino,
        .BadgeTurno{
            background:#FFFFFF !important;
            color:var(--Tinto101) !important;
            border:2px solid var(--Tinto101) !important;
        }

        .card-custom,
        .card,
        .MainCard{
            border:1px solid rgba(122,8,24,.06) !important;
        }

        #bitacora .card-custom{
            margin-top:0 !important;
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
           CORRECCIÓN FINAL DE BOTONES RELLENOS
           ----------------------------------------------------------
           Dejo todos los botones importantes con color de relleno desde
           el inicio. Ningún botón de guardar, navegación, acciones o
           exportación queda blanco. En hover solo se oscurece para que
           se note el paso del mouse sin romper el diseño institucional.
           ========================================================== */
        :root{
            --FinalTinto:#7A0818;
            --FinalTinto2:#A10D26;
            --FinalTintoHover:#4A0410;
            --FinalAzul:#1D4ED8;
            --FinalAzulHover:#123A9C;
            --FinalRojo:#B91C1C;
            --FinalRojoHover:#7F1D1D;
            --FinalVerde:#15803D;
            --FinalVerdeHover:#0F5F2A;
            --FinalNaranja:#C2410C;
            --FinalNaranjaHover:#8F2E08;
            --FinalMorado:#5B21B6;
            --FinalMoradoHover:#3B0F82;
            --FinalGris:#475569;
            --FinalGrisHover:#334155;
        }

        .nav-tabs .nav-link,
        .ModulosRecomendados .ActionBtn,
        .btn-guinda,
        .btn-primary,
        .btn-success,
        .btn-warning,
        .btn-dark,
        .btn-info,
        .btn-secondary,
        .btn-outline-success,
        .btn-outline-primary,
        .btn-outline-danger,
        .btn-outline-secondary,
        .btn-outline-light,
        button.btn:not(.btn-close):not(.navbar-toggler),
        a.btn:not(.btn-close):not(.navbar-toggler),
        .BtnGuardar,
        .BtnSaveEdit,
        .BtnConfirmDelete,
        .BtnLogin,
        .BotonAccion,
        .ActionBtn:not(.ActionEdit):not(.ActionDelete):not(.ActionInfo):not(.ActionSuccess):not(.ActionDanger):not(.ActionWarning),
        .BtnBack{
            background:linear-gradient(135deg,var(--FinalTinto),var(--FinalTinto2)) !important;
            border:2px solid var(--FinalTinto) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(122,8,24,.18) !important;
            text-decoration:none !important;
        }

        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link.active,
        .ModulosRecomendados .ActionBtn:hover,
        .btn-guinda:hover,
        .btn-primary:hover,
        .btn-success:hover,
        .btn-warning:hover,
        .btn-dark:hover,
        .btn-info:hover,
        .btn-secondary:hover,
        .btn-outline-success:hover,
        .btn-outline-primary:hover,
        .btn-outline-danger:hover,
        .btn-outline-secondary:hover,
        .btn-outline-light:hover,
        button.btn:not(.btn-close):not(.navbar-toggler):hover,
        a.btn:not(.btn-close):not(.navbar-toggler):hover,
        .BtnGuardar:hover,
        .BtnSaveEdit:hover,
        .BtnConfirmDelete:hover,
        .BtnLogin:hover,
        .BotonAccion:hover,
        .ActionBtn:not(.ActionEdit):not(.ActionDelete):not(.ActionInfo):not(.ActionSuccess):not(.ActionDanger):not(.ActionWarning):hover,
        .BtnBack:hover{
            background:linear-gradient(135deg,var(--FinalTintoHover),var(--FinalTinto)) !important;
            border-color:var(--FinalTintoHover) !important;
            color:#FFFFFF !important;
            transform:translateY(-2px) !important;
            box-shadow:0 14px 28px rgba(122,8,24,.30) !important;
        }

        .ActionBtn.ActionEdit,
        .ActionInfo{
            background:linear-gradient(135deg,var(--FinalAzul),#2563EB) !important;
            border-color:var(--FinalAzul) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(29,78,216,.20) !important;
        }

        .ActionBtn.ActionEdit:hover,
        .ActionInfo:hover{
            background:linear-gradient(135deg,var(--FinalAzulHover),var(--FinalAzul)) !important;
            border-color:var(--FinalAzulHover) !important;
            color:#FFFFFF !important;
        }

        .ActionBtn.ActionDelete,
        .ActionDanger{
            background:linear-gradient(135deg,var(--FinalRojo),#DC2626) !important;
            border-color:var(--FinalRojo) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(185,28,28,.20) !important;
        }

        .ActionBtn.ActionDelete:hover,
        .ActionDanger:hover{
            background:linear-gradient(135deg,var(--FinalRojoHover),var(--FinalRojo)) !important;
            border-color:var(--FinalRojoHover) !important;
            color:#FFFFFF !important;
        }

        .ActionSuccess{
            background:linear-gradient(135deg,var(--FinalVerde),#16A34A) !important;
            border-color:var(--FinalVerde) !important;
            color:#FFFFFF !important;
        }

        .ActionSuccess:hover{
            background:linear-gradient(135deg,var(--FinalVerdeHover),var(--FinalVerde)) !important;
            border-color:var(--FinalVerdeHover) !important;
            color:#FFFFFF !important;
        }

        .ActionWarning{
            background:linear-gradient(135deg,var(--FinalNaranja),#EA580C) !important;
            border-color:var(--FinalNaranja) !important;
            color:#FFFFFF !important;
        }

        .ActionWarning:hover{
            background:linear-gradient(135deg,var(--FinalNaranjaHover),var(--FinalNaranja)) !important;
            border-color:var(--FinalNaranjaHover) !important;
            color:#FFFFFF !important;
        }

        .ExportIcon,
        .ExportIcon *,
        .BtnExport,
        .BtnExport *{
            color:#FFFFFF !important;
        }

        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas),
        .BtnExport.ExportCalifExcel{
            background:linear-gradient(135deg,var(--FinalVerde),#16A34A) !important;
            border-color:var(--FinalVerde) !important;
        }

        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas):hover,
        .BtnExport.ExportCalifExcel:hover{
            background:linear-gradient(135deg,var(--FinalVerdeHover),var(--FinalVerde)) !important;
            border-color:var(--FinalVerdeHover) !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas),
        .BtnExport.ExportCalifPdf{
            background:linear-gradient(135deg,var(--FinalRojo),#DC2626) !important;
            border-color:var(--FinalRojo) !important;
        }

        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas):hover,
        .BtnExport.ExportCalifPdf:hover{
            background:linear-gradient(135deg,var(--FinalRojoHover),var(--FinalRojo)) !important;
            border-color:var(--FinalRojoHover) !important;
        }

        .ExportIcon.ExportHoy,
        .BtnExport.ExportAsisExcel{
            background:linear-gradient(135deg,var(--FinalNaranja),#EA580C) !important;
            border-color:var(--FinalNaranja) !important;
        }

        .ExportIcon.ExportHoy:hover,
        .BtnExport.ExportAsisExcel:hover{
            background:linear-gradient(135deg,var(--FinalNaranjaHover),var(--FinalNaranja)) !important;
            border-color:var(--FinalNaranjaHover) !important;
        }

        .ExportIcon.ExportTodas,
        .BtnExport.ExportAsisPdf{
            background:linear-gradient(135deg,var(--FinalMorado),#7C3AED) !important;
            border-color:var(--FinalMorado) !important;
        }

        .ExportIcon.ExportTodas:hover,
        .BtnExport.ExportAsisPdf:hover{
            background:linear-gradient(135deg,var(--FinalMoradoHover),var(--FinalMorado)) !important;
            border-color:var(--FinalMoradoHover) !important;
        }

        .ActionBtn i,
        .ActionBtn span,
        .ExportIcon i,
        .ExportIcon span,
        .BtnExport i,
        .BtnExport span,
        .btn i,
        .btn span,
        .BotonAccion i,
        .BotonAccion span{
            color:#FFFFFF !important;
        }

        .GrupoTextoSimple,
        .GrupoTextoSimple i{
            background:transparent !important;
            border:0 !important;
            box-shadow:none !important;
            color:#111827 !important;
            padding:0 !important;
        }

    

        /* ==========================================================
           AJUSTE FINAL DE DISEÑO - BOTONES RELLENOS Y CONSISTENTES
           Mantengo el color institucional tinto/guinda como base.
           El único botón blanco permitido en Admin es Cerrar Sesión.
        ========================================================== */
        :root{
            --FinalTinto:#7A0818;
            --FinalTinto2:#A10D26;
            --FinalTintoHover:#4F050F;
            --FinalAzul:#1E40AF;
            --FinalAzul2:#2563EB;
            --FinalAzulHover:#172554;
            --FinalRojo:#991B1B;
            --FinalRojo2:#DC2626;
            --FinalRojoHover:#7F1D1D;
            --FinalVerde:#166534;
            --FinalVerde2:#16A34A;
            --FinalNaranja:#9A3412;
            --FinalNaranja2:#EA580C;
            --FinalMorado:#4C1D95;
            --FinalMorado2:#7C3AED;
        }

        .nav-tabs .nav-link,
        .ModuleButton,
        .MenuButton,
        .btn-guinda,
        .btn-primary:not(.ActionBtn):not(.ActionEdit),
        .btn-success:not(.ActionBtn),
        .btn-warning:not(.ActionBtn),
        .btn-dark:not(.ActionBtn),
        .btn-outline-success:not(.ActionBtn):not(.BtnExport),
        .btn-outline-secondary:not(.ActionBtn),
        .card-custom button[type="submit"]:not(.ActionDelete),
        .card button[type="submit"]:not(.ActionDelete),
        .modal button[type="submit"]:not(.ActionDelete),
        .ModulosRecomendados .ActionBtn{
            background:linear-gradient(135deg,var(--FinalTinto),var(--FinalTinto2)) !important;
            border:2px solid var(--FinalTinto) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(122,8,24,.20) !important;
        }

        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link.active,
        .ModuleButton:hover,
        .MenuButton:hover,
        .btn-guinda:hover,
        .btn-primary:not(.ActionBtn):not(.ActionEdit):hover,
        .btn-success:not(.ActionBtn):hover,
        .btn-warning:not(.ActionBtn):hover,
        .btn-dark:not(.ActionBtn):hover,
        .btn-outline-success:not(.ActionBtn):not(.BtnExport):hover,
        .btn-outline-secondary:not(.ActionBtn):hover,
        .card-custom button[type="submit"]:not(.ActionDelete):hover,
        .card button[type="submit"]:not(.ActionDelete):hover,
        .modal button[type="submit"]:not(.ActionDelete):hover,
        .ModulosRecomendados .ActionBtn:hover{
            background:linear-gradient(135deg,var(--FinalTintoHover),var(--FinalTinto)) !important;
            border-color:var(--FinalTintoHover) !important;
            color:#FFFFFF !important;
            box-shadow:0 14px 30px rgba(122,8,24,.30) !important;
        }

        .ActionBtn,
        .BtnExport,
        .ExportIcon{
            color:#FFFFFF !important;
            border-width:2px !important;
            font-weight:800 !important;
        }

        .ActionBtn i,
        .ActionBtn span,
        .BtnExport i,
        .BtnExport span,
        .ExportIcon i{
            color:#FFFFFF !important;
        }

        /* Expediente y acciones informativas: tinto institucional. */
        .ActionBtn.ActionInfo{
            background:linear-gradient(135deg,var(--FinalTinto),var(--FinalTinto2)) !important;
            border-color:var(--FinalTinto) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(122,8,24,.22) !important;
        }

        .ActionBtn.ActionInfo:hover{
            background:linear-gradient(135deg,var(--FinalTintoHover),var(--FinalTinto)) !important;
            border-color:var(--FinalTintoHover) !important;
            color:#FFFFFF !important;
        }

        /* Editar: azul más fuerte para que no se vea bajo. */
        .ActionBtn.ActionEdit,
        .btn-outline-primary.ActionBtn,
        .btn-outline-primary{
            background:linear-gradient(135deg,var(--FinalAzul),var(--FinalAzul2)) !important;
            border-color:var(--FinalAzul) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(30,64,175,.25) !important;
        }

        .ActionBtn.ActionEdit:hover,
        .btn-outline-primary.ActionBtn:hover,
        .btn-outline-primary:hover{
            background:linear-gradient(135deg,var(--FinalAzulHover),var(--FinalAzul)) !important;
            border-color:var(--FinalAzulHover) !important;
            color:#FFFFFF !important;
        }

        /* Eliminar: rojo más fuerte para acciones destructivas. */
        .ActionBtn.ActionDelete,
        .btn-outline-danger.ActionBtn,
        .btn-outline-danger{
            background:linear-gradient(135deg,var(--FinalRojo),var(--FinalRojo2)) !important;
            border-color:var(--FinalRojo) !important;
            color:#FFFFFF !important;
            box-shadow:0 10px 22px rgba(153,27,27,.25) !important;
        }

        .ActionBtn.ActionDelete:hover,
        .btn-outline-danger.ActionBtn:hover,
        .btn-outline-danger:hover{
            background:linear-gradient(135deg,var(--FinalRojoHover),var(--FinalRojo)) !important;
            border-color:var(--FinalRojoHover) !important;
            color:#FFFFFF !important;
        }

        /* Exportaciones rellenas y con letras blancas. */
        .BtnExport.ExportCalifExcel,
        .ExportIcon.ExportExcel:not(.ExportHoy):not(.ExportTodas){
            background:linear-gradient(135deg,var(--FinalVerde),var(--FinalVerde2)) !important;
            border-color:var(--FinalVerde) !important;
            color:#FFFFFF !important;
        }

        .BtnExport.ExportCalifPdf,
        .ExportIcon.ExportPdf:not(.ExportHoy):not(.ExportTodas){
            background:linear-gradient(135deg,var(--FinalRojo),var(--FinalRojo2)) !important;
            border-color:var(--FinalRojo) !important;
            color:#FFFFFF !important;
        }

        .BtnExport.ExportAsisExcel,
        .ExportIcon.ExportHoy{
            background:linear-gradient(135deg,var(--FinalNaranja),var(--FinalNaranja2)) !important;
            border-color:var(--FinalNaranja) !important;
            color:#FFFFFF !important;
        }

        .BtnExport.ExportAsisPdf,
        .ExportIcon.ExportTodas{
            background:linear-gradient(135deg,var(--FinalMorado),var(--FinalMorado2)) !important;
            border-color:var(--FinalMorado) !important;
            color:#FFFFFF !important;
        }

        .BtnExport:hover,
        .ExportIcon:hover{
            filter:brightness(.88) !important;
            color:#FFFFFF !important;
        }

        /* Cerrar sesión en Admin: es el único botón blanco por decisión visual. */
        .navbar-custom a[href="Logout.php"],
        .navbar-custom .btn-outline-light[href="Logout.php"]{
            background:#FFFFFF !important;
            border:2px solid rgba(255,255,255,.85) !important;
            color:var(--FinalTinto) !important;
            box-shadow:0 10px 24px rgba(0,0,0,.12) !important;
        }

        .navbar-custom a[href="Logout.php"] i,
        .navbar-custom .btn-outline-light[href="Logout.php"] i{
            color:var(--FinalTinto) !important;
        }

        .navbar-custom a[href="Logout.php"]:hover,
        .navbar-custom .btn-outline-light[href="Logout.php"]:hover{
            background:#F8FAFC !important;
            color:var(--FinalTintoHover) !important;
            border-color:#FFFFFF !important;
        }



        /* ==========================================================
           CORRECCIÓN FINAL ADMIN
           ----------------------------------------------------------
           Mantengo CERRAR SESIÓN como el único botón blanco del panel.
           También dejo los accesos de expediente y bitácora apuntando a
           secciones independientes para evitar confusiones.
        ========================================================== */
        .navbar-custom a[href="Logout.php"],
        .navbar-custom .btn-outline-light[href="Logout.php"]{
            background:#FFFFFF !important;
            color:var(--FinalTinto,#7A0818) !important;
            border:2px solid #FFFFFF !important;
            box-shadow:0 10px 24px rgba(0,0,0,.16) !important;
        }

        .navbar-custom a[href="Logout.php"] i,
        .navbar-custom .btn-outline-light[href="Logout.php"] i{
            color:var(--FinalTinto,#7A0818) !important;
        }

        .navbar-custom a[href="Logout.php"]:hover,
        .navbar-custom .btn-outline-light[href="Logout.php"]:hover{
            background:#F8FAFC !important;
            color:var(--FinalTintoHover,#4F050F) !important;
            border-color:#FFFFFF !important;
            filter:none !important;
        }


        /* ==========================================================
           CORRECCIÓN DEFINITIVA: BOTÓN CERRAR SESIÓN BLANCO
           ----------------------------------------------------------
           Este botón queda forzado en blanco porque es el único botón
           que debe contrastar contra la barra guinda superior.
        ========================================================== */
        .navbar-custom .BotonCerrarSesionBlanco,
        .navbar-custom .BotonCerrarSesionBlanco:visited,
        .navbar-custom .BotonCerrarSesionBlanco:focus{
            background:#FFFFFF !important;
            background-image:none !important;
            color:#7A0818 !important;
            border:2px solid #FFFFFF !important;
            box-shadow:0 10px 24px rgba(0,0,0,.18) !important;
            opacity:1 !important;
            filter:none !important;
        }

        .navbar-custom .BotonCerrarSesionBlanco i,
        .navbar-custom .BotonCerrarSesionBlanco span{
            color:#7A0818 !important;
        }

        .navbar-custom .BotonCerrarSesionBlanco:hover{
            background:#F8FAFC !important;
            background-image:none !important;
            color:#4F050F !important;
            border-color:#FFFFFF !important;
            box-shadow:0 14px 30px rgba(0,0,0,.22) !important;
            transform:translateY(-2px) !important;
        }

        .navbar-custom .BotonCerrarSesionBlanco:hover i,
        .navbar-custom .BotonCerrarSesionBlanco:hover span{
            color:#4F050F !important;
        }


        /* ==========================================================
           BLINDAJE FINAL DEL BOTÓN CERRAR SESIÓN
           ----------------------------------------------------------
           El problema venía de reglas globales para .btn con !important.
           Para evitar conflictos, este botón ya no usa la clase .btn y
           aquí se fuerza con un ID único.
        ========================================================== */
        #BtnCerrarSesionAdmin,
        .navbar-custom #BtnCerrarSesionAdmin,
        body .navbar-custom #BtnCerrarSesionAdmin,
        html body .navbar-custom #BtnCerrarSesionAdmin{
            background:#FFFFFF !important;
            background-image:none !important;
            color:#7A0818 !important;
            border:2px solid #FFFFFF !important;
            border-radius:999px !important;
            min-height:42px !important;
            padding:8px 18px !important;
            display:inline-flex !important;
            align-items:center !important;
            justify-content:center !important;
            gap:8px !important;
            font-weight:900 !important;
            text-decoration:none !important;
            box-shadow:0 10px 24px rgba(0,0,0,.18) !important;
            opacity:1 !important;
            filter:none !important;
        }

        #BtnCerrarSesionAdmin i,
        #BtnCerrarSesionAdmin span{
            color:#7A0818 !important;
            opacity:1 !important;
            filter:none !important;
        }

        #BtnCerrarSesionAdmin:hover,
        .navbar-custom #BtnCerrarSesionAdmin:hover,
        body .navbar-custom #BtnCerrarSesionAdmin:hover{
            background:#F8FAFC !important;
            background-image:none !important;
            color:#4F050F !important;
            border-color:#FFFFFF !important;
            box-shadow:0 14px 30px rgba(0,0,0,.22) !important;
            transform:translateY(-2px) !important;
            filter:none !important;
        }

        #BtnCerrarSesionAdmin:hover i,
        #BtnCerrarSesionAdmin:hover span{
            color:#4F050F !important;
        }



        /* ==========================================================
           CORRECCIÓN POSICIÓN MODAL ELIMINAR
           ----------------------------------------------------------
           La confirmación de eliminar ya no depende del centrado vertical
           automático de Bootstrap. Esto evita que en pestañas largas como
           ALUMNOS parezca más abajo que en las demás secciones.
           ========================================================== */
        #ModalConfirmarEliminar{
            padding-top:clamp(72px, 8vh, 110px) !important;
        }

        #ModalConfirmarEliminar.show{
            display:block !important;
        }

        #ModalConfirmarEliminar .ModalEliminarFijo{
            width:calc(100% - 28px) !important;
            max-width:520px !important;
            margin:0 auto 28px auto !important;
            min-height:0 !important;
            transform:none !important;
        }

        #ModalConfirmarEliminar .DeleteModalContent{
            margin:0 auto !important;
        }

        @media(max-width:576px){
            #ModalConfirmarEliminar{
                padding-top:24px !important;
            }

            #ModalConfirmarEliminar .ModalEliminarFijo{
                width:calc(100% - 18px) !important;
                max-width:calc(100% - 18px) !important;
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
    min-height:44px !important;
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
    min-height:44px !important;
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
    height:44px !important;
    min-height:44px !important;
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



<style id="SgceModalCleanFix20">
/* ==========================================================
   SGCE FIX20 - MODALES SIN PARPADEO
   Causa encontrada: Admin.php tenía varios fixes acumulados que
   centraban la modal por CSS y por JS con setTimeout. Bootstrap la
   abría en una posición y luego esos scripts la movían, causando
   el parpadeo. Este bloque deja un solo comportamiento estable.
   ========================================================== */
html body.modal-open{
    overflow:hidden !important;
    padding-right:0 !important;
}
html body .modal{
    position:fixed !important;
    inset:0 !important;
    width:100vw !important;
    height:100vh !important;
    z-index:1060 !important;
    overflow:hidden !important;
    padding:16px !important;
}
html body .modal.show{
    display:block !important;
}
html body .modal-dialog{
    margin:0 auto !important;
    pointer-events:none !important;
    max-width:680px !important;
}
html body .modal-dialog-centered{
    display:flex !important;
    align-items:center !important;
    min-height:calc(100vh - 32px) !important;
}
html body .modal-dialog.modal-sm{
    max-width:520px !important;
}
html body .modal-dialog.modal-lg,
html body .ModalEditarPro{
    max-width:860px !important;
}
html body .modal-content,
html body .EditModalContent,
html body .DeleteModalContent{
    width:100% !important;
    max-height:calc(100vh - 32px) !important;
    overflow:hidden !important;
    pointer-events:auto !important;
    border:0 !important;
    border-radius:28px !important;
    box-shadow:0 32px 95px rgba(15,23,42,.42) !important;
    background:#FFFFFF !important;
}
html body .modal-body,
html body .EditModalBody,
html body .DeleteModalBody{
    overflow-y:auto !important;
    max-height:calc(100vh - 230px) !important;
    background:#FFFFFF !important;
}
html body .modal-backdrop.show{
    opacity:.58 !important;
}
/* Quito únicamente el deslizamiento de Bootstrap del cuadro para que no brinque. */
html body .modal.fade .modal-dialog{
    transition:none !important;
    transform:none !important;
}
html body .modal.show .modal-dialog{
    transform:none !important;
}
html body.modal-open .card-custom,
html body.modal-open .TabPane,
html body.modal-open .tab-pane,
html body.modal-open .TableCard,
html body.modal-open .ContentCard,
html body.modal-open .TablaContenedor,
html body.modal-open .table-responsive{
    transform:none !important;
    filter:none !important;
}
@media(max-width:576px){
    html body .modal{padding:10px !important;}
    html body .modal-dialog,
    html body .modal-dialog.modal-sm,
    html body .modal-dialog.modal-lg,
    html body .ModalEditarPro{
        width:calc(100vw - 20px) !important;
        max-width:calc(100vw - 20px) !important;
    }
    html body .modal-dialog-centered{
        min-height:calc(100vh - 20px) !important;
    }
    html body .modal-body,
    html body .EditModalBody,
    html body .DeleteModalBody{
        max-height:calc(100vh - 210px) !important;
        padding:20px !important;
    }
}
</style>
<script id="SgceModalCleanFix20Js">
(function(){
    function PrepararModales(){
        document.querySelectorAll('.modal').forEach(function(Modal){
            if(Modal.parentElement !== document.body){
                document.body.appendChild(Modal);
            }
            var Dialog = Modal.querySelector('.modal-dialog');
            if(Dialog && !Dialog.classList.contains('modal-dialog-centered')){
                Dialog.classList.add('modal-dialog-centered');
            }
        });
    }
    document.addEventListener('DOMContentLoaded', PrepararModales);
})();
</script>

</head>
<body>

<nav class="navbar navbar-dark navbar-custom py-2">
    <div class="container-fluid px-4">
        <span class="navbar-brand"><i class="fa-solid fa-sliders text-light"></i> SGCE | <span class="fw-light fs-6">Administrador</span></span>
        <a href="Logout.php"
           id="BtnCerrarSesionAdmin"
           class="SgceTopAction BtnLogout">
            <i class="fa-solid fa-power-off"></i>
            <span>Cerrar Sesión</span>
        </a>
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
            <button class="nav-link <?= $TabActual==='inicio'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#inicio">
                <i class="fa-solid fa-chart-line"></i> Inicio
            </button>
        </li>
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
            <button class="nav-link <?= $TabActual==='expedientes'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#expedientes">
                <i class="fa-solid fa-folder-open"></i> Expedientes
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link <?= $TabActual==='asignaciones'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#asignaciones">
                <i class="fa-solid fa-book-open"></i> Asignaciones
            </button>
        </li>
    
        <li class="nav-item">
            <button class="nav-link <?= $TabActual==='bitacora'?'active':'' ?>" data-bs-toggle="tab" data-bs-target="#bitacora">
                <i class="fa-solid fa-shield-halved"></i> Bitácora
            </button>
        </li>
    </ul>

    <div class="tab-content">



        <!-- ===================== INICIO / DASHBOARD ===================== -->
        <div class="tab-pane fade <?= $TabActual==='inicio'?'show active':'' ?>" id="inicio">
            <div class="row g-4 mb-4">
                <?php
                    $TarjetasInicio = [
                        ['ALUMNOS ACTIVOS', $TotalAlumnosActivos, 'fa-children', 'var(--SgceAzul)'],
                        ['MAESTROS ACTIVOS', $TotalMaestrosActivos, 'fa-chalkboard-user', 'var(--SgceVerde)'],
                        ['GRUPOS ACTIVOS', $TotalGruposActivos, 'fa-users-rectangle', 'var(--SgceAmarillo)'],
                        ['ASISTENCIAS HOY', $AsistenciasHoy, 'fa-calendar-check', 'var(--SgceGuinda)'],
                        ['FALTAS HOY', $FaltasHoy, 'fa-circle-xmark', 'var(--SgceRojo)'],
                        ['PROMEDIO GENERAL', $PromedioGeneral, 'fa-star', '#7C3AED']
                    ];
                ?>
                <?php foreach($TarjetasInicio as $T): ?>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card card-custom p-3 h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="StatsIcon" style="background:<?= $T[3] ?>22;color:<?= $T[3] ?>;">
                                <i class="fa-solid <?= $T[2] ?>"></i>
                            </div>
                            <div>
                                <div class="small text-muted fw-bold"><?= $T[0] ?></div>
                                <div class="fs-3 fw-black" style="font-weight:900;"><?= htmlspecialchars((string)$T[1]) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card card-custom p-4 h-100">
                        <h5 class="fw-bold text-danger mb-3"><i class="fa-solid fa-triangle-exclamation me-2"></i> ALUMNOS CON MAYOR RIESGO ACADÉMICO Y DE ASISTENCIA</h5>
                        <p class="text-muted fw-semibold small mb-3">
                            El riesgo se calcula con faltas y retardos de los últimos 30 días, más promedio menor a 7 cuando ya existen calificaciones. En la columna <strong>MOTIVO</strong> se explica si el riesgo viene por faltas, retardos, promedio bajo o una combinación de ellos.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-center">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <th>Prom.</th>
                                        <th>Faltas</th>
                                        <th>Retardos</th>
                                        <th>Nivel</th>
                                        <th>Motivo</th>
                                        <th>Puntos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($AlumnosRiesgo as $R): ?>
                                    <?php
                                        $ClaseNivelRiesgo = 'bg-warning text-dark';
                                        if ($R['NivelRiesgo'] === 'ALTO') { $ClaseNivelRiesgo = 'bg-danger'; }
                                        if ($R['NivelRiesgo'] === 'MEDIO') { $ClaseNivelRiesgo = 'bg-warning text-dark'; }
                                        if ($R['NivelRiesgo'] === 'BAJO') { $ClaseNivelRiesgo = 'bg-info text-dark'; }
                                    ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($R['NombreCompleto']) ?></td>
                                        <td><?= htmlspecialchars($R['Grado'].' '.$R['Grupo']) ?></td>
                                        <td><span class="badge <?= $R['Turno']==='MATUTINO'?'bg-primary':'bg-warning text-dark' ?>"><?= htmlspecialchars($R['Turno']) ?></span></td>
                                        <td><span class="badge <?= ($R['Promedio'] !== null && (float)$R['Promedio'] < 7) ? 'bg-danger' : 'bg-success' ?>"><?= $R['Promedio'] !== null ? htmlspecialchars($R['Promedio']) : 'S/C' ?></span></td>
                                        <td><span class="badge bg-danger"><?= (int)$R['Faltas'] ?></span></td>
                                        <td><span class="badge bg-warning text-dark"><?= (int)$R['Retardos'] ?></span></td>
                                        <td><span class="badge <?= $ClaseNivelRiesgo ?>"><?= htmlspecialchars($R['NivelRiesgo']) ?></span></td>
                                        <td><span class="badge bg-dark"><?= htmlspecialchars($R['MotivoRiesgo'] !== '' ? $R['MotivoRiesgo'] : 'SIN MOTIVO') ?></span></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($R['PuntajeRiesgo']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card card-custom p-4 h-100">
                        <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-lightbulb me-2"></i> MÓDULOS RECOMENDADOS</h5>
                        <div class="d-grid gap-3 ModulosRecomendados">
                            <a href="AvisosAdmin.php" class="ActionBtn ActionEdit w-100"><i class="fa-solid fa-bullhorn"></i> AVISOS Y COMUNICADOS</a>
                            <a href="ConsultaPadre.php" class="ActionBtn ActionInfo w-100"><i class="fa-solid fa-user-shield"></i> CONSULTA PÚBLICA DE PADRES</a>
                            <a href="ReportesAdmin.php" class="ActionBtn ActionInfo w-100"><i class="fa-solid fa-filter"></i> CENTRO DE REPORTES</a>
                            <a href="RestaurarBD.php" class="ActionBtn ActionWarning w-100"><i class="fa-solid fa-database"></i> RESPALDOS E IMPORTACIÓN</a>
                            <a href="Admin.php?Tab=expedientes" class="ActionBtn ActionInfo w-100"><i class="fa-solid fa-folder-open"></i> EXPEDIENTES DE ALUMNOS</a>
                            <a href="Admin.php?Tab=bitacora" class="ActionBtn ActionWarning w-100"><i class="fa-solid fa-clock-rotate-left"></i> VER BITÁCORA</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                    <?php echo CampoCsrf(); ?>
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
                    <?php echo CampoCsrf(); ?>
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
                    <?php echo CampoCsrf(); ?>
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
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">

                        <form method="POST">
                    <?php echo CampoCsrf(); ?>
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
                    <?php echo CampoCsrf(); ?>
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
                    <?php echo CampoCsrf(); ?>
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
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content">

                            <form method="POST">
                    <?php echo CampoCsrf(); ?>
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

        <!-- ===================== EXPEDIENTES ===================== -->

        <div class="tab-pane fade <?= $TabActual==='expedientes'?'show active':'' ?>" id="expedientes">
            <div class="card card-custom shadow-sm border-0">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div>
                            <h4 class="mb-1 fw-bold text-danger">
                                <i class="fa-solid fa-folder-open me-2"></i>
                                Expedientes De Alumnos
                            </h4>
                            <p class="text-muted mb-0 fw-semibold small">
                                Para evitar cargar alumnos de golpe, primero selecciona grado, grupo y turno. Después se muestra solo ese padrón.
                            </p>
                        </div>
                    </div>

                    <form method="GET" action="Admin.php" class="mb-4">
                        <input type="hidden" name="Tab" value="expedientes">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-7 col-md-8">
                                <label class="form-label fw-bold text-muted small">Grado / Grupo / Turno</label>
                                <select name="ExpGrupoId" class="form-select" required>
                                    <option value="">SELECCIONA GRUPO...</option>
                                    <?php foreach($Grupos as $GExp): ?>
                                        <option value="<?= (int)$GExp['Id'] ?>" <?= ((int)$ExpedienteGrupoId === (int)$GExp['Id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($GExp['Grado'].' '.$GExp['Grupo'].' - '.$GExp['Turno'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <button type="submit" class="ActionBtn ActionPrimary w-100">
                                    <i class="fa-solid fa-filter"></i><span>Cargar Expedientes</span>
                                </button>
                            </div>
                            <?php if($ExpedienteGrupoId > 0): ?>
                            <div class="col-lg-2 col-md-12">
                                <a href="Admin.php?Tab=expedientes" class="ActionBtn ActionDanger w-100">
                                    <i class="fa-solid fa-eraser"></i><span>Limpiar</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if($ExpedienteGrupoId <= 0): ?>
                        <div class="alert alert-info border-0 shadow-sm fw-semibold mb-0">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            Selecciona un grupo para cargar expedientes. Así el sistema no consulta todos los alumnos innecesariamente.
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                            <div class="fw-bold text-danger">
                                <i class="fa-solid fa-users me-2"></i>
                                Grupo seleccionado:
                                <?= $GrupoExpedienteSeleccionado
                                    ? htmlspecialchars($GrupoExpedienteSeleccionado['Grado'].' '.$GrupoExpedienteSeleccionado['Grupo'].' - '.$GrupoExpedienteSeleccionado['Turno'], ENT_QUOTES, 'UTF-8')
                                    : 'NO DISPONIBLE' ?>
                            </div>

                            <div class="input-group search-container" style="max-width: 360px;">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="SearchExpedientes" class="form-control border-start-0" placeholder="Buscar expediente...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="TableExpedientes">
                                <thead class="table-light">
                                    <tr>
                                        <th>Alumno</th>
                                        <th>Grupo</th>
                                        <th>Turno</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($AlumnosExpedientes as $Al): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="searchable">
                                            <?= htmlspecialchars($Al['Grado'].' '.$Al['Grupo'], ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td class="searchable">
                                            <span class="badge bg-dark"><?= htmlspecialchars($Al['Turno'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td>
                                            <a class="ActionBtn ActionInfo" href="HistorialAlumno.php?AlumnoId=<?= $Al['Id'] ?>" target="_blank">
                                                <i class="fa-solid fa-folder-open"></i><span>Abrir Expediente</span>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if(count($AlumnosExpedientes) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted fw-bold py-4">NO HAY ALUMNOS ACTIVOS EN ESTE GRUPO.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="PagerExpedientes" class="d-flex justify-content-center mt-3"></div>
                    <?php endif; ?>
                </div>
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
                    <?php echo CampoCsrf(); ?>

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
                    <?php echo CampoCsrf(); ?>
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
<a class="ActionBtn ActionInfo" href="HistorialAlumno.php?AlumnoId=<?= $Al['Id'] ?>" target="_blank">
                                                    <i class="fa-solid fa-folder-open"></i><span>Expediente</span>
                                                </a>

                                                <button class="ActionBtn ActionEdit" data-bs-toggle="modal" data-bs-target="#EAl<?= $Al['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="ALUMNO" data-confirm-message="¿DESEAS DAR DE BAJA A ESTE ALUMNO? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                    <?php echo CampoCsrf(); ?>
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
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <form method="POST">
                    <?php echo CampoCsrf(); ?>
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
                    <?php echo CampoCsrf(); ?>
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
                                        <span class="GrupoTextoSimple">
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
                    <?php echo CampoCsrf(); ?>
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
            <div class="modal-dialog modal-dialog-centered ModalEditarPro">
                <div class="modal-content">

                    <form method="POST">
                    <?php echo CampoCsrf(); ?>
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


        <!-- ===================== BITÁCORA ===================== -->
                <div class="tab-pane fade <?= $TabActual==='bitacora'?'show active':'' ?>" id="bitacora">
                    <div class="card card-custom p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                            <div>
                                <h4 class="fw-bold text-danger mb-1">
                                    <i class="fa-solid fa-shield-halved me-2"></i> BITÁCORA DE MOVIMIENTOS
                                </h4>
                                <p class="text-muted mb-0">
                                    Aquí se muestran los últimos movimientos importantes del sistema: altas, modificaciones, bajas, importaciones, asistencia y calificaciones.
                                </p>
                            </div>
        
                            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                <div class="input-group input-group-sm search-container" style="width:min(360px,100%);">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input type="text" id="SearchBitacora" class="form-control" placeholder="Buscar movimiento...">
                                </div>

                                <div class="BadgeTurno bg-light text-dark border">
                                    <i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i>
                                    <?= count($BitacoraReciente) ?> REGISTROS RECIENTES
                                </div>
                            </div>
                        </div>
        
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <i class="fa-solid fa-circle-info me-2"></i>
                            Si antes no aparecían registros, normalmente era porque la tabla de bitácora no existía en la base instalada o no se estaba mostrando la pestaña correctamente. Esta versión crea/verifica la tabla y muestra los movimientos aquí.
                        </div>
        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle text-center" id="TableBitacora">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Rol</th>
                                        <th>Acción</th>
                                        <th>Tabla</th>
                                        <th>Registro</th>
                                        <th>Detalle</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($BitacoraReciente)): ?>
                                        <tr>
                                            <td colspan="8" class="py-5 text-muted fw-bold">
                                                <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>
                                                TODAVÍA NO HAY MOVIMIENTOS REGISTRADOS.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($BitacoraReciente as $Mov): ?>
                                            <tr>
                                                <td class="fw-bold searchable">
                                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($Mov['FechaRegistro'])), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="searchable">
                                                    <?= htmlspecialchars($Mov['NombreCompleto'] ?: 'SISTEMA', ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="searchable">
                                                    <span class="badge bg-dark">
                                                        <?= htmlspecialchars(strtoupper((string)($Mov['Rol'] ?? '-')), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="searchable">
                                                    <span class="badge bg-primary">
                                                        <?= htmlspecialchars((string)$Mov['Accion'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </td>
                                                <td class="searchable"><?= htmlspecialchars((string)($Mov['TablaAfectada'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="searchable"><?= htmlspecialchars((string)($Mov['RegistroId'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-start searchable">
                                                    <?= htmlspecialchars((string)($Mov['Detalle'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td class="searchable"><?= htmlspecialchars((string)($Mov['Ip'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="PagerBitacora" class="d-flex justify-content-center mt-3"></div>
                    </div>
                </div>
        
    </div>
</div>


<!-- MODAL GLOBAL PARA CONFIRMAR ELIMINACIONES -->
<div class="modal fade" id="ModalConfirmarEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ModalEliminarFijo">
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
    // BUSCADOR + PAGINACIÓN (7 POR PÁGINA)
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

    // FIX25: 7 registros por página en todas las tablas principales.
    SetupSearchPagination('SearchMaestros', 'TableMaestros', 'PagerMaestros', 7);
    SetupSearchPagination('SearchGrupos',   'TableGrupos',   'PagerGrupos',   7);
    SetupSearchPagination('SearchAlumnos',  'TableAlumnos',  'PagerAlumnos',  7);
    SetupSearchPagination('SearchExpedientes','TableExpedientes','PagerExpedientes',7);
    SetupSearchPagination('SearchAsig',     'TableAsig',     'PagerAsig',     7);
    SetupSearchPagination('SearchBitacora','TableBitacora','PagerBitacora',7);



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

<?php ImprimirCsrfScript(); ?>



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
    height:44px !important;
    min-height:44px !important;
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
    min-height:44px !important;
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



<!-- SGCE FIX13: blindaje definitivo del botón Cerrar Sesión en Admin -->
<style id="SgceFix13AdminLogoutFinal">
html body nav.navbar.navbar-dark.navbar-custom #BtnCerrarSesionAdmin,
html body nav.navbar.navbar-custom #BtnCerrarSesionAdmin,
html body .navbar-custom #BtnCerrarSesionAdmin,
html body #BtnCerrarSesionAdmin{
    width:210px !important;
    min-width:210px !important;
    max-width:210px !important;
    height:44px !important;
    min-height:44px !important;
    padding:0 18px !important;
    margin:0 !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:9px !important;
    border-radius:999px !important;
    background:#FFFFFF !important;
    background-color:#FFFFFF !important;
    background-image:none !important;
    color:#7A0818 !important;
    border:3px solid #4F050F !important;
    font-weight:900 !important;
    font-size:14px !important;
    line-height:1 !important;
    letter-spacing:.02em !important;
    text-decoration:none !important;
    white-space:nowrap !important;
    opacity:1 !important;
    filter:none !important;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.82),0 8px 18px rgba(122,8,24,.22),0 0 0 3px rgba(122,8,24,.08) !important;
    transform:none !important;
}
html body nav.navbar.navbar-dark.navbar-custom #BtnCerrarSesionAdmin i,
html body nav.navbar.navbar-dark.navbar-custom #BtnCerrarSesionAdmin span,
html body .navbar-custom #BtnCerrarSesionAdmin i,
html body .navbar-custom #BtnCerrarSesionAdmin span,
html body #BtnCerrarSesionAdmin i,
html body #BtnCerrarSesionAdmin span{
    color:#7A0818 !important;
    opacity:1 !important;
    filter:none !important;
}
html body nav.navbar.navbar-dark.navbar-custom #BtnCerrarSesionAdmin:hover,
html body nav.navbar.navbar-custom #BtnCerrarSesionAdmin:hover,
html body .navbar-custom #BtnCerrarSesionAdmin:hover,
html body #BtnCerrarSesionAdmin:hover,
html body nav.navbar.navbar-dark.navbar-custom #BtnCerrarSesionAdmin:focus-visible,
html body .navbar-custom #BtnCerrarSesionAdmin:focus-visible,
html body #BtnCerrarSesionAdmin:focus-visible{
    background:#7A0818 !important;
    background-color:#7A0818 !important;
    background-image:linear-gradient(135deg,#7A0818,#3B030A) !important;
    color:#FFFFFF !important;
    border-color:#3B030A !important;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.20),0 15px 32px rgba(122,8,24,.38),0 0 0 4px rgba(122,8,24,.12) !important;
    transform:translateY(-2px) !important;
    opacity:1 !important;
    filter:none !important;
}
html body nav.navbar.navbar-dark.navbar-custom #BtnCerrarSesionAdmin:hover i,
html body nav.navbar.navbar-dark.navbar-custom #BtnCerrarSesionAdmin:hover span,
html body .navbar-custom #BtnCerrarSesionAdmin:hover i,
html body .navbar-custom #BtnCerrarSesionAdmin:hover span,
html body #BtnCerrarSesionAdmin:hover i,
html body #BtnCerrarSesionAdmin:hover span,
html body #BtnCerrarSesionAdmin:focus-visible i,
html body #BtnCerrarSesionAdmin:focus-visible span{
    color:#FFFFFF !important;
}
</style>
<script>
(function(){
    function AplicarEstiloBotonCerrarSesionAdmin(Hover){
        var Boton=document.getElementById('BtnCerrarSesionAdmin');
        if(!Boton){return;}
        if(Hover){
            Boton.style.setProperty('background','#7A0818','important');
            Boton.style.setProperty('background-color','#7A0818','important');
            Boton.style.setProperty('background-image','linear-gradient(135deg,#7A0818,#3B030A)','important');
            Boton.style.setProperty('color','#FFFFFF','important');
            Boton.style.setProperty('border-color','#3B030A','important');
            Boton.style.setProperty('transform','translateY(-2px)','important');
            Boton.style.setProperty('box-shadow','inset 0 0 0 1px rgba(255,255,255,.20),0 15px 32px rgba(122,8,24,.38),0 0 0 4px rgba(122,8,24,.12)','important');
        }else{
            Boton.style.setProperty('background','#FFFFFF','important');
            Boton.style.setProperty('background-color','#FFFFFF','important');
            Boton.style.setProperty('background-image','none','important');
            Boton.style.setProperty('color','#7A0818','important');
            Boton.style.setProperty('border-color','#4F050F','important');
            Boton.style.setProperty('transform','none','important');
            Boton.style.setProperty('box-shadow','inset 0 0 0 1px rgba(255,255,255,.82),0 8px 18px rgba(122,8,24,.22),0 0 0 3px rgba(122,8,24,.08)','important');
        }
        Boton.querySelectorAll('i,span').forEach(function(Elemento){
            Elemento.style.setProperty('color',Hover ? '#FFFFFF' : '#7A0818','important');
        });
    }
    document.addEventListener('DOMContentLoaded',function(){
        var Boton=document.getElementById('BtnCerrarSesionAdmin');
        if(!Boton){return;}
        AplicarEstiloBotonCerrarSesionAdmin(false);
        Boton.addEventListener('mouseenter',function(){AplicarEstiloBotonCerrarSesionAdmin(true);});
        Boton.addEventListener('mouseleave',function(){AplicarEstiloBotonCerrarSesionAdmin(false);});
        Boton.addEventListener('focus',function(){AplicarEstiloBotonCerrarSesionAdmin(true);});
        Boton.addEventListener('blur',function(){AplicarEstiloBotonCerrarSesionAdmin(false);});
    });
})();
</script>



<!-- SGCE FIX26: tablas homologadas, 7 registros por página y paginador visible -->
<style id="SgceFix26TableLayoutFinal">
html body #TableMaestros,
html body #TableGrupos,
html body #TableAlumnos,
html body #TableAsig,
html body #TableBitacora,
html body #TableExpedientes{
    margin-bottom:0 !important;
    table-layout:fixed !important;
}

html body #TableMaestros,
html body #TableGrupos,
html body #TableAlumnos,
html body #TableAsig,
html body #TableBitacora{
    min-height:452px !important;
}

html body #TableExpedientes{
    min-height:452px !important;
}

html body #TableMaestros thead tr,
html body #TableGrupos thead tr,
html body #TableAlumnos thead tr,
html body #TableAsig thead tr,
html body #TableBitacora thead tr,
html body #TableExpedientes thead tr{
    height:40px !important;
}

html body #TableMaestros tbody tr,
html body #TableGrupos tbody tr,
html body #TableAlumnos tbody tr,
html body #TableAsig tbody tr,
html body #TableBitacora tbody tr{
    height:44px !important;
}

html body #TableExpedientes tbody tr{
    height:44px !important;
}

html body #TableMaestros th,
html body #TableMaestros td,
html body #TableGrupos th,
html body #TableGrupos td,
html body #TableAlumnos th,
html body #TableAlumnos td,
html body #TableAsig th,
html body #TableAsig td,
html body #TableBitacora th,
html body #TableBitacora td,
html body #TableExpedientes th,
html body #TableExpedientes td{
    padding:.36rem .55rem !important;
    vertical-align:middle !important;
}

html body #TableExpedientes th,
html body #TableExpedientes td{
    padding:.34rem .52rem !important;
}

html body #TableMaestros tbody,
html body #TableGrupos tbody,
html body #TableAlumnos tbody,
html body #TableAsig tbody,
html body #TableBitacora tbody,
html body #TableExpedientes tbody{
    position:relative !important;
}

html body #TableMaestros tbody::after,
html body #TableGrupos tbody::after,
html body #TableAlumnos tbody::after,
html body #TableAsig tbody::after,
html body #TableBitacora tbody::after,
html body #TableExpedientes tbody::after{
    content:"";
    display:block;
    height:1px;
}

html body #TableMaestros + *,
html body #TableGrupos + *,
html body #TableAlumnos + *,
html body #TableAsig + *,
html body #TableBitacora + *,
html body #TableExpedientes + *{
    margin-top:.45rem !important;
}

html body #PagerMaestros,
html body #PagerGrupos,
html body #PagerAlumnos,
html body #PagerAsig,
html body #PagerBitacora,
html body #PagerExpedientes{
    min-height:38px !important;
    margin-top:.45rem !important;
    align-items:center !important;
}

html body #PagerMaestros .btn,
html body #PagerGrupos .btn,
html body #PagerAlumnos .btn,
html body #PagerAsig .btn,
html body #PagerBitacora .btn,
html body #PagerExpedientes .btn{
    width:32px !important;
    height:32px !important;
    min-height:32px !important;
    padding:0 !important;
    border-radius:50% !important;
    font-size:12px !important;
    font-weight:900 !important;
}

html body #TableExpedientes .ActionBtn,
html body #TableAlumnos .ActionBtn,
html body #TableMaestros .ActionBtn,
html body #TableGrupos .ActionBtn{
    min-height:32px !important;
    height:32px !important;
    padding:0 10px !important;
    font-size:12px !important;
    white-space:nowrap !important;
}

html body #TableExpedientes .badge{
    min-height:28px !important;
    display:inline-flex !important;
    align-items:center !important;
    padding:0 12px !important;
}

html body #TableExpedientes tbody tr td,
html body #TableAlumnos tbody tr td,
html body #TableMaestros tbody tr td,
html body #TableGrupos tbody tr td,
html body #TableAsig tbody tr td,
html body #TableBitacora tbody tr td{
    line-height:1.15 !important;
}

html body #TableMaestros:has(tbody tr[style*="display: none"]),
html body #TableAlumnos:has(tbody tr[style*="display: none"]),
html body #TableGrupos:has(tbody tr[style*="display: none"]),
html body #TableAsig:has(tbody tr[style*="display: none"]),
html body #TableBitacora:has(tbody tr[style*="display: none"]),
html body #TableExpedientes:has(tbody tr[style*="display: none"]){
    border-bottom:1px solid transparent !important;
}



/* FIX26: asignaciones sin etiquetas redundantes, botones compactos y paginador visible. */

html body #TableAsig .ExportLabel{
    display:none !important;
}
html body #TableAsig .ExportIcons{
    margin-top:0 !important;
}
html body #TableAsig .ExportCell,
html body #TableAsig td.text-center{
    vertical-align:middle !important;
}
html body #TableAsig .ExportIcons{
    gap:4px !important;
    flex-wrap:nowrap !important;
    justify-content:center !important;
}
html body #TableAsig .ExportIcon{
    min-width:64px !important;
    height:32px !important;
    min-height:32px !important;
    padding:0 10px !important;
    border-radius:999px !important;
    font-size:11px !important;
    line-height:1 !important;
}
html body #TableAsig .ExportLabel{
    font-size:10px !important;
    margin-bottom:2px !important;
    line-height:1 !important;
}
html body #TableAsig .ActionBtn{
    height:32px !important;
    min-height:32px !important;
    padding:0 11px !important;
    font-size:12px !important;
}
html body #TableAsig tbody tr{
    height:48px !important;
}
html body #TableAsig td,
html body #TableAsig th{
    padding:.25rem .38rem !important;
}

@media(max-width:992px){
    html body #TableMaestros,
    html body #TableGrupos,
    html body #TableAlumnos,
    html body #TableAsig,
    html body #TableBitacora{
        min-height:438px !important;
    }
    html body #TableExpedientes{
        min-height:438px !important;
    }
}
</style>
<script id="SgceFix26FixedTableSpace">
(function(){
    function AjustarContenedoresTablas(){
        var Config={
            TableMaestros:{Rows:7,Height:452},
            TableGrupos:{Rows:7,Height:452},
            TableAlumnos:{Rows:7,Height:452},
            TableAsig:{Rows:7,Height:452},
            TableBitacora:{Rows:7,Height:452},
            TableExpedientes:{Rows:7,Height:452}
        };
        Object.keys(Config).forEach(function(Id){
            var Tabla=document.getElementById(Id);
            if(!Tabla){return;}
            var Wrap=Tabla.closest('.table-responsive');
            if(!Wrap){return;}
            Wrap.classList.add('SgceTableFixedSpace');
            Wrap.style.setProperty('min-height',Config[Id].Height+'px','important');
            Wrap.style.setProperty('max-height','none','important');
            Wrap.style.setProperty('overflow-x','auto','important');
            Wrap.style.setProperty('overflow-y','visible','important');
            Wrap.style.setProperty('border-radius','16px','important');
        });
    }
    document.addEventListener('DOMContentLoaded',AjustarContenedoresTablas);
})();
</script>




<style id="Fix27TablaAccionesSinRedundancia">
/* FIX27: grupos sin etiquetas redundantes y acciones sin botones apilados.
   Causa del bug: la columna Acciones quedaba demasiado angosta y .AdminActions tenía flex-wrap:wrap;
   por eso Editar y Eliminar brincaban a dos líneas. Aquí se fuerza ancho mínimo y nowrap. */
html body #TableGrupos .ExportLabel,
html body #TableAsig .ExportLabel{
    display:none !important;
}

html body #TableGrupos .ExportIcons,
html body #TableAsig .ExportIcons{
    margin-top:0 !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:6px !important;
    flex-wrap:nowrap !important;
    white-space:nowrap !important;
}

html body #TableGrupos .ExportIcon,
html body #TableAsig .ExportIcon{
    min-width:66px !important;
    width:66px !important;
    height:32px !important;
    min-height:32px !important;
    padding:0 9px !important;
    border-radius:999px !important;
    font-size:11px !important;
    line-height:1 !important;
    flex:0 0 auto !important;
}

html body #TableGrupos th,
html body #TableGrupos td,
html body #TableAsig th,
html body #TableAsig td{
    vertical-align:middle !important;
}

/* Columnas de exportación con suficiente ancho para los dos botones lado a lado. */
html body #TableGrupos th:nth-child(4),
html body #TableGrupos td:nth-child(4),
html body #TableGrupos th:nth-child(5),
html body #TableGrupos td:nth-child(5),
html body #TableGrupos th:nth-child(6),
html body #TableGrupos td:nth-child(6){
    min-width:150px !important;
    width:150px !important;
}

html body #TableAsig th:nth-child(4),
html body #TableAsig td:nth-child(4),
html body #TableAsig th:nth-child(5),
html body #TableAsig td:nth-child(5),
html body #TableAsig th:nth-child(6),
html body #TableAsig td:nth-child(6){
    min-width:150px !important;
    width:150px !important;
}

/* Acciones: nunca apilar botones en tablas de escritorio. */
html body .table .AdminActions{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    flex-wrap:nowrap !important;
    gap:8px !important;
    width:auto !important;
    min-width:max-content !important;
    white-space:nowrap !important;
}

html body .table .AdminActions form{
    display:inline-flex !important;
    flex:0 0 auto !important;
    margin:0 !important;
    padding:0 !important;
    white-space:nowrap !important;
}

html body .table .AdminActions .ActionBtn{
    flex:0 0 auto !important;
    white-space:nowrap !important;
    min-width:92px !important;
    height:34px !important;
    min-height:34px !important;
    padding:0 12px !important;
    font-size:12px !important;
}

html body #TableGrupos th:last-child,
html body #TableGrupos td:last-child,
html body #TableMaestros th:last-child,
html body #TableMaestros td:last-child,
html body #TableAsig th:last-child,
html body #TableAsig td:last-child{
    min-width:220px !important;
    width:220px !important;
}

html body #TableAlumnos th:last-child,
html body #TableAlumnos td:last-child{
    min-width:330px !important;
    width:330px !important;
}

html body #TableExpedientes th:last-child,
html body #TableExpedientes td:last-child{
    min-width:180px !important;
    width:180px !important;
}

html body #TableGrupos tbody tr,
html body #TableAsig tbody tr{
    height:50px !important;
}

html body #TableGrupos td,
html body #TableAsig td{
    padding-top:.30rem !important;
    padding-bottom:.30rem !important;
}

/* Si la pantalla es pequeña, se permite scroll horizontal de tabla, pero nunca botones encima de otros. */
html body .table-responsive{
    overflow-x:auto !important;
    overflow-y:visible !important;
}


/* ==========================================================
   FIX28 - DISEÑO COMPACTO SIN SCROLL HORIZONTAL
   Reduce ligeramente botones/iconos/textos para que las tablas
   quepan mejor con 7 registros por página y sin amontonarse.
   ========================================================== */
html body .table-responsive{
    overflow-x:hidden !important;
    overflow-y:visible !important;
}

html body .table{
    width:100% !important;
    table-layout:fixed !important;
    margin-bottom:0 !important;
}

html body .table th,
html body .table td{
    padding-top:.34rem !important;
    padding-bottom:.34rem !important;
    padding-left:.30rem !important;
    padding-right:.30rem !important;
    font-size:.82rem !important;
    line-height:1.12 !important;
    overflow:visible !important;
}

html body .table thead th{
    font-size:.70rem !important;
    letter-spacing:.025em !important;
    white-space:nowrap !important;
}

/* Quita etiquetas repetidas dentro de las filas: el encabezado de la tabla ya explica cada columna. */
html body #TableGrupos .ExportLabel,
html body #TableAsig .ExportLabel{
    display:none !important;
}

html body #TableGrupos .ExportIcons,
html body #TableAsig .ExportIcons{
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    gap:4px !important;
    flex-wrap:nowrap !important;
    white-space:nowrap !important;
    width:100% !important;
}

html body #TableGrupos .ExportIcon,
html body #TableAsig .ExportIcon{
    width:54px !important;
    min-width:54px !important;
    height:28px !important;
    min-height:28px !important;
    padding:0 6px !important;
    border-radius:999px !important;
    font-size:10.4px !important;
    gap:3px !important;
    line-height:1 !important;
    flex:0 0 auto !important;
    box-shadow:0 6px 13px rgba(15,23,42,.15) !important;
}

html body #TableGrupos .ExportIcon i,
html body #TableAsig .ExportIcon i{
    font-size:.72rem !important;
    margin:0 !important;
}

html body #TableGrupos .ExportIcon .ExportText,
html body #TableAsig .ExportIcon .ExportText{
    font-size:10.4px !important;
    line-height:1 !important;
}

html body .table .AdminActions{
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    flex-wrap:nowrap !important;
    gap:5px !important;
    width:100% !important;
    min-width:0 !important;
    white-space:nowrap !important;
}

html body .table .AdminActions form{
    display:inline-flex !important;
    flex:0 0 auto !important;
    margin:0 !important;
    padding:0 !important;
}

html body .table .AdminActions .ActionBtn{
    min-width:69px !important;
    width:auto !important;
    height:29px !important;
    min-height:29px !important;
    padding:0 8px !important;
    font-size:10.6px !important;
    gap:4px !important;
    border-radius:999px !important;
    line-height:1 !important;
    flex:0 0 auto !important;
    white-space:nowrap !important;
}

html body .table .AdminActions .ActionBtn i{
    font-size:.72rem !important;
    margin:0 !important;
}

/* Anchos compactos por tabla para evitar scroll horizontal. */
html body #TableGrupos th:nth-child(1),
html body #TableGrupos td:nth-child(1){width:8% !important;min-width:0 !important;}
html body #TableGrupos th:nth-child(2),
html body #TableGrupos td:nth-child(2){width:8% !important;min-width:0 !important;}
html body #TableGrupos th:nth-child(3),
html body #TableGrupos td:nth-child(3){width:12% !important;min-width:0 !important;}
html body #TableGrupos th:nth-child(4),
html body #TableGrupos td:nth-child(4),
html body #TableGrupos th:nth-child(5),
html body #TableGrupos td:nth-child(5),
html body #TableGrupos th:nth-child(6),
html body #TableGrupos td:nth-child(6){width:14% !important;min-width:0 !important;}
html body #TableGrupos th:nth-child(7),
html body #TableGrupos td:nth-child(7){width:16% !important;min-width:0 !important;}

html body #TableAsig th:nth-child(1),
html body #TableAsig td:nth-child(1){width:20% !important;min-width:0 !important;}
html body #TableAsig th:nth-child(2),
html body #TableAsig td:nth-child(2){width:16% !important;min-width:0 !important;}
html body #TableAsig th:nth-child(3),
html body #TableAsig td:nth-child(3){width:16% !important;min-width:0 !important;}
html body #TableAsig th:nth-child(4),
html body #TableAsig td:nth-child(4),
html body #TableAsig th:nth-child(5),
html body #TableAsig td:nth-child(5),
html body #TableAsig th:nth-child(6),
html body #TableAsig td:nth-child(6){width:11% !important;min-width:0 !important;}
html body #TableAsig th:nth-child(7),
html body #TableAsig td:nth-child(7){width:15% !important;min-width:0 !important;}

html body #TableAlumnos th:nth-child(1),
html body #TableAlumnos td:nth-child(1){width:54% !important;}
html body #TableAlumnos th:nth-child(2),
html body #TableAlumnos td:nth-child(2){width:14% !important;}
html body #TableAlumnos th:nth-child(3),
html body #TableAlumnos td:nth-child(3){width:32% !important;min-width:0 !important;}
html body #TableAlumnos .AdminActions .ActionBtn{min-width:72px !important;padding:0 8px !important;}

html body #TableMaestros th:last-child,
html body #TableMaestros td:last-child,
html body #TableExpedientes th:last-child,
html body #TableExpedientes td:last-child,
html body #TableBitacora th:last-child,
html body #TableBitacora td:last-child{
    min-width:0 !important;
}

/* Altura compacta: 7 registros visibles sin que las filas se sientan apretadas. */
html body .AdminTableBox,
html body .TablaFijaBox{
    min-height:390px !important;
}

html body #TableGrupos tbody tr,
html body #TableAsig tbody tr,
html body #TableAlumnos tbody tr,
html body #TableMaestros tbody tr,
html body #TableExpedientes tbody tr,
html body #TableBitacora tbody tr{
    height:42px !important;
}

html body #PagerMaestros,
html body #PagerGrupos,
html body #PagerAlumnos,
html body #PagerExpedientes,
html body #PagerAsig,
html body #PagerBitacora{
    margin-top:10px !important;
    padding-top:0 !important;
    padding-bottom:0 !important;
}

html body .PagerBtn,
html body [id^="Pager"] button{
    width:30px !important;
    height:30px !important;
    min-height:30px !important;
    padding:0 !important;
    font-size:11px !important;
}
</style>

</body>
</html>
