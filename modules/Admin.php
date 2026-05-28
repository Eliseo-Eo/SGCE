<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

/*
    Archivo: Admin.php
    Descripción: Panel principal del administrador.
    Aquí controlo maestros, grupos, alumnos, asignaciones, exportaciones, importaciones, búsquedas y paginación.
    También mantengo activa la pestaña donde estaba trabajando después de guardar, editar o eliminar.
*/

require_once dirname(__DIR__) . '/config/Conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$UserSession = VerificarSesionCookie($Pdo);
if ($UserSession && $UserSession['Rol'] === 'admin') { SgceGenerarBackupAutomatico($Pdo); }
if (!$UserSession || $UserSession['Rol'] !== 'admin') { header('Location: index.php'); exit; }

// Tab actual (para pintar activo en UI)
// Al entrar al panel sin indicar pestaña, siempre inicio en el DASHBOARD.
// Ya no uso la última pestaña guardada en sesión para evitar que al iniciar sesión abra Asignaciones u otra sección anterior.
$TabActual = SgceTabAdminPermitida($_GET['Tab'] ?? $_POST['Tab'] ?? 'inicio');
$_SESSION['Tab'] = $TabActual;

// ================================
// --- LÓGICA DE PROCESAMIENTO ---
// ================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    RequerirCsrfPost();

    $TabPost = SgceTabAdminPermitida($_POST['Tab'] ?? 'maestros');
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

            SgceRedirectAdminTab($TabPost);
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

            SgceRedirectAdminTab($TabPost);
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

            SgceRedirectAdminTab($TabPost);
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

            SgceRedirectAdminTab($TabPost);
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
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');

        if ($User === '' || $Pass === '' || $Nombre === '') {
            $_SESSION['Mensaje'] = "Completa Todos Los Campos Del Docente. (Nombre solo letras)";
            SgceRedirectAdminTab($TabPost);
        }

        $ValidacionPassword = SgceValidarPasswordFuerte($Pass);
        if ($ValidacionPassword !== true) {
            $_SESSION['Mensaje'] = $ValidacionPassword;
            SgceRedirectAdminTab($TabPost);
        }

        try {

            $Pdo->prepare("
                INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol)
                VALUES (?, ?, ?, 'maestro')
            ")->execute([$User, SgcePasswordHash($Pass), $Nombre]);
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_DOCENTE', 'Usuarios', $Pdo->lastInsertId(), 'DOCENTE REGISTRADO');

            $_SESSION['Mensaje'] = "Docente Registrado";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "Ese usuario ya existe. Usa otro username.";
            } else {
                $_SESSION['Mensaje'] = "Error al registrar docente.";
            }
        }

        SgceRedirectAdminTab($TabPost);
    }

    if (isset($_POST['EditMaestro'])) {

        $Id = intval($_POST['Id'] ?? 0);

        $User = trim($_POST['User'] ?? '');
        $Pass = trim($_POST['Pass'] ?? '');
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');

        if ($Id <= 0 || $User === '' || $Nombre === '') {
            $_SESSION['Mensaje'] = "Datos Del Docente Inválidos. (Nombre solo letras)";
            SgceRedirectAdminTab($TabPost);
        }

        if ($Pass !== '') {
            $ValidacionPassword = SgceValidarPasswordFuerte($Pass);
            if ($ValidacionPassword !== true) {
                $_SESSION['Mensaje'] = $ValidacionPassword;
                SgceRedirectAdminTab($TabPost);
            }
        }

        try {

            $SqlPassword = $Pass !== '' ? ', Password = ?' : '';
            $Params = [$Nombre, $User];
            if ($Pass !== '') { $Params[] = SgcePasswordHash($Pass); }
            $Params[] = $Id;
            $Pdo->prepare("
                UPDATE Usuarios
                SET NombreCompleto = ?, Username = ? $SqlPassword
                WHERE Id = ? AND Rol = 'maestro'
            ")->execute($Params);
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_DOCENTE', 'Usuarios', $Id, 'DOCENTE ACTUALIZADO');

            $_SESSION['Mensaje'] = "Docente Actualizado";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "Ese usuario ya existe. Usa otro username.";
            } else {
                $_SESSION['Mensaje'] = "Error al actualizar docente.";
            }
        }

        SgceRedirectAdminTab($TabPost);
    }

    // ----------------------------
    // GRUPOS
    // ----------------------------
    if (isset($_POST['AltaGrupo'])) {

        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');

        if (!SgceValidarGrado($Grado) || $Grupo === '' || $Turno === '') {
            $_SESSION['Mensaje'] = "Grupo Inválido: Grado Solo Números y Grupo Solo Letras Mayúsculas.";
            SgceRedirectAdminTab($TabPost);
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

        SgceRedirectAdminTab($TabPost);
    }

    if (isset($_POST['EditGrupo'])) {

        $Id = intval($_POST['Id'] ?? 0);

        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');

        if ($Id <= 0 || !SgceValidarGrado($Grado) || $Grupo === '' || $Turno === '') {
            $_SESSION['Mensaje'] = "Grupo Inválido: Grado Solo Números y Grupo Solo Letras Mayúsculas.";
            SgceRedirectAdminTab($TabPost);
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

        SgceRedirectAdminTab($TabPost);
    }

    // ----------------------------
    // ALUMNOS
    // ----------------------------
    if (isset($_POST['AltaAlumno'])) {

        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        if ($Nombre === '' || $GrupoId <= 0) {
            $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. (Nombre solo letras)";
            SgceRedirectAdminTab($TabPost);
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

        SgceRedirectAdminTab($TabPost);
    }

    if (isset($_POST['EditAlumno'])) {

        $Id = intval($_POST['Id'] ?? 0);
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        if ($Id <= 0 || $Nombre === '' || $GrupoId <= 0) {
            $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. (Nombre solo letras)";
            SgceRedirectAdminTab($TabPost);
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

        SgceRedirectAdminTab($TabPost);
    }

    // ----------------------------
    // ASIGNACIONES
    // ----------------------------
    if (isset($_POST['AltaAsignacion'])) {

        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');

        if ($MaestroId <= 0 || $GrupoId <= 0 || $Materia === '') {
            $_SESSION['Mensaje'] = "Datos De Asignación Inválidos.";
            SgceRedirectAdminTab($TabPost);
        }

        $StmtValMaestro = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Id = ? AND Rol = 'maestro' AND Activo = 1");
        $StmtValMaestro->execute([$MaestroId]);
        $StmtValGrupo = $Pdo->prepare("SELECT COUNT(*) FROM Grupos WHERE Id = ? AND Activo = 1");
        $StmtValGrupo->execute([$GrupoId]);
        if ((int)$StmtValMaestro->fetchColumn() <= 0 || (int)$StmtValGrupo->fetchColumn() <= 0) {
            $_SESSION['Mensaje'] = "La asignación requiere docente y grupo activos.";
            SgceRedirectAdminTab($TabPost);
        }

        try {

            $StmtDuplicadaActiva = $Pdo->prepare("
                SELECT Id
                FROM Asignaciones
                WHERE MaestroId = ?
                  AND GrupoId = ?
                  AND MateriaNombre = ?
                  AND Activo = 1
                LIMIT 1
            ");
            $StmtDuplicadaActiva->execute([$MaestroId, $GrupoId, $Materia]);
            $AsignacionActivaId = (int)$StmtDuplicadaActiva->fetchColumn();

            if ($AsignacionActivaId > 0) {
                $_SESSION['Mensaje'] = "Esa materia ya está asignada a ese docente y grupo.";
                SgceRedirectAdminTab($TabPost);
            }

            $StmtDuplicadaInactiva = $Pdo->prepare("
                SELECT Id
                FROM Asignaciones
                WHERE MaestroId = ?
                  AND GrupoId = ?
                  AND MateriaNombre = ?
                  AND Activo = 0
                LIMIT 1
            ");
            $StmtDuplicadaInactiva->execute([$MaestroId, $GrupoId, $Materia]);
            $AsignacionInactivaId = (int)$StmtDuplicadaInactiva->fetchColumn();

            if ($AsignacionInactivaId > 0) {
                $Pdo->prepare("UPDATE Asignaciones SET Activo = 1 WHERE Id = ?")->execute([$AsignacionInactivaId]);
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_ASIGNACION', 'Asignaciones', $AsignacionInactivaId, 'ASIGNACIÓN REACTIVADA DESDE ADMIN');
                $_SESSION['Mensaje'] = "Asignación Reactivada";
                SgceRedirectAdminTab($TabPost);
            }

            $Pdo->prepare("
                INSERT INTO Asignaciones (MaestroId, GrupoId, MateriaNombre)
                VALUES (?, ?, ?)
            ")->execute([$MaestroId, $GrupoId, $Materia]);
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_ASIGNACION', 'Asignaciones', $Pdo->lastInsertId(), 'MATERIA ASIGNADA');

            $_SESSION['Mensaje'] = "Materia Asignada";

        } catch (PDOException $Ex) {

            if ($Ex->getCode() === '23000') {
                $_SESSION['Mensaje'] = "Esa asignación ya existe. Cambia la materia, el docente o el grupo.";
            } else {
                $_SESSION['Mensaje'] = "Error al asignar materia.";
            }
        }

        SgceRedirectAdminTab($TabPost);
    }

    if (isset($_POST['EditAsignacion'])) {

        $Id = intval($_POST['Id'] ?? 0);
        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');

        if ($Id <= 0 || $MaestroId <= 0 || $GrupoId <= 0 || $Materia === '') {
            $_SESSION['Mensaje'] = "Datos De Asignación Inválidos.";
            SgceRedirectAdminTab($TabPost);
        }

        try {

            $StmtDuplicada = $Pdo->prepare("
                SELECT Id
                FROM Asignaciones
                WHERE MaestroId = ?
                  AND GrupoId = ?
                  AND MateriaNombre = ?
                  AND Activo = 1
                  AND Id <> ?
                LIMIT 1
            ");
            $StmtDuplicada->execute([$MaestroId, $GrupoId, $Materia, $Id]);

            if ((int)$StmtDuplicada->fetchColumn() > 0) {
                $_SESSION['Mensaje'] = "Ya existe otra asignación activa con ese docente, grupo y materia.";
                SgceRedirectAdminTab($TabPost);
            }

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

        SgceRedirectAdminTab($TabPost);
    }
}

// ================================
// --- CONSULTAS A LA BASE DE DATOS ---
// ================================

$PageSizeAdmin = 7;
$PageSizeAsignaciones = 6;
$PagMaestros = SgcePaginaActual('PagMaestros');
$PagGrupos = SgcePaginaActual('PagGrupos');
$PagAlumnos = SgcePaginaActual('PagAlumnos');
$PagAsig = SgcePaginaActual('PagAsig');
[$OffsetMaestros, $LimitMaestros] = SgceLimitOffset($PagMaestros, $PageSizeAdmin);
[$OffsetGrupos, $LimitGrupos] = SgceLimitOffset($PagGrupos, $PageSizeAdmin);
[$OffsetAlumnos, $LimitAlumnos] = SgceLimitOffset($PagAlumnos, $PageSizeAdmin);
[$OffsetAsig, $LimitAsig] = SgceLimitOffset($PagAsig, $PageSizeAsignaciones);

$Maestros = $Pdo->query("SELECT Id, NombreCompleto, Username FROM Usuarios WHERE Rol='maestro' AND Activo = 1 ORDER BY NombreCompleto ASC")->fetchAll();
$Grupos = $Pdo->query("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Activo = 1 ORDER BY Turno, Grado, Grupo ASC")->fetchAll();

$TotalMaestrosTabla = (int)$Pdo->query("SELECT COUNT(*) FROM Usuarios WHERE Rol='maestro' AND Activo = 1")->fetchColumn();
$StmtMaestrosTabla = $Pdo->prepare("SELECT Id, NombreCompleto, Username FROM Usuarios WHERE Rol='maestro' AND Activo = 1 ORDER BY NombreCompleto ASC LIMIT ? OFFSET ?");
$StmtMaestrosTabla->bindValue(1, $LimitMaestros, PDO::PARAM_INT);
$StmtMaestrosTabla->bindValue(2, $OffsetMaestros, PDO::PARAM_INT);
$StmtMaestrosTabla->execute();
$MaestrosTabla = $StmtMaestrosTabla->fetchAll();

$TotalGruposTabla = (int)$Pdo->query("SELECT COUNT(*) FROM Grupos WHERE Activo = 1")->fetchColumn();
$StmtGruposTabla = $Pdo->prepare("SELECT Id, Grado, Grupo, Turno FROM Grupos WHERE Activo = 1 ORDER BY Turno, Grado, Grupo ASC LIMIT ? OFFSET ?");
$StmtGruposTabla->bindValue(1, $LimitGrupos, PDO::PARAM_INT);
$StmtGruposTabla->bindValue(2, $OffsetGrupos, PDO::PARAM_INT);
$StmtGruposTabla->execute();
$GruposTabla = $StmtGruposTabla->fetchAll();

$TotalAlumnosTabla = (int)$Pdo->query("SELECT COUNT(*) FROM Alumnos A WHERE A.Activo = 1")->fetchColumn();
$StmtAlumnosTabla = $Pdo->prepare("SELECT A.Id, A.NombreCompleto, A.GrupoId, G.Grado, G.Grupo, G.Turno FROM Alumnos A LEFT JOIN Grupos G ON A.GrupoId = G.Id WHERE A.Activo = 1 ORDER BY G.Turno, G.Grado, G.Grupo, A.NombreCompleto ASC LIMIT ? OFFSET ?");
$StmtAlumnosTabla->bindValue(1, $LimitAlumnos, PDO::PARAM_INT);
$StmtAlumnosTabla->bindValue(2, $OffsetAlumnos, PDO::PARAM_INT);
$StmtAlumnosTabla->execute();
$Alumnos = $StmtAlumnosTabla->fetchAll();

$TotalAsignacionesTabla = (int)$Pdo->query("SELECT COUNT(*) FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id WHERE Asn.Activo = 1 AND U.Activo = 1 AND G.Activo = 1")->fetchColumn();
$StmtAsignacionesTabla = $Pdo->prepare("SELECT Asn.Id, Asn.MateriaNombre, U.NombreCompleto AS Maestro, U.Id AS MaestroId, G.Id AS GrupoId, G.Grado, G.Grupo, G.Turno FROM Asignaciones Asn JOIN Usuarios U ON Asn.MaestroId = U.Id JOIN Grupos G ON Asn.GrupoId = G.Id WHERE Asn.Activo = 1 AND U.Activo = 1 AND G.Activo = 1 ORDER BY U.NombreCompleto ASC LIMIT ? OFFSET ?");
$StmtAsignacionesTabla->bindValue(1, $LimitAsig, PDO::PARAM_INT);
$StmtAsignacionesTabla->bindValue(2, $OffsetAsig, PDO::PARAM_INT);
$StmtAsignacionesTabla->execute();
$Asignaciones = $StmtAsignacionesTabla->fetchAll();

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
$CicloActivo = SgceCicloActivo($Pdo);
$CicloActivoId = (int)($CicloActivo['Id'] ?? 0);
$QueryCicloActivoAsistencia = '';
if (!empty($CicloActivo['FechaInicio']) && !empty($CicloActivo['FechaFin'])) {
    $QueryCicloActivoAsistencia = '&FechaInicio=' . urlencode((string)$CicloActivo['FechaInicio']) . '&FechaFin=' . urlencode((string)$CicloActivo['FechaFin']) . '&CicloId=' . urlencode((string)$CicloActivoId);
}
$CicloFechaInicio = $CicloActivo['FechaInicio'] ?? date('Y-01-01');
$CicloFechaFin = $CicloActivo['FechaFin'] ?? date('Y-12-31');
$PromedioStmt = $Pdo->prepare("SELECT ROUND(AVG(C.Calificacion), 1) FROM Calificaciones C INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId WHERE P.CicloId = ? AND P.Activo = 1");
$PromedioStmt->execute([$CicloActivoId]);
$PromedioGeneral = $PromedioStmt->fetchColumn();
$PromedioGeneral = $PromedioGeneral !== null ? $PromedioGeneral : '0.0';
// En esta consulta calculo los alumnos con riesgo.
// Tomo faltas, retardos y promedios del ciclo escolar activo.
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
        WHERE FechaDia BETWEEN " . $Pdo->quote($CicloFechaInicio) . " AND " . $Pdo->quote($CicloFechaFin) . "
        GROUP BY AlumnoId
    ) AsisAgg ON AsisAgg.AlumnoId = Al.Id
    LEFT JOIN (
        SELECT
            AlumnoId,
            ROUND(AVG(Calificacion), 1) AS Promedio
        FROM Calificaciones C
        INNER JOIN PeriodosEvaluacion P ON P.Id = C.PeriodoId
        WHERE P.CicloId = " . (int)$CicloActivoId . "
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/sgce-base.css?v=1.0.0">
<?= SgceEstilosTema($Pdo) ?>

</head>
<body>

<div class="SgcePageWrap SgceModuleWrap container-fluid px-4 py-4">
    <?php
        $AdminTabMeta = [
            'inicio' => ['SGCE | Administrador', 'Panel principal, accesos rápidos, contadores y alumnos con riesgo.', 'fa-sliders'],
            'maestros' => ['Maestros', 'Alta, edición y control de docentes.', 'fa-user-tie'],
            'grupos' => ['Grupos', 'Control de grado, grupo y turno.', 'fa-users-rectangle'],
            'alumnos' => ['Alumnos', 'Inscripciones y administración de estudiantes.', 'fa-children'],
            'expedientes' => ['Expedientes', 'Historial y consulta individual de alumnos.', 'fa-folder-open'],
            'asignaciones' => ['Asignaciones', 'Materias vinculadas con docentes y grupos.', 'fa-book-open'],
            'bitacora' => ['Bitácora', 'Movimientos importantes realizados en el sistema.', 'fa-shield-halved']
        ];
        $AdminMeta = $AdminTabMeta[$TabActual] ?? $AdminTabMeta['inicio'];
    ?>

    <section class="SgceHero mb-4">
        <div class="SgceHeroInfo">
            <div class="SgceHeroIcon"><i class="fa-solid <?= htmlspecialchars($AdminMeta[2], ENT_QUOTES, 'UTF-8') ?>"></i></div>
            <div>
                <h1><?= htmlspecialchars($AdminMeta[0], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($AdminMeta[1], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <div class="SgceHeroActions">
            <?php if ($TabActual === 'inicio'): ?>
                <a href="Logout.php" class="SgceHeroBtn SgceHeroLogout" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Salir</span>
                </a>
            <?php else: ?>
                <a href="Admin.php?Tab=inicio" class="SgceBtnVolverInicio" title="Volver al inicio" aria-label="Volver al inicio"><i class="fa-solid fa-house"></i><span>Volver al inicio</span></a>
            <?php endif; ?>
        </div>
    </section>

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
<div class="tab-content">



        <!-- ===================== INICIO / DASHBOARD ===================== -->
        <div class="tab-pane fade <?= $TabActual==='inicio'?'show active':'' ?>" id="inicio">
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

            <?php
                $DashboardFechaHoy = date('d/m/Y');
                $DashboardCicloNombre = trim((string)($CicloActivo['Nombre'] ?? 'CICLO NO CONFIGURADO'));
                $DashboardEstadoTexto = 'Sin pases de lista hoy';
                $DashboardEstadoClase = 'StatusNeutral';
                if ((int)$AsistenciasHoy > 0 && (int)$FaltasHoy > 0) {
                    $DashboardEstadoTexto = 'Actividad con incidencias';
                    $DashboardEstadoClase = 'StatusWarning';
                } elseif ((int)$AsistenciasHoy > 0) {
                    $DashboardEstadoTexto = 'Asistencia registrada';
                    $DashboardEstadoClase = 'StatusOk';
                }

                $DashboardKpis = [
                    ['Alumnos activos', $TotalAlumnosActivos, 'fa-children', 'KpiBlue', 'Inscritos vigentes'],
                    ['Maestros activos', $TotalMaestrosActivos, 'fa-chalkboard-user', 'KpiGreen', 'Docentes disponibles'],
                    ['Grupos activos', $TotalGruposActivos, 'fa-users-rectangle', 'KpiOrange', 'Grado, grupo y turno'],
                    ['Asistencias hoy', $AsistenciasHoy, 'fa-calendar-check', 'KpiWine', 'Registros del día'],
                    ['Faltas hoy', $FaltasHoy, 'fa-circle-xmark', 'KpiRed', 'Incidencias actuales'],
                    ['Promedio general', $PromedioGeneral, 'fa-star', 'KpiPurple', 'Ciclo activo']
                ];
            ?>

            <div class="DashboardTopPro">
                <section class="DashboardAccessPanel">
                    <div class="DashboardSectionHeader">
                        <div>
                            <span class="DashboardSectionKicker"><i class="fa-solid fa-grip"></i> Accesos rápidos</span>
                            <h2>Panel principal</h2>
                        </div>
                        <span class="DashboardMiniBadge">13 módulos</span>
                    </div>

                    <div class="DashboardModuleGridPro ModulosRecomendados">
                        <a href="PeriodosAdmin.php" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>Periodos</span>
                            <small>Ciclos</small>
                        </a>
                        <a href="Admin.php?Tab=maestros" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-user-tie"></i>
                            <span>Maestros</span>
                            <small>Docentes</small>
                        </a>
                        <a href="Admin.php?Tab=grupos" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-users-rectangle"></i>
                            <span>Grupos</span>
                            <small>Grado y turno</small>
                        </a>
                        <a href="Admin.php?Tab=alumnos" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-children"></i>
                            <span>Alumnos</span>
                            <small>Inscripciones</small>
                        </a>
                        <a href="Admin.php?Tab=asignaciones" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-book-open"></i>
                            <span>Asignaciones</span>
                            <small>Materias</small>
                        </a>
                        <a href="AvisosAdmin.php" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-bullhorn"></i>
                            <span>Avisos</span>
                            <small>Comunicados</small>
                        </a>
                        <a href="Admin.php?Tab=expedientes" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-folder-open"></i>
                            <span>Expedientes</span>
                            <small>Alumnos</small>
                        </a>
                        <a href="ReportesAdmin.php" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>Reportes</span>
                            <small>Centro</small>
                        </a>
                        <a href="ConsultaPadre.php" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Padres</span>
                            <small>Consulta</small>
                        </a>
                        <a href="UsuariosAdmin.php" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-users-gear"></i>
                            <span>Usuarios</span>
                            <small>Roles</small>
                        </a>
                        <a href="RestaurarBD.php" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-database"></i>
                            <span>Respaldos</span>
                            <small>Datos</small>
                        </a>
                        <a href="ConfiguracionAdmin.php" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-school-circle-check"></i>
                            <span>Config.</span>
                            <small>Escuela</small>
                        </a>
                        <a href="Admin.php?Tab=bitacora" class="DashboardModuleCard DashboardModuleWine">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Bitácora</span>
                            <small>Movimientos</small>
                        </a>
                    </div>
                </section>

                <section class="DashboardSummaryPanel">
                    <div class="DashboardSectionHeader">
                        <div>
                            <span class="DashboardSectionKicker"><i class="fa-solid fa-chart-simple"></i> Monitoreo escolar</span>
                            <h2>Resumen general</h2>
                        </div>
                        <span class="DashboardMiniBadge DashboardCycleBadge"><?= htmlspecialchars($DashboardCicloNombre, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <div class="DashboardTodayStrip">
                        <div class="DashboardTodayIcon"><i class="fa-solid fa-calendar-day"></i></div>
                        <div class="DashboardTodayText">
                            <span>Estado del día</span>
                            <strong><?= htmlspecialchars($DashboardFechaHoy, ENT_QUOTES, 'UTF-8') ?></strong>
                            <small>Resumen operativo del ciclo activo</small>
                        </div>
                        <div class="DashboardStatusPill <?= htmlspecialchars($DashboardEstadoClase, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($DashboardEstadoTexto, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>

                    <div class="DashboardKpiGrid">
                        <?php foreach($DashboardKpis as $Kpi): ?>
                            <article class="DashboardKpiCard <?= htmlspecialchars($Kpi[3], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="DashboardKpiIcon"><i class="fa-solid <?= htmlspecialchars($Kpi[2], ENT_QUOTES, 'UTF-8') ?>"></i></div>
                                <div class="DashboardKpiValue"><?= htmlspecialchars((string)$Kpi[1], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="DashboardKpiLabel"><?= htmlspecialchars($Kpi[0], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="DashboardKpiHint"><?= htmlspecialchars($Kpi[4], ENT_QUOTES, 'UTF-8') ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="row g-3 DashboardRiskRow">

                <div class="col-12">
                    <section class="card card-custom DashboardRiskPro">
                        <div class="DashboardRiskHeader">
                            <div>
                                <span class="DashboardSectionKicker DashboardRiskKicker"><i class="fa-solid fa-triangle-exclamation"></i> Seguimiento preventivo</span>
                                <h2>Alumnos con mayor riesgo académico y de asistencia</h2>
                                <p>
                                    El riesgo se calcula con faltas, retardos y promedio menor a 7 dentro del ciclo activo.
                                </p>
                            </div>
                            <span class="DashboardRiskCount"><?= count($AlumnosRiesgo) ?> registros</span>
                        </div>

                        <?php if (count($AlumnosRiesgo) === 0): ?>
                            <div class="DashboardRiskEmpty">
                                <div class="DashboardRiskEmptyIcon"><i class="fa-solid fa-circle-check"></i></div>
                                <div>
                                    <strong>Sin alumnos en riesgo por ahora</strong>
                                    <span>Cuando existan faltas, retardos o promedios bajos, aparecerán aquí automáticamente.</span>
                                </div>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive DashboardRiskTableWrap">
                            <table class="table table-hover align-middle text-center DashboardRiskTable">
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
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </div>

        <!-- ===================== MAESTROS ===================== -->

        <div class="tab-pane fade <?= $TabActual==='maestros'?'show active':'' ?>" id="maestros">
            <div class="row MaestrosLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol">

                    <div class="card card-custom MaestrosSideCard MaestrosRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle">
                            <i class="fa-solid fa-user-plus"></i> Registrar Maestro
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaMaestro">
                                <input type="hidden" name="Tab" value="maestros">

                                <div class="MaestrosFieldGroup">
                                    <label>Nombre completo</label>
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control form-control-sm SoloLetrasMayus MaestrosInput"
                                           placeholder="NOMBRE COMPLETO"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup">
                                    <label>Usuario</label>
                                    <input type="text" name="User" class="form-control form-control-sm TextoLibre MaestrosInput" placeholder="USUARIO" required autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup">
                                    <label>Contraseña</label>
                                    <input type="password" name="Pass" class="form-control form-control-sm TextoLibre MaestrosInput" placeholder="CONTRASEÑA" required autocomplete="off">
                                </div>

                                <button type="submit" class="BtnPrimary BtnTeacherPrimary w-100">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar Maestro
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard MaestrosImportCard">
                        <div class="card-header-custom MaestrosCardTitle MaestrosImportTitle">
                            <i class="fa-solid fa-file-excel"></i> Importar CSV / Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarDocentes">
                                <input type="hidden" name="Tab" value="maestros">

                                <p class="MaestrosHelpText">
                                    FORMATO CSV O EXCEL: <code>NOMBRE, USUARIO, CONTRASEÑA</code>
                                </p>

                                <div class="MaestrosFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvDocentes" class="form-control form-control-sm MaestrosInput MaestrosFileInput" accept=".csv,.xlsx" required>
                                </div>

                                <button type="submit" class="BtnPrimary BtnTeacherPrimary w-100">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Cargar Archivo
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol">

                    <div class="card card-custom p-3 MaestrosTableCard">

                        <div class="d-flex justify-content-between align-items-center mb-3 MaestrosTableTop">
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

                                    <?php foreach($MaestrosTabla as $M): ?>
                                    <tr>
                                        <td class="text-start searchable"><?= htmlspecialchars($M['NombreCompleto']) ?></td>
                                        <td class="searchable"><?= htmlspecialchars($M['Username']) ?></td>

                                        <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit BtnTeacherEdit" data-bs-toggle="modal" data-bs-target="#EM<?= $M['Id'] ?>">
                                                <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                            </button>

                                            <form method="POST" class="m-0 p-0" data-confirm-delete="DOCENTE" data-confirm-message="¿DESEAS ELIMINAR ESTE DOCENTE? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                    <?php echo CampoCsrf(); ?>
                                                <input type="hidden" name="Tab" value="maestros">
                                                <button type="submit" name="DelMaestro" value="<?= $M['Id'] ?>" class="ActionBtn ActionDelete BtnTeacherDelete">
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
                        <?= SgceRenderPager('PagMaestros', $PagMaestros, $TotalMaestrosTabla, $PageSizeAdmin, ['Tab'=>'maestros']) ?>

                    </div>

                </div>

            </div>

            <?php foreach($MaestrosTabla as $M): ?>
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
                                <input type="password"
                                       name="Pass"
                                       value="" placeholder="NUEVA CONTRASEÑA OPCIONAL"
                                       class="form-control form-control-sm mb-3 TextoLibre"
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
            <div class="row MaestrosLayoutRow GruposLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol GruposSideCol">

                    <div class="card card-custom MaestrosSideCard GruposSideCard GruposRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle GruposCardTitle">
                            <i class="fa-solid fa-plus-square"></i> Crear Grupo
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack GruposFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaGrupo">
                                <input type="hidden" name="Tab" value="grupos">

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Grado</label>
                                    <input type="text"
                                           name="Grado"
                                           class="form-control form-control-sm MaestrosInput GruposInput"
                                           placeholder="GRADO (EJ: 1)"
                                           required
                                           inputmode="numeric"
                                           pattern="^\d+$"
                                           autocomplete="off"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Grupo</label>
                                    <input type="text"
                                           name="Grupo"
                                           class="form-control form-control-sm MaestrosInput GruposInput"
                                           placeholder="GRUPO (EJ: A)"
                                           required
                                           pattern="^[A-Z]+$"
                                           autocomplete="off"
                                           oninput="this.value = this.value.toUpperCase().replace(/[^A-Z]/g,'')">
                                </div>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Turno</label>
                                    <select name="Turno" class="form-select form-select-sm MaestrosInput GruposInput" required>
                                        <option value="">SELECCIONA TURNO...</option>
                                        <option value="MATUTINO">MATUTINO</option>
                                        <option value="VESPERTINO">VESPERTINO</option>
                                    </select>
                                </div>

                                <button type="submit" class="BtnPrimary BtnTeacherPrimary BtnGroupPrimary w-100">
                                    <i class="fa-solid fa-layer-group"></i> Guardar Grupo
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard GruposSideCard GruposImportCard">
                        <div class="card-header-custom MaestrosCardTitle MaestrosImportTitle GruposCardTitle GruposImportTitle">
                            <i class="fa-solid fa-file-excel"></i> Importar CSV / Excel
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack GruposFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarGrupos">
                                <input type="hidden" name="Tab" value="grupos">

                                <p class="MaestrosHelpText GruposHelpText">
                                    FORMATO CSV O EXCEL: <code>GRADO, GRUPO, TURNO</code><br>
                                    EJEMPLO: <code>1, C, VESPERTINO</code>
                                </p>

                                <div class="MaestrosFieldGroup GruposFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvGrupos" class="form-control form-control-sm MaestrosInput MaestrosFileInput GruposInput" accept=".csv,.xlsx" required>
                                </div>

                                <button type="submit" class="BtnPrimary BtnTeacherPrimary BtnGroupPrimary w-100">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Cargar Archivo
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol GruposTableCol">
                    <div class="card card-custom p-3 MaestrosTableCard GruposTableCard">

                        <div class="d-flex justify-content-between align-items-center mb-3 MaestrosTableTop GruposTableTop">
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
                                    <?php foreach($GruposTabla as $G): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($G['Grado']) ?></td>
                                        <td class="searchable"><span class="GruposGrupoBadge"><?= htmlspecialchars($G['Grupo']) ?></span></td>

                                        <td class="searchable">
                                            <span class="GruposTurnoBadge">
                                                <?= htmlspecialchars($G['Turno']) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel" target="_blank" title="Calificaciones del grupo en Excel" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf" target="_blank" title="Calificaciones del grupo en PDF" href="ExportarCalificaciones.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportHoy" target="_blank" title="Asistencias de hoy del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Hoy">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportHoy" target="_blank" title="Asistencias de hoy del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Hoy">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="ExportIcons">
                                                <a class="ExportIcon ExportExcel ExportTodas" target="_blank" title="Todas las asistencias del grupo en Excel" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Excel&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                    <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                                </a>
                                                <a class="ExportIcon ExportPdf ExportTodas" target="_blank" title="Todas las asistencias del grupo en PDF" href="ExportarAsistencia.php?GrupoId=<?= $G['Id'] ?>&Tipo=Pdf&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                    <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                                </a>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <div class="AdminActions">
                                                <button class="ActionBtn ActionEdit BtnGroupEdit" data-bs-toggle="modal" data-bs-target="#EG<?= $G['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="GRUPO" data-confirm-message="¿DESEAS ELIMINAR ESTE GRUPO? SI TIENE DATOS RELACIONADOS, EL SISTEMA PUEDE IMPEDIRLO.">
                    <?php echo CampoCsrf(); ?>
                                                    <input type="hidden" name="Tab" value="grupos">
                                                    <button type="submit" name="DelGrupo" value="<?= $G['Id'] ?>" class="ActionBtn ActionDelete BtnGroupDelete">
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

                        <?= SgceRenderPager('PagGrupos', $PagGrupos, $TotalGruposTabla, $PageSizeAdmin, ['Tab'=>'grupos']) ?>

                    </div>
                </div>

                <?php foreach($GruposTabla as $G): ?>
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
            <div class="card card-custom ExpedientesCard">
                <div class="card-body ExpedientesCardBody">

                    <div class="ExpedientesTop">
                        <div class="ExpedientesTitleBlock">
                            <span class="ExpedientesTitleIcon"><i class="fa-solid fa-folder-open"></i></span>
                            <div>
                                <h4>Expedientes de Alumnos</h4>
                                <p>Selecciona un grupo para consultar solo el padrón correspondiente y abrir el historial individual.</p>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="Admin.php" class="ExpedientesFilterForm">
                        <input type="hidden" name="Tab" value="expedientes">
                        <div class="ExpedientesFilterGrid <?= $ExpedienteGrupoId > 0 ? 'HasCleanButton' : '' ?>">
                            <div class="ExpedientesGroupField">
                                <label>Grado / Grupo / Turno</label>
                                <select name="ExpGrupoId" class="form-select" required>
                                    <option value="">SELECCIONA GRUPO...</option>
                                    <?php foreach($Grupos as $GExp): ?>
                                        <option value="<?= (int)$GExp['Id'] ?>" <?= ((int)$ExpedienteGrupoId === (int)$GExp['Id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($GExp['Grado'].' '.$GExp['Grupo'].' - '.$GExp['Turno'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="ExpedientesActionField">
                                <label>Acción</label>
                                <button type="submit" class="ActionBtn BtnExpedienteLoad">
                                    <i class="fa-solid fa-filter"></i><span>Cargar Expedientes</span>
                                </button>
                            </div>

                            <?php if($ExpedienteGrupoId > 0): ?>
                            <div class="ExpedientesActionField">
                                <label>Restablecer</label>
                                <a href="Admin.php?Tab=expedientes" class="ActionBtn BtnExpedienteClean">
                                    <i class="fa-solid fa-eraser"></i><span>Limpiar</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if($ExpedienteGrupoId <= 0): ?>
                        <div class="ExpedientesEmptyState">
                            <span><i class="fa-solid fa-circle-info"></i></span>
                            <div>
                                <strong>Selecciona un grupo para cargar expedientes.</strong>
                                <p>Así el sistema evita consultar todos los alumnos y mantiene la pantalla rápida y ordenada.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="ExpedientesTools">
                            <div class="ExpedientesSelectedGroup">
                                <i class="fa-solid fa-users"></i>
                                <span>Grupo seleccionado:</span>
                                <strong>
                                    <?= $GrupoExpedienteSeleccionado
                                        ? htmlspecialchars($GrupoExpedienteSeleccionado['Grado'].' '.$GrupoExpedienteSeleccionado['Grupo'].' - '.$GrupoExpedienteSeleccionado['Turno'], ENT_QUOTES, 'UTF-8')
                                        : 'NO DISPONIBLE' ?>
                                </strong>
                            </div>

                            <div class="input-group search-container ExpedientesSearch">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="text" id="SearchExpedientes" class="form-control" placeholder="Buscar expediente...">
                            </div>
                        </div>

                        <div class="table-responsive ExpedientesTableWrap">
                            <table class="table table-hover align-middle" id="TableExpedientes">
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th class="text-center">Grupo</th>
                                        <th class="text-center">Turno</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($AlumnosExpedientes as $Al): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($Al['NombreCompleto'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="searchable text-center">
                                            <span class="ExpedientesGroupBadge"><?= htmlspecialchars($Al['Grado'].' '.$Al['Grupo'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="searchable text-center">
                                            <span class="ExpedientesTurnBadge"><?= htmlspecialchars($Al['Turno'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a class="ActionBtn BtnExpedienteOpen" href="HistorialAlumno.php?AlumnoId=<?= $Al['Id'] ?>">
                                                <i class="fa-solid fa-folder-open"></i><span>Abrir Expediente</span>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if(count($AlumnosExpedientes) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="ExpedientesNoData">NO HAY ALUMNOS ACTIVOS EN ESTE GRUPO.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="PagerExpedientes" class="SgcePagerServer ExpedientesPager"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ===================== ALUMNOS ===================== -->

        <div class="tab-pane fade <?= $TabActual==='alumnos'?'show active':'' ?>" id="alumnos">
            <div class="row MaestrosLayoutRow AlumnosLayoutRow">

                <div class="col-xl-3 col-lg-4 MaestrosSideCol AlumnosSideCol">

                    <div class="card card-custom MaestrosSideCard AlumnosSideCard AlumnosRegisterCard mb-3">
                        <div class="card-header-custom MaestrosCardTitle AlumnosCardTitle">
                            <i class="fa-solid fa-user-plus"></i> Inscribir Alumno
                        </div>

                        <div class="card-body">
                            <form method="POST" class="MaestrosFormStack AlumnosFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="AltaAlumno">
                                <input type="hidden" name="Tab" value="alumnos">

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Nombre completo</label>
                                    <input type="text"
                                           name="Nombre"
                                           class="form-control form-control-sm MaestrosInput AlumnosInput SoloLetrasMayus"
                                           placeholder="NOMBRE COMPLETO"
                                           required
                                           pattern="^[A-ZÁÉÍÓÚÜÑ\s]+$"
                                           title="Solo letras y espacios"
                                           autocomplete="off">
                                </div>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Grupo</label>
                                    <select name="GrupoId" class="form-select form-select-sm MaestrosInput AlumnosInput" required>
                                        <option value="">SELECCIONAR...</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= $G['Id'] ?>">
                                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="BtnPrimary BtnStudentPrimary w-100">
                                    <i class="fa-solid fa-user-plus"></i> Registrar Alumno
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card card-custom MaestrosSideCard AlumnosSideCard AlumnosImportCard">
                        <div class="card-header-custom MaestrosCardTitle AlumnosCardTitle AlumnosImportTitle">
                            <i class="fa-solid fa-file-excel"></i> Importar Datos
                        </div>

                        <div class="card-body">
                            <form action="Importar.php" method="POST" enctype="multipart/form-data" class="MaestrosFormStack AlumnosFormStack">
                    <?php echo CampoCsrf(); ?>
                                <input type="hidden" name="ImportarAlumnos">
                                <input type="hidden" name="Tab" value="alumnos">

                                <p class="MaestrosHelpText AlumnosHelpText">
                                    Selecciona el grupo destino y carga un archivo CSV o Excel con nombres de alumnos.
                                </p>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Grupo destino</label>
                                    <select name="GrupoId" class="form-select form-select-sm MaestrosInput AlumnosInput" required>
                                        <option value="">¿A DÓNDE VAN?</option>
                                        <?php foreach($Grupos as $G): ?>
                                            <option value="<?= $G['Id'] ?>">
                                                <?= $G['Grado'] ?> "<?= $G['Grupo'] ?>" (<?= $G['Turno'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="MaestrosFieldGroup AlumnosFieldGroup">
                                    <label>Archivo CSV o Excel</label>
                                    <input type="file" name="CsvAlumnos" class="form-control form-control-sm MaestrosInput MaestrosFileInput AlumnosInput AlumnosFileInput" accept=".csv,.xlsx" required>
                                </div>

                                <button type="submit" class="BtnPrimary BtnStudentPrimary w-100">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Cargar Archivo
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="col-xl-9 col-lg-8 MaestrosTableCol AlumnosTableCol">

                    <div class="card card-custom p-3 AlumnosTableCard">

                        <div class="d-flex justify-content-between align-items-center mb-3 AlumnosTableTop">
                            <div>
                                <h6 class="mb-0 text-muted">Padrón de Alumnos</h6>
                                <small class="text-muted fw-semibold">Consulta, expediente y administración de estudiantes.</small>
                            </div>

                            <div class="input-group input-group-sm search-container w-50">
                                <span class="input-group-text">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </span>
                                <input type="text" id="SearchAlumnos" class="form-control" placeholder="Buscar alumno...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="TableAlumnos">

                                <thead>
                                    <tr>
                                        <th>Nombre del Alumno</th>
                                        <th class="text-center">Grupo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach($Alumnos as $Al): ?>
                                    <tr>
                                        <td class="searchable fw-bold"><?= htmlspecialchars($Al['NombreCompleto']) ?></td>

                                        <td class="searchable text-center">
                                            <?= $Al['Grado']
                                                ? "<span class='badge AlumnosGroupBadge'>".$Al['Grado']." ".$Al['Grupo']."</span>"
                                                : '<span class="text-danger small fw-bold">Sin Grupo</span>' ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="AdminActions AlumnosActions">
                                                <a class="ActionBtn BtnStudentFile" href="HistorialAlumno.php?AlumnoId=<?= $Al['Id'] ?>">
                                                    <i class="fa-solid fa-folder-open"></i><span>Expediente</span>
                                                </a>

                                                <button class="ActionBtn BtnStudentEdit" data-bs-toggle="modal" data-bs-target="#EAl<?= $Al['Id'] ?>">
                                                    <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                                </button>

                                                <form method="POST" class="m-0 p-0" data-confirm-delete="ALUMNO" data-confirm-message="¿DESEAS DAR DE BAJA A ESTE ALUMNO? ESTA ACCIÓN NO SE PUEDE DESHACER.">
                    <?php echo CampoCsrf(); ?>
                                                    <input type="hidden" name="Tab" value="alumnos">
                                                    <button type="submit" name="DelAlumno" value="<?= $Al['Id'] ?>" class="ActionBtn BtnStudentDelete">
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

                        <?= SgceRenderPager('PagAlumnos', $PagAlumnos, $TotalAlumnosTabla, $PageSizeAdmin, ['Tab'=>'alumnos']) ?>

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
            <div class="card card-custom shadow-sm border-0 AsignacionesTableCard">

                <div class="card-header bg-white py-3 border-bottom AsignacionesHeaderCard">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-link text-dark me-2"></i>
                        Nueva Asignación Académica
                    </h6>
                </div>

                <div class="card-body p-4 AsignacionesCardBody">

                    <form method="POST" class="row g-3 align-items-end mb-4 AsignacionForm">
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

                        <div class="col-md-2 AsignacionButtonCol">
                            <label class="small fw-bold text-muted d-block">Acción</label>
                            <button type="submit" class="btn w-100 fw-bold BtnAsignacionPrimary">
                                <i class="fa-solid fa-link"></i><span>Vincular</span>
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

                    <div class="table-responsive AsignacionesTableWrap">
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

                                    <td class="searchable">
                                        <span class="AsignacionMateriaTexto"><?= htmlspecialchars($Asg['MateriaNombre']) ?></span>
                                    </td>

                                    <td class="searchable">
                                        <?php $TurnoAsignacion = strtoupper((string)$Asg['Turno']); ?>
                                        <div class="AsignacionGrupoChips">
                                            <span class="AsignacionGrupoChip AsignacionGradoChip"><?= htmlspecialchars($Asg['Grado'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="AsignacionGrupoChip AsignacionGrupoChipLetra"><?= htmlspecialchars($Asg['Grupo'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="AsignacionGrupoChip AsignacionTurnoChip"><?= htmlspecialchars($TurnoAsignacion, ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>

                                    <!-- CALIFICACIONES -->
                                    <td class="text-center">
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
                                        <div class="ExportIcons">
                                            <a class="ExportIcon ExportExcel ExportTodas"
                                               target="_blank"
                                               title="Exportar todas las asistencias en Excel"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Excel&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                <i class="fa-solid fa-file-excel"></i><span class="ExportText">Excel</span>
                                            </a>

                                            <a class="ExportIcon ExportPdf ExportTodas"
                                               target="_blank"
                                               title="Exportar todas las asistencias en PDF"
                                               href="ExportarAsistencia.php?AsignacionId=<?= $Asg['Id'] ?>&Tipo=Pdf&Rango=Todas<?= $QueryCicloActivoAsistencia ?>">
                                                <i class="fa-solid fa-file-pdf"></i><span class="ExportText">PDF</span>
                                            </a>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                            <div class="AdminActions">
<button class="ActionBtn ActionEdit BtnAsignacionEdit" data-bs-toggle="modal" data-bs-target="#EAsg<?= $Asg['Id'] ?>">
                                            <i class="fa-solid fa-pen-to-square"></i><span>Editar</span>
                                        </button>

                                        <form method="POST" class="m-0 p-0" data-confirm-delete="ASIGNACIÓN" data-confirm-message="¿DESEAS ELIMINAR ESTA ASIGNACIÓN ACADÉMICA?">
                    <?php echo CampoCsrf(); ?>
                                            <input type="hidden" name="Tab" value="asignaciones">
                                            <button type="submit" name="DelAsignacion" value="<?= $Asg['Id'] ?>" class="ActionBtn ActionDelete BtnAsignacionDelete">
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
                    <?= SgceRenderPager('PagAsig', $PagAsig, $TotalAsignacionesTabla, $PageSizeAsignaciones, ['Tab'=>'asignaciones']) ?>

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
                    <div class="card card-custom p-4 SgceBitacoraCard">
                        <div class="SgceBitacoraHead">
                            <div class="SgceBitacoraTitle">
                                <span class="SgceBitacoraIcon"><i class="fa-solid fa-shield-halved"></i></span>
                                <div>
                                    <h4>BITÁCORA DE MOVIMIENTOS</h4>
                                    <p>Aquí se muestran los últimos movimientos importantes del sistema: altas, modificaciones, bajas, importaciones, asistencia y calificaciones.</p>
                                </div>
                            </div>

                            <div class="SgceBitacoraTools">
                                <div class="SgceSearchBox SgceSearchBoxSmall">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="SearchBitacora" placeholder="Buscar movimiento...">
                                </div>

                                <div class="SgceCountPill">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span><?= count($BitacoraReciente) ?> registros recientes</span>
                                </div>
                            </div>
                        </div>

                        <div class="SgceInfoBanner mb-4">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Esta pantalla registra movimientos importantes del sistema y ayuda a revisar altas, bajas, modificaciones, sesiones, asistencias y calificaciones.</span>
                        </div>

                        <div class="table-responsive SgceTableWrap">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- ============================================================
     NOTIFICACIONES AUTOMÁTICAS DEL SISTEMA
     ------------------------------------------------------------
     Bloque utilizado para homologar notificaciones visuales del sistema.
     Cualquier alerta puede cerrarse manualmente con la tachita y,
     si el usuario no la cierra, desaparece sola después de unos segundos.
     ============================================================ -->





<?php ImprimirCsrfScript(); ?>
<script src="assets/js/sgce-shared.js?v=1.0.0"></script>
<script src="assets/js/Admin.js?v=1.0.0"></script>
</body>
</html>
