<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }
require_once __DIR__ . '/AdminAccionesServicios.php';

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
                    $_SESSION['Mensaje'] = "No se puede desactivar: El docente tiene $AsignacionesActuales asignación(es) activa(s) en el ciclo actual. Primero realiza el relevo/interinato a otro docente.";
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
                if (SgceGrupoTieneUsoAcademico($Pdo, $Id)) { $_SESSION['Mensaje'] = "No se puede desactivar un grupo con alumnos, asignaciones, asistencias o calificaciones. Conserva el historial o usa migración de ciclo."; SgceRedirectAdminTab($TabPost, $UserSession); }
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
                if ($CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "No hay ciclo activo para registrar la baja del alumno."; SgceRedirectAdminTab($TabPost, $UserSession); }
                $StmtBajaAlumno = $Pdo->prepare("UPDATE AlumnoInscripciones SET Estado = 'BAJA' WHERE AlumnoId = ? AND CicloId = ? AND Estado = 'INSCRITO'");
                $StmtBajaAlumno->execute([$Id, $CicloActivoAccionId]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_ALUMNO', 'AlumnoInscripciones', $Id, 'ALUMNO MARCADO COMO BAJA SOLO EN EL CICLO ACTIVO; HISTORIAL CONSERVADO');
                $_SESSION['Mensaje'] = "Alumno marcado como baja en el ciclo activo. Su historial se conserva.";
            } catch (PDOException $Ex) { $_SESSION['Mensaje'] = "Error al eliminar alumno."; }
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

    if (isset($_POST['DelMateriaGrupo'])) {
        $Id = intval($_POST['DelMateriaGrupo']);
        if ($Id > 0) {
            try {
                $MateriaGrupo = SgceMateriaGrupoObtener($Pdo, $Id);
                if (!$MateriaGrupo || (int)$MateriaGrupo['CicloId'] !== $CicloActivoAccionId) {
                    $_SESSION['Mensaje'] = "Solo puedes desactivar materias del ciclo activo.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }
                if (SgceMateriaGrupoTieneAsignacion($Pdo, $Id)) {
                    $_SESSION['Mensaje'] = "No se puede desactivar esta materia porque ya tiene docente asignado. Primero desasigna la carga académica si aún no tiene calificaciones ni asistencias.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }
                if (SgceMateriaGrupoTieneDatosAcademicos($Pdo, $Id)) {
                    $_SESSION['Mensaje'] = "No se puede desactivar esta materia porque ya tiene historial académico.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }
                $Pdo->prepare('UPDATE MateriasGrupo SET Activo = 0 WHERE Id = ? AND CicloId = ?')->execute([$Id, $CicloActivoAccionId]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_MATERIA_GRUPO', 'MateriasGrupo', $Id, 'MATERIA DE GRUPO DESACTIVADA SIN HISTORIAL ACADÉMICO');
                $_SESSION['Mensaje'] = "Materia desactivada del grupo.";
            } catch (PDOException $Ex) { $_SESSION['Mensaje'] = "Error al desactivar materia."; }
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
                $Pdo->beginTransaction();
                $Pdo->prepare("UPDATE Asignaciones SET Activo = 0 WHERE Id = ? AND CicloId = ?")->execute([$Id, $CicloActivoAccionId]);
                $Pdo->prepare('UPDATE AsignacionDocenteHistorial SET FechaFin = NOW() WHERE AsignacionId = ? AND FechaFin IS NULL')->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_ASIGNACION', 'Asignaciones', $Id, 'ASIGNACIÓN SIN DATOS ACADÉMICOS DESACTIVADA');
                $Pdo->commit();
                $_SESSION['Mensaje'] = "Materia Desasignada";
            } catch (PDOException $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = "Error al desasignar materia."; }
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
                $Pdo->prepare("UPDATE Usuarios SET NombreCompleto = ?, NombreBusqueda = ?, Password = ?, Activo = 1, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'")->execute([$Nombre, SgceTextoBusquedaNormalizado($Nombre), SgcePasswordHash($Pass), $UsuarioIdExistente]);
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_DOCENTE', 'Usuarios', $UsuarioIdExistente, 'DOCENTE REACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Docente Reactivado";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol) VALUES (?, ?, ?, ?, 'maestro')")->execute([$User, SgcePasswordHash($Pass), $Nombre, SgceTextoBusquedaNormalizado($Nombre)]);
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
            $Params = [$Nombre, SgceTextoBusquedaNormalizado($Nombre), $User];
            if ($Pass !== '') { $Params[] = SgcePasswordHash($Pass); }
            $Params[] = $Id;
            $Pdo->prepare("UPDATE Usuarios SET NombreCompleto = ?, NombreBusqueda = ?, Username = ? $SqlPassword, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'")->execute($Params);
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_DOCENTE', 'Usuarios', $Id, 'DOCENTE ACTUALIZADO');
            $_SESSION['Mensaje'] = "Docente Actualizado";
        } catch (PDOException $Ex) { $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Ese usuario ya existe. Usa otro username." : "Error al actualizar docente."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaGrupo'])) {
        $Oferta = SgceOfertaActiva($Pdo);
        $OfertaId = (int)($Oferta['Id'] ?? 0);
        $EtapaId = intval($_POST['EtapaId'] ?? 0);
        $ProgramaId = intval($_POST['ProgramaId'] ?? 0);
        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');
        if ($CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Primero configura un ciclo escolar activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        if ($OfertaId > 0) {
            $Etapa = SgceEtapaAcademicaPorId($Pdo, $EtapaId);
            if (!$Etapa || (int)$Etapa['OfertaId'] !== $OfertaId) { $_SESSION['Mensaje'] = "Selecciona una etapa académica válida."; SgceRedirectAdminTab($TabPost, $UserSession); }
            if (!empty($Oferta['UsaProgramas'])) {
                $Programa = SgceProgramaPorId($Pdo, $ProgramaId);
                if (!$Programa || (int)$Programa['Activo'] !== 1) { $_SESSION['Mensaje'] = "Selecciona un programa educativo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            } else { $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaId); }
            $Grado = SgceEtapaNombreVisual($Etapa, (string)($Oferta['TipoPeriodizacion'] ?? 'ANUAL'));
        }
        if (!SgceValidarGrado($Grado) || SgceLongitudTexto($Grado) > 40 || $Grupo === '' || $Turno === '') { $_SESSION['Mensaje'] = "Grupo inválido: Selecciona etapa académica, grupo y turno."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $Pdo->beginTransaction();
            $GrupoId = SgceGrupoCrearOReactivar($Pdo, $CicloActivoAccionId, $Grado, $Grupo, $Turno, $EtapaId, $ProgramaId, $OfertaId);
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_GRUPO', 'Grupos', $GrupoId, 'GRUPO CREADO/REACTIVADO EN CICLO ACTIVO CON ESTRUCTURA ACADÉMICA');
            $Pdo->commit();
            $_SESSION['Mensaje'] = "Grupo Creado";
        } catch (Throwable $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = $Ex instanceof PDOException && $Ex->getCode() === '23000' ? "Ese grupo ya existe en el ciclo activo." : "Error al crear grupo: " . $Ex->getMessage(); }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditGrupo'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $Oferta = SgceOfertaActiva($Pdo);
        $OfertaId = (int)($Oferta['Id'] ?? 0);
        $EtapaId = intval($_POST['EtapaId'] ?? 0);
        $ProgramaId = intval($_POST['ProgramaId'] ?? 0);
        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');
        if ($OfertaId > 0) {
            $Etapa = SgceEtapaAcademicaPorId($Pdo, $EtapaId);
            if (!$Etapa || (int)$Etapa['OfertaId'] !== $OfertaId) { $_SESSION['Mensaje'] = "Selecciona una etapa académica válida."; SgceRedirectAdminTab($TabPost, $UserSession); }
            if (!empty($Oferta['UsaProgramas'])) {
                $Programa = SgceProgramaPorId($Pdo, $ProgramaId);
                if (!$Programa || (int)$Programa['Activo'] !== 1) { $_SESSION['Mensaje'] = "Selecciona un programa educativo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            } else { $ProgramaId = SgceProgramaGeneralId($Pdo, $OfertaId); }
            $Grado = SgceEtapaNombreVisual($Etapa, (string)($Oferta['TipoPeriodizacion'] ?? 'ANUAL'));
        }
        if ($Id <= 0 || !SgceValidarGrado($Grado) || SgceLongitudTexto($Grado) > 40 || $Grupo === '' || $Turno === '') { $_SESSION['Mensaje'] = "Grupo inválido: Selecciona etapa académica, grupo y turno."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            if (!SgceGrupoObtenerActivoPorId($Pdo, $Id)) { $_SESSION['Mensaje'] = "Solo puedes editar grupos del ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $GrupoActual = SgceGrupoObtenerPorId($Pdo, $Id);
            $CambiaEstructuraGrupo = $GrupoActual && ((int)$GrupoActual['OfertaId'] !== $OfertaId || (int)$GrupoActual['ProgramaId'] !== $ProgramaId || (int)$GrupoActual['EtapaId'] !== $EtapaId || (string)$GrupoActual['Grupo'] !== $Grupo || (string)$GrupoActual['Turno'] !== $Turno);
            if ($CambiaEstructuraGrupo && SgceGrupoTieneUsoAcademico($Pdo, $Id)) {
                $_SESSION['Mensaje'] = "Este grupo ya tiene alumnos, asignaciones, asistencias o calificaciones. Por seguridad no puedes cambiar su estructura; crea otro grupo o usa migración de ciclo.";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            $Pdo->beginTransaction();
            $Pdo->prepare("UPDATE Grupos SET OfertaId = ?, ProgramaId = ?, EtapaId = ?, Grado = ?, Grupo = ?, Turno = ? WHERE Id = ? AND CicloId = ?")->execute([$OfertaId, $ProgramaId, $EtapaId, $Grado, $Grupo, $Turno, $Id, $CicloActivoAccionId]);
            $Pdo->prepare("UPDATE AlumnoInscripciones SET OfertaId = ?, ProgramaId = ?, EtapaId = ? WHERE GrupoId = ? AND CicloId = ?")->execute([$OfertaId, $ProgramaId, $EtapaId, $Id, $CicloActivoAccionId]);
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_GRUPO', 'Grupos', $Id, 'GRUPO ACTUALIZADO CON ESTRUCTURA ACADÉMICA');
            $Pdo->commit();
            $_SESSION['Mensaje'] = "Grupo Actualizado";
        } catch (PDOException $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Ese grupo ya existe en el ciclo activo." : "Error al actualizar grupo."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaAlumno'])) {
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $MatriculaManual = SgceAdminNormalizarMatricula($_POST['Matricula'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        if ($Nombre === '' || SgceLongitudTexto($Nombre) > 160 || $GrupoId <= 0 || $CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. Nombre solo letras, máximo 160 caracteres, ciclo activo y grupo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $GrupoSel = SgceGrupoObtenerActivoPorId($Pdo, $GrupoId);
            if (!$GrupoSel) { $_SESSION['Mensaje'] = "El grupo seleccionado debe pertenecer al ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $StmtAlumnoExistente = $Pdo->prepare("SELECT A.Id, A.Activo FROM Alumnos A INNER JOIN AlumnoInscripciones AI ON AI.AlumnoId = A.Id WHERE A.NombreCompleto = ? AND AI.CicloId = ? AND AI.GrupoId = ? AND AI.Estado = 'INSCRITO' LIMIT 1");
            $StmtAlumnoExistente->execute([$Nombre, $CicloActivoAccionId, $GrupoId]);
            $AlumnoExistente = $StmtAlumnoExistente->fetch();
            if ($AlumnoExistente) { $_SESSION['Mensaje'] = "Ese alumno ya está inscrito en el grupo seleccionado para el ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }

            $Pdo->beginTransaction();
            $StmtAlumnoMismoNombre = $Pdo->prepare("SELECT Id, Activo FROM Alumnos WHERE NombreCompleto = ? AND GrupoId = ? LIMIT 1");
            $StmtAlumnoMismoNombre->execute([$Nombre, $GrupoId]);
            $AlumnoBase = $StmtAlumnoMismoNombre->fetch();
            if ($AlumnoBase) {
                $AlumnoId = (int)$AlumnoBase['Id'];
                $Pdo->prepare("UPDATE Alumnos SET Activo = 1, NombreBusqueda = ?, Matricula = COALESCE(NULLIF(?, ''), Matricula) WHERE Id = ?")->execute([SgceTextoBusquedaNormalizado($Nombre), $MatriculaManual, $AlumnoId]);
            } else {
                $Pdo->prepare("INSERT INTO Alumnos (NombreCompleto, NombreBusqueda, Matricula, GrupoId, Activo) VALUES (?, ?, NULLIF(?, ''), ?, 1)")->execute([$Nombre, SgceTextoBusquedaNormalizado($Nombre), $MatriculaManual, $GrupoId]);
                $AlumnoId = (int)$Pdo->lastInsertId();
            }
            SgceAsignarMatriculaSiAplica($Pdo, $AlumnoId, $CicloActivoAccionId);
            if (!SgceAlumnoInscribirEnCiclo($Pdo, $AlumnoId, $CicloActivoAccionId, $GrupoId, 'INSCRITO')) { throw new RuntimeException('No se pudo crear la inscripción del alumno en el ciclo activo.'); }
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_ALUMNO', 'Alumnos', $AlumnoId, 'ALUMNO INSCRITO EN CICLO ACTIVO');
            $Pdo->commit();
            $_SESSION['Mensaje'] = "Alumno Inscrito";
        } catch (Throwable $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = $Ex instanceof PDOException && $Ex->getCode() === '23000' ? "No se pudo inscribir: Ya existe una inscripción para este ciclo." : "Error al inscribir alumno."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditAlumno'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $MatriculaManual = SgceAdminNormalizarMatricula($_POST['Matricula'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        if ($Id <= 0 || $Nombre === '' || SgceLongitudTexto($Nombre) > 160 || $GrupoId <= 0 || $CicloActivoAccionId <= 0) { $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. Nombre solo letras, máximo 160 caracteres y selecciona grupo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $GrupoMeta = SgceGrupoObtenerActivoPorId($Pdo, $GrupoId);
            if (!$GrupoMeta) { $_SESSION['Mensaje'] = "El grupo debe pertenecer al ciclo activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $Pdo->beginTransaction();
            $Pdo->prepare("UPDATE Alumnos SET NombreCompleto = ?, NombreBusqueda = ?, Matricula = COALESCE(NULLIF(?, ''), Matricula), GrupoId = ?, Activo = 1 WHERE Id = ?")->execute([$Nombre, SgceTextoBusquedaNormalizado($Nombre), $MatriculaManual, $GrupoId, $Id]);
            SgceAsignarMatriculaSiAplica($Pdo, $Id, $CicloActivoAccionId);
            $StmtIns = $Pdo->prepare("SELECT Id FROM AlumnoInscripciones WHERE AlumnoId = ? AND CicloId = ? LIMIT 1");
            $StmtIns->execute([$Id, $CicloActivoAccionId]);
            $InsId = (int)$StmtIns->fetchColumn();
            if ($InsId > 0) { $Pdo->prepare("UPDATE AlumnoInscripciones SET GrupoId = ?, OfertaId = ?, ProgramaId = ?, EtapaId = ?, Estado = 'INSCRITO' WHERE Id = ?")->execute([$GrupoId, (int)$GrupoMeta['OfertaId'], (int)$GrupoMeta['ProgramaId'], (int)$GrupoMeta['EtapaId'], $InsId]); }
            else { if (!SgceAlumnoInscribirEnCiclo($Pdo, $Id, $CicloActivoAccionId, $GrupoId, 'INSCRITO')) { throw new RuntimeException('No se pudo crear la inscripción del alumno en el ciclo activo.'); } }
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_ALUMNO', 'Alumnos', $Id, 'ALUMNO ACTUALIZADO EN CICLO ACTIVO');
            $Pdo->commit();
            $_SESSION['Mensaje'] = "Alumno Actualizado";
        } catch (Throwable $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = "Error al actualizar alumno."; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaMateriaGrupo'])) {
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');
        $HorasSemana = SgceHorasMateriaSeguro($_POST['HorasSemana'] ?? 0);
        if ($GrupoId <= 0 || $Materia === '' || SgceLongitudTexto($Materia) > 140 || $HorasSemana <= 0 || $CicloActivoAccionId <= 0) {
            $_SESSION['Mensaje'] = "Datos de materia inválidos. Captura grupo, materia y horas de 1 a 40.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
        try {
            $MateriaGrupoId = SgceMateriaGrupoCrearOReactivar($Pdo, $GrupoId, $Materia, $HorasSemana, $CicloActivoAccionId);
            RegistrarBitacora($Pdo, $UserSession, 'ALTA_MATERIA_GRUPO', 'MateriasGrupo', $MateriaGrupoId, 'MATERIA REGISTRADA PARA GRUPO CON HORAS SEMANALES');
            $_SESSION['Mensaje'] = "Materia registrada para el grupo.";
        } catch (RuntimeException $Ex) { $_SESSION['Mensaje'] = $Ex->getMessage(); $_SESSION['MensajeTipo'] = 'danger'; }
        catch (PDOException $Ex) { $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Esa materia ya existe para ese grupo." : "Error al registrar materia."; $_SESSION['MensajeTipo'] = 'danger'; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditMateriaGrupo'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');
        $HorasSemana = SgceHorasMateriaSeguro($_POST['HorasSemana'] ?? 0);
        if ($Id <= 0 || $GrupoId <= 0 || $Materia === '' || SgceLongitudTexto($Materia) > 140 || $HorasSemana <= 0 || $CicloActivoAccionId <= 0) {
            $_SESSION['Mensaje'] = "Datos de materia inválidos.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
        try {
            SgceMateriaGrupoActualizarSeguro($Pdo, $Id, $GrupoId, $Materia, $HorasSemana, $CicloActivoAccionId, $UserSession);
            RegistrarBitacora($Pdo, $UserSession, 'EDITAR_MATERIA_GRUPO', 'MateriasGrupo', $Id, 'MATERIA DE GRUPO ACTUALIZADA CON BLOQUEO TRANSACCIONAL');
            $_SESSION['Mensaje'] = "Materia actualizada.";
        } catch (PDOException $Ex) {
            $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Esa materia ya existe para ese grupo." : "Error al actualizar materia.";
            $_SESSION['MensajeTipo'] = 'danger';
        } catch (RuntimeException $Ex) {
            $_SESSION['Mensaje'] = $Ex->getMessage();
            $_SESSION['MensajeTipo'] = 'danger';
        }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaAsignacion'])) {
        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $MateriaGrupoId = intval($_POST['MateriaGrupoId'] ?? 0);
        if ($MaestroId <= 0 || $MateriaGrupoId <= 0 || $CicloActivoAccionId <= 0) {
            $_SESSION['Mensaje'] = "Selecciona docente y materia disponible.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
        if (!SgceMaestroExisteActivo($Pdo, $MaestroId)) { $_SESSION['Mensaje'] = "El docente debe estar activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
        try {
            $MateriaGrupo = SgceMateriaGrupoObtener($Pdo, $MateriaGrupoId);
            if (!$MateriaGrupo || (int)$MateriaGrupo['CicloId'] !== $CicloActivoAccionId || (int)$MateriaGrupo['Activo'] !== 1) {
                $_SESSION['Mensaje'] = "La materia seleccionada debe pertenecer al ciclo activo.";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            $StmtAsignacionExistente = $Pdo->prepare('SELECT Id, Activo FROM Asignaciones WHERE MateriaGrupoId = ? LIMIT 1');
            $StmtAsignacionExistente->execute([$MateriaGrupoId]);
            $AsignacionExistente = $StmtAsignacionExistente->fetch();
            if ($AsignacionExistente && (int)$AsignacionExistente['Activo'] === 1) {
                $_SESSION['Mensaje'] = "Esa materia ya tiene docente asignado para ese grupo. Usa editar para relevo/interinato.";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            $Pdo->beginTransaction();
            if ($AsignacionExistente) {
                $NuevaAsignacionId = (int)$AsignacionExistente['Id'];
                $Pdo->prepare('UPDATE Asignaciones SET MaestroId = ?, CicloId = ?, GrupoId = ?, MateriaId = ?, MateriaNombre = ?, MateriaBusqueda = ?, HorasSemana = ?, Activo = 1 WHERE Id = ?')
                    ->execute([$MaestroId, $CicloActivoAccionId, (int)$MateriaGrupo['GrupoId'], (int)$MateriaGrupo['MateriaId'], (string)$MateriaGrupo['MateriaNombre'], SgceTextoBusquedaNormalizado((string)$MateriaGrupo['MateriaNombre']), (int)$MateriaGrupo['HorasSemana'], $NuevaAsignacionId]);
                SgceRegistrarDocenteAsignacionActual($Pdo, $NuevaAsignacionId, $MaestroId, (int)($UserSession['Id'] ?? 0), 'TITULAR', 'REACTIVACIÓN DE ASIGNACIÓN DESDE MATERIA DISPONIBLE');
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_ASIGNACION', 'Asignaciones', $NuevaAsignacionId, 'DOCENTE VINCULADO A MATERIA DE GRUPO REACTIVADA');
                $Pdo->commit();
                $_SESSION['Mensaje'] = "Asignación reactivada.";
            } else {
                $Pdo->prepare('INSERT INTO Asignaciones (CicloId, MaestroId, GrupoId, MateriaGrupoId, MateriaId, MateriaNombre, MateriaBusqueda, HorasSemana, Activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)')
                    ->execute([$CicloActivoAccionId, $MaestroId, (int)$MateriaGrupo['GrupoId'], $MateriaGrupoId, (int)$MateriaGrupo['MateriaId'], (string)$MateriaGrupo['MateriaNombre'], SgceTextoBusquedaNormalizado((string)$MateriaGrupo['MateriaNombre']), (int)$MateriaGrupo['HorasSemana']]);
                $NuevaAsignacionId = (int)$Pdo->lastInsertId();
                SgceRegistrarDocenteAsignacionActual($Pdo, $NuevaAsignacionId, $MaestroId, (int)($UserSession['Id'] ?? 0), 'TITULAR', 'ALTA DE ASIGNACIÓN DESDE MATERIA DISPONIBLE');
                RegistrarBitacora($Pdo, $UserSession, 'ALTA_ASIGNACION', 'Asignaciones', $NuevaAsignacionId, 'DOCENTE VINCULADO A MATERIA DE GRUPO');
                $Pdo->commit();
                $_SESSION['Mensaje'] = "Docente asignado a la materia.";
            }
        } catch (PDOException $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = $Ex->getCode() === '23000' ? "Esa materia ya tiene asignación activa." : "Error al asignar materia."; $_SESSION['MensajeTipo'] = 'danger'; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditAsignacion'])) {
        $Id = intval($_POST['Id'] ?? 0);
        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $MotivoRelevo = trim((string)($_POST['MotivoRelevo'] ?? 'RELEVO DOCENTE / INTERINATO'));
        if ($Id <= 0 || $MaestroId <= 0 || $CicloActivoAccionId <= 0) {
            $_SESSION['Mensaje'] = "Datos de asignación inválidos.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
        try {
            $AsignacionActual = SgceAsignacionObtener($Pdo, $Id);
            if (!$AsignacionActual || (int)$AsignacionActual['CicloId'] !== $CicloActivoAccionId || (int)$AsignacionActual['Activo'] !== 1) {
                $_SESSION['Mensaje'] = "Solo puedes modificar asignaciones activas del ciclo actual.";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
            if (!SgceMaestroExisteActivo($Pdo, $MaestroId)) { $_SESSION['Mensaje'] = "Selecciona un docente activo."; SgceRedirectAdminTab($TabPost, $UserSession); }
            $Pdo->beginTransaction();
            if ((int)$AsignacionActual['MaestroId'] !== $MaestroId) {
                SgceRelevarDocenteAsignacion($Pdo, $Id, $MaestroId, (int)($UserSession['Id'] ?? 0), $MotivoRelevo !== '' ? $MotivoRelevo : 'RELEVO DOCENTE / INTERINATO');
                RegistrarBitacora($Pdo, $UserSession, 'RELEVO_DOCENTE_ASIGNACION', 'Asignaciones', $Id, 'RELEVO/INTERINATO SOBRE MATERIA DE GRUPO');
                $Pdo->commit();
                $_SESSION['Mensaje'] = "Relevo docente registrado.";
            } else {
                SgceRegistrarDocenteAsignacionActual($Pdo, $Id, $MaestroId, (int)($UserSession['Id'] ?? 0), 'TITULAR', 'CONFIRMACIÓN DE DOCENTE ACTUAL');
                $Pdo->commit();
                $_SESSION['Mensaje'] = "Asignación sin cambios estructurales.";
            }
        } catch (RuntimeException $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = $Ex->getMessage(); $_SESSION['MensajeTipo'] = 'danger'; }
        catch (PDOException $Ex) { if ($Pdo->inTransaction()) { $Pdo->rollBack(); } $_SESSION['Mensaje'] = "Error al modificar asignación."; $_SESSION['MensajeTipo'] = 'danger'; }
        SgceRedirectAdminTab($TabPost, $UserSession);
    }
}
