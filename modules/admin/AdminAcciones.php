<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    RequerirCsrfPost();
    SgceExigirPermiso($UserSession, 'catalogos', 'No tienes permiso para modificar catálogos escolares.');

    $TabPost = SgceTabAdminPermitida($_POST['Tab'] ?? 'maestros', $UserSession);
    $_SESSION['Tab'] = $TabPost;
    $CicloActivoAccion = SgceCicloActivo($Pdo);
    $CicloActivoAccionId = (int)($CicloActivoAccion['Id'] ?? 0);

    if (isset($_POST['DelMaestro'])) {
        $Id = intval($_POST['DelMaestro']);
        if ($Id > 0) {
            try {
                $AsignacionesActuales = SgceDocenteAsignacionesActuales($Pdo, $Id);
                if ($AsignacionesActuales > 0) {
                    $_SESSION['Mensaje'] = "No se puede desactivar: el docente tiene $AsignacionesActuales asignación(es) activa(s) en el ciclo actual. Primero realiza el relevo/interinato a otro docente.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }
                $Pdo->prepare("UPDATE Usuarios SET Activo = 0, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_DOCENTE', 'Usuarios', $Id, 'DOCENTE DESACTIVADO SIN ASIGNACIONES ACTUALES');
                $_SESSION['Mensaje'] = "Docente Desactivado";
            } catch (PDOException $Ex) { $_SESSION['Mensaje'] = "Error al eliminar docente."; }
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

    if (isset($_POST['DelGrupo'])) {
        $Id = intval($_POST['DelGrupo']);
        if ($Id > 0) {
            try {
                $Grupo = SgceGrupoObtenerActivoPorId($Pdo, $Id);
                if (!$Grupo) { $_SESSION['Mensaje'] = "Solo puedes desactivar grupos del ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
                $Pdo->prepare("UPDATE Grupos SET Activo = 0 WHERE Id = ?")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_GRUPO', 'Grupos', $Id, 'GRUPO DESACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Grupo Desactivado";
            } catch (PDOException $Ex) {
                $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "No se puede eliminar el grupo porque tiene datos relacionados (alumnos/asignaciones)." : "Error al eliminar grupo.";
            }
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

    if (isset($_POST['DelAlumno'])) {
        $Id = intval($_POST['DelAlumno']);
        if ($Id > 0) {
            try {
                if ($CicloActivoAccionId > 0) {
                    $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'BAJA' WHERE AlumnoId = ? AND CicloId = ?")->execute([$Id, $CicloActivoAccionId]);
                }
                $Pdo->prepare("UPDATE Alumnos SET Activo = 0 WHERE Id = ?")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_ALUMNO', 'Alumnos', $Id, 'ALUMNO DESACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Alumno Desactivado";
            } catch (PDOException $Ex) { $_SESSION['Mensaje'] = "Error al eliminar alumno."; }
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

    if (isset($_POST['DelAsignacion'])) {
        $Id = intval($_POST['DelAsignacion']);
        if ($Id > 0) {
            try {
                $Asignacion = SgceAsignacionObtener($Pdo, $Id);
                if (!$Asignacion || (int)$Asignacion['CicloId'] !== $CicloActivoAccionId) {
                    $_SESSION['Mensaje'] = "Solo puedes desactivar asignaciones del ciclo activo.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }
                if (SgceAsignacionTieneDatosAcademicos($Pdo, $Id)) {
                    $_SESSION['Mensaje'] = "No se puede eliminar/desactivar esta asignación porque ya tiene asistencias o calificaciones. La materia debe conservarse; si cambió el docente, usa Editar para hacer relevo/interinato.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }
                $Pdo->prepare("UPDATE Asignaciones SET Activo = 0 WHERE Id = ? AND CicloId = ?")->execute([$Id, $CicloActivoAccionId]);
                $Pdo->prepare('UPDATE AsignacionDocenteHistorial SET FechaFin = NOW() WHERE AsignacionId = ? AND FechaFin IS NULL')->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_ASIGNACION', 'Asignaciones', $Id, 'ASIGNACIÓN SIN DATOS ACADÉMICOS DESACTIVADA');
                $_SESSION['Mensaje'] = "Materia Desasignada";
            } catch (PDOException $Ex) { $_SESSION['Mensaje'] = "Error al desasignar materia."; }
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

    if (isset($_POST['AltaMaestro'])) {
        $User = trim($_POST['User'] ?? '');
        $Pass = trim($_POST['Pass'] ?? '');
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        if ($User === '' || $Pass === '' || $Nombre === '') { $_SESSION['Mensaje'] = "Completa Todos Los Campos Del Docente. (Nombre solo letras)"; SgceRedirectAdminTab($TabPost, $UserSession); }
        if (SgceLongitudTexto($Nombre) > 140) { $_SESSION['Mensaje'] = "El nombre del docente no debe superar 140 caracteres."; SgceRedirectAdminTab($TabPost, $UserSession); }
        if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $User)) { $_SESSION['Mensaje'] = "El usuario del docente debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @."; SgceRedirectAdminTab($TabPost, $UserSession); }
        $ValidacionPassword = SgceValidarPasswordFuerte($Pass);
        if ($ValidacionPassword !== true) { $_SESSION['Mensaje'] = $ValidacionPassword; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $StmtUsuarioExistente = $Pdo->prepare("SELECT Id, Rol, Activo FROM Usuarios WHERE Username = ? LIMIT 1");
            $StmtUsuarioExistente->execute([$User]);
            $UsuarioExistente = $StmtUsuarioExistente->fetch();
            if ($UsuarioExistente) {
                $UsuarioIdExistente = (int)$UsuarioExistente['Id'];
                if ((string)$UsuarioExistente['Rol'] !== 'maestro') { $_SESSION['Mensaje'] = "Ese usuario ya existe con otro rol. Usa otro username."; SgceRedirectAdminTab($TabPost, $UserSession); }
                if ((int)$UsuarioExistente['Activo'] === 1) { $_SESSION['Mensaje'] = "Ese docente ya está activo. Usa otro username."; SgceRedirectAdminTab($TabPost, $UserSession); }
                $Pdo->prepare("UPDATE Usuarios SET NombreCompleto = ?, Password = ?, Activo = 1, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'")->execute([$Nombre, SgcePasswordHash($Pass), $UsuarioIdExistente]);
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_DOCENTE', 'Usuarios', $UsuarioIdExistente, 'DOCENTE REACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Docente Reactivado";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, Rol) VALUES (?, ?, ?, 'maestro')")->execute([$User, SgcePasswordHash($Pass), $Nombre]);
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_DOCENTE', 'Usuarios', $Pdo->lastInsertId(), 'DOCENTE REGISTRADO');
            $_SESSION['Mensaje'] = "Docente Registrado";
        } catch (PDOException $Ex) { $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Ese usuario ya existe. Usa otro username." : "Error al registrar docente."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditMaestro'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $User = trim($_POST['User'] ?? '');
        $Pass = trim($_POST['Pass'] ?? '');
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        if ($Id <= 0 || $User === '' || $Nombre === '') { $_SESSION['Mensaje'] = "Datos Del Docente Inválidos. (Nombre solo letras)"; SgceRedirectAdminTab($TabPost, $UserSession); }
        if (SgceLongitudTexto($Nombre) > 140) { $_SESSION['Mensaje'] = "El nombre del docente no debe superar 140 caracteres."; SgceRedirectAdminTab($TabPost, $UserSession); }
        if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $User)) { $_SESSION['Mensaje'] = "El usuario del docente debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @."; SgceRedirectAdminTab($TabPost, $UserSession); }
        if ($Pass !== '') { $ValidacionPassword = SgceValidarPasswordFuerte($Pass); if ($ValidacionPassword !== true) { $_SESSION['Mensaje'] = $ValidacionPassword; SgceRedirectAdminTab($TabPost, $UserSession); } }
        try {
            $SqlPassword = $Pass !== '' ? ', Password = ?' : '';
            $Params = [$Nombre, $User];
            if ($Pass !== '') { $Params[] = SgcePasswordHash($Pass); }
            $Params[] = $Id;
            $Pdo->prepare("UPDATE Usuarios SET NombreCompleto = ?, Username = ? $SqlPassword, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'")->execute($Params);
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_DOCENTE', 'Usuarios', $Id, 'DOCENTE ACTUALIZADO');
            $_SESSION['Mensaje'] = "Docente Actualizado";
        } catch (PDOException $Ex) { $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Ese usuario ya existe. Usa otro username." : "Error al actualizar docente."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaGrupo'])) {
        $Oferta = SgceOfertaActiva($Pdo);
        $OfertaId = (int)($Oferta['Id'] ?? 0);
        $EtapaId = intval($_POST['EtapaId'] ?? 0);
        $CarreraId = intval($_POST['CarreraId'] ?? 0);
        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');
        if ($CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Primero configura un ciclo escolar activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        if ($OfertaId > 0) {
            $Etapa = SgceEtapaAcademicaPorId($Pdo, $EtapaId);
            if (!$Etapa || (int)$Etapa['OfertaId'] !== $OfertaId) { $_SESSION['Mensaje'] = "Selecciona una etapa académica válida."; SgceRedirectAdminTab($TabPost, $UserSession); }
            if (!empty($Oferta['UsaCarreras'])) {
                $Carrera = SgceCarreraPorId($Pdo, $CarreraId);
                if (!$Carrera || (int)$Carrera['Activo'] !== 1) { $_SESSION['Mensaje'] = "Selecciona una carrera/programa activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            } else { $CarreraId = 0; }
            $Grado = (string)$Etapa['Nombre'];
        }
        if (!SgceValidarGrado($Grado) || SgceLongitudTexto($Grado) > 40 || $Grupo === '' || $Turno === '') { $_SESSION['Mensaje'] = "Grupo inválido: selecciona etapa/grado, grupo y turno."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $GrupoId = SgceGrupoCrearOReactivar($Pdo, $CicloActivoAccionId, $Grado, $Grupo, $Turno, $EtapaId, $CarreraId, $OfertaId);
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_GRUPO', 'Grupos', $GrupoId, 'GRUPO CREADO/REACTIVADO EN CICLO ACTIVO CON ESTRUCTURA MULTIESCOLAR');
            $_SESSION['Mensaje'] = "Grupo Creado";
        } catch (Throwable $Ex) { $_SESSION['Mensaje'] = $Ex instanceof PDOException && $Ex->getCode() === '23000' ? "Ese grupo ya existe en el ciclo activo." : "Error al crear grupo: " . $Ex->getMessage(); }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditGrupo'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $Oferta = SgceOfertaActiva($Pdo);
        $OfertaId = (int)($Oferta['Id'] ?? 0);
        $EtapaId = intval($_POST['EtapaId'] ?? 0);
        $CarreraId = intval($_POST['CarreraId'] ?? 0);
        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');
        if ($OfertaId > 0) {
            $Etapa = SgceEtapaAcademicaPorId($Pdo, $EtapaId);
            if (!$Etapa || (int)$Etapa['OfertaId'] !== $OfertaId) { $_SESSION['Mensaje'] = "Selecciona una etapa académica válida."; SgceRedirectAdminTab($TabPost, $UserSession); }
            if (!empty($Oferta['UsaCarreras'])) {
                $Carrera = SgceCarreraPorId($Pdo, $CarreraId);
                if (!$Carrera || (int)$Carrera['Activo'] !== 1) { $_SESSION['Mensaje'] = "Selecciona una carrera/programa activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            } else { $CarreraId = 0; }
            $Grado = (string)$Etapa['Nombre'];
        }
        if ($Id <= 0 || !SgceValidarGrado($Grado) || SgceLongitudTexto($Grado) > 40 || $Grupo === '' || $Turno === '') { $_SESSION['Mensaje'] = "Grupo inválido: selecciona etapa/grado, grupo y turno."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            if (!SgceGrupoObtenerActivoPorId($Pdo, $Id)) { $_SESSION['Mensaje'] = "Solo puedes editar grupos del ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $Pdo->prepare("UPDATE Grupos SET OfertaId = NULLIF(?,0), CarreraId = NULLIF(?,0), EtapaId = NULLIF(?,0), Grado = ?, Grupo = ?, Turno = ? WHERE Id = ? AND CicloId = ?")->execute([$OfertaId, $CarreraId, $EtapaId, $Grado, $Grupo, $Turno, $Id, $CicloActivoAccionId]);
            $Pdo->prepare("UPDATE AlumnoInscripciones SET OfertaId = NULLIF(?,0), CarreraId = NULLIF(?,0), EtapaId = NULLIF(?,0) WHERE GrupoId = ? AND CicloId = ?")->execute([$OfertaId, $CarreraId, $EtapaId, $Id, $CicloActivoAccionId]);
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_GRUPO', 'Grupos', $Id, 'GRUPO ACTUALIZADO CON ESTRUCTURA MULTIESCOLAR');
            $_SESSION['Mensaje'] = "Grupo Actualizado";
        } catch (PDOException $Ex) { $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Ese grupo ya existe en el ciclo activo." : "Error al actualizar grupo."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaAlumno'])) {
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        if ($Nombre === '' || SgceLongitudTexto($Nombre) > 160 || $GrupoId <= 0 || $CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. Nombre solo letras, máximo 160 caracteres, ciclo activo y grupo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $GrupoSel = SgceGrupoObtenerActivoPorId($Pdo, $GrupoId);
            if (!$GrupoSel) { $_SESSION['Mensaje'] = "El grupo seleccionado debe pertenecer al ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $StmtAlumnoExistente = $Pdo->prepare("SELECT A.Id, A.Activo FROM Alumnos A INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = A.Id WHERE A.NombreCompleto = ? AND AI.CicloId = ? AND AI.GrupoId = ? AND AI.Estado = 'INSCRITO' LIMIT 1");
            $StmtAlumnoExistente->execute([$Nombre, $CicloActivoAccionId, $GrupoId]);
            $AlumnoExistente = $StmtAlumnoExistente->fetch();
            if ($AlumnoExistente) { $_SESSION['Mensaje'] = "Ese alumno ya está inscrito en el grupo seleccionado para el ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }

            $StmtAlumnoMismoNombre = $Pdo->prepare("SELECT Id, Activo FROM Alumnos WHERE NombreCompleto = ? AND GrupoId = ? LIMIT 1");
            $StmtAlumnoMismoNombre->execute([$Nombre, $GrupoId]);
            $AlumnoBase = $StmtAlumnoMismoNombre->fetch();
            if ($AlumnoBase) {
                $AlumnoId = (int)$AlumnoBase['Id'];
                $Pdo->prepare("UPDATE Alumnos SET Activo = 1 WHERE Id = ?")->execute([$AlumnoId]);
            } else {
                $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, GrupoId, Activo) VALUES (?, ?, 1)")->execute([$Nombre, $GrupoId]);
                $AlumnoId = (int)$Pdo->lastInsertId();
            }
            SgceAlumnoInscribirEnCiclo($Pdo, $AlumnoId, $CicloActivoAccionId, $GrupoId, 'INSCRITO');
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_ALUMNO', 'Alumnos', $AlumnoId, 'ALUMNO INSCRITO EN CICLO ACTIVO');
            $_SESSION['Mensaje'] = "Alumno Inscrito";
        } catch (PDOException $Ex) { $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "No se pudo inscribir: ya existe una inscripción para este ciclo." : "Error al inscribir alumno."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditAlumno'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        if ($Id <= 0 || $Nombre === '' || SgceLongitudTexto($Nombre) > 160 || $GrupoId <= 0 || $CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. Nombre solo letras, máximo 160 caracteres y selecciona grupo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            if (!SgceGrupoObtenerActivoPorId($Pdo, $GrupoId)) { $_SESSION['Mensaje'] = "El grupo debe pertenecer al ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $Pdo->prepare("UPDATE Alumnos SET NombreCompleto = ?, GrupoId = ?, Activo = 1 WHERE Id = ?")->execute([$Nombre, $GrupoId, $Id]);
            $StmtIns = $Pdo->prepare("SELECT Id FROM AlumnoInscripciones WHERE AlumnoId = ? AND CicloId = ? LIMIT 1");
            $StmtIns->execute([$Id, $CicloActivoAccionId]);
            $InsId = (int)$StmtIns->fetchColumn();
            if ($InsId > 0) { $Pdo->prepare("UPDATE AlumnoInscripciones SET GrupoId = ?, Estado = 'INSCRITO' WHERE Id = ?")->execute([$GrupoId, $InsId]); }
            else { SgceAlumnoInscribirEnCiclo($Pdo, $Id, $CicloActivoAccionId, $GrupoId, 'INSCRITO'); }
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_ALUMNO', 'Alumnos', $Id, 'ALUMNO ACTUALIZADO EN CICLO ACTIVO');
            $_SESSION['Mensaje'] = "Alumno Actualizado";
        } catch (PDOException $Ex) { $_SESSION['Mensaje'] = "Error al actualizar alumno."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaAsignacion'])) {
        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');
        if ($MaestroId <= 0 || $GrupoId <= 0 || $Materia === '' || SgceLongitudTexto($Materia) > 140 || $CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Datos De Asignación Inválidos. La materia no debe superar 140 caracteres."; SgceRedirectAdminTab($TabPost, $UserSession); }
        if (!SgceMaestroExisteActivo($Pdo, $MaestroId) || !SgceGrupoExisteActivo($Pdo, $GrupoId)) { $_SESSION['Mensaje'] = "La asignación requiere docente y grupo activos del ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $StmtDuplicada = $Pdo->prepare("SELECT Id, Activo, MaestroId FROM Asignaciones WHERE CicloId = ? AND GrupoId = ? AND MateriaNombre = ? LIMIT 1");
            $StmtDuplicada->execute([$CicloActivoAccionId, $GrupoId, $Materia]);
            $Duplicada = $StmtDuplicada->fetch();
            if ($Duplicada) {
                if ((int)$Duplicada['Activo'] === 1) { $_SESSION['Mensaje'] = "Esa materia ya existe para ese grupo en el ciclo activo. Si cambió el profesor, edita la asignación y registra el relevo/interinato."; SgceRedirectAdminTab($TabPost, $UserSession); }
                $Pdo->prepare("UPDATE Asignaciones SET MaestroId = ?, Activo = 1 WHERE Id = ?")->execute([$MaestroId, (int)$Duplicada['Id']]);
                SgceRegistrarDocenteAsignacionActual($Pdo, (int)$Duplicada['Id'], $MaestroId, (int)($UserSession['Id'] ?? 0), 'RELEVO', 'REACTIVACIÓN DE ASIGNACIÓN');
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_ASIGNACION', 'Asignaciones', (int)$Duplicada['Id'], 'ASIGNACIÓN REACTIVADA EN CICLO ACTIVO');
                $_SESSION['Mensaje'] = "Asignación Reactivada";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            $MateriaId = SgceMateriaIdPorNombre($Pdo, $Materia);
            $Pdo->prepare("INSERT INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaId, MateriaNombre) VALUES (?, ?, ?, NULLIF(?,0), ?)")->execute([$CicloActivoAccionId, $MaestroId, $GrupoId, $MateriaId, $Materia]);
            $NuevaAsignacionId = (int)$Pdo->lastInsertId();
            SgceRegistrarDocenteAsignacionActual($Pdo, $NuevaAsignacionId, $MaestroId, (int)($UserSession['Id'] ?? 0), 'TITULAR', 'ALTA DE ASIGNACIÓN');
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_ASIGNACION', 'Asignaciones', $NuevaAsignacionId, 'MATERIA ASIGNADA EN CICLO ACTIVO');
            $_SESSION['Mensaje'] = "Materia Asignada";
        } catch (PDOException $Ex) { $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Esa asignación ya existe. Cambia la materia, el docente o el grupo." : "Error al asignar materia."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditAsignacion'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');
        $MotivoRelevo = trim((string)($_POST['MotivoRelevo'] ?? 'RELEVO DOCENTE / INTERINATO'));
        if ($Id <= 0 || $MaestroId <= 0 || $GrupoId <= 0 || $Materia === '' || SgceLongitudTexto($Materia) > 140 || $CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Datos De Asignación Inválidos. La materia no debe superar 140 caracteres."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $AsignacionActual = SgceAsignacionObtener($Pdo, $Id);
            if (!$AsignacionActual || (int)$AsignacionActual['CicloId'] !== $CicloActivoAccionId || (int)$AsignacionActual['Activo'] !== 1) {
                $_SESSION['Mensaje'] = "Solo puedes modificar asignaciones activas del ciclo actual.";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            if (!SgceMaestroExisteActivo($Pdo, $MaestroId) || !SgceGrupoExisteActivo($Pdo, $GrupoId)) { $_SESSION['Mensaje'] = "Selecciona un docente y un grupo activos del ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $TieneDatos = SgceAsignacionTieneDatosAcademicos($Pdo, $Id);
            $CambiaGrupoOMateria = ((int)$AsignacionActual['GrupoId'] !== $GrupoId) || ((string)$AsignacionActual['MateriaNombre'] !== $Materia);
            if ($TieneDatos && $CambiaGrupoOMateria) {
                $_SESSION['Mensaje'] = "Esta asignación ya tiene asistencias o calificaciones. Por seguridad no puedes cambiar materia ni grupo; solo puedes hacer relevo/interinato de docente.";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            $StmtDuplicada = $Pdo->prepare("SELECT Id FROM Asignaciones WHERE CicloId = ? AND GrupoId = ? AND MateriaNombre = ? AND Activo = 1 AND Id <> ? LIMIT 1");
            $StmtDuplicada->execute([$CicloActivoAccionId, $GrupoId, $Materia, $Id]);
            if ((int)$StmtDuplicada->fetchColumn() > 0) { $_SESSION['Mensaje'] = "Ya existe otra asignación activa para esa materia y grupo en este ciclo. Usa la asignación existente para hacer el relevo docente."; SgceRedirectAdminTab($TabPost, $UserSession); }

            $MateriaId = SgceMateriaIdPorNombre($Pdo, $Materia);
            if ((int)$AsignacionActual['MaestroId'] !== $MaestroId) {
                SgceRelevarDocenteAsignacion($Pdo, $Id, $MaestroId, (int)($UserSession['Id'] ?? 0), $MotivoRelevo !== '' ? $MotivoRelevo : 'RELEVO DOCENTE / INTERINATO');
            }
            if (!$TieneDatos) {
                $Pdo->prepare("UPDATE Asignaciones SET CicloId = ?, GrupoId = ?, MateriaId = NULLIF(?,0), MateriaNombre = ? WHERE Id = ? AND CicloId = ?")->execute([$CicloActivoAccionId, $GrupoId, $MateriaId, $Materia, $Id, $CicloActivoAccionId]);
                SgceRegistrarDocenteAsignacionActual($Pdo, $Id, $MaestroId, (int)($UserSession['Id'] ?? 0), 'TITULAR', 'ACTUALIZACIÓN DE ASIGNACIÓN SIN DATOS ACADÉMICOS');
                RegistrarBitacora($Pdo, $UserSession, 'EDITAR_ASIGNACION', 'Asignaciones', $Id, 'ASIGNACIÓN MODIFICADA SIN DATOS ACADÉMICOS');
                $_SESSION['Mensaje'] = "Asignación Modificada";
            } else {
                RegistrarBitacora($Pdo, $UserSession, 'RELEVO_DOCENTE_ASIGNACION', 'Asignaciones', $Id, 'RELEVO/INTERINATO SOBRE ASIGNACIÓN CON HISTORIAL ACADÉMICO');
                $_SESSION['Mensaje'] = "Relevo docente registrado sin alterar calificaciones ni asistencias.";
            }
        } catch (RuntimeException $Ex) { $_SESSION['Mensaje'] = $Ex->getMessage(); }
        catch (PDOException $Ex) { $_SESSION['Mensaje'] = "Error al modificar asignación."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }
}
