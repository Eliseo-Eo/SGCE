<?php
if (!defined('SGCE_APP')) { http_response_code(403); exit('Acceso directo no permitido.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    RequerirCsrfPost();
    SgceExigirPermiso($UserSession, 'catalogos', 'No tienes permiso para modificar catálogos escolares.');

    $TabPost = SgceTabAdminPermitida($_POST['Tab'] ?? 'maestros', $UserSession);
    $_SESSION['Tab'] = $TabPost;

    if (isset($_POST['DelMaestro'])) {

        $Id = intval($_POST['DelMaestro']);

        if ($Id > 0) {

            try {
                $Pdo->prepare("UPDATE Usuarios SET Activo = 0, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ? AND Rol = 'maestro'")->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'BAJA_DOCENTE', 'Usuarios', $Id, 'DOCENTE DESACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Docente Desactivado";
            } catch (PDOException $Ex) {
                $_SESSION['Mensaje'] = "Error al eliminar docente.";
            }

            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

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

            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

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

            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

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

            SgceRedirectAdminTab($TabPost, $UserSession);
        }
    }

    if (isset($_POST['AltaMaestro'])) {

        $User = trim($_POST['User'] ?? '');
        $Pass = trim($_POST['Pass'] ?? '');
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');

        if ($User === '' || $Pass === '' || $Nombre === '') {
            $_SESSION['Mensaje'] = "Completa Todos Los Campos Del Docente. (Nombre solo letras)";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
        if (SgceLongitudTexto($Nombre) > 140) {
            $_SESSION['Mensaje'] = "El nombre del docente no debe superar 140 caracteres.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $User)) {
            $_SESSION['Mensaje'] = "El usuario del docente debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        $ValidacionPassword = SgceValidarPasswordFuerte($Pass);
        if ($ValidacionPassword !== true) {
            $_SESSION['Mensaje'] = $ValidacionPassword;
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        try {

            $StmtUsuarioExistente = $Pdo->prepare("SELECT Id, Rol, Activo FROM Usuarios WHERE Username = ? LIMIT 1");
            $StmtUsuarioExistente->execute([$User]);
            $UsuarioExistente = $StmtUsuarioExistente->fetch();

            if ($UsuarioExistente) {
                $UsuarioIdExistente = (int)$UsuarioExistente['Id'];

                if ((string)$UsuarioExistente['Rol'] !== 'maestro') {
                    $_SESSION['Mensaje'] = "Ese usuario ya existe con otro rol. Usa otro username.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }

                if ((int)$UsuarioExistente['Activo'] === 1) {
                    $_SESSION['Mensaje'] = "Ese docente ya está activo. Usa otro username.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }

                $Pdo->prepare("
                    UPDATE Usuarios
                    SET NombreCompleto = ?, Password = ?, Activo = 1, SessionToken = NULL, SessionTokenExpira = NULL
                    WHERE Id = ? AND Rol = 'maestro'
                ")->execute([$Nombre, SgcePasswordHash($Pass), $UsuarioIdExistente]);

                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_DOCENTE', 'Usuarios', $UsuarioIdExistente, 'DOCENTE REACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Docente Reactivado";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }

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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditMaestro'])) {

        $Id = intval($_POST['Id'] ?? 0);

        $User = trim($_POST['User'] ?? '');
        $Pass = trim($_POST['Pass'] ?? '');
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');

        if ($Id <= 0 || $User === '' || $Nombre === '') {
            $_SESSION['Mensaje'] = "Datos Del Docente Inválidos. (Nombre solo letras)";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }
        if (SgceLongitudTexto($Nombre) > 140) {
            $_SESSION['Mensaje'] = "El nombre del docente no debe superar 140 caracteres.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $User)) {
            $_SESSION['Mensaje'] = "El usuario del docente debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        if ($Pass !== '') {
            $ValidacionPassword = SgceValidarPasswordFuerte($Pass);
            if ($ValidacionPassword !== true) {
                $_SESSION['Mensaje'] = $ValidacionPassword;
                SgceRedirectAdminTab($TabPost, $UserSession);
            }
        }

        try {

            $SqlPassword = $Pass !== '' ? ', Password = ?' : '';
            $Params = [$Nombre, $User];
            if ($Pass !== '') { $Params[] = SgcePasswordHash($Pass); }
            $Params[] = $Id;
            $Pdo->prepare("
                UPDATE Usuarios
                SET NombreCompleto = ?, Username = ? $SqlPassword,
                    SessionToken = NULL,
                    SessionTokenExpira = NULL
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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaGrupo'])) {

        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');

        if (!SgceValidarGrado($Grado) || SgceLongitudTexto($Grado) > 20 || $Grupo === '' || $Turno === '') {
            $_SESSION['Mensaje'] = "Grupo Inválido: grado solo números, máximo 20 caracteres; grupo solo letras mayúsculas.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        try {

            $StmtGrupoExistente = $Pdo->prepare("SELECT Id, Activo FROM Grupos WHERE Grado = ? AND Grupo = ? AND Turno = ? LIMIT 1");
            $StmtGrupoExistente->execute([$Grado, $Grupo, $Turno]);
            $GrupoExistente = $StmtGrupoExistente->fetch();

            if ($GrupoExistente) {
                $GrupoIdExistente = (int)$GrupoExistente['Id'];

                if ((int)$GrupoExistente['Activo'] === 1) {
                    $_SESSION['Mensaje'] = "Ese grupo ya está activo.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }

                $Pdo->prepare("UPDATE Grupos SET Activo = 1 WHERE Id = ?")->execute([$GrupoIdExistente]);
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_GRUPO', 'Grupos', $GrupoIdExistente, 'GRUPO REACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Grupo Reactivado";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }

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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditGrupo'])) {

        $Id = intval($_POST['Id'] ?? 0);

        $Grado = trim($_POST['Grado'] ?? '');
        $Grupo = SgceNormalizarGrupo($_POST['Grupo'] ?? '');
        $Turno = SgceNormalizarTurno($_POST['Turno'] ?? '');

        if ($Id <= 0 || !SgceValidarGrado($Grado) || SgceLongitudTexto($Grado) > 20 || $Grupo === '' || $Turno === '') {
            $_SESSION['Mensaje'] = "Grupo Inválido: grado solo números, máximo 20 caracteres; grupo solo letras mayúsculas.";
            SgceRedirectAdminTab($TabPost, $UserSession);
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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaAlumno'])) {

        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        if ($Nombre === '' || SgceLongitudTexto($Nombre) > 160 || $GrupoId <= 0) {
            $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. Nombre solo letras, máximo 160 caracteres y selecciona grupo.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        try {

            $StmtAlumnoExistente = $Pdo->prepare("SELECT Id, Activo FROM Alumnos WHERE NombreCompleto = ? AND GrupoId = ? LIMIT 1");
            $StmtAlumnoExistente->execute([$Nombre, $GrupoId]);
            $AlumnoExistente = $StmtAlumnoExistente->fetch();

            if ($AlumnoExistente) {
                $AlumnoIdExistente = (int)$AlumnoExistente['Id'];

                if ((int)$AlumnoExistente['Activo'] === 1) {
                    $_SESSION['Mensaje'] = "Ese alumno ya está activo en el grupo seleccionado.";
                    SgceRedirectAdminTab($TabPost, $UserSession);
                }

                $Pdo->prepare("UPDATE Alumnos SET Activo = 1 WHERE Id = ?")->execute([$AlumnoIdExistente]);
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_ALUMNO', 'Alumnos', $AlumnoIdExistente, 'ALUMNO REACTIVADO DESDE ADMIN');
                $_SESSION['Mensaje'] = "Alumno Reactivado";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }

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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditAlumno'])) {

        $Id = intval($_POST['Id'] ?? 0);
        $Nombre = SgceNormalizarNombre($_POST['Nombre'] ?? '');
        $GrupoId = intval($_POST['GrupoId'] ?? 0);

        if ($Id <= 0 || $Nombre === '' || SgceLongitudTexto($Nombre) > 160 || $GrupoId <= 0) {
            $_SESSION['Mensaje'] = "Datos Del Alumno Inválidos. Nombre solo letras, máximo 160 caracteres y selecciona grupo.";
            SgceRedirectAdminTab($TabPost, $UserSession);
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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['AltaAsignacion'])) {

        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');

        if ($MaestroId <= 0 || $GrupoId <= 0 || $Materia === '' || SgceLongitudTexto($Materia) > 140) {
            $_SESSION['Mensaje'] = "Datos De Asignación Inválidos. La materia no debe superar 140 caracteres.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        if (!SgceMaestroExisteActivo($Pdo, $MaestroId) || !SgceGrupoExisteActivo($Pdo, $GrupoId)) {
            $_SESSION['Mensaje'] = "La asignación requiere docente y grupo activos.";
            SgceRedirectAdminTab($TabPost, $UserSession);
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
                SgceRedirectAdminTab($TabPost, $UserSession);
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
                SgceRedirectAdminTab($TabPost, $UserSession);
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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }

    if (isset($_POST['EditAsignacion'])) {

        $Id = intval($_POST['Id'] ?? 0);
        $MaestroId = intval($_POST['MaestroId'] ?? 0);
        $GrupoId = intval($_POST['GrupoId'] ?? 0);
        $Materia = SgceNormalizarMayusculas($_POST['Materia'] ?? '');

        if ($Id <= 0 || $MaestroId <= 0 || $GrupoId <= 0 || $Materia === '' || SgceLongitudTexto($Materia) > 140) {
            $_SESSION['Mensaje'] = "Datos De Asignación Inválidos. La materia no debe superar 140 caracteres.";
            SgceRedirectAdminTab($TabPost, $UserSession);
        }

        try {

            if (!SgceMaestroExisteActivo($Pdo, $MaestroId) || !SgceGrupoExisteActivo($Pdo, $GrupoId)) {
                $_SESSION['Mensaje'] = "Selecciona un docente y un grupo activos.";
                SgceRedirectAdminTab($TabPost, $UserSession);
            }

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
                SgceRedirectAdminTab($TabPost, $UserSession);
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

        SgceRedirectAdminTab($TabPost, $UserSession);
    }
}

