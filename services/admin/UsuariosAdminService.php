<?php
if (!defined('SGCE_APP') && php_sapi_name() !== 'cli') { http_response_code(403); exit('Acceso directo no permitido.'); }

function SgceUsuariosAdminPreparar(PDO $Pdo, array $UserSession): array {
    $Roles = SgceRolesSistema();
    $CicloActivoUsuarios = SgceCicloActivo($Pdo);
    $CicloActivoUsuariosId = (int)($CicloActivoUsuarios['Id'] ?? 0);
    $Mensaje = trim((string)($_GET['M'] ?? ''));
    $Error = '';


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        RequerirCsrfPost();
        $Accion = (string)($_POST['Accion'] ?? '');

        try {
            if ($Accion === 'CrearUsuario') {
                $NombreCompleto = SgceNormalizarTextoUsuarios($_POST['NombreCompleto'] ?? '');
                $Username = trim((string)($_POST['Username'] ?? ''));
                $Password = trim((string)($_POST['Password'] ?? ''));
                $Rol = SgceNormalizarRolSistema($_POST['Rol'] ?? '');

                if ($NombreCompleto === '' || SgceLongitudTexto($NombreCompleto) > 140 || $Username === '' || $Password === '' || !SgceValidarRolUsuario($Rol, $Roles)) {
                    throw new Exception('Completa nombre, usuario, contraseña y rol válido. El nombre no debe superar 140 caracteres.');
                }

                $ValidacionPassword = SgceValidarPasswordFuerte($Password);
                if ($ValidacionPassword !== true) { throw new Exception($ValidacionPassword); }

                if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $Username)) {
                    throw new Exception('El usuario debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @.');
                }

                $StmtExiste = $Pdo->prepare("SELECT Id, Rol, Activo FROM Usuarios WHERE Username = ? LIMIT 1");
                $StmtExiste->execute([$Username]);
                $UsuarioExistente = $StmtExiste->fetch();

                if ($UsuarioExistente) {
                    $UsuarioExistenteId = (int)$UsuarioExistente['Id'];

                    if ((int)$UsuarioExistente['Activo'] === 1) {
                        throw new Exception('Ya existe un usuario activo con ese nombre de acceso.');
                    }

                    if ((string)$UsuarioExistente['Rol'] === 'maestro' && $Rol !== 'maestro') {
                        $StmtAsignaciones = $Pdo->prepare("SELECT COUNT(*) FROM Asignaciones WHERE MaestroId = ? AND Activo = 1 AND (? = 0 OR CicloId = ?)");
                        $StmtAsignaciones->execute([$UsuarioExistenteId, $CicloActivoUsuariosId, $CicloActivoUsuariosId]);
                        if ((int)$StmtAsignaciones->fetchColumn() > 0) {
                            throw new Exception('Ese username pertenece a un docente inactivo con asignaciones. Reactívalo como maestro o reasigna primero sus materias.');
                        }
                    }

                    $Stmt = $Pdo->prepare("
                        UPDATE Usuarios
                        SET Password = ?, NombreCompleto = ?, NombreBusqueda = ?, Rol = ?, Activo = 1, SessionToken = NULL, SessionTokenExpira = NULL
                        WHERE Id = ?
                    " );
                    $Stmt->execute([SgcePasswordHash($Password), $NombreCompleto, SgceTextoBusquedaNormalizado($NombreCompleto), $Rol, $UsuarioExistenteId]);
                    RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_USUARIO', 'Usuarios', $UsuarioExistenteId, 'USUARIO REACTIVADO CON ROL: ' . strtoupper($Rol));
                    header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario reactivado correctamente'));
                    exit;
                }

                $Stmt = $Pdo->prepare("INSERT INTO Usuarios (Username, Password, NombreCompleto, NombreBusqueda, Rol, Activo, SessionToken) VALUES (?, ?, ?, ?, ?, 1, NULL)");
                $Stmt->execute([$Username, SgcePasswordHash($Password), $NombreCompleto, SgceTextoBusquedaNormalizado($NombreCompleto), $Rol]);
                RegistrarBitacora($Pdo, $UserSession, 'CREAR_USUARIO', 'Usuarios', (int)$Pdo->lastInsertId(), 'ALTA DE USUARIO CON ROL: ' . strtoupper($Rol));
                header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario creado correctamente'));
                exit;
            }

            if ($Accion === 'EditarUsuario') {
                $Id = (int)($_POST['Id'] ?? 0);
                $NombreCompleto = SgceNormalizarTextoUsuarios($_POST['NombreCompleto'] ?? '');
                $Username = trim((string)($_POST['Username'] ?? ''));
                $Password = trim((string)($_POST['Password'] ?? ''));
                $Rol = SgceNormalizarRolSistema($_POST['Rol'] ?? '');
                $Activo = isset($_POST['Activo']) ? 1 : 0;

                if ($Id <= 0 || $NombreCompleto === '' || SgceLongitudTexto($NombreCompleto) > 140 || $Username === '' || !SgceValidarRolUsuario($Rol, $Roles)) {
                    throw new Exception('Datos incompletos o rol inválido. El nombre no debe superar 140 caracteres.');
                }

                if ($Password !== '') {
                    $ValidacionPassword = SgceValidarPasswordFuerte($Password);
                    if ($ValidacionPassword !== true) { throw new Exception($ValidacionPassword); }
                }

                if (!preg_match('/^[a-zA-Z0-9._@-]{3,80}$/', $Username)) {
                    throw new Exception('El usuario debe tener de 3 a 80 caracteres y solo puede usar letras, números, punto, guion, guion bajo o @.');
                }

                $StmtActual = $Pdo->prepare("SELECT Id, Username, Rol, Activo FROM Usuarios WHERE Id = ? LIMIT 1");
                $StmtActual->execute([$Id]);
                $Actual = $StmtActual->fetch();
                if (!$Actual) { throw new Exception('El usuario no existe.'); }

                if ((int)$Id === (int)$UserSession['Id'] && ($Activo === 0 || $Rol !== 'admin')) {
                    throw new Exception('No puedes quitarte a ti mismo el acceso de administrador ni desactivarte.');
                }

                if ($Actual['Rol'] === 'admin' && ($Rol !== 'admin' || $Activo === 0) && SgceContarAdminsActivos($Pdo) <= 1) {
                    throw new Exception('Debe existir al menos un administrador activo.');
                }

                if ($Actual['Rol'] === 'maestro' && $Rol !== 'maestro') {
                    $StmtAsignaciones = $Pdo->prepare("SELECT COUNT(*) FROM Asignaciones WHERE MaestroId = ? AND Activo = 1 AND (? = 0 OR CicloId = ?)");
                    $StmtAsignaciones->execute([$Id, $CicloActivoUsuariosId, $CicloActivoUsuariosId]);
                    if ((int)$StmtAsignaciones->fetchColumn() > 0) {
                        throw new Exception('No puedes cambiar el rol de un docente con asignaciones activas. Primero desactiva o reasigna sus materias.');
                    }
                }

                $StmtExiste = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Username = ? AND Id <> ?");
                $StmtExiste->execute([$Username, $Id]);
                if ((int)$StmtExiste->fetchColumn() > 0) {
                    throw new Exception('Ya existe otro usuario con ese nombre de acceso.');
                }

                $DebeCerrarSesiones = ($Password !== '')
                    || ((string)$Actual['Username'] !== $Username)
                    || ((string)$Actual['Rol'] !== $Rol)
                    || ($Activo === 0);
                $SessionSql = $DebeCerrarSesiones ? ', SessionToken = NULL, SessionTokenExpira = NULL' : '';
                $PasswordSql = $Password !== '' ? ', Password = ?' : '';
                $Params = [$Username, $NombreCompleto, SgceTextoBusquedaNormalizado($NombreCompleto), $Rol, $Activo];
                if ($Password !== '') { $Params[] = SgcePasswordHash($Password); }
                $Params[] = $Id;
                $Stmt = $Pdo->prepare("UPDATE Usuarios SET Username = ?, NombreCompleto = ?, NombreBusqueda = ?, Rol = ?, Activo = ? $PasswordSql $SessionSql WHERE Id = ?");
                $Stmt->execute($Params);
                RegistrarBitacora($Pdo, $UserSession, 'EDITAR_USUARIO', 'Usuarios', $Id, 'EDICIÓN DE USUARIO. ROL: ' . strtoupper($Rol));
                header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario actualizado correctamente'));
                exit;
            }

            if ($Accion === 'DesactivarUsuario') {
                $Id = (int)($_POST['Id'] ?? 0);
                if ($Id <= 0) { throw new Exception('Usuario inválido.'); }
                if ((int)$Id === (int)$UserSession['Id']) { throw new Exception('No puedes desactivar tu propio usuario.'); }

                $StmtActual = $Pdo->prepare("SELECT Rol FROM Usuarios WHERE Id = ? LIMIT 1");
                $StmtActual->execute([$Id]);
                $RolActual = (string)$StmtActual->fetchColumn();
                if ($RolActual === '') { throw new Exception('El usuario no existe.'); }
                if ($RolActual === 'admin' && SgceContarAdminsActivos($Pdo) <= 1) {
                    throw new Exception('Debe existir al menos un administrador activo.');
                }

                $Stmt = $Pdo->prepare("UPDATE Usuarios SET Activo = 0, SessionToken = NULL, SessionTokenExpira = NULL WHERE Id = ?");
                $Stmt->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'DESACTIVAR_USUARIO', 'Usuarios', $Id, 'USUARIO DESACTIVADO DESDE MÓDULO DE USUARIOS');
                header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario desactivado correctamente'));
                exit;
            }

            if ($Accion === 'ReactivarUsuario') {
                $Id = (int)($_POST['Id'] ?? 0);
                if ($Id <= 0) { throw new Exception('Usuario inválido.'); }
                $Stmt = $Pdo->prepare("UPDATE Usuarios SET Activo = 1 WHERE Id = ?");
                $Stmt->execute([$Id]);
                RegistrarBitacora($Pdo, $UserSession, 'REACTIVAR_USUARIO', 'Usuarios', $Id, 'USUARIO REACTIVADO DESDE MÓDULO DE USUARIOS');
                header('Location: UsuariosAdmin.php?M=' . urlencode('Usuario reactivado correctamente'));
                exit;
            }
        } catch (Exception $E) {
            $Error = $E->getMessage();
        }
    }

    $FiltroRol = SgceNormalizarRolSistema($_GET['Rol'] ?? '');
    $FiltroEstado = trim((string)($_GET['Estado'] ?? 'activos'));
    $Buscar = trim((string)($_GET['Buscar'] ?? ''));
    $Pagina = SgcePaginaActual('PagUsuarios', 1);
    $PorPagina = 7;
    [$Offset, $Limit] = SgceLimitOffset($Pagina, $PorPagina);

    $Where = [];
    $Params = [];
    if ($FiltroRol !== '' && SgceValidarRolUsuario($FiltroRol, $Roles)) { $Where[] = 'Rol = ?'; $Params[] = $FiltroRol; }
    if ($FiltroEstado === 'activos') { $Where[] = 'Activo = 1'; }
    elseif ($FiltroEstado === 'inactivos') { $Where[] = 'Activo = 0'; }
    if ($Buscar !== '') {
        $Where[] = '(NombreCompleto LIKE ? OR Username LIKE ?)';
        $Params[] = '%' . $Buscar . '%';
        $Params[] = '%' . $Buscar . '%';
    }
    $WhereSql = $Where ? ('WHERE ' . implode(' AND ', $Where)) : '';

    $StmtTotal = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios $WhereSql");
    $StmtTotal->execute($Params);
    $TotalUsuarios = (int)$StmtTotal->fetchColumn();

    $Stmt = $Pdo->prepare("SELECT Id, Username, NombreCompleto, Rol, Activo FROM Usuarios $WhereSql ORDER BY Activo DESC, FIELD(Rol,'admin','administrativo','maestro'), NombreCompleto ASC LIMIT $Limit OFFSET $Offset");
    $Stmt->execute($Params);
    $Usuarios = $Stmt->fetchAll();

    $Stats = [];
    foreach ($Roles as $Key => $Label) {
        $St = $Pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Rol = ? AND Activo = 1");
        $St->execute([$Key]);
        $Stats[$Key] = (int)$St->fetchColumn();
    }

    return compact('Roles', 'CicloActivoUsuarios', 'CicloActivoUsuariosId', 'Mensaje', 'Error', 'FiltroRol', 'FiltroEstado', 'Buscar', 'Pagina', 'PorPagina', 'Offset', 'Limit', 'TotalUsuarios', 'Usuarios', 'Stats');
}
